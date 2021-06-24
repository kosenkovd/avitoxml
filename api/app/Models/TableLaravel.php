<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Table
 *
 * @property int|null                     $id
 * @property int                          $userId
 * @property string                       $googleSheetId
 * @property string|null                  $googleDriveId
 * @property string|null                  $yandexToken
 * @property string|null                  $avitoClientId
 * @property string|null                  $avitoClientSecret
 * @property string|null                  $avitoUserId
 * @property int|null                     $dateExpired
 * @property bool                         $isDeleted
 * @property int|null                     $dateDeleted
 * @property string|null                  $notes
 * @property string                       $tableGuid
 * @property int                          $dateLastModified
 * @property Collection<GeneratorLaravel> $generators
 * @property UserLaravel                  $user
 * @property Carbon                       $created_at
 * @property Carbon                       $updated_at
 */
class TableLaravel extends Model {
    use HasFactory;
    
    protected $table = 'avitoxml_tables';
    
    protected $fillable = [
        'dateExpired',
        'notes',
        'yandexToken',
        'avitoClientId',
        'avitoClientSecret',
        'avitoUserId',
    ];
    
    public function generators(): HasMany
    {
        return $this->hasMany(GeneratorLaravel::class, 'tableId', 'id');
    }
    
    public function generator(string $generatorGuid): ?GeneratorLaravel
    {
        foreach ($this->generators as $generator) {
            if (strcmp($generator->generatorGuid, $generatorGuid) == 0) {
                return $generator;
            }
        }
        
        return null;
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserLaravel::class, 'userId', 'id');
    }
}
