<?php


namespace App\Console\Jobs;


use App\Helpers\SpreadsheetHelper;
use App\Models\TableHeader;
use App\Repositories\Interfaces\ITableRepository;
use App\Services\Interfaces\IGoogleServicesClient;
use App\Services\SpintaxService;
use Ramsey\Uuid\Guid\Guid;

class RandomizeTextJob
{
    private SpintaxService $spintaxService;
    private IGoogleServicesClient $googleClient;
    private ITableRepository $tableRepository;

    private string $jobId;

    private function log(string $message) : void
    {
        return;
        $timestamp = new \DateTime();
        $timestamp->setTimestamp(time());
        $file = __DIR__."/../Logs/randomizerLog.log";
        file_put_contents($file,
            $timestamp->format(DATE_ISO8601)." ".$this->jobId." ".$message.PHP_EOL,
            FILE_APPEND | LOCK_EX);
    }

    /**
     * Randomises text in specified result column based on pattern column and updates spreadsheet.
     *
     * @param string $tableId spreadsheet id.
     * @param int $patternCol column to take pattern from.
     * @param int $resultCol column to fill in randomized result.
     * @param int $numRow row number, required for spreadsheet update.
     * @param array $row row data.
     */
    private function randomizeText(string $tableId, int $patternCol, int $resultCol, int $numRow, array $row) : void
    {
        $alreadyFilled = isset($row[$resultCol]) && $row[$resultCol] != '';
        $noSource = !isset($row[$patternCol]) || $row[$patternCol] == '';

        if($alreadyFilled || $noSource)
        {
            return;
        }

        echo "Randomizing row ".$numRow.", pattern is: ".$row[$patternCol].PHP_EOL;
        $this->log("Randomizing row ".$numRow.", pattern is: ".$row[$patternCol]);

        $text = $this->spintaxService->randomize($row[$patternCol]);

        echo "Randomized text is: ".$text.PHP_EOL;
        $this->log("Randomized text is: ".$text);

        // Счет строк начинается с 1, а не с 0 и первая строка - заголовок
        $numRow += +2;
        $columnName = SpreadsheetHelper::getColumnLetterByNumber($resultCol);
        $range = 'Avito!' . $columnName . $numRow . ':' . $columnName . $numRow;
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $this->googleClient->updateSpreadsheetCellsRange($tableId, $range, [[$text]], $params);
    }

    public function __construct(
        SpintaxService $spintaxService,
        IGoogleServicesClient $googleClient,
        ITableRepository $tableRepository
    )
    {
        $this->jobId = Guid::uuid4()->toString();
        $this->spintaxService = $spintaxService;
        $this->googleClient = $googleClient;
        $this->tableRepository = $tableRepository;
    }

    /**
     * Start job.
     *
     * Randomizes texts in all tables that were not randomized before.
     */
    public function start() : void
    {
        $tables = $this->tableRepository->getTables();

        foreach ($tables as $table)
        {
            $tableID = $table->getGoogleSheetId();
            echo "Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId().PHP_EOL;
            $this->log("Processing table ".$table->getTableId().", spreadsheet id ".$table->getGoogleSheetId());

            $headerRange = 'Avito!A1:FZ1';
            $headerResponse = $this->googleClient->getSpreadsheetCellsRange($tableID, $headerRange);
            $propertyColumns = new TableHeader($headerResponse[0]);

            $range = 'Avito!A2:FZ5001';
            $values = $this->googleClient->getSpreadsheetCellsRange($tableID, $range);

            if(empty($values))
            {
                continue;
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
                        $row);
                }
            }

            // Waiting so as not to exceed reads and writes quota.
            sleep(15);
        }
    }
}
