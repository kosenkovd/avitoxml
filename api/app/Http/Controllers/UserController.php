<?php


namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\DTOs\ErrorResponse;
use App\DTOs\UserDTO;
use App\Enums\Roles;
use App\Helpers\LinkHelper;
use App\Mappers\UserDTOMapper;
use App\Models\Generator;
use App\Models\Table;
use App\Models\User;
use App\Repositories\GeneratorRepository;
use App\Repositories\Interfaces\IGeneratorRepository;
use App\Repositories\Interfaces\ITableRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Repositories\TableRepository;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\MailService;
use App\Services\SpreadsheetClientService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
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
     * @var ISpreadsheetClientService
     */
    private ISpreadsheetClientService $spreadsheetClientService;
    /**
     * @var ITableRepository
     */
    private ITableRepository $tableRepository;
    /**
     * @var MailService
     */
    private MailService $mailService;
    /**
     * @var IGeneratorRepository
     */
    private IGeneratorRepository $generatorRepository;
    /**
     * @var SheetNames
     */
    private SheetNames $sheetNamesConfig;
    
    public function __construct(
        IUserRepository $userRepository,
        ITableRepository $tableRepository,
        JsonMapper $jsonMapper
    )
    {
        $this->userRepository = $userRepository;
        $this->tableRepository = $tableRepository;
        $this->jsonMapper = $jsonMapper;
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
        return response()->json(UserDTOMapper::mapModelToDTO($currentUser), 200);
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
        /** @var User $currentUser */
        $currentUser = $request->input("currentUser");
        
        if ($currentUser->getRoleId() !== $this->roles->Admin) {
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
        /** @var User $currentUser */
        $currentUser = $request->input("currentUser");
        
		if ($currentUser->getRoleId() !== $this->roles->Admin) {
			return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
		}

		$count = $request->query('count') ? (int)$request->query('count') : 1;
		if ($count > 20) {
            return response()->json(new ErrorResponse(''), 400);
        }
		
		$users = [];
		for ($i = 0; $i < $count; $i++) {
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

            $this->userRepository->insert($user);
            $createdUser = $this->userRepository->getUserByApiKey($apiKey);
            $users[] = UserDTOMapper::mapModelToDTO($createdUser);
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
        /** @var User $currentUser */
        $currentUser = $request->input("currentUser");
        
        if (!(($currentUser->getUserId() === (int)$id) ||
            ($currentUser->getRoleId() === $this->roles->Admin)))
        {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
    
        $existingUser = $this->userRepository->getUserById((int)$id);
        if (is_null($existingUser)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        try {
            $userDTO = $this->jsonMapper->map($request->json(), new UserDTO());
        } catch (Exception $e) {
            return response()->json(new ErrorResponse(Response::$statusTexts[400]), 400);
        }
    
        $user = UserDTOMapper::mapDTOToModel($userDTO);
        $this->userRepository->update($user);
        
        if ((int)$request->query->get('recentlyCreated') === 1) {
            $existingUserTables = $this->tableRepository->getTables($user->getUserId());
            if (count($existingUserTables) === 0) {
                $table = $this->createTable($user->getUserId());
            }
        }
        
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
        /** @var User $currentUser */
        $currentUser = $request->input("currentUser");
        
        if ($currentUser->getRoleId() !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
    
        $user = $this->userRepository->getUserById((int)$id);
        if (is_null($user)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
    
        if ($user->getRoleId() === $this->roles->Admin) {
            return response()->json($user->getApiKey(), 200);
        }
        
        $newApiKey = md5(Guid::uuid4()->toString());
        $user->setApiKey($newApiKey);
        $this->userRepository->update($user);
        
        return response()->json($newApiKey, 200);
    }
    
    /**
     * Create table for specified user
     *
     * @param int $userId
     * @return Table
     */
    private function createTable(int $userId): Table
    {
        // TODO make service for that
        
        $this->spreadsheetClientService = new SpreadsheetClientService();
        $this->sheetNamesConfig = new SheetNames();
        $this->generatorRepository = new GeneratorRepository();
        $this->mailService = new MailService();
        
        $googleTableId = $this->spreadsheetClientService->copyTable();
    
        $dateExpired = Carbon::now()->addDays(3)->getTimestamp();
        
        $table = new Table(
            null,
            $userId,
            $googleTableId,
            null,
            null,
            $dateExpired,
            false,
            null,
            null,
            Guid::uuid4()->toString(),
            0,
            []
        );
        
        $newTableId = $this->tableRepository->insert($table);
        
        $targetsToAdd = [
            [
                "cell" => "C3",
                "target" => $this->sheetNamesConfig->getAvito()
            ],
            [
                "cell" => "C4",
                "target" => $this->sheetNamesConfig->getYoula()
            ],
            [
                "cell" => "C5",
                "target" => $this->sheetNamesConfig->getYandex()
            ]
        ];
        
        $table->setTableId($newTableId);
        
        foreach ($targetsToAdd as $target) {
            $generator = new Generator(
                null,
                $newTableId,
                Guid::uuid4()->toString(),
                0,
                $target["target"],
                30
            );
            
            $newGeneratorId = $this->generatorRepository->insert($generator);
            
            $table->addGenerator($generator->setGeneratorId($newGeneratorId));
            
            $this->spreadsheetClientService->updateCellContent(
                $googleTableId,
                $this->sheetNamesConfig->getInformation(),
                $target["cell"],
                LinkHelper::getXmlGeneratorLink($table->getTableGuid(), $generator->getGeneratorGuid())
            );
        }
        
        $this->mailService->sendEmailWithTableData($table);
        
        return $table;
    }
}
