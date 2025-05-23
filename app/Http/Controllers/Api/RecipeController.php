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

            // Convert cooking_time from string (e.g., "30 minutes") to integer
            $cookingTime = (int) preg_replace('/[^0-9]/', '', $validated['cooking_time']);
            $validated['cooking_time'] = $cookingTime;

            // Convert servings to integer
            $validated['servings'] = (int) $validated['servings'];

            // Filter out empty values from arrays
            $validated['ingredients'] = array_filter($validated['ingredients']);
            $validated['instructions'] = array_filter($validated['instructions']);

            // Set the user_id
            $validated['user_id'] = auth()->id();
            $validated['source'] = 'user';

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

            return response()->json(['recipe' => $formattedRecipe]);
        } catch (\Exception $e) {
            Log::error('Error retrieving recipe: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve recipe'], 500);
        }
    }
} 