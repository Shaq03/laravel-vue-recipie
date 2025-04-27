<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;

class RecipeScraperService
{
    /**
     * Search for recipes based on ingredients
     * 
     * @param array $ingredients
     * @return array
     */
    public function searchRecipesByIngredients(array $ingredients): array
    {
        $ingredientsString = implode('+', $ingredients);
        $cacheKey = 'recipe_search_' . md5($ingredientsString);
        
        // Try to get from cache first
        if ($cachedResults = Cache::get($cacheKey)) {
            return $cachedResults;
        }
        
        $recipes = [];
        
        try {
            // Search from multiple sources for better results
            $allRecipesResults = $this->searchAllRecipes($ingredients);
            $recipes = array_merge($recipes, $allRecipesResults);
            
            // Search from Food Network
            $foodNetworkResults = $this->searchFoodNetwork($ingredients);
            $recipes = array_merge($recipes, $foodNetworkResults);
            
            // Search from Epicurious
            $epicuriousResults = $this->searchEpicurious($ingredients);
            $recipes = array_merge($recipes, $epicuriousResults);
            
            // If no recipes found, try fallback search
            if (empty($recipes)) {
                $fallbackResults = $this->fallbackSearch($ingredients);
                $recipes = array_merge($recipes, $fallbackResults);
            }
            
            // Cache results for 24 hours
            Cache::put($cacheKey, $recipes, 60 * 24);
            
            return $recipes;
        } catch (\Exception $e) {
            Log::error('Recipe scraping failed', [
                'ingredients' => $ingredients,
                'error' => $e->getMessage()
            ]);
            
            // Return fallback recipes if scraping fails
            return $this->getFallbackRecipes($ingredients);
        }
    }
    
