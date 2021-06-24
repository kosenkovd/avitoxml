<?php

namespace App\Http\Middleware;

use App\DTOs\ErrorResponse;
use App\Enums\Roles;
use App\Models\UserLaravel;
use Closure;
use App\Repositories\Interfaces;
use \Illuminate\Http\Request;
use Illuminate\Http\Response;

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
            return response()->json(new ErrorResponse(Response::$statusTexts[401]), 401);
        }

        /** @var UserLaravel $user */
        $user = UserLaravel::query()->where('apiKey', $hash)->first();
        if(is_null($user))
        {
            return response()->json(
                new ErrorResponse("User with specified hash was not found"),
                403
            );
        }
    
        if(
            ($user->roleId !== $this->roles->Admin) && // self ban fix
            $user->isBlocked
        )
        {
            return response()->json(
                new ErrorResponse("User is blocked", 'BLOCKED'),
                403
            );
        }
    
        auth()->login($user);
        
        $user = $this->userRepository->getUserByApiKey($hash);
        $request->request->add([
            'currentUser' => $user,
        ]);

        return $next($request);
    }
}
