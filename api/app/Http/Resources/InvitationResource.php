<?php

namespace App\Http\Resources;

use App\Models\UserLaravel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class InviteResource
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
 */
class InvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'invitationHash' => $this->hash,
            'income' => $this->income,
            'discount' => (int)$this->discount,
            'registrations' => $this->registrations,
            'referralsCounter' => $this->referralsCounter,
            'activeReferralsCounter' => $this->activeReferralsCounter,
        ];
    }
}
