<?php

namespace App\Http\Controllers;

use App\Services\RecipeScraperService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Recipe;

class WebRecipeController extends Controller
{
    protected $recipeScraperService;

    public function __construct(RecipeScraperService $recipeScraperService)
    {
        $this->recipeScraperService = $recipeScraperService;
    }

    /**
     * Search for recipes based on ingredients
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByIngredients(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ingredients' => 'required|array',
                'ingredients.*' => 'string'
            ]);

            $ingredients = $request->ingredients;
            
            // Log the search request
            Log::info('Web recipe search requested', [
                'ingredients' => $ingredients,
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]);
            
            // Search for recipes
            $recipes = $this->recipeScraperService->searchRecipesByIngredients($ingredients);
            
            // Log the number of recipes found
            Log::info('Recipes found before filtering', [
                'count' => count($recipes),
                'ingredients' => $ingredients
            ]);
            
            // Filter recipes to ensure they match the ingredients
            $filteredRecipes = $this->recipeScraperService->filterRecipesByIngredients($recipes, $ingredients);
            
            // Log the number of recipes after filtering
            Log::info('Recipes after filtering', [
                'count' => count($filteredRecipes),
                'ingredients' => $ingredients
            ]);
            
            return response()->json([
                'recipes' => $filteredRecipes,
                'count' => count($filteredRecipes),
                'ingredients' => $ingredients,
                'message' => count($filteredRecipes) > 0 
                    ? 'Recipes found successfully!' 
                    : 'No recipes found with these ingredients. Showing suggested recipes instead.'
            ]);
        } catch (\Exception $e) {
            Log::error('Web recipe search failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ingredients' => $request->ingredients ?? []
            ]);
            
            // Try to get fallback recipes
            try {
                $fallbackRecipes = $this->recipeScraperService->filterRecipesByIngredients([], $request->ingredients ?? []);
                
                return response()->json([
                    'recipes' => $fallbackRecipes,
                    'count' => count($fallbackRecipes),
                    'ingredients' => $request->ingredients ?? [],
                    'message' => 'Error in recipe search. Showing suggested recipes instead.',
                    'error' => $e->getMessage()
                ]);
            } catch (\Exception $fallbackError) {
                return response()->json([
                    'error' => 'Failed to search for recipes: ' . $e->getMessage(),
                    'recipes' => [],
                    'count' => 0
                ], 500);
            }
        }
    }
    
    /**
     * Save a web recipe to the database
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function saveRecipe(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'cooking_time' => 'required|string',
                'servings' => 'required|integer',
                'difficulty' => 'required|in:easy,medium,hard',
                'ingredients' => 'required|array',
                'instructions' => 'required|array',
                'image_url' => 'nullable|string',
                'source_url' => 'nullable|string'
            ]);
            
            // Check if recipe already exists
            $existingRecipe = Recipe::where('title', $request->title)
                ->where('source_url', $request->source_url)
                ->first();
                
            if ($existingRecipe) {
                return response()->json([
                    'message' => 'Recipe already saved',
                    'recipe' => $existingRecipe
                ]);
            }
            
            // Create a new recipe with source set to 'web'
            $recipeData = $request->all();
            $recipeData['source'] = 'web';
            $recipeData['user_id'] = auth()->id();
            
            $recipe = Recipe::create($recipeData);
            
            Log::info('Recipe saved successfully', [
                'recipe_id' => $recipe->id,
                'title' => $recipe->title
            ]);
            
            return response()->json([
                'message' => 'Recipe saved successfully',
                'recipe' => $recipe
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to save web recipe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Failed to save recipe: ' . $e->getMessage()
            ], 500);
        }
    }
} 