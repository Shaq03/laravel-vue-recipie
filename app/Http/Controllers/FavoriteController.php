<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites;
        return response()->json(['favorites' => $favorites]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id'
        ]);

        $recipe = Recipe::findOrFail($request->recipe_id);
        $request->user()->favorites()->attach($recipe->id);

        return response()->json(['message' => 'Recipe added to favorites']);
    }

    public function destroy(Request $request, Recipe $recipe)
    {
        $request->user()->favorites()->detach($recipe->id);
        return response()->json(['message' => 'Recipe removed from favorites']);
    }
} 