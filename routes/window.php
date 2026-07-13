<?php

use App\Http\Controllers\Api\WindowController;
use App\Http\Controllers\Api\ServiceWindowController;
use App\Http\Controllers\Api\OfficerWindowAssignmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/windows', [WindowController::class, 'index']);
    Route::post('/windows', [WindowController::class, 'store']);
    Route::put('/windows/{window}', [WindowController::class, 'update']);
    Route::delete('/windows/{window}', [WindowController::class, 'destroy']);

    Route::get('/service-window/board', [ServiceWindowController::class, 'board']);
    Route::post('/service-window/move', [ServiceWindowController::class, 'move']);
    Route::delete('/service-window/services/{service}', [ServiceWindowController::class, 'unassign']);

    Route::post('/services/{service}/windows', [ServiceWindowController::class, 'assign']);
    Route::get('/services/{service}/windows', [ServiceWindowController::class, 'show']);

    Route::get('/officer-window-assignment/board', [OfficerWindowAssignmentController::class, 'board']);
    Route::post('/officer-window-assignment/assign', [OfficerWindowAssignmentController::class, 'assign']);
    Route::delete('/officer-window-assignment/unassign', [OfficerWindowAssignmentController::class, 'unassign']);
    Route::get('/officer-window-assignment/windows/{window}/officers', [OfficerWindowAssignmentController::class, 'officers']);
});
