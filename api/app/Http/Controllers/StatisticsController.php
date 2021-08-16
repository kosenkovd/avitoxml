<?php


namespace App\Http\Controllers;

use App\Models\GeneratorLaravel;
use App\Models\Invitation;
use App\Models\TableLaravel;
use App\Models\Transaction;
use App\Models\UserLaravel;
use App\Services\TransactionsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;

class StatisticsController extends BaseController
{
    private TransactionsService $transactionsService;
    
    public function __construct(
        TransactionsService $transactionsService
    )
    {
        $this->transactionsService = $transactionsService;
    }
    
    /**
     * GET /statistics
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $totalBalance = $this->transactionsService->getAdminBalance();
        $usersWhoToppedUpBalance = UserLaravel::query()
            ->has('totalProfit')
            ->count();
        
        $LTV = round($totalBalance / $usersWhoToppedUpBalance, 2);
        $userLifeTime = 11111111;
        
        $periods = collect([
            'all' => [],
            'month' => [
                Carbon::now()
                    ->firstOfMonth()
                    ->timestamp,
                Carbon::now()
                    ->timestamp
            ],
            'tomorrow' => [
                Carbon::now()
                    ->subRealDay()
                    ->setTime(0, 0)
                    ->timestamp,
                Carbon::now()
                    ->setTime(0, 0)
                    ->timestamp
            ],
            'today' => [
                Carbon::now()
                    ->setTime(0, 0)
                    ->timestamp,
                Carbon::now()
                    ->timestamp
            ]
        ]);
        
        $invitationsCollection = Invitation::query()->get(['created_at']);
        $invitations = $this->mapCollectionToPeriodCounters($periods, $invitationsCollection);
        $invitations['title'] = 'Всего ссылок выдано';
        $invitations['code'] = 'invitations';
        
        $registrationsCollection = UserLaravel::query()
            ->whereNotNull('email_verified_at')
            ->get('created_at');
        $registrations = $this->mapCollectionToPeriodCounters($periods, $registrationsCollection);
        $registrations['title'] = 'Всего зарегистрировалось';
        $registrations['code'] = 'registrations';
        
        $usersWhoToppedUpBalanceCollection = UserLaravel::query()
            ->has('totalProfit')
            ->get(['created_at']);
        $usersWhoToppedUpBalance = $this->mapCollectionToPeriodCounters($periods, $usersWhoToppedUpBalanceCollection);
        $usersWhoToppedUpBalance['title'] = 'Всего клиентов купили';
        $usersWhoToppedUpBalance['code'] = 'usersWhoToppedUpBalance';
        
        $topUp = $periods->map(function (array $period): float {
            $topUpQuery = Transaction::query()->where('type', 'top_up_balance');
            if (count($period) === 0) {
                return $topUpQuery->sum('amount');
            }
            
            return $topUpQuery->whereBetween('created_at', $period)->sum('amount');
        });
        $topUp['title'] = 'Всего выручка';
        $topUp['code'] = 'topUp';
        
        $maxAds = $periods->map(function (array $period): int {
            $maxAdsQuery = Transaction::query()
                ->where('type', 'tariff_purchase');
            if (count($period) === 0) {
                return $maxAdsQuery->count();
            }
            
            return $maxAdsQuery->whereBetween('created_at', $period)->count();
        });
        $maxAds['title'] = 'Всего оплачено объявлений';
        $maxAds['code'] = 'maxAds';
        
        $tables = $periods->map(function (array $period): int {
            $collection = Transaction::query()
                ->where('type', 'tariff_purchase')
                ->whereNotNull('tableId')
                ->get(['created_at', 'tableId']);
            if (count($period) === 0) {
                return $collection->count();
            }
            
            return $collection
                ->whereBetween('created_at', $period)
                ->count();
        });
        $tables['title'] = 'Всего купили таблиц';
        $tables['code'] = 'tables';
        
        
        $activeTables = TableLaravel::query()
            ->where('dateExpired', '>=', Carbon::now()->timestamp)
            ->count();
        
        $activeUsers = UserLaravel::query()
            ->whereHas('tables', function (Builder $query) {
                $query->where('dateExpired', '>=', Carbon::now()->timestamp);
            })
            ->count();
        
        $totalBalanceForRegistrations = round($totalBalance / $registrations['all'], 2);
        
        $invitationsCounter = Invitation::query()->count();
        $totalBalanceForInvitations = round($totalBalance / $invitationsCounter, 2);
        
        $summaryAds = GeneratorLaravel::query()
            ->whereHas('table', function (Builder $q) {
                $q->where('dateExpired', '>=', Carbon::now()->timestamp);
            })
            ->where('targetPlatform', 'Avito')
            ->where('maxAds', '<=', 5000)
            ->sum('maxAds');
        
        $summaryAdsOnActiveTables = round($summaryAds / $activeTables);
        
        $activeTablesOnActiveUsers = round($activeTables / $activeUsers);
        
        return response()->json([
            'general' => [
                [
                    'title' => 'LTV',
                    'value' => $LTV
                ],
//                [
//                    'title' => 'Время жизни клиента',
//                    'value' => $userLifeTime
//                ],
            ],
            'activities' => [
                $invitations,
                $registrations,
                $usersWhoToppedUpBalance,
                $topUp,
                $maxAds,
                $tables
            ],
            'active' => [
                [
                    'title' => 'Активных таблиц',
                    'value' => $activeTables
                ],
                [
                    'title' => 'Активных пользователей',
                    'value' => $activeUsers
                ],
            ],
            'payments' => [
                [
                    'title' => 'Выручка на регистрацию',
                    'value' => $totalBalanceForRegistrations
                ],
                [
                    'title' => 'Выручка на выданную ссылку',
                    'value' => $totalBalanceForInvitations
                ],
                [
                    'title' => 'Ср. кол-во объяв. на таблицу',
                    'value' => $summaryAdsOnActiveTables
                ],
                [
                    'title' => 'Таблиц на пользователя',
                    'value' => $activeTablesOnActiveUsers
                ],
            ],
        ]);
    }
    
    /**
     * POST /statistics/period
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function period(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'string|required',
            'from' => 'string|required',
        ]);
        
        $period = [
            Carbon::createFromFormat('Y-m-d', $request->input('from'))->setTime(0, 0)->timestamp,
            Carbon::createFromFormat('Y-m-d', $request->input('to'))->setTime(0, 0)->timestamp,
        ];
        
        $invitations = Invitation::query()
            ->whereBetween('created_at', $period)
            ->count();
        
        $registrations = UserLaravel::query()
            ->whereNotNull('email_verified_at')
            ->whereBetween('created_at', $period)
            ->count();
        
        $usersWhoToppedUpBalance = UserLaravel::query()
            ->has('totalProfit')
            ->whereBetween('created_at', $period)
            ->count();
        
        $topUp = Transaction::query()
            ->where('type', 'top_up_balance')
            ->whereBetween('created_at', $period)
            ->sum('amount');
        
        $maxAds = Transaction::query()
            ->where('type', 'tariff_purchase')
            ->whereBetween('created_at', $period)
            ->count();
        
        $tables = Transaction::query()
            ->where('type', 'tariff_purchase')
            ->whereNotNull('tableId')
            ->whereBetween('created_at', $period)
            ->count();
        
        return response()->json([
            'invitations' => $invitations,
            'registrations' => $registrations,
            'usersWhoToppedUpBalance' => $usersWhoToppedUpBalance,
            'topUp' => $topUp,
            'maxAds' => $maxAds,
            'tables' => $tables
        ]);
    }
    
    private function mapCollectionToPeriodCounters(Collection $periods, Collection $searchCollection): Collection
    {
        return $periods->map(function (array $period) use ($searchCollection): int {
            if (count($period) === 0) {
                return $searchCollection->count();
            }
            
            return $searchCollection->whereBetween('created_at', $period)->count();
        });
    }
}
