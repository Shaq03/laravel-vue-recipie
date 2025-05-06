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
            // Get all recipes except the current one
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
            $weights = [
                'ingredients' => 0.4,
                'cuisine' => 0.2,
                'difficulty' => 0.1,
                'cooking_time' => 0.1,
                'tags' => 0.2
            ];

            // Calculate ingredient similarity
            $ingredientSimilarity = $this->calculateIngredientSimilarity($recipe1, $recipe2);

            // Calculate cuisine similarity
            $cuisineSimilarity = $this->calculateCuisineSimilarity($recipe1, $recipe2);

            // Calculate difficulty similarity
            $difficultySimilarity = $this->calculateDifficultySimilarity($recipe1->difficulty, $recipe2->difficulty);

            // Calculate cooking time similarity
            $timeSimilarity = $this->calculateTimeSimilarity($recipe1->cooking_time, $recipe2->cooking_time);

            // Calculate tag similarity
            $tagSimilarity = $this->calculateTagSimilarity($recipe1, $recipe2);

            // Calculate weighted average
            return (
                $ingredientSimilarity * $weights['ingredients'] +
                $cuisineSimilarity * $weights['cuisine'] +
                $difficultySimilarity * $weights['difficulty'] +
                $timeSimilarity * $weights['cooking_time'] +
                $tagSimilarity * $weights['tags']
            );
        } catch (\Exception $e) {
            Log::error('Error calculating recipe similarity: ' . $e->getMessage());
            return 0.0;
        }
    }

    private function calculateIngredientSimilarity(Recipe $recipe1, Recipe $recipe2): float
    {
        // Get ingredients as arrays
        $ingredients1 = is_array($recipe1->ingredients) ? $recipe1->ingredients : json_decode($recipe1->ingredients, true);
        $ingredients2 = is_array($recipe2->ingredients) ? $recipe2->ingredients : json_decode($recipe2->ingredients, true);

        if (empty($ingredients1) || empty($ingredients2)) {
            return 0.0;
        }

        // Normalize ingredients for comparison
        $ingredients1 = array_map(function($ingredient) {
            return strtolower(trim($ingredient));
        }, $ingredients1);
        
        $ingredients2 = array_map(function($ingredient) {
            return strtolower(trim($ingredient));
        }, $ingredients2);

        // Calculate Jaccard similarity
        $intersection = array_intersect($ingredients1, $ingredients2);
        $union = array_unique(array_merge($ingredients1, $ingredients2));

        if (empty($union)) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    private function calculateCuisineSimilarity(Recipe $recipe1, Recipe $recipe2): float
    {
        $cuisines1 = is_array($recipe1->cuisines) ? $recipe1->cuisines : json_decode($recipe1->cuisines, true);
        $cuisines2 = is_array($recipe2->cuisines) ? $recipe2->cuisines : json_decode($recipe2->cuisines, true);

        if (empty($cuisines1) || empty($cuisines2)) {
            return 0.0;
        }

        // Normalize cuisines to lowercase
        $cuisines1 = array_map('strtolower', $cuisines1);
        $cuisines2 = array_map('strtolower', $cuisines2);

        // Calculate Jaccard similarity for cuisines
        $intersection = array_intersect($cuisines1, $cuisines2);
        $union = array_unique(array_merge($cuisines1, $cuisines2));

        return count($intersection) / count($union);
    }

    private function calculateDifficultySimilarity(string $difficulty1, string $difficulty2): float
    {
        $difficulties = ['easy' => 1, 'medium' => 2, 'hard' => 3];
        $diff1 = $difficulties[strtolower($difficulty1)] ?? 2;
        $diff2 = $difficulties[strtolower($difficulty2)] ?? 2;

        return 1 - (abs($diff1 - $diff2) / 2);
    }

    private function calculateTimeSimilarity(int $time1, int $time2): float
    {
        $maxTime = 120; // Maximum cooking time in minutes
        return 1 - (abs($time1 - $time2) / $maxTime);
    }

    private function calculateTagSimilarity(Recipe $recipe1, Recipe $recipe2): float
    {
        $tags1 = is_array($recipe1->tags) ? $recipe1->tags : json_decode($recipe1->tags, true);
        $tags2 = is_array($recipe2->tags) ? $recipe2->tags : json_decode($recipe2->tags, true);

        if (empty($tags1) || empty($tags2)) {
            return 0.0;
        }

        // Normalize tags to lowercase
        $tags1 = array_map('strtolower', $tags1);
        $tags2 = array_map('strtolower', $tags2);

        // Calculate Jaccard similarity for tags
        $intersection = array_intersect($tags1, $tags2);
        $union = array_unique(array_merge($tags1, $tags2));

        return count($intersection) / count($union);
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