    /**
     * Search AllRecipes website for recipes
     * 
     * @param array $ingredients
     * @return array
     */
    private function searchAllRecipes(array $ingredients): array
    {
        $searchQuery = implode('+', $ingredients);
        $url = "https://www.allrecipes.com/search?q=" . urlencode($searchQuery);
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
            ])->get($url);
            
            if ($response->failed()) {
                throw new \Exception('Failed to fetch from AllRecipes: ' . $response->status());
            }
            
            $html = $response->body();
            
            // Parse HTML
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $recipes = [];
            
            // Updated selectors for AllRecipes - try multiple possible selectors
            $recipeCards = $xpath->query('//div[contains(@class, "mntl-card")]');
            
            if ($recipeCards->length === 0) {
                // Try alternative selector
                $recipeCards = $xpath->query('//div[contains(@class, "component")]//a[contains(@class, "card")]/..');
            }
            
            if ($recipeCards->length === 0) {
                // Try another alternative selector
                $recipeCards = $xpath->query('//div[contains(@class, "recipe-card")]');
            }
            
            // Log the number of recipe cards found
            Log::info('Recipe cards found: ' . $recipeCards->length);
            
            foreach ($recipeCards as $index => $card) {
                if ($index >= 5) break; // Limit to 5 recipes
                
                // Try multiple possible selectors for title
                $titleNode = $xpath->query('.//span[contains(@class, "card__title")] | .//h3 | .//h2 | .//span[contains(@class, "title")]', $card)->item(0);
                
                // Try multiple possible selectors for link
                $linkNode = $xpath->query('.//a[contains(@href, "/recipe/")]', $card)->item(0);
                if (!$linkNode) {
                    $linkNode = $xpath->query('.//a', $card)->item(0);
                }
                
                // Try multiple possible selectors for image
                $imageNode = $xpath->query('.//img', $card)->item(0);
                
                if ($titleNode && $linkNode) {
                    $title = trim($titleNode->textContent);
                    $link = '';
                    $imageUrl = null;
                    
                    if ($linkNode instanceof \DOMElement) {
                        $link = $linkNode->getAttribute('href');
                    }
                    
                    if ($imageNode instanceof \DOMElement) {
                        $imageUrl = $imageNode->getAttribute('src');
                        if (empty($imageUrl)) {
                            $imageUrl = $imageNode->getAttribute('data-src');
                        }
                    }
                    
                    // Log the found recipe
                    Log::info('Found recipe: ' . $title . ' at ' . $link);
                    
                    // Get recipe details
                    $recipeDetails = $this->getRecipeDetails($link);
                    
                    $recipes[] = [
                        'title' => $title,
                        'description' => $recipeDetails['description'] ?? 'A delicious recipe using your ingredients.',
                        'cooking_time' => $recipeDetails['cooking_time'] ?? '30 mins',
                        'servings' => $recipeDetails['servings'] ?? 4,
                        'difficulty' => $recipeDetails['difficulty'] ?? 'medium',
                        'ingredients' => $recipeDetails['ingredients'] ?? $ingredients,
                        'instructions' => $recipeDetails['instructions'] ?? ['Cook according to your preference'],
                        'image_url' => $imageUrl,
                        'source_url' => $link
                    ];
                }
            }
            
            return $recipes;
        } catch (\Exception $e) {
            Log::error('AllRecipes scraping failed', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get detailed recipe information from a recipe page
     * 
     * @param string $url
     * @return array
     */
    private function getRecipeDetails(string $url): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
            ])->get($url);
            
            if ($response->failed()) {
                throw new \Exception('Failed to fetch recipe details: ' . $response->status());
            }
            
            $html = $response->body();
            
            // Parse HTML
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            // Extract recipe details - try multiple possible selectors
            $description = '';
            $descNodes = $xpath->query('//div[contains(@class, "recipe-summary")] | //div[contains(@class, "article-subheading")] | //p[contains(@class, "recipe-summary")]');
            if ($descNodes->length > 0) {
                $description = trim($descNodes->item(0)->textContent);
            }
            
            // Get cooking time - try multiple possible selectors
            $cookingTime = '30 mins';
            $timeNodes = $xpath->query('//div[contains(@class, "recipe-meta-item")][contains(., "cook")] | //div[contains(@class, "recipe-meta")][contains(., "cook")] | //span[contains(@class, "cook-time")]');
            if ($timeNodes->length > 0) {
                $cookingTime = trim($timeNodes->item(0)->textContent);
                $cookingTime = preg_replace('/[^0-9]/', '', $cookingTime) . ' mins';
            }
            
            // Get servings - try multiple possible selectors
            $servings = 4;
            $servingsNodes = $xpath->query('//div[contains(@class, "recipe-meta-item")][contains(., "serv")] | //div[contains(@class, "recipe-meta")][contains(., "serv")] | //span[contains(@class, "servings")]');
            if ($servingsNodes->length > 0) {
                $servingsText = trim($servingsNodes->item(0)->textContent);
                preg_match('/(\d+)/', $servingsText, $matches);
                if (isset($matches[1])) {
                    $servings = (int)$matches[1];
                }
            }
            
            // Get ingredients - try multiple possible selectors
            $ingredients = [];
            $ingredientNodes = $xpath->query('//ul[contains(@class, "ingredients-section")]/li | //ul[contains(@class, "ingredients")]/li | //div[contains(@class, "ingredients-item")]');
            foreach ($ingredientNodes as $node) {
                $ingredients[] = trim($node->textContent);
            }
            
            // Get instructions - try multiple possible selectors
            $instructions = [];
            $instructionNodes = $xpath->query('//div[contains(@class, "instructions-section")]/div[contains(@class, "section-body")]/p | //ol[contains(@class, "instructions")]/li | //div[contains(@class, "direction-section")]/p');
            foreach ($instructionNodes as $node) {
                $instructions[] = trim($node->textContent);
            }
            
            return [
                'description' => $description,
                'cooking_time' => $cookingTime,
                'servings' => $servings,
                'difficulty' => 'medium', // Default as it's hard to determine
                'ingredients' => !empty($ingredients) ? $ingredients : ['No ingredients found'],
                'instructions' => !empty($instructions) ? $instructions : ['No instructions found']
            ];
        } catch (\Exception $e) {
            Log::error('Recipe details scraping failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Fallback search method using a different recipe site
     * 
     * @param array $ingredients
     * @return array
     */
    private function fallbackSearch(array $ingredients): array
    {
        $searchQuery = implode('+', $ingredients);
        $url = "https://www.epicurious.com/search/" . urlencode($searchQuery);
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
            ])->get($url);
            
            if ($response->failed()) {
                throw new \Exception('Failed to fetch from Epicurious: ' . $response->status());
            }
            
            $html = $response->body();
            
            // Parse HTML
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $recipes = [];
            
            // Extract recipe cards
            $recipeCards = $xpath->query('//article[contains(@class, "recipe-content-card")]');
            
            foreach ($recipeCards as $index => $card) {
                if ($index >= 5) break; // Limit to 5 recipes
                
                $titleNode = $xpath->query('.//h4', $card)->item(0);
                $linkNode = $xpath->query('.//a', $card)->item(0);
                $imageNode = $xpath->query('.//img', $card)->item(0);
                
                if ($titleNode && $linkNode) {
                    $title = trim($titleNode->textContent);
                    $link = '';
                    $imageUrl = null;
                    
                    if ($linkNode instanceof \DOMElement) {
                        $link = 'https://www.epicurious.com' . $linkNode->getAttribute('href');
                    }
                    
                    if ($imageNode instanceof \DOMElement) {
                        $imageUrl = $imageNode->getAttribute('src');
                        if (empty($imageUrl)) {
                            $imageUrl = $imageNode->getAttribute('data-src');
                        }
                    }
                    
                    $recipes[] = [
                        'title' => $title,
                        'description' => 'A delicious recipe using your ingredients.',
                        'cooking_time' => '30 mins',
                        'servings' => 4,
                        'difficulty' => 'medium',
                        'ingredients' => $ingredients,
                        'instructions' => ['Visit the recipe page for detailed instructions.'],
                        'image_url' => $imageUrl,
                        'source_url' => $link
                    ];
                }
            }
            
            return $recipes;
        } catch (\Exception $e) {
            Log::error('Epicurious scraping failed', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Generate fallback recipes when scraping fails
     * 
     * @param array $ingredients
     * @return array
     */
    private function getFallbackRecipes(array $ingredients): array
    {
        $recipes = [];
        
        // Create some basic recipes based on common ingredients
        if (in_array('chicken', $ingredients) || in_array('chicken breast', $ingredients)) {
            $recipes[] = [
                'title' => 'Simple Chicken Stir Fry',
                'description' => 'A quick and easy chicken stir fry with your available ingredients.',
                'cooking_time' => '20 mins',
                'servings' => 4,
                'difficulty' => 'easy',
                'ingredients' => array_merge(['chicken breast', 'garlic', 'soy sauce', 'vegetable oil'], $ingredients),
                'instructions' => [
                    'Cut chicken into bite-sized pieces.',
                    'Heat oil in a large pan or wok over medium-high heat.',
                    'Add chicken and cook until browned, about 5-6 minutes.',
                    'Add vegetables and garlic, stir fry for 3-4 minutes.',
                    'Add soy sauce and other seasonings, cook for 1-2 more minutes.',
                    'Serve hot over rice or noodles.'
                ],
                'image_url' => 'https://www.allrecipes.com/thmb/7VlxGbgaZ-_z_sZUL3JEbGGlGXE=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/223382_chicken-stir-fry_Rita-1x1-1-b6b835cceb234e66be43b6ef9218b5ec.jpg',
                'source_url' => 'https://www.allrecipes.com/recipe/223382/chicken-stir-fry/'
            ];
        }
        
        if (in_array('pasta', $ingredients) || in_array('spaghetti', $ingredients)) {
            $recipes[] = [
                'title' => 'Quick Pasta with Garlic and Oil',
                'description' => 'A simple Italian pasta dish that uses minimal ingredients.',
                'cooking_time' => '15 mins',
                'servings' => 2,
                'difficulty' => 'easy',
                'ingredients' => array_merge(['pasta', 'garlic', 'olive oil', 'red pepper flakes', 'parsley'], $ingredients),
                'instructions' => [
                    'Cook pasta according to package directions.',
                    'While pasta cooks, heat olive oil in a pan over medium heat.',
                    'Add minced garlic and red pepper flakes, cook for 1-2 minutes until fragrant.',
                    'Drain pasta, reserving 1/4 cup of pasta water.',
                    'Add pasta to the pan with garlic oil, toss to coat.',
                    'Add pasta water as needed to create a light sauce.',
                    'Garnish with chopped parsley and serve.'
                ],
                'image_url' => 'https://www.allrecipes.com/thmb/oAX77jA2_3WaXl6GXrGvr4tlKJE=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/8962-spaghetti-aglio-e-olio-ddmfs-4x3-0193-1-42d37a9e36c0445aa1e5a8d6e0419d68.jpg',
                'source_url' => 'https://www.allrecipes.com/recipe/8962/spaghetti-aglio-e-olio/'
            ];
        }
        
        if (in_array('rice', $ingredients)) {
            $recipes[] = [
                'title' => 'Simple Fried Rice',
                'description' => 'A versatile fried rice recipe that works with many ingredients.',
                'cooking_time' => '25 mins',
                'servings' => 4,
                'difficulty' => 'medium',
                'ingredients' => array_merge(['rice', 'eggs', 'green onions', 'soy sauce', 'vegetable oil'], $ingredients),
                'instructions' => [
                    'Cook rice according to package directions, or use leftover cold rice.',
                    'Heat oil in a large pan or wok over medium-high heat.',
                    'Add any protein (chicken, shrimp, tofu) and cook until done.',
                    'Push protein to the side, add beaten eggs and scramble.',
                    'Add rice and any vegetables, stir fry for 3-4 minutes.',
                    'Add soy sauce and other seasonings, cook for 1-2 more minutes.',
                    'Garnish with sliced green onions and serve.'
                ],
                'image_url' => 'https://www.allrecipes.com/thmb/V1fkCYzWyw2jFayJGgaWXvYxDM8=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/79543-fried-rice-restaurant-style-DDMFS-4x3-b5a294be7a2a4c5f8095b641696a2d0a.jpg',
                'source_url' => 'https://www.allrecipes.com/recipe/79543/fried-rice-restaurant-style/'
            ];
        }
        
        // Add more fallback recipes as needed
        
        // If no specific recipes match, provide a generic one
        if (empty($recipes)) {
            $recipes[] = [
                'title' => 'Custom Recipe with Your Ingredients',
                'description' => 'A flexible recipe using the ingredients you have on hand.',
                'cooking_time' => '30 mins',
                'servings' => 4,
                'difficulty' => 'medium',
                'ingredients' => $ingredients,
                'instructions' => [
                    'Prepare all ingredients by washing, peeling, and chopping as needed.',
                    'Heat a pan with oil or butter over medium heat.',
                    'Add proteins first and cook until nearly done.',
                    'Add vegetables and cook until tender.',
                    'Season with salt, pepper, and your favorite herbs and spices.',
                    'Serve hot and enjoy your custom creation!'
                ],
                'image_url' => 'https://www.allrecipes.com/thmb/Qz3NeAG0seXj-jFcDqQxvVKkILY=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/7498519-baked-chicken-and-vegetables-DDMFS-4x3-b7ae0c114a1f47caaa0e4b086ad4f277.jpg',
                'source_url' => 'https://www.allrecipes.com/recipes/1947/everyday-cooking/more-meal-ideas/5-ingredient/'
            ];
        }
        
        return $recipes;
    }
    
    /**
     * Filter recipes to ensure they contain the required ingredients
     * 
     * @param array $recipes
     * @param array $requiredIngredients
     * @return array
     */
    public function filterRecipesByIngredients(array $recipes, array $requiredIngredients): array
    {
        if (empty($recipes)) {
            return $this->getFallbackRecipes($requiredIngredients);
        }
        
        $filteredRecipes = [];
        $requiredIngredientsLower = array_map('strtolower', $requiredIngredients);
        
        foreach ($recipes as $recipe) {
            $recipeIngredients = array_map('strtolower', $recipe['ingredients']);
            $matchCount = 0;
            
            foreach ($requiredIngredientsLower as $ingredient) {
                foreach ($recipeIngredients as $recipeIngredient) {
                    if (strpos($recipeIngredient, $ingredient) !== false) {
                        $matchCount++;
                        break;
                    }
                }
            }
            
            // If at least 50% of required ingredients are found (lowered threshold)
            if ($matchCount >= count($requiredIngredientsLower) * 0.5) {
                $filteredRecipes[] = $recipe;
            }
        }
        
        // If no recipes match the filter, return all recipes
        if (empty($filteredRecipes)) {
            return $recipes;
        }
        
        return $filteredRecipes;
    }
    
    /**
     * Search Food Network website for recipes
     * 
     * @param array $ingredients
     * @return array
     */
    private function searchFoodNetwork(array $ingredients): array
    {
        $searchQuery = implode('+', $ingredients);
        $url = "https://www.foodnetwork.com/search/" . urlencode($searchQuery) . "-";
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
            ])->get($url);
            
            if ($response->failed()) {
                throw new \Exception('Failed to fetch from Food Network: ' . $response->status());
            }
            
            $html = $response->body();
            
            // Parse HTML
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $recipes = [];
            
            // Find recipe cards
            $recipeCards = $xpath->query('//div[contains(@class, "o-Card")]');
            
            foreach ($recipeCards as $index => $card) {
                if ($index >= 5) break; // Limit to 5 recipes per site
                
                $titleNode = $xpath->query('.//h3[contains(@class, "o-Card__a-Headline")]', $card)->item(0);
                $linkNode = $xpath->query('.//a[contains(@class, "o-Card__a-Headline")]', $card)->item(0);
                $imageNode = $xpath->query('.//img', $card)->item(0);
                
                if ($titleNode && $linkNode) {
                    $title = trim($titleNode->textContent);
                    $link = $linkNode->getAttribute('href');
                    $imageUrl = $imageNode ? $imageNode->getAttribute('src') : null;
                    
                    // Get recipe details
                    $recipeDetails = $this->getRecipeDetails($link);
                    
                    $recipes[] = [
                        'title' => $title,
                        'description' => $recipeDetails['description'] ?? 'A delicious recipe using your ingredients.',
                        'cooking_time' => $recipeDetails['cooking_time'] ?? '30 mins',
                        'servings' => $recipeDetails['servings'] ?? 4,
                        'difficulty' => $recipeDetails['difficulty'] ?? 'medium',
                        'ingredients' => $recipeDetails['ingredients'] ?? $ingredients,
                        'instructions' => $recipeDetails['instructions'] ?? ['Cook according to your preference'],
                        'image_url' => $imageUrl,
                        'source_url' => $link
                    ];
                }
            }
            
            return $recipes;
        } catch (\Exception $e) {
            Log::error('Food Network scraping failed', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Search Epicurious website for recipes
     * 
     * @param array $ingredients
     * @return array
     */
    private function searchEpicurious(array $ingredients): array
    {
        $searchQuery = implode('+', $ingredients);
        $url = "https://www.epicurious.com/search/" . urlencode($searchQuery);
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
            ])->get($url);
            
            if ($response->failed()) {
                throw new \Exception('Failed to fetch from Epicurious: ' . $response->status());
            }
            
            $html = $response->body();
            
            // Parse HTML
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $recipes = [];
            
            // Find recipe cards
            $recipeCards = $xpath->query('//article[contains(@class, "recipe-content-card")]');
            
            foreach ($recipeCards as $index => $card) {
                if ($index >= 5) break; // Limit to 5 recipes per site
                
                $titleNode = $xpath->query('.//h4[contains(@class, "recipe-content-card__title")]', $card)->item(0);
                $linkNode = $xpath->query('.//a[contains(@class, "recipe-content-card__title")]', $card)->item(0);
                $imageNode = $xpath->query('.//img', $card)->item(0);
                
                if ($titleNode && $linkNode) {
                    $title = trim($titleNode->textContent);
                    $link = $linkNode->getAttribute('href');
                    $imageUrl = $imageNode ? $imageNode->getAttribute('src') : null;
                    
                    // Get recipe details
                    $recipeDetails = $this->getRecipeDetails($link);
                    
                    $recipes[] = [
                        'title' => $title,
                        'description' => $recipeDetails['description'] ?? 'A delicious recipe using your ingredients.',
                        'cooking_time' => $recipeDetails['cooking_time'] ?? '30 mins',
                        'servings' => $recipeDetails['servings'] ?? 4,
                        'difficulty' => $recipeDetails['difficulty'] ?? 'medium',
                        'ingredients' => $recipeDetails['ingredients'] ?? $ingredients,
                        'instructions' => $recipeDetails['instructions'] ?? ['Cook according to your preference'],
                        'image_url' => $imageUrl,
                        'source_url' => $link
                    ];
                }
            }
            
            return $recipes;
        } catch (\Exception $e) {
            Log::error('Epicurious scraping failed', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
} 