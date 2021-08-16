<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property int|null                     $id
 * @property int                          $roleId
 * @property int                          $dateCreated
 * @property Carbon                       $created_at
 * @property Carbon                       $updated_at
 * @property string                       $email
 * @property Carbon                       $email_verified_at
 * @property string|null                  $name
 * @property string                       $password
 * @property string|null                  $phoneNumber
 * @property string|null                  $socialNetworkUrl
 * @property bool                         $isBlocked
 * @property string                       $apiKey
 * @property string|null                  $notes
 * @property int|null                     $masterInvitationId
 * @property Wallet                       $wallet
 * @property Collection<TableLaravel>     $tables
 * @property Collection<tableMarketplace> $tablesMarketplace
 * @property Collection<Transaction>      $transactions
 * @property Collection<Invitation>       $invitations
 * @property Invitation                   $masterInvitation
 * @property Collection<UserLaravel>      $referrals
 * @property ReferralProfit               $referralProfit
 * @property TotalProfit                  $totalProfit
 * @property float                        $totalMasterProfit
 * @property int                          $referralsCounter
 * @property int                          $activeReferralsCounter
 * @property int                          $referralIncome
 * @property float                        $balance
 */
class UserLaravel extends \Illuminate\Foundation\Auth\User implements \Illuminate\Contracts\Auth\MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, MustVerifyEmail;
    
    protected $table = 'avitoxml_users';
    
    protected $fillable = [
        'phoneNumber',
        'socialNetworkUrl',
        'isBlocked',
        'apiKey',
        'notes',
        'name',
    ];
    
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'userId', 'id');
    }
    
    public function tables(): HasMany
    {
        return $this->hasMany(TableLaravel::class, 'userId', 'id');
    }
    
    public function tablesMarketplace(): HasMany
    {
        return $this->hasMany(TableMarketplace::class, 'userId', 'id');
    }
    
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'userId', 'id');
    }
    
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'userId', 'id');
    }
    
    public function masterInvitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class, 'masterInvitationId', 'id');
    }
    
    public function referralProfit(): hasOne
    {
        return $this->hasOne(ReferralProfit::class, 'userId', 'id');
    }
    
    public function totalProfit(): hasOne
    {
        return $this->hasOne(TotalProfit::class, 'userId', 'id');
    }
    
    public function getTotalMasterProfitAttribute(): float
    {
        $invitationsIds = $this->invitations->map(function (Invitation $invitation): int {
            return $invitation->id;
        });
        
        return UserLaravel::query()
            ->whereIn('masterInvitationId', $invitationsIds)
            ->whereNotNull('email_verified_at')
            ->whereHas('referralProfit', function (Builder $query) {
                $query->where('amount', '>', 0);
            })
            ->withSum('referralProfit', 'amount')
            ->get(['referral_profit_sum_amount'])
            ->map(function (UserLaravel $userLaravel): float {
                return $userLaravel->referral_profit_sum_amount ?: 0;
            })
            ->sum();
    }
    
    /**
     * @return Collection<UserLaravel>
     */
    public function getReferralsAttribute(): Collection
    {
        $invitationsIds = $this->invitations->map(function (Invitation $invitation) {
            return $invitation->id;
        });
        return UserLaravel::query()
            ->whereIn('masterInvitationId', $invitationsIds)
            ->whereNotNull('email_verified_at')
            ->has('referralProfit')
            ->with('referralProfit')
            ->with('tables:id,userId,dateExpired')
            ->with('tablesMarketplace:id,userId,dateExpired')
            ->get();
    }
    
    public function getReferralsCounterAttribute(): int
    {
        $invitationsIds = $this->invitations->map(function (Invitation $invitation) {
            return $invitation->id;
        });
        return UserLaravel::query()
            ->whereNotNull('email_verified_at')
            ->has('referralProfit')
            ->whereIn('masterInvitationId', $invitationsIds)
            ->count();
    }
    
    public function getActiveReferralsCounterAttribute(): int
    {
        $invitationsIds = $this->invitations->map(function (Invitation $invitation) {
            return $invitation->id;
        });
        return UserLaravel::query()
            ->whereNotNull('email_verified_at')
            ->has('referralProfit')
            ->whereIn('masterInvitationId', $invitationsIds)
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
    
    public function getReferralIncomeAttribute(): int
    {
        $activeReferrals = $this->activeReferralsCounter;
        if ($activeReferrals >= 30) {
            return 30;
        } elseif ($activeReferrals >= 25) {
            return 25;
        } elseif ($activeReferrals >= 20) {
            return 20;
        } elseif ($activeReferrals >= 15) {
            return 15;
        } elseif ($activeReferrals >= 10) {
            return 10;
        } else {
            return 5;
        }
    }
    
    public function getBalanceAttribute(): float
    {
        return $this->wallet ? $this->wallet->balance : 0;
    }
    
    
    public function sendPasswordResetNotification($token): void
    {
        $mail = (new ResetPassword($token))->toMail($this);
        
        mail(
            $this->email,
            $mail->subject,
            $mail->render(),
            "Content-Type: text/html; charset=UTF-8\r\n"
        );
    }
    
    public function sendEmailVerificationNotification(): void
    {
        $mail = (new VerifyEmail())->toMail($this);
        
        mail(
            $this->email,
            $mail->subject,
            $mail->render(),
            "Content-Type: text/html; charset=UTF-8\r\n"
        );
    }
}
