<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $recipes = Recipe::latest()->get();
            return response()->json(['recipes' => $recipes]);
        } catch (\Exception $e) {
            Log::error('Error retrieving recipes: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve recipes'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'cooking_time' => 'required|string',
                'servings' => 'required|integer',
                'difficulty' => 'required|in:easy,medium,hard',
                'ingredients' => 'required|array',
                'instructions' => 'required|array',
                'image_url' => 'nullable|string|url'
            ]);

            $recipe = Recipe::create($validated);
            return response()->json($recipe, 201);
        } catch (\Exception $e) {
            Log::error('Error creating recipe: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create recipe'], 500);
        }
    }

    public function show(Recipe $recipe): JsonResponse
    {
        return response()->json($recipe);
    }

    public function update(Request $request, Recipe $recipe): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'string|max:255',
                'description' => 'string',
                'cooking_time' => 'string',
                'servings' => 'integer',
                'difficulty' => 'in:easy,medium,hard',
                'ingredients' => 'array',
                'instructions' => 'array',
                'image_url' => 'nullable|string|url'
            ]);

            $recipe->update($validated);
            return response()->json($recipe);
        } catch (\Exception $e) {
            Log::error('Error updating recipe: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update recipe'], 500);
        }
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        try {
            $recipe->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting recipe: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete recipe'], 500);
        }
    }
} 