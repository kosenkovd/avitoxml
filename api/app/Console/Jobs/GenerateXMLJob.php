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
//            try {
//                $timeModified = $this->spreadsheetClientService->getFileModifiedTime($table->getGoogleSheetId());
//            } catch (Exception $exception) {
//                $message = "Error getting last modified on '" . $table->getGoogleDriveId(). "'" . PHP_EOL .
//                    $exception->getMessage();
//                $this->log($message);
//                Log::error($message);
//                $this->throwExceptionIfQuota($exception);
//                return;
//            }
            
//            if ($this->isTableExpiredOrDeleted($table) || $this->isXmlActual($generator, $timeModified)) {
//                $message = "Generator for table '" . $table->getGoogleDriveId(). "'" . " is up to date.";
//                $this->log($message);
//                Log::info($message);
//            } else {
                try {
                    $content = $this->xmlGenerationService->generateAvitoXML(
                        $table->getGoogleSheetId(),
                        $generator->getTargetPlatform()
                    );
                    $generator->setLastGenerated(time());
                    $this->generatorRepository->update($generator);
                    $this->generatorRepository->setLastGeneration($generator->getGeneratorId(), $content);
                } catch (Exception $exception) {
                    $message = "Error on '" . $table->getGoogleDriveId(). "'" . PHP_EOL .
                        $exception->getMessage();
                    $this->log($message);
                    Log::error($message);
                    $this->throwExceptionIfQuota($exception);
                }
//            }
        }
    
        /**
         * Expired or deleted tables always return last generated XML
         *
         * @param Table $table
         * @return bool
         */
        private function isTableExpiredOrDeleted(Table $table): bool
        {
            return $table->isDeleted() ||
                (!is_null($table->getDateExpired()) && $table->getDateExpired() < time());
        }
    
        /**
         * Xml must be regenerated twice an hour to update yandex ads that rely on date created that can be set long
         * before real actual date
         *
         * @param Generator $generator
         * @param DateTime $timeModified
         * @return bool
         */
        private function isXmlActual(Generator $generator, DateTime $timeModified): bool
        {
            return ($generator->getTargetPlatform() != $this->sheetNamesConfig->getYandex() ||
                    time() - $generator->getLastGenerated() < 1800) &&
                (is_null($timeModified) || ($generator->getLastGenerated() > $timeModified->getTimestamp()));
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
         * Randomizes texts in all tables that were not randomized before.
         *
         * @param Table $table table to process.
         * @throws Exception
         */
        public function start(Table $table): void
        {
            $this->log("Processing table '" . $table->getGoogleSheetId() . "'...");
            $this->startTimestamp = time();
            
            $existingSheets = $this->spreadsheetClientService->getSheets(
                $table->getGoogleSheetId()
//            $table->getTableGuid()."rtj"
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
                    
                    $this->log("Processing table '" . $table->getGoogleSheetId() . "'" .
                        ", sheet " . $targetSheet . "...");
                    $this->processSheet($table, $generator);
                    $this->stopIfTimeout();
                }
            }
            
            $this->log("Finished table '" . $table->getGoogleSheetId() . "'...");
        }
    }
