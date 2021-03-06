<?php
    
    namespace App\Console;
    
    use App\Configuration\XmlGeneration;
    use App\Console\Jobs\FillImagesJob;
    use App\Console\Jobs\FillImagesJobYandex;
    use App\Console\Jobs\JobBase;
    use App\Console\Jobs\RandomizeTextJob;
    use App\Models\Table;
    use App\Repositories\TableRepository;
    use App\Services\GoogleDriveClientService;
    use App\Services\SpintaxService;
    use App\Services\SpreadsheetClientService;
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
            $tableRepository = new TableRepository();
            $tables = $tableRepository->getTables();
            
            $schedule->call(function () use ($tables) {
                Log::info("Starting Schedule");
                foreach ($tables as $table) {
                    $this->startFillImagesJob($table);
                    $this->startRandomizeTextJob($table);
                }
                Log::info("Ending Schedule");
            })
                ->name("Tables2") // имя процесса сбрасывается withoutOverlapping через 24 часа
                ->withoutOverlapping();
        }
        
        protected function startFillImagesJob($table): void
        {
            Log::info("Starting FillImagesJob for " . $table->getGoogleSheetId());
            echo "Starting FillImagesJob for " . $table->getGoogleSheetId();
            switch ($table->getTableId()) {
                case 148:
                    // Google Drive
                    try {
                        (new FillImagesJob(
                            new SpreadsheetClientService(),
                            new GoogleDriveClientService()
                        ))
                            ->start($table);
                    } catch (Exception $exception) {
                        $this->logTableError($table, $exception);
                        $this->restartIfQuota(
                            $table,
                            (int)$exception->getCode(),
                            (new FillImagesJob(
                                new SpreadsheetClientService(),
                                new GoogleDriveClientService()
                            ))
                        );
                    }
                    break;
                default:
                    try {
                        (new FillImagesJobYandex(
                            new SpreadsheetClientService(),
                            new YandexDiskService(),
                            new TableRepository(),
                            new XmlGeneration()
                        ))
                            ->start($table);
                    } catch (Exception $exception) {
                        $this->logTableError($table, $exception);
                        $this->restartIfQuota(
                            $table,
                            (int)$exception->getCode(),
                            (new FillImagesJobYandex(
                                new SpreadsheetClientService(),
                                new YandexDiskService(),
                                new TableRepository(),
                                new XmlGeneration()
                            ))
                        );
                    }
            }
        }
        
        protected function startRandomizeTextJob($table): void
        {
            Log::info("Starting RandomizeTextJob for " . $table->getGoogleSheetId());
            echo "Starting RandomizeTextJob for " . $table->getGoogleSheetId();
            try {
                (new RandomizeTextJob(new SpintaxService(), new SpreadsheetClientService(), new XmlGeneration()))
                    ->start($table);
            } catch (Exception $exception) {
                $this->logTableError($table, $exception);
                $this->restartIfQuota(
                    $table,
                    (int)$exception->getCode(),
                    (new RandomizeTextJob(new SpintaxService(), new SpreadsheetClientService(), new XmlGeneration()))
                );
            }
        }
        
        protected function restartIfQuota(Table $table, int $status, JobBase $job): void
        {
            if ($this->isQuota($status)) {
                sleep($this->secondToSleep);
                Log::info("Restarting ".get_class($job)." for " . $table->getGoogleSheetId());
                echo "Restarting ".get_class($job)." for " . $table->getGoogleSheetId();
                try {
                    $job->start($table);
                } catch (Exception $exception) {
                    $this->logTableError($table, $exception);
//                    $this->restartIfQuota($table, $status, $job);
                }
            }
        }
        
        protected function isQuota(int $status): bool
        {
            return $status === 429;
        }
        
        protected function logTableError(Table $table, Exception $exception): void
        {
            Log::error("Error on ".$table->getGoogleSheetId() . PHP_EOL . $exception->getMessage());
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
