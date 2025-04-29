<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Recipe;
use App\Services\MLService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class MLServiceTest extends TestCase
{
    use RefreshDatabase;

    private MLService $service;
    private Recipe $recipe1;
    private Recipe $recipe2;
    private Recipe $recipe3;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new MLService();
        
        // Create test recipes with controlled ingredients for similarity testing
        $this->recipe1 = Recipe::factory()->create([
            'ingredients' => ['chicken', 'rice', 'onion', 'garlic', 'ginger'],
            'cuisines' => ['asian'],
            'difficulty' => 'medium'
        ]);
        
        $this->recipe2 = Recipe::factory()->create([
            'ingredients' => ['chicken', 'rice', 'onion', 'garlic'],
            'cuisines' => ['asian'],
            'difficulty' => 'medium'
        ]);
        
        $this->recipe3 = Recipe::factory()->create([
            'ingredients' => ['beef', 'potato', 'onion'],
            'cuisines' => ['western'],
            'difficulty' => 'hard'
        ]);
    }

    public function test_get_similar_recipes()
    {
        $similarRecipes = $this->service->getSimilarRecipes($this->recipe1);

        $this->assertInstanceOf(Collection::class, $similarRecipes);
        $this->assertNotEmpty($similarRecipes);
        $this->assertLessThanOrEqual(3, $similarRecipes->count());

        $recipe2 = $similarRecipes->firstWhere('id', $this->recipe2->id);
        $this->assertNotNull($recipe2, 'Recipe 2 should be in similar recipes');
        
        // Recipe 2 should have a high similarity score
        $this->assertGreaterThanOrEqual(0.8, $recipe2->similarity_score);
    }

    public function test_get_similar_recipes_with_custom_limit()
    {
        $similarRecipes = $this->service->getSimilarRecipes($this->recipe1, 1);
        
        $this->assertCount(1, $similarRecipes);
        $this->assertEquals($this->recipe2->id, $similarRecipes->first()->id);
    }

    public function test_get_similar_recipes_with_min_similarity()
    {
        $similarRecipes = $this->service->getSimilarRecipes($this->recipe1, 3, 0.5);

        $this->assertInstanceOf(Collection::class, $similarRecipes);
        foreach ($similarRecipes as $recipe) {
            $this->assertGreaterThanOrEqual(0.5, $recipe->similarity_score);
        }
    }

    public function test_get_similar_recipes_with_no_matches()
    {
        // Create a very unique recipe that shouldn't match anything
        $uniqueRecipe = Recipe::factory()->create([
            'title' => 'Unique Dish',
            'ingredients' => ['unique_ingredient1', 'unique_ingredient2'],
            'instructions' => ['Unique step 1', 'Unique step 2'],
            'cuisines' => ['unique_cuisine'],
            'tags' => ['unique_tag'],
            'difficulty' => 'hard',
            'cooking_time' => 120
        ]);

        $similarRecipes = $this->service->getSimilarRecipes($uniqueRecipe, 3, 0.9);
        
        $this->assertEmpty($similarRecipes);
    }

    public function test_get_similar_recipes_returns_sorted_by_similarity()
    {
        $similarRecipes = $this->service->getSimilarRecipes($this->recipe1);
        
        $previousScore = 1.0;
        foreach ($similarRecipes as $recipe) {
            $this->assertLessThanOrEqual($previousScore, $recipe->similarity_score);
            $previousScore = $recipe->similarity_score;
        }
    }
} 