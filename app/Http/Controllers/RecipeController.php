<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    public function index(Request $request = null): JsonResponse
    {
        try {
            // Get only user-created recipes, excluding AI-generated ones
            $query = Recipe::where('source', 'user')
                ->where('user_id', Auth::id())
                ->with(['user', 'ratings', 'favorites'])
                ->latest();

            $perPage = $request ? $request->input('per_page', 12) : 12;
            $recipes = $query->paginate($perPage);

            $response = [
                'recipes' => (object)[
                    'data' => $recipes->items(),
                    'total' => $recipes->total(),
                    'per_page' => $recipes->perPage(),
                    'current_page' => $recipes->currentPage(),
                    'last_page' => $recipes->lastPage()
                ],
                'message' => 'Recipes retrieved successfully'
            ];

            return response()->json((object)$response);
        } catch (\Exception $e) {
            Log::error('Error retrieving recipes: ' . $e->getMessage());
            return response()->json((object)[
                'message' => 'Failed to retrieve recipes',
                'recipes' => (object)[
                    'data' => [],
                    'total' => 0,
                    'per_page' => 12,
                    'current_page' => 1,
                    'last_page' => 1
                ]
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $recipe = Recipe::with([
                'ratings',
                'favoritedBy',
                'user'
            ])->findOrFail($id);

            // Format the recipe data
            $formattedRecipe = [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'description' => $recipe->description,
                'ingredients' => $recipe->ingredients,
                'instructions' => $recipe->instructions,
                'cooking_time' => $recipe->cooking_time,
                'servings' => $recipe->servings,
                'difficulty' => $recipe->difficulty,
                'image_url' => $recipe->image_url,
                'cuisines' => $recipe->cuisines,
                'tags' => $recipe->tags,
                'dietary_restrictions' => $recipe->dietary_restrictions,
                'average_rating' => $recipe->ratings->avg('rating'),
                'total_ratings' => $recipe->ratings->count(),
                'is_favorited' => $recipe->favoritedBy->contains(auth()->id()),
                'created_at' => $recipe->created_at,
                'updated_at' => $recipe->updated_at,
                'user' => $recipe->user ? [
                    'id' => $recipe->user->id,
                    'name' => $recipe->user->name
                ] : null
            ];

            return response()->json(['recipe' => $formattedRecipe])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Recipe not found', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Recipe not found'], 404)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('Error retrieving recipe', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to retrieve recipe'], 500)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
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
            $recipe->source = 'user';
            $recipe->save();

            return response()->json([
                'message' => 'Recipe created successfully',
                'recipe' => $recipe
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating recipe: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create recipe'], 500);
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
            Log::error('Error updating recipe: ' . $e->getMessage());
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

            return response()->json([
                'message' => 'Recipe deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting recipe: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete recipe'], 500);
        }
    }
} 