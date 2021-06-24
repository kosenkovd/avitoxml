<?php
    
    
    namespace App\Console\Jobs;
    
    
    use App\Configuration\XmlGeneration;
    use App\Helpers\SpreadsheetHelper;
    use App\Models\Table;
    use App\Models\TableHeader;
    use App\Services\Interfaces\ISpintaxService;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Log;
    
    class RandomizeTextJob extends JobBase
    {
        const title = "title";
        const price = "price";
        const description = "description";
        
        private array $randomizedText = [];
        
        private ISpintaxService $spintaxService;
        
        /**
         * @var bool is logging enabled.
         */
        protected bool $loggingEnabled = true;
        
        protected bool $timeoutEnabled = false;
        
        protected int $maxJobTime = 60 * 60;
        
        private bool $needsToUpdate = false;
        
        private XmlGeneration $xmlGeneration;
    
        /**
         * Randomises text in specified result column based on pattern column and updates spreadsheet.
         *
         * @param string $tableId    spreadsheet id.
         * @param int    $patternCol column to take pattern from.
         * @param int    $resultCol  column to fill in randomized result.
         * @param int    $numRow     row number, required for spreadsheet update.
         * @param array  $row        row data.
         *
         * @return string
         */
        private function randomizeText(
            string $tableId,
            int $patternCol,
            int $resultCol,
            int $numRow,
            array $row
        ): string
        {
            $alreadyFilled = isset($row[$resultCol]) && trim($row[$resultCol]) != '';
            $noSource = !isset($row[$patternCol]) || trim($row[$patternCol]) == '';
            
            if ($alreadyFilled) {
                return $row[$resultCol];
            }
            
            if ($noSource) {
                return '';
            }
            
            // Счет строк начинается с 1, а не с 0 и первая строка - заголовок
            $message = "Table '".$tableId."' randomizing row ".($numRow + 2)."...";
            $this->log($message);
            Log::channel($this->logChannel)->info($message);
            
            $this->needsToUpdate = true;
            
            $pattern = $row[$patternCol];
    
            $pattern = preg_replace_callback(
                '/#start_no_space#(.*?)#stop_no_space#/s',
                function (array $matches): string {
                    return preg_replace('{\s+}s', '', $matches[1]);
                },
                $pattern
            );
            
            $title = $this->randomizedText[self::title][$numRow][0] ?? '';
            $price = $this->randomizedText[self::price][$numRow][0] ?? '';
    
            $date = Carbon::now();
            $format = 'd.m.Y';
            $pattern = preg_replace(
                [
                    '/%title%/',
                    '/%price%/',
                    '/%date%/',
                    '/%yesterday_date%/',
                    '/%tomorrow_date%/',
                    '/%next_week%/',
                ],
                [
                    $title,
                    $price,
                    $date->format($format),
                    $date->subDay()->format($format),
                    $date->addDays(2)->format($format),
                    $date->addWeek()->format($format),
                ],
                $pattern
            );
            
            return $this->spintaxService->randomize($pattern);
        }
        
        
        /**
         * Randomize text for specified generator.
         *
         * @param string $tableId Google spreadsheet id.
         * @param string $sheetName sheet name.
         * @throws \Exception
         */
        private function processSheet(string $tableId, string $sheetName): void
        {
            [$propertyColumns, $values] = $this->getHeaderAndDataFromTable(
                $tableId,
                $sheetName
            );
            
            if ($propertyColumns && empty($values)) {
                return;
            }
            
            $randomizers = [
                self::title =>[
                    "pattern" => $propertyColumns->titleSpintax,
                    "result" => $propertyColumns->title
                ],
                self::price => [
                    "pattern" => $propertyColumns->priceSpintax,
                    "result" => is_null($propertyColumns->price) ? $propertyColumns->salary : $propertyColumns->price
                ],
                self::description => [
                    "pattern" => $propertyColumns->descriptionSpintax,
                    "result" => $propertyColumns->description
                ]
            ];
    
            $this->randomizedText = [];
            foreach ($randomizers as $name => $randomizer) {
                if (is_null($randomizer["pattern"]) || is_null($randomizer["result"])) {
                    continue;
                }
                
                $this->randomizedText[$name] = [];
                foreach ($values as $numRow => $row) {
                    $this->randomizedText[$name][$numRow][] = $this->randomizeText(
                        $tableId,
                        $randomizer["pattern"],
                        $randomizer["result"],
                        $numRow,
                        $row
                    );
                }
                
                if (!$this->needsToUpdate) {
                    continue;
                }
                
                try {
                    $columnLetter = SpreadsheetHelper::getColumnLetterByNumber($randomizer['result']);
                    $range = $sheetName.'!'.$columnLetter.'2:'.$columnLetter.'5001';
                    $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                        $tableId,
                        $range,
                        $this->randomizedText[$name],
                        [
                            'valueInputOption' => 'RAW'
                        ]
                    );
                } catch (\Exception $exception) {
                    $message = "Error on '".$tableId."' while writing to table".PHP_EOL.
                        $exception->getMessage();
                    $this->log($message);
                    Log::channel($this->logChannel)->error($message);
                    
                    throw $exception;
                }
                
                sleep(1);
            }
        }
        
        public function __construct(
            ISpintaxService $spintaxService,
            ISpreadsheetClientService $spreadsheetClientService,
            XmlGeneration $xmlGeneration)
        {
            parent::__construct($spreadsheetClientService);
            $this->spintaxService = $spintaxService;
            $this->xmlGeneration = $xmlGeneration;
        }
        
        /**
         * Start job.
         *
         * Randomizes texts in all tables that were not randomized before.
         *
         * @param Table $table table to process.
         * @throws \Exception
         */
        public function start(Table $table): void
        {
            $message = "Table '".$table->getGoogleSheetId()."' processing...";
            $this->log($message);
            Log::channel($this->logChannel)->info($message);
            
            $this->startTimestamp = time();
            $tableId = $table->getGoogleSheetId();
            
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
                    Log::channel($this->logChannel)->info($message);
                    
                    $this->processSheet($tableId, $targetSheet);
                    $this->stopIfTimeout();
                }
            }
            
            $message = "Table '".$table->getGoogleSheetId()."' finished.";
            $this->log($message);
            Log::channel($this->logChannel)->info($message);
        }
    }
