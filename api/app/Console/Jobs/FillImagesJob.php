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
         * @param array $images images to add.
         * @param int $numRow number of row.
         * @param string $tableID table id.
         * @param string $columnName col name.
         * @return void
         */
        private function addImagesToTable(array $images, int $numRow, string $tableID, string $columnName): void
        {
            // Счет строк начинается с 1, а не с 0 и первая строка - заголовок
            $numRow += +2;
            $range = 'Sheet1!' . $columnName . $numRow . ':' . $columnName . $numRow;

            $links = [];
            foreach ($images as $image)
            {
                $links[] = LinkHelper::getPictureDownloadLink($image->id);
            }
            $imagesString = join(PHP_EOL, $links);
            $values = [
                [$imagesString]
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
            $newFolderName = crc32b(Guid::uuid4()->toString());
            $newFolderId = $this->googleClient->createFolder($newFolderName);

            foreach ($sourceFolders as $sourceFolder)
            {
                $sourceFolderId = $this->googleClient->getChildFolderByName($baseFolderId, $sourceFolder);
                $images = $this->googleClient->listFolderImages($sourceFolderId);
                if(count($images) == 0)
                {
                    continue;
                }
                $this->googleClient->moveFile($images[0], $newFolderId);
            }

            return $newFolderId;
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
            $tables = $this->tableRepository->getTables();

            foreach ($tables as $table)
            {
                $tableID = $table->getGoogleSheetId();
                echo "Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId().PHP_EOL;
                $baseFolderID = $table->getGoogleDriveId();

                $headerRange = 'Sheet1!A1:FZ1';
                $headerResponse = $this->googleClient->getSpreadsheetCellsRange($tableID, $headerRange);
                $propertyColumns = new TableHeader($headerResponse[0]);

                $range = 'Sheet1!A2:FZ5001';
                $values = $this->googleClient->getSpreadsheetCellsRange($tableID, $range);

                if (!empty($values))
                {
                    foreach ($values as $numRow => $row) {
                        $alreadyFilled = isset($row[$propertyColumns->imagesRaw]) &&
                            $row[$propertyColumns->imagesRaw] != '';
                        $noSource = !isset($row[$propertyColumns->photoSourceFolder]) ||
                            $row[$propertyColumns->photoSourceFolder] == '';

                        if($alreadyFilled || $noSource)
                        {
                            continue;
                        }

                        echo "Filling row ".$numRow.", subfolder ".$row[$propertyColumns->subFolderName].PHP_EOL;

                        if(!isset($row[$propertyColumns->subFolderName]) ||
                            $row[$propertyColumns->subFolderName] == '')
                        {
                            $subFolderName = $this->createSubFolderWithContent($baseFolderID, $row, $propertyColumns);
                        }
                        else
                        {
                            $subFolderName = $row[$propertyColumns->subFolderName];
                        }

                        $images = $this->getImages($baseFolderID, $subFolderName);
                        if ($images !== []) {
                            $this->addImagesToTable(
                                $images,
                                $numRow,
                                $tableID,
                                SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->imagesRaw));
                        }
                    }
                }

                // Waiting so as not to exceed reads and writes quota.
                //sleep(20);
            }
        }
    }
