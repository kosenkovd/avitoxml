<?php


namespace App\Http\Controllers;

use App\DTOs\ErrorResponse;
use App\Enums\Roles;
use App\Models\UserLaravel;
use App\Services\TransactionsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class WalletController extends BaseController
{
    private TransactionsService $transactionsService;
    private Roles $roles;
    
    public function __construct(
        TransactionsService $transactionsService
    )
    {
        $this->transactionsService = $transactionsService;
        $this->roles = new Roles();
    }
    
    /**
     * GET /wallet/balance
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        if ($user->roleId === $this->roles->Admin) {
            return response()->json($this->transactionsService->getAdminBalance());
        }
        
        if (!$user->hasVerifiedEmail()) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[403], 'User must verify email.'),
                403
            );
        }
        
        return response()->json($user->wallet->balance);
    }
    
    /**
     * POST /wallet/deposit
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deposit(Request $request): JsonResponse
    {
//        /** @var UserLaravel $user */
//        $user = auth()->user();
//
//        if (!$user->hasVerifiedEmail()) {
//            return response()->json(new ErrorResponse(
//                Response::$statusTexts[403],
//                'User has no wallet'
//            ), 403);
//        }
//
//        $request->validate([
//            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
//        ]);
//
//        $this->transactionsService->handleTopUp($user, $request->input('amount'));
//
//        return response()->json($user->wallet->balance);
        return response()->json();
    }
}
