<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\ManagerApplicationController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ServiceApplicationController;
use App\Http\Controllers\Api\Admin\ServiceFormController;
use App\Http\Controllers\Api\Admin\ServiceFormFieldController;
use App\Http\Controllers\Api\Admin\ServiceFormFieldConditionController;
use App\Http\Controllers\Api\Admin\ServiceFormSectionController;
use App\Http\Controllers\Api\Admin\ServiceFormStepController;
use App\Http\Controllers\Api\Admin\ApplicationDashboardController;
use App\Http\Controllers\Api\Admin\ReportingDashboardController;
use App\Http\Controllers\Api\Customer\CustomerServiceApplicationController;
use App\Http\Controllers\Api\Customer\CustomerNotificationController;
use App\Http\Controllers\Api\OfficerApplicationShareController;
use App\Http\Controllers\Api\Public\PublicApplicationController;
use App\Http\Controllers\Api\Public\ApplicationTrackingController;
use App\Http\Controllers\Api\Officer\OfficerApplicationController;
use App\Http\Controllers\Api\Officer\CertificateController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\WindowController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::put('/applications/{application}', [ApplicationController::class, 'update']);
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);
    Route::get('/customer/notifications', [CustomerNotificationController::class, 'index']);
    Route::get('/customer/service-applications', [CustomerServiceApplicationController::class, 'index']);
    Route::get('/customer/service-applications/{application}', [CustomerServiceApplicationController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('service-forms', ServiceFormController::class);
    Route::apiResource('service-form-sections', ServiceFormSectionController::class);
    Route::apiResource('service-form-steps', ServiceFormStepController::class);
    Route::apiResource('service-form-fields', ServiceFormFieldController::class);
    Route::apiResource('service-form-field-conditions', ServiceFormFieldConditionController::class);
    Route::get('/service-applications', [ServiceApplicationController::class, 'index']);
    Route::get('/service-applications/{serviceApplication}', [ServiceApplicationController::class, 'show']);
    Route::put('/service-applications/{serviceApplication}', [ServiceApplicationController::class, 'update']);
    Route::delete('/service-applications/{serviceApplication}', [ServiceApplicationController::class, 'destroy']);
    Route::get('/applications/summary', [ApplicationDashboardController::class, 'summary']);
    Route::get('/dashboard/reporting', [ReportingDashboardController::class, 'index']);
    Route::get('/dashboard/reporting/report', [ReportingDashboardController::class, 'report']);
});

Route::prefix('public')->group(function () {
    Route::get('/services/{service}/form', [PublicApplicationController::class, 'form']);
    Route::middleware('auth:sanctum')->post('/services/{service}/apply', [PublicApplicationController::class, 'apply']);
    Route::post('/track-application', [ApplicationTrackingController::class, 'track']);
});

Route::middleware('auth:sanctum')->prefix('officer')->group(function () {
    Route::get('/applications/queue', [OfficerApplicationController::class, 'queue']);
    Route::get('/notifications', [OfficerApplicationController::class, 'notifications']);
    Route::get('/applications/{application}', [OfficerApplicationController::class, 'show']);
    Route::get('/sharing/windows', [OfficerApplicationShareController::class, 'windows']);
    Route::get('/sharing/windows/{window}/officers', [OfficerApplicationShareController::class, 'officers']);
    Route::post('/applications/{application}/share-to-officer', [OfficerApplicationShareController::class, 'share']);
    Route::post('/applications/{application}/accept', [OfficerApplicationController::class, 'accept']);
    Route::post('/applications/{application}/appointment', [OfficerApplicationController::class, 'appointment']);
    Route::post('/applications/{application}/share', [OfficerApplicationController::class, 'share']);
    Route::post('/applications/{application}/forward-to-back-officer', [OfficerApplicationController::class, 'forwardToBackOfficer']);
    Route::post('/applications/{application}/approve', [OfficerApplicationController::class, 'approve']);
    Route::post('/applications/{application}/reject', [OfficerApplicationController::class, 'reject']);
    Route::post('/applications/{application}/return', [OfficerApplicationController::class, 'returnApplication']);
    Route::post('/applications/{application}/complete', [OfficerApplicationController::class, 'complete']);
    Route::post('/applications/{application}/escalate-to-manager', [OfficerApplicationController::class, 'escalateToManager']);
    Route::get('/applications/{application}/certificate', [CertificateController::class, 'download']);
});

Route::middleware('auth:sanctum')->prefix('manager')->group(function () {
    Route::get('/applications/queue', [ManagerApplicationController::class, 'queue']);
    Route::get('/applications/{application}', [ManagerApplicationController::class, 'show']);
    Route::post('/applications/{application}/assign-officer', [ManagerApplicationController::class, 'assign']);
    Route::post('/applications/{application}/return-to-officer', [ManagerApplicationController::class, 'returnToOfficer']);
    Route::post('/applications/{application}/escalate-up', [ManagerApplicationController::class, 'escalateUp']);
});


Route::apiResource('feedback', FeedbackController::class);
Route::get(
    'windows/{window}/services',
    [WindowController::class, 'services']
);
