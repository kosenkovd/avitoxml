<?php


namespace App\Console\Jobs;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Helpers\SpreadsheetHelper;
use App\Models\Table;
use App\Repositories\Interfaces\ITableRepository;
use App\Services\Interfaces\ISpreadsheetClientService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;

class ParserAmountJob extends JobBase
{
    const start = 'Старт';
    const stop = 'Стоп';
    const categoryRowNum = 0;
    const categoryColumnNum = 0;
    const stateRowNum = 1;
    const stateColumnNum = 0;
    const cityNameColumnNum = 1;
    const cityIdColumnNum = 2;
    
    private string $category = '';
    
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
        $message = "'".$tableID."' processing...";
        Log::channel($this->logChannel)->info($message);
        
        $values = $this->getFullDataFromTable($tableID, $sheetName);
        
        if (empty($values)) {
            return;
        }
        
        // Категория
        $this->category = trim($values[self::categoryRowNum][self::categoryColumnNum]) ?? '';
        
        // Старт/стоп
        $state = $values[self::stateRowNum][self::stateColumnNum] ?? '';
        
        if ($state === self::stop || $state !== self::start) {
            Log::channel($this->logChannel)->info("'".$tableID."' Стоп.");
            return;
        }
        
        // Пишем начиная с 1 строчки, пропуская заголовки
        $this->newValues[0][self::stateColumnNum] = self::stop;
        
        // Заголовки
        $propertyColumns = array_shift($values);
        
