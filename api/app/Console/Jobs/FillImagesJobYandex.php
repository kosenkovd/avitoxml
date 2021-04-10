<?php
    
    
    namespace App\Console\Jobs;
    
    use App\Configuration\Spreadsheet\SheetNames;
    use App\Configuration\XmlGeneration;
    use App\Helpers\LinkHelper;
    use App\Helpers\SpreadsheetHelper;
    use App\Models\Table;
    use App\Models\TableHeader;
    use App\Repositories\Interfaces\ITableRepository;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use App\Services\Interfaces\IYandexDiskService;
    use Illuminate\Support\Facades\Log;
    use Ramsey\Uuid\Guid\Guid;
    
    class FillImagesJobYandex extends JobBase {
        private static string $rootFolder = 'Autoz';
        private static string $folderWithImages = 'Папки_с_фото';
        private static string $folderWithFolders = 'Генератор_папок';
        
        protected IYandexDiskService $yandexDiskService;
        protected SheetNames $sheetNamesConfig;
        private ITableRepository $tableRepository;
        private XmlGeneration $xmlGeneration;
        
        protected int $maxJobTime = 60 * 5;
        protected bool $loggingEnabled = true;
        protected bool $timeoutEnabled = true;
        
        private int $imagesColumn = 0;
        private int $subFolderColumn = 1;
        private int $errorNumColumn = 0;
        private array $images = [];
        private array $errors = [];
        private array $newValues = [];
        private bool $needsToUpdate = false;
        
        public function __construct(
            ISpreadsheetClientService $spreadsheetClientService,
            IYandexDiskService $yandexDiskService,
            ITableRepository $tableRepository,
            XmlGeneration $xmlGeneration)
        {
            parent::__construct($spreadsheetClientService);
            $this->yandexDiskService = $yandexDiskService;
            $this->tableRepository = $tableRepository;
            $this->sheetNamesConfig = new SheetNames();
            $this->xmlGeneration = $xmlGeneration;
        }
        
        /**
         * Start job.
         *
         * Fills images for all tables that were not filled before.
         *
         * @param Table $table table to process.
         * @throws \Exception
         */
        public function start(Table $table): void
        {
            $message = "Table '".$table->getGoogleSheetId()."' processing...";
            $this->log($message);
            Log::info($message);
            
            $this->startTimestamp = time();
            
            if (!$this->init($table)) {
                $message = "Table '".$table->getGoogleSheetId()."' no yandex token found, finished.";
                $this->log($message);
                Log::info($message);
                return;
            }
            
            $existingSheets = $this->spreadsheetClientService->getSheets(
                $table->getGoogleSheetId()
            );
            
            $this->ensureCreatedRootFolders();
            
            foreach ($table->getGenerators() as $generator) {
                switch ($generator->getTargetPlatform()) {
                    case "Avito":
                        $targetSheets = $this->xmlGeneration->getAvitoTabs();
                        break;
                    case "Юла":
                        $targetSheets = $this->xmlGeneration->getYoulaTabs();
                        break;
                    case "Яндекс":
                        $targetSheets = $this->xmlGeneration->getYandexTabs();
                        break;
                }
                
                $splitTargetSheets = explode(",", $targetSheets);
                foreach ($splitTargetSheets as $targetSheet) {
                    
                    $targetSheet = trim($targetSheet);
                    if (!in_array($targetSheet, $existingSheets)) {
                        continue;
                    }
                    
                    $message = "Table '".$table->getGoogleSheetId()."' processing sheet '".$targetSheet."'...";
                    $this->log($message);
                    Log::info($message);
                    
                    $this->processSheet(
                        $table,
                        $targetSheet
                    );
                    
                    if ($this->checkIsTimeout()) {
                        Log::alert("Table '".$table->getGoogleSheetId()."' finished by timeout.");
                        $table->setDateLastModified(0);
                        $this->tableRepository->update($table);
                        break;
                    }
                }
            }
            
            $message = "Table '".$table->getGoogleSheetId()."' finished.";
            $this->log($message);
            Log::info($message);
        }
        
        /**
         * Tries to init yandex disk service for table.
         *
         * @param Table $table
         * @return bool is init successful.
         * @throws \Exception
         */
        private function init(Table $table): bool
        {
            $yandexTokenCell = 'D7';
            
            $range = $this->sheetNamesConfig->getInformation().'!'.$yandexTokenCell.':'.$yandexTokenCell;
            
            try {
                $yandexConfig = $this->spreadsheetClientService->getSpreadsheetCellsRange(
                    $table->getGoogleSheetId(),
                    $range,
                    $table->getTableGuid()."fijy");
            } catch (\Exception $exception) {
                $message = "Error on '".$table->getGoogleDriveId()."' while getting yandex token ".PHP_EOL.
                    $exception->getMessage();
                $this->log($message);
                Log::error($message);
                
                throw $exception;
            }
            
            $yandexToken = $yandexConfig ? @$yandexConfig[0][0] : null;
            
            // If there is a token in spreadsheet, renew token in database and remove token from spreadsheet
            if (!is_null($yandexToken) && ($yandexToken !== '')) {
                $this->tableRepository->updateYandexToken($table->getTableId(), $yandexToken);
                $table->setYandexToken($yandexToken);
                $this->spreadsheetClientService->updateCellContent(
                    $table->getGoogleSheetId(),
                    $this->sheetNamesConfig->getInformation(),
                    $yandexTokenCell,
                    "",
                    $table->getTableGuid()."figt");
            }
            
            if (is_null($table->getYandexToken()) || $table->getYandexToken() == "") {
                return false;
            }
            
            $this->yandexDiskService->init($table->getYandexToken());
            
            return true;
        }
    
        /**
         * Fills images for specified generator.
         *
         * @param Table $table
         * @param string $sheetName target sheet.
         * @throws \Exception
         */
        private function processSheet(
            Table $table,
            string $sheetName
        ): void
        {
            $tableGuid = $table->getTableGuid();
            $tableId = $table->getGoogleSheetId();
            
            $values = $this->getFullDataFromTable($tableId, $sheetName);
            $propertyColumns = new TableHeader(array_shift($values));
            
            $this->newValues = [];
            
            if ($propertyColumns && empty($values)) {
                return;
            }
            
             if (is_null($propertyColumns->imagesRaw)) {
                Log::error("no images column");
                return;
            }
            
            foreach ($values as $numRow => $row) {
                if ($this->checkIsTimeout()) {
                    $table->setDateLastModified(0);
                    $this->tableRepository->update($table);
                    Log::alert("Table '".$tableId."' finished by timeout.");
                    break;
                }
                
                $this->setEmptyNewValuesForRow($numRow);
                $this->errors = [];
                
                if ($this->isExistsInRow($row, $propertyColumns->imagesRaw)) {
                    $this->fillNewValueWithContent(
                        $numRow,
                        $this->imagesColumn,
                        trim($row[$propertyColumns->imagesRaw])
                    );
                    if ($this->isExistsInRow($row, $propertyColumns->subFolderName)) {
                        $this->fillNewValueWithContent(
                            $numRow,
                            $this->subFolderColumn,
                            trim($row[$propertyColumns->subFolderName])
                        );
                    }
                    
                    continue;
                }
                
                if (!$this->canFillImages($row, $propertyColumns)) {
                    continue;
                }
                
                // content starts at line 2
                $spreadsheetRowNum = $numRow + 2;
                
                $message = "Table '".$tableId."' start filling row ".$spreadsheetRowNum;
                $this->log($message);
                Log::info($message);
                
                $this->needsToUpdate = true;
                
                if (!$this->isExistsInRow($row, $propertyColumns->subFolderName)) {
                    $subFolderName = $this->createSubFolderWithContent($row, $propertyColumns);
                    
                    if (is_null($subFolderName)) {
                        // Errors found while creating
                        if (!$this->hasErrors()) {
                            $this->errors[] = 'Неизвестная ошибка';
                        }
                        $this->fillNewValueWithErrors($numRow);
                        
                        continue;
                    } else {
                        $this->fillNewValueWithContent(
                            $numRow,
                            $this->subFolderColumn,
                            $subFolderName
                        );
                        $subFolderPath = '/'.self::$rootFolder.'/'.self::$folderWithImages.'/'.$subFolderName.'/';
                    }
                    
                    $message = "Table '".$tableId."' folder name - ".$subFolderName;
                    $this->log($message);
                    Log::info($message);
                } else {
                    $subFolderName = trim($row[$propertyColumns->subFolderName]);
                    $this->fillNewValueWithContent(
                        $numRow,
                        $this->subFolderColumn,
                        $subFolderName
                    );
                    
                    $message = "Table '".$tableId."' folder '".$subFolderName."' already in row";
                    $this->log($message);
                    Log::info($message);
                    
                    $subFolderPath = $this->checkAndGetFolder($subFolderName, self::$folderWithImages);
                    // check errors if folder does not exist
                    if (is_null($subFolderPath)) {
                        if (!$this->hasErrors()) {
                            $this->errors[] = 'Неизвестная ошибка';
                        }
                        $this->fillNewValueWithErrors($numRow);
                        
                        continue;
                    }
                }
                
                $images = $this->getImages($subFolderPath);
                
                $message = "Table '".$tableId."' found ".count($images)." images";
                $this->log($message);
                Log::info($message);
                
                if ($images !== []) {
                    $imagesString = $this->getEncodedImageString($images, $tableGuid);
                    
                    $message = "Table '".$tableId."' filling ".$spreadsheetRowNum."...";
                    $this->log($message.PHP_EOL.$imagesString);
                    Log::info($message);
                    
                    $this->fillNewValueWithContent(
                        $numRow,
                        $this->imagesColumn,
                        $imagesString
                    );
                } else {
                    $this->errors[] = "❗ в папке '".$subFolderName."' нет фото";
                    $this->fillNewValueWithErrors($numRow);
                }
            }
            
            $this->fillSheet($tableId, $sheetName, $propertyColumns);
        }
        
        /**
         * Set empty value due fix php arrays keys
         *
         * @param int $numRow
         */
        private function setEmptyNewValuesForRow(int $numRow): void
        {
            $this->newValues[$numRow][0] = '';
            $this->newValues[$numRow][1] = '';
        }
        
        /**
         * Fill sheet cell with Content or Errors if any
         *
         * @param int $numRow
         * @param int $numColumn
         * @param string|null $content
         */
        private function fillNewValueWithContent(
            int $numRow,
            int $numColumn,
            string $content
        ): void
        {
            $this->newValues[$numRow][$numColumn] = $content;
        }
        
        private function fillNewValueWithErrors(int $numRow): void
        {
            $errorsUnique = array_unique($this->errors);
            $errorsString = implode(PHP_EOL, $errorsUnique);
            $this->newValues[$numRow][$this->errorNumColumn] = $errorsString;
        }
        
        private function hasErrors(): bool
        {
            return count($this->errors) > 0;
        }
        
        /**
         * Checks if it is possible to fill images in row.
         *
         * @param array $row data row.
         * @param TableHeader $propertyColumns human-readable columns.
         * @return bool can fill images.
         */
        private function canFillImages(array $row, TableHeader $propertyColumns): bool
        {
            $subFolderExists = $this->isExistsInRow($row, $propertyColumns->subFolderName);
            $photoSourceFolderExists = $this->isExistsInRow($row, $propertyColumns->photoSourceFolder);
            
            return $subFolderExists || $photoSourceFolderExists;
        }
        
        private function isExistsInRow(array $row, ?int $column): bool
        {
            return !is_null($column) &&
                isset($row[$column]) &&
                (trim($row[$column]) != '');
        }
        
        private function ensureCreatedRootFolders(): void
        {
            if (!$this->yandexDiskService->exists('/'.self::$rootFolder)) {
                $this->yandexDiskService->createFolder(self::$rootFolder);
            }
            
            if (!$this->yandexDiskService->exists('/'.self::$rootFolder.'/'.self::$folderWithImages)) {
                $this->yandexDiskService->createFolder(self::$folderWithImages, self::$rootFolder);
            }
            
            if (!$this->yandexDiskService->exists('/'.self::$rootFolder.'/'.self::$folderWithFolders)) {
                $this->yandexDiskService->createFolder(self::$folderWithFolders, self::$rootFolder);
            }
        }
        
        /**
         * Creates sub folder and fills with images from source folders.
         *
         * @param array $row data row.
         * @param TableHeader $propertyColumns human readable columns.
         * @return string|null new folder id if it can be created with content.
         */
        private function createSubFolderWithContent(array $row, TableHeader $propertyColumns): ?string
        {
            $this->log("Source folders ".$row[$propertyColumns->photoSourceFolder]);
            
            $sourceFolders = explode(PHP_EOL, $row[$propertyColumns->photoSourceFolder]);
            $sourceFolders = array_filter(
                $sourceFolders,
                function ($sourceFolder) {
                    return trim($sourceFolder) !== "";
                }
            );
            ksort($sourceFolders);
            $imageCopyData = $this->getImageCopyData($sourceFolders);
            
            if (
                (count($imageCopyData) > 0) &&
                (count($imageCopyData) === count($sourceFolders))
            ) {
                $this->removeOriginalImagesFromCache($sourceFolders);
                
                $newFolderName = crc32(Guid::uuid4()->toString());
                $newFolderPath = self::$rootFolder.'/'.self::$folderWithImages.'/'.$newFolderName;
                
                foreach ($imageCopyData as $imageCopyDatum) {
                    try {
                        $this->yandexDiskService->moveFile(
                            $imageCopyDatum["image"],
                            $newFolderPath,
                            $imageCopyDatum["newName"]
                        );
                    } catch (\Exception $exception) {
                        Log::error("Error on moving images from ".
                            self::$folderWithFolders.". code: ".$exception->getCode().PHP_EOL.$exception->getMessage());
                    }
                }
                
                return $newFolderName;
            } else {
                // errors found earlier
                return null;
            }
        }
        
        /**
         * Copy images from source folders
         *
         * @param array $sourceFolders
         * @return array copied images
         */
        private function getImageCopyData(array $sourceFolders): array
        {
            $maxNumberOfSymbolsInFileNumber = strlen(strval(count($sourceFolders)));
            $imageCopyData = [];
            $imageNumber = 1;
            
            $alreadyUsedImages = [];
            foreach ($sourceFolders as $sourceFolder) {
                $sourceFolder = trim($sourceFolder);
                
                if ($sourceFolder === "") {
                    Log::error("Empty source folder given after filtering");
                    continue;
                }
                
                $this->log("Processing ".$sourceFolder);
                
                if (isset($this->images[$sourceFolder])) {
                    $this->log("Num of images from ".$sourceFolder." is ".
                        count($this->images[$sourceFolder]));
                } else {
                    $this->log("Getting images from "
                        .$sourceFolder);
                    
                    $sourceFolderPath = $this->checkAndGetFolder($sourceFolder, self::$folderWithFolders);
                    if (is_null($sourceFolderPath)) {
                        // errors found earlier
                        continue;
                    }
                    
                    $res = $this->getFolderImages($sourceFolderPath);
                    $this->images[$sourceFolder] = $res;
                    
                    $this->log($sourceFolderPath." contains ".count($this->images[$sourceFolder]).
                        " images. Loaded them into cache.");
                }
                
                if (count($this->images[$sourceFolder]) == 0) {
                    $this->log('folder no images in '.$sourceFolder);
                    $this->errors[] = "❗ в папке '".$sourceFolder."' нет фото";
                    
                    continue;
                }
                
                $image = $this->checkAndGetNextImage($sourceFolder, $alreadyUsedImages);
                if (is_null($image)) {
                    // errors found earlier
                    continue;
                }
                $alreadyUsedImages[] = $image;
                
                $filePathArray = explode('/', $image);
                $imageName = $filePathArray[count($filePathArray) - 1];
                $imageCopyData[] = [
                    "image" => $image,
                    "newName" => str_pad(
                            $imageNumber,
                            $maxNumberOfSymbolsInFileNumber,
                            '0',
                            STR_PAD_LEFT
                        ).$imageName
                ];
                $imageNumber++;
            }
            
            return $imageCopyData;
        }
        
        /**
         * @param string $sourceFolderPath
         * @return string[]
         */
        private function getFolderImages(string $sourceFolderPath): array
        {
            $res = $this->yandexDiskService->listFolderImages($sourceFolderPath);
            if (count($res) === 0) {
                $sourceFolderPathOld = '/'.$sourceFolderPath.'/';
                $res = $this->yandexDiskService->listFolderImages($sourceFolderPathOld);
            }
            
            return $res;
        }
        
        private function checkAndGetNextImage(string $sourceFolder, array $alreadyUsedImages, $i = 0): ?string
        {
            if ($i === count($this->images[$sourceFolder])) {
                Log::error('not enough images in '.$sourceFolder);
                $this->errors[] = "❗ в папке '".$sourceFolder."' недостаточно фото";
                return null;
            }
            
            $image = $this->images[$sourceFolder][$i];
            if (in_array($image, $alreadyUsedImages)) {
                $i++;
                $image = $this->checkAndGetNextImage($sourceFolder, $alreadyUsedImages, $i);
            }
            
            return $image;
        }
        
        /**
         * Removes copied Images from $this->images
         *
         * @param array $sourceFolders
         */
        private function removeOriginalImagesFromCache(array $sourceFolders): void
        {
            foreach ($sourceFolders as $sourceFolder) {
                $sourceFolder = trim($sourceFolder);
                array_shift($this->images[$sourceFolder]);
            }
        }
        
        /**
         * Get Images from Yandex Disk.
         *
         * @param string $subFolderPath folder path.
         * @return array images in folder
         */
        private function getImages(string $subFolderPath): array
        {
            return $this->yandexDiskService->listFolderImages($subFolderPath);
        }
        
        private function getEncodedImageString(array $images, string $tableGuid): string
        {
            $links = array_map(
                function (string $imageRaw) use ($tableGuid): string {
                    $imagePath = preg_replace('/disk:\//', '', $imageRaw);
                    $base64 = base64_encode($imagePath);
                    $urlSafeBase64 = preg_replace(['/\+/', '/\//', '/=/'], ['-', '_', ''], $base64);
                    $fileInfo = $urlSafeBase64;
                    return LinkHelper::getPictureDownloadLink($tableGuid, $fileInfo)." ";
                },
                $images
            );
            return join(PHP_EOL, $links);
        }
        
        private function checkAndGetFolder(string $folderName, string $subRootFolder): ?string
        {
            $folderPath = '/'.self::$rootFolder.'/'.$subRootFolder.'/'.$folderName.'/';
            $folderPathOld = '/'.$folderName.'/';
            
            if (!$this->yandexDiskService->exists($folderPath)) {
                if (!$this->yandexDiskService->exists($folderPathOld)) {
                    $this->log('folder do not exist '.$folderName);
                    $this->errors[] = "❗❗ папка '".$folderName."' не найдена";
                    
                    return null;
                }
                
                return $folderPathOld;
            }
            
            return $folderPath;
        }
        
        private function fillSheet(string $tableId, string $sheetName, TableHeader $propertyColumns): void
        {
            if (!$this->needsToUpdate) {
                $message = "Table '".$tableId."' is already filled.";
                Log::info($message);
                return;
            }
            
            $columnLetterImages = SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->imagesRaw);
            $columnLetterSubFolder = SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->subFolderName);
            $range = $sheetName.'!'.$columnLetterImages.'2:'.$columnLetterSubFolder.'5001';
            
            $message = "Table '".$tableId."' writing values to table...";
            Log::info($message);
            
            try {
                $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                    $tableId,
                    $range,
                    $this->newValues,
                    [
                        'valueInputOption' => 'RAW'
                    ]
                );
            } catch (\Exception $exception) {
                $message = "Error on '".$tableId."' while writing to table".PHP_EOL.
                    $exception->getMessage();
                $this->log($message);
                Log::error($message);
                
                throw $exception;
            }
        }
    }
