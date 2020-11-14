<?php

namespace App\Http\Middleware;

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

    public function __construct(Interfaces\IUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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
        $hash = $request->query('hash');
        if(is_null($hash))
        {
            http_response_code(401);
            return;
        }

        $user = self::$userRepository->getUser($hash);
        if(is_null($user))
        {
            http_response_code(403);
            return json_encode([
                "message" => "User with specified hash was not found."
            ]);
        }

        $request->request->add([
            'userId' => $user->userId,
            'roleId' => $user->roleId
        ]);

        return $next($request);
    }
}
