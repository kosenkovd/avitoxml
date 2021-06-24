<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property string|null                  $name
 * @property string|null                  $phoneNumber
 * @property string|null                  $socialNetworkUrl
 * @property bool                         $isBlocked
 * @property string                       $apiKey
 * @property string|null                  $notes
 * @property Collection<TableLaravel>     $tables
 * @property Collection<tableMarketplace> tablesMarketplace
 */
class UserLaravel extends \Illuminate\Foundation\Auth\User
{
    use HasFactory;
    
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
}
