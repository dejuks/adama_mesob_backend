<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\ServiceProviderController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\SubcityController;
use App\Http\Controllers\Api\WoredaController;
use App\Http\Controllers\Api\CustomerController;

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\UserActivationRequestController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/profile/update', [UserController::class, 'updateProfile']);
    Route::post('/profile/change-password', [UserController::class, 'changeOwnPassword']);
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/roles-lite', [UserController::class, 'rolesLite']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users/{id}/roles', [UserController::class, 'assignRole']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::patch('/users/{id}/toggle', [UserController::class, 'toggle']);
    Route::post('/users/{id}/change-password', [UserController::class, 'changePassword']);
    Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);

    Route::get('/user-activation-requests', [UserActivationRequestController::class, 'index']);
    Route::post('/user-activation-requests/bulk-verify', [UserActivationRequestController::class, 'bulkVerify']);
    Route::post('/user-activation-requests/bulk-approve', [UserActivationRequestController::class, 'bulkApprove']);
    Route::post('/user-activation-requests/{activationRequest}/verify', [UserActivationRequestController::class, 'verify']);
    Route::post('/user-activation-requests/{activationRequest}/approve', [UserActivationRequestController::class, 'approve']);
    Route::post('/user-activation-requests/{activationRequest}/reject', [UserActivationRequestController::class, 'reject']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::get('/roles/{id}/permissions', [RoleController::class, 'rolePermissions']);
    Route::post('/roles/{id}/permissions', [RoleController::class, 'assignPermissions']);

    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::put('/permissions/{id}', [PermissionController::class, 'update']);
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);

    Route::get('/offices', [OfficeController::class, 'index']);
    Route::post('/offices', [OfficeController::class, 'store']);
    Route::get('/offices/{office}', [OfficeController::class, 'show']);
    Route::put('/offices/{office}', [OfficeController::class, 'update']);
    Route::delete('/offices/{office}', [OfficeController::class, 'destroy']);



    Route::get('/service-providers', [ServiceProviderController::class, 'index']);
    Route::post('/service-providers', [ServiceProviderController::class, 'store']);
    Route::get('/service-providers/{serviceProvider}', [ServiceProviderController::class, 'show']);
    Route::put('/service-providers/{serviceProvider}', [ServiceProviderController::class, 'update']);
    Route::delete('/service-providers/{serviceProvider}', [ServiceProviderController::class, 'destroy']);

    Route::apiResource('cities', CityController::class);
    Route::apiResource('subcities', SubcityController::class);
    Route::apiResource('woredas', WoredaController::class);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::post('/audit-logs', [AuditLogController::class, 'store']);
    Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);
    Route::put('/audit-logs/{id}', [AuditLogController::class, 'update']);
    Route::delete('/audit-logs/{id}', [AuditLogController::class, 'destroy']);
});

Route::post('/sms/send-phone', [SmsController::class, 'sendPhone']);
Route::post('/sms/send-otp', [SmsController::class, 'sendOtp']);
Route::post('/sms/send-bulk', [SmsController::class, 'sendBulk']);

Route::get(
    '/feedback/{token}',
    [FeedbackController::class, 'show']
);

Route::post(
    '/feedback/{token}',
    [FeedbackController::class, 'store']
);

Route::get('/customers', [CustomerController::class, 'customerlist']);
  Route::get('/customers/{id}',[ CustomerController::class,'show']);
