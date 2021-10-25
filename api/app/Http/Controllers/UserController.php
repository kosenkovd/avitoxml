<?php


namespace App\Http\Controllers;

use App\DTOs\ErrorResponse;
use App\Enums\Roles;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\GeneratorLaravel;
use App\Models\Invitation;
use App\Models\TableLaravel;
use App\Models\UserLaravel;
use App\Services\Interfaces\IXmlGenerationService;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\ClientRepository;
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
    private static int $botClientId = 3;
    
    private Roles $roles;
    
    /**
     * @var IXmlGenerationService
     */
    private IXmlGenerationService $xmlGenerationService;
    
    public function __construct(
        IXmlGenerationService $xmlGenerationService
    )
    {
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
        return response()->json(new UserResource(auth()->user()));
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
        
        switch ($currentUser->roleId) {
            case $this->roles->Admin:
            case $this->roles->Service:
                return response()->json(new UserCollection(UserLaravel::with('wallet')->get()));
            default:
                return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
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
        
        return response()->json(null);
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
            return response()->json($user->apiKey);
        }
        
        $newApiKey = md5(Guid::uuid4()->toString());
        $user->apiKey = $newApiKey;
        $user->update();
        
        return response()->json($newApiKey);
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
        
        return response()->json(null);
    }
    
    /**
     * Post /users/accessToken
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function storeAccessClient(Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
    
        switch ($currentUser->roleId) {
            case $this->roles->Admin:
            case $this->roles->Service:
                return response()->json('');
            default:
                $client = DB::table('oauth_clients')
                    ->where('user_id', $currentUser->id)
                    ->where('personal_access_client', true)
                    ->first();
                if (!is_null($client)) {
                    return response()->json(
                        new ErrorResponse(Response::$statusTexts[409],
                            'Already has Access Client'
                        ), 409);
                }
            
                /** @var ClientRepository $clientRepository */
                $clientRepository = resolve(ClientRepository::class);
                $newClient = $clientRepository->create(
                    $currentUser->id,
                    'Laravel Personal Access Client',
                    'http://localhost',
                    'users',
                    true,
                    false,
                    true
                );
            
                return response()->json($newClient ? $newClient->getAttribute('secret') : '');
        }
    }
    
    public function getAccessClient(Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
    
        switch ($currentUser->roleId) {
            case $this->roles->Admin:
            case $this->roles->Service:
                $client = DB::table('oauth_clients')
                    ->where('user_id', self::$botClientId)
                    ->where('personal_access_client', true)
                    ->first();
            
                return response()->json($client ? $client->secret : '');
            default:
                $client = DB::table('oauth_clients')
                    ->where('user_id', $currentUser->id)
                    ->where('personal_access_client', true)
                    ->first();
            
                return response()->json($client ? $client->secret : '');
        }
    }
    
    public function test(Request $request)
    {
    }
}
