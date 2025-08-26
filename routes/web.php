<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServersController;
use App\Http\Controllers\VpsController;
use App\Http\Controllers\LinesController;

// Dashboard Route
Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

// Clients Routes
Route::resource('clients', ClientController::class);

// Servers Routes
Route::resource('servers', ServersController::class);

// VPS Routes
Route::resource('vps', VpsController::class);

// Lines Routes
Route::get('/lines', [LinesController::class, 'index'])->name('lines.index');
Route::get('/lines/client/{clientId}/lines', [LinesController::class, 'getClientLines'])->name('lines.client.lines');
Route::get('/lines/line/{lineId}/client/{clientId}/vps', [LinesController::class, 'getLineVps'])->name('lines.line.vps');
Route::get('/lines/test', [LinesController::class, 'test'])->name('lines.test');

// Load Balancers Routes
Route::get('/load-balancers', function () {
    return view('load-balancers.index');
})->name('load-balancers.index');

// Settings Route
Route::get('/settings', function () {
    return view('settings');
})->name('settings');