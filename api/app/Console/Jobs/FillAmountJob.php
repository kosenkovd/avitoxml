<?php
    
    
    namespace App\Console\Jobs;
    
    use App\Configuration\Spreadsheet\SheetNames;
    use App\Configuration\XmlGeneration;
    use App\Helpers\SpreadsheetHelper;
    use App\Models\Table;
    use App\Repositories\Interfaces\ITableRepository;
    use App\Services\Interfaces\ISpreadsheetClientService;

    class FillAmountJob extends JobBase {
        /**
         * @var int max time to execute job.
         */
        protected int $maxJobTime = 60 * 5;
        
        /**
         * @var bool is logging enabled.
         */
        protected bool $loggingEnabled = true;
        
        protected bool $timeoutEnabled = true;
        
        /** @var int seconds to sleep between requests to avito.ru */
        private int $secondsToSleepAvito = 6;
        
        /** @var int id of city for row */
        private int $cityId;
        
        /** @var string[][] values to write */
        private array $newValues = [];
        
        private bool $needsToUpdate = false;
        
        private ?int $lastRowUntilJobStops = null;
        
        private int $cityNameColumnNum = 0;
        private int $cityIdColumnNum = 1;
    
    
        /**
         * @var SheetNames
         */
        protected SheetNames $sheetNamesConfig;
        
        private ITableRepository $tableRepository;
        
        private XmlGeneration $xmlGeneration;
    
        /**
         * Fills cityIds and amount for query from table column name for specified sheet.
         *
         * @param string $tableID Google spreadsheet id.
         * @param string $sheetName target sheet.
         * @throws \Exception
         */
        private function processSheet(string $tableID, string $sheetName): void
        {
            $message = $tableID." processing...";
            dump($message);
            
            $values = $this->getFullDataFromTable($tableID, $sheetName);
            
            if (empty($values)) {
                return;
            }
            
            // Заголовки
            $propertyColumns = array_shift($values);
    
            $this->getNewValues($tableID, $values, $propertyColumns);
            $this->fillSheet($tableID, $sheetName);
        }
        
        private function getAvitoCityId(string $city): int
        {
            $url = "https://www.avito.ru/web/1/slocations?limit=2&q=".urlencode($city);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            
            sleep($this->secondsToSleepAvito);
    
            $locations = json_decode($result)->result->locations;
            
            if (
                isset($locations[1]) &&
                isset($locations[1]->parent) &&
                ((int)$locations[1]->parent->id === (int)$locations[0]->id)
            ) {
                return (int)$locations[1]->id;
            } else {
                return (int)$locations[0]->id;
            }
        }
        
        private function getAvitoAmount(string $cityId, string $filling): int
        {
            $url = "https://www.avito.ru/js/catalog?locationId=".$cityId."&name=".urlencode($filling)."&countOnly=1&bt=1";
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
    
            sleep($this->secondsToSleepAvito);
            
            return json_decode($result)->mainCount;
        }
        
        private function getNewValues(string $tableID, array $values, array $propertyColumns): void
        {
            foreach ($values as $numRow => $row) {
                // content starts at line 2
                $spreadsheetRowNum = $numRow + 2;
        
                if ($this->checkIsTimeout()) {
                    dump("timeout");
                    
                    // row before that row
                    $this->lastRowUntilJobStops = $spreadsheetRowNum - 1;
                    
                    return;
                }
        
                $cityName = $row[$this->cityNameColumnNum];
        
                foreach ($propertyColumns as $column => $propertyColumn) {
                    
                    if ($column === $this->cityNameColumnNum) {
                        $this->newValues[$numRow][$column] = $cityName;
                    }
            
                    $alreadyFilled = isset($row[$column]) &&
                        trim($row[$column]) != '';
            
                    // check alreadyFilled
                    if ($alreadyFilled) {
                        if ($column === $this->cityIdColumnNum) {
                            $this->cityId = $row[$column];
                        }
                
                        $this->newValues[$numRow][$column] = $row[$column];
                
                        continue;
                    }
            
                    $cell = SpreadsheetHelper::getColumnLetterByNumber($column).$spreadsheetRowNum;
            
                    if ($column === $this->cityIdColumnNum) {
                        $message = $tableID." filling city id on ".$cell;
                        dump($message);
                        
                        try {
                            $this->cityId = $this->getAvitoCityId($cityName);
                        } catch (\Exception $exception) {
                            dump($exception);
                            
                            $this->newValues[$numRow][$column] = '';
                            $this->lastRowUntilJobStops = $spreadsheetRowNum;
                            
                            return;
                        }
    
                        $this->newValues[$numRow][$column] = $this->cityId;
                        $this->needsToUpdate = true;
    
                        continue;
                    }
            
                    $message = $tableID." filling ".$cell;
                    dump($message);
                    try {
                        $amount = $this->getAvitoAmount($this->cityId, $propertyColumn);
                    } catch (\Exception $exception) {
                        dump($exception);
                        
                        $this->newValues[$numRow][$column] = '';
                        $this->lastRowUntilJobStops = $spreadsheetRowNum;
                        
                        return;
                    }
    
                    $this->newValues[$numRow][$column] = $amount;
                    $this->needsToUpdate = true;
                }
            }
        }
        
        private function fillSheet(string $tableID, string $sheetName)
        {
            if (!$this->needsToUpdate) {
                $message = $tableID." is already filled.";
                dump($message);
                return;
            }
            
            if ($this->checkIsTimeout() || !is_null($this->lastRowUntilJobStops)) {
                $range = $sheetName.'!A2:FZ'.$this->lastRowUntilJobStops;
            } else {
                $range = $sheetName.'!A2:FZ5001';
            }
            
            $message = $tableID." writing values to table...";
            dump($message);
    
            try {
                $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                    $tableID,
                    $range,
                    $this->newValues,
                    [
                        'valueInputOption' => 'RAW'
                    ]
                );
            } catch (\Exception $exception) {
                dump($exception);
            }
    
            $message = $tableID." successfully wrote values.";
            dump($message);
        }
        
        public function __construct(
            ISpreadsheetClientService $spreadsheetClientService,
            ITableRepository $tableRepository,
            XmlGeneration $xmlGeneration)
        {
            parent::__construct($spreadsheetClientService);
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
            $this->startTimestamp = time();
            $targetSheet = 'Лист1';
            $this->processSheet(
                $table->getGoogleSheetId(),
                $targetSheet
            );
        }
    }
