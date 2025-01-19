<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\AuthController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Recipe routes
Route::prefix('v1')->group(function () {
    Route::get('/recipes', [RecipeController::class, 'index']);
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);
});

// Protected routes (if needed later)
Route::middleware('auth.token')->prefix('v1')->group(function () {
    // Add protected routes here
}); 