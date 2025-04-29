<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private UserPreference $preferences;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->preferences = UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'preferred_cuisines' => ['italian', 'mexican'],
            'dietary_restrictions' => ['vegetarian', 'gluten-free'],
            'cooking_skill_level' => 'intermediate',
            'seasonal_preferences' => true,
            'favorite_ingredients' => ['tomato', 'basil'],
            'disliked_ingredients' => ['cilantro'],
            'cooking_history' => []
        ]);
    }

    public function test_preferences_has_correct_attributes()
    {
        $this->assertEquals($this->user->id, $this->preferences->user_id);
        $this->assertEquals(['italian', 'mexican'], $this->preferences->preferred_cuisines);
        $this->assertEquals(['vegetarian', 'gluten-free'], $this->preferences->dietary_restrictions);
        $this->assertEquals('intermediate', $this->preferences->cooking_skill_level);
        $this->assertTrue($this->preferences->seasonal_preferences);
        $this->assertEquals(['tomato', 'basil'], $this->preferences->favorite_ingredients);
        $this->assertEquals(['cilantro'], $this->preferences->disliked_ingredients);
        $this->assertEquals([], $this->preferences->cooking_history);
    }

    public function test_preferences_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->preferences->user);
        $this->assertEquals($this->user->id, $this->preferences->user->id);
    }

    public function test_update_cooking_history()
    {
        $recipeId = 1;
        $recipeTitle = 'Test Recipe';

        $this->preferences->updateCookingHistory($recipeId, $recipeTitle);

        $this->assertCount(1, $this->preferences->cooking_history);
        $this->assertEquals($recipeId, $this->preferences->cooking_history[0]['recipe_id']);
        $this->assertEquals($recipeTitle, $this->preferences->cooking_history[0]['title']);
        $this->assertArrayHasKey('cooked_at', $this->preferences->cooking_history[0]);
    }

    public function test_cooking_history_limits_to_50_entries()
    {
        // Add 51 entries to cooking history
        for ($i = 1; $i <= 51; $i++) {
            $this->preferences->updateCookingHistory($i, "Recipe $i");
        }

        $this->assertCount(50, $this->preferences->cooking_history);
        $this->assertEquals(51, $this->preferences->cooking_history[49]['recipe_id']);
        $this->assertEquals('Recipe 51', $this->preferences->cooking_history[49]['title']);
    }

    public function test_preferences_can_be_updated()
    {
        $newCuisines = ['japanese', 'thai'];
        $this->preferences->preferred_cuisines = $newCuisines;
        $this->preferences->save();

        $this->assertEquals($newCuisines, $this->preferences->fresh()->preferred_cuisines);
    }

    public function test_preferences_can_have_empty_arrays()
    {
        $this->preferences->preferred_cuisines = [];
        $this->preferences->dietary_restrictions = [];
        $this->preferences->favorite_ingredients = [];
        $this->preferences->disliked_ingredients = [];
        $this->preferences->save();

        $this->assertEmpty($this->preferences->fresh()->preferred_cuisines);
        $this->assertEmpty($this->preferences->fresh()->dietary_restrictions);
        $this->assertEmpty($this->preferences->fresh()->favorite_ingredients);
        $this->assertEmpty($this->preferences->fresh()->disliked_ingredients);
    }
} 