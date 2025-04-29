<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Recipe $recipe1;
    private Recipe $recipe2;
    private Recipe $recipe3;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Create recipes with different attributes
        $this->recipe1 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Chicken Curry',
            'ingredients' => ['chicken', 'curry', 'rice'],
            'cuisines' => ['indian'],
            'difficulty' => 'medium',
            'is_vegetarian' => false,
            'is_vegan' => false,
            'is_gluten_free' => true,
            'cooking_time' => 45
        ]);

        $this->recipe2 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Vegetable Pasta',
            'ingredients' => ['pasta', 'tomato', 'basil'],
            'cuisines' => ['italian'],
            'difficulty' => 'easy',
            'is_vegetarian' => true,
            'is_vegan' => true,
            'is_gluten_free' => false,
            'cooking_time' => 30
        ]);

        $this->recipe3 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Beef Steak',
            'ingredients' => ['beef', 'potato', 'vegetables'],
            'cuisines' => ['american'],
            'difficulty' => 'hard',
            'is_vegetarian' => false,
            'is_vegan' => false,
            'is_gluten_free' => true,
            'cooking_time' => 60
        ]);
    }

    public function test_search_by_title()
    {
        $results = Recipe::where('title', 'like', '%Chicken%')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($this->recipe1->id, $results->first()->id);
    }

    public function test_search_by_ingredients()
    {
        $results = Recipe::whereJsonContains('ingredients', 'chicken')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($this->recipe1->id, $results->first()->id);
    }

    public function test_search_by_cuisine()
    {
        $results = Recipe::whereJsonContains('cuisines', 'italian')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($this->recipe2->id, $results->first()->id);
    }

    public function test_search_by_difficulty()
    {
        $results = Recipe::where('difficulty', 'easy')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($this->recipe2->id, $results->first()->id);
    }

    public function test_search_by_dietary_restrictions()
    {
        $results = Recipe::where('is_vegetarian', true)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($this->recipe2->id, $results->first()->id);
    }

    public function test_search_by_cooking_time()
    {
        $results = Recipe::where('cooking_time', '<=', 45)->get();
        $this->assertCount(2, $results);
    }

    public function test_combined_search()
    {
        $results = Recipe::where('is_gluten_free', true)
            ->where('cooking_time', '<=', 60)
            ->get();
        $this->assertCount(2, $results);
    }

    public function test_search_with_multiple_criteria()
    {
        $results = Recipe::where('is_vegetarian', true)
            ->whereJsonContains('cuisines', 'italian')
            ->where('difficulty', 'easy')
            ->get();
        $this->assertCount(1, $results);
        $this->assertEquals($this->recipe2->id, $results->first()->id);
    }

    public function test_search_with_no_results()
    {
        $results = Recipe::where('title', 'like', '%Nonexistent%')->get();
        $this->assertCount(0, $results);
    }

    public function test_search_with_partial_matches()
    {
        $results = Recipe::where('title', 'like', '%Curry%')->get();
        $this->assertCount(1, $results);
        $this->assertEquals($this->recipe1->id, $results->first()->id);
    }
} 