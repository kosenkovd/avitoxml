<?php
    
    
    namespace App\Console\Jobs;
    
    
    use App\Configuration\XmlGeneration;
    use App\Helpers\SpreadsheetHelper;
    use App\Models\Table;
    use App\Models\TableHeader;
    use App\Services\Interfaces\ISpintaxService;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use Illuminate\Support\Facades\Log;
    
    class RandomizeTextJob extends JobBase {
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
         * @param string $tableId spreadsheet id.
         * @param int $patternCol column to take pattern from.
         * @param int $resultCol column to fill in randomized result.
         * @param int $numRow row number, required for spreadsheet update.
         * @param array $row row data.
         * @return string
         * @throws \Exception
         */
        private function randomizeText(
            string $tableId,
            int $patternCol,
            int $resultCol,
            int $numRow,
            array $row
        ): string
        {
            $alreadyFilled = isset($row[$resultCol]) && $row[$resultCol] != '';
            $noSource = !isset($row[$patternCol]) || $row[$patternCol] == '';
            
            if ($alreadyFilled) {
                return $row[$resultCol];
            }
            
            if ($noSource) {
                return '';
            }
            
            // Счет строк начинается с 1, а не с 0 и первая строка - заголовок
            $numRow += +2;
            
            $message = "Table '".$tableId."' randomizing row ".$numRow."...";
            $this->log($message);
            Log::info($message);
            
            $this->needsToUpdate = true;
            return $this->spintaxService->randomize($row[$patternCol]);
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
            $values = $this->getFullDataFromTable($tableId, $sheetName);
            $propertyColumns = new TableHeader(array_shift($values));
            
            if ($propertyColumns && empty($values)) {
                return;
            }
            
            $randomizers = [
                [
                    "pattern" => $propertyColumns->titleSpintax,
                    "result" => $propertyColumns->title
                ],
                [
                    "pattern" => $propertyColumns->descriptionSpintax,
                    "result" => $propertyColumns->description
                ],
                [
                    "pattern" => $propertyColumns->priceSpintax,
                    "result" => is_null($propertyColumns->price) ? $propertyColumns->salary : $propertyColumns->price
                ]
            ];
            
            foreach ($randomizers as $randomizer) {
                if (is_null($randomizer["pattern"]) || is_null($randomizer["result"])) {
                    continue;
                }
                
                $randomizedText = [];
                foreach ($values as $numRow => $row) {
                    $randomizedText[$numRow][] = $this->randomizeText(
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
                        $randomizedText,
                        [
                            'valueInputOption' => 'RAW'
                        ]
                    );
                } catch (\Exception $exception) {
                    $message = "Error on '".$tableId."' while writing to table".PHP_EOL.
                        $exception->getMessage();
                    $this->log($message);
                    Log::error($message);
                    
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
            Log::info($message);
            
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
                    Log::info($message);
                    
                    $this->processSheet($tableId, $targetSheet);
                    $this->stopIfTimeout();
                }
            }
            
            $message = "Table '".$table->getGoogleSheetId()."' finished.";
            $this->log($message);
            Log::info($message);
        }
    }
