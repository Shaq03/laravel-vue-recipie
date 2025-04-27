<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\AIRecommendationController;
use App\Http\Controllers\MLRecommendationController;
use App\Http\Controllers\WebRecipeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRecipeController;
use App\Http\Controllers\FavoriteController;

// Public routes
Route::post('/v1/register', [AuthController::class, 'register']);
Route::post('/v1/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/v1/logout', [AuthController::class, 'logout']);
    Route::get('/v1/user', [AuthController::class, 'user']);
    
    // User profile routes
    Route::put('/v1/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/v1/user/password', [UserController::class, 'updatePassword']);

    // User recipes
    Route::get('/v1/user/recipes', [UserRecipeController::class, 'index']);
    Route::post('/v1/user/recipes', [UserRecipeController::class, 'store']);
    Route::get('/v1/user/recipes/{recipe}', [UserRecipeController::class, 'show']);
    Route::put('/v1/user/recipes/{recipe}', [UserRecipeController::class, 'update']);
    Route::delete('/v1/user/recipes/{recipe}', [UserRecipeController::class, 'destroy']);

    // User favorites
    Route::get('/v1/user/favorites', [FavoriteController::class, 'index']);
    Route::post('/v1/user/favorites', [FavoriteController::class, 'store']);
    Route::delete('/v1/user/favorites/{recipe}', [FavoriteController::class, 'destroy']);

    // API v1 routes
    Route::prefix('v1')->group(function () {
        // Recipe routes
        Route::get('/recipes', [RecipeController::class, 'index']);
        Route::post('/recipes', [RecipeController::class, 'store']);
        Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);
        Route::put('/recipes/{recipe}', [RecipeController::class, 'update']);
        Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);

        // AI recommendations
        Route::post('/ai/recommendations', [AIRecommendationController::class, 'getRecommendations']);
        Route::put('/ai/preferences', [AIRecommendationController::class, 'updatePreferences']);
        Route::get('/ai/recipes/{recipe}', [AIRecommendationController::class, 'getRecipe']);
        
        // ML recommendations
        Route::get('/ml/recipes/{recipe}/similar', [MLRecommendationController::class, 'getSimilarRecipes']);
    });
});

// Web recipe routes
Route::post('/web/recipes/search', [WebRecipeController::class, 'searchByIngredients']);
Route::post('/web/recipes/save', [WebRecipeController::class, 'saveRecipe']); 