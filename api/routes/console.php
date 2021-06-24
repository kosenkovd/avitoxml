<?php

use App\Configuration\Spreadsheet\SheetNames;
use App\Console\Jobs\FillAvitoStatisticsJob;
use App\Models\TableLaravel;
use App\Models\TableMarketplace;
use App\Models\UserLaravel;
use App\Services\AvitoService;
use App\Services\Interfaces\ISpintaxService;
use App\Services\SpintaxService;
use App\Services\SpreadsheetClientService;
use Carbon\Carbon;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Guid\Guid;

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
    /** @var $this ClosureCommand */
    
    $locks = DB::table('cron_lock')->first();
    $this->comment($locks->name);
});
