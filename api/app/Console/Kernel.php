<?php

namespace App\Console;

use App\Configuration\Config;
use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Console\Jobs\FillImagesJobYandexLaravel;
use App\Console\Jobs\GenerateOzonXMLJob;
use App\Console\Jobs\GenerateXMLJobLaravel;
use App\Console\Jobs\ParserAmountJob;
use App\Console\Jobs\FillAvitoReportJob;
use App\Console\Jobs\FillAvitoStatisticsJob;
use App\Console\Jobs\GetAvitoTokensJob;
use App\Console\Jobs\JobBase;
use App\Console\Jobs\RandomizeTextJobLaravel;
use App\Console\Jobs\UpdateXMLJobLaravel;
use App\Models\TableLaravel;
use App\Models\TableMarketplace;
use App\Services\AvitoService;
use App\Services\CronLockService;
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
//            return;
            /** @var CronLockService $cronLockService */
            $cronLockService = resolve(CronLockService::class);
            $scriptName = "Tables";
            $logChannel = "Tables";
            $scriptId = Guid::uuid4();
            $lockMinutes = 15;
            
            if ($cronLockService->checkAndCreate($scriptName, $lockMinutes, $scriptId)) {
                return;
            }
            
            Log::channel($logChannel)->alert("Starting Schedule ".$scriptId);
            
            $spreadsheetClientService = new SpreadsheetClientService();
            $needsToUpdateTimeStamp = (new Config())->getNeedsToUpdateTimeStamp();
            $noLock = false;
    
            $tables = TableLaravel::query()
                ->whereHas('user', function (Builder $query) {
                    $query->where('isBlocked', false);
                })
