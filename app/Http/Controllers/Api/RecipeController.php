<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    public function index(): JsonResponse
    {
        Log::info('Recipe index endpoint hit');
        try {
            $recipes = Recipe::latest()->get();
            Log::info('Recipes retrieved successfully', [
                'count' => $recipes->count(),
                'recipes' => $recipes->toArray()
            ]);
            return response()->json($recipes);
        } catch (\Exception $e) {
            Log::error('Error retrieving recipes', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to retrieve recipes'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'cooking_time' => 'required|integer|min:1',
            'servings' => 'required|integer|min:1',
            'difficulty' => 'required|string|in:easy,medium,hard',
            'ingredients' => 'required|array',
            'instructions' => 'required|array',
            'image_url' => 'nullable|url',
        ]);

        $recipe = Recipe::create($validated);
        return response()->json($recipe, 201);
    }
} 