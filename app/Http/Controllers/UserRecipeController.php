<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class UserRecipeController extends Controller
{
    public function index(Request $request)
    {
        $recipes = $request->user()->recipes;
        return response()->json(['recipes' => $recipes]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'cooking_time' => 'required|string',
            'servings' => 'required|integer',
            'difficulty' => 'required|string',
            'ingredients' => 'required|array',
            'instructions' => 'required|array',
            'image_url' => 'nullable|string',
        ]);

        $recipe = $request->user()->recipes()->create($request->all());
        return response()->json($recipe, 201);
    }

    public function show(Request $request, Recipe $recipe)
    {
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($recipe);
    }

    public function update(Request $request, Recipe $recipe)
    {
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'cooking_time' => 'required|string',
            'servings' => 'required|integer',
            'difficulty' => 'required|string',
            'ingredients' => 'required|array',
            'instructions' => 'required|array',
            'image_url' => 'nullable|string',
        ]);

        $recipe->update($request->all());
        return response()->json($recipe);
    }

    public function destroy(Request $request, Recipe $recipe)
    {
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $recipe->delete();
        return response()->json(null, 204);
    }
} 