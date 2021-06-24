<?php


namespace App\Console\Jobs;


use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Helpers\SpreadsheetHelper;
use App\Models\TableHeader;
use App\Models\TableLaravel;
use App\Services\Interfaces\IAvitoService;
use App\Services\Interfaces\ISpreadsheetClientService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FillAvitoStatisticsJob extends JobBase {
    private IAvitoService $avitoService;
    private XmlGeneration $xmlGeneration;
    
    const uniqViews = 'uniqViews';
    const uniqContacts = 'uniqContacts';
    const uniqFavorites = 'uniqFavorites';
    private array $fields = [
        self::uniqViews,
        self::uniqContacts,
        self::uniqFavorites
    ];
    const day = 'day';
    const week = 'week';
    const month = 'month';
    const allTime = 'allTime';
    const manual = 'manual';
    
    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService,
        IAvitoService $avitoService,
        XmlGeneration $xmlGeneration,
        string $logChannel = 'avitoStatistics'
    )
    {
        parent::__construct($spreadsheetClientService);
        
        $this->avitoService = $avitoService;
        $this->xmlGeneration = $xmlGeneration;
        $this->logChannel = $logChannel;
    }
    
    /**
     * @param TableLaravel $table
     *
     * @throws Exception
     */
    public function start(TableLaravel $table): void
    {
        if (
            !$table->avitoClientId ||
            !$table->avitoClientSecret ||
            !$table->avitoUserId
        ) {
            Log::channel($this->logChannel)->error("Error on '".$table->googleSheetId."' table have no tokens");
            return;
        }
    
        $existingSheets = $this->spreadsheetClientService->getSheets(
            $table->googleSheetId
        );
        $targetSheets = $this->xmlGeneration->getAvitoTabs();
    
        $splitTargetSheets = explode(",", $targetSheets);
        foreach ($splitTargetSheets as $targetSheet) {
        
            $targetSheet = trim($targetSheet);
            if (!in_array($targetSheet, $existingSheets)) {
                continue;
            }
        
            $message = "Table '".$table->googleSheetId."' processing sheet '".$targetSheet."'...";
            Log::channel($this->logChannel)->info($message);
        
            $this->processSheet(
                $table,
                $targetSheet
            );
        }
    }
    
    /**
     * @param TableLaravel $table
     * @param string       $sheetName
     *
     * @throws Exception
     */
    private function processSheet(TableLaravel $table, string $sheetName): void
    {
        /** @var TableHeader $propertyColumns */
        [$propertyColumns, $values] = $this->getHeaderAndDataFromTable(
            $table->googleSheetId,
            $sheetName
        );
        
        if ($propertyColumns && empty($values)) {
            return;
        }
        
        if (!$this->areNecessaryColumnsExists($propertyColumns)) {
            Log::channel($this->logChannel)->error("Error on '".$table->googleSheetId."' table have no columns");
            return;
        }
        
        $auth = $this->avitoService->authorize(
            $table->avitoClientId,
            $table->avitoClientSecret
        );
        $itemIds = $this->getItemIds($values, $propertyColumns);
        
        $collectedStatistics = new Collection;
        while (!($fragment = $itemIds->splice(0, 200))->isEmpty()) {
            $items = $this->avitoService->getStatistics(
                $table->avitoUserId,
                $auth,
                array_values($fragment->toArray()),
                $this->fields
            );
            
            if ($items->isEmpty()) {
                continue;
            }
            
            $items->each(function (array $item) use ($collectedStatistics): void {
                $statistics = $this->getStatistics($item['stats']);
                $collectedStatistics->put($item['itemId'], $statistics);
            });
        }
        
        $valuesToWrite = new Collection;
        foreach ($values as $row) {
            if (!$this->areNecessaryColumnsValuesExists($row, $propertyColumns)) {
                $valuesToWrite->add([
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]);
                continue;
            }
            
            $stat = $collectedStatistics[$row[$propertyColumns->unloadingAvitoId]];
            $stat = $this->formatStatistics($stat, $propertyColumns);
            
            $valuesToWrite->add($stat);
        }
        
        $this->fillSheet(
            $valuesToWrite->toArray(),
            $table->googleSheetId,
            $sheetName,
            $propertyColumns
        );
    }
    
    private function areNecessaryColumnsExists(TableHeader $propertyColumns): bool
    {
        if (
            is_null($propertyColumns->ID) ||
            is_null($propertyColumns->unloadingAvitoId) ||
            is_null($propertyColumns->statisticViews) ||
            is_null($propertyColumns->statisticMessage) ||
            is_null($propertyColumns->statisticInfo) ||
            is_null($propertyColumns->statisticFav) ||
            is_null($propertyColumns->statisticViewsDay) ||
            is_null($propertyColumns->statisticMessageDay) ||
            is_null($propertyColumns->statisticInfoDay) ||
            is_null($propertyColumns->statisticFavDay) ||
            is_null($propertyColumns->statisticViewsWeek) ||
            is_null($propertyColumns->statisticMessageWeek) ||
            is_null($propertyColumns->statisticInfoWeek) ||
            is_null($propertyColumns->statisticFavWeek) ||
            is_null($propertyColumns->statisticViewsMonth) ||
            is_null($propertyColumns->statisticMessageMonth) ||
            is_null($propertyColumns->statisticInfoMonth) ||
            is_null($propertyColumns->statisticFavMonth)
        ) {
            return false;
        }
        
        return true;
    }
    
    private function areNecessaryColumnsValuesExists(array $row, TableHeader $propertyColumns): bool
    {
        return (isset($row[$propertyColumns->ID]) && $row[$propertyColumns->ID]) &&
            (isset($row[$propertyColumns->unloadingAvitoId]) && $row[$propertyColumns->unloadingAvitoId]);
    }
    
    /**
     * @param array       $values
     * @param TableHeader $propertyColumns
     *
     * @return Collection
     */
    private function getItemIds(array $values, TableHeader $propertyColumns): Collection
    {
        $itemIds = new Collection;
        foreach ($values as $row) {
            if (
                (!isset($row[$propertyColumns->ID]) || !$row[$propertyColumns->ID]) ||
                (!isset($row[$propertyColumns->unloadingAvitoId]) || !$row[$propertyColumns->unloadingAvitoId])
            ) {
                continue;
            }
            
            $itemIds->put($row[$propertyColumns->ID], (int)$row[$propertyColumns->unloadingAvitoId]);
        }
        
        return $itemIds;
    }
    
    /**
     * @param array $statistics
     *
     * @return array
     */
    private function getStatistics(array $statistics): array
    {
        $result = [];
        foreach ($this->fields as $field) {
            $result[self::day][$field] = 0;
            $result[self::week][$field] = 0;
            $result[self::month][$field] = 0;
            $result[self::allTime][$field] = 0;
        }
        
        foreach ($this->fields as $field) {
            foreach ($statistics as $statistic) {
                $date = Carbon::createFromFormat('Y-m-d', $statistic['date']);
                
                if ($date->diffInDays() === 0) {
                    $result[self::day][$field] += $statistic[$field];
                }
                if ($date->diffInWeeks() === 0) {
                    $result[self::week][$field] += $statistic[$field];
                }
                if ($date->diffInMonths() === 0) {
                    $result[self::month][$field] += $statistic[$field];
                }
                
                $result[self::allTime][$field] += $statistic[$field];
            }
        }
        
        return $result;
    }
    
    /**
     * @param array       $stat
     * @param TableHeader $propertyColumns
     *
     * @return array
     */
    private function formatStatistics(array $stat, TableHeader $propertyColumns): array
    {
        $formatted = new Collection;
        foreach ($stat as $key => $value) {
            if (isset($value[self::uniqViews]) && isset($value[self::uniqContacts])) {
                $uniqViews = $value[self::uniqViews];
                $uniqContacts = $value[self::uniqContacts];
                if (($value[self::uniqViews] === 0) || ($value[self::uniqContacts] === 0)) {
                    $stat[$key][self::manual] = 0;
                    
                    continue;
                }
                
                $stat[$key][self::manual] = 100 / $uniqViews * $uniqContacts;
            }
        }
        
        $formatted->put($propertyColumns->statisticViews, $stat[self::allTime][self::uniqViews]);
        $formatted->put($propertyColumns->statisticMessage, $stat[self::allTime][self::uniqContacts]);
        $formatted->put($propertyColumns->statisticInfo, $stat[self::allTime][self::manual]);
        $formatted->put($propertyColumns->statisticFav, $stat[self::allTime][self::uniqFavorites]);
        
        $formatted->put($propertyColumns->statisticViewsMonth, $stat[self::month][self::uniqViews]);
        $formatted->put($propertyColumns->statisticMessageMonth, $stat[self::month][self::uniqContacts]);
        $formatted->put($propertyColumns->statisticInfoMonth, $stat[self::month][self::manual]);
        $formatted->put($propertyColumns->statisticFavMonth, $stat[self::month][self::uniqFavorites]);
        
        $formatted->put($propertyColumns->statisticViewsWeek, $stat[self::week][self::uniqViews]);
        $formatted->put($propertyColumns->statisticMessageWeek, $stat[self::week][self::uniqContacts]);
        $formatted->put($propertyColumns->statisticInfoWeek, $stat[self::week][self::manual]);
        $formatted->put($propertyColumns->statisticFavWeek, $stat[self::week][self::uniqFavorites]);
        
        $formatted->put($propertyColumns->statisticViewsDay, $stat[self::day][self::uniqViews]);
        $formatted->put($propertyColumns->statisticMessageDay, $stat[self::day][self::uniqContacts]);
        $formatted->put($propertyColumns->statisticInfoDay, $stat[self::day][self::manual]);
        $formatted->put($propertyColumns->statisticFavDay, $stat[self::day][self::uniqFavorites]);
        
        return array_values($formatted->toArray());
    }
    
    /**
     * @throws Exception
     */
    private function fillSheet(
        array $values,
        string $googleSheetId,
        string $sheetName,
        TableHeader $propertyColumns
    ): void
    {
        $columnLetterStart = SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->statisticViews);
        $columnLetterEnd = SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->statisticFavDay);
        $range = $sheetName.'!'.$columnLetterStart.'2:'.$columnLetterEnd.'5001';
        
        $message = "Table '".$googleSheetId."' writing values to table...";
        Log::channel($this->logChannel)->info($message);

        try {
            $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                $googleSheetId,
                $range,
                $values,
                [
                    'valueInputOption' => 'RAW'
                ]
            );
        } catch (\Exception $exception) {
            $message = "Error on '".$googleSheetId."' while writing to table".PHP_EOL.
                $exception->getMessage();
            Log::channel($this->logChannel)->error($message);
            
            throw $exception;
        }
    }
}
