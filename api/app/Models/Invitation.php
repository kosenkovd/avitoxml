<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
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
 * Class Invitation
 *
 * @property int                     $id
 * @property int                     $userId
 * @property Carbon                  $created_at
 * @property Carbon                  $updated_at
 * @property string                  $hash
 * @property int                     $discount
 * @property UserLaravel             $master
 * @property Collection<UserLaravel> $users
 * @property int                     $registrations
 * @property int                     $referralsCounter
 * @property int                     $activeReferralsCounter
 * @property int                     $income
 * @property int                     $profit
 */
class Invitation extends Model
{
    use HasFactory;
    
    protected $table = 'avitoxml_invitations';
    
    protected $fillable = [];
    
    public function master(): belongsTo
    {
        return $this->belongsTo(UserLaravel::class, 'userId', 'id');
    }
    
    public function users(): hasMany
    {
        return $this->hasMany(UserLaravel::class, 'masterInvitationId', 'id');
    }
    
    public function getRegistrationsAttribute(): int
    {
        return $this->users()->count();
    }
    
    public function getReferralsCounterAttribute(): int
    {
        return $this->users()->whereNotNull('email_verified_at')->count();
    }
    
    public function getActiveReferralsCounterAttribute(): int
    {
        return $this->users()
            ->whereNotNull('email_verified_at')
            ->where(function (Builder $q) {
                $q->whereHas('tables', function (Builder $query) {
                    $query->where('dateExpired', '>=', time());
                })
                    ->orWhereHas('tablesMarketplace', function (Builder $query) {
                        $query->where('dateExpired', '>=', time());
                    });
            })
            ->count();
    }
    
    public function getIncomeAttribute(): int
    {
        return $this->master->referralIncome;
    }
    
    public function getProfitAttribute(): int
    {
        return $this->income - $this->discount;
    }
}
