<?php
    
    namespace App\Console;
    
    use App\Configuration\Config;
    use App\Configuration\Spreadsheet\SheetNames;
    use App\Configuration\XmlGeneration;
    use App\Console\Jobs\FillAmountJob;
    use App\Console\Jobs\FillImagesJob;
    use App\Console\Jobs\FillImagesJobYandex;
    use App\Console\Jobs\GenerateXMLJob;
    use App\Console\Jobs\JobBase;
    use App\Console\Jobs\RandomizeTextJob;
    use App\Console\Jobs\UpdateXMLJob;
    use App\Models\Table;
    use App\Repositories\DictRepository;
    use App\Repositories\GeneratorRepository;
    use App\Repositories\Interfaces\ITableRepository;
	use App\Repositories\Interfaces\IUserRepository;
	use App\Repositories\TableRepository;
	use App\Repositories\UserRepository;
	use App\Services\GoogleDriveClientService;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use App\Services\SpintaxService;
    use App\Services\SpreadsheetClientService;
    use App\Services\SpreadsheetClientServiceSecond;
    use App\Services\XmlGenerationService;
    use App\Services\YandexDiskService;
    use Exception;
    use Illuminate\Console\Scheduling\Schedule;
    use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
    use Illuminate\Support\Facades\Log;
    
    class Kernel extends ConsoleKernel {
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
            $schedule->call(function () {
                Log::alert("Starting Schedule");
                $tableRepository = new TableRepository();
                $userRepository = new UserRepository();
                $spreadsheetClientService = new SpreadsheetClientService();
                $tables = $tableRepository->getTables();
                $needsToUpdateTimeStamp = (new Config())->getNeedsToUpdateTimeStamp();
                
                foreach ($tables as $table) {
                    $this->processTable(
                        $table,
                        $tableRepository,
                        $userRepository,
                        $spreadsheetClientService,
                        $needsToUpdateTimeStamp
                    );
                }
                Log::alert("Ending Schedule");
            })
                ->name("Tables2") // имя процесса сбрасывается withoutOverlapping через 24 часа
                ->withoutOverlapping();
    
            $schedule->call(function () {
                Log::channel('xml')->alert("Starting Schedule");
                $tableRepository = new TableRepository();
                $tables = $tableRepository->getTables();
        
                foreach ($tables as $table) {
                    $this->processUpdateXml($table);
                }
                Log::channel('xml')->alert("Ending Schedule");
            })
                ->name("UpdateXML1")
                ->withoutOverlapping();
        }
        
        private function processTable(
            Table $table,
            ITableRepository $tableRepository,
            IUserRepository $userRepository,
            ISpreadsheetClientService $spreadsheetClientService,
            int $needsToUpdateTimeStamp
        ): void
        {
            Log::info("Table '".$table->getGoogleSheetId()."' started");
            try {
                if (
                	!$this->isBlocked($table, $userRepository) &&
                	$this->isModified($table, $spreadsheetClientService)
				) {
                    Log::info("Table '".$table->getGoogleSheetId()."' updating...");
                    $this->startRandomizeTextJob($table);
                    $this->startFillImagesJob($table, $needsToUpdateTimeStamp);
                    $this->startXMLGenerationJob($table);
                    $this->updateLastModified($table, $tableRepository, $needsToUpdateTimeStamp);
                }
            } catch (Exception $exception) {
				$this->logTableError($table, $exception);
            }
            Log::info("Table '".$table->getGoogleSheetId()."' finished.");
        }

		/**
		 * @param Table           $table
		 * @param IUserRepository $userRepository
		 * @return bool
		 * @throws Exception
		 */
        private function isBlocked(Table $table, IUserRepository $userRepository): bool
		{
			$user = $userRepository->getUserById($table->getUserId());

			if (is_null($user)) {
				$message = "Error on '".$table->getGoogleSheetId()."' table have no user!";
				Log::channel('fatal')->error($message);
				throw new Exception($message);
			}

			if ($user->isBlocked()) {
				Log::info("Table '".$table->getGoogleSheetId()."' user is blocked, do nothing.");
				return true;
			}

			return false;
		}

		/**
		 * @param Table                     $table
		 * @param ISpreadsheetClientService $spreadsheetClientService
		 * @return bool
		 * @throws Exception
		 */
        private function isModified(
            Table $table,
            ISpreadsheetClientService $spreadsheetClientService
        ): bool
        {
			$isTableExpired = !is_null($table->getDateExpired()) &&
                (($table->getDateExpired() + 86400) < time());
			if ($isTableExpired) {
				Log::info("Table '".$table->getGoogleSheetId()."' expired.");
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
				Log::info("Table '".$table->getGoogleSheetId()."' is up to date.");
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
         * @param Table $table
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
                $this->logTableError($table, $exception);
            }
        }
        
        private function logTableHandling($table, $job, string $actionType): void
        {
            $message = "Table '".$table->getGoogleSheetId()."' ".$actionType." '".get_class($job)."'...";
            Log::info($message);
            echo $message;
        }
        
        private function logTableError(Table $table, Exception $exception): void
        {
            $message = "Error on '".$table->getGoogleSheetId()."' Kernel".PHP_EOL.$exception->getMessage();
            Log::error($message);
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
            Log::info("Table '".$table->getGoogleSheetId()."' updated.");
        }
        
        private function processUpdateXml(Table $table): void
        {
            Log::channel('xml')->info("Table '".$table->getGoogleSheetId()."' started");
            try {
                Log::channel('xml')->info("Table '".$table->getGoogleSheetId()."' updating...");
                $this->startXMLUpdateJob($table);
            } catch (Exception $exception) {
                Log::channel('xml')->error($exception->getMessage());
            }
            Log::channel('xml')->info("Table '".$table->getGoogleSheetId()."' finished.");
        
            $timeToSleep = 1;
            Log::channel('xml')->info("sleep ".$timeToSleep);
            sleep($timeToSleep);
        }
    
        private function startXMLUpdateJob(Table $table): void
        {
            $this->handleSecondJob(
                $table,
                (new UpdateXMLJob(
                    new SpreadsheetClientServiceSecond(),
                    new XmlGeneration(),
                    new TableRepository(),
                    new GeneratorRepository(),
                    new XmlGenerationService(
                        new SpreadsheetClientServiceSecond(),
                        new SheetNames(),
                        new XmlGeneration(),
                        new DictRepository()
                    ),
                    new SheetNames()
                ))
            );
        }
    
        /**
         * @param Table $table
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
