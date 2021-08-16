<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Passport\HasApiTokens;

/**
 * Class UserLaravel
 *
 * @property int         $id
 * @property int         $userId
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property float       $balance
 * @property UserLaravel $user
 */
class Wallet extends Model
{
    use HasFactory;
    
    protected $table = 'avitoxml_wallets';
    
    protected $fillable = [
        'balance',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserLaravel::class, 'walletId', 'id');
    }
}
