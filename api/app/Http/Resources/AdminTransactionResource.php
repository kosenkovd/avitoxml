<?php

namespace App\Http\Resources;

use App\Models\UserLaravel;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

/**
 * Class AdminTransactionResource
 *
 * @property int         $id
 * @property int         $userId
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property float       $amount
 * @property bool        $debit
 * @property string      $type
 * @property UserLaravel $user
 */
class AdminTransactionResource extends JsonResource
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
            'amount' => $this->amount,
            'debit' => $this->debit,
            'type' => Lang::get('transactions.'.$this->type, [], 'ru'),
            'referralId' => $this->userId,
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'email' => $this->user->email,
            'name' => $this->user->name,
            'phoneNumber' => $this->user->phoneNumber,
            'socialNetworkUrl' => $this->user->socialNetworkUrl,
        ];
    }
}
