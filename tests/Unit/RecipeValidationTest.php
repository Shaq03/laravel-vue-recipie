<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class RecipeValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $recipeData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->recipeData = [
            'user_id' => $this->user->id,
            'title' => 'Test Recipe',
            'description' => 'A test recipe description',
            'ingredients' => ['ingredient1', 'ingredient2'],
            'instructions' => ['step1', 'step2'],
            'cooking_time' => 30,
            'difficulty' => 'medium',
            'cuisines' => ['italian'],
            'tags' => ['pasta'],
            'servings' => 4,
            'preparation_time' => 15,
            'total_time' => 45,
            'calories' => 500,
            'protein' => 20.5,
            'carbs' => 60.5,
            'fat' => 15.5,
            'fiber' => 5.5,
            'sugar' => 10.5,
            'sodium' => 800,
            'cholesterol' => 50,
            'is_vegetarian' => true,
            'is_vegan' => false,
            'is_gluten_free' => true,
            'is_dairy_free' => false,
            'is_nut_free' => true,
            'is_halal' => true,
            'is_kosher' => true,
            'seasonal' => true
        ];
    }

    public function test_recipe_validation_passes_with_valid_data()
    {
        $validator = Validator::make($this->recipeData, Recipe::rules());
        $this->assertTrue($validator->passes());
    }

    public function test_recipe_validation_fails_without_title()
    {
        $data = $this->recipeData;
        unset($data['title']);
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_recipe_validation_fails_without_ingredients()
    {
        $data = $this->recipeData;
        unset($data['ingredients']);
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ingredients', $validator->errors()->toArray());
    }

    public function test_recipe_validation_fails_without_instructions()
    {
        $data = $this->recipeData;
        unset($data['instructions']);
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('instructions', $validator->errors()->toArray());
    }

    public function test_recipe_validation_fails_with_invalid_difficulty()
    {
        $data = $this->recipeData;
        $data['difficulty'] = 'invalid_difficulty';
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('difficulty', $validator->errors()->toArray());
    }

    public function test_recipe_validation_fails_with_negative_cooking_time()
    {
        $data = $this->recipeData;
        $data['cooking_time'] = -1;
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cooking_time', $validator->errors()->toArray());
    }

    public function test_recipe_validation_fails_with_negative_servings()
    {
        $data = $this->recipeData;
        $data['servings'] = -1;
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('servings', $validator->errors()->toArray());
    }

    public function test_recipe_validation_fails_with_invalid_cuisines()
    {
        $data = $this->recipeData;
        $data['cuisines'] = 'not_an_array';
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cuisines', $validator->errors()->toArray());
    }

    public function test_recipe_validation_fails_with_invalid_tags()
    {
        $data = $this->recipeData;
        $data['tags'] = 'not_an_array';
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tags', $validator->errors()->toArray());
    }

    public function test_recipe_validation_fails_with_invalid_nutritional_values()
    {
        $data = $this->recipeData;
        $data['calories'] = 'not_a_number';
        
        $validator = Validator::make($data, Recipe::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('calories', $validator->errors()->toArray());
    }

    public function test_recipe_validation_passes_with_minimal_required_data()
    {
        $minimalData = [
            'user_id' => $this->user->id,
            'title' => 'Test Recipe',
            'ingredients' => ['ingredient1'],
            'instructions' => ['step1'],
            'cooking_time' => 30,
            'difficulty' => 'medium',
            'servings' => 4,
            'cuisines' => ['italian']
        ];
        
        $validator = Validator::make($minimalData, Recipe::rules());
        $this->assertTrue($validator->passes());
    }
} 