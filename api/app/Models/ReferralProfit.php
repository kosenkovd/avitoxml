<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class ReferralProfit
 *
 * @property int    $id
 * @property int    $userId
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property float  $amount
 */
class ReferralProfit extends Model
{
    use HasFactory;
    
    protected $table = 'avitoxml_referral_profit';
    
    protected $fillable = [];
}
