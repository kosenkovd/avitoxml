<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\ErrorResponse;
use App\Enums\Roles;
use App\Models\Invitation;
use App\Models\UserLaravel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Guid\Guid;

class RegisterController extends Controller
{
    private Roles $roles;
    
    public function __construct()
    {
        $this->roles = new Roles();
    }
    
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:100', 'unique:avitoxml_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'invitation' => 'string|nullable'
        ]);
        
        /** @var UserLaravel $user */
        $user = UserLaravel::query()->make();
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->roleId = $this->roles->Customer;
        $user->apiKey = md5(Guid::uuid4()->toString()); // TODO delete
        $user->isBlocked = false;
        
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
        
        return response()->json();
    }
}
