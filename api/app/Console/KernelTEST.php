<?php

namespace App\Console;

use App\Configuration\Config;
use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Console\Jobs\GenerateOzonXMLJob;
use App\Console\Jobs\ParserAmountJob;
use App\Console\Jobs\FillAvitoReportJob;
use App\Console\Jobs\FillAvitoStatisticsJob;
use App\Console\Jobs\FillImagesJob;
use App\Console\Jobs\FillImagesJobYandex;
use App\Console\Jobs\GenerateXMLJob;
use App\Console\Jobs\GetAvitoTokensJob;
use App\Console\Jobs\JobBase;
use App\Console\Jobs\RandomizeTextJob;
use App\Console\Jobs\UpdateXMLJob;
use App\Models\Table;
use App\Models\TableLaravel;
use App\Models\TableMarketplace;
use App\Models\UserLaravel;
use App\Repositories\DictRepository;
use App\Repositories\GeneratorRepository;
use App\Repositories\Interfaces\ITableRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Repositories\TableRepository;
use App\Repositories\UserRepository;
use App\Services\AvitoService;
use App\Services\CronLockService;
use App\Services\GoogleDriveClientService;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\SpintaxService;
use App\Services\SpreadsheetClientService;
use App\Services\SpreadsheetClientServiceSecond;
use App\Services\SpreadsheetClientServiceThird;
use App\Services\XmlGenerationService;
use App\Services\YandexDiskService;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Guid\Guid;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];
    
    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     *
     * @return void
     * @throws Exception
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            /** @var CronLockService $cronLockService */
            $cronLockService = resolve(CronLockService::class);
            $scriptName = "Tables";
            $lockMinutes = 15;
            
            if ($cronLockService->checkAndCreate($scriptName, $lockMinutes)) {
                return;
            }
            
            $scriptId = Guid::uuid4();
            Log::channel('Tables')->alert("Starting Schedule ".$scriptId);
            
            $tableRepository = new TableRepository();
            $spreadsheetClientService = new SpreadsheetClientService();
            $tables = $tableRepository->getTables();
            $needsToUpdateTimeStamp = (new Config())->getNeedsToUpdateTimeStamp();
            
            foreach ($tables as $table) {
                if ($cronLockService->checkAndRefreshOrClearIfTimeout($scriptName, $lockMinutes)) {
                    Log::info("Table '".$table->getGoogleSheetId()."' stopped due timeout.");
                    break;
                }
                
                $this->processTable(
                    $table,
                    $tableRepository,
                    $spreadsheetClientService,
                    $needsToUpdateTimeStamp
                );
            }
    
            $cronLockService->clear($scriptName);
            Log::alert("Ending Schedule ".$scriptId);
        })
            ->name("Tables2") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
        
        $schedule->call(function () {
            /** @var CronLockService $cronLockService */
            $cronLockService = resolve(CronLockService::class);
            $scriptName = "Xml";
            $logChannel = 'xml';
            $lockMinutes = 15;
    
            if ($cronLockService->checkAndCreate($scriptName, $lockMinutes)) {
                return;
            }
            
            Log::channel($logChannel)->alert("Starting Schedule");
            $tableRepository = new TableRepository();
            $tables = $tableRepository->getTables();
            
            foreach ($tables as $table) {
                if ($cronLockService->checkAndRefreshOrClearIfTimeout($scriptName, $lockMinutes)) {
                    Log::channel($logChannel)->info("Table '".$table->getGoogleSheetId()."' stopped due timeout.");
                    break;
                }
                
                $this->processUpdateXml($table);
            }
    
            $cronLockService->clear($scriptName);
            Log::channel($logChannel)->alert("Ending Schedule");
        })
            ->name("UpdateXML1")
            ->withoutOverlapping();

        $schedule->call(function () {
            $scriptId = Guid::uuid4();
            $logChannel = 'parser';
            Log::channel($logChannel)->alert("Starting AmountParser ".$scriptId);
            try {
                $googleSheetId = '1VJdo7mkIHk2I8D_fCol21sOSrVi6wuVmRz3NEvvLQe0';

                (new ParserAmountJob(
                    new SpreadsheetClientServiceThird(),
                    new TableRepository(),
                    new XmlGeneration()
                ))->start($googleSheetId);
            } catch (Exception $exception) {
                $this->logTableError($googleSheetId, $exception, $logChannel);
            }
            Log::channel($logChannel)->alert("Finished AmountParser ".$scriptId);
        })
            ->name("AmountParser1") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
        
        $schedule->call(function () {
            $scriptId = Guid::uuid4();
            $logChannel = 'avitoTokens';
            Log::channel($logChannel)->alert("Starting Schedule ".$scriptId);
    
            $spreadsheetClientService = new SpreadsheetClientServiceThird();
            
            $tables = TableLaravel::query()
                ->with('user')
                ->whereHas('user', function (Builder $query) {
                    $query->where('isBlocked', false);
                })
                ->get();
    
            /** @var TableLaravel $table */
            foreach ($tables as $table) {
                if (
                    !$this->isTableLaravelModified(
                        $table,
                        $spreadsheetClientService,
                        $logChannel
                    )
                ) {
                    continue;
                }
        
                try {
                    (new GetAvitoTokensJob(
                        $spreadsheetClientService,
                        new SheetNames()
                    ))->start($table);
                } catch (Exception $exception) {
                    $this->logTableError($table->googleSheetId, $exception, $logChannel);
                }
            }
    
            Log::channel($logChannel)->alert("Ending Schedule ".$scriptId);
        })
            ->name("AvitoTokens1") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
        
        $schedule->call(function () {
            $scriptId = Guid::uuid4();
            $logChannel = 'avitoReport';
            Log::channel($logChannel)->alert("Starting Schedule ".$scriptId);
            
            $spreadsheetClientService = new SpreadsheetClientServiceThird();
            $tables = TableLaravel::query()
                ->whereNotNull(['avitoClientId', 'avitoClientSecret', 'avitoUserId'])
                ->with('user')
                ->whereHas('user', function (Builder $query) {
                    $query->where('isBlocked', false);
                })
                ->get();
            
            /** @var TableLaravel $table */
            foreach ($tables as $table) {
                if (
                    $this->isTableLaravelBlocked($table, $logChannel) ||
                    !$this->isTableLaravelModified(
                        $table,
                        $spreadsheetClientService,
                        $logChannel
                    )
                ) {
                    continue;
                }
                
                try {
                    (new FillAvitoReportJob(
                        $spreadsheetClientService,
                        new AvitoService(),
                        new XmlGeneration()
                    ))->start($table);
                } catch (Exception $exception) {
                    $this->logTableError($table->googleSheetId, $exception, $logChannel);
                }
            }
            
            Log::channel($logChannel)->alert("Ending Schedule ".$scriptId);
        })
            ->name("AvitoLastReport2") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
        
        $schedule->call(function () {
            $scriptId = Guid::uuid4();
            $logChannel = 'avitoStatistics';
            Log::channel($logChannel)->alert("Starting Schedule ".$scriptId);
            
            $spreadsheetClientService = new SpreadsheetClientServiceThird();
            $tables = TableLaravel::query()
                ->whereNotNull(['avitoClientId', 'avitoClientSecret', 'avitoUserId'])
                ->with('user')
                ->whereHas('user', function (Builder $query) {
                    $query->where('isBlocked', false);
                })
                ->get();
            
            /** @var TableLaravel $table */
            foreach ($tables as $table) {
                if (
                    $this->isTableLaravelBlocked($table, $logChannel) ||
                    !$this->isTableLaravelModified(
                        $table,
                        $spreadsheetClientService,
                        $logChannel
                    )
                ) {
                    continue;
                }
                
                try {
                    (new FillAvitoStatisticsJob(
                        $spreadsheetClientService,
                        new AvitoService(),
                        new XmlGeneration(),
                        $logChannel
                    ))->start($table);
                } catch (Exception $exception) {
                    $this->logTableError($table->googleSheetId, $exception, $logChannel);
                }
            }
            
            Log::channel($logChannel)->alert("Ending Schedule ".$scriptId);
        })
            ->name("avitoStatistics3") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
        
        $schedule->call(function () {
            /** @var CronLockService $cronLockService */
            $cronLockService = resolve(CronLockService::class);
            $scriptName = "OZON";
            $logChannel = 'OZON';
            $lockMinutes = 15;
    
            if ($cronLockService->checkAndCreate($scriptName, $lockMinutes)) {
                return;
            }
    
            $scriptId = Guid::uuid4();
            Log::channel($logChannel)->alert("Starting Schedule ".$scriptId);
    
            $spreadsheetClientService = new SpreadsheetClientServiceThird();
            $xmlGenerationService = new XmlGenerationService(
                $spreadsheetClientService,
                new SheetNames(),
                new XmlGeneration(),
                new DictRepository()
            );
            $tables = TableMarketplace::query()
                ->with('generators:id,tableMarketplaceId,targetPlatform,maxAds')
                ->with('user')
                ->whereHas('user', function (Builder $query) {
                    $query->where('isBlocked', false);
                })
                ->get();
    
            /** @var TableMarketplace $table */
            foreach ($tables as $table) {
                if ($cronLockService->checkAndRefreshOrClearIfTimeout($scriptName, $lockMinutes)) {
                    Log::channel($logChannel)->info("Table '".$table->googleSheetId."' stopped due timeout.");
                    break;
                }
    
                try {
                    (new GenerateOzonXMLJob(
                        $spreadsheetClientService,
                        new XmlGeneration(),
                        $xmlGenerationService,
                        new SheetNames()
                    ))->start($table);
                } catch (Exception $exception) {
                    $this->logTableError($table->googleSheetId, $exception, $logChannel);
                }
            }
    
            $cronLockService->clear($scriptName);
            Log::channel($logChannel)->alert("Ending Schedule ".$scriptId);
        })
            ->name("OZON") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
    }
    
    private function processTable(
        Table $table,
        ITableRepository $tableRepository,
        ISpreadsheetClientService $spreadsheetClientService,
        int $needsToUpdateTimeStamp
    ): void
    {
        Log::channel('Tables')->info("Table '".$table->getGoogleSheetId()."' started.");
        try {
            if (
                !$this->isTableBlocked($table) &&
                $this->isModified($table, $spreadsheetClientService)
            ) {
                Log::channel('Tables')->info("Table '".$table->getGoogleSheetId()."' updating...");
                $this->startRandomizeTextJob($table);
                $this->startFillImagesJob($table, $needsToUpdateTimeStamp);
                $this->startXMLGenerationJob($table);
                $this->updateLastModified($table, $tableRepository, $needsToUpdateTimeStamp);
                Log::channel('Tables')->info("Table '".$table->getGoogleSheetId()."' finished.");
            }
        } catch (Exception $exception) {
            $this->logTableError($table->getGoogleSheetId(), $exception);
        }
    }
    
    /**
     * @param Table       $table
     * @param string|null $logChannel
     *
     * @return bool
     * @throws Exception
     */
    private function isTableBlocked(Table $table, ?string $logChannel = 'Tables'): bool
    {
        /** @var UserLaravel|null $user */
        $user = UserLaravel::query()->find($table->getUserId());
        
        if (is_null($user)) {
            $message = "Error on '".$table->getGoogleSheetId()."' table have no user!";
            Log::channel('fatal')->error($message);
            throw new Exception($message);
        }
        
        if ($user->isBlocked) {
            Log::channel($logChannel)->info("Table '".$table->getGoogleSheetId()."' user is blocked, do nothing.");
            return true;
        }
        
        return false;
    }
    
    /**
     * @param TableLaravel $table
     * @param string|null  $logChannel
     *
     * @return bool
     * @throws Exception
     */
    private function isTableLaravelBlocked(TableLaravel $table, ?string $logChannel = 'Tables'): bool
    {
        $user = $table->user;
        
        if (is_null($user)) {
            $message = "Error on '".$table->googleSheetId."' table have no user!";
            Log::channel('fatal')->error($message);
            throw new Exception($message);
        }
        
        if ($user->isBlocked) {
            Log::channel($logChannel)->info("Table '".$table->googleSheetId."' user is blocked, do nothing.");
            return true;
        }
        
        return false;
    }
    
    /**
     * @param Table                     $table
     * @param ISpreadsheetClientService $spreadsheetClientService
     * @param string|null               $logChannel
     *
     * @return bool
     * @throws Exception
     */
    private function isModified(
        Table $table,
        ISpreadsheetClientService $spreadsheetClientService,
        ?string $logChannel = 'Tables'
    ): bool
    {
        $isTableExpired = !is_null($table->getDateExpired()) &&
            (($table->getDateExpired() + 86400) < time()); // TODO change to days like xml
        if ($isTableExpired) {
            Log::channel($logChannel)->info("Table '".$table->getGoogleSheetId()."' expired.");
            return false;
        }
        
        try {
            $timeModified = $spreadsheetClientService->getFileModifiedTime($table->getGoogleSheetId());
        } catch (Exception $exception) {
            throw new Exception("Table '".$table->getGoogleSheetId()."' google error.".PHP_EOL.
                $exception->getMessage());
        }
        
        $isModified = $table->getDateLastModified() < $timeModified->getTimestamp();
        if (!$isModified) {
            Log::channel($logChannel)->info("Table '".$table->getGoogleSheetId()."' is up to date.");
        }
        
        return $isModified;
    }
    
    /**
     * @param TableLaravel              $table
     * @param ISpreadsheetClientService $spreadsheetClientService
     * @param string|null               $logChannel
     *
     * @return bool
     * @throws Exception
     */
    private function isTableLaravelModified(
        TableLaravel $table,
        ISpreadsheetClientService $spreadsheetClientService,
        ?string $logChannel = 'Tables'
    ): bool
    {
        $isTableExpired = !is_null($table->dateExpired) &&
            (($table->dateExpired + 86400) < time()); // TODO change to days like xml
        if ($isTableExpired) {
            Log::channel($logChannel)->info("Table '".$table->googleSheetId."' expired.");
            return false;
        }
        
        try {
            $timeModified = $spreadsheetClientService->getFileModifiedTime($table->googleSheetId);
        } catch (Exception $exception) {
            throw new Exception("Table '".$table->googleSheetId."' google error.".PHP_EOL.
                $exception->getMessage());
        }
        
        $isModified = $table->dateLastModified < $timeModified->getTimestamp();
        if (!$isModified) {
            Log::channel($logChannel)->info("Table '".$table->googleSheetId."' is up to date.");
        }
        
        return $isModified;
    }
    
    private function startRandomizeTextJob(Table $table): void
    {
        $this->handleJob(
            $table,
            (new RandomizeTextJob(
                new SpintaxService(),
                new SpreadsheetClientService(),
                new XmlGeneration()
            ))
        );
    }
    
    private function startFillImagesJob(Table $table, int $needsToUpdateTimeStamp): void
    {
        switch ($table->getTableId()) {
            case 99999:
                // tables using Google Drive Disk
                $this->handleJob(
                    $table,
                    (new FillImagesJob(
                        new SpreadsheetClientService(),
                        new GoogleDriveClientService()
                    ))
                );
                break;
            default:
                $this->handleJob(
                    $table,
                    (new FillImagesJobYandex(
                        new SpreadsheetClientService(),
                        new YandexDiskService(),
                        new TableRepository(),
                        new XmlGeneration(),
                        $needsToUpdateTimeStamp
                    ))
                );
        }
    }
    
    private function startXMLGenerationJob(Table $table): void
    {
        $this->handleJob(
            $table,
            (new GenerateXMLJob(
                new SpreadsheetClientService(),
                new XmlGeneration(),
                new TableRepository(),
                new GeneratorRepository(),
                new XmlGenerationService(
                    new SpreadsheetClientService(),
                    new SheetNames(),
                    new XmlGeneration(),
                    new DictRepository()
                ),
                new SheetNames()
            ))
        );
    }
    
    /**
     * @param Table   $table
     * @param JobBase $job
     */
    private function handleJob(
        Table $table,
        JobBase $job
    ): void
    {
        $actionType = 'starting';
        $this->logTableHandling($table, $job, $actionType);
        
        try {
            $job->start($table);
        } catch (Exception $exception) {
            $this->logTableError($table->getGoogleSheetId(), $exception);
        }
    }
    
    private function logTableHandling($table, $job, string $actionType): void
    {
        $message = "Table '".$table->getGoogleSheetId()."' ".$actionType." '".get_class($job)."'...";
        Log::channel('Tables')->info($message);
        echo $message;
    }
    
    private function logTableError(string $googleSheetId, Exception $exception, ?string $logChannel = 'Tables'): void
    {
        $message = "Error on '".$googleSheetId."' Kernel".PHP_EOL.$exception->getMessage();
        Log::channel($logChannel)->error($message);
        echo $message;
    }
    
    private function updateLastModified(
        Table $table,
        ITableRepository $tableRepository,
        int $needsToUpdateTimeStamp
    ): void
    {
        $existingTable = $tableRepository->get($table->getTableGuid());
        if ($existingTable && ($existingTable->getDateLastModified() === $needsToUpdateTimeStamp)) {
            return;
        }
        
        $table->setDateLastModified(time());
        $tableRepository->update($table);
        Log::channel('Tables')->info("Table '".$table->getGoogleSheetId()."' updated.");
    }
    
    private function processUpdateXml(Table $table): void
    {
        $spreadsheetClientService = new SpreadsheetClientServiceSecond();
        
        $logChannel = 'xml';
        Log::channel($logChannel)->info("Table '".$table->getGoogleSheetId()."' started");
        try {
            if (!$this->isTableBlocked($table, $logChannel)) {
                Log::channel($logChannel)->info("Table '".$table->getGoogleSheetId()."' updating...");
                $this->startXMLUpdateJob($table, $spreadsheetClientService);
                Log::channel($logChannel)->info("Table '".$table->getGoogleSheetId()."' finished.");
            }
        } catch (Exception $exception) {
            $this->logTableError($table->getGoogleSheetId(), $exception, $logChannel);
        }
        
        $timeToSleep = 1;
        Log::channel($logChannel)->info("sleep ".$timeToSleep);
        sleep($timeToSleep);
    }
    
    private function startXMLUpdateJob(Table $table, ISpreadsheetClientService $spreadsheetClientService): void
    {
        $this->handleSecondJob(
            $table,
            (new UpdateXMLJob(
                $spreadsheetClientService,
                new XmlGeneration(),
                new TableRepository(),
                new GeneratorRepository(),
                new XmlGenerationService(
                    $spreadsheetClientService,
                    new SheetNames(),
                    new XmlGeneration(),
                    new DictRepository()
                ),
                new SheetNames()
            ))
        );
    }
    
    /**
     * @param Table   $table
     * @param JobBase $job
     */
    private function handleSecondJob(
        Table $table,
        JobBase $job
    ): void
    {
        $actionType = 'starting';
        $this->logTableHandlingSecond($table, $job, $actionType);
        
        try {
            $job->start($table);
        } catch (Exception $exception) {
            $this->logTableErrorSecond($table, $exception);
        }
    }
    
    private function logTableHandlingSecond($table, $job, string $actionType): void
    {
        $message = "Table '".$table->getGoogleSheetId()."' ".$actionType." '".get_class($job)."'...";
        Log::channel('xml')->info($message);
        echo $message;
    }
    
    private function logTableErrorSecond(Table $table, Exception $exception): void
    {
        $message = "Error on '".$table->getGoogleSheetId()."' Kernel".PHP_EOL.$exception->getMessage();
        Log::channel('xml')->error($message);
        echo $message;
    }
    
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        
        require base_path('routes/console.php');
    }
}
