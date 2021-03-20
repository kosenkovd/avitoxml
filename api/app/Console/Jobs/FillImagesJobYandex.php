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
    use Leonied7\Yandex\Disk\Item\File;
    use Ramsey\Uuid\Guid\Guid;
    
    class FillImagesJobYandex extends JobBase {
        private static string $rootFolder = 'Автозагрузка';
        private static string $folderWithImages = 'Папки_с_фотографиями';
        private static string $folderWithFolders = 'Генератор_папок';
        
        protected IYandexDiskService $yandexDiskService;
        protected SheetNames $sheetNamesConfig;
        private ITableRepository $tableRepository;
        private XmlGeneration $xmlGeneration;
        
        protected int $maxJobTime = 60 * 5;
        protected bool $loggingEnabled = true;
        protected bool $timeoutEnabled = false;
        
        private array $images = [];
        private array $errors = [];
        
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
                        $table->getTableGuid(),
                        $table->getGoogleSheetId(),
                        $targetSheet
                    );
                    $this->stopIfTimeout();
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
         * @param string $tableGuid table guid.
         * @param string $tableId Google spreadsheet id.
         * @param string $sheetName target sheet.
         * @throws \Exception
         */
        private function processSheet(
            string $tableGuid,
            string $tableId,
            string $sheetName
        ): void
        {
            $values = $this->getFullDataFromTable($tableId, $sheetName);
            $propertyColumns = new TableHeader(array_shift($values));
            
            if ($propertyColumns && empty($values)) {
                return;
            }
            
            $this->checkAndCreateRootFolders();
            
            foreach ($values as $numRow => $row) {
                $this->errors = [];
                
                $this->stopIfTimeout();
                
                if (
                    $this->isExistsInRow($row, $propertyColumns->imagesRaw) ||
                    !$this->canFillImages($row, $propertyColumns)
                ) {
                    continue;
                }
                
                // content starts at line 2
                $spreadsheetRowNum = $numRow + 2;
                
                $message = "Table '".$tableId."' start filling row ".$spreadsheetRowNum;
                $this->log($message);
                Log::info($message);
                
                if (!$this->isExistsInRow($row, $propertyColumns->subFolderName)) {
                    $subFolderName = $this->createSubFolderWithContent($row, $propertyColumns);
                    
                    $this->fillCellWithContentOrErrors(
                        $tableId,
                        $sheetName,
                        SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->subFolderName).$spreadsheetRowNum,
                        $subFolderName
                    );
                    
                    if (is_null($subFolderName)) {
                        // Errors found in createSubFolderWithContent method
                        continue;
                    }
                } else {
                    $subFolderName = trim($row[$propertyColumns->subFolderName]);
                    
                    Log::info('already exists '.$subFolderName);
                }
                
                $message = "Table '".$tableId."' folder name ".$subFolderName;
                $this->log($message);
                Log::info($message);
                
                $images = $this->getImages($subFolderName);
                
                $message = "Table '".$tableId."' found ".count($images)." images";
                $this->log($message);
                Log::info($message);
                
                if ($images !== []) {
                    $links = array_map(
                        function (string $image) use ($tableGuid): string {
                            Log::info($image);
                            $base64 = base64_encode($image);
                            $urlSafeBase64 = preg_replace(['/\+/', '/\//', '/=/'], ['-', '_', ''], $base64);
                            $fileInfo = $urlSafeBase64;
                            Log::info('base64 '.$fileInfo);
                            return LinkHelper::getPictureDownloadLink($tableGuid, $fileInfo)." ";
                        },
                        $images
                    );
                    $imagesString = join(PHP_EOL, $links);
                    
                    $message = "Table '".$tableId."' filling ".$spreadsheetRowNum."...";
                    $this->log($message.PHP_EOL.$imagesString);
                    Log::info($message);
                } else {
                    $imagesString = '';
                    
                    $subFolderPath = '/'.self::$rootFolder.'/'.self::$folderWithImages.'/'.$subFolderName.'/';
                    $this->errors[] = 'Не найдено фото в папке '.$subFolderPath;
                }
                
                $this->fillCellWithContentOrErrors(
                    $tableId,
                    $sheetName,
                    SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->imagesRaw).$spreadsheetRowNum,
                    $imagesString
                );
                
                sleep(1);
            }
        }
        
        /**
         * Fill sheet cell with Content or Errors if any
         *
         * @param string $tableId
         * @param string $sheetName
         * @param string $cell
         * @param string|null $content
         */
        private function fillCellWithContentOrErrors(
            string $tableId,
            string $sheetName,
            string $cell,
            ?string $content
        ): void
        {
            if (count($this->errors) > 0) {
                $content = $this->getContentWithErrors();
            }
            
            if (is_null($content)) {
                return;
            }
            
            $this->spreadsheetClientService->updateCellContent(
                $tableId,
                $sheetName,
                $cell,
                $content,
            );
        }
        
        private function getContentWithErrors(): string
        {
            return implode(PHP_EOL, $this->errors);
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
        
        private function checkAndCreateRootFolders(): void
        {
            if (!$this->yandexDiskService->exists('/'.self::$rootFolder)) {
                $this->yandexDiskService->createFolder(self::$rootFolder);
                Log::info('folder '.self::$rootFolder.' created');
            }
            
            if (!$this->yandexDiskService->exists('/'.self::$rootFolder.'/'.self::$folderWithImages)) {
                $this->yandexDiskService->createFolder(self::$folderWithImages, self::$rootFolder);
                Log::info('folder '.self::$folderWithImages.' created');
            }
            
            if (!$this->yandexDiskService->exists('/'.self::$rootFolder.'/'.self::$folderWithFolders)) {
                $this->yandexDiskService->createFolder(self::$folderWithFolders, self::$rootFolder);
                Log::info('folder '.self::$folderWithFolders.' created');
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
            $imageCopyData = $this->getImageCopyData($sourceFolders);
            
            if (
                (count($imageCopyData) > 0) &&
                (count($imageCopyData) === count($sourceFolders))
            ) {
                $this->removeOriginalImagesFromCache($sourceFolders);
                
                $newFolderName = crc32(Guid::uuid4()->toString());
                $newFolderPath = self::$rootFolder.'/'.self::$folderWithImages.'/'.$newFolderName;
                foreach ($imageCopyData as $imageCopyDatum) {
                    $this->yandexDiskService->moveFile(
                        $imageCopyDatum["image"],
                        $newFolderPath,
                        $imageCopyDatum["newName"]);
                }
                
                return $newFolderName;
            } else {
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
            foreach ($sourceFolders as $sourceFolder) {
                $sourceFolder = trim($sourceFolder);
                
                if ($sourceFolder === "") {
                    continue;
                }
                
                $this->log("Processing ".$sourceFolder);
                
                if (isset($this->images[$sourceFolder])) {
                    $this->log("Num of images from ".$sourceFolder." is ".
                        count($this->images[$sourceFolder]));
                } else {
                    $sourceFolderPath = '/'.self::$rootFolder.'/'.self::$folderWithFolders.'/'.$sourceFolder.'/';
                    
                    $this->log("Getting images from "
                        .$sourceFolderPath);
                    $this->log("Does folder ".$sourceFolderPath." exist: ".
                        $this->yandexDiskService->exists($sourceFolderPath));
                    
                    $this->images[$sourceFolder] = $this->yandexDiskService->listFolderImages($sourceFolderPath);
                    
                    $this->log($sourceFolderPath." contains ".count($this->images[$sourceFolder]).
                        " images. Loaded them into cache.");
                }
                
                if (count($this->images[$sourceFolder]) == 0) {
                    Log::error('folder no images in '.$sourceFolder);
                    $this->errors[] = 'Не найдено фото в папке '.$sourceFolderPath;
                    continue;
                }
                
                /** @var $image File */
                $image = $this->images[$sourceFolder][0];
                
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
         * @param string $subFolderName folder name.
         * @return array images in folder
         */
        private function getImages(string $subFolderName): array
        {
            if ($subFolderName == '') {
                return [];
            }
            
            $subFolderPath = '/'.self::$rootFolder.'/'.self::$folderWithImages.'/'.$subFolderName.'/';
            return $this->yandexDiskService->listFolderImages($subFolderPath);
        }
    }
