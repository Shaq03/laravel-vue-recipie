<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Services\MLService;
use Illuminate\Http\Request;

class MLRecommendationController extends Controller
{
    private $mlService;

    public function __construct(MLService $mlService)
    {
        $this->mlService = $mlService;
    }

    public function getSimilarRecipes(Recipe $recipe)
    {
        try {
            $similarRecipes = $this->mlService->getSimilarRecipes($recipe);
            
            // Add similarity scores to the response
            $recipesWithScores = $similarRecipes->map(function($recipe) {
                return [
                    'recipe' => $recipe,
                    'similarity_score' => number_format($recipe->similarity_score * 100, 1) . '%'
                ];
            });

            return response()->json([
                'success' => true,
                'similar_recipes' => $recipesWithScores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get similar recipes: ' . $e->getMessage()
            ], 500);
        }
    }
} 