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
    use phpDocumentor\Reflection\Types\Callable_;
    
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
                    switch ($table->getTableId()) {
                        case 148:
                            // Google Drive
                            Log::info("Starting FillImagesJob for " . $table->getTableGuid());
                            echo "Starting FillImagesJob for " . $table->getTableGuid();
                            try {
                                (new FillImagesJob(new SpreadsheetClientService(), new GoogleDriveClientService()))
                                    ->start($table);
                            } catch (Exception $exception) {
                                Log::error($table->getTableGuid() . ' ' . $exception->getCode() . PHP_EOL . $exception->getMessage());
                                
                                $this->restartIfQuota(
                                    $table,
                                    (int)$exception->getCode(),
                                    (new FillImagesJob(new SpreadsheetClientService(), new GoogleDriveClientService()))
                                );
                            }
                            break;
                        default:
                            Log::info("Starting FillImagesJob for " . $table->getTableGuid());
                            echo "Starting FillImagesJob for " . $table->getTableGuid();
                            try {
                                (new FillImagesJobYandex(
                                    new SpreadsheetClientService(), new YandexDiskService(), new TableRepository(), new XmlGeneration()))
                                    ->start($table);
                            } catch (Exception $exception) {
                                Log::error($table->getTableGuid() . ' ' . $exception->getCode() . PHP_EOL . $exception->getMessage());
                                $this->restartIfQuota(
                                    $table,
                                    (int)$exception->getCode(),
                                    (new FillImagesJobYandex(
                                        new SpreadsheetClientService(), new YandexDiskService(), new TableRepository(), new XmlGeneration()))
                                );
                            }
                    }
                    
                    Log::info("Starting RandomizeTextJob for " . $table->getTableGuid());
                    echo "Starting RandomizeTextJob for " . $table->getTableGuid();
                    try {
                        (new RandomizeTextJob(new SpintaxService(), new SpreadsheetClientService(), new XmlGeneration()))
                            ->start($table);
                    } catch (Exception $exception) {
                        Log::error($table->getTableGuid() . ' ' . $exception->getCode() . PHP_EOL . $exception->getMessage());
                        $this->restartIfQuota(
                            $table,
                            (int)$exception->getCode(),
                            (new RandomizeTextJob(new SpintaxService(), new SpreadsheetClientService(), new XmlGeneration()))
                        );
                    }
                }
                
                Log::info("Ending Schedule");
            })
                ->name("Tables2") // имя процесса сбрасывается withoutOverlapping через 24 часа
                ->withoutOverlapping();

//            foreach ($tables as $table) {
//                /*$schedule->exec("cd /var/www/agishev-xml.ru/api && php artisan table:fillImages ".$table->getTableGuid())
//                    ->name("Fill image links command ".$table->getTableId())
//                    ->everyFiveMinutes()
//                    ->runInBackground()
//                    ->withoutOverlapping(60);
//
//                $schedule->exec("cd /var/www/agishev-xml.ru/api && php artisan table:randomizeText ".$table->getTableGuid())
//                    ->name("Randomize text command ".$table->getTableId())
//                    ->everyFiveMinutes()
//                    ->runInBackground()
//                    ->withoutOverlapping(60);
//
//                sleep(1);*/
//
//                switch ($table->getTableId()) {
//                    case 148:
//                        // Google Drive
//                        $schedule->call(function () use ($table) {
//                            echo "Starting FillImagesJob for " . $table->getTableGuid();
//                            try {
//                                (new FillImagesJob(new SpreadsheetClientService(), new GoogleDriveClientService()))
//                                    ->start($table);
//                            } catch (Exception $exception) {
//                                sleep(45);
//                            }
//                        })
//                            ->name("Randomize Google images " . $table->getTableId())
//                            ->everyTenMinutes()
//                            ->withoutOverlapping();
//                        break;
//                    default:
//                        $schedule->call(function () use ($table) {
//                            echo "Starting FillImagesJob for " . $table->getTableGuid();
//                            try {
//                                (new FillImagesJobYandex(
//                                    new SpreadsheetClientService(), new YandexDiskService(), new TableRepository(), new XmlGeneration()))
//                                    ->start($table);
//                            } catch (Exception $exception) {
//                                sleep(45);
//                            }
//                        })
//                            ->name("Randomize yandex images " . $table->getTableId())
//                            ->everyFiveMinutes()
//                            ->withoutOverlapping();
//                }
//
//
//                $schedule->call(function () use ($table) {
//                    echo "Starting RandomizeTextJob for " . $table->getTableGuid();
//                    try {
//                        (new RandomizeTextJob(new SpintaxService(), new SpreadsheetClientService(), new XmlGeneration()))
//                            ->start($table);
//                    } catch (Exception $exception) {
//                        sleep(45);
//                    }
//                })
//                    ->name("Randomize text " . $table->getTableId())
//                    ->everyThreeMinutes()
//                    ->withoutOverlapping();
//            }
        }
        
        protected function restartIfQuota(Table $table, int $status, JobBase $job): void
        {
            if ($this->isQuota($status)) {
                sleep($this->secondToSleep);
                echo "Restarting " . get_class($job) . " for " . $table->getTableGuid();
                try {
                    $job->start($table);
                } catch (Exception $exception) {
                    Log::error($table->getTableGuid() . ' ' . $exception->getCode() . PHP_EOL . $exception->getMessage());

                    $this->restartIfQuota($table, $status, $job);
                }
            }
        }
        
        protected function isQuota(int $status): bool
        {
            return $status === 429;
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
