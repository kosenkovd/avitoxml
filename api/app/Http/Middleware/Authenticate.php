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

        $request->request->add([
            'currentUser' => $user
        ]);

        return $next($request);
    }
}
