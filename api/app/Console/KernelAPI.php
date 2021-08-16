<?php

namespace App\Console;

use App\Configuration\Config;
use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Console\Jobs\FillAmountJob;
use App\Console\Jobs\FillAvitoReportJob;
use App\Console\Jobs\FillAvitoStatisticsJob;
use App\Console\Jobs\FillImagesJob;
use App\Console\Jobs\FillImagesJobYandex;
use App\Console\Jobs\GenerateXMLJob;
use App\Console\Jobs\JobBase;
use App\Console\Jobs\RandomizeTextJob;
use App\Console\Jobs\UpdateXMLJob;
use App\Models\GeneratorLaravel;
use App\Models\Table;
use App\Models\TableLaravel;
use App\Models\UserLaravel;
use App\Repositories\DictRepository;
use App\Repositories\GeneratorRepository;
use App\Repositories\Interfaces\ITableRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Repositories\TableRepository;
use App\Repositories\UserRepository;
use App\Services\AvitoService;
use App\Services\GoogleDriveClientService;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\PriceService;
use App\Services\SpintaxService;
use App\Services\SpreadsheetClientService;
use App\Services\SpreadsheetClientServiceSecond;
use App\Services\TransactionsService;
use App\Services\XmlGenerationService;
use App\Services\YandexDiskService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * @param Schedule $schedule
     *
     * @return void
     * @throws Exception
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            /** @var TransactionsService $transactionService */
            $transactionService = resolve(TransactionsService::class);
            /** @var PriceService $priceService */
            $priceService = resolve(PriceService::class);
            $targetPlatform = 'Avito';
            
            $tables = TableLaravel::query()
                ->where('dateExpired', '<=', time())
                ->whereHas('user', function (Builder $q) {
                    $q->whereNotNull('email_verified_at')
                        ->where('isBlocked', false);
                })
                ->whereHas('generators', function (Builder $q) use ($targetPlatform) {
                    $q->where('targetPlatform', $targetPlatform)
                        ->where('subscribed', true);
                })
                ->with('user:id,walletId,masterInvitationId')
                ->with('generators:id,tableId,targetPlatform,maxAds,subscribed')
                ->get();
            
            $tables->each(function (TableLaravel $table) use ($priceService, $transactionService, $targetPlatform) {
                Log::channel('subscribe')
                    ->info("Table '".$table->googleSheetId."' Renewing");
                
                /** @var GeneratorLaravel $generator */
                foreach ($table->generators as $generator) {
                    if ($generator->targetPlatform === $targetPlatform) {
                        $maxAds = $generator->maxAds;
                        
                        $discount = DB::table('discount')
                            ->where('ads', $maxAds)
                            ->first();
                        if (is_null($discount)) {
                            Log::channel('subscribe')
                                ->error("Table '".$table->googleSheetId."' Invalid max ads value");
                            continue;
                        }
                        $discount = $discount->discount;
                        
                        $priceTargetPlatform = DB::table('prices')
                            ->where('targetPlatform', $targetPlatform)
                            ->first();
                        $priceForAd = $priceTargetPlatform->price;
                        
                        $priceWithoutReferralProgram = $priceService->getMaxAdsPriceWithoutReferralProgram(
                            $priceForAd,
                            $maxAds,
                            $discount,
                            $generator,
                            true
                        );
                        
                        $success = $transactionService->handleMaxAds(
                            $table->user,
                            $generator,
                            $priceWithoutReferralProgram,
                            $maxAds,
                            $generator->subscribed
                        );
                        
                        Log::channel('subscribe')
                            ->info("Table '".$table->googleSheetId."' ".($success ? "Renewed" : "Has errors"));
                        break;
                    }
                }
            });
        })
            ->name("subscribe") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
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
