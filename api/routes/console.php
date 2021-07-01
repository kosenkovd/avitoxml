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
    
    /** @var UserLaravel $user */
    $user = UserLaravel::query()->make();
    $user->roleId = 1;
    $user->email = 'test@test.com';
    $user->password = 'test@test.com';
    $user->apiKey = md5(Guid::uuid4()->toString());
    $user->isBlocked = false;
    
    $user->save();
});
