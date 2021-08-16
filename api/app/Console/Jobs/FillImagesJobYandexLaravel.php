<?php


namespace App\Console\Jobs;

use App\Configuration\Config;
use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Helpers\LinkHelper;
use App\Helpers\SpreadsheetHelper;
use App\Models\GeneratorLaravel;
use App\Models\Table;
use App\Models\TableHeader;
use App\Models\TableLaravel;
use App\Repositories\Interfaces\ITableRepository;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IYandexDiskService;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Guid\Guid;

class FillImagesJobYandexLaravel extends JobBase
{
    private static string $rootFolder = 'Autoz';
    private static string $folderWithImages = 'Папки_с_фото';
    private static string $folderWithFolders = 'Генератор_папок';
    
    protected IYandexDiskService $yandexDiskService;
    protected SheetNames $sheetNamesConfig;
    private XmlGeneration $xmlGeneration;
    private int $needsToUpdateTimeStamp;
    
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
        IYandexDiskService        $yandexDiskService,
        XmlGeneration             $xmlGeneration,
        int                       $needsToUpdateTimeStamp
    )
    {
        parent::__construct($spreadsheetClientService);
        $this->yandexDiskService = $yandexDiskService;
        $this->sheetNamesConfig = new SheetNames();
        $this->xmlGeneration = $xmlGeneration;
        $this->needsToUpdateTimeStamp = $needsToUpdateTimeStamp;
    }
    
    /**
     * Start job.
     *
     * Fills images for all tables that were not filled before.
     *
     * @param TableLaravel $table table to process.
     *
     * @throws \Exception
     */
    public function start(TableLaravel $table): void
    {
        $googleSheetId = $table->googleSheetId;
        $message = "Table '".$googleSheetId."' processing...";
        Log::channel($this->logChannel)->info($message);
        
        $this->startTimestamp = time();
        
        if (!$this->init($table)) {
            $message = "Table '".$googleSheetId."' no yandex token found, finished.";
            Log::channel($this->logChannel)->info($message);
            return;
        }
        
        $existingSheets = $this->spreadsheetClientService->getSheets(
            $googleSheetId
        );
    
        try {
            $this->ensureCreatedRootFolders();
        } catch (\Exception $exception) {
            if ($exception->getCode() !== 401) {
                $message = "Table '".$googleSheetId."' yandex error.".PHP_EOL.$exception->getMessage();
                Log::channel($this->logChannel)->error($message);
                
                return;
            }
            
            $message = "Table '".$googleSheetId."' incorrect yandex token";
            Log::channel($this->logChannel)->alert($message);
            
            return;
        }
        
        /** @var GeneratorLaravel $generator */
        foreach ($table->generators as $generator) {
            switch ($generator->targetPlatform) {
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
                
                $message = "Table '".$googleSheetId."' processing sheet '".$targetSheet."'...";
                Log::channel($this->logChannel)->info($message);
                
                $this->processSheet(
                    $table,
                    $targetSheet
                );
                
                if ($this->checkIsTimeout()) {
                    Log::channel($this->logChannel)->info("Table '".$googleSheetId."' finished by timeout.");
                    $table->dateLastModified = $this->needsToUpdateTimeStamp;
                    $table->save();
                    break;
                }
            }
        }
        
        $message = "Table '".$googleSheetId."' finished.";
        Log::channel($this->logChannel)->info($message);
    }
    
    /**
     * Tries to init yandex disk service for table.
     *
     * @param TableLaravel $table
     *
     * @return bool is init successful.
     * @throws \Exception
     */
    private function init(TableLaravel $table): bool
    {
        $yandexTokenCell = 'D7';
        
        $range = $this->sheetNamesConfig->getInformation().'!'.$yandexTokenCell.':'.$yandexTokenCell;
        
        try {
            $yandexConfig = $this->spreadsheetClientService->getSpreadsheetCellsRange(
                $table->googleSheetId,
                $range
            );
        } catch (\Exception $exception) {
            $message = "Error on '".$table->googleSheetId."' while getting yandex token ".PHP_EOL.
                $exception->getMessage();
            Log::channel($this->logChannel)->error($message);
            
            throw $exception;
        }
        
        $yandexToken = $yandexConfig ? @$yandexConfig[0][0] : null;
        
        // If there is a token in spreadsheet, renew token in database and remove token from spreadsheet
        if (!is_null($yandexToken) && (trim($yandexToken) !== '')) {
            $table->yandexToken = trim($yandexToken);
            $table->save();
            
            $this->spreadsheetClientService->updateCellContent(
                $table->googleSheetId,
                $this->sheetNamesConfig->getInformation(),
                $yandexTokenCell,
                ""
            );
        }
        
        if ($table->yandexToken == "") {
            return false;
        }
        
        $this->yandexDiskService->init($table->yandexToken);
        
        return true;
    }
    
    /**
     * Fills images for specified generator.
     *
     * @param TableLaravel $table
     * @param string       $sheetName target sheet.
     *
     * @throws \Exception
     */
    private function processSheet(
        TableLaravel $table,
        string       $sheetName
    ): void
    {
        $tableGuid = $table->tableGuid;
        $googleSheetId = $table->googleSheetId;
        
        [$propertyColumns, $values] = $this->getHeaderAndDataFromTable(
            $googleSheetId,
            $sheetName
        );
        
        if ($propertyColumns && empty($values)) {
            return;
        }
        
        $this->newValues = [];
        
        if (is_null($propertyColumns->imagesRaw)) {
            Log::channel($this->logChannel)->error("no images column");
            return;
        }
        
        if (is_null($propertyColumns->subFolderName)) {
            Log::channel($this->logChannel)->error("no sub folder column");
            return;
        }
        
        foreach ($values as $numRow => $row) {
            if ($this->checkIsTimeout()) {
                $table->dateLastModified = $this->needsToUpdateTimeStamp;
                $table->save();
                Log::channel($this->logChannel)->info("Table '".$googleSheetId."' finished by timeout.");
                
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
            
            $message = "Table '".$googleSheetId."' start filling row ".$spreadsheetRowNum;
            Log::channel($this->logChannel)->info($message);
            
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
                
                $message = "Table '".$googleSheetId."' folder name - ".$subFolderName;
                Log::channel($this->logChannel)->info($message);
            } else {
                $subFoldersRawString = $row[$propertyColumns->subFolderName];
                $subFolders = explode(PHP_EOL, $subFoldersRawString);
                $this->fillNewValueWithContent(
                    $numRow,
                    $this->subFolderColumn,
                    $subFoldersRawString
                );
                
                $subFolderPathArray = [];
                foreach ($subFolders as $subFolder) {
                    $subFolderName = trim($subFolder);
                    if ($subFolderName === '') {
                        continue;
                    }
                    
                    $message = "Table '".$googleSheetId."' folder '".$subFolderName."' already in row";
                    Log::channel($this->logChannel)->info($message);
                    
                    $subFolderPath = $this->checkAndGetFolder($subFolderName, self::$folderWithImages);
                    // check errors if folder does not exist
                    if (is_null($subFolderPath)) {
                        if (!$this->hasErrors()) {
                            $this->errors[] = 'Неизвестная ошибка';
                        }
                        $this->fillNewValueWithErrors($numRow);
                        
                        break;
                    }
                    $subFolderPathArray[] = $subFolderPath;
                }
                
                if ($this->hasErrors()) {
                    continue;
                }
            }
            
            if (isset($subFolderPathArray) && count($subFolderPathArray) > 0) {
                $images = [];
                foreach ($subFolderPathArray as $sub) {
                    $images = [...$images, ...$this->getImages($sub)];
                }
            } else {
                $images = $this->getImages($subFolderPath);
            }
            
            $message = "Table '".$googleSheetId."' found ".count($images)." images";
            Log::channel($this->logChannel)->info($message);
            
            if ($images !== []) {
                $imagesString = $this->getEncodedImageString($images, $tableGuid);
                
                $message = "Table '".$googleSheetId."' filling ".$spreadsheetRowNum."...";
                Log::channel($this->logChannel)->info($message);
                
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
        
        if (!$this->checkIsTimeout()) {
            $table->dateLastModified = 0;
            $table->save();
        }
        
        $this->fillSheet($googleSheetId, $sheetName, $propertyColumns);
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
     * @param int    $numRow
     * @param int    $numColumn
     * @param string $content
     */
    private function fillNewValueWithContent(
        int    $numRow,
        int    $numColumn,
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
     * @param array       $row             data row.
     * @param TableHeader $propertyColumns human-readable columns.
     *
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
     * @param array       $row             data row.
     * @param TableHeader $propertyColumns human readable columns.
     *
     * @return string|null new folder id if it can be created with content.
     */
    private function createSubFolderWithContent(array $row, TableHeader $propertyColumns): ?string
    {
//        $this->log("Source folders ".$row[$propertyColumns->photoSourceFolder]);
        
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
                    Log::channel($this->logChannel)->error("Error on moving images from ".
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
     *
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
                Log::channel($this->logChannel)->error("Empty source folder given after filtering");
                continue;
            }
            
//            $this->log("Processing ".$sourceFolder);
            
            if (isset($this->images[$sourceFolder])) {
//                $this->log("Num of images from ".$sourceFolder." is ".
//                    count($this->images[$sourceFolder]));
            } else {
//                $this->log("Getting images from "
//                    .$sourceFolder);
                
                $sourceFolderPath = $this->checkAndGetFolder($sourceFolder, self::$folderWithFolders);
                if (is_null($sourceFolderPath)) {
                    // errors found earlier
                    continue;
                }
                
                $res = $this->getFolderImages($sourceFolderPath);
                $this->images[$sourceFolder] = $res;
                
//                $this->log($sourceFolderPath." contains ".count($this->images[$sourceFolder]).
//                    " images. Loaded them into cache.");
            }
            
            if (count($this->images[$sourceFolder]) == 0) {
//                $this->log('folder no images in '.$sourceFolder);
//                $this->errors[] = "❗ в папке '".$sourceFolder."' нет фото";
                
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
     *
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
            Log::channel($this->logChannel)->error('not enough images in '.$sourceFolder);
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
     *
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
                $this->errors[] = "❗❗ папка '".$folderName."' не найдена";
                
                return null;
            }
            
            return $folderPathOld;
        }
        
        return $folderPath;
    }
    
    private function fillSheet(string $googleSheetId, string $sheetName, TableHeader $propertyColumns): void
    {
        if (!$this->needsToUpdate) {
            $message = "Table '".$googleSheetId."' is already filled.";
            Log::channel($this->logChannel)->info($message);
            return;
        }
        
        $columnLetterImages = SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->imagesRaw);
        $columnLetterSubFolder = SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->subFolderName);
        $range = $sheetName.'!'.$columnLetterImages.'2:'.$columnLetterSubFolder.$this->adsLimit;
        
        $message = "Table '".$googleSheetId."' writing values to table...";
        Log::channel($this->logChannel)->info($message);
        
        try {
            $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                $googleSheetId,
                $range,
                $this->newValues,
                [
                    'valueInputOption' => 'RAW'
                ]
            );
        } catch (\Exception $exception) {
            $message = "Error on '".$googleSheetId."' while writing to table".PHP_EOL.
                $exception->getMessage();
            Log::channel($this->logChannel)->error($message);
            
            throw $exception;
        }
    }
}
