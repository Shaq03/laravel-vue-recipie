<?php

namespace App\Services;

use App\Models\Recipe;

class MLService
{
    public function getSimilarRecipes(Recipe $recipe, $limit = 3, $minSimilarity = 0.3)
    {
        $allRecipes = Recipe::where('id', '!=', $recipe->id)->get();
        $similarities = [];

        foreach ($allRecipes as $otherRecipe) {
            $similarity = $this->calculateSimilarity($recipe, $otherRecipe);
            if ($similarity >= $minSimilarity) {
                $similarities[$otherRecipe->id] = $similarity;
                // Attach similarity score to recipe
                $otherRecipe->similarity_score = $similarity;
            }
        }

        // Sort by similarity score (highest first)
        arsort($similarities);

        // Get top N similar recipe IDs
        $similarRecipeIds = array_slice(array_keys($similarities), 0, $limit);

        return Recipe::whereIn('id', $similarRecipeIds)
            ->get()
            ->map(function ($recipe) use ($similarities) {
                $recipe->similarity_score = $similarities[$recipe->id];
                return $recipe;
            })
            ->sortByDesc('similarity_score');
    }

    private function calculateSimilarity(Recipe $recipe1, Recipe $recipe2)
    {
        $score = 0;

        // Compare ingredients (using Jaccard similarity)
        $ingredients1 = collect($recipe1->ingredients);
        $ingredients2 = collect($recipe2->ingredients);
        
        $intersection = $ingredients1->intersect($ingredients2)->count();
        $union = $ingredients1->union($ingredients2)->count();
        $ingredientScore = $union > 0 ? $intersection / $union : 0;
        $score += $ingredientScore * 0.4; // Ingredients are weighted 40%

        // Compare cooking time (normalized difference)
        $time1 = intval(str_replace(['min', 'mins', ' '], '', $recipe1->cooking_time));
        $time2 = intval(str_replace(['min', 'mins', ' '], '', $recipe2->cooking_time));
        $maxTime = max($time1, $time2);
        $timeScore = $maxTime > 0 ? 1 - (abs($time1 - $time2) / $maxTime) : 1;
        $score += $timeScore * 0.2; // Cooking time is weighted 20%

        // Compare difficulty
        $difficultyScore = $recipe1->difficulty === $recipe2->difficulty ? 1 : 0;
        $score += $difficultyScore * 0.2; // Difficulty is weighted 20%

        // Compare servings
        $maxServings = max($recipe1->servings, $recipe2->servings);
        $servingsScore = $maxServings > 0 ? 
            1 - (abs($recipe1->servings - $recipe2->servings) / $maxServings) : 1;
        $score += $servingsScore * 0.2; // Servings is weighted 20%

        return $score;
    }
} 