<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ServerApiController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Client Servers API
Route::get('/clients/{client}/servers', [ServerApiController::class, 'getClientServers']);

// Fetch Servers from Client's Domain
Route::post('/fetch-servers', [ServerApiController::class, 'fetchServersFromClientDomain']);

// Lines API Endpoints
Route::get('/lines', [App\Http\Controllers\LinesController::class, 'getLines']);
Route::get('/lines/client/{clientId}/lines', [App\Http\Controllers\LinesController::class, 'getClientLines']);
Route::get('/lines/client/{clientId}/stored-lines', [App\Http\Controllers\LinesController::class, 'getStoredLines']);
Route::post('/lines/client/{clientId}/fetch-store', [App\Http\Controllers\LinesController::class, 'fetchAndStoreLines']);
Route::get('/lines/line/{lineId}/client/{clientId}/vps', [App\Http\Controllers\LinesController::class, 'getLineVps']);
Route::get('/lines/test', [App\Http\Controllers\LinesController::class, 'test']);

// Line Status Check API
Route::get('/check-line-status', [App\Http\Controllers\LinesController::class, 'checkLineStatus']);

// VPS Line Management API
Route::get('/client/{clientId}/lines', [App\Http\Controllers\VpsController::class, 'getClientLines']);
Route::post('/assign-line', [App\Http\Controllers\VpsController::class, 'assignLineToVps']);
Route::post('/unassign-line', [App\Http\Controllers\VpsController::class, 'unassignLineFromVps']);

Route::get('/get-servers', [App\Http\Controllers\Api\ServerApiController::class, 'getServers']);