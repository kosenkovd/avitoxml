<?php


namespace App\Services;


use App\Configuration\Config;
use App\Configuration\Spreadsheet\SheetNames;
use App\Models\GeneratorLaravel;
use App\Models\ReferralProfit;
use App\Models\TotalProfit;
use App\Models\Transaction;
use App\Models\UserLaravel;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionsService
{
    private PriceService $priceService;
    private SheetNames $sheetNames;
    private Config $config;
    
    public function __construct(
        PriceService $priceService,
        SheetNames $sheetNames,
        Config $config
    )
    {
        $this->priceService = $priceService;
        $this->sheetNames = $sheetNames;
        $this->config = $config;
    }
    
    public function createWallet(UserLaravel $user): void
    {
        /** @var Wallet $wallet */
        $wallet = Wallet::query()->make();
        $wallet->userId = $user->id;
        $wallet->save();
    }
    
    public function getAdminBalance(): float
    {
        return $this->getAdminWallet()->balance;
    }
    
    public function handleTopUp(UserLaravel $user, float $amount): bool
    {
        try {
            DB::beginTransaction();
            
            $wallet = $user->wallet;
            $wallet->balance += $amount;
            $wallet->save();
            
            $this->cacheAppBalance($amount);
            $this->cacheTotalProfit(
                $user,
                $amount
            );
            
            Transaction::query()->insert([
                'amount' => $amount,
                'debit' => false,
                'type' => 'top_up_balance',
                'userId' => $user->id
            ]);
            
            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            
            return false;
        }
        
        return true;
    }
    
    public function handleMaxAds(
        UserLaravel $user,
        GeneratorLaravel $generator,
        float $priceWithoutReferralProgram,
        int $maxAds,
        bool $subscribe
    ): bool
    {
        $price = $priceWithoutReferralProgram;
        $invitation = $user->masterInvitation;
        if (!is_null($invitation) && !$user->referralProfit) {
            $price = $this->priceService->getClientPriceWithReferralProgram(
                $priceWithoutReferralProgram,
                $invitation->discount
            );
        }
        
        $wallet = $user->wallet;
        if ($wallet->balance < $price) {
            return false;
        }
    
        $generatorTable = $generator->table ?:
            ($generator->tableMarketplace ?: null);
        if (is_null($generatorTable)) {
            return false;
        }
    
        if ($generator->maxAds < 500) {
            // new table
            if (($generatorTable->dateExpired) < time()) {
                $newDateExpired = Carbon::now()->addDays(30)->timestamp;
            } else {
                $newDateExpired = Carbon::createFromTimestamp($generatorTable->dateExpired)
                    ->addDays(30)
                    ->timestamp;
            }
        } else {
            if (($generatorTable->dateExpired) < time()) {
                $newDateExpired = Carbon::now()->addDays(30)->timestamp;
            } else {
                $newDateExpired = $generatorTable->dateExpired;
            }
        }
        
        try {
            DB::beginTransaction();
            
            $wallet->balance -= $price;
            $wallet->save();
            
            if (!is_null($invitation)) {
                $master = $invitation->master;
                $masterWallet = $master->wallet;
    
                $masterProfit = $invitation->income;
                if (!$user->referralProfit) {
                    $masterProfit = $invitation->profit;
                }
                $amount = $this->priceService->getMasterProfitWithReferralProgram(
                    $priceWithoutReferralProgram,
                    $masterProfit
                );
                $masterWallet->balance += $amount;
                $masterWallet->save();
                
                $this->cacheReferralProfit(
                    $user,
                    $amount
                );
                
                $this->cacheTotalProfit(
                    $user,
                    $amount
                );
                
                Transaction::query()->insert([
                    'amount' => $amount,
                    'debit' => false,
                    'type' => 'referral_program',
                    'userId' => $master->id,
                    'tableId' => $generatorTable->id,
                ]);
            }
            
            $this->setGeneratorsMaxAds(
                $generator,
                $maxAds
            );
            
            $generator->table->dateExpired = $newDateExpired;
            $generator->table->save();

            if ($user->id !== 1662) {
                Transaction::query()->insert([
                    'amount' => $price,
                    'debit' => true,
                    'type' => 'tariff_purchase',
                    'userId' => $user->id,
                    'tableId' => $generatorTable->id,
                ]);
            }

            $this->handleSubscribeGenerator($generator, $subscribe);
            
            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            
            return false;
        }
        
        return true;
    }
    
    public function setGeneratorsMaxAds(GeneratorLaravel $generator, $maxAds): void
    {
        $generator->table->generators->each(function (GeneratorLaravel $generator) use ($maxAds): void {
            if (
                $generator->targetPlatform === $this->sheetNames->getYandex() ||
                $generator->targetPlatform === $this->sheetNames->getMultimarket()
            ) {
                $generator->maxAds = $this->config->getMaxAdsLimit();
                $generator->save();
                return;
            }
            
            $generator->maxAds = $maxAds;
            $generator->save();
        });
    }
    
    public function handleSubscribeGenerator(GeneratorLaravel $generator, bool $subscribe): void
    {
        $generator->table->generators->each(function (GeneratorLaravel $generator) use ($subscribe): void {
            if ($subscribe) {
                $generator->subscribed = true;
                $generator->save();
                return;
            }
            
            $generator->subscribed = false;
            $generator->save();
        });
    }
    
    public function handleMaxAdsAdmin(
        UserLaravel $generatorUser,
        GeneratorLaravel $generator,
        float $priceWithoutReferralProgram,
        int $maxAds
    ): bool
    {
        $price = $priceWithoutReferralProgram;
        
        try {
            DB::beginTransaction();
            
            $this->setGeneratorsMaxAds(
                $generator,
                $maxAds
            );
            
            $generator->table->dateExpired = Carbon::now()->addDays(30)->timestamp;
            $generator->table->save();
            
            Transaction::query()->insert([
                'amount' => $price,
                'debit' => false,
                'type' => 'tariff_gift',
                'userId' => $generatorUser->id
            ]);
            
            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            
            return false;
        }
        
        return true;
    }
    
    private function cacheAppBalance(float $amount): void
    {
        $adminWallet = $this->getAdminWallet();
        $adminWallet->balance += $amount;
        $adminWallet->save();
    }
    
    private function getAdminWallet(): Wallet
    {
        /** @var UserLaravel $admin */
        $admin = UserLaravel::query()->find(1);
        return $admin->wallet;
    }
    
    private function cacheReferralProfit(
        UserLaravel $user,
        float $amount
    ): void
    {
        $cacheProfit = $user->referralProfit;
        if (is_null($cacheProfit)) {
            /** @var ReferralProfit $cacheProfit */
            $cacheProfit = ReferralProfit::query()->make();
            $cacheProfit->userId = $user->id;
            $cacheProfit->amount = $amount;
            $cacheProfit->save();
            
            return;
        }
        
        $cacheProfit->amount += $amount;
        $cacheProfit->save();
    }
    
    private function cacheTotalProfit(
        UserLaravel $user,
        float $amount
    ): void
    {
        $cacheProfit = $user->totalProfit;
        if (is_null($cacheProfit)) {
            /** @var TotalProfit $cacheProfit */
            $cacheProfit = TotalProfit::query()->make();
            $cacheProfit->userId = $user->id;
            $cacheProfit->amount = $amount;
            $cacheProfit->save();
            
            return;
        }
        
        $cacheProfit->amount += $amount;
        $cacheProfit->save();
    }
}
