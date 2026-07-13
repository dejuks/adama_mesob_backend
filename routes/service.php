<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ServiceFormController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceCriterionController;
use App\Http\Controllers\Api\UserServiceAssignmentController;
use App\Http\Controllers\Api\ServiceFormSectionController;
use App\Http\Controllers\Api\ServiceFormFieldController;
use App\Http\Controllers\SmsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services-dropdown', [ServiceController::class, 'allServices']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

    Route::apiResource('service-criteria', ServiceCriterionController::class);

    Route::apiResource('service-forms', ServiceFormController::class);

    Route::get('/user-services/board', [UserServiceAssignmentController::class, 'board']);
    Route::post('/user-services/assign', [UserServiceAssignmentController::class, 'assignAdvanced']);
    Route::delete('/user-services/unassign', [UserServiceAssignmentController::class, 'unassignAdvanced']);

    Route::get('/users/{user}/services', [UserServiceAssignmentController::class, 'show']);
    Route::post('/users/{user}/services', [UserServiceAssignmentController::class, 'assign']);
    Route::delete('/users/{user}/services/{serviceId}', [UserServiceAssignmentController::class, 'remove']);
    Route::patch('/users/{user}/services/{serviceId}/toggle', [UserServiceAssignmentController::class, 'toggle']);

    Route::get('/service-officers', [UserServiceAssignmentController::class, 'officers']);

    Route::get('/service-form-sections', [ServiceFormSectionController::class, 'index']);
    Route::post('/service-form-sections', [ServiceFormSectionController::class, 'store']);
    Route::get('/service-form-sections/{serviceFormSection}', [ServiceFormSectionController::class, 'show']);
    Route::put('/service-form-sections/{serviceFormSection}', [ServiceFormSectionController::class, 'update']);
    Route::delete('/service-form-sections/{serviceFormSection}', [ServiceFormSectionController::class, 'destroy']);

    Route::get('/service-form-fields', [ServiceFormFieldController::class, 'index']);
    Route::post('/service-form-fields', [ServiceFormFieldController::class, 'store']);
    Route::get('/service-form-fields/{serviceFormField}', [ServiceFormFieldController::class, 'show']);
    Route::put('/service-form-fields/{serviceFormField}', [ServiceFormFieldController::class, 'update']);
    Route::delete('/service-form-fields/{serviceFormField}', [ServiceFormFieldController::class, 'destroy']);
});

    Route::post('/sms/send-phone', [SmsController::class, 'sendPhone']);
    Route::post('/sms/send-otp', [SmsController::class, 'sendOtp']);
    Route::post('/sms/send-bulk', [SmsController::class, 'sendBulk']);