<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\ErrorResponse;
use App\Models\UserLaravel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RefreshController extends Controller
{
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
            'refresh_token' => ['required', 'string'],
        ]);
        
        $client = DB::table('oauth_clients')
            ->where('password_client', true)
            ->first();
        
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => ''
        ];
        $response = Http::post('https://api.agishev-autoz.ru/oauth/token', $data);
        
        if ($response->status() !== 200) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[403],
                'The refresh token is invalid'
                ), 403);
        }
    
        $auth = $response->json();
        $auth['expires_on'] = Carbon::now()->addSeconds($auth['expires_in'])->getTimestamp();
        unset($auth['expires_in']);
        
        return response()->json($auth);
    }
}
