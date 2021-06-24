<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Generator
 *
 * @property int|null     $id
 * @property int          $tableId
 * @property int          $tableMarketplaceId
 * @property string       $generatorGuid
 * @property int|null     $lastGeneration
 * @property string       $targetPlatform
 * @property int          $maxAds
 * @property TableLaravel $table
 * @property Carbon       $created_at
 * @property Carbon       $updated_at
 */
class GeneratorLaravel extends Model {
    use HasFactory;
    
    protected $table = 'avitoxml_generators';
    
    protected $fillable = [
        'maxAds'
    ];
    
    public function table(): BelongsTo
    {
        return $this->belongsTo(TableLaravel::class, 'tableId', 'id');
    }
}