//                ->where('dateExpired', '>=', Carbon::now()->startOfDay()->timestamp)
                ->with('user')
                ->with('generators:id,tableId,targetPlatform,maxAds')
                ->get();
            
            /**
             * @var int          $key
             * @var TableLaravel $table
             */
            foreach ($tables as $key => $table) {
                $noLock = !$cronLockService->checkWhileProcessing($scriptName, $lockMinutes, $scriptId);
                if ($noLock) {
                    break;
                }
                
                $this->processTable(
                    $table,
                    $spreadsheetClientService,
                    $needsToUpdateTimeStamp,
                    $key
                );
            }
            
            if (!$noLock) {
                $cronLockService->clear($scriptName);
            }
            Log::channel($logChannel)->alert("Ending Schedule ".$scriptId);
        })
            ->name("Tables2") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
        
        $schedule->call(function () {
//            return;
            /** @var CronLockService $cronLockService */
            $cronLockService = resolve(CronLockService::class);
            $scriptName = "Xml";
            $logChannel = 'xml';
            $scriptId = Guid::uuid4();
            $lockMinutes = 180;
            
            if ($cronLockService->checkAndCreate($scriptName, $lockMinutes, $scriptId)) {
                return;
            }
            
            Log::channel($logChannel)->alert("Starting Schedule");
            
            $spreadsheetClientService = new SpreadsheetClientServiceSecond();
            $noLock = false;
            
            $tables = TableLaravel::query()
                ->whereHas('user', function (Builder $query) {
                    $query->where('isBlocked', false);
                })
//                ->where('dateExpired', '>=', Carbon::now()->startOfDay()->timestamp)
                ->with('user')
                ->with('generators:id,tableId,targetPlatform,maxAds')
                ->get();
            
            /**
             * @var int          $key
             * @var TableLaravel $table
             */
            foreach ($tables as $key => $table) {
                $noLock = !$cronLockService->checkWhileProcessing($scriptName, $lockMinutes, $scriptId);
                if ($noLock) {
                    break;
                }
                
                $this->processUpdateXml(
                    $table,
                    $spreadsheetClientService,
                    $key
                );
            }
            
            if (!$noLock) {
                $cronLockService->clear($scriptName);
            }
            Log::channel($logChannel)->alert("Ending Schedule");
        })
            ->name("UpdateXML1")
            ->withoutOverlapping();
        
        $schedule->call(function () {
            $logChannel = 'parser';
            
            /** @var CronLockService $cronLockService */
            $cronLockService = resolve(CronLockService::class);
            $scriptName = "AmountParser";
            $lockMinutes = 6;
            
            if ($cronLockService->checkAndCreate($scriptName, $lockMinutes)) {
                return;
            }
            
            $scriptId = Guid::uuid4();
            Log::channel($logChannel)->alert("Starting AmountParser ".$scriptId);
            try {
                $googleSheetId = '1VJdo7mkIHk2I8D_fCol21sOSrVi6wuVmRz3NEvvLQe0';
                
                (new ParserAmountJob(
                    new SpreadsheetClientServiceThird(),
                    new XmlGeneration()
                ))->start($googleSheetId);
            } catch (Exception $exception) {
                $this->logTableError($googleSheetId, $exception, $logChannel);
            }
            
            $cronLockService->clear($scriptName);
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
                try {
                    if (
                        !$this->isTableLaravelModified(
                            $table,
                            $spreadsheetClientService,
                            $logChannel
                        )
                    ) {
                        continue;
                    }
                    
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
                try {
                    if (
                        !$this->isTableLaravelModified(
                            $table,
                            $spreadsheetClientService,
                            $logChannel
                        )
                    ) {
                        continue;
                    }
                    
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
                try {
                    if (
                        !$this->isTableLaravelModified(
                            $table,
                            $spreadsheetClientService,
                            $logChannel
                        )
                    ) {
                        continue;
                    }
                    
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
            $scriptId = Guid::uuid4();
            $scriptName = "OZON";
            $logChannel = 'OZON';
            
            $lock = DB::table('cron_lock')->where('name', $scriptName)->first();
            if (!is_null($lock)) {
                if (($lock->created_at + 60 * 15) > time()) {
                    Log::channel($logChannel)->alert("Script '".$scriptName."' already running");
                    
                    return;
                }
                
                DB::table('cron_lock')->where('name', $scriptName)->delete();
            }
            try {
                DB::table('cron_lock')->insert([
                    'name' => $scriptName,
                    'created_at' => time()
                ]);
            } catch (Exception $exception) {
                Log::channel('fatal')->error("Script '".$scriptName."' already running.".PHP_EOL.
                    $exception->getMessage());
            }
            
            Log::channel($logChannel)->alert("Starting Schedule ".$scriptId);
            
            $spreadsheetClientService = new SpreadsheetClientServiceThird();
            $xmlGenerationService = new XmlGenerationService(
                $spreadsheetClientService,
                new SheetNames(),
                new XmlGeneration()
            );
            $tables = TableMarketplace::query()
                ->with('generators:id,tableMarketplaceId,targetPlatform,maxAds')
                ->with('user')
                ->whereHas('user', function (Builder $query) {
                    $query->where('isBlocked', false);
                })
                ->get();
            
            $noLock = false;
            
            /** @var TableMarketplace $table */
            foreach ($tables as $table) {
                $lock = DB::table('cron_lock')->where('name', $scriptName)->first();
                if (is_null($lock)) {
//                    Log::channel('fatal')->error("Script '".$scriptName."' have no lock.");
                    $noLock = true;
                    
                    break;
                }
                if (time() > ($lock->created_at + 60 * 15)) {
                    Log::channel($logChannel)->info("Table '".$table->googleSheetId."' stopped due timeout.");
                    DB::table('cron_lock')->where('name', $scriptName)->delete();
                    
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
            
            if (!$noLock) {
                DB::table('cron_lock')->where('name', $scriptName)->delete();
            }
            Log::channel($logChannel)->alert("Ending Schedule ".$scriptId);
        })
            ->name("OZON") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
    }
    
    private function processTable(
        TableLaravel              $table,
        ISpreadsheetClientService $spreadsheetClientService,
        int                       $needsToUpdateTimeStamp,
        int                       $key
    ): void
    {
        Log::channel('Tables')->info("Table '".$table->googleSheetId."' started ".$key.".");
        try {
            if (!$this->isTableLaravelModified($table, $spreadsheetClientService)) {
                return;
            }
            
            Log::channel('Tables')->info("Table '".$table->googleSheetId."' updating...");
            $this->startRandomizeTextJob($table, $spreadsheetClientService);
            $this->startFillImagesJob($table, $spreadsheetClientService, $needsToUpdateTimeStamp);
            $this->startXMLGenerationJob($table, $spreadsheetClientService);
            $this->updateLastModified($table, $needsToUpdateTimeStamp);
            Log::channel('Tables')->info("Table '".$table->googleSheetId."' finished.");
        } catch (Exception $exception) {
            $this->logTableError($table->googleSheetId, $exception);
        }
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
        TableLaravel              $table,
        ISpreadsheetClientService $spreadsheetClientService,
        ?string                   $logChannel = 'Tables'
    ): bool
    {
        if (is_null($table->dateExpired)) {
            Log::channel($logChannel)->info("Table '".$table->googleSheetId."' has no dateExpired.");
            return false;
        }
    
        if (($table->dateExpired + 86400) < time()) {
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
    
    private function startRandomizeTextJob(TableLaravel $table, ISpreadsheetClientService $spreadsheetClientService): void
    {
        $this->handleJob(
            $table,
            (new RandomizeTextJobLaravel(
                new SpintaxService(),
                $spreadsheetClientService,
                new XmlGeneration()
            ))
        );
    }
    
    private function startFillImagesJob(
        TableLaravel              $table,
        ISpreadsheetClientService $spreadsheetClientService,
        int                       $needsToUpdateTimeStamp
    ): void
    {
        $this->handleJob(
            $table,
            (new FillImagesJobYandexLaravel(
                $spreadsheetClientService,
                new YandexDiskService(),
                new XmlGeneration(),
                $needsToUpdateTimeStamp
            ))
        );
    }
    
    private function startXMLGenerationJob(TableLaravel $table, ISpreadsheetClientService $spreadsheetClientService): void
    {
        $this->handleJob(
            $table,
            (new GenerateXMLJobLaravel(
                $spreadsheetClientService,
                new XmlGeneration(),
                new XmlGenerationService(
                    $spreadsheetClientService,
                    new SheetNames(),
                    new XmlGeneration()
                )
            ))
        );
    }
    
    /**
     * @param TableLaravel $table
     * @param JobBase      $job
     * @param string       $logChannel
     */
    private function handleJob(
        TableLaravel $table,
        JobBase      $job,
        string       $logChannel = 'Tables'
    ): void
    {
        $actionType = 'starting';
        $this->logTableHandling($table->googleSheetId, $job, $actionType, $logChannel);
        
        try {
            $job->start($table);
        } catch (Exception $exception) {
            $this->logTableError($table->googleSheetId, $exception, $logChannel);
        }
    }
    
    private function logTableHandling(string $googleSheetId, $job, string $actionType, string $logChannel = 'Tables'): void
    {
        $message = "Table '".$googleSheetId."' ".$actionType." '".get_class($job)."'...";
        Log::channel($logChannel)->info($message);
    }
    
    private function logTableError(string $googleSheetId, Exception $exception, string $logChannel = 'Tables'): void
    {
        $message = "Error on '".$googleSheetId."' at Kernel".PHP_EOL.$exception->getMessage();
        Log::channel($logChannel)->error($message);
    }
    
    private function updateLastModified(
        TableLaravel $table,
        int          $needsToUpdateTimeStamp
    ): void
    {
        if ($table->dateLastModified === $needsToUpdateTimeStamp) {
            return;
        }
        
        $table->dateLastModified = time();
        $table->save();
        
        Log::channel('Tables')->info("Table '".$table->googleSheetId."' updated.");
    }
    
    private function processUpdateXml(
        TableLaravel              $table,
        ISpreadsheetClientService $spreadsheetClientService,
        int                       $key,
        string                    $logChannel = 'xml'
    ): void
    {
        Log::channel($logChannel)->info("Table '".$table->googleSheetId."' started ".$key.".");
        try {
            Log::channel($logChannel)->info("Table '".$table->googleSheetId."' updating...");
            $this->startXMLUpdateJob($table, $spreadsheetClientService);
            Log::channel($logChannel)->info("Table '".$table->googleSheetId."' finished.");
        } catch (Exception $exception) {
            $this->logTableError($table->googleSheetId, $exception, $logChannel);
        }
        
        $timeToSleep = 1;
        Log::channel($logChannel)->info("sleep ".$timeToSleep);
        sleep($timeToSleep);
    }
    
    private function startXMLUpdateJob(
        TableLaravel              $table,
        ISpreadsheetClientService $spreadsheetClientService
    ): void
    {
        $this->handleJob(
            $table,
            (new UpdateXMLJobLaravel(
                $spreadsheetClientService,
                new XmlGeneration(),
                new XmlGenerationService(
                    $spreadsheetClientService,
                    new SheetNames(),
                    new XmlGeneration()
                )
            )),
            'xml'
        );
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
