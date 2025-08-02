<?php

use App\Http\Controllers\Api\TerminalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', function (Request $request) {
        return $request->user();
    });

    // Terminal API routes
    Route::prefix('terminal')->group(function () {
        Route::post('/connect', [TerminalController::class, 'connect']);
        Route::post('/execute', [TerminalController::class, 'execute']);
        Route::post('/disconnect', [TerminalController::class, 'disconnect']);
    });
});
