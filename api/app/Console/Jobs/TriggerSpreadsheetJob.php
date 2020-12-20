<?php


namespace App\Console\Jobs;

use App\Configuration\Spreadsheet;
use App\Services\Interfaces\IGoogleServicesClient;

/**
 * Class TriggerSpreadsheetJob
 * Changes spreadsheet cell value to trigger spreadsheet script execution.
 *
 * @package App\Console\Jobs
 */
class TriggerSpreadsheetJob extends JobBase
{
    private Spreadsheet $spreadsheetConfig;

    private string $sheetName = "Инструкция";
    private string $cellToChange = "A5";

    public function __construct(
        IGoogleServicesClient $googleClient,
        Spreadsheet $spreadsheet
    )
    {
        parent::__construct($googleClient);
        $this->spreadsheetConfig = $spreadsheet;
    }

    /**
     * Start job.
     *
     * Changes spreadsheet cell value to trigger spreadsheet script execution.
     *
     */
    public function start() : void
    {
        $tableID = $this->spreadsheetConfig->getHourlyTriggerSpreadsheetId();

        $range = $this->sheetName.'!'.$this->cellToChange.':'.$this->cellToChange;

        $cellValue = $this->googleClient->getSpreadsheetCellsRange(
            $tableID, $range);

        $newCellValue = @$cellValue[0][0] == 2 ? "1" : "2";

        $this->googleClient->updateCellContent(
            $tableID, $this->sheetName, $this->cellToChange, $newCellValue);
    }

}
