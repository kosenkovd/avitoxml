<?php


namespace App\Console\Jobs;


use App\Models\TableHeader;
use App\Services\Interfaces\ISpreadsheetClientService;
use DateTime;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Guid\Guid;

abstract class JobBase
{
    /**
     * @var ISpreadsheetClientService Google Spreadsheet client.
     */
    protected ISpreadsheetClientService $spreadsheetClientService;

    protected string $jobId;

    /**
     * @var bool is logging enabled.
     */
    protected bool $loggingEnabled = false;
    
    /**
     * @var bool is logging enabled.
     */
    protected bool $timeoutEnabled = false;

    /**
     * @var int job execution time start epoch.
     */
    protected int $startTimestamp;

    /**
     * @var int max time to execute job.
     */
    protected int $maxJobTime;

    protected function log(string $message) : void
    {
        echo $message.PHP_EOL;
        if(!$this->loggingEnabled)
        {
            return;
        }

        $timestamp = new DateTime();
        $timestamp->setTimestamp(time());
        $file = __DIR__."/../Logs/".get_class($this).".log";
        file_put_contents($file,
            $timestamp->format(DATE_ISO8601)." ".$this->jobId." ".$message.PHP_EOL,
            FILE_APPEND | LOCK_EX);
    }

    /**
     * Stops job execution in case of timeout.
     */
    protected function stopIfTimeout(): void
    {
        if($this->checkIsTimeout())
        {
            $this->log("Finished ".get_class($this)." job by timeout");
            exit;
        }
    }
    
    protected function checkIsTimeout(): bool
    {
        return $this->timeoutEnabled && (time() >= ($this->startTimestamp + $this->maxJobTime));
    }
    
    /**
     * Extracts TableHeader and rows from table sheet.
     *
     * @param string $tableID
     * @param string $sheetName
     * @return array [ TableHeader, values ]
     * @throws \Exception
     */
    protected function getHeaderAndDataFromTable(string $tableID, string $sheetName) : array
    {
        $values = $this->getFullDataFromTable($tableID, $sheetName);
        
        $propertyColumns = new TableHeader(array_shift($values));
        
        return [ $propertyColumns, $values ];
    }
    
    /**
     * Extracts data from table sheet.
     *
     * @param string $tableID
     * @param string $sheetName
     * @return array [ TableHeader, values ]
     * @throws \Exception
     */
    protected function getFullDataFromTable(string $tableID, string $sheetName) : array
    {
        try {
            $range = $sheetName.'!A1:FZ5001';
            $values = $this->spreadsheetClientService->getSpreadsheetCellsRange(
                $tableID,
                $range
        );
        } catch (\Exception $exception) {
            $message = "Error on '". $tableID."' while getting spreadsheet values".PHP_EOL.$exception->getMessage();
            $this->log($message);
            throw $exception;
        }
    
        return $values;
    }

    public function __construct(ISpreadsheetClientService $spreadsheetClientService)
    {
        $this->jobId = Guid::uuid4()->toString();
        $this->spreadsheetClientService = $spreadsheetClientService;
    }
}
