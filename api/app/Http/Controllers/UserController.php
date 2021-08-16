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
use App\Models\Invitation;
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
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_Permission;
use Google_Service_Sheets;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
            return "https://lk.agishev-autoz.ru/tables?hash=".$user->apiKey;
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
    
        Log::channel('apilog')->info('PUT /users/'.$id.' - user '.$currentUser->id);
        
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
        
        if ($currentUser->roleId !== $this->roles->Admin) {
            $attributes = $request->only([
                'phoneNumber',
                'socialNetworkUrl',
                'name'
            ]);
        } else {
            $attributes = $request->only([
                'phoneNumber',
                'socialNetworkUrl',
                'isBlocked',
                'apiKey',
                'notes',
                'name'
            ]);
        }
        
        $user->update($attributes);
        
        if (
            ($currentUser->roleId === $this->roles->Admin) &&
            ((bool)$request->input('isBlocked') === true)
        ) {
            $user->tables->each(function (TableLaravel $table) {
                $table->generators->each(function (GeneratorLaravel $generator) {
                    $generator->lastGeneration = $this->xmlGenerationService
                        ->getEmptyGeneratedXML($generator->targetPlatform);
                    $generator->update();
                });
            });
        }
        
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
    
        Log::channel('apilog')->info('PUT /users/'.$id.'/token - user '.$currentUser->id);
        
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
     * Put /users/update
     * Update user to Login Pass
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function updateToLoginPass(Request $request, int $id): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
    
        Log::channel('apilog')->info('PUT /users/update - user '.$currentUser->id);
        
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:100', 'unique:avitoxml_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'invitation' => 'string|nullable'
        ]);
        
        if (
            ($currentUser->roleId !== $this->roles->Admin) &&
            ($currentUser->id !== $id)
        ) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[403], 'Not Admin'),
                403
            );
        }
    
        /** @var UserLaravel|null $user */
        $user = UserLaravel::query()->find($id);
        if (is_null($user)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        if ($user->password && $user->email) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[403], 'Already updated to Login Pass'),
                403
            );
        }
    
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
    
        $invitationHash = $request->input('invitation');
        if (!is_null($invitationHash)) {
            /** @var Invitation|null $invitation */
            $invitation = Invitation::query()->where('hash', $invitationHash)->first();
            if (is_null($invitation)) {
                return response()->json(
                    new ErrorResponse(Response::$statusTexts[404], 'Invalid invitation hash.'),
                    404
                );
            }
        
            $user->masterInvitationId = $invitation->id;
        }
        
        $user->save();
    
        event(new Registered($user));
        
        return response()->json(null, 200);
    }
    
    public function test(Request $request)
    {
//        if ($request->get('code')) {
//
//            $res = Http::post('https://oauth2.googleapis.com/token', [
//                'code' => $request->get('code'),
//                'client_id' => "126284738006-c7evde41fmje87q6i3j64kgfn2bpj499.apps.googleusercontent.com",
//                'client_secret' => "AcQWTzVI3qHqgBF5aKDJ3k30",
//                'grant_type' => 'authorization_code',
//                'redirect_uri' => 'https://api.agishev-autoz.ru/api/test'
//            ]);
//
//            echo(json_encode($res->json()));
//            file_put_contents(__dir__. '/../../Configuration/test'.time().'.json', json_encode($res->json()));
//            die;
//
////        $client = new Google_Client();
////        $client->setAuthConfig(__dir__. '/../../Configuration/OAuth.json');
////        $access_token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
////        var_dump($access_token);
////
////        $client->setAccessToken($access_token);
////        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
////        $client->setAccessType('offline');
//
////        setPermissions('1-jeOZluHoKBIdvM21ViX2z6tmdyUA4vFUqrboT5nJ1o', $client);
//
//
//        }
//
//        dd($request->json());
    }
}
