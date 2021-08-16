<?php


namespace App\Http\Controllers;

use App\DTOs\ErrorResponse;
use App\Enums\Roles;
use App\Http\Resources\ReferralCollection;
use App\Http\Resources\ReferralStatisticsCollection;
use App\Models\ReferralProfit;
use App\Models\UserLaravel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class ReferralsController extends BaseController
{
    private Roles $roles;
    
    public function __construct()
    {
        $this->roles = new Roles();
    }
    
    /**
     * GET /referrals
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        switch ($user->roleId) {
            case $this->roles->Admin:
                $allReferrals = UserLaravel::query()
                    ->whereNotNull('email_verified_at')
                    ->whereHas('invitations', function (Builder $q) {
                        $q->whereHas('users', function (Builder $q) {
                            $q->whereHas('referralProfit');
                        });
                    })
                    ->get();
                return response()->json(new ReferralStatisticsCollection($allReferrals));
            case $this->roles->Customer:
                return response()->json(new ReferralCollection($user->referrals));
        }
    }
    
    /**
     * GET /referrals/profit
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function partnersProfit(Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        if ($user->roleId !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        $partnersProfit = ReferralProfit::query()
            ->sum('amount');
        return response()->json($partnersProfit);
    }
    
    /**
     * GET /referrals/counters
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function counters(Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        switch ($user->roleId) {
            case $this->roles->Admin:
                $referralsCounter = UserLaravel::query()
                    ->whereNotNull('email_verified_at')
                    ->whereNotNull('masterInvitationId')
                    ->has('referralProfit')
                    ->count();
                $activeReferralsCounter = UserLaravel::query()
                    ->whereNotNull('email_verified_at')
                    ->whereNotNull('masterInvitationId')
                    ->has('referralProfit')
                    ->where(function (Builder $q) {
                        $q->whereHas('tables', function (Builder $query) {
                            $query->where('dateExpired', '>=', time());
                        })
                            ->orWhereHas('tablesMarketplace', function (Builder $query) {
                                $query->where('dateExpired', '>=', time());
                            });
                    })
                    ->count();
                return response()->json([
                    'referralsCounter' => $referralsCounter,
                    'activeReferralsCounter' => $activeReferralsCounter,
                ]);
            case $this->roles->Customer:
                return response()->json([
                    'referralsCounter' => $user->referralsCounter,
                    'activeReferralsCounter' => $user->activeReferralsCounter,
                ]);
        }
    }
}
