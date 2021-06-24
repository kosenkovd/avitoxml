<?php


namespace App\Console\Jobs;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Helpers\SpreadsheetHelper;
use App\Models\Table;
use App\Repositories\Interfaces\ITableRepository;
use App\Services\Interfaces\ISpreadsheetClientService;
use Illuminate\Support\Facades\Log;

class ParserAmountJob extends JobBase
{
    const start = 'Старт';
    const stop = 'Стоп';
    const stateColumnNum = 0;
    const cityNameColumnNum = 1;
    const cityIdColumnNum = 2;
    
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
    
    /**
     * @var SheetNames
     */
    protected SheetNames $sheetNamesConfig;
    
    private ITableRepository $tableRepository;
    private XmlGeneration $xmlGeneration;
    
    /**
     * Fills cityIds and amount for query from table column name for specified sheet.
     *
     * @param string $tableID   Google spreadsheet id.
     * @param string $sheetName target sheet.
     *
     * @throws \Exception
     */
    private function processSheet(string $tableID, string $sheetName): void
    {
        $message = $tableID." processing...";
        Log::channel($this->logChannel)->info($message);
        
        $values = $this->getFullDataFromTable($tableID, $sheetName);
        
        if (empty($values)) {
            return;
        }
        
        // Старт/стоп
        $state = $values[1][0] ?? '';
    
        if ($state === self::stop || $state !== self::start) {
            return;
        }
        $this->newValues[0][self::stateColumnNum] = self::stop;
        
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
//        return;
        foreach ($values as $numRow => $row) {
            // content starts at line 2
            $spreadsheetRowNum = $numRow + 2;
            
            if ($this->checkIsTimeout()) {
                $message = "timeout";
                Log::channel($this->logChannel)->info($message);
    
                $this->newValues[0][self::stateColumnNum] = self::start;
                
                // row before that row
                $this->lastRowUntilJobStops = $spreadsheetRowNum - 1;
                
                return;
            }
            
            $cityName = $row[self::cityNameColumnNum];
            
            foreach ($propertyColumns as $column => $propertyColumn) {
                if ($column === self::stateColumnNum) {
                    $this->newValues[$numRow][$column] = $row[$column] ?? '';
                    
                    continue;
                }
                
                if ($column === self::cityNameColumnNum) {
                    $this->newValues[$numRow][$column] = $cityName;
                }
                
                $alreadyFilled = isset($row[$column]) &&
                    trim($row[$column]) != '';
                
                // check alreadyFilled
                if ($alreadyFilled) {
                    if ($column === self::cityIdColumnNum) {
                        $this->cityId = $row[$column];
                    }
                    
                    $this->newValues[$numRow][$column] = $row[$column];
                    
                    continue;
                }
                
                $cell = SpreadsheetHelper::getColumnLetterByNumber($column).$spreadsheetRowNum;
                
                if ($column === self::cityIdColumnNum) {
                    $message = $tableID." filling city id on ".$cell;
                    Log::channel($this->logChannel)->info($message);
                    
                    try {
                        $this->cityId = $this->getAvitoCityId($cityName);
                    } catch (\Exception $exception) {
                        Log::channel($this->logChannel)->info($exception->getMessage());
                        
                        $this->newValues[$numRow][$column] = '';
                        $this->lastRowUntilJobStops = $spreadsheetRowNum;
                        
                        return;
                    }
                    
                    $this->newValues[$numRow][$column] = $this->cityId;
                    $this->needsToUpdate = true;
                    
                    continue;
                }
                
                $message = $tableID." filling ".$cell;
                Log::channel($this->logChannel)->info($message);
                
                try {
                    $amount = $this->getAvitoAmount($this->cityId, $propertyColumn);
                } catch (\Exception $exception) {
                    Log::channel($this->logChannel)->info($exception->getMessage());
                    
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
            Log::channel($this->logChannel)->info($message);
            
            return;
        }
    
        if ($this->checkIsTimeout() || !is_null($this->lastRowUntilJobStops)) {
            $range = $sheetName.'!A2:FZ'.$this->lastRowUntilJobStops;
        } else {
            $range = $sheetName.'!A2:FZ5001';
        }
        
        $message = $tableID." writing values to table...";
        Log::channel($this->logChannel)->info($message);
        
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
            Log::channel($this->logChannel)->info($exception->getMessage());
        }
        
        $message = $tableID." successfully wrote values.";
        Log::channel($this->logChannel)->info($message);
    }
    
    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService,
        ITableRepository $tableRepository,
        XmlGeneration $xmlGeneration,
        $logChannel = 'parser'
    )
    {
        parent::__construct($spreadsheetClientService);
        $this->tableRepository = $tableRepository;
        $this->sheetNamesConfig = new SheetNames();
        $this->xmlGeneration = $xmlGeneration;
        $this->logChannel = $logChannel;
    }
    
    /**
     * Start job.
     * Fills images for all tables that were not filled before.
     *
     * @param string $googleSheetId
     *
     * @throws \Exception
     */
    public function start(string $googleSheetId): void
    {
        $this->startTimestamp = time();
        $targetSheet = 'Парсер Авито';
        $this->processSheet(
            $googleSheetId,
            $targetSheet
        );
    }
}
