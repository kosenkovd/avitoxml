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

class FillAvitoReportJob extends JobBase {
    private IAvitoService $avitoService;
    private XmlGeneration $xmlGeneration;
    
    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService,
        IAvitoService $avitoService,
        XmlGeneration $xmlGeneration,
        string $logChannel = 'avitoReport'
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
        $report = $this->avitoService->getLastReport($table->avitoUserId, $auth);
        
        $ads = new Collection($report['ads']);
        
        $reportValues = new Collection;
        
        foreach ($values as $numRow => $row) {
            if (!isset($row[$propertyColumns->ID]) || (trim($row[$propertyColumns->ID]) === '')) {
                $reportValues->put(
                    $numRow,
                    [
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ]
                );
                continue;
            }
            
            $ad = $ads->firstWhere('ad_id', $row[$propertyColumns->ID]);
            if (is_null($ad)) {
                $reportValues->put($numRow, []);
                continue;
            }
            
            $messages = $this->formatMessages($ad);
            $dateStart = $this->formatDateStart($ad);
            $dateEnd = $this->formatDateEnd($ad);
            $dateInfo = $this->formatDateInfo($report);
            
            $avitoId = $ad['avito_id'] ?? '';
            $url = $this->formatUrl($ad, $avitoId);
            
            $reportValues->put(
                $numRow,
                [
                    ($ad['statuses']['general']['help'] ?? '').PHP_EOL.($ad['statuses']['processing']['help'] ?? ''),
                    $ad['statuses']['avito']['help'] ?? '',
                    $messages,
                    $dateStart,
                    $dateEnd,
                    $avitoId,
                    $url,
                    $dateInfo
                ]
            );
        }
        
