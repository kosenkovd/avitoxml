<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\ErrorResponse;
use App\Models\UserLaravel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class VerifyEmailController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param string  $id
     * @param string  $hash
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function verify(string $id, string $hash, Request $request): JsonResponse
    {
        if ($request->hasValidSignature()) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[401],
                    'Invalid/Expired url provided.'
                ), 401);
        }
        
        /** @var UserLaravel $user */
        $user = UserLaravel::query()->find($id);
        if (is_null($user)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        if ($user->hasVerifiedEmail()) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[409],
                    'Already verified'
                ), 409);
        }
        
        $user->markEmailAsVerified();
        
        return response()->json();
    }
    
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        auth()->user()->sendEmailVerificationNotification();
    
        return response()->json();
    }
}
