<?php

namespace App\Http\Middleware;

use App\DTOs\ErrorResponse;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param Request     $request
     * @param \Closure    $next
     * @param string|null $redirectToRoute
     */
    public function handle(Request $request, Closure $next, string $redirectToRoute = null)
    {
        if (!$request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
                !$request->user()->hasVerifiedEmail())) {
            return $request->expectsJson()
                ? response()->json(new ErrorResponse('Your email address is not verified.', 403))
                : response('Your email address is not verified.');
        }
        
        return $next($request);
    }
}