        $this->getNewValues($tableID, $values, $propertyColumns);
        $this->fillSheet($tableID, $sheetName);
    }
    
    private function getAvitoCityId(string $city): int
    {
        $res = Http::get(
            'https://www.avito.ru/web/1/slocations',
            [
                'limit' => '2',
                'q' => $city,
            ]
        );
        
        $json = $res->json();
        
        sleep($this->secondsToSleepAvito);
        
        if ($res->status() !== 200) {
            $message = $res->status().' '.(isset($json['error']) ? $json['error']['message'] : 'Error');
            throw new \Exception($message);
        }
        
        $locations = $json['result']['locations'];
        if (!isset($locations[0])) {
            Log::channel($this->logChannel)->error('Problems with city name');
            return 0;
        }
        
        if (
            isset($locations[1]) &&
            isset($locations[1]['parent']) &&
            ((int)$locations[1]['parent']['id'] === (int)$locations[0]['id'])
        ) {
            return (int)$locations[1]['id'];
        } else {
            return (int)$locations[0]['id'];
        }
    }
    
    private function getAvitoAmount(string $cityId, string $filling, string $category): int
    {
        $res = Http::get(
            'https://www.avito.ru/js/catalog',
            [
                'locationId' => $cityId,
                'name' => urlencode($filling),
                'categoryId' => $category ? $this->getCategoryId($category) : '',
                'countOnly' => '1',
                'bt' => '1',
            ]
        );
        
        $json = $res->json();
        
        sleep($this->secondsToSleepAvito);
        
        if ($res->status() !== 200) {
            $message = $res->status().' '.(isset($json['error']) ? $json['error']['message'] : 'Error');
            throw new \Exception($message);
        }
        
        return $json['mainCount'];
    }
    
    private function getCategoryId(string $category): string
    {
        switch ($category) {
            case 'Транспорт':
                return 1;
            case 'Автомобили':
                return 9;
            case 'Мотоциклы и мототехника':
                return 14;
            case 'Грузовики и спецтехника':
                return 81;
            case 'Водный транспорт':
                return 11;
            case 'Запчасти и аксессуары':
                return 10;
            case 'Недвижимость':
                return 4;
            case 'Квартиры':
                return 24;
            case 'Комнаты':
                return 23;
            case 'Дома, дачи, коттеджи':
                return 25;
            case 'Земельные участки':
                return 26;
            case 'Гаражи и машиноместа':
                return 85;
            case 'Коммерческая недвижимость':
                return 42;
            case 'Недвижимость за рубежом':
                return 86;
            case 'Работа':
                return 110;
            case 'Вакансии':
                return 111;
            case 'Резюме':
                return 112;
            case 'Услуги':
                return 114;
            case 'Личные вещи':
                return 5;
            case 'Одежда, обувь, аксессуары':
                return 27;
            case 'Детская одежда и обувь':
                return 29;
            case 'Товары для детей и игрушки':
                return 30;
            case 'Часы и украшения':
                return 28;
            case 'Красота и здоровье':
                return 88;
            case 'Для дома и дачи':
                return 2;
            case 'Бытовая техника':
                return 21;
            case 'Мебель и интерьер':
                return 20;
            case 'Посуда и товары для кухни':
                return 87;
            case 'Продукты питания':
                return 82;
            case 'Ремонт и строительство':
                return 19;
            case 'Растения':
                return 106;
            case 'Электроника':
                return 6;
            case 'Аудио и видео':
                return 32;
            case 'Игры, приставки и программы':
                return 97;
            case 'Настольные компьютеры':
                return 31;
            case 'Ноутбуки':
                return 98;
            case 'Оргтехника и расходники':
                return 99;
            case 'Планшеты и электронные книги':
                return 96;
            case 'Телефоны':
                return 84;
            case 'Товары для компьютера':
                return 101;
            case 'Фототехника':
                return 105;
            case 'Хобби и отдых':
                return 7;
            case 'Билеты и путешествия':
                return 33;
            case 'Велосипеды':
                return 34;
            case 'Книги и журналы':
                return 83;
            case 'Коллекционирование':
                return 36;
            case 'Музыкальные инструменты':
                return 38;
            case 'Охота и рыбалка':
                return 102;
            case 'Спорт и отдых':
                return 39;
            case 'Животные':
                return 35;
            case 'Собаки':
                return 89;
            case 'Кошки':
                return 90;
            case 'Птицы':
                return 91;
            case 'Аквариум':
                return 92;
            case 'Другие животные':
                return 93;
            case 'Товары для животных':
                return 94;
            case 'Готовый бизнес и оборудование':
                return 8;
            case 'Готовый бизнес':
                return 116;
            case 'Оборудование для бизнеса':
                return 40;
            default:
                return '';
        }
    }
    
    private function getNewValues(string $tableID, array $values, array $propertyColumns): void
    {
//        return;
        foreach ($values as $numRow => $row) {
            // content starts at line 2
            $spreadsheetRowNum = $numRow + 2;
            
            if ($this->checkIsTimeout()) {
                $message = "'".$tableID."' timeout.";
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
                    $message = "'".$tableID."' filling city id on '".$cell."'";
                    Log::channel($this->logChannel)->info($message);
                    
                    /** @var object|null $cityId */
                    $cityId = DB::table('parser_avito_city_id')
                        ->where('name', $cityName)
                        ->first();
                    if (is_null($cityId)) {
                        try {
                            $this->cityId = $this->getAvitoCityId($cityName);
                        } catch (\Exception $exception) {
                            Log::channel($this->logChannel)->error($exception->getMessage());
                            
                            $this->newValues[$numRow][$column] = '';
                            $this->lastRowUntilJobStops = $spreadsheetRowNum;
                            
                            return;
                        }
                    } else {
                        $this->cityId = $cityId->id;
                    }
                    
                    if ($this->cityId !== 0) {
                        $this->newValues[$numRow][$column] = $this->cityId;
                        
                        try {
                            DB::table('parser_avito_city_id')
                                ->updateOrInsert(
                                    [
                                        'id' => $this->cityId,
                                    ],
                                    [
                                        'name' => $cityName
                                    ]
                                );
                        } catch (\Exception $exception) {
                            Log::channel($this->logChannel)->error("Can't save city id");
                        }
                    } else {
                        $this->newValues[$numRow][$column] = '';
                    }
                    
                    $this->needsToUpdate = true;
                    
                    continue;
                }
                
                if ($this->cityId === 0) {
                    $this->newValues[$numRow][$column] = '';
                    $this->needsToUpdate = true;
                    
                    continue;
                }
                
                $message = "'".$tableID."' filling value on '".$cell."'";
                Log::channel($this->logChannel)->info($message);
                
                try {
                    $amount = $this->getAvitoAmount($this->cityId, $propertyColumn, $this->category);
                } catch (\Exception $exception) {
                    Log::channel($this->logChannel)->error($exception->getMessage());
                    
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
            $message = "'".$tableID."' no values to fill.";
            Log::channel($this->logChannel)->info($message);
            
            return;
        }
        
        if ($this->checkIsTimeout() || !is_null($this->lastRowUntilJobStops)) {
            $range = $sheetName.'!A2:FZ'.$this->lastRowUntilJobStops;
        } else {
            $range = $sheetName.'!A2:FZ'.$this->adsLimit;
        }
        
        $message = "'".$tableID."' writing values to table...";
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
            Log::channel($this->logChannel)->error($exception->getMessage());
        }
        
        $message = "'".$tableID."' successfully wrote values.";
        Log::channel($this->logChannel)->info($message);
    }
    
    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService,
        ITableRepository          $tableRepository,
        XmlGeneration             $xmlGeneration,
        string                    $logChannel = 'parser'
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
