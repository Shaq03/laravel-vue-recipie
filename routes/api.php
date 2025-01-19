<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\AIRecommendationController;
use App\Http\Controllers\MLRecommendationController;

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

Route::post('/v1/ai/recommendations', [AIRecommendationController::class, 'getRecommendations']);
Route::get('/v1/ml/recipes/{recipe}/similar', [MLRecommendationController::class, 'getSimilarRecipes']); 