<?php


namespace App\Console\Jobs;

use App\Configuration\Spreadsheet;
use App\Services\Interfaces\ISpreadsheetClientService;

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
    private string $cellToChange = "B9";

    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService,
        Spreadsheet $spreadsheet
    )
    {
        parent::__construct($spreadsheetClientService);
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

        $cellValue = $this->spreadsheetClientService->getSpreadsheetCellsRange(
            $tableID, $range, "TriggerSpreadsheet");

        $newCellValue = @$cellValue[0][0] == 2 ? "1" : "2";

        $this->spreadsheetClientService->updateCellContent(
            $tableID, $this->sheetName, $this->cellToChange, $newCellValue, "TriggerSpreadsheet");
    }

}
