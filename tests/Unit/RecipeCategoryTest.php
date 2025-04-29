<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Recipe $recipe;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->recipe = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Recipe',
            'cuisines' => ['italian', 'mediterranean'],
            'tags' => ['pasta', 'vegetarian', 'quick'],
            'dietary_restrictions' => ['vegetarian', 'gluten-free'],
            'difficulty' => 'medium',
            'seasonal' => true
        ]);
    }

    public function test_recipe_has_cuisines()
    {
        $this->assertIsArray($this->recipe->cuisines);
        $this->assertCount(2, $this->recipe->cuisines);
        $this->assertContains('italian', $this->recipe->cuisines);
        $this->assertContains('mediterranean', $this->recipe->cuisines);
    }

    public function test_recipe_has_tags()
    {
        $this->assertIsArray($this->recipe->tags);
        $this->assertCount(3, $this->recipe->tags);
        $this->assertContains('pasta', $this->recipe->tags);
        $this->assertContains('vegetarian', $this->recipe->tags);
        $this->assertContains('quick', $this->recipe->tags);
    }

    public function test_recipe_has_dietary_restrictions()
    {
        $this->assertIsArray($this->recipe->dietary_restrictions);
        $this->assertCount(2, $this->recipe->dietary_restrictions);
        $this->assertContains('vegetarian', $this->recipe->dietary_restrictions);
        $this->assertContains('gluten-free', $this->recipe->dietary_restrictions);
    }

    public function test_recipe_can_be_filtered_by_cuisine()
    {
        $italianRecipes = Recipe::whereJsonContains('cuisines', 'italian')->get();
        $this->assertCount(1, $italianRecipes);
        $this->assertEquals($this->recipe->id, $italianRecipes->first()->id);
    }

    public function test_recipe_can_be_filtered_by_tag()
    {
        $vegetarianRecipes = Recipe::whereJsonContains('tags', 'vegetarian')->get();
        $this->assertCount(1, $vegetarianRecipes);
        $this->assertEquals($this->recipe->id, $vegetarianRecipes->first()->id);
    }

    public function test_recipe_can_be_filtered_by_dietary_restriction()
    {
        $glutenFreeRecipes = Recipe::whereJsonContains('dietary_restrictions', 'gluten-free')->get();
        $this->assertCount(1, $glutenFreeRecipes);
        $this->assertEquals($this->recipe->id, $glutenFreeRecipes->first()->id);
    }

    public function test_recipe_can_be_filtered_by_difficulty()
    {
        $mediumRecipes = Recipe::where('difficulty', 'medium')->get();
        $this->assertCount(1, $mediumRecipes);
        $this->assertEquals($this->recipe->id, $mediumRecipes->first()->id);
    }

    public function test_recipe_can_be_filtered_by_seasonal()
    {
        $seasonalRecipes = Recipe::where('seasonal', true)->get();
        $this->assertCount(1, $seasonalRecipes);
        $this->assertEquals($this->recipe->id, $seasonalRecipes->first()->id);
    }

    public function test_recipe_can_have_multiple_categories()
    {
        $this->recipe->cuisines = array_merge($this->recipe->cuisines, ['french']);
        $this->recipe->save();

        $this->assertCount(3, $this->recipe->fresh()->cuisines);
        $this->assertContains('french', $this->recipe->fresh()->cuisines);
    }

    public function test_recipe_can_have_multiple_tags()
    {
        $this->recipe->tags = array_merge($this->recipe->tags, ['healthy']);
        $this->recipe->save();

        $this->assertCount(4, $this->recipe->fresh()->tags);
        $this->assertContains('healthy', $this->recipe->fresh()->tags);
    }

    public function test_recipe_can_have_multiple_dietary_restrictions()
    {
        $this->recipe->dietary_restrictions = array_merge($this->recipe->dietary_restrictions, ['vegan']);
        $this->recipe->save();

        $this->assertCount(3, $this->recipe->fresh()->dietary_restrictions);
        $this->assertContains('vegan', $this->recipe->fresh()->dietary_restrictions);
    }

    public function test_recipe_can_be_filtered_by_multiple_criteria()
    {
        $recipes = Recipe::whereJsonContains('cuisines', 'italian')
            ->whereJsonContains('tags', 'vegetarian')
            ->where('difficulty', 'medium')
            ->get();

        $this->assertCount(1, $recipes);
        $this->assertEquals($this->recipe->id, $recipes->first()->id);
    }
} 