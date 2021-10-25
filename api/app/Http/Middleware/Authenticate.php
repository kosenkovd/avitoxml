<?php

namespace App\Http\Middleware;

use App\DTOs\ErrorResponse;
use App\Models\UserLaravel;
use Closure;
use Illuminate\Auth\AuthenticationException;
use \Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

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
                // TODO refactor this...
                try {
                    /** @var ResourceServer $server */
                    $server = resolve(\League\OAuth2\Server\ResourceServer::class);
                    /** @var TokenRepository $tokenRepository */
                    $tokenRepository = resolve(TokenRepository::class);

                    $psr = (new PsrHttpFactory(
                        new Psr17Factory,
                        new Psr17Factory,
                        new Psr17Factory,
                        new Psr17Factory
                    ))->createRequest($request);
                    try {
                        $psr = $server->validateAuthenticatedRequest($psr);
                    } catch (OAuthServerException $e) {
                        throw new AuthenticationException;
                    }
                    $tokenId = $psr->getAttribute('oauth_access_token_id');
                    /** @var Client $client */
                    $client = $tokenRepository->find($tokenId)->client;
                    /** @var UserLaravel $user */
                    $user = UserLaravel::query()
                        ->where('id', $client->user_id)
                        ->whereNotNull('email')
                        ->first();
                    auth()->setUser($user);

                    return $next($request);
                } catch (\Exception $exception) {
                    return response()->json(new ErrorResponse(Response::$statusTexts[401]), 401);
                }
            }
            
            /** @var UserLaravel $user */
            $user = UserLaravel::query()
                ->where('apiKey', $hash)
//                ->whereNull('email') // TODO change it when front updated
                ->first();
            if (is_null($user)) {
                return response()->json(
                    new ErrorResponse("User with specified hash was not found"),
                    403
                );
            }
            
//            if ($user->isBlocked) {
//                return response()->json(
//                    new ErrorResponse("User is blocked", 'BLOCKED'),
//                    403
//                );
//            }
    
            auth()->setUser($user);
            
            return $next($request);
            // hash ends
//            return response()->json(new ErrorResponse(Response::$statusTexts[401], 401));
        }
        
//        if ($user->isBlocked) {
//            return response()->json(new ErrorResponse("User is blocked", 'BLOCKED'), 403);
//        }
        
        return $next($request);
    }
}
