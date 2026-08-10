<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Chatbot\CustomerChatbotController;
use App\Http\Controllers\Api\Chatbot\ChatbotCategoryController;
use App\Http\Controllers\Api\Chatbot\ChatbotTrainingQuestionController;
use App\Http\Controllers\Api\FeedbackController;
Route::middleware('auth:sanctum')->post('/chatbot/message', [CustomerChatbotController::class, 'message']);

Route::middleware('auth:sanctum')->prefix('admin/chatbot')->group(function () {
    Route::apiResource('categories', ChatbotCategoryController::class);
    Route::apiResource('training-questions', ChatbotTrainingQuestionController::class);
});

Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'eService API is working',
        'time' => now(),
    ]);
});
// Public kiosk submission — no login required at the service window.
Route::post('feedback', [FeedbackController::class, 'store']);

// Viewing / managing feedback requires an authenticated agent so it can be
// scoped to their city / subcity / woreda.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('feedback', [FeedbackController::class, 'index']);
    // Must be registered before feedback/{feedback} or "windows" gets
    // swallowed by the {feedback} route-model-binding wildcard.
    Route::get('feedback/windows', [FeedbackController::class, 'windows']);
    Route::get('feedback/{feedback}', [FeedbackController::class, 'show']);
    Route::put('feedback/{feedback}', [FeedbackController::class, 'update']);
    Route::patch('feedback/{feedback}', [FeedbackController::class, 'update']);
    Route::delete('feedback/{feedback}', [FeedbackController::class, 'destroy']);
});
require base_path('routes/public.php');
require base_path('routes/auth.php');
require base_path('routes/user.php');
require base_path('routes/service.php');
require base_path('routes/window.php');
require base_path('routes/application.php');
