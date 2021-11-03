<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\ErrorResponse;
use App\Models\UserLaravel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class LoginController extends Controller
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
            'grant_type' => ['string', 'nullable'],
            'client_secret' => ['requiredIf:grant_type,client_credentials', 'nullable'],
            'email' => [Rule::requiredIf(is_null($request->get('grant_type'))), 'string'],
            'password' => [Rule::requiredIf(is_null($request->get('grant_type'))), 'string']
        ]);
        
        if ($request->get('grant_type') === 'client_credentials') {
            $client = DB::table('oauth_clients')
                ->where('secret', $request->get('client_secret'))
                ->first();
            if (is_null($client)) {
                return response()->json(
                    new ErrorResponse(Response::$statusTexts[403], 'User not found'),
                    403
                );
            }
    
            $data = [
                'grant_type' => 'client_credentials',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
            ];
            $response = Http::post('https://api.agishev-autoz.ru/oauth/token', $data);
    
            if ($response->status() !== 200) {
                return response()->json(
                    new ErrorResponse(Response::$statusTexts[403], 'Invalid credentials'),
                    403
                );
            }
    
            $auth = $response->json();
            $auth['expires_on'] = Carbon::now()->addSeconds($auth['expires_in'])->getTimestamp();
            unset($auth['expires_in']);
    
            return response()->json($auth);
        }
        
        $credentials = $request->only(['email', 'password']);
        
        auth('web')->attempt($credentials);
        /** @var UserLaravel|null $user */
        $user = auth('web')->user();
        if (is_null($user)) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[403], 'User with specified login / password was not found'),
                403
            );
        }

        $client = DB::table('oauth_clients')
            ->where('password_client', true)
            ->first();
        
        $data = [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => $credentials['email'],
            'password' => $credentials['password'],
            'scope' => '',
        ];
        $response = Http::post('https://api.agishev-autoz.ru/oauth/token', $data);
        
        if ($response->status() !== 200) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[403], 'Invalid credentials'),
                403
            );
        }
    
        $auth = $response->json();
        $auth['expires_on'] = Carbon::now()->addSeconds($auth['expires_in'])->getTimestamp();
        unset($auth['expires_in']);
        
        return response()->json($auth);
    }
}
