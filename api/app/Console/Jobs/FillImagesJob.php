<?php


    namespace App\Console\Jobs;

    use App\Helpers\LinkHelper;
    use App\Helpers\SpreadsheetHelper;
    use App\Models\TableHeader;
    use App\Repositories\Interfaces\ITableRepository;
    use App\Services\Interfaces\IGoogleServicesClient;
    use Ramsey\Uuid\Guid\Guid;

    class FillImagesJob {
        private IGoogleServicesClient $googleClient;
        private ITableRepository $tableRepository;

        private array $images;

        private function log(string $message) : void
        {
            $timestamp = new \DateTime();
            $timestamp->setTimestamp(time());
            $file = __DIR__."/../Logs/imageFillerLog.log";
            file_put_contents($file,
                $timestamp->format(DATE_ISO8601)." ".$message.PHP_EOL,
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
            // Счет строк начинается с 1, а не с 0 и первая строка - заголовок
            $numRow += +2;
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
         * @return string new folder id.
         */
        private function createSubFolderWithContent(string $baseFolderId, array $row, TableHeader $propertyColumns) : string
        {
            $sourceFolders = explode(PHP_EOL, $row[$propertyColumns->photoSourceFolder]);
            $newFolderName = crc32(Guid::uuid4()->toString());
            $newFolderId = $this->googleClient->createFolder($newFolderName, $baseFolderId);

            $maxNumberOfSymbolsInFileNumber = strlen(strval(count($sourceFolders)));

            $imageNumber = 1;
            foreach ($sourceFolders as $sourceFolder)
            {
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
                    $this->images[$sourceFolderId] = $this->googleClient->listFolderImages($sourceFolderId);
                    if(count($this->images[$sourceFolderId]) == 0)
                    {
                        continue;
                    }
                    $image = array_shift($this->images[$sourceFolderId]);
                }

                $this->googleClient->moveFile(
                    $image,
                    $newFolderId,
                    str_pad(
                        $imageNumber,
                        $maxNumberOfSymbolsInFileNumber,
                        '0',
                        STR_PAD_LEFT).$image->getName());
                $imageNumber++;
            }

            return $newFolderName;
        }

        public function __construct(
            IGoogleServicesClient $googleClient,
            ITableRepository $tableRepository
        )
        {
            $this->googleClient = $googleClient;
            $this->tableRepository = $tableRepository;
        }

        /**
         * Start job.
         *
         * Fills images for all tables that were not filled before.
         */
        public function start(): void
        {
            $logs = "";
            $tables = $this->tableRepository->getTables();

            foreach ($tables as $table)
            {
                $tableID = $table->getGoogleSheetId();
                echo "Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId().PHP_EOL;
                $this->log("Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId());
                $baseFolderID = $table->getGoogleDriveId();

                $headerRange = 'Avito!A1:FZ1';
                $headerResponse = $this->googleClient->getSpreadsheetCellsRange($tableID, $headerRange);
                $propertyColumns = new TableHeader($headerResponse[0]);

                $range = 'Avito!A2:FZ5001';
                $values = $this->googleClient->getSpreadsheetCellsRange($tableID, $range);

                if (!empty($values))
                {
                    foreach ($values as $numRow => $row) {
                        $alreadyFilled = isset($row[$propertyColumns->imagesRaw]) &&
                            $row[$propertyColumns->imagesRaw] != '';

                        if($alreadyFilled || !$this->canFillImages($row, $propertyColumns))
                        {
                            continue;
                        }

                        echo "Filling row ".$numRow.PHP_EOL;
                        $this->log("Filling row ".$numRow);

                        if(!isset($row[$propertyColumns->subFolderName]) ||
                            $row[$propertyColumns->subFolderName] == '')
                        {
                            $subFolderName = $this->createSubFolderWithContent($baseFolderID, $row, $propertyColumns);
                        }
                        else
                        {
                            $subFolderName = $row[$propertyColumns->subFolderName];
                        }

                        $this->log("Folder name ".$subFolderName);
                        echo "Folder name ".$subFolderName.PHP_EOL;

                        $this->updateCellContent(
                            $subFolderName,
                            $numRow,
                            $tableID,
                            SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->subFolderName));

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
                                $numRow,
                                $tableID,
                                SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->imagesRaw));
                        }
                    }
                }

                // Waiting so as not to exceed reads and writes quota.
                //sleep(33);
            }
        }
    }
