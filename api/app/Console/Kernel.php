<?php

namespace App\Console;

use App\Configuration\Spreadsheet;
use App\Console\Jobs\FillImagesJob;
use App\Console\Jobs\FillImagesJobYandex;
use App\Console\Jobs\RandomizeTextJob;
use App\Console\Jobs\TriggerSpreadsheetJob;
use App\Repositories\TableRepository;
use App\Repositories\TableUpdateLockRepository;
use App\Services\GoogleDriveClientService;
use App\Services\SpintaxService;
use App\Services\SpreadsheetClientService;
use App\Services\YandexDiskService;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    private static function execInBackground($cmd) {
        if (substr(php_uname(), 0, 7) == "Windows"){
            pclose(popen("start /B ". $cmd, "r"));
        }
        else {
            exec($cmd . " > /dev/null &");
        }
    }

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
        $tableRepository = new TableRepository(new TableUpdateLockRepository());
        $tables = $tableRepository->getTables();

        foreach ($tables as $table)
        {
            /*$schedule->exec("cd /var/www/agishev-xml.ru/api && php artisan table:fillImages ".$table->getTableGuid())
                ->name("Fill image links command ".$table->getTableId())
                ->everyFiveMinutes()
                ->runInBackground()
                ->withoutOverlapping(60);

            $schedule->exec("cd /var/www/agishev-xml.ru/api && php artisan table:randomizeText ".$table->getTableGuid())
                ->name("Randomize text command ".$table->getTableId())
                ->everyFiveMinutes()
                ->runInBackground()
                ->withoutOverlapping(60);

            sleep(1);*/

            $schedule->call(function () use($table) {
                echo "Starting FillImagesJob for ".$table->getTableGuid();
                (new FillImagesJobYandex(new SpreadsheetClientService(), new YandexDiskService()))
                    ->start($table);
            })
                ->name("Randomize yandex images ".$table->getTableId())
                ->everyFiveMinutes()
                ->runInBackground()
                ->withoutOverlapping();


            $schedule->call(function () use($table) {
                echo "Starting RandomizeTextJob for ".$table->getTableGuid();
                (new RandomizeTextJob(new SpintaxService(), new SpreadsheetClientService()))
                    ->start($table);
            })
                ->name("Randomize text ".$table->getTableId())
                ->everyFiveMinutes()
                ->runInBackground()
                ->withoutOverlapping();
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
