<?php


namespace App\Http\Controllers;

use App\DTOs\ErrorResponse;
use App\Http\Resources\InvitationCollection;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\UserLaravel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Ramsey\Uuid\Guid\Guid;

class InvitationsController extends BaseController
{
    /**
     * GET /users/invitations
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        return response()->json(new InvitationCollection($user->invitations));
    }
    
    /**
     * GET /users/invitations/{hash}
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function check(string $hash, Request $request): JsonResponse
    {
        /** @var Invitation|null $invitation */
        $invitation = Invitation::query()->where('hash', $hash)->first();
        if (is_null($invitation)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        return response()->json();
    }
    
    /**
     * POST /users/invitations
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        /** @var Invitation $invitation */
        $invitation = Invitation::query()->make();
        $invitation->userId = $user->id;
        $invitation->hash = Guid::uuid4()->toString();
        
        $invitation->save();
        
        return response()->json(new InvitationResource($invitation), 201);
    }
    
    /**
     * PUT /users/invitations/{hash}
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function update(string $hash, Request $request): JsonResponse
    {
        /** @var UserLaravel $user */
        $user = auth()->user();
        
        /** @var Invitation|null $invitation */
        $invitation = $user->invitations()->where('hash', $hash)->first();
        if (is_null($invitation)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        $request->validate([
            'discount' => 'integer|required'
        ]);
        $discount = $request->input('discount');
        
        if ($discount > $invitation->income) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[400], 'Discount is too high.'),
                400
            );
        }
        
        $invitation->discount = $discount;
        $invitation->save();
        
        return response()->json();
    }
}
