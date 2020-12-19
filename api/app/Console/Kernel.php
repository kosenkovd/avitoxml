<?php

namespace App\Console;

use App\Console\Jobs\RandomizeTextJob;
use App\Repositories\TableRepository;
use App\Services\GoogleServicesClient;
use App\Services\SpintaxService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Jobs\FillImagesJob;

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
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $startTime = time();
        $timeout = 540;

        $tableRepository = new TableRepository();
        $tables = $tableRepository->getTables();

        // Shuffle, because all tasks are run synchronously and stops by timeout, and this
        // will allow tables from end to be processed too.
        shuffle($tables);
        foreach ($tables as $table)
        {
            $schedule->call(function () use($table) {
                echo "Starting FillImagesJob for ".$table->getTableGuid();
                (new FillImagesJob(new GoogleServicesClient(), new TableRepository()))->start($table);
            })
                ->name("Fill image links ".$table->getTableId())
                ->everyTenMinutes()
                ->withoutOverlapping();

            $schedule->call(function () use($table) {
                echo "Starting RandomizeTextJob for ".$table->getTableGuid();
                (new RandomizeTextJob(new SpintaxService(), new GoogleServicesClient(), new TableRepository()))
                    ->start($table);
            })
                ->name("Randomize text ".$table->getTableId())
                ->everyFiveMinutes()
                ->withoutOverlapping();

            if(time() >= $startTime + $timeout)
            {
                break;
            }
        }
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
