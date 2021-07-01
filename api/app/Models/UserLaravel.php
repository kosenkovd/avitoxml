<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
 * @property string|null                  $name
 * @property string                       $password
 * @property string|null                  $phoneNumber
 * @property string|null                  $socialNetworkUrl
 * @property bool                         $isBlocked
 * @property string                       $apiKey
 * @property string|null                  $notes
 * @property Collection<TableLaravel>     $tables
 * @property Collection<tableMarketplace> tablesMarketplace
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
    
    public function tables(): HasMany
    {
        return $this->hasMany(TableLaravel::class, 'userId', 'id');
    }
    
    public function tablesMarketplace(): HasMany
    {
        return $this->hasMany(TableMarketplace::class, 'userId', 'id');
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
