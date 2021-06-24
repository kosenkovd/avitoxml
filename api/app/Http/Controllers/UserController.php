<?php


namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Console\Jobs\FillAvitoReportJob;
use App\DTOs\ErrorResponse;
use App\DTOs\UserDTO;
use App\Enums\Roles;
use App\Helpers\LinkHelper;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Mappers\UserDTOMapper;
use App\Models\Generator;
use App\Models\GeneratorLaravel;
use App\Models\Table;
use App\Models\TableLaravel;
use App\Models\User;
use App\Models\UserLaravel;
use App\Repositories\GeneratorRepository;
use App\Repositories\Interfaces\IGeneratorRepository;
use App\Repositories\Interfaces\ITableRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Repositories\TableRepository;
use App\Services\AvitoService;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IXmlGenerationService;
use App\Services\MailService;
use App\Services\SpreadsheetClientService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
class UserController extends BaseController {
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
    /**
     * @var IXmlGenerationService
     */
    private IXmlGenerationService $xmlGenerationService;
    
    public function __construct(
        IUserRepository $userRepository,
        ITableRepository $tableRepository,
        JsonMapper $jsonMapper,
        IXmlGenerationService $xmlGenerationService
    )
    {
        $this->userRepository = $userRepository;
        $this->tableRepository = $tableRepository;
        $this->jsonMapper = $jsonMapper;
        $this->roles = new Roles();
        $this->xmlGenerationService = $xmlGenerationService;
    }
    
    /**
     * GET /myAccount
     *
     * Get current user info.
     *
     * @return JsonResponse current user information.
     */
    public function myAccount(): JsonResponse
    {
        return response()->json(new UserResource(auth()->user()), 200);
    }
    
    /**
     * GET /users
     *
     * Get all users.
     *
     * @param $request Request request.
     *
     * @return JsonResponse current users.
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
        
        if ($currentUser->roleId !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        return response()->json(new UserCollection(UserLaravel::all()), 200);
    }
    
    /**
     * Post /users
     *
     * Create new User
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
        
        if ($currentUser->roleId !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        $count = $request->query('count') ? (int)$request->query('count') : 1;
        if ($count > 500) {
            return response()->json(new ErrorResponse('Count is to high'), 400);
        }
        
        $users = new Collection();
        for ($i = 0; $i < $count; $i++) {
            /** @var UserLaravel $user */
            $user = UserLaravel::query()->make();
            $user->roleId = $this->roles->Customer;
            $user->apiKey = md5(Guid::uuid4()->toString());
            $user->isBlocked = false;
            
            $user->save();
            
            $users->add($user);
        }
        
        $userLinks = $users->map(function (UserLaravel $user) {
            return "http://lk.agishev-autoz.ru/tables?hash=".$user->apiKey;
        })->implode(PHP_EOL);
        mail('maksimagishev@mail.ru', 'Новые пользователи', $userLinks);
        
        return response()->json(new UserCollection($users), 201);
    }
    
    /**
     * Put /users/{$id}
     *
     * @param Request $request
     * @param         $id
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function update(Request $request, $id): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
        
        if (!(($currentUser->id === (int)$id) ||
            ($currentUser->roleId === $this->roles->Admin))) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        /** @var UserLaravel|null $user */
        $user = UserLaravel::query()->find((int)$id);
        if (is_null($user)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        $request->validate([
            'phoneNumber' => 'string|nullable',
            'socialNetworkUrl' => 'string|nullable',
            'isBlocked' => 'boolean|nullable',
            'apiKey' => 'string|nullable',
            'notes' => 'string|nullable',
            'name' => 'string|nullable',
        ]);
        
        $user->update($request->only([
            'phoneNumber',
            'socialNetworkUrl',
            'isBlocked',
            'apiKey',
            'notes',
            'name'
        ]));
        
        if ((bool)$request->input('isBlocked') === true) {
            $user->tables->each(function (TableLaravel $table) {
                $table->generators->each(function (GeneratorLaravel $generator) {
                    $generator->lastGeneration = $this->xmlGenerationService
                        ->getEmptyGeneratedXML($generator->targetPlatform);
                    $generator->update();
                });
            });
        }
        
//        if ((int)$request->input('recentlyCreated') === 1) {
//            $userTables = $user->tables;
//            if ($userTables->isEmpty()) {
//                $table = $this->createTable($user->id);
//            }
//        }
        
        return response()->json(null, 200);
    }
    
    /**
     * Put /users/{$id}/token
     *
     * @param Request $request
     * @param         $id
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function refreshToken(Request $request, $id): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
        
        if ($currentUser->roleId !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        /** @var UserLaravel|null $user */
        $user = UserLaravel::query()->find((int)$id);
        if (is_null($user)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        if ($user->roleId === $this->roles->Admin) {
            return response()->json($user->apiKey, 200);
        }
        
        $newApiKey = md5(Guid::uuid4()->toString());
        $user->apiKey = $newApiKey;
        $user->update();
        
        return response()->json($newApiKey, 200);
    }
    
    /**
     * Create table for specified user
     *
     * @param int $userId
     *
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
    
    public function test()
    {
        return response('it works');
//        /** @var TableLaravel $table */
//        $table = TableLaravel::query()
//            ->where('googleSheetId', '1GoQCTGscJOYvsnllJwGFfxwytEQYxvC3kKGjYWKaQJ8')
//            ->first();
//
//        (new FillAvitoReportJob(
//            new SpreadsheetClientService(),
//            new AvitoService(),
//            new SheetNames()
//        ))->start($table);
    }
}
