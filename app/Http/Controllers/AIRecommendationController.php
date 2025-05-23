<?php

namespace App\Http\Controllers;

use App\Services\AIRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Recipe;
use Illuminate\Support\Str;

class AIRecommendationController extends Controller
{
    private $aiService;

    private $cookingMethods = [
        'bake', 'boil', 'broil', 'fry', 'grill', 'roast', 'saute', 'simmer', 'steam', 'stir-fry'
    ];

    private $cookingTimes = [
        'quick' => '15-20 minutes',
        'medium' => '25-35 minutes',
        'long' => '40-60 minutes'
    ];

    private $difficultyLevels = [
        'easy' => 'Suitable for beginners',
        'medium' => 'Some cooking experience needed',
        'advanced' => 'For experienced cooks'
    ];

    public function __construct(AIRecommendationService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function getRecommendations(Request $request)
    {
        try {
            $request->validate([
                'ingredients' => 'required|array',
                'ingredients.*' => 'string'
            ]);

            Log::info('Getting AI recommendations', [
                'user_id' => $request->user()->id,
                'ingredients' => $request->ingredients
            ]);

            // Check if we have any AI recipes in the database
            $aiRecipeCount = Recipe::where('source', 'ai')->count();
            if ($aiRecipeCount === 0) {
                Log::warning('No AI recipes found in database');
                return response()->json([
                    'error' => 'No recipes available for recommendations. Please try again later.'
                ], 404)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            }

            $recommendations = $this->aiService->getRecommendations(
                $request->user(),
                $request->ingredients
            );

            if (empty($recommendations)) {
                Log::info('No recommendations found', [
                    'user_id' => $request->user()->id,
                    'ingredients' => $request->ingredients
                ]);
                return response()->json([
                    'error' => 'No recipes found matching your ingredients. Try adding different ingredients.'
                ], 404)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            }

            return response()->json([
                'recommendations' => $recommendations
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Exception $e) {
            Log::error('AI Recommendation Error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'ingredients' => $request->ingredients,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to generate recommendations. Please try again.'
            ], 500);
        }
    }

    public function updatePreferences(Request $request)
    {
        try {
            $request->validate([
                'cooking_skill_level' => 'required|string|in:beginner,intermediate,advanced',
                'preferred_cuisines' => 'array',
                'dietary_restrictions' => 'array',
                'seasonal_preferences' => 'boolean'
            ]);

            $user = $request->user();
            $preferences = $user->preferences ?? $user->preferences()->create();

            $preferences->update([
                'cooking_skill_level' => $request->cooking_skill_level,
                'preferred_cuisines' => $request->preferred_cuisines ?? [],
                'dietary_restrictions' => $request->dietary_restrictions ?? [],
                'seasonal_preferences' => $request->seasonal_preferences ?? true
            ]);

            return response()->json([
                'message' => 'Preferences updated successfully',
                'preferences' => $preferences
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Exception $e) {
            Log::error('Update Preferences Error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update preferences. Please try again.'
            ], 500)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }
    }

    private function generateAIRecipes($mainIngredients)
    {
        try {
            // Use Spoonacular's free API
            $response = Http::get('https://api.spoonacular.com/recipes/findByIngredients', [
                'ingredients' => implode(',', $mainIngredients),
                'number' => 1,
                'ranking' => 1,
                'ignorePantry' => true,
                'apiKey' => config('services.spoonacular.api_key')
            ]);

            if (!$response->successful()) {
                Log::error('Spoonacular API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to find recipes with these ingredients');
            }

            $recipeData = $response->json();
            
            if (empty($recipeData)) {
                throw new \Exception('No recipes found with these ingredients');
            }

            $recipeData = $recipeData[0];
            
            // Get detailed recipe information
            $recipeDetails = Http::get("https://api.spoonacular.com/recipes/{$recipeData['id']}/information", [
                'apiKey' => config('services.spoonacular.api_key')
            ]);

            if (!$recipeDetails->successful()) {
                Log::error('Spoonacular recipe details error', [
                    'status' => $recipeDetails->status(),
                    'body' => $recipeDetails->body()
                ]);
                throw new \Exception('Failed to get recipe details');
            }

            $details = $recipeDetails->json();

            return [
                'id' => $recipeData['id'],
                'title' => $details['title'],
                'description' => $details['summary'],
                'ingredients' => array_map(function($ingredient) {
                    return $ingredient['original'];
                }, $details['extendedIngredients']),
                'instructions' => array_map(function($step) {
                    return $step['step'];
                }, $details['analyzedInstructions'][0]['steps']),
                'cooking_time' => $details['readyInMinutes'] . ' minutes',
                'difficulty' => $this->determineDifficulty($details['readyInMinutes'], count($details['extendedIngredients'])),
                'servings' => $details['servings'],
                'source' => 'ai'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate recipe', [
                'error' => $e->getMessage(),
                'ingredients' => $mainIngredients
            ]);
            throw new \Exception('Unable to generate recipe. Please try again with different ingredients.');
        }
    }

    private function determineDifficulty($cookingTime, $ingredientCount)
    {
        $score = ($cookingTime / 10) + ($ingredientCount * 0.5);
        
        if ($score <= 5) {
            return 'easy';
        } elseif ($score <= 10) {
            return 'medium';
        } else {
            return 'hard';
        }
    }

    private function parseRecipeText($text, $mainIngredients)
    {
        // Split text into sections
        $sections = explode("\n\n", $text);
        
        // Extract recipe components
        $title = $this->extractTitle($sections);
        $ingredients = $this->extractIngredients($sections);
        $instructions = $this->extractInstructions($sections);
        
        // Ensure we have the main ingredients
        $ingredients = array_unique(array_merge($mainIngredients, $ingredients));
        
        return [
            'id' => md5(implode(',', $mainIngredients) . time()),
            'title' => $title,
            'description' => $this->generateDescription($ingredients),
            'ingredients' => $ingredients,
            'instructions' => $instructions,
            'cooking_time' => $this->estimateCookingTime($instructions),
            'difficulty' => $this->estimateDifficulty($ingredients, $instructions),
            'servings' => 4
        ];
    }

    private function extractTitle($sections)
    {
        foreach ($sections as $section) {
            if (strpos(strtolower($section), 'recipe') !== false || 
                strpos(strtolower($section), 'dish') !== false) {
                return ucwords(trim($section));
            }
        }
        return "Custom Recipe";
    }

    private function extractIngredients($sections)
    {
        $ingredients = [];
        foreach ($sections as $section) {
            if (strpos(strtolower($section), 'ingredient') !== false ||
                preg_match('/\d+\s+(?:cup|tablespoon|teaspoon|gram|ounce)/', $section)) {
                $lines = explode("\n", $section);
                foreach ($lines as $line) {
                    if (!empty(trim($line)) && strpos(strtolower($line), 'ingredient') === false) {
                        $ingredients[] = trim($line);
                    }
                }
                break;
            }
        }
        return $ingredients;
    }

    private function extractInstructions($sections)
    {
        $instructions = [];
        $foundInstructions = false;
        
        foreach ($sections as $section) {
            if ($foundInstructions || 
                strpos(strtolower($section), 'instruction') !== false || 
                strpos(strtolower($section), 'direction') !== false ||
                preg_match('/^\d+\.\s/', $section)) {
                
                $foundInstructions = true;
                $lines = explode("\n", $section);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && 
                        strpos(strtolower($line), 'instruction') === false && 
                        strpos(strtolower($line), 'direction') === false) {
                        // Clean up the instruction text
                        $line = preg_replace('/^\d+\.\s*/', '', $line);
                        $instructions[] = ucfirst($line);
                    }
                }
            }
        }
        
        return $instructions;
    }

