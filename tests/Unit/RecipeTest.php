<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Recipe;
use App\Models\User;
use App\Models\RecipeRating;
use App\Models\CookingHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    private Recipe $recipe;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->recipe = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Recipe',
            'description' => 'Test Description',
            'ingredients' => ['ingredient1', 'ingredient2'],
            'instructions' => ['step1', 'step2'],
            'cooking_time' => 30,
            'difficulty' => 'medium',
            'cuisines' => ['italian'],
            'tags' => ['pasta'],
            'nutritional_info' => ['calories' => 500],
            'popularity_score' => 0,
            'average_rating' => 0,
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
            'seasonal' => true,
            'total_ratings' => 0,
            'total_favorites' => 0,
            'total_views' => 0,
            'dietary_restrictions' => ['gluten-free']
        ]);
    }

    public function test_recipe_has_correct_attributes()
    {
        $this->assertEquals('Test Recipe', $this->recipe->title);
        $this->assertEquals('Test Description', $this->recipe->description);
        $this->assertEquals(['ingredient1', 'ingredient2'], $this->recipe->ingredients);
        $this->assertEquals(['step1', 'step2'], $this->recipe->instructions);
        $this->assertEquals(30, $this->recipe->cooking_time);
        $this->assertEquals('medium', $this->recipe->difficulty);
        $this->assertEquals(['italian'], $this->recipe->cuisines);
        $this->assertEquals(['pasta'], $this->recipe->tags);
        $this->assertEquals(['calories' => 500], $this->recipe->nutritional_info);
    }

    public function test_recipe_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->recipe->user);
        $this->assertEquals($this->user->id, $this->recipe->user->id);
    }

    public function test_recipe_has_ratings()
    {
        RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => $this->user->id,
            'rating' => 4.5
        ]);

        $this->assertInstanceOf(RecipeRating::class, $this->recipe->ratings->first());
        $this->assertEquals(4.5, $this->recipe->ratings->first()->rating);
    }

    public function test_recipe_has_cooking_history()
    {
        CookingHistory::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(CookingHistory::class, $this->recipe->cookingHistory->first());
    }

    public function test_update_average_rating()
    {
        RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => $this->user->id,
            'rating' => 4.0
        ]);

        RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5.0
        ]);

        $this->recipe->updateAverageRating();
        $this->assertEquals(4.5, $this->recipe->average_rating);
    }

    public function test_increment_popularity()
    {
        $initialScore = $this->recipe->popularity_score;
        $this->recipe->incrementPopularity();
        $this->assertEquals($initialScore + 1, $this->recipe->popularity_score);
    }

    public function test_scope_user_created()
    {
        $this->recipe->source = 'user';
        $this->recipe->save();

        $userCreatedRecipes = Recipe::userCreated()->get();
        $this->assertTrue($userCreatedRecipes->contains($this->recipe));
    }

    public function test_scope_ai_generated()
    {
        $this->recipe->source = 'ai';
        $this->recipe->save();

        $aiGeneratedRecipes = Recipe::aIGenerated()->get();
        $this->assertTrue($aiGeneratedRecipes->contains($this->recipe));
    }

    public function test_get_average_rating_attribute()
    {
        RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => $this->user->id,
            'rating' => 4.0
        ]);

        $this->assertEquals(4.0, $this->recipe->getAverageRatingAttribute());
    }

    public function test_get_total_ratings_attribute()
    {
        RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => $this->user->id,
            'rating' => 4.0
        ]);

        RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5.0
        ]);

        $this->assertEquals(2, $this->recipe->getTotalRatingsAttribute());
    }

    public function test_get_is_favorited_attribute()
    {
        $this->actingAs($this->user);
        $this->recipe->favoritedBy()->attach($this->user->id);
        
        $this->assertTrue($this->recipe->getIsFavoritedAttribute());
    }
} 