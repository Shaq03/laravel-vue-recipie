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
    private const MIN_SIMILARITY_THRESHOLD = 0.85; // Threshold to consider recipes too similar
    private const DIVERSITY_PENALTY = 0.2; // Penalty for similar recipes to promote variety

    public function __construct()
    {
        $this->initializeRecipeDatabase();
        $this->buildSimilarityMatrices();
    }

    private function initializeRecipeDatabase()
    {
        // Load only AI-generated recipes
        $this->recipeDatabase = Recipe::where('source', 'ai')
            ->with(['ratings', 'favoritedBy'])
            ->get()
            ->toArray();
    }

    private function buildSimilarityMatrices()
    {
        // Build ingredient similarity matrix
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

        // Build recipe similarity matrix
        foreach ($this->recipeDatabase as $recipe1) {
            foreach ($this->recipeDatabase as $recipe2) {
                if ($recipe1['id'] !== $recipe2['id']) {
                    $this->recipeSimilarityMatrix[$recipe1['id']][$recipe2['id']] = 
                        $this->calculateRecipeSimilarity($recipe1, $recipe2);
                }
            }
        }
    }

    public function getRecommendations(User $user, array $ingredients)
    {
        try {
            // Reload recipe database to ensure we have the latest data
            $this->initializeRecipeDatabase();
            
            // Get user preferences
            $this->loadUserData($user);

            // Get all AI recipes
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
                // Skip if no ingredients provided and recipe doesn't match user preferences
                if (empty($ingredients) && !empty($this->userPreferences)) {
                    // First check cuisine preferences if set
                    if (!empty($this->userPreferences['preferred_cuisines'])) {
                        $recipeCuisines = is_array($recipe->cuisines) ? $recipe->cuisines : json_decode($recipe->cuisines, true);
                        $hasMatchingCuisine = false;
                        foreach ($this->userPreferences['preferred_cuisines'] as $preferredCuisine) {
                            if (in_array(strtolower($preferredCuisine), array_map('strtolower', $recipeCuisines))) {
                                $hasMatchingCuisine = true;
                                break;
                            }
                        }
                        if (!$hasMatchingCuisine) {
                            $filteredOut['cuisine']++;
                            continue;
                        }
                    }
                }

                // Calculate ingredient match score if ingredients provided
                $ingredientScore = empty($ingredients) ? 1 : $this->calculateIngredientMatchScore($recipe, $ingredients);
                
                // Skip recipes with no ingredient matches if ingredients were provided
                if (!empty($ingredients) && $ingredientScore <= 0) {
                    $filteredOut['ingredients']++;
                    continue;
                }

                // Strictly check dietary restrictions
                if (!empty($this->userPreferences['dietary_restrictions'])) {
                    $dietaryScore = $this->calculateDietaryScore($recipe);
                    if ($dietaryScore === 0) {
                        $filteredOut['dietary']++;
                        continue;
                    }
                }

                // Check difficulty level if user preference exists
                if (!empty($this->userPreferences['cooking_skill_level'])) {
                    $skillLevel = strtolower($this->userPreferences['cooking_skill_level']);
                    $difficulty = strtolower($recipe->difficulty);
                    if (!$this->isRecipeSuitableForSkillLevel($skillLevel, $difficulty)) {
                        $filteredOut['skill_level']++;
                        continue;
                    }
                }

                $recommendations[] = [
                    'recipe' => $recipe,
                    'score' => $ingredientScore,
                    'normalized_score' => 0 // Will be normalized later
                ];
            }

            // Log if no recommendations found
            if (empty($recommendations)) {
                $errorMessage = 'No recipes found that match your criteria. ';
                
                if ($filteredOut['ingredients'] > 0) {
                    $errorMessage .= 'Try different ingredients. ';
                }
                if ($filteredOut['dietary'] > 0) {
                    $errorMessage .= 'No recipes match your dietary restrictions. ';
                }
                if ($filteredOut['skill_level'] > 0) {
                    $errorMessage .= 'No recipes match your cooking skill level. ';
                }
                if ($filteredOut['cuisine'] > 0) {
                    $errorMessage .= 'No recipes match your preferred cuisines. ';
                }

                Log::info('No recommendations found', [
                    'user_id' => $user->id,
                    'ingredients' => $ingredients,
                    'filtered_out' => $filteredOut,
                    'message' => $errorMessage
                ]);

                return [];
            }

            // Sort by score
            usort($recommendations, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Normalize scores
            if (!empty($recommendations)) {
                $maxScore = max(array_column($recommendations, 'score'));
                foreach ($recommendations as &$recommendation) {
                    $recommendation['normalized_score'] = $recommendation['score'] / $maxScore;
                }
            }

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
        // Load user preferences
        $this->userPreferences = $user->preferences ? $user->preferences->toArray() : [];

        // Load cooking history
        $this->cookingHistory = $user->cookingHistory()->with('recipe')->get()->toArray();
    }

    private function calculateIngredientMatchScore($recipe, $ingredients)
    {
        $recipeIngredients = is_array($recipe->ingredients) ? $recipe->ingredients : json_decode($recipe->ingredients, true);
        if (!is_array($recipeIngredients)) {
            return 0;
        }

        // Normalize ingredients for comparison
        $recipeIngredients = array_map(function($ingredient) {
            return strtolower(trim($ingredient));
        }, $recipeIngredients);
        
        $searchIngredients = array_map(function($ingredient) {
            return strtolower(trim($ingredient));
        }, $ingredients);

        $matches = 0;
        $totalWeight = count($searchIngredients);
        
        // Return 0 if no ingredients to match against
        if ($totalWeight === 0) {
            return 0;
        }

        $matchedIngredients = [];

        foreach ($searchIngredients as $searchIngredient) {
            $bestMatch = 0;
            foreach ($recipeIngredients as $recipeIngredient) {
                // Exact match
                if ($recipeIngredient === $searchIngredient) {
                    $bestMatch = 1;
                    break;
                }
                // Contains match
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

        // Calculate final score
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

        // Check cuisine preferences
        if (!empty($this->userPreferences['preferred_cuisines'])) {
            $recipeCuisines = is_array($recipe->cuisines) ? $recipe->cuisines : json_decode($recipe->cuisines, true);
            $commonCuisines = array_intersect(
                array_map('strtolower', $this->userPreferences['preferred_cuisines']),
                array_map('strtolower', $recipeCuisines ?? [])
            );
            $score += (count($commonCuisines) / count($this->userPreferences['preferred_cuisines'])) * $weights['cuisine'];
        }

        // Check cooking skill level
        $skillLevel = strtolower($this->userPreferences['cooking_skill_level'] ?? 'beginner');
        $difficulty = strtolower($recipe->difficulty ?? 'medium');
        
        $skillLevelScores = [
            'beginner' => ['easy' => 1.0, 'medium' => 0.3, 'hard' => 0.0],
            'intermediate' => ['easy' => 0.5, 'medium' => 1.0, 'hard' => 0.5],
            'advanced' => ['easy' => 0.3, 'medium' => 0.7, 'hard' => 1.0]
        ];

        $score += ($skillLevelScores[$skillLevel][$difficulty] ?? 0) * $weights['skill_level'];

        // Check dietary restrictions
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
        
        // Define restricted ingredients for each dietary restriction
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

    private function calculateIngredientSetSimilarity($ingredients1, $ingredients2)
    {
        if (empty($ingredients1) || empty($ingredients2)) {
            return 0;
        }

        // Ensure ingredients are arrays
        $ingredients1 = is_string($ingredients1) ? json_decode($ingredients1, true) : $ingredients1;
        $ingredients2 = is_string($ingredients2) ? json_decode($ingredients2, true) : $ingredients2;

        if (!is_array($ingredients1) || !is_array($ingredients2)) {
            return 0;
        }

        // Calculate Jaccard similarity
        $intersection = array_intersect($ingredients1, $ingredients2);
        $union = array_unique(array_merge($ingredients1, $ingredients2));
        
        return count($intersection) / count($union);
    }

    private function calculateCuisineSimilarity($cuisines1, $cuisines2)
    {
        if (empty($cuisines1) || empty($cuisines2)) {
            return 0;
        }

        // Normalize cuisines to lowercase
        $cuisines1 = array_map('strtolower', $cuisines1);
        $cuisines2 = array_map('strtolower', $cuisines2);

        // Define cuisine variations and synonyms
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
                // Check direct match
                if ($cuisine1 === $cuisine2) {
                    $maxSimilarity = 1;
                    break;
                }

                // Check variations
                foreach ($cuisineVariations as $mainCuisine => $variations) {
                    if (in_array($cuisine1, $variations) && in_array($cuisine2, $variations)) {
                        $maxSimilarity = 0.8;
                        break 2;
                    }
                }

                // Check partial match
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
        $maxTime = 60; // Maximum cooking time in minutes
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

    private function applyContentBasedFiltering(array $recommendations)
    {
        $filtered = [];
        foreach ($recommendations as $recommendation) {
            $recipe = $recommendation['recipe'];
            $score = $recommendation['score'];

            // Apply content-based scoring
            $contentScore = $this->calculateContentScore($recipe);
            $finalScore = ($score * 0.7) + ($contentScore * 0.3);

            $filtered[] = [
                'recipe' => $recipe,
                'score' => $finalScore
            ];
        }

        return $filtered;
    }

    private function calculateContentScore($recipe)
    {
        $score = 0;

        // Consider recipe popularity
        $score += ($recipe['popularity_score'] ?? 0) * 0.3;

        // Consider average rating
        $score += ($recipe['average_rating'] ?? 0) * 0.3;

        // Consider recipe complexity
        $complexityScore = $this->calculateComplexityScore($recipe);
        $score += $complexityScore * 0.4;

        return $score;
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

    private function applyCollaborativeFiltering(array $recommendations)
    {
        $filtered = [];
        foreach ($recommendations as $recommendation) {
            $recipe = $recommendation['recipe'];
            $score = $recommendation['score'];

            // Apply collaborative filtering
            $collaborativeScore = $this->calculateCollaborativeScore($recipe);
            $finalScore = ($score * 0.6) + ($collaborativeScore * 0.4);

            $filtered[] = [
                'recipe' => $recipe,
                'score' => $finalScore
            ];
        }

        return $filtered;
    }

    private function calculateCollaborativeScore($recipe)
    {
        $score = 0;
        $similarRecipes = $this->getSimilarRecipes($recipe['id'], 5);
        $totalSimilarity = 0;

        foreach ($similarRecipes as $similarRecipeId => $similarity) {
            $similarRecipe = collect($this->recipeDatabase)->firstWhere('id', $similarRecipeId);
            if ($similarRecipe) {
                $score += $similarity * ($similarRecipe['average_rating'] ?? 0);
                $totalSimilarity += $similarity;
            }
        }

        return $totalSimilarity > 0 ? $score / $totalSimilarity : 0;
    }

    private function getSimilarRecipes($recipeId, $limit)
    {
        $similarities = [];
        foreach ($this->recipeDatabase as $recipe) {
            if ($recipe['id'] !== $recipeId) {
                $similarities[$recipe['id']] = $this->calculateRecipeSimilarity(
                    $this->recipeDatabase[$recipeId] ?? $recipe,
                    $recipe
                );
            }
        }
        
        arsort($similarities);
        return array_slice($similarities, 0, $limit, true);
    }

    private function applyPersonalization(array $recommendations)
    {
        $personalized = [];
        $filteredOut = [
            'skill_level' => 0,
            'dietary' => 0,
            'cuisine' => 0,
            'seasonal' => 0
        ];

        foreach ($recommendations as $recommendation) {
            $recipe = $recommendation['recipe'];
            $score = $recommendation['score'];

            // Apply personalization
            $personalizationScore = $this->calculatePersonalizationScore($recipe);
            
            // Strictly filter based on cooking skill level
            $skillLevel = $this->userPreferences['cooking_skill_level'] ?? 'beginner';
            $difficulty = $recipe['difficulty'] ?? 'medium';
            
            // Skip recipes that don't match the user's skill level
            if (!$this->isRecipeSuitableForSkillLevel($skillLevel, $difficulty)) {
                $filteredOut['skill_level']++;
                continue;
            }

            // Log detailed preference scores
            Log::debug('Recipe Preference Scores', [
                'recipe_id' => $recipe['id'],
                'title' => $recipe['title'],
                'skill_level_match' => $this->isRecipeSuitableForSkillLevel($skillLevel, $difficulty),
                'dietary_score' => $this->calculateDietaryScore($recipe),
                'cuisine_score' => $this->calculateCuisineSimilarity(
                    $this->userPreferences['preferred_cuisines'] ?? [],
                    $recipe['cuisines'] ?? []
                ),
                'seasonal_score' => $this->calculateSeasonalScore($recipe),
                'final_personalization_score' => $personalizationScore
            ]);

            $finalScore = ($score * 0.5) + ($personalizationScore * 0.5);

            $personalized[] = [
                'recipe' => $recipe,
                'score' => $finalScore
            ];
        }

        // Log filtering statistics
        Log::info('Preference-based Filtering Results', [
            'total_recipes' => count($recommendations),
            'filtered_out' => $filteredOut,
            'remaining_recipes' => count($personalized)
        ]);

        return $personalized;
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

    private function calculatePersonalizationScore($recipe)
    {
        $score = 0;
        $weights = [
            'preferences' => 0.4,
            'history' => 0.3,
            'dietary' => 0.3
        ];

        // Consider user preferences with higher weight
        if (!empty($this->userPreferences)) {
            $preferenceScore = $this->calculatePreferenceScore($recipe);
            $score += $preferenceScore * $weights['preferences'];
        }

        // Consider cooking history
        if (!empty($this->cookingHistory)) {
            $historyScore = $this->calculateHistoryScore($recipe);
            $score += $historyScore * $weights['history'];
        }

        // Consider dietary restrictions with strict filtering
        if (!empty($this->userPreferences['dietary_restrictions'])) {
            $dietaryScore = $this->calculateDietaryScore($recipe);
            // If recipe doesn't meet dietary restrictions, return 0
            if ($dietaryScore === 0) {
                return 0;
            }
            $score += $dietaryScore * $weights['dietary'];
        }

        return $score;
    }

    private function calculateHistoryScore($recipe)
    {
        $score = 0;
        $count = 0;

        foreach ($this->cookingHistory as $history) {
            if (isset($history['recipe'])) {
                $similarity = $this->calculateRecipeSimilarity($recipe, $history['recipe']);
                $score += $similarity;
                $count++;
            }
        }

        return $count > 0 ? $score / $count : 0;
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

        // Check each category of seasonal ingredients
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
            // Weight different categories
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

    private function applyDiversityFiltering(array $recommendations)
    {
        $filtered = [];
        $seenRecipes = [];

        foreach ($recommendations as $recommendation) {
            $recipe = $recommendation['recipe'];
            $score = $recommendation['score'];
            $isTooSimilar = false;

            // Check similarity with already selected recipes
            foreach ($seenRecipes as $seenRecipe) {
                $similarity = $this->calculateRecipeSimilarity($recipe, $seenRecipe);
                if ($similarity > self::MIN_SIMILARITY_THRESHOLD) {
                    $isTooSimilar = true;
                    break;
                }
                // Apply diversity penalty based on similarity
                $score *= (1 - ($similarity * self::DIVERSITY_PENALTY));
            }

            if (!$isTooSimilar) {
                $filtered[] = [
                    'recipe' => $recipe,
                    'score' => $score,
                    'similarity_scores' => $this->calculateSimilarityScores($recipe)
                ];
                $seenRecipes[] = $recipe;
            }
        }

        return $filtered;
    }

    private function calculateSimilarityScores($recipe)
    {
        $scores = [];
        
        // Calculate similarity with user's favorite recipes
        if (!empty($this->userPreferences['favorite_recipes'])) {
            foreach ($this->userPreferences['favorite_recipes'] as $favoriteRecipe) {
                $scores['favorites'][] = [
                    'recipe_id' => $favoriteRecipe['id'],
                    'title' => $favoriteRecipe['title'],
                    'similarity' => $this->calculateRecipeSimilarity($recipe, $favoriteRecipe)
                ];
            }
        }

        // Calculate similarity with recently cooked recipes
        if (!empty($this->cookingHistory)) {
            foreach (array_slice($this->cookingHistory, 0, 5) as $historyItem) {
                if (isset($historyItem['recipe'])) {
                    $scores['history'][] = [
                        'recipe_id' => $historyItem['recipe']['id'],
                        'title' => $historyItem['recipe']['title'],
                        'similarity' => $this->calculateRecipeSimilarity($recipe, $historyItem['recipe'])
                    ];
                }
            }
        }

        return $scores;
    }

    private function rankRecommendations(array $recommendations)
    {
        // If no recommendations, return empty array
        if (empty($recommendations)) {
            Log::warning('No recommendations to rank');
            return [];
        }

        // Sort by score in descending order
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Return top 15 recommendations with similarity scores
        $topRecommendations = array_slice($recommendations, 0, 15);

        // Add normalized scores
        $maxScore = max(array_column($topRecommendations, 'score'));
        if ($maxScore > 0) {
            foreach ($topRecommendations as &$recommendation) {
                $recommendation['normalized_score'] = $recommendation['score'] / $maxScore;
            }
        } else {
            // If all scores are 0, set normalized score to 0
            foreach ($topRecommendations as &$recommendation) {
                $recommendation['normalized_score'] = 0;
            }
        }

        return $topRecommendations;
    }

    private function getBasicRecipes(array $ingredients)
    {
        // Get some basic recipes that use common ingredients
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

        // If no recipes found, get some random AI recipes
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
                'score' => 0.5, // Basic score for fallback recipes
                'normalized_score' => 0.5
            ];
        }, $basicRecipes);
    }
} 