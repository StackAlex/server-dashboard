<?php
use Illuminate\Http\Request;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Docker;
use App\Services\SystemMonitorService;

Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/users', [UserController::class, 'index'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/agents', [AgentController::class, 'index']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->post('/terminal', [TerminalController::class, 'execute']);

Route::middleware(['agent.auth'])->group(function () {
    Route::post('/agent/heartbeat', [AgentController::class, 'heartbeat']);
    Route::post('/agent/logs', [AgentController::class, 'storeLogs']);
});

Route::get('/allAgents', [AgentController::class, 'getAll'])->middleware('auth:sanctum');

Route::post('/saveAgent', [AgentController::class, 'saveAgent'])->middleware('auth:sanctum'); 

Route::post('/deleteAgent', [AgentController::class, 'deleteAgent'])->middleware('auth:sanctum'); 

Route::get('/docker/containers', [Docker::class, 'getContainers'])
    ->middleware('auth:sanctum');

Route::get('/dashboard/settings', [SettingsController::class, 'dashboardSettings'])->middleware('auth:sanctum');