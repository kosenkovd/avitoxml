<?php
    
    
    namespace App\Console\Jobs;
    
    
    use App\Configuration\Spreadsheet\SheetNames;
    use App\Configuration\XmlGeneration;
    use App\Models\GeneratorLaravel;
    use App\Models\TableMarketplace;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use App\Services\Interfaces\IXmlGenerationService;
    use \Exception;
    use Illuminate\Support\Facades\Log;
    
    class GenerateOzonXMLJob extends JobBase {
        private IXmlGenerationService $xmlGenerationService;
        
        private XmlGeneration $xmlGeneration;
        
        private SheetNames $sheetNamesConfig;
    
        protected string $logChannel = 'OZON';
        
        /**
         * @var bool is logging enabled.
         */
        protected bool $loggingEnabled = true;
        
        protected bool $timeoutEnabled = false;
        
        protected int $maxJobTime = 60 * 60;
    
        /**
         *
         * @param TableMarketplace $table
         * @param GeneratorLaravel $generator
         *
         * @throws Exception
         */
        private function processSheet(TableMarketplace $table, GeneratorLaravel $generator): void
        {
            try {
                switch ($generator->targetPlatform) {
                    case "OZON":
                        $content = $this->xmlGenerationService->generateOzonXML(
                            $table->googleSheetId,
                            $generator->targetPlatform,
                            $generator->maxAds
                        );
                        break;
                    default:
                        return;
                }
                
                $generator->lastGeneration = $content;
                $generator->update();
            } catch (Exception $exception) {
                Log::channel($this->logChannel)
                    ->error("Error on '".$table->googleSheetId."' while processSheet".PHP_EOL
                        .$exception->getMessage());
                
                throw $exception;
            }
        }
        
        public function __construct(
            ISpreadsheetClientService $spreadsheetClientService,
            XmlGeneration $xmlGeneration,
            IXmlGenerationService $xmlGenerationService,
            SheetNames $sheetNamesConfig
        )
        {
            parent::__construct($spreadsheetClientService);
            $this->xmlGeneration = $xmlGeneration;
            $this->xmlGenerationService = $xmlGenerationService;
            $this->sheetNamesConfig = $sheetNamesConfig;
        }
    
        /**
         * Start job.
         *
         * Generate xml for table.
         *
         * @param TableMarketplace $table table to process.
         *
         * @throws Exception
         */
        public function start(TableMarketplace $table): void
        {
            Log::channel($this->logChannel)->info("Table '".$table->googleSheetId."' processing...");
            
            $this->startTimestamp = time();
            
            $existingSheets = $this->spreadsheetClientService->getSheets(
                $table->googleSheetId
            );
            
            /** @var GeneratorLaravel $generator */
            foreach ($table->generators as $generator) {
                switch ($generator->targetPlatform) {
                    case "OZON":
                        $targetSheets = $this->xmlGeneration->getOzonTabs();
                        break;
                }
                
                $splitTargetSheets = explode(",", $targetSheets);
                foreach ($splitTargetSheets as $targetSheet) {
                    $targetSheet = trim($targetSheet);
                    if (!in_array($targetSheet, $existingSheets)) {
                        continue;
                    }
                    
                    Log::channel($this->logChannel)->info("Table '".$table->googleSheetId."' processing sheet '"
                        .$targetSheet."'...");
                    
                    $this->processSheet($table, $generator);
    
                    Log::channel($this->logChannel)->info("Table '".$table->googleSheetId."' processing sheet '"
                        .$targetSheet."' finished.");
                    
                    $this->stopIfTimeout();
                }
            }
            
            Log::channel($this->logChannel)->info("Table '".$table->googleSheetId."' finished.");
        }
    }
