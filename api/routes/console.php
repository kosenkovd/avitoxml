<?php
    
    use App\Console\Jobs\FillImagesJob;
    use App\Console\Jobs\RandomizeTextJob;
    use App\Repositories\TableRepository;
    use App\Services\GoogleServicesClient;
    use App\Services\SpintaxService;
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

Artisan::command('FillImages {table}', function ($table) {
    $table = json_decode($table);
    echo "Starting FillImagesJob for ".$table->getTableGuid();
    (new FillImagesJob(new GoogleServicesClient(), new TableRepository()))->start($table);
});

Artisan::command('FillImages2 {table}', function ($table) {
    $table = json_decode($table);
    echo "Starting RandomizeTextJob for ".$table->getTableGuid();
    (new RandomizeTextJob(new SpintaxService(), new GoogleServicesClient(), new TableRepository()))
        ->start($table);
});
