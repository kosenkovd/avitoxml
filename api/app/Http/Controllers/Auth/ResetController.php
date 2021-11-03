<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\ErrorResponse;
use App\Models\UserLaravel;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;

class ResetController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|string|email']);
        
        $credentials = $request->only('email');
        
        /** @var UserLaravel $user */
        $user = Password::getUser($credentials);
        if (is_null($user)) {
            return response()->json(new ErrorResponse(
                Response::$statusTexts[404],
                Lang::get(Password::INVALID_USER)
            ), 404);
        }
//        if ($user->isBlocked) {
//            return response()->json(new ErrorResponse("User is blocked", 'BLOCKED'), 403);
//        }
        
        $status = Password::sendResetLink($credentials);
        
        switch ($status) {
            case (Password::RESET_THROTTLED):
                return response()->json(new ErrorResponse(Response::$statusTexts[429], Lang::get($status)), 429);
            default:
                return response()->json(Lang::get($status));
        }
    }
    
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (UserLaravel $user, string $password) {
                $user->password = Hash::make($password);
            
                $user->save();
            
                event(new PasswordReset($user));
            }
        );
    
        switch ($status) {
            case (Password::INVALID_USER):
                return response()->json(new ErrorResponse(Response::$statusTexts[404], Lang::get($status)), 404);
            case (Password::INVALID_TOKEN):
                return response()->json(new ErrorResponse(Response::$statusTexts[403], Lang::get($status)), 403);
            default:
                return response()->json(Lang::get($status));
        }
    }
}
