<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserRecipeController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // Get only user-created recipes for the authenticated user
            $recipes = Recipe::where('user_id', Auth::id())
                ->where('source', 'user')
                ->with(['ratings', 'favorites'])
                ->latest()
                ->get();

            return response()->json([
                'recipes' => $recipes
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve user recipes'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'ingredients' => 'required|array',
                'instructions' => 'required|array',
                'cooking_time' => 'required|integer|min:1',
                'difficulty' => 'required|in:easy,medium,hard',
                'cuisines' => 'required|array',
                'tags' => 'nullable|array',
                'image' => 'nullable|string'
            ]);

            $recipe = new Recipe($validated);
            $recipe->user_id = Auth::id();
            $recipe->source = 'user'; // Mark as user-created
            $recipe->save();

            return response()->json([
                'message' => 'Recipe created successfully',
                'recipe' => $recipe
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create recipe'], 500);
        }
    }

    public function show(Recipe $recipe): JsonResponse
    {
        // Check if user owns the recipe
        if ($recipe->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $recipe->load(['ratings', 'favorites']);
            return response()->json(['recipe' => $recipe]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve recipe'], 500);
        }
    }

    public function update(Request $request, Recipe $recipe): JsonResponse
    {
        // Check if user owns the recipe
        if ($recipe->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'ingredients' => 'required|array',
                'instructions' => 'required|array',
                'cooking_time' => 'required|integer|min:1',
                'difficulty' => 'required|in:easy,medium,hard',
                'cuisines' => 'required|array',
                'tags' => 'nullable|array',
                'image' => 'nullable|string'
            ]);

            $recipe->update($validated);

            return response()->json([
                'message' => 'Recipe updated successfully',
                'recipe' => $recipe
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update recipe'], 500);
        }
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        // Check if user owns the recipe
        if ($recipe->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $recipe->delete();
            return response()->json(['message' => 'Recipe deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete recipe'], 500);
        }
    }
} 