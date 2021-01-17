<?php


    namespace App\Console\Jobs;

    use App\Configuration\Spreadsheet\SheetNames;
    use App\Helpers\LinkHelper;
    use App\Helpers\SpreadsheetHelper;
    use App\Models\Generator;
    use App\Models\Table;
    use App\Models\TableHeader;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use App\Services\Interfaces\IYandexDiskService;
    use Leonied7\Yandex\Disk\Item\File;
    use Ramsey\Uuid\Guid\Guid;

    class FillImagesJobYandex extends JobBase
    {
        /**
         * @var int max time to execute job.
         */
        protected int $maxJobTime = 60*60;

        private array $images = [];
        
        /**
         * @var IYandexDiskService Google Spreadsheet client.
         */
        protected IYandexDiskService $yandexDiskService;
    
        protected string $yandexToken;
    
        /**
         * @var SheetNames
         */
        protected SheetNames $sheetNamesConfig;

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
         * Updates GoogleSheet cell content.
         *
         * @param string $content content to put in cell.
         * @param int $numRow number of row.
         * @param string $tableID table id.
         * @param string $columnName col name.
         * @param string $targetSheet sheet name.
         * @return void
         */
        private function updateCellContent(
            string $content, int $numRow, string $tableID, string $columnName, string $targetSheet): void
        {
            $range = $targetSheet.'!' . $columnName . $numRow . ':' . $columnName . $numRow;

            $values = [
                [$content]
            ];
            $params = [
                'valueInputOption' => 'RAW'
            ];
            $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                $tableID,
                $range,
                $values,
                $params
            );
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

            $maxNumberOfSymbolsInFileNumber = strlen(strval(count($sourceFolders)));

            $imageCopyData = [];
            $imageNumber = 1;
            foreach ($sourceFolders as $sourceFolder)
            {
                $sourceFolder = trim($sourceFolder);
                $this->log("Processing ".$sourceFolder);
                $sourceFolderId = $this->yandexDiskService->getChildFolderByName($baseFolderId, $sourceFolder);
                /** @var $image File */
                $image = null;
                if(isset($this->images[$sourceFolderId]))
                {
                    if(count($this->images[$sourceFolderId]) == 0)
                    {
                        continue;
                    }
                    $image = array_shift($this->images[$sourceFolderId]);
                }
                else
                {
                    $this->log("Getting images from ".$sourceFolder);
                    $this->images[$sourceFolderId] = $this->yandexDiskService->listFolderImages($sourceFolderId);
                    if(count($this->images[$sourceFolderId]) == 0)
                    {
                        continue;
                    }
                    $image = array_shift($this->images[$sourceFolderId]);
                }
                
                $imageCopyData[] = [
                    "image" => $image,
                    "newName" => str_pad(
                            $imageNumber,
                            $maxNumberOfSymbolsInFileNumber,
                            '0',
                            STR_PAD_LEFT).$image->getProperties()->find('displayname')->getValue()
                ];
                $imageNumber++;
            }

            if(count($imageCopyData) > 0)
            {
                $newFolderName = crc32(Guid::uuid4()->toString());
                
                $newFolderId = $this->yandexDiskService->createFolder($newFolderName, null, false);
    
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
         * @param string $tableID Google spreadsheet id.
         * @param string $baseFolderID Google drive base folder id.
         * @param Generator $generator Generator.
         */
        private function processSheet(string $tableID, string $baseFolderID, Generator $generator): void
        {
            $sheetName = $generator->getTargetPlatform();
            $this->log("Processing sheet (sheetName: ".$sheetName.", tableID: ".$tableID.")");
            [ $propertyColumns, $values ] = $this->getHeaderAndDataFromTable($tableID, $sheetName);

            if (empty($values))
            {
                return;
            }

            foreach ($values as $numRow => $row)
            {
                $this->stopIfTimeout();

                var_dump($numRow);
                $alreadyFilled = isset($row[$propertyColumns->imagesRaw]) &&
                    $row[$propertyColumns->imagesRaw] != '';

                // content starts at line 2
                $spreadsheetRowNum = $numRow + 2;
                if($alreadyFilled || !$this->canFillImages($row, $propertyColumns))
                {
                    continue;
                }

                $this->log("Filling row ".$spreadsheetRowNum);

                if(!isset($row[$propertyColumns->subFolderName]) ||
                    $row[$propertyColumns->subFolderName] == '')
                {
                    $subFolderName = $this->createSubFolderWithContent($baseFolderID, $row, $propertyColumns);
                    if(is_null($subFolderName))
                    {
                        continue;
                    }

                    $this->updateCellContent(
                        $subFolderName,
                        $spreadsheetRowNum,
                        $tableID,
                        SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->subFolderName),
                        $sheetName);
                }
                else
                {
                    $subFolderName = trim($row[$propertyColumns->subFolderName]);
                }


                $this->log("Folder name ".$subFolderName);

                $images = $this->getImages($baseFolderID, $subFolderName);
                $this->log("Found ".count($images)." images");

                if ($images !== []) {
                    $links = [];
                    /** @var $image File */
                    foreach ($images as $image)
                    {
                        $links[] = $image->getProperties()->find('file_url')->getValue();
                    }
                    $imagesString = join(PHP_EOL, $links);

                    $this->log("Images string ".$imagesString);
                    $this->updateCellContent(
                        $imagesString,
                        $spreadsheetRowNum,
                        $tableID,
                        SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->imagesRaw),
                        $generator->getTargetPlatform());
                }
            }
        }
        
        private function init(string $tableID): void
        {
            $yandexConfigFrom = 'V5';
            $yandexConfigTo = 'V5';
            
            $range = $this->sheetNamesConfig->getYandex().'!'.$yandexConfigFrom.':'.$yandexConfigTo;
            
            $yandexConfig = $this->spreadsheetClientService->getSpreadsheetCellsRange(
                $tableID,
                $range
            );
            $this->yandexToken = $yandexConfig[0][0];
            
            if ($this->doesYandexTokenExist()) {
                $this->yandexDiskService->init($this->yandexToken);
            }
        }
    
        private function doesYandexTokenExist(): bool
        {
            return !is_null($this->yandexToken) && ($this->yandexToken !== '');
        }

        public function __construct(
            ISpreadsheetClientService $spreadsheetClientService,
            IYandexDiskService $yandexDiskService
        )
        {
            parent::__construct($spreadsheetClientService);
            $this->yandexDiskService = $yandexDiskService;
            $this->sheetNamesConfig = new SheetNames();
        }

        /**
         * Start job.
         *
         * Fills images for all tables that were not filled before.
         *
         * @param Table $table table to process.
         */
        public function start(Table $table): void
        {
            $this->log("Start fill images job");

            $this->startTimestamp = time();

            $tableID = $table->getGoogleSheetId();
            $this->log("Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId());
            $baseFolderID = $table->getGoogleDriveId();
            
            $this->init($tableID);

            foreach ($table->getGenerators() as $generator)
            {
                $this->processSheet($tableID, $baseFolderID, $generator);
                $this->stopIfTimeout();
            }

            $this->log("Finished fill images job.");
        }
    }
