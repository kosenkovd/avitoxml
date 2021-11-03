<?php

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Console\Jobs\FillAvitoStatisticsJob;
use App\Console\Jobs\ParserAmountJob;
use App\Models\TableLaravel;
use App\Models\TableMarketplace;
use App\Models\UserLaravel;
use App\Services\AvitoService;
use App\Services\Interfaces\ISpintaxService;
use App\Services\SpintaxService;
use App\Services\SpreadsheetClientService;
use App\Services\SpreadsheetClientServiceThird;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

Artisan::command('test', function () {
    /** @var $this ClosureCommand */
    
    $client = new Google_Client();
    $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
    $client->addScope(Google_Service_Drive::DRIVE);
    $client->setAccessType('offline');
    $client->setAuthConfig(__dir__.'/../app/Configuration/OAuth.json');


//    $client->setRedirectUri('https://api.agishev-autoz.ru/api/test'); // start
//    $authUrl = $client->createAuthUrl();
//    $this->comment($authUrl);
//    die; // end
    
    
    $access_token = json_decode(file_get_contents(__dir__.'/../app/Configuration/test1628610353.json'), true);
    
    $client->setAccessToken($access_token);
    
    $tables = TableMarketplace::all();
    
//    class Some
//    {
//        public static bool $skip = true;
//
//        public static function setSome(bool $skip): void
//        {
//            self::$skip = $skip;
//        }
//
//    }
    
    $tables->each(function (TableMarketplace $table) use ($client) {
//        if ($table->googleSheetId === '1mwKkW_d_Y2bz50fLI1PtmCofo7LTEqioyInIfJC9hsw') {
//            Some::setSome(false);
//        }
//
//        if (Some::$skip) {
//            return;
//        }
//
        Log::channel('test')->info($table->id);
        Log::channel('test')->info($table->googleSheetId);
        try {
            setPermissions($table->googleSheetId, $client);
            Log::channel('test')->info($table->googleSheetId.' ok');
        } catch (Exception $exception) {
            Log::channel('test')->error($exception->getMessage());
        }
        
        sleep(5);
//        die;
    });
});

function setPermissions(string $id, Google_Client $client): void
{
    $client->addScope(Google_Service_Drive::DRIVE);
    $driveService = new Google_Service_Drive($client);
    $drivePermissions = new Google_Service_Drive_Permission();
    
    $drivePermissions->setRole('writer');
    $drivePermissions->setType('anyone');
    $driveService->permissions->create($id, $drivePermissions);
    
    $drivePermissions->setRole('writer');
    $drivePermissions->setType('user');
    $drivePermissions->setEmailAddress('Ipagishev@gmail.com');
    $driveService->permissions->create($id, $drivePermissions);
    
    $drivePermissions->setRole('owner');
    $drivePermissions->setType('user');
    $drivePermissions->setEmailAddress('xml.avito@gmail.com');
    $driveService->permissions->create(
        $id,
        $drivePermissions,
        [
            "transferOwnership" => true
        ]);
}
