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

    class FillImagesJobYandex extends JobBase
    {
        /**
         * @var int max time to execute job.
         */
        protected int $maxJobTime = 60*5;

        /**
         * @var bool is logging enabled.
         */
        protected bool $loggingEnabled = true;

        protected bool $timeoutEnabled = false;

        private array $images = [];

        /**
         * @var IYandexDiskService Yandex Disk client.
         */
        protected IYandexDiskService $yandexDiskService;

        /**
         * @var SheetNames
         */
        protected SheetNames $sheetNamesConfig;

        private ITableRepository $tableRepository;

        private XmlGeneration $xmlGeneration;

        /**
         * Checks if it is possible to fill images in row.
         *
         * @param array $row data row.
         * @param TableHeader $propertyColumns human-readable columns.
         * @return bool can fill images.
         */
        private function canFillImages(array $row, TableHeader $propertyColumns) : bool
        {
            $subFolderExists =
                !is_null($propertyColumns->subFolderName) &&
                isset($row[$propertyColumns->subFolderName]) &&
                $row[$propertyColumns->subFolderName] != '';
            $photoSourceFolderExists =
                !is_null($propertyColumns->photoSourceFolder) &&
                isset($row[$propertyColumns->photoSourceFolder]) &&
                $row[$propertyColumns->photoSourceFolder] != '';

            return $subFolderExists || $photoSourceFolderExists;
        }

        /**
         * Get Images from GoogleDrive.
         *
         * @param string $folderID folder id.
         * @param string $subFolderName sub folder name.
         * @return array images in folders
         */
        private function getImages(string $folderID, string $subFolderName): array
        {
            if ($subFolderName == '') {
                return [];
            }

            $childFolder = $this->yandexDiskService->getChildFolderByName($folderID, $subFolderName);

            if ($childFolder == '') {
                return [];
            }

            return $this->yandexDiskService->listFolderImages($childFolder);
        }

        /**
         * Creates sub folder and fills with images from source folders.
         *
         * @param string $baseFolderId base folder id.
         * @param array $row data row.
         * @param TableHeader $propertyColumns human readable columns.
         * @return string|null new folder id if it can be created with content.
         */
        private function createSubFolderWithContent(
            string $baseFolderId, array $row, TableHeader $propertyColumns) : ?string
        {
            $newFolderName = null;
            $this->log("Source folders ".$row[$propertyColumns->photoSourceFolder]);
            $sourceFolders = explode(PHP_EOL, $row[$propertyColumns->photoSourceFolder]);

            var_dump($sourceFolders);
            $maxNumberOfSymbolsInFileNumber = strlen(strval(count($sourceFolders)));

            $imageCopyData = [];
            $imageNumber = 1;
            foreach ($sourceFolders as $sourceFolder)
            {
                $sourceFolder = trim($sourceFolder);
                if($sourceFolder === "")
                {
                    continue;
                }

                $this->log("Processing ".$sourceFolder);
                $sourceFolderId = $this->yandexDiskService->getChildFolderByName($baseFolderId, $sourceFolder);

                var_dump($sourceFolderId);
                /** @var $image File */
                $image = null;
                if(isset($this->images[$sourceFolderId]))
                {
                    $this->log("Num of images from ".$sourceFolderId." is ".count($this->images[$sourceFolderId]));
                    if(count($this->images[$sourceFolderId]) == 0)
                    {
                        continue;
                    }
                    $image = array_shift($this->images[$sourceFolderId]);
                }
                else
                {
                    $this->log("Getting images from ".$sourceFolderId);
                    $this->log("Does folder ".$sourceFolderId." exist: ".$this->yandexDiskService->exists($sourceFolderId));
                    $this->images[$sourceFolderId] = $this->yandexDiskService->listFolderImages($sourceFolderId);
                    $this->log($sourceFolderId." contains ".count($this->images["$sourceFolderId"])." images. Loaded them into cache.");
                    if(count($this->images[$sourceFolderId]) == 0)
                    {
                        continue;
                    }
                    $image = array_shift($this->images[$sourceFolderId]);
                }

                $filePathArray = explode('/', $image);
                $imageName = $filePathArray[count($filePathArray) - 1];
                $imageCopyData[] = [
                    "image" => $image,
                    "newName" => str_pad(
                            $imageNumber,
                            $maxNumberOfSymbolsInFileNumber,
                            '0',
                            STR_PAD_LEFT).$imageName
                ];
                $imageNumber++;
            }

            if(count($imageCopyData) > 0)
            {
                $newFolderName = crc32(Guid::uuid4()->toString());

                foreach ($imageCopyData as $imageCopyDatum)
                {
                    $this->yandexDiskService->moveFile(
                        $imageCopyDatum["image"],
                        $newFolderName,
                        $imageCopyDatum["newName"]);
                }
            }

            return $newFolderName;
        }

        /**
         * Fills images for specified generator.
         *
         * @param string $tableGuid table guid.
         * @param string $tableID Google spreadsheet id.
         * @param string $baseFolderID Google drive base folder id.
         * @param string $sheetName target sheet.
         * @param string $quotaUserPrefix quota user prefix.
         * @throws \Exception
         */
        private function processSheet(
            string $tableGuid, string $tableID, string $baseFolderID, string $sheetName, string $quotaUserPrefix): void
        {
            [ $propertyColumns, $values ] = $this->getHeaderAndDataFromTable($tableID, $sheetName, $quotaUserPrefix);

            if ($propertyColumns && empty($values))
            {
                return;
            }

            foreach ($values as $numRow => $row)
            {
                $this->stopIfTimeout();

                $alreadyFilled = isset($row[$propertyColumns->imagesRaw]) &&
                    trim($row[$propertyColumns->imagesRaw]) != '';

                // content starts at line 2
                $spreadsheetRowNum = $numRow + 2;
                if($alreadyFilled || !$this->canFillImages($row, $propertyColumns))
                {
                    continue;
                }

                $message = "Table '".$tableID."' start filling row ".$spreadsheetRowNum;
                $this->log($message);
                Log::info($message);

                if(!isset($row[$propertyColumns->subFolderName]) ||
                    trim($row[$propertyColumns->subFolderName]) == '')
                {
                    $subFolderName = $this->createSubFolderWithContent($baseFolderID, $row, $propertyColumns);
                    if(is_null($subFolderName))
                    {
                        continue;
                    }

                    $this->spreadsheetClientService->updateCellContent(
                        $tableID,
                        $sheetName,
                        SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->subFolderName).$spreadsheetRowNum,
                        $subFolderName
//                        $quotaUserPrefix."NewFolder".$spreadsheetRowNum
                    );
                }
                else
                {
                    $subFolderName = trim($row[$propertyColumns->subFolderName]);
                }
    
    
                $message = "Table '".$tableID."' folder name ".$subFolderName;
                $this->log($message);
                Log::info($message);

                $images = $this->getImages($baseFolderID, $subFolderName);
                
                $message = "Table '".$tableID."' found ".count($images)." images";
                $this->log($message);
                Log::info($message);

                if ($images !== []) {
                    /*$links = array_map(
                        function (string $image): string  {
                            $path = $this->yandexDiskService->getFileUrl($image);
                            return $path ? $path." " : '';
                        },
                        $images
                    );
                    array_filter($links);*/
                    $links = array_map(
                        function (string $image) use ($tableGuid): string  {
                        $fileInfo = urlencode(base64_encode($image));
                        return LinkHelper::getTestPictureDownloadLink($tableGuid, $fileInfo)." ";
                        },
                        $images
                    );
                    $imagesString = join(PHP_EOL, $links);

                    $message = "Table '".$tableID."' filling ".$spreadsheetRowNum."...";
                    $this->log($message.PHP_EOL.$imagesString);
                    Log::info($message);
                    
                    $this->spreadsheetClientService->updateCellContent(
                        $tableID,
                        $sheetName,
                        SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->imagesRaw).$spreadsheetRowNum,
                        $imagesString,
                        $quotaUserPrefix."NewImages".$spreadsheetRowNum);
                }

                sleep(2);
            }
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
                    $table->getTableGuid() . "fijy");
            } catch(\Exception $exception) {
                $message = "Error on '".$table->getGoogleDriveId()."' while getting yandex token ".PHP_EOL.
                    $exception->getMessage();
                $this->log($message);
                Log::error($message);
                $this->throwExceptionIfQuota($exception);
                
                $yandexConfig = null;
            }

            $yandexToken = $yandexConfig ? @$yandexConfig[0][0] : null;

            // If there is a token in spreadsheet, renew token in database and remove token from spreadsheet
            if (!is_null($yandexToken) && ($yandexToken !== ''))
            {
                $this->tableRepository->updateYandexToken($table->getTableId(), $yandexToken);
                $table->setYandexToken($yandexToken);
                $this->spreadsheetClientService->updateCellContent(
                    $table->getGoogleSheetId(),
                    $this->sheetNamesConfig->getInformation(),
                    $yandexTokenCell,
                    "",
                    $table->getTableGuid()."figt");
            }

            if(is_null($table->getYandexToken()) || $table->getYandexToken() == "")
            {
                return false;
            }

            $this->yandexDiskService->init($table->getYandexToken());

            return true;
        }

        public function __construct(
            ISpreadsheetClientService $spreadsheetClientService,
            IYandexDiskService $yandexDiskService,
            ITableRepository $tableRepository,
            XmlGeneration  $xmlGeneration)
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
            $baseFolderID = "";

            if(!$this->init($table))
            {
                $message = "Table '".$table->getGoogleSheetId()."' no yandex token found, finished.";
                $this->log($message);
                Log::info($message);
                return;
            }

            $existingSheets = $this->spreadsheetClientService->getSheets(
                $table->getGoogleSheetId()
//                $table->getTableGuid()."fijy"
            );

            foreach ($table->getGenerators() as $generator)
            {
                switch($generator->getTargetPlatform())
                {
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
                foreach ($splitTargetSheets as $targetSheet)
                {
                    $targetSheet = trim($targetSheet);
                    if(!in_array($targetSheet, $existingSheets))
                    {
                        continue;
                    }

                    $quotaUserPrefix = substr($table->getTableGuid(), 0, 10).
                        (strlen($targetSheet) > 10 ? substr($targetSheet, 0, 10) : $targetSheet).
                        "RTJ";
    
                    $message = "Table '".$table->getGoogleSheetId()."' processing sheet '".$targetSheet."'...";
                    $this->log($message);
                    Log::info($message);
                    
                    $this->processSheet(
                        $table->getTableGuid(),
                        $table->getGoogleSheetId(),
                        $baseFolderID,
                        $targetSheet,
                        $quotaUserPrefix
                    );
                    $this->stopIfTimeout();
                }
            }

            $message = "Table '".$table->getGoogleSheetId()."' finished.";
            $this->log($message);
            Log::info($message);
        }
    }
