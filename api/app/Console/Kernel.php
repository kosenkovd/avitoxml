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
        $schedule->call(function () {
            (new FillImagesJob(new GoogleServicesClient(), new TableRepository()))->start();
        })->everyTwoMinutes();
        $schedule->call(function () {
            (new RandomizeTextJob(new SpintaxService(), new GoogleServicesClient(), new TableRepository()))->start();
        })->everyThreeMinutes();
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