    private function generateDescription($ingredients)
    {
        $mainIngredient = $ingredients[0];
        $otherIngredients = array_slice($ingredients, 1);
        
        return "A delicious recipe featuring " . $mainIngredient . 
               (!empty($otherIngredients) ? " combined with " . implode(', ', $otherIngredients) : "") . 
               ". Perfect for any occasion.";
    }

    private function estimateCookingTime($instructions)
    {
        $totalTime = 20; // Base preparation time
        
        // Look for time indicators in instructions
        foreach ($instructions as $instruction) {
            if (preg_match('/(\d+)[\s-]*(minute|hour)s?/i', $instruction, $matches)) {
                $time = intval($matches[1]);
                if (strtolower($matches[2]) === 'hour') {
                    $time *= 60;
                }
                $totalTime += $time;
            }
        }
        
        return $totalTime . " minutes";
    }

    private function estimateDifficulty($ingredients, $instructions)
    {
        $score = 0;
        
        // More ingredients = higher difficulty
        $score += count($ingredients) * 2;
        
        // More instructions = higher difficulty
        $score += count($instructions) * 1.5;
        
        // Look for complex techniques
        $complexTechniques = ['knead', 'fold', 'whisk', 'marinate', 'ferment', 'proof', 'blanch'];
        foreach ($instructions as $instruction) {
            foreach ($complexTechniques as $technique) {
                if (stripos($instruction, $technique) !== false) {
                    $score += 3;
                }
            }
        }
        
        // Convert score to difficulty level
        if ($score <= 15) {
            return 'easy';
        } elseif ($score <= 25) {
            return 'medium';
        } else {
            return 'hard';
        }
    }

    private function analyzeIngredients($ingredients)
    {
        $proteinIngredients = ['chicken', 'beef', 'pork', 'fish', 'tofu', 'eggs'];
        $vegetableIngredients = ['carrot', 'broccoli', 'spinach', 'tomato', 'potato'];
        $grainIngredients = ['rice', 'pasta', 'noodles', 'quinoa', 'bread'];
        
        $types = [];
        foreach ($ingredients as $ingredient) {
            if (in_array(strtolower($ingredient), $proteinIngredients)) {
                $types[] = 'protein';
            } elseif (in_array(strtolower($ingredient), $vegetableIngredients)) {
                $types[] = 'vegetable';
            } elseif (in_array(strtolower($ingredient), $grainIngredients)) {
                $types[] = 'grain';
            }
        }
        
        return array_unique($types);
    }

