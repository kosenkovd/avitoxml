<?php

use Illuminate\Http\Request;
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
    'tables.generators' => C\GeneratorController::class
]);

Route::get('/users/myAccount', [C\UserController::class, 'myAccount']);
Route::get('/tables/{tableId}/yandexFile', [C\FileWrapperController::class, 'yandexFile'])->withoutMiddleware([\Illuminate\Routing\Middleware\ThrottleRequests::class]);
