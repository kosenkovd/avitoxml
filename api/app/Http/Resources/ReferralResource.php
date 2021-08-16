<?php

namespace App\Http\Resources;

use App\Models\Invitation;
use App\Models\ReferralProfit;
use App\Models\TableLaravel;
use App\Models\TableMarketplace;
use App\Models\TotalProfit;
use App\Models\Transaction;
use App\Models\UserLaravel;
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
 * @property string                       $email
 * @property Carbon                       $email_verified_at
 * @property string|null                  $name
 * @property string                       $password
 * @property string|null                  $phoneNumber
 * @property string|null                  $socialNetworkUrl
 * @property bool                         $isBlocked
 * @property string                       $apiKey
 * @property string|null                  $notes
 * @property int|null                     $masterInvitationId
 * @property Wallet                       $wallet
 * @property Collection<TableLaravel>     $tables
 * @property Collection<tableMarketplace> $tablesMarketplace
 * @property Collection<Transaction>      $transactions
 * @property Collection<Invitation>       $invitations
 * @property Invitation                   $masterInvitation
 * @property Collection<UserLaravel>      $referrals
 * @property ReferralProfit               $referralProfit
 * @property TotalProfit                  $totalProfit
 * @property float                        $totalMasterProfit
 * @property int                          $referralsCounter
 * @property int                          $activeReferralsCounter
 * @property int                          $referralIncome
 * @property float                        $balance
 */
class ReferralResource extends JsonResource
{
    public function __construct(UserLaravel $resource)
    {
        parent::__construct($resource);
    }
    
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'referralId' => $this->id,
            'email' => $this->email,
            'created_at' => Carbon::createFromTimeString($this->email_verified_at)->format('d.m.Y H:i'),
            'invitationHash' => $this->masterInvitation->hash,
            'profit' => (float)$this->referralProfit->amount,
        ];
    }
}