    private function determineCookingMethod($ingredients)
    {
        $methods = [
            'bake' => ['chicken', 'fish', 'potato', 'bread'],
            'stir_fry' => ['vegetables', 'tofu', 'noodles'],
            'grill' => ['beef', 'chicken', 'fish'],
            'boil' => ['pasta', 'rice', 'eggs'],
            'steam' => ['vegetables', 'fish', 'rice']
        ];
        
        foreach ($ingredients as $ingredient) {
            foreach ($methods as $method => $suitableIngredients) {
                if (in_array(strtolower($ingredient), $suitableIngredients)) {
                    return $method;
                }
            }
        }
        
        return 'stir_fry'; // Default method
    }

    private function generateTitle($ingredients, $types, $method)
    {
        $mainIngredient = $ingredients[0];
        $methodName = str_replace('_', '-', $method);
        return ucwords($methodName . ' ' . $mainIngredient . ' ' . implode(' & ', array_slice($ingredients, 1)));
    }

    private function generateIngredientList($mainIngredients)
    {
        $ingredients = $mainIngredients;
        
        // Add common cooking ingredients
        $commonIngredients = [
            'Salt and pepper to taste',
            'Olive oil',
            'Garlic',
            'Onion'
        ];
        
        return array_merge($ingredients, $commonIngredients);
    }

    private function generateInstructions($ingredients, $method)
    {
        $instructions = [];
        
        // Preparation steps
        $instructions[] = "Wash and prepare all ingredients.";
        
        // Method-specific steps
        switch ($method) {
            case 'bake':
                $instructions[] = "Preheat oven to 375°F (190°C).";
                $instructions[] = "Season " . $ingredients[0] . " with salt and pepper.";
                $instructions[] = "Place in baking dish and bake for " . $this->calculateCookingTime($method, count($ingredients)) . ".";
                break;
            case 'stir_fry':
                $instructions[] = "Heat oil in a large wok or frying pan over medium-high heat.";
                $instructions[] = "Add " . $ingredients[0] . " and stir-fry until cooked.";
                $instructions[] = "Add remaining ingredients and stir-fry for additional 5 minutes.";
                break;
            case 'grill':
                $instructions[] = "Preheat grill to medium-high heat.";
                $instructions[] = "Season " . $ingredients[0] . " with salt and pepper.";
                $instructions[] = "Grill for " . $this->calculateCookingTime($method, count($ingredients)) . ", turning occasionally.";
                break;
            case 'boil':
                $instructions[] = "Bring a large pot of water to boil.";
                $instructions[] = "Add " . $ingredients[0] . " and cook for " . $this->calculateCookingTime($method, count($ingredients)) . ".";
                break;
            case 'steam':
                $instructions[] = "Bring water to boil in a steamer or pot with steamer basket.";
                $instructions[] = "Place " . $ingredients[0] . " in the steamer.";
                $instructions[] = "Steam for " . $this->calculateCookingTime($method, count($ingredients)) . ".";
                break;
        }
        
        // Finishing steps
        $instructions[] = "Season to taste with salt and pepper.";
        $instructions[] = "Serve hot and enjoy!";
        
        return $instructions;
    }

    private function calculateCookingTime($method, $ingredientCount)
    {
        $baseTimes = [
            'bake' => 30,
            'stir_fry' => 15,
            'grill' => 20,
            'boil' => 15,
            'steam' => 20
        ];
        
        $baseTime = $baseTimes[$method] ?? 20;
        $additionalTime = ($ingredientCount - 1) * 5;
        
        return $baseTime + $additionalTime . " minutes";
    }

    private function calculateDifficulty($ingredients, $method)
    {
        $score = 0;
        
        // More ingredients = higher difficulty
        $score += count($ingredients) * 2;
        
        // Method-based difficulty
        $methodScores = [
            'bake' => 2,
            'stir_fry' => 3,
            'grill' => 4,
            'boil' => 1,
            'steam' => 2
        ];
        
        $score += $methodScores[$method] ?? 2;
        
        // Convert score to difficulty level
        if ($score <= 8) {
            return 'easy';
        } elseif ($score <= 12) {
            return 'medium';
        } else {
            return 'hard';
        }
    }

    public function show($id)
    {
        try {
            $recipe = Recipe::where('source', 'ai')
                ->with(['ratings', 'favoritedBy'])
                ->findOrFail($id);

            if (!$recipe) {
                Log::warning('AI Recipe not found', ['recipe_id' => $id]);
                return response()->json([
                    'error' => 'Recipe not found. Please try generating new recommendations.'
                ], 404)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                  ->header('Pragma', 'no-cache')
                  ->header('Expires', '0');
            }

            return response()->json([
                'recipe' => $recipe
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('AI Recipe not found', [
                'error' => $e->getMessage(),
                'recipe_id' => $id
            ]);

            return response()->json([
                'error' => 'Recipe not found. Please try generating new recommendations.'
            ], 404)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('AI Recipe Retrieval Error', [
                'error' => $e->getMessage(),
                'recipe_id' => $id
            ]);

            return response()->json([
                'error' => 'Failed to retrieve recipe. Please try again.'
            ], 500)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');
        }
    }
} 