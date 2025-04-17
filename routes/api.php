<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\AIRecommendationController;
use App\Http\Controllers\MLRecommendationController;
use App\Http\Controllers\WebRecipeController;

// All v1 routes
Route::prefix('v1')->group(function () {
    // Recipe routes
    Route::get('/recipes', [RecipeController::class, 'index']);
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);

    // AI recommendations
    Route::post('/ai/recommendations', [AIRecommendationController::class, 'getRecommendations']);
    
    // ML recommendations
    Route::get('/ml/recipes/{recipe}/similar', [MLRecommendationController::class, 'getSimilarRecipes']);
    
    // Web recipe routes
    Route::post('/web/recipes/search', [WebRecipeController::class, 'searchByIngredients']);
    Route::post('/web/recipes/save', [WebRecipeController::class, 'saveRecipe']);
});

// Protected routes (if needed later)
Route::middleware('auth.token')->prefix('v1')->group(function () {
    // Add protected routes here
}); 