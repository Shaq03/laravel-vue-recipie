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
            Log::info('Received recipe data:', $request->all());
            
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'cooking_time' => 'required|string',  // Changed from integer to string
                'servings' => 'required|numeric',     // Changed to numeric to handle string numbers
                'difficulty' => 'required|in:easy,medium,hard',
                'ingredients' => 'required|array|min:1',
                'ingredients.*' => 'required|string',  // Validate each ingredient
                'instructions' => 'required|array|min:1',
                'instructions.*' => 'required|string', // Validate each instruction
                'image_url' => 'nullable|string|url'
            ]);

            // Convert servings to integer
            $validated['servings'] = (int) $validated['servings'];

            // Filter out empty values from arrays
            $validated['ingredients'] = array_filter($validated['ingredients']);
            $validated['instructions'] = array_filter($validated['instructions']);

            $recipe = Recipe::create($validated);
            
            Log::info('Recipe created successfully:', $recipe->toArray());
            return response()->json($recipe, 201);
        } catch (\Exception $e) {
            Log::error('Error creating recipe: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create recipe',
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : [$e->getMessage()]
            ], $e instanceof \Illuminate\Validation\ValidationException ? 422 : 500);
        }
    }
} 