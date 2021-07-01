<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers as C;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::apiResources([
    'tables' => C\TableController::class,
    'tables.generators' => C\GeneratorController::class, // show ignore auth middleware in that middleware
    'marketplaces' => C\TableMarketplaceController::class,
]);

Route::put('/tables/{tableGuid}/tokens', [C\TableController::class, 'updateTokens']);

Route::get('/users/myAccount', [C\UserController::class, 'myAccount']);
Route::get('/users/', [C\UserController::class, 'index']);
Route::post('/users/', [C\UserController::class, 'store']);
Route::put('/users/{id}', [C\UserController::class, 'update']);
Route::put('/users/{id}/token', [C\UserController::class, 'refreshToken']);
Route::put('/users/{id}/update', [C\UserController::class, 'updateToLoginPass']);

Route::put('/test', [C\UserController::class, 'test'])
    ->withoutMiddleware('auth');

Route::post('/auth/register', C\Auth\RegisterController::class)
    ->withoutMiddleware('auth');
Route::post('/auth/login', C\Auth\LoginController::class)
    ->withoutMiddleware('auth');
Route::post('/auth/refresh', C\Auth\RefreshController::class)
    ->withoutMiddleware('auth');

Route::post('/auth/passwordForgot', [C\Auth\ResetController::class, 'forgot'])
    ->name('password.email')
    ->withoutMiddleware('auth');
Route::post('/auth/passwordReset', [C\Auth\ResetController::class, 'reset'])
    ->name('password.reset')
    ->withoutMiddleware('auth');
Route::get('/email/verify/{id}/{hash}', [C\Auth\VerifyEmailController::class, 'verify'])
    ->withoutMiddleware('auth')
    ->name('verification.verify');
Route::post('/email/resendVerify', [C\Auth\VerifyEmailController::class, 'resend'])
    ->withoutMiddleware('auth');

