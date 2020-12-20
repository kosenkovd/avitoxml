<?php

use App\Repositories\TableRepository;
use App\Services\Interfaces\IGoogleServicesClient;
use App\Services\Interfaces\ISpintaxService;
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

Artisan::command('table:fillImages {tableID}', function (
    TableRepository $tableRepository,
    IGoogleServicesClient $googleServicesClient,
    string $tableID) {
    echo "Starting FillImagesJob for ".$tableID;
    $table = $tableRepository->get($tableID);
    (new \App\Console\Jobs\FillImagesJob($googleServicesClient))->start($table);
})->purpose('Fill images for table');

Artisan::command('table:randomizeText {tableID}', function (
    TableRepository $tableRepository,
    IGoogleServicesClient $googleServicesClient,
    ISpintaxService $spintaxService,
    string $tableID) {
    echo "Starting RandomizeTextJob for ".$tableID;
    $table = $tableRepository->get($tableID);
    (new \App\Console\Jobs\RandomizeTextJob($spintaxService, $googleServicesClient))->start($table);
})->purpose('Fill images for table');