        $this->fillSheet(
            $reportValues->toArray(),
            $table->googleSheetId,
            $sheetName,
            $propertyColumns
        );
    }
    
    private function areNecessaryColumnsExists(TableHeader $propertyColumns): bool
    {
        if (
            is_null($propertyColumns->ID) ||
            is_null($propertyColumns->unloadingStatus) ||
            is_null($propertyColumns->unloadingAvitoStatus) ||
            is_null($propertyColumns->unloadingMessages) ||
            is_null($propertyColumns->unloadingDateStart) ||
            is_null($propertyColumns->unloadingDateEnd) ||
            is_null($propertyColumns->unloadingAvitoId) ||
            is_null($propertyColumns->unloadingUrl) ||
            is_null($propertyColumns->unloadingDateInfo)
        ) {
            return false;
        }
        
        return true;
    }
    
    private function formatMessages(array $ad): string
    {
        if (!isset($ad['messages'])) {
            return '';
        }
        
        $messages = new Collection($ad['messages']);
        $messagesStrings = $messages->map(function (array $message): string {
            switch ($message['code']) {
                case 3000:
                    return 'Опубликовано';
                case 3001:
                    return 'Отредактировано';
                case 3002:
                    return 'Активировано';
                case 3003:
                    return 'Остановлено';
                case 3004:
                    return 'Применена услуга продвижения';
                case 3005:
                    return 'Списание средств';
                case 3006:
                    return 'Пропущено из за порционной выгрузки. увеличьте лимиты в настройках Автозагрузке на сайте Avito: https://www.avito.ru/autoload/settings';
                case 3007:
                    return 'Истек срок показа';
                case 3008:
                    return 'Не наступил срок показа';
                case 3010:
                    return 'Новое объявление';
                case 3011:
                    return 'Повторная подача';
                case 'Images':
                    return 'Ошибка фото';
                case 'Condition':
                    return 'Состояние товара';
            }
            
            $description = $message['description'];
            $description = preg_replace('/(<p.*?>.*?>)/', "$1".PHP_EOL, $description);
            $description = preg_replace('/(<a.*?>.*?>)/', " $1 ", $description);
            $description = strip_tags($description);
            
            $description = preg_replace('/Images/', "Ошибка фото", $description);
            
            return ($message['element_name'] ? ($message['element_name'].PHP_EOL) : '').
                ($message['description'] ? ($description.PHP_EOL.PHP_EOL) : '').
                ($message['code'] ?: '');
        });
        
        return $messagesStrings->implode(PHP_EOL.PHP_EOL);
    }
    
    private function formatDateStart(array $ad): string
    {
        if (!isset($ad['avito_date_end']) || is_null($ad['avito_date_end'])) {
            return '';
        }
        
        return Carbon::createFromTimeString($ad['avito_date_end'])
            ->subMonth()
            ->format("d-m-Y H:i:s");
    }
    
    private function formatDateEnd(array $ad): string
    {
        if (!isset($ad['avito_date_end']) || is_null($ad['avito_date_end'])) {
            return '';
        }
        
        return $this->formatDate($ad['avito_date_end']);
    }
    
    private function formatDate(string $date): string
    {
        return Carbon::createFromTimeString($date)
            ->format("d-m-Y H:i:s");
    }
    
    private function formatDateInfo(array $report): string
    {
        $generated_at = $this->formatDateInfoString($report, 'generated_at', 'Запись');
        $started_at = $this->formatDateInfoString($report, 'started_at', 'Начало');
        $finished_at = $this->formatDateInfoString($report, 'finished_at', 'Окончание');
        
        return $generated_at.$started_at.$finished_at;
    }
    
    private function formatDateInfoString(array $report, string $key, string $text): string
    {
        if (!isset($report[$key]) && !$report[$key]) {
            return '';
        }
    
        return $text.' '.$this->formatDate($report[$key]).PHP_EOL;
    }
    
    private function formatUrl(array $ad, string $avitoId): string
    {
        if (isset($ad['url']) && ($ad['url'] != '')) {
            return $ad['url'];
        }
        
        return $avitoId ? 'https://www.avito.ru/items/'.$avitoId : '';
    }
    
    /**
     * @throws Exception
     */
    private function fillSheet(
        array $reportValues,
        string $googleSheetId,
        string $sheetName,
        TableHeader $propertyColumns
    ): void
    {
        $columnLetterStart = SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->unloadingStatus);
        $columnLetterEnd = SpreadsheetHelper::getColumnLetterByNumber($propertyColumns->unloadingDateInfo);
        $range = $sheetName.'!'.$columnLetterStart.'2:'.$columnLetterEnd.'5001';
        
        $message = "Table '".$googleSheetId."' writing values to table...";
        Log::channel($this->logChannel)->info($message);

        try {
            $this->spreadsheetClientService->updateSpreadsheetCellsRange(
                $googleSheetId,
                $range,
                $reportValues,
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
    
    private function getMock(): array {
        return [
            'id' => 76934553,
            'ads' => [
                [
                    "statuses" => [
                        "processing" => [
                            "value" => "processed",
                            "help" => "Объявления, которые в данный цикл автозагрузки без изменений оставлены активными на сайте."
                        ],
                        "avito" => [
                            "value" => "active",
                            "help" => "Активное"
                        ],
                        "general" => [
                            "value" => "success",
                            "help" => "Успешно опубликовано"
                        ]
                    ],
                    "ad_id" => "М-0001",
                    "avito_id" => "2078740792",
                    "avito_date_end" => "2021-05-26T14:07:49+03:00",
                    "url" => "https://www.avito.ru/sankt-peterburg/tovary_dlya_detey_i_igrushki/rastuschiy_stul_2078740792",
                    "vas_active" => []
                ],
                [
                    "statuses" => [
                        "processing" => [
                            "value" => null,
                            "help" => "Количество объявлений в XML-файле с повторяющимися ID или одинаковым описанием и значениями параметров."
                        ],
                        "avito" => [
                            "value" => null,
                            "help" => null
                        ],
                        "general" => [
                            "value" => "in_feed",
                            "help" => "Объявлений в файле"
                        ]
                    ],
                    "ad_id" => "М-0002",
                    "avito_id" => null,
                    "avito_date_end" => null,
                    "vas_active" => [],
                    "messages" => [
                        [
                            "type" => "error",
                            "code" => null,
                            "help_url" => null,
                            "element_name" => null,
                            "description" => "Проверьте корректность значения в элементе <a href=\"https://autoload.avito.ru/format/lichnye_veschi/#Id\">Id</a> или удалите дубли объявлений из вашего XML-файла."
                        ]
                    ]
                ],
            ],
            "customer_name" => "",
            "finished_at" => null,
            "generated_at" => "2021-05-08T22:16:51.728918+03:00",
            "status" => "processing",
            "started_at" => "2021-05-08T22:02:30.425000+03:00",
            "version" => "0.3"
        ];
    }
}
