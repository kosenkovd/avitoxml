<?php

namespace App\Http\Resources;

use App\DTOs\GeneratorDTO;
use App\Helpers\LinkHelper;
use App\Models\TableLaravel;
use App\Models\TableMarketplace;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class GeneratorResource
 *
 * @property int|null         $id
 * @property int              $tableId
 * @property int              $tableMarketplaceId
 * @property string           $generatorGuid
 * @property int|null         $lastGeneration
 * @property string           $targetPlatform
 * @property int              $maxAds
 * @property bool             $subscribed
 * @property TableLaravel     $table
 * @property TableMarketplace $tableMarketplace
 * @property Carbon           $created_at
 * @property Carbon           $updated_at
 */
class GeneratorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return GeneratorDTO
     */
    public function toArray($request): GeneratorDTO
    {
        return (new GeneratorDTO())
            ->setTargetPlatform($this->targetPlatform)
            ->setGeneratorUrl(LinkHelper::getXmlGeneratorLink($this->table->tableGuid, $this->generatorGuid))
            ->setGeneratorGuid($this->generatorGuid)
            ->setMaxAds($this->maxAds)
            ->setSubscribedMaxAds($this->subscribedMaxAds)
            ->setSubscribed($this->subscribed);
    }
}
