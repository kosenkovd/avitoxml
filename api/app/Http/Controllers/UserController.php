<?php


namespace App\Http\Controllers;

use App\DTOs\UserDTO;
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
        $currentUser = $request->input("currentUser");
        return response()->json(UserDTOMapper::mapModelToUserDTO($currentUser), 200);
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
        /** @var User $currentUser */
        $currentUser = $request->input("currentUser");
        
        if ($currentUser->getRoleId() !== $this->roles->Admin) {
            return response()->json(null, 403);
        }
        
        $users = $this->userRepository->getUsers();
        
        $usersDTOs = array_map(function (User $user) {
            return UserDTOMapper::mapModelToUserDTO($user);
        },
        $users);
        
        return response()->json($usersDTOs, 200);
    }
    
    public function put(Request $request, $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->input("currentUser");
        
        if (
            ($currentUser->getUserId() !== $id)
            ||
            ($currentUser->getRoleId() !== $this->roles->Admin)
        ) {
            return response()->json(null, 403);
        }
    
        $input = $request->only([
            'roleId',
            'dateCreated',
            'phoneNumber',
            'socialNetworkUrl',
            'isBlocked',
            'token',
            'notes',
            'name',
        ]);
    
        $userDTO = new UserDTO(
            $id,
            $input['roleId'],
            $input['dateCreated'],
            $input['phoneNumber'],
            $input['socialNetworkUrl'],
            $input['isBlocked'],
            $input['token'],
            $input['notes'],
            $input['name'],
        );
        
        $user = UserDTOMapper::mapUserDTOToModel($userDTO);
    
        $result = $this->userRepository->updateUser($id, $user);
        if ($result) {
            return response()->json($result, 200);
        } else {
            return response()->json($result, 500);
        }
    }
}
