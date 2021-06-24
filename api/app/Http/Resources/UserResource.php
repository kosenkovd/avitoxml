<?php

namespace App\Http\Resources;

use App\DTOs\UserDTO;
use App\Models\TableLaravel;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class UserResource
 *
 * @property int|null                $id
 * @property int                     $roleId
 * @property int                     $dateCreated
 * @property Carbon                  $created_at
 * @property Carbon                  $updated_at
 * @property string|null             $name
 * @property string|null             $phoneNumber
 * @property string|null             $socialNetworkUrl
 * @property bool                    $isBlocked
 * @property string                  $apiKey
 * @property string|null             $notes
 * @property Collection<TableLaravel> $tables
 */
class UserResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return UserDTO
     */
    public function toArray($request): UserDTO
    {
        return (new UserDTO())
            ->setUserId($this->id)
            ->setRoleId($this->roleId)
            ->setDateCreated($this->dateCreated ?: $this->created_at->timestamp)
            ->setPhoneNumber($this->phoneNumber)
            ->setSocialNetworkUrl($this->socialNetworkUrl)
            ->setIsBlocked($this->isBlocked)
            ->setNotes($this->notes)
            ->setName($this->name)
            ->setToken($this->apiKey);
    }
}
