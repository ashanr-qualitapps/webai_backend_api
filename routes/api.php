<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PersonaController;
use App\Http\Controllers\Api\V1\TenantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes (no middleware required)
Route::prefix('v1')->middleware(['auth.rate_limit'])->group(function () {
    // Authentication endpoints
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/register', [AuthController::class, 'register']);
    
    // Tenant registration (for new domains)
    Route::post('/tenants', [TenantController::class, 'store']);
    
    // Public persona routes (no authentication required)
    Route::get('/personas', [PersonaController::class, 'index']);
    Route::get('/personas/{id}', [PersonaController::class, 'show']);
});

// Protected routes (require authentication)
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    // Authentication endpoints that require token
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Tenant information
    Route::get('/tenant/current', [TenantController::class, 'current']);
    
    // Example protected routes with both permission and scope middleware
    Route::middleware(['permission:users.read', 'scope:users:read'])->group(function () {
        // Routes that require both 'users.read' permission AND 'users:read' scope
        // Route::get('/admin-users', [AdminUserController::class, 'index']);
    });
    
    Route::middleware(['permission:users.create', 'scope:users:write,write'])->group(function () {
        // Routes that require 'users.create' permission AND ('users:write' OR 'write') scope
        // No normal user endpoints - using admin users only
    });
    
    Route::middleware(['permission:users.update', 'scope:users:write,write'])->group(function () {
        // Routes that require 'users.update' permission AND ('users:write' OR 'write') scope
        // Route::put('/admin-users/{id}', [AdminUserController::class, 'update']);
    });
    
    Route::middleware(['permission:users.delete', 'scope:users:delete,delete'])->group(function () {
        // Routes that require 'users.delete' permission AND ('users:delete' OR 'delete') scope
        Route::delete('/admin-users/{id}', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'destroy']);
    });
    
    // Admin only routes (wildcard permission)
    Route::middleware(['permission:admin.*'])->group(function () {
        // Routes that require admin permissions
        // Route::get('/system/logs', [SystemController::class, 'logs']);
        // Route::get('/system/stats', [SystemController::class, 'stats']);
    });
    
    // Knowledge base routes with both permission and scope checks
    Route::middleware(['permission:knowledge.read', 'scope:knowledge:read,read'])->group(function () {
        // Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index']);
        // Route::get('/knowledge-base/{id}', [KnowledgeBaseController::class, 'show']);
    });
    
    Route::middleware(['permission:knowledge.create', 'scope:knowledge:write,write'])->group(function () {
        // Route::post('/knowledge-base', [KnowledgeBaseController::class, 'store']);
    });
    
    Route::middleware(['permission:knowledge.update', 'scope:knowledge:write,write'])->group(function () {
        // Route::put('/knowledge-base/{id}', [KnowledgeBaseController::class, 'update']);
    });
    
    Route::middleware(['permission:knowledge.delete', 'scope:knowledge:delete,delete'])->group(function () {
        // Route::delete('/knowledge-base/{id}', [KnowledgeBaseController::class, 'destroy']);
    });
    
    // Chat session routes with both permission and scope checks
    Route::middleware(['permission:chat.read', 'scope:chat:read,read'])->group(function () {
        // Route::get('/chat-sessions', [ChatSessionController::class, 'index']);
        // Route::get('/chat-sessions/{id}', [ChatSessionController::class, 'show']);
    });
    
    Route::middleware(['permission:chat.create'])->group(function () {
        // Route::post('/chat-sessions', [ChatSessionController::class, 'store']);
    });
    
    // Persona routes (protected - require authentication and permissions)
    Route::middleware(['permission:persona:write'])->group(function () {
        Route::post('/personas', [PersonaController::class, 'store']);
        Route::put('/personas/{id}', [PersonaController::class, 'update']);
        Route::patch('/personas/{id}', [PersonaController::class, 'update']);
        Route::delete('/personas/{id}', [PersonaController::class, 'destroy']);
    });
    
    // Snippet routes
    Route::middleware(['permission:snippets.read'])->group(function () {
        // Route::get('/snippets', [SnippetController::class, 'index']);
        // Route::get('/snippets/{id}', [SnippetController::class, 'show']);
    });
    
    Route::middleware(['permission:snippets.create'])->group(function () {
        // Route::post('/snippets', [SnippetController::class, 'store']);
    });
    
    // Suggestion routes
    Route::middleware(['permission:suggestions.read'])->group(function () {
        // Route::get('/suggestions', [SuggestionController::class, 'index']);
        // Route::get('/suggestions/{id}', [SuggestionController::class, 'show']);
    });
    
    Route::middleware(['permission:suggestions.create'])->group(function () {
        // Route::post('/suggestions', [SuggestionController::class, 'store']);
    });
});
