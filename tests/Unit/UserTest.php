<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Recipe;
use App\Models\UserPreference;
use App\Models\CookingHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_has_correct_attributes()
    {
        $this->assertEquals('testuser', $this->user->username);
        $this->assertEquals('Test User', $this->user->name);
        $this->assertEquals('test@example.com', $this->user->email);
    }

    public function test_user_has_recipes()
    {
        Recipe::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Recipe'
        ]);

        $this->assertInstanceOf(Recipe::class, $this->user->recipes->first());
        $this->assertEquals('Test Recipe', $this->user->recipes->first()->title);
    }

    public function test_user_has_favorites()
    {
        $recipe = Recipe::factory()->create();
        $this->user->favorites()->attach($recipe->id);

        $this->assertInstanceOf(Recipe::class, $this->user->favorites->first());
        $this->assertEquals($recipe->id, $this->user->favorites->first()->id);
    }

    public function test_user_has_preferences()
    {
        UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'preferred_cuisines' => ['italian', 'mexican'],
            'dietary_restrictions' => ['vegetarian'],
            'cooking_skill_level' => 'intermediate'
        ]);

        $this->assertInstanceOf(UserPreference::class, $this->user->preferences);
        $this->assertEquals(['italian', 'mexican'], $this->user->preferences->preferred_cuisines);
        $this->assertEquals(['vegetarian'], $this->user->preferences->dietary_restrictions);
        $this->assertEquals('intermediate', $this->user->preferences->cooking_skill_level);
    }

    public function test_user_has_cooking_history()
    {
        CookingHistory::factory()->create([
            'user_id' => $this->user->id,
            'recipe_id' => Recipe::factory()->create()->id
        ]);

        $this->assertInstanceOf(CookingHistory::class, $this->user->cookingHistory->first());
    }

    public function test_user_can_add_favorite_recipe()
    {
        $recipe = Recipe::factory()->create();
        $this->user->favorites()->attach($recipe->id);

        $this->assertTrue($this->user->favorites->contains($recipe));
    }

    public function test_user_can_remove_favorite_recipe()
    {
        $recipe = Recipe::factory()->create();
        $this->user->favorites()->attach($recipe->id);
        $this->user->favorites()->detach($recipe->id);

        $this->assertFalse($this->user->favorites->contains($recipe));
    }

    public function test_user_can_have_multiple_recipes()
    {
        Recipe::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $this->assertCount(3, $this->user->recipes);
    }

    public function test_user_can_have_multiple_favorites()
    {
        $recipes = Recipe::factory()->count(3)->create();
        foreach ($recipes as $recipe) {
            $this->user->favorites()->attach($recipe->id);
        }

        $this->assertCount(3, $this->user->favorites);
    }
} 