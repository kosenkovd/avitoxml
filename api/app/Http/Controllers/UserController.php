<?php


namespace App\Http\Controllers;

use App\Enums\Roles;
use App\Mappers\UserDTOMapper;
use App\Models\User;
use App\Repositories\Interfaces\IUserRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;


/**
 * Class UserController
 *
 * Base route /api/users
 *
 * @package App\Http\Controllers
 */
class UserController extends BaseController
{
    private Roles $roles;
    
    private IUserRepository $userRepository;
    
    public function __construct(
        IUserRepository $userRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->roles = new Roles();
    }
    
    /**
     * GET /myAccount
     *
     * Get current user info.
     *
     * @param $request Request request.
     * @return JsonResponse current user information.
     */
    public function myAccount(Request $request) : JsonResponse
    {
        $user = $request->input("currentUser");
        return response()->json(UserDTOMapper::mapUserInfo($user), 200);
    }
    
    /**
     * GET /users
     *
     * Get current user info.
     *
     * @param $request Request request.
     * @return JsonResponse current users.
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->input("currentUser");
        
        $users = [];
        if ($user->getRoleId() !== $this->roles->Admin) {
            return response()->json(null, 403);
        } else {
            $users = $this->userRepository->getUsers();
        }
        
        $usersDTOs = array_map(function (User $user) {
            return UserDTOMapper::mapUserRow($user);
        },
        $users);
        
        return response()->json($usersDTOs, 200);
    }
}
