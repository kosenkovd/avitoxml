<?php

namespace App\Console;

use App\Models\GeneratorLaravel;
use App\Models\TableLaravel;
use App\Models\UserLaravel;
use App\Services\PriceService;
use App\Services\TransactionsService;
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
                    $q->whereNotNull('email_verified_at');
                })
                ->whereHas('generators', function (Builder $q) use ($targetPlatform) {
                    $q->where('targetPlatform', $targetPlatform)
                        ->where('subscribed', true);
                })
                ->with('user:id,walletId,masterInvitationId')
                ->with('generators:id,tableId,targetPlatform,maxAds,subscribedMaxAds,subscribed')
                ->get();
            
            $tables->each(function (TableLaravel $table) use ($priceService, $transactionService, $targetPlatform) {
                Log::channel('subscribe')
                    ->info("Table '".$table->googleSheetId."' Renewing");
                
                /** @var GeneratorLaravel $generator */
                foreach ($table->generators as $generator) {
                    if ($generator->targetPlatform === $targetPlatform) {
                        
                        switch ($table->userId) {
                            // premium
                            case 38:
                            case 40:
                            case 41:
                            case 42:
                            case 320:
            
                                // float 1200.00
                                $premiumPrice = 1200.00;
                                
                                $user = $table->user;
                                $success = $transactionService->handleMaxAds(
                                    $user,
                                    $generator,
                                    $premiumPrice,
                                    $generator->maxAds,
                                    true
                                );
            
                                if ($success) {
                                    $table->generators->each(function (GeneratorLaravel $generator) {
                                        $generator->subscribedMaxAds = null;
                                        $generator->save();
                                    });
                
                                    if ($user->isBlocked) {
                                        $user->isBlocked = false;
                                        $user->save();
                                    }
                                }
            
                                Log::channel('subscribe')
                                    ->info("Table '".$table->googleSheetId."' ".($success ? "Renewed" : "Has errors"));
            
                                return;
                            default:
                        }

                        // Regular users

                        // Выбирается указанное на след. месяц число
                        $maxAds = $generator->subscribedMaxAds ?: $generator->maxAds;
                        
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
                        
                        $user = $table->user;
                        $success = $transactionService->handleMaxAds(
                            $user,
                            $generator,
                            $priceWithoutReferralProgram,
                            $maxAds,
                            true
                        );
                        
                        if ($success) {
                            $table->generators->each(function (GeneratorLaravel $generator) {
                                $generator->subscribedMaxAds = null;
                                $generator->save();
                            });

                            if ($user->isBlocked) {
                                $user->isBlocked = false;
                                $user->save();
                            }
                        }
                        
                        Log::channel('subscribe')
                            ->info("Table '".$table->googleSheetId."' ".($success ? "Renewed" : "Has errors"));
                        break;
                    }
                }
            });
        })
            ->name("subscribe") // имя процесса сбрасывается withoutOverlapping через 24 часа
            ->withoutOverlapping();
        
        $schedule->call(function () {
            UserLaravel::query()
                ->where('isBlocked', false)
                ->whereDoesntHave('tables', function (Builder $q) {
                    $q->where('dateExpired', '>', time() - 86400);
                })
                ->update(['isBlocked' => true]);
            UserLaravel::query()
                ->where('isBlocked', true)
                ->whereHas('tables', function (Builder $q) {
                    $q->where('dateExpired', '>', time() - 86400);
                })
                ->update(['isBlocked' => false]);
        })
            ->name("archive") // имя процесса сбрасывается withoutOverlapping через 24 часа
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
