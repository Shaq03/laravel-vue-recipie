<?php

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class MLService
{
    public function getSimilarRecipes(Recipe $recipe, int $limit = 3, float $minSimilarity = 0.2): Collection
    {
        try {
            $allRecipes = Recipe::where('id', '!=', $recipe->id)->get();
            $similarities = [];

            foreach ($allRecipes as $otherRecipe) {
                $similarity = $this->calculateSimilarity($recipe, $otherRecipe);
                if ($similarity >= $minSimilarity) {
                    $similarities[] = [
                        'recipe' => $otherRecipe,
                        'similarity' => $similarity
                    ];
                }
            }

            // Sort by similarity in descending order
            usort($similarities, function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            // Take top N recipes
            $similarities = array_slice($similarities, 0, $limit);

            // Convert to Eloquent Collection
            return Recipe::whereIn('id', collect($similarities)->pluck('recipe.id'))->get()
                ->map(function ($recipe) use ($similarities) {
                    $recipe->similarity_score = collect($similarities)
                        ->firstWhere('recipe.id', $recipe->id)['similarity'];
                    return $recipe;
                });
        } catch (\Exception $e) {
            Log::error('Error finding similar recipes: ' . $e->getMessage());
            return new Collection([]);
        }
    }

    public function calculateSimilarity(Recipe $recipe1, Recipe $recipe2): float
    {
        try {
            // Get ingredients as arrays
            $ingredients1 = is_array($recipe1->ingredients) ? $recipe1->ingredients : json_decode($recipe1->ingredients, true);
            $ingredients2 = is_array($recipe2->ingredients) ? $recipe2->ingredients : json_decode($recipe2->ingredients, true);

            if (empty($ingredients1) || empty($ingredients2)) {
                return 0.0;
            }

            // Calculate Jaccard similarity
            $intersection = array_intersect($ingredients1, $ingredients2);
            $union = array_unique(array_merge($ingredients1, $ingredients2));

            if (empty($union)) {
                return 0.0;
            }

            return count($intersection) / count($union);
        } catch (\Exception $e) {
            Log::error('Error calculating recipe similarity: ' . $e->getMessage());
            return 0.0;
        }
    }

    public function findSimilarRecipes(Recipe $recipe, int $limit = 5): Collection
    {
        try {
            $allRecipes = Recipe::where('id', '!=', $recipe->id)->get();
            $similarities = [];

            foreach ($allRecipes as $otherRecipe) {
                $similarity = $this->calculateSimilarity($recipe, $otherRecipe);
                if ($similarity > 0) {
                    $similarities[] = [
                        'recipe' => $otherRecipe,
                        'similarity' => $similarity
                    ];
                }
            }

            // Sort by similarity in descending order
            usort($similarities, function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            // Take top N recipes
            $similarities = array_slice($similarities, 0, $limit);

            return collect($similarities)->pluck('recipe');
        } catch (\Exception $e) {
            Log::error('Error finding similar recipes: ' . $e->getMessage());
            return collect([]);
        }
    }
} 