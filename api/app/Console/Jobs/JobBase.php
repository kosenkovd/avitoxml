<?php


namespace App\Console\Jobs;


use App\Models\TableHeader;
use App\Services\Interfaces\ISpreadsheetClientService;
use DateTime;
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
        if(time() >= $this->startTimestamp + $this->maxJobTime)
        {
            $this->log("Finished ".get_class($this)." job by timeout.");
            exit;
        }
    }

    /**
     * Extracts TableHeader and rows from table sheet.
     *
     * @param string $tableID
     * @param string $sheetName
     * @param string $quotaUserPrefix
     * @return array [ TableHeader, values ]
     */
    protected function getHeaderAndDataFromTable(string $tableID, string $sheetName, string $quotaUserPrefix) : array
    {
        $headerRange = $sheetName.'!A1:FZ1';
        $headerResponse = $this->spreadsheetClientService->getSpreadsheetCellsRange(
            $tableID, $headerRange, $quotaUserPrefix."GH");
        $propertyColumns = new TableHeader($headerResponse[0]);

        $range = $sheetName.'!A2:FZ5001';
        $values = $this->spreadsheetClientService->getSpreadsheetCellsRange(
            $tableID, $range, $quotaUserPrefix."GB");

        return [ $propertyColumns, $values ];
    }

    public function __construct(ISpreadsheetClientService $spreadsheetClientService)
    {
        $this->jobId = Guid::uuid4()->toString();
        $this->spreadsheetClientService = $spreadsheetClientService;
    }
}
