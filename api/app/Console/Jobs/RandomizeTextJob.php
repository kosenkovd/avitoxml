<?php


namespace App\Console\Jobs;


use App\Configuration\XmlGeneration;
use App\Helpers\SpreadsheetHelper;
use App\Models\Table;
use App\Services\Interfaces\ISpintaxService;
use App\Services\Interfaces\ISpreadsheetClientService;

class RandomizeTextJob extends JobBase
{
    private ISpintaxService $spintaxService;

    /**
     * @var bool is logging enabled.
     */
    protected bool $loggingEnabled = false;
    
    protected bool $timeoutEnabled = false;

    protected int $maxJobTime = 60*60;

    private XmlGeneration $xmlGeneration;

    /**
     * Randomises text in specified result column based on pattern column and updates spreadsheet.
     *
     * @param string $tableId spreadsheet id.
     * @param int $patternCol column to take pattern from.
     * @param int $resultCol column to fill in randomized result.
     * @param int $numRow row number, required for spreadsheet update.
     * @param string $sheetName sheet name, required for spreadsheet update.
     * @param array $row row data.
     * @param string $quotaUser quota user.
     */
    private function randomizeText(
        string $tableId,
        int $patternCol,
        int $resultCol,
        int $numRow,
        array $row,
        string $sheetName,
        string $quotaUser) : void
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
        $this->spreadsheetClientService->updateCellContent(
            $tableId, $sheetName, $columnName.$numRow, $text, $quotaUser);
    }


    /**
     * Randomize text for specified generator.
     *
     * @param string $tableID Google spreadsheet id.
     * @param string $sheetName sheet name.
     * @param string $quotaUserPrefix quota user prefix.
     */
    private function processSheet(string $tableID, string $sheetName, string $quotaUserPrefix): void
    {
        [ $propertyColumns, $values ] = $this->getHeaderAndDataFromTable($tableID, $sheetName, $quotaUserPrefix);

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
                "result" => is_null($propertyColumns->price) ? $propertyColumns->salary : $propertyColumns->price
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
                    $sheetName,
                    $quotaUserPrefix."randText".$randomizer["result"]);

                sleep(1);
            }
        }
    }

    public function __construct(
        ISpintaxService $spintaxService,
        ISpreadsheetClientService $spreadsheetClientService,
        XmlGeneration $xmlGeneration)
    {
        parent::__construct($spreadsheetClientService);
        $this->spintaxService = $spintaxService;
        $this->xmlGeneration = $xmlGeneration;
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

        $existingSheets = $this->spreadsheetClientService->getSheets(
            $table->getGoogleSheetId(), $table->getTableGuid()."rtj");

        foreach ($table->getGenerators() as $generator)
        {
            switch($generator->getTargetPlatform())
            {
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
            foreach ($splitTargetSheets as $targetSheet)
            {
                $targetSheet = trim($targetSheet);
                if(!in_array($targetSheet, $existingSheets))
                {
                    continue;
                }

                $quotaUserPrefix = substr($table->getTableGuid(), 0, 10).
                    (strlen($targetSheet) > 10 ? substr($targetSheet, 0, 10) : $targetSheet).
                    "RTJ";

                $this->log("Processing table ".$table->getTableId().", sheet ".$targetSheet.", spreadsheet id ".$table->getGoogleSheetId());
                $this->processSheet($tableID, $targetSheet, $quotaUserPrefix);
                $this->stopIfTimeout();
            }
        }

        $this->log("Finished processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId());
    }
}
