<?php

namespace App\Http\Middleware;

use App\Enums\Roles;
use Closure;
use App\Http\Models;
use App\Repositories\Interfaces;
use \Illuminate\Http\Request;

class Authenticate
{
    /**
     * @var Interfaces\IUserRepository Models\User repository.
     */
    private Interfaces\IUserRepository $userRepository;
    
    /**
     * @var Roles
     */
    private Roles $roles;

    private static array $anonymousAllowed = [
        "App\Http\Controllers\GeneratorController@show",
        "App\Http\Controllers\FileWrapperController@yandexFile"
    ];

    public function __construct(Interfaces\IUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->roles = new Roles();
    }

    /**
     * Authenticate user.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $action = $request->route()->getAction()["controller"];
        if(in_array($action, self::$anonymousAllowed))
        {
            return $next($request);
        }

        $hash = $request->query('hash');
        if(is_null($hash))
        {
            return response(null, 401);
        }

        $user = $this->userRepository->getUserByApiKey($hash);
        if(is_null($user))
        {
            http_response_code(403);
            return response()->json([
                "message" => "User with specified hash was not found."
            ], 403);
        }
    
        if(($user->getRoleId() !== $this->roles->Admin) && $user->isBlocked())
        {
            http_response_code(403);
            return response()->json([
                "message" => "User is blocked."
            ], 403);
        }

        $request->request->add([
            'currentUser' => $user
        ]);

        return $next($request);
    }
}
