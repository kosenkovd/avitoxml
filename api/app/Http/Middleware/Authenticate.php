<?php

namespace App\Http\Middleware;

use App\DTOs\ErrorResponse;
use App\Models\UserLaravel;
use Closure;
use \Illuminate\Http\Request;
use Illuminate\Http\Response;

class Authenticate
{
    private static array $anonymousAllowed = [
        "App\Http\Controllers\GeneratorController@show",
    ];
    
    /**
     * Authenticate user.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $action = $request->route()->getAction()["controller"];
        if (in_array($action, self::$anonymousAllowed)) {
            return $next($request);
        }
        
        /** @var UserLaravel|null $user */
        $user = auth()->user();
        if (is_null($user)) {
            // hash
            $hash = $request->query('hash');
            if (is_null($hash)) {
                return response()->json(new ErrorResponse(Response::$statusTexts[401]), 401);
            }
            
            /** @var UserLaravel $user */
            $user = UserLaravel::query()->where('apiKey', $hash)->first();
            if (is_null($user)) {
                return response()->json(
                    new ErrorResponse("User with specified hash was not found"),
                    403
                );
            }
            
            if ($user->isBlocked) {
                return response()->json(
                    new ErrorResponse("User is blocked", 'BLOCKED'),
                    403
                );
            }
    
            auth()->setUser($user);
            
            return $next($request);
            // hash ends
//            return response()->json(new ErrorResponse(Response::$statusTexts[401], 401));
        }
        
        if ($user->isBlocked) {
            return response()->json(new ErrorResponse("User is blocked", 'BLOCKED'), 403);
        }
        
        return $next($request);
    }
}
