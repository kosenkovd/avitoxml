<?php


namespace App\Http\Controllers;

use App\Mappers\UserDTOMapper;
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
        return response()->json(UserDTOMapper::mapUser($user), 200);
    }
}
