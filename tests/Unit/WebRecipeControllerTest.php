<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Recipe;
use App\Models\User;
use App\Http\Controllers\WebRecipeController;
use App\Services\RecipeScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;

class WebRecipeControllerTest extends TestCase
{
    use RefreshDatabase;

    private WebRecipeController $controller;
    private User $user;
    private $recipeScraperService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->recipeScraperService = Mockery::mock(RecipeScraperService::class);
        $this->controller = new WebRecipeController($this->recipeScraperService);
        $this->user = User::factory()->create();
        
        // Create some test recipes
        $pastaRecipe = Recipe::factory()->create([
            'title' => 'Pasta Carbonara',
            'ingredients' => ['pasta', 'eggs', 'bacon', 'parmesan'],
            'cuisines' => ['italian'],
            'difficulty' => 'medium'
        ]);

        $curryRecipe = Recipe::factory()->create([
            'title' => 'Chicken Curry',
            'ingredients' => ['chicken', 'curry powder', 'coconut milk', 'rice'],
            'cuisines' => ['indian'],
            'difficulty' => 'medium'
        ]);

        // Mock the RecipeScraperService methods
        $this->recipeScraperService->shouldReceive('searchRecipesByIngredients')
            ->andReturn([$pastaRecipe, $curryRecipe]);

        $this->recipeScraperService->shouldReceive('filterRecipesByIngredients')
            ->andReturnUsing(function ($recipes, $ingredients) {
                return array_filter($recipes, function ($recipe) use ($ingredients) {
                    return count(array_intersect($recipe->ingredients, $ingredients)) > 0;
                });
            });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_recipes_by_ingredients()
    {
        $request = new Request([
            'ingredients' => ['pasta', 'eggs']
        ]);

        $response = $this->controller->searchByIngredients($request);
        $data = $response->getData();

        $this->assertTrue(property_exists($data, 'recipes'));
        $this->assertNotEmpty($data->recipes);
        $this->assertEquals('Pasta Carbonara', $data->recipes[0]->title);
    }

    public function test_search_recipes_with_no_ingredients()
    {
        $request = new Request([]);
        
        $response = $this->controller->searchByIngredients($request);
        $data = $response->getData();

        $this->assertTrue(property_exists($data, 'recipes'));
        $this->assertEmpty($data->recipes);
    }

    public function test_search_recipes_with_non_existing_ingredients()
    {
        $request = new Request([
            'ingredients' => ['nonexistent']
        ]);

        $response = $this->controller->searchByIngredients($request);
        $data = $response->getData();

        $this->assertTrue(property_exists($data, 'recipes'));
        $this->assertEmpty($data->recipes);
    }

    public function test_search_recipes_with_multiple_matching_ingredients()
    {
        $request = new Request([
            'ingredients' => ['chicken', 'curry powder', 'rice']
        ]);

        $response = $this->controller->searchByIngredients($request);
        $data = $response->getData();

        $this->assertTrue(property_exists($data, 'recipes'));
        $this->assertNotEmpty($data->recipes);
        $this->assertIsArray($data->recipes);
        $this->assertEquals('Chicken Curry', $data->recipes[0]->title);
        $this->assertGreaterThan(0, $data->count);
        $this->assertContains('chicken', $data->ingredients);
    }
} 