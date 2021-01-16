<?php

namespace App\Console;

use App\Configuration\Spreadsheet;
use App\Console\Jobs\TriggerSpreadsheetJob;
use App\Repositories\TableRepository;
use App\Repositories\TableUpdateLockRepository;
use App\Services\SpreadsheetClientService;
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
        $schedule->call(function () {
            echo "Starting TriggerSpreadsheetJob".PHP_EOL;
            (new TriggerSpreadsheetJob(
                new SpreadsheetClientService(), new Spreadsheet()))->start();
        })
            ->cron("50 * * * *");

        $tableRepository = new TableRepository(new TableUpdateLockRepository());
        $tables = $tableRepository->getTables();

        foreach ($tables as $table)
        {
            $schedule->exec("cd /var/www/agishev-xml.ru/api && php artisan table:fillImages ".$table->getTableGuid())
                ->name("Fill image links command ".$table->getTableId())
                ->everyFiveMinutes()
                ->runInBackground()
                ->withoutOverlapping(60);

            $schedule->exec("cd /var/www/agishev-xml.ru/api && php artisan table:randomizeText ".$table->getTableGuid())
                ->name("Randomize text command ".$table->getTableId())
                ->everyFiveMinutes()
                ->runInBackground()
                ->withoutOverlapping(60);

            sleep(1);

            /*$schedule->call(function() use($table) {
                exec("cd ~/avitoxml.beget.tech/public_html/api && /usr/local/bin/php7.4 artisan table:fillImages ".$table->getTableGuid()."  > /dev/null &");
            })
                ->name("Fill image links exec command ".$table->getTableId())
                ->everyMinute();

            $schedule->call(function () use($table) {
                echo "Starting FillImagesJob for ".$table->getTableGuid();
                (new FillImagesJob(new GoogleServicesClient()))
                    ->start($table);
            })
                ->name("Randomize images ".$table->getTableId())
                ->everyTenMinutes()
                ->runInBackground()
                ->withoutOverlapping();

            $schedule->call(function () use($table) {
                echo "Starting RandomizeTextJob for ".$table->getTableGuid();
                (new RandomizeTextJob(new SpintaxService(), new GoogleServicesClient()))
                    ->start($table);
            })
                ->name("Randomize text ".$table->getTableId())
                ->everyFiveMinutes()
                ->runInBackground()
                ->withoutOverlapping();*/
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
