<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Recipe;
use App\Services\AIRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AIRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AIRecommendationService $aiService;
    private Recipe $recipe1;
    private Recipe $recipe2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Create recipes before initializing the service
        $this->recipe1 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'source' => 'ai',
            'title' => 'Chicken Rice Bowl',
            'description' => 'A delicious Asian-inspired dish',
            'ingredients' => ['chicken', 'rice', 'vegetables'],
            'instructions' => ['Cook rice', 'Cook chicken', 'Combine'],
            'cuisines' => ['asian'],
            'difficulty' => 'medium',
            'cooking_time' => 30,
            'servings' => 4,
            'is_vegetarian' => false,
            'is_vegan' => false,
            'is_gluten_free' => true,
            'tags' => ['quick', 'healthy'],
            'dietary_restrictions' => ['gluten-free'],
            'seasonal' => true
        ]);

        $this->recipe2 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'source' => 'ai',
            'title' => 'Beef Pasta',
            'description' => 'A classic Italian dish',
            'ingredients' => ['beef', 'pasta', 'tomato'],
            'instructions' => ['Cook pasta', 'Cook beef', 'Combine'],
            'cuisines' => ['italian'],
            'difficulty' => 'easy',
            'cooking_time' => 25,
            'servings' => 4,
            'is_vegetarian' => false,
            'is_vegan' => false,
            'is_gluten_free' => false,
            'tags' => ['quick', 'classic'],
            'dietary_restrictions' => [],
            'seasonal' => false
        ]);

        // Initialize service after creating recipes
        $this->aiService = app(AIRecommendationService::class);
    }

    public function test_get_recommendations_based_on_ingredients()
    {
        $ingredients = ['chicken', 'rice'];
        $recommendations = $this->aiService->getRecommendations($this->user, $ingredients);

        $this->assertNotEmpty($recommendations);
        $this->assertContains($this->recipe1->id, collect($recommendations)->pluck('recipe.id')->toArray());
    }

    public function test_get_recommendations_based_on_cuisine()
    {
        $this->user->preferences()->create([
            'preferred_cuisines' => ['asian'],
            'cooking_skill_level' => 'intermediate'
        ]);

        $recommendations = $this->aiService->getRecommendations($this->user, []);

        $this->assertNotEmpty($recommendations);
        $this->assertContains($this->recipe1->id, collect($recommendations)->pluck('recipe.id')->toArray());
    }

    public function test_get_recommendations_based_on_difficulty()
    {
        $this->user->preferences()->create([
            'cooking_skill_level' => 'intermediate'
        ]);

        $recommendations = $this->aiService->getRecommendations($this->user, []);

        $this->assertNotEmpty($recommendations);
        $this->assertContains($this->recipe1->id, collect($recommendations)->pluck('recipe.id')->toArray());
    }

    public function test_get_recommendations_with_no_matches()
    {
        $ingredients = ['nonexistent_ingredient'];
        $recommendations = $this->aiService->getRecommendations($this->user, $ingredients);

        $this->assertEmpty($recommendations);
    }

    public function test_get_recommendations_with_user_preferences()
    {
        $this->user->preferences()->create([
            'preferred_cuisines' => ['asian'],
            'dietary_restrictions' => ['gluten-free'],
            'cooking_skill_level' => 'intermediate',
            'favorite_ingredients' => ['chicken', 'rice']
        ]);

        $recommendations = $this->aiService->getRecommendations($this->user, []);

        $this->assertNotEmpty($recommendations);
        $this->assertContains($this->recipe1->id, collect($recommendations)->pluck('recipe.id')->toArray());
    }

    public function test_get_recommendations_with_cooking_history()
    {
        // Add cooking history
        $this->user->cookingHistory()->create([
            'recipe_id' => $this->recipe1->id,
            'cooked_at' => now(),
            'rating' => 5.0
        ]);

        $recommendations = $this->aiService->getRecommendations($this->user, []);

        $this->assertNotEmpty($recommendations);
        $this->assertContains($this->recipe1->id, collect($recommendations)->pluck('recipe.id')->toArray());
    }
} 