<?php


namespace App\Http\Controllers;

use App\DTOs\ErrorResponse;
use App\DTOs\UserDTO;
use App\Enums\Roles;
use App\Mappers\UserDTOMapper;
use App\Models\User;
use App\Repositories\Interfaces\IUserRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use JsonMapper;
use Ramsey\Uuid\Guid\Guid;

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
    
    private JsonMapper $jsonMapper;

	/**
	 * @var User authenticated user through query hash
	 */
	private User $currentUser;

	public function __construct(
        IUserRepository $userRepository,
        JsonMapper $jsonMapper
    )
    {
        $this->userRepository = $userRepository;
        $this->jsonMapper = $jsonMapper;
        $this->roles = new Roles();

        $this->currentUser = request()->input("currentUser");
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
        return response()->json(UserDTOMapper::mapModelToDTO($this->currentUser), 200);
    }
    
    /**
     * GET /users
     *
     * Get all users.
     *
     * @param $request Request request.
     * @return JsonResponse current users.
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        if ($this->currentUser->getRoleId() !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        $users = $this->userRepository->get();
        
        $usersDTOs = array_map(function (User $user) {
            return UserDTOMapper::mapModelToDTO($user);
        },
        $users);
        
        return response()->json($usersDTOs, 200);
    }

	/**
	 * Post /users
	 *
	 * Create new User
	 *
	 * @param Request $request
	 * @var int $count
	 * @return JsonResponse <User[]>
	 */
    public function store(Request $request): JsonResponse
	{
	    // work in progress
        die();
        
		if ($this->currentUser->getRoleId() === $this->roles->Admin) {
			return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
		}

		$count = $request->query('count') ? (int)$request->query('count') : 1;
		$users = [];
		for ($i = 1; $i < $count; $i++) {
			$apiKey = md5(Guid::uuid4()->toString());

			$user = new User(
				null,
				$this->roles->Customer,
				time(),
				null,
				null,
				false,
				$apiKey,
				null,
				null
			);

			try {
				$this->userRepository->insert($user);
				$createdUser = $this->userRepository->getUserByApiKey($apiKey);
				$users[] = UserDTOMapper::mapModelToDTO($createdUser);
			} catch (Exception $exception) {
				Log::channel('api')->error("Error on inserting ".User::class.PHP_EOL.$exception->getMessage());
			}
		}

		return response()->json($users, 201);
	}

	/**
	 * Put /users/{$id}
	 *
	 * @param Request $request
	 * @param         $id
	 * @return JsonResponse
	 * @throws Exception
	 */
    public function update(Request $request, $id): JsonResponse
    {
        if (!(($this->currentUser->getUserId() === (int)$id) ||
            ($this->currentUser->getRoleId() === $this->roles->Admin)))
        {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
    
        try {
            $userDTO = $this->jsonMapper->map($request->json(), new UserDTO());
        } catch (Exception $e) {
            return response()->json(new ErrorResponse(Response::$statusTexts[400]), 400);
        }
    
        $user = UserDTOMapper::mapDTOToModel($userDTO);

		// TODO disable all tables and generators for user if isBlocked
        
        $this->userRepository->update($user);
        return response()->json(null, 200);
    }

	/**
	 * Put /users/{$id}/token
	 *
	 * @param Request $request
	 * @param         $id
	 * @return JsonResponse
	 * @throws Exception
	 */
    public function refreshToken(Request $request, $id): JsonResponse
    {
        if ($this->currentUser->getRoleId() !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        $newApiKey = md5(Guid::uuid4()->toString());
        
        $this->userRepository->updateApiKey($id, $newApiKey);
        return response()->json($newApiKey, 200);
    }
}
