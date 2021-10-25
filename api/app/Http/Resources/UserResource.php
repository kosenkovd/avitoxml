<?php

namespace App\Http\Resources;

use App\DTOs\UserDTO;
use App\Enums\Roles;
use App\Models\TableLaravel;
use App\Models\TableMarketplace;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class UserResource
 *
 * @property int|null                     $id
 * @property int                          $roleId
 * @property int                          $dateCreated
 * @property Carbon                       $created_at
 * @property Carbon                       $updated_at
 * @property string|null                  $name
 * @property string                       $email
 * @property string|null                  $phoneNumber
 * @property string|null                  $socialNetworkUrl
 * @property bool                         $isBlocked
 * @property string                       $apiKey
 * @property string|null                  $notes
 * @property Collection<TableLaravel>     $tables
 * @property Collection<tableMarketplace> tablesMarketplace
 * @property Wallet                       $wallet
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return UserDTO
     */
    public function toArray($request): UserDTO
    {
        $roles = new Roles();
        $balance = (($this->id === 1662) || ($this->roleId === $roles->Admin) ||
            ($this->roleId === $roles->Service) || !$this->wallet) ?
            null :
            $this->wallet->balance;
        
        return (new UserDTO())
            ->setUserId($this->id)
            ->setRoleId($this->roleId)
            ->setDateCreated($this->dateCreated ?: $this->created_at->timestamp)
            ->setPhoneNumber($this->phoneNumber)
            ->setSocialNetworkUrl($this->socialNetworkUrl)
            ->setIsBlocked($this->isBlocked)
            ->setNotes($this->notes)
            ->setName($this->name)
            ->setToken($this->apiKey)
            ->setEmail($this->email)
            ->setHasVerifyEmail($this->hasVerifiedEmail())
            ->setBalance($balance);
    }
}
