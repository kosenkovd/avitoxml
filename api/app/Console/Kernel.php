<?php
    
    namespace App\Console;
    
    use App\Configuration\Spreadsheet\SheetNames;
    use App\Configuration\XmlGeneration;
    use App\Console\Jobs\FillImagesJob;
    use App\Console\Jobs\FillImagesJobYandex;
    use App\Console\Jobs\GenerateXMLJob;
    use App\Console\Jobs\JobBase;
    use App\Console\Jobs\RandomizeTextJob;
    use App\Models\Table;
    use App\Repositories\GeneratorRepository;
    use App\Repositories\Interfaces\ITableRepository;
    use App\Repositories\TableRepository;
    use App\Services\GoogleDriveClientService;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use App\Services\SpintaxService;
    use App\Services\SpreadsheetClientService;
    use App\Services\XmlGenerationService;
    use App\Services\YandexDiskService;
    use Exception;
    use Illuminate\Console\Scheduling\Schedule;
    use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
    use Illuminate\Support\Facades\Log;
    
    class Kernel extends ConsoleKernel {
        private static function execInBackground($cmd)
        {
            if (substr(php_uname(), 0, 7) == "Windows") {
                pclose(popen("start /B " . $cmd, "r"));
            } else {
                exec($cmd . " > /dev/null &");
            }
        }
        
        private int $secondToSleep = 45;
        private int $attemptsAfterGettingQuota = 2;
        
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
         * @return void
         * @throws Exception
         */
        protected function schedule(Schedule $schedule)
        {
//            Log::info("CronTab activate");
            
            $schedule->call(function () {
                Log::alert("Starting Schedule");
                $tableRepository = new TableRepository();
                $spreadsheetClientService = new SpreadsheetClientService();
                $tables = $tableRepository->getTables();
                
                foreach ($tables as $table) {
                    Log::info("Table '" . $table->getGoogleSheetId() . "' started");
                    if (
                        $this->isModified(
                            $table,
                            $spreadsheetClientService
                        )
                    ) {
                        Log::info("Table '" . $table->getGoogleSheetId() . "' updating...");
                        $this->startRandomizeTextJob($table);
                        $this->startFillImagesJob($table);
                        $this->startXMLGenerationJob($table);
                        $this->updateLastModified($table, $tableRepository);
                    } else {
                        Log::info("Table '" . $table->getGoogleSheetId() . "' is up to date.");
                    }
                    Log::info("Table '" . $table->getGoogleSheetId() . "' finished.");
                }
                Log::alert("Ending Schedule");
            })
                ->name("Tables2") // имя процесса сбрасывается withoutOverlapping через 24 часа
                ->withoutOverlapping();
        }
        
        private function isModified(
            Table $table,
            ISpreadsheetClientService $spreadsheetClientService,
            int $attempts = 0
        ): bool
        {
            if ($attempts >= $this->attemptsAfterGettingQuota) {
                return false;
            } else {
                $attempts++;
            }
            
            try {
                $timeModified = $spreadsheetClientService->getFileModifiedTime($table->getGoogleSheetId());
            } catch (Exception $exception) {
                $this->logTableError($table, $exception);
                $this->isModified(
                    $table,
                    $spreadsheetClientService,
                    $attempts
                );
            }
    
            $isTableExpiredOrDeleted = $table->isDeleted() ||
                (!is_null($table->getDateExpired()) && $table->getDateExpired() < time());
    
            if ($isTableExpiredOrDeleted) {
                return false;
            }
            
            return $table->getDateLastModified() < $timeModified->getTimestamp();
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
    
        private function startFillImagesJob(Table $table): void
        {
            switch ($table->getTableId()) {
                case 148:
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
                            new XmlGeneration()
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
                        new XmlGeneration()
                    ),
                    new SheetNames()
                ))
            );
        }
    
        /**
         * @param Table $table
         * @param JobBase $job
         * @param int|null $status
         * @param int $attempts
         */
        private function handleJob(Table $table, JobBase $job, int $status = null, int $attempts = 0): void
        {
            if ($attempts >= $this->attemptsAfterGettingQuota) {
                return;
            } else {
                $attempts++;
            }
            
            if (!is_null($status) && $this->isQuota($status)) {
                $actionType = 'restarting';
                Log::alert('sleep ' . $this->secondToSleep);
                sleep($this->secondToSleep);
            } else {
                $actionType = 'starting';
            }
            
            $this->logTableHandling($table, $job, $actionType);
            
            try {
                $job->start($table);
            } catch (Exception $exception) {
                $this->logTableError($table, $exception);
                $this->handleJob($table, $job, (int)$exception->getCode(), $attempts);
            }
        }
    
        private function isQuota(int $status): bool
        {
            return $status === 429;
        }
    
        private function logTableHandling($table, $job, string $actionType): void
        {
            $message = "Table '" . $table->getGoogleSheetId() . "' " . $actionType . " '" . get_class($job) . "'...";
            Log::info($message);
            echo $message;
        }
    
        private function logTableError(Table $table, Exception $exception): void
        {
            $message = "Error on '" . $table->getGoogleSheetId() . "'" . PHP_EOL . $exception->getMessage();
            Log::error($message);
            echo $message;
        }
        
        private function updateLastModified(Table $table, ITableRepository $tableRepository): void
        {
            $tableRepository->updateLastModified($table->getTableId());
            Log::info("Table '" . $table->getGoogleSheetId() . "' updated.");
        }
        
        /**
         * Register the commands for the application.
         *
         * @return void
         */
        protected function commands()
        {
            $this->load(__DIR__ . '/Commands');
            
            require base_path('routes/console.php');
        }
    }
