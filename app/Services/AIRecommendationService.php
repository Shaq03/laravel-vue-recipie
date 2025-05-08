<?php

namespace App\Services;

use App\Models\User;
use App\Models\Recipe;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class AIRecommendationService
{
    private $recipeDatabase = [];
    private $userPreferences = [];
    private $cookingHistory = [];
    private $ingredientSimilarityMatrix = [];
    private $recipeSimilarityMatrix = [];
    private const MIN_SIMILARITY_THRESHOLD = 0.85; 
    private const DIVERSITY_PENALTY = 0.2; 

    public function __construct()
    {
        $this->initializeRecipeDatabase();
        $this->buildSimilarityMatrices();
    }

    private function initializeRecipeDatabase()
    {
        $this->recipeDatabase = Recipe::where('source', 'ai')
            ->with(['ratings', 'favoritedBy'])
            ->get()
            ->toArray();
    }

    private function buildSimilarityMatrices()
    {
        foreach ($this->recipeDatabase as $recipe1) {
            foreach ($this->recipeDatabase as $recipe2) {
                if ($recipe1['id'] !== $recipe2['id']) {
                    $this->ingredientSimilarityMatrix[$recipe1['id']][$recipe2['id']] = 
                        $this->calculateIngredientSetSimilarity(
                            $recipe1['ingredients'],
                            $recipe2['ingredients']
                        );
                }
            }
        }

        foreach ($this->recipeDatabase as $recipe1) {
            foreach ($this->recipeDatabase as $recipe2) {
                if ($recipe1['id'] !== $recipe2['id']) {
                    $this->recipeSimilarityMatrix[$recipe1['id']][$recipe2['id']] = 
                        $this->calculateRecipeSimilarity($recipe1, $recipe2);
                }
            }
        }
    }

    private function calculateMLScore($recipe, $ingredients)
    {
        $scores = [
            'ingredient_match' => $this->calculateIngredientMatchScore($recipe, $ingredients),
            'preference_match' => $this->calculatePreferenceScore($recipe),
            'complexity_match' => $this->calculateComplexityScore($recipe),
            'seasonal_match' => $this->calculateSeasonalScore($recipe)
        ];

        $weights = [
            'ingredient_match' => 0.4,
            'preference_match' => 0.3,
            'complexity_match' => 0.2,
            'seasonal_match' => 0.1
        ];

        $totalScore = 0;
        foreach ($scores as $type => $score) {
            $totalScore += $score * $weights[$type];
        }

        return $totalScore;
    }

    private function vectorizeIngredients($ingredients)
    {
        $vector = [];
        foreach ($ingredients as $ingredient) {
            $vector[strtolower($ingredient)] = 1;
        }
        return $vector;
    }

    private function calculateCosineSimilarity($vector1, $vector2)
    {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        foreach ($vector1 as $key => $value) {
            if (isset($vector2[$key])) {
                $dotProduct += $value * $vector2[$key];
            }
            $magnitude1 += $value * $value;
        }

        foreach ($vector2 as $value) {
            $magnitude2 += $value * $value;
        }

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($magnitude1) * sqrt($magnitude2));
    }

    private function calculateIngredientSetSimilarity($ingredients1, $ingredients2)
    {
        if (empty($ingredients1) || empty($ingredients2)) {
            return 0;
        }

        $ingredients1 = is_string($ingredients1) ? json_decode($ingredients1, true) : $ingredients1;
        $ingredients2 = is_string($ingredients2) ? json_decode($ingredients2, true) : $ingredients2;

        if (!is_array($ingredients1) || !is_array($ingredients2)) {
            return 0;
        }

        $intersection = array_intersect($ingredients1, $ingredients2);
        $union = array_unique(array_merge($ingredients1, $ingredients2));
        
        return count($intersection) / count($union);
    }

    private function calculateRecipeSimilarity($recipe1, $recipe2)
    {
        $weights = [
            'ingredients' => 0.35,
            'cuisine' => 0.20,
            'difficulty' => 0.10,
            'cooking_time' => 0.10,
            'tags' => 0.15,
            'popularity' => 0.10
        ];

        $ingredientSimilarity = $this->calculateIngredientSetSimilarity(
            $recipe1['ingredients'],
            $recipe2['ingredients']
        );

        $cuisineSimilarity = $this->calculateCuisineSimilarity(
            $recipe1['cuisines'] ?? [],
            $recipe2['cuisines'] ?? []
        );

        $difficultySimilarity = $this->calculateDifficultySimilarity(
            $recipe1['difficulty'] ?? 'medium',
            $recipe2['difficulty'] ?? 'medium'
        );

        $timeSimilarity = $this->calculateTimeSimilarity(
            $recipe1['cooking_time'] ?? 30,
            $recipe2['cooking_time'] ?? 30
        );

        $tagSimilarity = $this->calculateTagSimilarity(
            $recipe1['tags'] ?? [],
            $recipe2['tags'] ?? []
        );

        $popularitySimilarity = $this->calculatePopularitySimilarity(
            $recipe1['popularity_score'] ?? 0,
            $recipe2['popularity_score'] ?? 0
        );

        return (
            $ingredientSimilarity * $weights['ingredients'] +
            $cuisineSimilarity * $weights['cuisine'] +
            $difficultySimilarity * $weights['difficulty'] +
            $timeSimilarity * $weights['cooking_time'] +
            $tagSimilarity * $weights['tags'] +
            $popularitySimilarity * $weights['popularity']
        );
    }

    private function calculateCuisineSimilarity($cuisines1, $cuisines2)
    {
        if (empty($cuisines1) || empty($cuisines2)) {
            return 0;
        }

        $cuisines1 = array_map('strtolower', $cuisines1);
        $cuisines2 = array_map('strtolower', $cuisines2);

        $cuisineVariations = [
            'italian' => ['italian', 'mediterranean', 'tuscan', 'sicilian', 'roman'],
            'mexican' => ['mexican', 'tex-mex', 'latin american'],
            'chinese' => ['chinese', 'cantonese', 'sichuan', 'hunan', 'dim sum'],
            'japanese' => ['japanese', 'sushi', 'ramen', 'izakaya'],
            'indian' => ['indian', 'curry', 'spicy', 'tandoori'],
            'thai' => ['thai', 'thailand', 'southeast asian'],
            'american' => ['american', 'southern', 'cajun', 'creole'],
            'mediterranean' => ['mediterranean', 'greek', 'turkish', 'lebanese'],
            'french' => ['french', 'provencal', 'bistro'],
            'korean' => ['korean', 'bbq', 'kimchi']
        ];

        $score = 0;
        $totalCuisines = count($cuisines1);

        foreach ($cuisines1 as $cuisine1) {
            $maxSimilarity = 0;
            foreach ($cuisines2 as $cuisine2) {
                if ($cuisine1 === $cuisine2) {
                    $maxSimilarity = 1;
                    break;
                }

                foreach ($cuisineVariations as $mainCuisine => $variations) {
                    if (in_array($cuisine1, $variations) && in_array($cuisine2, $variations)) {
                        $maxSimilarity = 0.8;
                        break 2;
                    }
                }

                if (stripos($cuisine1, $cuisine2) !== false || stripos($cuisine2, $cuisine1) !== false) {
                    $maxSimilarity = max($maxSimilarity, 0.6);
                }
            }
            $score += $maxSimilarity;
        }

        return $score / $totalCuisines;
    }

    private function calculateDifficultySimilarity($difficulty1, $difficulty2)
    {
        $difficulties = ['easy' => 1, 'medium' => 2, 'hard' => 3];
        $diff1 = $difficulties[$difficulty1] ?? 2;
        $diff2 = $difficulties[$difficulty2] ?? 2;

        return 1 - (abs($diff1 - $diff2) / 2);
    }

    private function calculateTimeSimilarity($time1, $time2)
    {
        $maxTime = 60; 
        return 1 - (abs($time1 - $time2) / $maxTime);
    }

    private function calculateTagSimilarity($tags1, $tags2)
    {
        if (empty($tags1) || empty($tags2)) {
            return 0;
        }

        $commonTags = array_intersect($tags1, $tags2);
        return count($commonTags) / max(count($tags1), count($tags2));
    }

    private function calculatePopularitySimilarity($score1, $score2)
    {
        return 1 - abs($score1 - $score2);
    }

    private function calculateComplexityScore($recipe)
    {
        $ingredientCount = count($recipe['ingredients']);
        $instructionCount = count($recipe['instructions']);
        $difficultyWeight = $this->getDifficultyWeight($recipe['difficulty']);

        return (($ingredientCount * 0.4) + ($instructionCount * 0.4) + ($difficultyWeight * 0.2)) / 10;
    }

    private function getDifficultyWeight($difficulty)
    {
        return [
            'easy' => 1,
            'medium' => 2,
            'hard' => 3
        ][$difficulty] ?? 2;
    }

    private function calculateSeasonalScore($recipe)
    {
        $currentSeason = $this->getCurrentSeason();
        $seasonalIngredients = $this->getSeasonalIngredients($currentSeason);
        
        $recipeIngredients = is_array($recipe['ingredients']) ? $recipe['ingredients'] : json_decode($recipe['ingredients'], true);
        
        $score = 0;
        $totalIngredients = count($recipeIngredients);
        
        if ($totalIngredients === 0) {
            return 0;
        }

        foreach ($seasonalIngredients as $category => $ingredients) {
            $matches = 0;
            foreach ($ingredients as $ingredient) {
                foreach ($recipeIngredients as $recipeIngredient) {
                    if (stripos($recipeIngredient, $ingredient) !== false) {
                        $matches++;
                        break;
                    }
                }
            }
          
            $categoryWeight = [
                'vegetables' => 0.5,
                'fruits' => 0.3,
                'herbs' => 0.2
            ];
            $score += ($matches / count($ingredients)) * $categoryWeight[$category];
        }

        return $score;
    }

    private function getCurrentSeason()
    {
        $month = date('n');
        if ($month >= 3 && $month <= 5) return 'spring';
        if ($month >= 6 && $month <= 8) return 'summer';
        if ($month >= 9 && $month <= 11) return 'fall';
        return 'winter';
    }

    private function getSeasonalIngredients($season)
    {
        $seasonalIngredients = [
            'spring' => [
                'vegetables' => ['asparagus', 'peas', 'radishes', 'spinach', 'artichokes', 'fava beans', 'green beans', 'lettuce', 'spring onions', 'watercress'],
                'fruits' => ['strawberries', 'rhubarb', 'apricots', 'cherries', 'pineapple'],
                'herbs' => ['mint', 'parsley', 'chives', 'dill', 'basil']
            ],
            'summer' => [
                'vegetables' => ['tomatoes', 'corn', 'zucchini', 'eggplant', 'bell peppers', 'cucumbers', 'green beans', 'okra', 'summer squash'],
                'fruits' => ['berries', 'peaches', 'watermelon', 'cantaloupe', 'plums', 'nectarines', 'cherries'],
                'herbs' => ['basil', 'oregano', 'thyme', 'rosemary', 'sage']
            ],
            'fall' => [
                'vegetables' => ['pumpkin', 'squash', 'sweet potatoes', 'brussels sprouts', 'cauliflower', 'broccoli', 'kale', 'cabbage', 'turnips', 'parsnips'],
                'fruits' => ['apples', 'pears', 'cranberries', 'pomegranates', 'persimmons', 'quince'],
                'herbs' => ['sage', 'rosemary', 'thyme', 'parsley']
            ],
            'winter' => [
                'vegetables' => ['citrus', 'kale', 'brussels sprouts', 'root vegetables', 'winter squash', 'cabbage', 'leeks', 'celery root', 'beets', 'carrots'],
                'fruits' => ['citrus', 'apples', 'pears', 'pomegranates', 'kiwi'],
                'herbs' => ['rosemary', 'thyme', 'sage', 'bay leaves']
            ]
        ];
        
        return $seasonalIngredients[$season] ?? [];
    }

    private function calculateIngredientMatchScore($recipe, $ingredients)
    {
        $recipeIngredients = is_array($recipe->ingredients) ? $recipe->ingredients : json_decode($recipe->ingredients, true);
        if (!is_array($recipeIngredients)) {
            return 0;
        }

        $recipeIngredients = array_map(function($ingredient) {
            return strtolower(trim($ingredient));
        }, $recipeIngredients);
        
        $searchIngredients = array_map(function($ingredient) {
            return strtolower(trim($ingredient));
        }, $ingredients);

        $matches = 0;
        $totalWeight = count($searchIngredients);
        
        if ($totalWeight === 0) {
            return 0;
        }

        $matchedIngredients = [];

        foreach ($searchIngredients as $searchIngredient) {
            $bestMatch = 0;
            foreach ($recipeIngredients as $recipeIngredient) {
                if ($recipeIngredient === $searchIngredient) {
                    $bestMatch = 1;
                    break;
                }
                if (str_contains($recipeIngredient, $searchIngredient) || 
                    str_contains($searchIngredient, $recipeIngredient)) {
                    $bestMatch = 0.8;
                }
            }
            if ($bestMatch > 0) {
                $matches += $bestMatch;
                $matchedIngredients[] = $searchIngredient;
            }
        }

        return $matches / $totalWeight;
    }

    private function calculatePreferenceScore($recipe)
    {
        $score = 0;
        $weights = [
            'cuisine' => 0.4,
            'skill_level' => 0.3,
            'dietary' => 0.3
        ];

        if (!empty($this->userPreferences['preferred_cuisines'])) {
            $recipeCuisines = is_array($recipe->cuisines) ? $recipe->cuisines : json_decode($recipe->cuisines, true);
            $commonCuisines = array_intersect(
                array_map('strtolower', $this->userPreferences['preferred_cuisines']),
                array_map('strtolower', $recipeCuisines ?? [])
            );
            $score += (count($commonCuisines) / count($this->userPreferences['preferred_cuisines'])) * $weights['cuisine'];
        }

        $skillLevel = strtolower($this->userPreferences['cooking_skill_level'] ?? 'beginner');
        $difficulty = strtolower($recipe->difficulty ?? 'medium');
        
        $skillLevelScores = [
            'beginner' => ['easy' => 1.0, 'medium' => 0.3, 'hard' => 0.0],
            'intermediate' => ['easy' => 0.5, 'medium' => 1.0, 'hard' => 0.5],
            'advanced' => ['easy' => 0.3, 'medium' => 0.7, 'hard' => 1.0]
        ];

        $score += ($skillLevelScores[$skillLevel][$difficulty] ?? 0) * $weights['skill_level'];

        if (!empty($this->userPreferences['dietary_restrictions'])) {
            $dietaryScore = $this->calculateDietaryScore($recipe);
            $score += $dietaryScore * $weights['dietary'];
        }

        return $score;
    }

    private function calculateDietaryScore($recipe)
    {
        $score = 1.0;
        $dietaryRestrictions = $this->userPreferences['dietary_restrictions'] ?? [];
        
        if (empty($dietaryRestrictions)) {
            return $score;
        }

        $recipeIngredients = is_array($recipe->ingredients) ? $recipe->ingredients : json_decode($recipe->ingredients, true);
        
        $restrictedIngredients = [
            'Vegetarian' => ['beef', 'pork', 'chicken', 'lamb', 'fish', 'seafood', 'meat'],
            'Vegan' => ['beef', 'pork', 'chicken', 'lamb', 'fish', 'seafood', 'meat', 'milk', 'cheese', 'yogurt', 'butter', 'cream', 'eggs', 'honey'],
            'Gluten-Free' => ['wheat', 'barley', 'rye', 'bread', 'pasta', 'flour'],
            'Dairy-Free' => ['milk', 'cheese', 'yogurt', 'butter', 'cream'],
            'Nut-Free' => ['peanuts', 'almonds', 'walnuts', 'cashews', 'pecans', 'hazelnuts'],
            'Halal' => ['pork', 'alcohol', 'wine', 'beer'],
            'Kosher' => ['pork', 'shellfish', 'mixing meat and dairy']
        ];

        foreach ($dietaryRestrictions as $restriction) {
            if (isset($restrictedIngredients[$restriction])) {
                foreach ($restrictedIngredients[$restriction] as $ingredient) {
                    foreach ($recipeIngredients as $recipeIngredient) {
                        if (stripos($recipeIngredient, $ingredient) !== false) {
                            return 0; // Recipe contains restricted ingredient
                        }
                    }
                }
            }
        }

        return $score;
    }

    public function getRecommendations(User $user, array $ingredients)
    {
        try {
            $this->initializeRecipeDatabase();
            $this->loadUserData($user);

            $recipes = Recipe::where('source', 'ai')
                ->with(['ratings', 'favoritedBy'])
                ->get();

            $recommendations = [];
            $filteredOut = [
                'ingredients' => 0,
                'dietary' => 0,
                'skill_level' => 0,
                'cuisine' => 0
            ];

            foreach ($recipes as $recipe) {
                $features = $this->extractFeatures($recipe);
                $prediction = $this->calculateMLScore($recipe, $ingredients);
                
                if ($prediction === 0) {
                    continue;
                }

                $ingredientScore = empty($ingredients) ? 1 : $this->calculateIngredientMatchScore($recipe, $ingredients);
                
                if (!empty($ingredients) && $ingredientScore <= 0) {
                    $filteredOut['ingredients']++;
                    continue;
                }

                if (!empty($this->userPreferences['dietary_restrictions'])) {
                    $dietaryScore = $this->calculateDietaryScore($recipe);
                    if ($dietaryScore === 0) {
                        $filteredOut['dietary']++;
                        continue;
                    }
                }

                if (!empty($this->userPreferences['cooking_skill_level'])) {
                    $skillLevel = strtolower($this->userPreferences['cooking_skill_level']);
                    $difficulty = strtolower($recipe->difficulty);
                    if (!$this->isRecipeSuitableForSkillLevel($skillLevel, $difficulty)) {
                        $filteredOut['skill_level']++;
                        continue;
                    }
                }

                $aiConfidenceScore = $this->calculateAIConfidenceScore($recipe, $ingredients);
                $mlScore = $this->calculateMLScore($recipe, $ingredients);

                $recommendations[] = [
                    'recipe' => $recipe,
                    'score' => $ingredientScore,
                    'normalized_score' => ($aiConfidenceScore * 0.6) + ($mlScore * 0.4)
                ];
            }

            if (empty($recommendations)) {
                $errorMessage = $this->generateErrorMessage($filteredOut);
                Log::info('No recommendations found', [
                    'user_id' => $user->id,
                    'ingredients' => $ingredients,
                    'filtered_out' => $filteredOut,
                    'message' => $errorMessage
                ]);
                return [];
            }

            $recommendations = $this->rankRecommendations($recommendations);
            return $recommendations;
        } catch (\Exception $e) {
            Log::error('AI Recommendation Error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'ingredients' => $ingredients,
                'filtered_out' => $filteredOut ?? []
            ]);
            throw $e;
        }
    }

    private function loadUserData(User $user)
    {
        $this->userPreferences = $user->preferences ? $user->preferences->toArray() : [];
        $this->cookingHistory = $user->cookingHistory()->with('recipe')->get()->toArray();
    }

    private function extractFeatures($recipe)
    {
        $ingredients = is_array($recipe['ingredients']) ? $recipe['ingredients'] : json_decode($recipe['ingredients'], true);
        $instructions = is_array($recipe['instructions']) ? $recipe['instructions'] : json_decode($recipe['instructions'], true);
        
        return [
            'ingredient_count' => count($ingredients),
            'instruction_count' => count($instructions),
            'cooking_time' => $this->normalizeCookingTime($recipe['cooking_time']),
            'difficulty_score' => $this->getDifficultyScore($recipe['difficulty']),
            'ingredient_complexity' => $this->calculateIngredientComplexity($ingredients),
            'instruction_complexity' => $this->calculateInstructionComplexity($instructions)
        ];
    }

    private function normalizeCookingTime($time)
    {
        $time = preg_replace('/[^0-9]/', '', $time);
        return min(1, $time / 120);
    }

    private function getDifficultyScore($difficulty)
    {
        return [
            'easy' => 0.33,
            'medium' => 0.66,
            'hard' => 1.0
        ][strtolower($difficulty)] ?? 0.5;
    }

    private function calculateIngredientComplexity($ingredients)
    {
        $complexity = 0;
        foreach ($ingredients as $ingredient) {
            $complexity += $this->getIngredientComplexityScore($ingredient);
        }
        return $complexity / count($ingredients);
    }

    private function calculateInstructionComplexity($instructions)
    {
        $complexity = 0;
        foreach ($instructions as $instruction) {
            $complexity += $this->getInstructionComplexityScore($instruction);
        }
        return $complexity / count($instructions);
    }

    private function getIngredientComplexityScore($ingredient)
    {
        $complexIngredients = [
            'saffron', 'truffle', 'foie gras', 'quinoa', 'kombu', 'miso',
            'tamarind', 'galangal', 'lemongrass', 'kaffir lime'
        ];
        
        return in_array(strtolower($ingredient), $complexIngredients) ? 1 : 0.5;
    }

    private function getInstructionComplexityScore($instruction)
    {
        $complexTechniques = [
            'sous vide', 'braise', 'confit', 'temper', 'clarify', 'emulsify',
            'reduce', 'deglaze', 'poach', 'sauté'
        ];
        
        $score = 0.5;
        foreach ($complexTechniques as $technique) {
            if (stripos($instruction, $technique) !== false) {
                $score += 0.5;
            }
        }
        return min(1, $score);
    }

    private function isRecipeSuitableForSkillLevel($skillLevel, $difficulty)
    {
        $skillLevels = [
            'beginner' => ['easy'],
            'intermediate' => ['easy', 'medium'],
            'advanced' => ['easy', 'medium', 'hard']
        ];

        return in_array($difficulty, $skillLevels[$skillLevel] ?? []);
    }

    private function calculateAIConfidenceScore($recipe, $ingredients)
    {
        $scores = [];
        
        // Ingredient matching confidence
        $scores[] = $this->calculateIngredientMatchScore($recipe, $ingredients);
        
        // Recipe complexity confidence
        $scores[] = $this->calculateComplexityScore($recipe);
        
        // User preference matching confidence
        $scores[] = $this->calculatePreferenceScore($recipe);
        
        // Seasonal relevance confidence
        if ($this->userPreferences['seasonal_preferences'] ?? false) {
            $scores[] = $this->calculateSeasonalScore($recipe);
        }
        
        // Calculate weighted average
        $weights = [0.4, 0.2, 0.2, 0.2]; // Adjust weights based on importance
        $weightedSum = 0;
        $weightSum = 0;
        
        foreach ($scores as $index => $score) {
            if (isset($weights[$index])) {
                $weightedSum += $score * $weights[$index];
                $weightSum += $weights[$index];
            }
        }
        
        return $weightSum > 0 ? $weightedSum / $weightSum : 0;
    }

    private function rankRecommendations(array $recommendations)
    {
        if (empty($recommendations)) {
            Log::warning('No recommendations to rank');
            return [];
        }

        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $topRecommendations = array_slice($recommendations, 0, 15);

        $maxScore = max(array_column($topRecommendations, 'score'));
        if ($maxScore > 0) {
            foreach ($topRecommendations as &$recommendation) {
                $recommendation['normalized_score'] = $recommendation['score'] / $maxScore;
            }
        } else {
            foreach ($topRecommendations as &$recommendation) {
                $recommendation['normalized_score'] = 0;
            }
        }

        return $topRecommendations;
    }

    private function getBasicRecipes(array $ingredients)
    {
        $basicRecipes = Recipe::where('source', 'ai')
            ->where(function($query) use ($ingredients) {
                foreach ($ingredients as $ingredient) {
                    $query->orWhere(function($q) use ($ingredient) {
                        $q->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(ingredients, "$[*]"))) LIKE ?', ['%' . strtolower($ingredient) . '%']);
                    });
                }
            })
            ->with(['ratings', 'favoritedBy'])
            ->limit(5)
            ->get()
            ->toArray();

        if (empty($basicRecipes)) {
            $basicRecipes = Recipe::where('source', 'ai')
                ->with(['ratings', 'favoritedBy'])
                ->inRandomOrder()
                ->limit(5)
                ->get()
                ->toArray();
        }

        return array_map(function($recipe) {
            return [
                'recipe' => $recipe,
                'score' => 0.5, 
                'normalized_score' => 0.5
            ];
        }, $basicRecipes);
    }

    private function generateErrorMessage($filteredOut)
    {
        $messages = [];
        
        if ($filteredOut['ingredients'] > 0) {
            $messages[] = 'Try different ingredients';
        }
        if ($filteredOut['dietary'] > 0) {
            $messages[] = 'No recipes match your dietary restrictions';
        }
        if ($filteredOut['skill_level'] > 0) {
            $messages[] = 'No recipes match your cooking skill level';
        }
        if ($filteredOut['cuisine'] > 0) {
            $messages[] = 'No recipes match your preferred cuisines';
        }
        
        return 'No recipes found that match your criteria. ' . implode('. ', $messages);
    }
} 