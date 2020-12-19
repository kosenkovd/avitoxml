<?php


    namespace App\Console\Jobs;

    use App\Helpers\LinkHelper;
    use App\Helpers\SpreadsheetHelper;
    use App\Models\Table;
    use App\Models\TableHeader;
    use App\Repositories\Interfaces\ITableRepository;
    use App\Services\Interfaces\IGoogleServicesClient;
    use Ramsey\Uuid\Guid\Guid;

    class FillImagesJob {
        private IGoogleServicesClient $googleClient;
        private ITableRepository $tableRepository;

        private string $jobId;

        private array $images = [];

        private function log(string $message) : void
        {
            $timestamp = new \DateTime();
            $timestamp->setTimestamp(time());
            $file = __DIR__."/../Logs/imageFillerLog.log";
            file_put_contents($file,
                $timestamp->format(DATE_ISO8601)." ".$this->jobId." ".$message.PHP_EOL,
                FILE_APPEND | LOCK_EX);
        }

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

            $childFolder = $this->googleClient->getChildFolderByName($folderID, $subFolderName);

            if ($childFolder == '') {
                return [];
            }

            return $this->googleClient->listFolderImages($childFolder);
        }

        /**
         * Add Images to GoogleSheet.
         *
         * @param string $content content to put in cell.
         * @param int $numRow number of row.
         * @param string $tableID table id.
         * @param string $columnName col name.
         * @return void
         */
        private function updateCellContent(string $content, int $numRow, string $tableID, string $columnName): void
        {
            $range = 'Avito!' . $columnName . $numRow . ':' . $columnName . $numRow;

            $values = [
                [$content]
            ];
            $params = [
                'valueInputOption' => 'RAW'
            ];
            $this->googleClient->updateSpreadsheetCellsRange(
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
                $sourceFolderId = $this->googleClient->getChildFolderByName($baseFolderId, $sourceFolder);
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
                    $this->images[$sourceFolderId] = $this->googleClient->listFolderImages($sourceFolderId);
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
                            STR_PAD_LEFT).$image->getName()
                ];
                $imageNumber++;
            }

            if(count($imageCopyData) > 0)
            {
                $newFolderName = crc32(Guid::uuid4()->toString());
                $newFolderId = $this->googleClient->createFolder($newFolderName, $baseFolderId, false);

                foreach ($imageCopyData as $imageCopyDatum)
                {
                    $this->googleClient->moveFile(
                        $imageCopyDatum["image"],
                        $newFolderId,
                        $imageCopyDatum["newName"]);
                }
            }

            return $newFolderName;
        }

        public function __construct(
            IGoogleServicesClient $googleClient,
            ITableRepository $tableRepository
        )
        {
            $this->jobId = Guid::uuid4()->toString();
            $this->googleClient = $googleClient;
            $this->tableRepository = $tableRepository;
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

            $tableID = $table->getGoogleSheetId();
            echo "Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId().PHP_EOL;
            $this->log("Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId());
            $baseFolderID = $table->getGoogleDriveId();

            $headerRange = 'Avito!A1:FZ1';
            $headerResponse = $this->googleClient->getSpreadsheetCellsRange($tableID, $headerRange);
            $propertyColumns = new TableHeader($headerResponse[0]);

            $range = 'Avito!A2:FZ5001';
            $values = $this->googleClient->getSpreadsheetCellsRange($tableID, $range);

            if (empty($values))
            {
                return;
            }

            foreach ($values as $numRow => $row) {
                $alreadyFilled = isset($row[$propertyColumns->imagesRaw]) &&
                    $row[$propertyColumns->imagesRaw] != '';

                // content starts at line 2
                $spreadsheetRowNum = $numRow + 2;
                if($alreadyFilled || !$this->canFillImages($row, $propertyColumns))
                {
                    continue;
                }

                echo "Filling row ".$spreadsheetRowNum.PHP_EOL;
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
                        SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->subFolderName));
                }
                else
                {
                    $subFolderName = trim($row[$propertyColumns->subFolderName]);
                }


                $this->log("Folder name ".$subFolderName);
                echo "Folder name ".$subFolderName.PHP_EOL;

                $images = $this->getImages($baseFolderID, $subFolderName);
                $this->log("Found ".count($images)." images");
                echo "Found ".count($images)." images";

                if ($images !== []) {
                    $links = [];
                    foreach ($images as $image)
                    {
                        $links[] = LinkHelper::getPictureDownloadLink($image->id);
                    }
                    $imagesString = join(PHP_EOL, $links);

                    $this->log("Images string ".$imagesString);
                    echo "Images string ".$imagesString.PHP_EOL;
                    $this->updateCellContent(
                        $imagesString,
                        $spreadsheetRowNum,
                        $tableID,
                        SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->imagesRaw));
                }
            }

            $this->log("Finished fill images job.");
        }
    }
