<?php


namespace App\Services;


use App\Models\GeneratorLaravel;
use App\Models\UserLaravel;
use Illuminate\Support\Facades\DB;

class PriceService
{
    public function getMaxAdsPrice(
        UserLaravel $user,
        float $priceForAd,
        int $maxAds,
        float $discount,
        GeneratorLaravel $generator
    ): float
    {
        $priceWithoutReferralProgram = $this->getMaxAdsPriceWithoutReferralProgram(
            $priceForAd,
            $maxAds,
            $discount,
            $generator,
        );
        
        $invitation = $user->masterInvitation;
        if (
            !is_null($invitation) &&
            !$user->referralProfit
        ) {
            return $this->getClientPriceWithReferralProgram(
                $priceWithoutReferralProgram,
                $invitation->discount
            );
        }
        
        return $priceWithoutReferralProgram;
    }
    
    public function getClientPriceWithReferralProgram(
        float $priceWithoutReferralProgram,
        int $referralDiscount
    ): float
    {
        return round($priceWithoutReferralProgram * (1 - ($referralDiscount / 100)), 2);
    }
    
    public function getMasterProfitWithReferralProgram(
        float $priceWithoutReferralProgram,
        int $masterProfit
    ): float
    {
        return round($priceWithoutReferralProgram * ($masterProfit / 100), 2);
    }
    
    public function getMaxAdsPriceWithoutReferralProgram(
        float $priceForAd,
        int $maxAds,
        float $discount,
        GeneratorLaravel $generator,
        bool $renewing = false
    ): float
    {
        $price = round($priceForAd * $maxAds * (1 - $discount / 100), 2);
        
        if ($generator && ($generator->maxAds < 500)) {
            return $price;
        }
        
        if ($renewing) {
            return $price;
        }
        
        $maxAdsOld = $generator->maxAds;
        
        $discountOld = DB::table('discount')
            ->where('ads', $maxAds)
            ->first()
            ->discount;
        
        $priceOld = round($priceForAd * $maxAdsOld * (1 - $discountOld / 100), 2);
        $price = $price - $priceOld;
        return ($price > 0) ? $price : 0;
    }
}
