<?php
    
    
    namespace App\Console\Jobs;
    
    
    use App\Configuration\Spreadsheet\SheetNames;
    use App\Configuration\XmlGeneration;
    use App\Models\Generator;
    use App\Models\Table;
    use App\Repositories\Interfaces\IGeneratorRepository;
    use App\Repositories\Interfaces\ITableRepository;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use App\Services\Interfaces\IXmlGenerationService;
    use DateTime;
    use \Exception;
    use Illuminate\Support\Facades\Log;
    
    class GenerateXMLJob extends JobBase {
        private IXmlGenerationService $xmlGenerationService;
        
        private XmlGeneration $xmlGeneration;
        
        private ITableRepository $tablesRepository;
        
        private IGeneratorRepository $generatorRepository;
        
        private SheetNames $sheetNamesConfig;
        
        /**
         * @var bool is logging enabled.
         */
        protected bool $loggingEnabled = true;
        
        protected bool $timeoutEnabled = false;
        
        protected int $maxJobTime = 60 * 60;
        
        /**
         *
         * @param Table $table
         * @param Generator $generator
         * @throws Exception
         */
        private function processSheet(Table $table, Generator $generator): void
        {
            try {
                switch ($generator->getTargetPlatform()) {
                    case "Яндекс":
                        $content = $this->xmlGenerationService->generateYandexXML(
                            $table->getGoogleSheetId(),
                            $generator->getTargetPlatform()
                        );
                        break;
                    default:
                        $content = $this->xmlGenerationService->generateAvitoXML(
                            $table->getGoogleSheetId(),
                            $generator->getTargetPlatform()
                        );
                }
                $this->generatorRepository->setLastGeneration($generator->getGeneratorId(), $content);
            } catch (Exception $exception) {
                $message = "Error on '".$table->getGoogleSheetId()."' while processSheet".PHP_EOL.
                    $exception->getMessage();
                $this->log($message);
                Log::error($message);
                
                throw $exception;
            }
        }
        
        public function __construct(
            ISpreadsheetClientService $spreadsheetClientService,
            XmlGeneration $xmlGeneration,
            ITableRepository $tableRepository,
            IGeneratorRepository $generatorRepository,
            IXmlGenerationService $xmlGenerationService,
            SheetNames $sheetNamesConfig
        )
        {
            parent::__construct($spreadsheetClientService);
            $this->xmlGeneration = $xmlGeneration;
            $this->tablesRepository = $tableRepository;
            $this->generatorRepository = $generatorRepository;
            $this->xmlGenerationService = $xmlGenerationService;
            $this->sheetNamesConfig = $sheetNamesConfig;
        }
        
        /**
         * Start job.
         *
         * Generate xml for table.
         *
         * @param Table $table table to process.
         * @throws Exception
         */
        public function start(Table $table): void
        {
            $message = "Table '".$table->getGoogleSheetId()."' processing...";
            $this->log($message);
            Log::info($message);
            
            $this->startTimestamp = time();
            
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
                    
                    $this->processSheet($table, $generator);
    
                    $message = "Table '".$table->getGoogleSheetId()."' processing sheet '".$targetSheet."' finished.";
                    $this->log($message);
                    Log::info($message);
                    
                    $this->stopIfTimeout();
                }
            }
            
            $message = "Table '".$table->getGoogleSheetId()."' finished.";
            $this->log($message);
            Log::info($message);
        }
    }
