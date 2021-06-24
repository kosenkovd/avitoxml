<?php

namespace App\Http\Resources;

use App\DTOs\TableDTO;
use App\Helpers\LinkHelper;
use App\Models\GeneratorLaravel;
use App\Models\TableMarketplace;
use App\Models\UserLaravel;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class TableResource
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
class TableMarketplaceResource extends JsonResource
{
    public function __construct(TableMarketplace $resource)
    {
        parent::__construct($resource);
    }
    
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return TableDTO
     */
    public function toArray($request): TableDTO
    {
        $generators = $this->generators->map(function (GeneratorLaravel $generator) {
            return [
                'userId' => $generator->id,
                'generatorGuid' => $generator->generatorGuid,
                'targetPlatform' => $generator->targetPlatform,
                'maxAds' => $generator->maxAds,
            ];
        });
        
        return (new TableDTO())
            ->setTableId($this->id)
            ->setUserId($this->userId)
            ->setTableGuid($this->tableGuid)
            ->setGoogleSheetId($this->googleSheetId)
            ->setGoogleSheetUrl(LinkHelper::getGoogleSpreadsheetLink($this->googleSheetId))
            ->setGenerators($generators)
            ->setDateExpired($this->dateExpired)
            ->setTableNotes($this->notes)
            ->setType('marketplaces');
    }
}
