<?php

use App\Console\Jobs\FillImagesJob;
use App\Console\Jobs\RandomizeTextJob;
use App\Repositories\Interfaces\ITableRepository;
use App\Repositories\Interfaces\ITableUpdateLockRepository;
//use App\Services\Interfaces\IGoogleServicesClient;
use App\Services\Interfaces\ISpintaxService;
use App\Services\Logger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

//Artisan::command('table:fillImages {tableID}', function (
//    ITableRepository $tableRepository,
//    ITableUpdateLockRepository $tableUpdateLockRepository,
//    IGoogleServicesClient $googleServicesClient,
//    string $tableID) {
//    $logger = new Logger(
//        "FillImagesBackground",
//        new \App\Configuration\Config()
//    );
//    sleep(rand(1, 60));
//
//    $table = $tableRepository->get($tableID);
//
//    $lock = $tableUpdateLockRepository->getByTableId($table->getTableId());
//    /*if($lock->getFillImagesLock() == 1)
//    {
//        $logger->log("Lock is taken for ".$tableID);
//        return;
//    }*/
//
//    $logger->log("Setting lock for ".$tableID);
//    $tableUpdateLockRepository->update($lock->setFillImagesLock(1));
//
//    $logger->log("Starting FillImagesJob for ".$tableID);
//
//    try
//    {
//        (new FillImagesJob($googleServicesClient))->start($table);
//        $logger->log("Finished job execution for ".$tableID);
//    }
//    catch (Exception $e)
//    {
//        $logger->log("Exception thrown for ".$tableID);
//        $logger->log(var_export($e, true));
//        throw $e;
//    }
//    finally
//    {
//        $logger->log("Resetting lock start for ".$tableID);
//        $tableUpdateLockRepository->update($lock->setFillImagesLock(0));
//        $logger->log("Resetting lock completed for ".$tableID);
//    }
//})->purpose('Fill images for table');
//
//Artisan::command('table:randomizeText {tableID}', function (
//    ITableRepository $tableRepository,
//    ITableUpdateLockRepository $tableUpdateLockRepository,
//    IGoogleServicesClient $googleServicesClient,
//    ISpintaxService $spintaxService,
//    string $tableID) {
//    $logger = new Logger(
//        "RandomizeTextBackground",
//        new \App\Configuration\Config()
//    );
//    sleep(rand(1, 60));
//
//    $table = $tableRepository->get($tableID);
//
//    $lock = $tableUpdateLockRepository->getByTableId($table->getTableId());
//    /*if($lock->getRandomizeTextLock() == 1)
//    {
//        $logger->log("Lock is taken for ".$tableID);
//        return;
//    }*/
//
//    $logger->log("Setting lock for ".$tableID);
//    $tableUpdateLockRepository->update($lock->setRandomizeTextLock(1));
//
//    $logger->log("Starting RandomizeTextJob for ".$tableID);
//
//    try
//    {
//        (new RandomizeTextJob($spintaxService, $googleServicesClient))->start($table);
//        $logger->log("Finished job execution for ".$tableID);
//    }
//    catch (Exception $e)
//    {
//        $logger->log("Exception thrown for ".$tableID);
//        $logger->log(var_export($e, true));
//        throw $e;
//    }
//    finally
//    {
//        $logger->log("Resetting lock start for ".$tableID);
//        $tableUpdateLockRepository->update($lock->setRandomizeTextLock(0));
//        $logger->log("Resetting lock completed for ".$tableID);
//    }
//})->purpose('Randomize text for table');
