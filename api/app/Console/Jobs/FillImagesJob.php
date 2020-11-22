<?php
    
    
    namespace App\Console\Jobs;
    
    use App\Models\TableHeader;
    use App\Repositories\Interfaces\ITableRepository;
    use App\Services\Interfaces\IGoogleServicesClient;
    
    class FillImagesJob {
        private IGoogleServicesClient $googleClient;
        private ITableRepository $tableRepository;
        
        public function __construct(
            IGoogleServicesClient $googleClient,
            ITableRepository $tableRepository
        )
        {
            $this->googleClient = $googleClient;
            $this->tableRepository = $tableRepository;
        }
        
        public function start(): void
        {
            $tables = $this->tableRepository->getTables();
            
            foreach ($tables as $table) {
                $tableID = $table->getGoogleSheetId();
                $folderID = $table->getGoogleDriveId();
    
                $headerRange = 'Sheet1!A1:FZ1';
                $headerResponse = $this->googleClient->getSpreadsheetCellsRange($tableID, $headerRange);
                $propertyColumns = new TableHeader($headerResponse[0]);
    
                $range = 'Sheet1!A2:FZ5001';
                $values = $this->googleClient->getSpreadsheetCellsRange($tableID, $range);
    
                if (!empty($values))
                {
                    foreach ($values as $numRow => $row) {
                        if (isset($row[$propertyColumns->subFolderName])) {
                            $subFolderName = $row[$propertyColumns->subFolderName];
                            $images = $this->getImages($folderID, $subFolderName);
                            if ($images !== []) {
                                $this->addImagesToTable($images, $numRow, $tableID, getColumnLetterByNumber($propertyColumns->imagesRaw));
                            }
                        }
                    }
                }
            }
        }
        
        /**
         * Get Images from GoogleDrive.
         *
         * @param string $folderID folder id.
         * @param string $subFolderName sub folder name.
         * @return array images in folders
         */
        protected function getImages(string $folderID, string $subFolderName): array
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
        protected function addImagesToTable(array $images, int $numRow, string $tableID, string $columnName): void
        {
            // Счет строк начинается с 1, а не с 0 и первая строка - заголовок
            $numRow += +2;
            $range = 'Sheet1!' . $columnName . $numRow . ':' . $columnName . $numRow;
            
            $imagesString = join(PHP_EOL, $images);
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
    }
