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
    use function PHPUnit\Framework\isNull;

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
        
        private int $secondsToSleepAvito = 6;
        private int $secondsToSleepGoogle = 2;
//        private int $mSecondsToSleepAvito = 6000;
        
        private ?int $cityId = null;
        private array $newValues = [];
        private bool $needsToUpdate = false;
        private bool $stoppedDueAvitoQuota = false;
        private ?int $lastRow = null;
        
        /**
         * @var SheetNames
         */
        protected SheetNames $sheetNamesConfig;
        
        private ITableRepository $tableRepository;
        
        private XmlGeneration $xmlGeneration;
    
        /**
         * Fills amount for specified generator.
         *
         * @param string $tableID Google spreadsheet id.
         * @param string $sheetName target sheet.
         * @param string $quotaUserPrefix
         * @throws \Exception
         */
        private function processSheet(string $tableID, string $sheetName): void
        {
            
            dd('testing');
            $message = $tableID." processing...";
            dump($message);
            
            $values = $this->getFullDataFromTable($tableID, $sheetName);
            
            if (empty($values)) {
                return;
            }
            
            // Заголовки
            $propertyColumns = array_shift($values);
    
            foreach ($values as $numRow => $row) {
                // content starts at line 2
                $spreadsheetRowNum = $numRow + 2;
                
                if($this->timeoutEnabled && (time() >= $this->startTimestamp + $this->maxJobTime)) {
                    // fill what we can
                    // row before that
                    $this->lastRow = $spreadsheetRowNum - 1;
    
                    dump("timeout");
    
                    break;
                }
    
                if ($this->stoppedDueAvitoQuota) {
                    break;
                }
                
                // city name
                $city = $row[0];
    
                foreach ($propertyColumns as $column => $propertyColumn) {
                    if ($this->stoppedDueAvitoQuota) {
                        break;
                    }
                    
                    // city
                    if ($column === 0) {
                        $this->newValues[$numRow][$column] = $city;
                    }
    
                    $alreadyFilled = isset($row[$column]) &&
                        trim($row[$column]) != '';
    
                    // check alreadyFilled
                    if ($alreadyFilled) {
                        if ($column === 1) {
                            $this->cityId = $row[$column];
                        }
                        
                        $this->newValues[$numRow][$column] = $row[$column];
                        
                        continue;
                    }
                    
                    $cell = SpreadsheetHelper::getColumnLetterByNumber($column).$spreadsheetRowNum;
                    
                    // cityId
                    if ($column === 1) {
                        $message = $tableID." filling city id on ".$cell;
                        dump($message);
                        try {
                            $this->cityId = $this->getAvitoCityId($city);
                            $this->newValues[$numRow][$column] = $this->cityId;
                        } catch (\Exception $exception) {
                            // fill what we can
                            $this->newValues[$numRow][$column] = '';
                            $this->stoppedDueAvitoQuota = true;
                            $this->lastRow = $spreadsheetRowNum;
                            
                            dump($exception);
                            
                            break;
                        }
    
                        $this->needsToUpdate = true;
                        
                        continue;
                    }
    
                    $message = $tableID." filling ".$cell;
                    dump($message);
                    try {
                        $amount = $this->getAvitoAmount($this->cityId, $propertyColumn);
                    } catch (\Exception $exception) {
                        // fill what we can
                        $this->newValues[$numRow][$column] = '';
                        $this->stoppedDueAvitoQuota = true;
                        $this->lastRow = $spreadsheetRowNum;
    
                        dump($exception);
    
                        break;
                    }
                    
                    $this->newValues[$numRow][$column] = $amount;
                    $this->needsToUpdate = true;
                }
            }
    
            if (
                $this->stoppedDueAvitoQuota ||
                ($this->timeoutEnabled && (time() >= $this->startTimestamp + $this->maxJobTime))
            ) {
                $range = $sheetName.'!A2:FZ'.$this->lastRow;
            } else {
                $range = $sheetName.'!A2:FZ5001';
            }
    
            if (!$this->needsToUpdate) {
                $message = $tableID." is already full.";
                dump($message);
                return;
            }
            
            $message = $tableID." writing values to table...";
            dump($message);
            
           
            $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                $tableID,
                $range,
                $this->newValues,
                [
                    'valueInputOption' => 'RAW'
                ],
                false
            );
    
            $message = $tableID." successfully wrote values.";
            dump($message);
            
            if ($this->stoppedDueAvitoQuota) {
                $message = $tableID." needs to restart";
                dump($message);
            }
        }
        
        private function getAvitoCityId(string $city): int
        {
            $url = "https://www.avito.ru/web/1/slocations?limit=2&q=".urlencode($city);
//            $opts = array(
//                'http'=>array(
//                    'method'=>"GET",
//                    'header'=>"Host: www.avito.ru"
//                )
//            );
//
//            $context = stream_context_create($opts);
//            $result = file_get_contents($url, false, $context);
    
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
    
            curl_close($ch);
            
            sleep($this->secondsToSleepAvito);
//            usleep($this->mSecondsToSleepAvito);
    
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
//            // Создаём поток
//            $opts = array(
//                'http'=>array(
//                    'method'=>"GET",
//                    'header'=>"Host: www.avito.ru"
//                )
//            );
//
//            $context = stream_context_create($opts);
//            $result = file_get_contents($url, false, $context);
    
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
    
            curl_close($ch);
            
            
    
            sleep($this->secondsToSleepAvito);
//            usleep($this->mSecondsToSleepAvito);
            
            return json_decode($result)->mainCount;
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
//            $message = "Table '".$table->getGoogleSheetId()."' processing...";
//            $this->log($message);
//            Log::info($message);
            
            $this->startTimestamp = time();
//            $baseFolderID = "";

//            $existingSheets = $this->spreadsheetClientService->getSheets(
//                $table->getGoogleSheetId()
////                $table->getTableGuid()."fijy"
//            );

//            foreach ($table->getGenerators() as $generator)
//            {
//                switch($generator->getTargetPlatform())
//                {
//                    case "Avito":
//                        $targetSheets = $this->xmlGeneration->getAvitoTabs();
//                        break;
//                    case "Юла":
//                        $targetSheets = $this->xmlGeneration->getYoulaTabs();
//                        break;
//                    case "Яндекс":
//                        $targetSheets = $this->xmlGeneration->getYandexTabs();
//                        break;
//                }
//
//                $splitTargetSheets = explode(",", $targetSheets);
//                foreach ($splitTargetSheets as $targetSheet)
//                {
//                    $targetSheet = trim($targetSheet);
////                    if(!in_array($targetSheet, $existingSheets))
////                    {
////                        continue;
////                    }
//

//            $quotaUserPrefix = substr($table->getTableGuid(), 0, 10).
//                (strlen($targetSheet) > 10 ? substr($targetSheet, 0, 10) : $targetSheet).
//                "RTJ";
//
//                    $message = "Table '".$table->getGoogleSheetId()."' processing sheet '".$targetSheet."'...";
////                    $this->log($message);
////                    Log::info($message);
//
            
            $targetSheet = 'Лист1';
            $this->processSheet(
                $table->getGoogleSheetId(),
                $targetSheet
            );
//                    $this->stopIfTimeout();
//                }
//            }

//            $message = "Table '".$table->getGoogleSheetId()."' finished.";
//            $this->log($message);
//            Log::info($message);
        }
    }
