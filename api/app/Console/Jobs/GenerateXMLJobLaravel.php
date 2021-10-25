<?php


namespace App\Console\Jobs;


use App\Configuration\XmlGeneration;
use App\Models\GeneratorLaravel;
use App\Models\TableLaravel;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IXmlGenerationService;
use \Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateXMLJobLaravel extends JobBase
{
    private IXmlGenerationService $xmlGenerationService;
    
    private XmlGeneration $xmlGeneration;
    
    protected bool $loggingEnabled = true;
    
    protected bool $timeoutEnabled = false;
    
    protected int $maxJobTime = 60 * 60;
    
    private string $generatorsContentTable = 'avitoxml_generators_content';
    private int $maxContentFragmentLength = 30000000;
    
    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService,
        XmlGeneration             $xmlGeneration,
        IXmlGenerationService     $xmlGenerationService
    )
    {
        parent::__construct($spreadsheetClientService);
        $this->xmlGeneration = $xmlGeneration;
        $this->xmlGenerationService = $xmlGenerationService;
    }
    
    /**
     * Start job.
     *
     * Generate xml for table.
     *
     * @param TableLaravel $table table to process.
     *
     * @throws Exception
     */
    public function start(TableLaravel $table): void
    {
        $googleSheetId = $table->googleSheetId;
        $message = "Table '".$googleSheetId."' processing...";
        $this->log($message);
        Log::channel($this->logChannel)->info($message);
        
        $this->startTimestamp = time();
        
        $existingSheets = $this->spreadsheetClientService->getSheets(
            $googleSheetId
        );
        
        /** @var GeneratorLaravel $generator */
        foreach ($table->generators as $generator) {
            switch ($generator->targetPlatform) {
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
                
                $message = "Table '".$googleSheetId."' processing sheet '".$targetSheet."'...";
                $this->log($message);
                Log::channel($this->logChannel)->info($message);
                
                $this->processSheet($table, $generator);
                
                $message = "Table '".$googleSheetId."' processing sheet '".$targetSheet."' finished.";
                $this->log($message);
                Log::channel($this->logChannel)->info($message);
                
                $this->stopIfTimeout();
            }
        }
        
        $message = "Table '".$googleSheetId."' finished.";
        $this->log($message);
        Log::channel($this->logChannel)->info($message);
    }
    
    /**
     *
     * @param TableLaravel     $table
     * @param GeneratorLaravel $generator
     *
     * @throws Exception
     */
    private function processSheet(TableLaravel $table, GeneratorLaravel $generator): void
    {
        try {
            switch ($generator->targetPlatform) {
                case "Юла":
                    $content = $this->xmlGenerationService->generateUlaXML(
                        $table->googleSheetId,
                        $generator->targetPlatform,
                        $generator->maxAds
                    );
                    break;
                case "Яндекс":
                    $content = $this->xmlGenerationService->generateYandexXML(
                        $table->googleSheetId,
                        $generator->targetPlatform,
                        $generator->maxAds
                    );
                    break;
                default:
                    $content = $this->xmlGenerationService->generateAvitoXML(
                        $table->googleSheetId,
                        $generator->targetPlatform,
                        $generator->maxAds
                    );
            }
            
            try {
                $this->saveContent($generator, $content);
            } catch (\Throwable $exception) {
                Log::channel($this->logChannel)->error($exception->getMessage());
            }
        } catch (Exception $exception) {
            $message = "Error on '".$table->googleSheetId."' while processSheet".PHP_EOL.
                $exception->getMessage();
            $this->log($message);
            Log::channel($this->logChannel)->error($message);
            
            throw $exception;
        }
    }
    
    private function saveContent(GeneratorLaravel $generator, string $content): void
    {
        $stringNotCompletelyLoaded = true;
        $offset = 0;
        $order = 0;
        while ($stringNotCompletelyLoaded) {
            $fragment = substr($content, $offset, $this->maxContentFragmentLength);
            DB::table($this->generatorsContentTable)
                ->updateOrInsert(
                    [
                        'generatorId' => $generator->id,
                        'order' => $order
                    ],
                    [
                        'content' => $fragment,
                        'order' => $order
                    ]
                );
            
            $offset += $this->maxContentFragmentLength;
            $order++;
            
            if ($offset > strlen($content)) {
                $stringNotCompletelyLoaded = false;
            }
        }
        
        // Clear old version of saving
        $generator->update([
            'lastGeneration' => null
        ]);
    }
}
