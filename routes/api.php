<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\SnippetController;
use App\Http\Controllers\SuggestionController;

Route::prefix('api/v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('admin-users', AdminUserController::class);
    Route::apiResource('knowledge-base', KnowledgeBaseController::class);
    Route::apiResource('chat-sessions', ChatSessionController::class);
    Route::apiResource('personas', PersonaController::class);
    Route::apiResource('snippets', SnippetController::class);
    Route::apiResource('suggestions', SuggestionController::class);
});
