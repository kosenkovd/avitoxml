<?php


namespace App\Console\Jobs;


use App\Helpers\SpreadsheetHelper;
use App\Models\Generator;
use App\Models\Table;
use App\Services\Interfaces\IGoogleDriveClientService;
use App\Services\Interfaces\ISpintaxService;
use App\Services\Interfaces\ISpreadsheetClientService;

class RandomizeTextJob extends JobBase
{
    private ISpintaxService $spintaxService;

    protected int $maxJobTime = 60*60;

    /**
     * Randomises text in specified result column based on pattern column and updates spreadsheet.
     *
     * @param string $tableId spreadsheet id.
     * @param int $patternCol column to take pattern from.
     * @param int $resultCol column to fill in randomized result.
     * @param int $numRow row number, required for spreadsheet update.
     * @param string $sheetName sheet name, required for spreadsheet update.
     * @param array $row row data.
     */
    private function randomizeText(
        string $tableId, int $patternCol, int $resultCol, int $numRow, array $row, string $sheetName) : void
    {
        $alreadyFilled = isset($row[$resultCol]) && $row[$resultCol] != '';
        $noSource = !isset($row[$patternCol]) || $row[$patternCol] == '';

        if($alreadyFilled || $noSource)
        {
            return;
        }

        $this->log("Randomizing row ".$numRow);

        $text = $this->spintaxService->randomize($row[$patternCol]);

        // Счет строк начинается с 1, а не с 0 и первая строка - заголовок
        $numRow += +2;
        $columnName = SpreadsheetHelper::getColumnLetterByNumber($resultCol);
        $range = $sheetName.'!' . $columnName . $numRow . ':' . $columnName . $numRow;
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $this->spreadsheetClientService->updateSpreadsheetCellsRange($tableId, $range, [[$text]], $params);
    }


    /**
     * Randomize text for specified generator.
     *
     * @param string $tableID Google spreadsheet id.
     * @param Generator $generator Generator.
     */
    private function processSheet(string $tableID, Generator $generator): void
    {
        $sheetName = $generator->getTargetPlatform();
        [ $propertyColumns, $values ] = $this->getHeaderAndDataFromTable($tableID, $sheetName);

        var_dump(count($values));
        if (empty($values))
        {
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
                "result" => $propertyColumns->price
            ]
        ];
        foreach ($values as $numRow => $row)
        {
            foreach ($randomizers as $randomizer)
            {
                if(is_null($randomizer["pattern"]) || is_null($randomizer["result"]))
                {
                    continue;
                }

                $this->randomizeText(
                    $tableID,
                    $randomizer["pattern"],
                    $randomizer["result"],
                    $numRow,
                    $row,
                    $sheetName);
            }
        }
    }

    public function __construct(
        ISpintaxService $spintaxService,
        ISpreadsheetClientService $spreadsheetClientService,
        IGoogleDriveClientService $googleDriveClientService
    )
    {
        parent::__construct(
            $spreadsheetClientService,
            $googleDriveClientService
        );
        $this->spintaxService = $spintaxService;
    }

    /**
     * Start job.
     *
     * Randomizes texts in all tables that were not randomized before.
     *
     * @param Table $table table to process.
     */
    public function start(Table $table) : void
    {
        $this->startTimestamp = time();
        $tableID = $table->getGoogleSheetId();
        $this->log("Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId());

        foreach ($table->getGenerators() as $generator)
        {
            $this->log("Processing table ".$table->getTableId().", sheet ".$generator->getTargetPlatform().", spreadsheet id ".$table->getGoogleSheetId());
            $this->processSheet($tableID, $generator);
            $this->stopIfTimeout();
        }

        $this->log("Finished processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId());
    }
}
