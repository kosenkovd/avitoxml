<?php


namespace App\Http\Controllers;

use App\Configuration\Config;
use App\DTOs\ErrorResponse;
use App\Enums\Roles;
use App\Http\Resources\AdminTransactionCollection;
use App\Http\Resources\TransactionCollection;
use App\Models\GeneratorLaravel;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\UserLaravel;
use App\Services\PriceService;
use App\Services\TransactionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Guid\Guid;

class TransactionsController extends BaseController
{
    private PriceService $priceService;
    private TransactionsService $transactionsService;
    private Roles $roles;
    private Config $config;
    
    public function __construct(
        PriceService $priceService,
        TransactionsService $transactionsService,
        Config $config
    )
    {
        $this->priceService = $priceService;
        $this->transactionsService = $transactionsService;
        $this->roles = new Roles();
        $this->config = $config;
    }
    
    /**
     * GET /transactions
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
                $transactions = Transaction::query()
                    ->orderBy('created_at', 'DESC')
                    ->with('user:id,email,name,phoneNumber,socialNetworkUrl')
                    ->get();
                return response()->json(new AdminTransactionCollection($transactions));
            case $this->roles->Customer:
                $transactions = $user
                    ->transactions()
                    ->orderBy('created_at', 'DESC')
                    ->get();
                return response()->json(new TransactionCollection($transactions));
        }
    }
    
    /**
     * POST /transactions/maxAds
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function maxAds(Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        $request->validate([
            'targetPlatform' => 'string|required',
            'maxAds' => 'integer|required',
            'generatorGuid' => 'string|required',
            'subscribe' => ['boolean', Rule::requiredIf($user->roleId !== $this->roles->Admin)]
        ]);
        
        /** @var GeneratorLaravel|null $generator */
        $generator = GeneratorLaravel::query()
            ->where('generatorGuid', $request->input('generatorGuid'))
            ->first();
        if (is_null($generator)) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[404], 'Can\'t find user\'s generator'),
                404
            );
        }
        
        $generatorUser = $generator->table ? $generator->table->user :
            ($generator->tableMarketplace ? $generator->tableMarketplace->user : null);
        
        if (is_null($generatorUser)) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[404], 'Can\'t find generator\'s user'),
                404
            );
        }
        if ($user->id !== $generatorUser->id) {
            if ($user->roleId !== $this->roles->Admin) {
                return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
            }
        }
        
        $targetPlatform = $request->input('targetPlatform');
        $maxAds = $request->input('maxAds');
        $subscribe = $request->input('subscribe');
        
        $discount = DB::table('discount')
            ->where('ads', $maxAds)
            ->first();
        if (is_null($discount)) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[400], 'Invalid max ads amount'),
                400
            );
        }
        $discount = $discount->discount;
        
        $priceTargetPlatform = DB::table('prices')
            ->where('targetPlatform', $targetPlatform)
            ->first();
        if (is_null($priceTargetPlatform)) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[400], 'Can\'t find target platform'),
                400
            );
        }
        $priceForAd = $priceTargetPlatform->price;
        
        $priceWithoutReferralProgram = $this->priceService->getMaxAdsPriceWithoutReferralProgram(
            $priceForAd,
            $maxAds,
            $discount,
            $generator,
        );
        
        // Admin
        if ($user->roleId === $this->roles->Admin) {
            if ($priceWithoutReferralProgram == 0) {
                $this->transactionsService->setGeneratorsMaxAds(
                    $generator,
                    $maxAds
                );
                
                return response()->json($generator->table->dateExpired);
            }
            
            $success = $this->transactionsService->handleMaxAdsAdmin(
                $generatorUser,
                $generator,
                $priceWithoutReferralProgram,
                $maxAds
            );
            
            return response()->json($success ? Carbon::now()->addMonth()->timestamp : false);
        }
        
        // Client
        if ($priceWithoutReferralProgram == 0) {
            $this->transactionsService->setGeneratorsMaxAds(
                $generator,
                $maxAds
            );
            $this->transactionsService->handleSubscribeGenerator($generator, $subscribe);
            
            return response()->json($generator->table->dateExpired);
        }
        
        $success = $this->transactionsService->handleMaxAds(
            $generatorUser,
            $generator,
            $priceWithoutReferralProgram,
            $maxAds,
            $subscribe
        );
        
        return response()->json($success ? Carbon::now()->addMonth()->timestamp : false);
    }
    
    public function order(Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        /** @var Order $order */
        $order = Order::query()->make();
        $order->userId = $user->id;
        $order->guid = Guid::uuid4()->toString();
        $order->save();
        
        return response()->json($order->guid);
    }
    
    /**
     * POST /transactions/notifications
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function notifications(Request $request): Response
    {
        Log::channel('transactions')->info('New notification');
        Log::channel('transactions')->info(json_encode($request->toArray()));
        
        $request->validate([
            'Amount' => 'required',
            'CardId' => 'required',
            'ErrorCode' => 'required',
            'ExpDate' => 'required',
            'OrderId' => 'required',
            'Pan' => 'required',
            'PaymentId' => 'required',
            'Status' => 'required',
            'Success' => 'required',
            'TerminalKey' => 'required',
            'Token' => 'required',
        ]);
        
        $orderMessage = "'".$request->get('OrderId')."'";
        
        $credentials = collect($request->only([
            'Amount',
            'CardId',
            'ErrorCode',
            'ExpDate',
            'OrderId',
            'Pan',
            'PaymentId',
            'Status',
            'Success',
            'TerminalKey',
        ]));
        $credentials->put('Password', $this->config->getTerminalPassword());
        $credentials->put('Success', $request->input('Success') ? 'true' : 'false');
        
        $token = $credentials->sortKeys()->join('');
        $check = hash_equals(hash('sha256', $token), $request->input('Token'));
        if (!$check) {
            Log::channel('transactions')->info($orderMessage.' Wrong token');
            
            return response()->json(new ErrorResponse(Response::$statusTexts[400]), 400);
        }
    
        /** @var Order|null $order */
        $order = Order::query()
            ->where('guid', $request->input('OrderId'))
            ->first();
        if (is_null($order)) {
            Log::channel('transactions')->info($orderMessage.' Can\'t find specified order.');
            return response()->json(
                new ErrorResponse(Response::$statusTexts[404], 'Can\'t find specified order.'),
                404
            );
        }
    
        $status = $request->input('Status');
        if ($order->status === 'CONFIRMED') {
            Log::channel('transactions')->info($orderMessage.' Already confirmed');
            return response('OK');
        }
        
        $order->status = $status;
        $order->save();
        
        if ($status !== 'CONFIRMED') {
            Log::channel('transactions')->info($orderMessage.' status - '.$status);
            return response()->json();
        }
    
        /** @var UserLaravel $user */
        $user = UserLaravel::query()
            ->find($order->userId);
        $amount = $request->input('Amount') / 100;
        $success = $this->transactionsService->handleTopUp($user, $amount);
        if (!$success) {
            Log::channel('transactions')->info($orderMessage.' DB error');
            return response()->json(new ErrorResponse(Response::$statusTexts[500]), 500);
        }
    
        Log::channel('transactions')->info($orderMessage.' OK');
        return response('OK');
    }
}
