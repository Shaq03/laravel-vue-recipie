<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Recipe;
use App\Models\User;
use App\Http\Controllers\RecipeController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecipeControllerTest extends TestCase
{
    use RefreshDatabase;

    private RecipeController $controller;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new RecipeController();
        $this->user = User::factory()->create();
        Auth::login($this->user);
        
        // Create some test recipes
        Recipe::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'source' => 'user'
        ]);

        Recipe::factory()->count(3)->create([
            'user_id' => User::factory()->create()->id,
            'source' => 'user'
        ]);
    }

    public function test_get_user_recipes()
    {
        $response = $this->controller->index();
        $data = $response->getData();

        $this->assertIsObject($data);
        $this->assertTrue(property_exists($data, 'recipes'));
        $this->assertIsObject($data->recipes);
        $this->assertTrue(property_exists($data->recipes, 'data'));
        $this->assertCount(5, $data->recipes->data);
        foreach ($data->recipes->data as $recipe) {
            $this->assertEquals($this->user->id, $recipe->user_id);
        }
    }

    public function test_get_user_recipes_with_pagination()
    {
        // Create more recipes to ensure pagination works
        Recipe::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'source' => 'user'
        ]);
        
        $request = new Request(['page' => 1, 'per_page' => 3]);
        $response = $this->controller->index($request);
        $data = $response->getData();

        $this->assertIsObject($data);
        $this->assertTrue(property_exists($data, 'recipes'));
        $this->assertIsObject($data->recipes);
        $this->assertTrue(property_exists($data->recipes, 'data'));
        $this->assertCount(3, $data->recipes->data);
        $this->assertEquals(15, $data->recipes->total); // 5 original + 10 new recipes
        $this->assertEquals(1, $data->recipes->current_page);
        $this->assertEquals(3, $data->recipes->per_page);
        $this->assertEquals(5, $data->recipes->last_page);
    }

    public function test_create_recipe()
    {
        Auth::login($this->user);
        
        $recipeData = [
            'title' => 'New Recipe',
            'description' => 'A delicious recipe',
            'ingredients' => ['ingredient1', 'ingredient2'],
            'instructions' => ['step1', 'step2'],
            'cuisines' => ['italian'],
            'difficulty' => 'easy',
            'cooking_time' => 30
        ];

        $request = new Request($recipeData);
        $response = $this->controller->store($request);
        $data = $response->getData();

        $this->assertTrue(property_exists($data, 'message'));
        $this->assertEquals('Recipe created successfully', $data->message);
        
        $recipe = Recipe::where('title', $recipeData['title'])->first();
        $this->assertNotNull($recipe);
        $this->assertEquals($this->user->id, $recipe->user_id);
    }

    public function test_update_recipe()
    {
        Auth::login($this->user);
        
        $recipe = Recipe::where('user_id', $this->user->id)->first();
        
        $updateData = [
            'title' => 'Updated Recipe',
            'description' => 'Updated description',
            'ingredients' => ['new_ingredient'],
            'instructions' => ['new_step'],
            'cuisines' => ['french'],
            'difficulty' => 'hard',
            'cooking_time' => 45
        ];

        $request = new Request($updateData);
        $response = $this->controller->update($request, $recipe);
        $data = $response->getData();

        $this->assertTrue(property_exists($data, 'message'));
        $this->assertEquals('Recipe updated successfully', $data->message);
        
        $updatedRecipe = Recipe::find($recipe->id);
        $this->assertEquals($updateData['title'], $updatedRecipe->title);
        $this->assertEquals($updateData['difficulty'], $updatedRecipe->difficulty);
    }

    public function test_delete_recipe()
    {
        Auth::login($this->user);
        
        $recipe = Recipe::where('user_id', $this->user->id)->first();
        
        $response = $this->controller->destroy($recipe);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull(Recipe::find($recipe->id));
    }

    public function test_cannot_update_other_users_recipe()
    {
        Auth::login($this->user);
        
        $otherUserRecipe = Recipe::where('user_id', '!=', $this->user->id)->first();
        
        $updateData = [
            'title' => 'Try to Update',
            'description' => 'Try to update description',
            'ingredients' => ['ingredient'],
            'instructions' => ['step'],
            'cuisines' => ['cuisine'],
            'difficulty' => 'easy',
            'cooking_time' => 30
        ];

        $request = new Request($updateData);
        $response = $this->controller->update($request, $otherUserRecipe);
        
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNotEquals($updateData['title'], $otherUserRecipe->fresh()->title);
    }

    public function test_cannot_delete_other_users_recipe()
    {
        Auth::login($this->user);
        
        $otherUserRecipe = Recipe::where('user_id', '!=', $this->user->id)->first();
        $recipeId = $otherUserRecipe->id;
        
        $response = $this->controller->destroy($otherUserRecipe);
        
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNotNull(Recipe::find($recipeId));
    }
} 