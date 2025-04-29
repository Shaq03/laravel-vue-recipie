<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Recipe;
use App\Models\RecipeRating;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeRatingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Recipe $recipe;
    private RecipeRating $rating;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->recipe = Recipe::factory()->create();
        $this->rating = RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => $this->user->id,
            'rating' => 4.5,
            'comment' => 'Great recipe!'
        ]);
    }

    public function test_rating_has_correct_attributes()
    {
        $this->assertEquals($this->recipe->id, $this->rating->recipe_id);
        $this->assertEquals($this->user->id, $this->rating->user_id);
        $this->assertEquals(4.5, $this->rating->rating);
        $this->assertEquals('Great recipe!', $this->rating->comment);
    }

    public function test_rating_belongs_to_recipe()
    {
        $this->assertInstanceOf(Recipe::class, $this->rating->recipe);
        $this->assertEquals($this->recipe->id, $this->rating->recipe->id);
    }

    public function test_rating_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->rating->user);
        $this->assertEquals($this->user->id, $this->rating->user->id);
    }

    public function test_rating_can_be_updated()
    {
        $this->rating->rating = 5.0;
        $this->rating->comment = 'Updated comment';
        $this->rating->save();

        $this->assertEquals(5.0, $this->rating->fresh()->rating);
        $this->assertEquals('Updated comment', $this->rating->fresh()->comment);
    }

    public function test_rating_can_be_deleted()
    {
        $ratingId = $this->rating->id;
        $this->rating->delete();

        $this->assertNull(RecipeRating::find($ratingId));
    }

    public function test_recipe_can_have_multiple_ratings()
    {
        RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 4.0
        ]);

        RecipeRating::factory()->create([
            'recipe_id' => $this->recipe->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5.0
        ]);

        $this->assertCount(3, $this->recipe->ratings);
    }

    public function test_user_can_rate_multiple_recipes()
    {
        $recipe2 = Recipe::factory()->create();
        $recipe3 = Recipe::factory()->create();

        RecipeRating::factory()->create([
            'recipe_id' => $recipe2->id,
            'user_id' => $this->user->id,
            'rating' => 4.0
        ]);

        RecipeRating::factory()->create([
            'recipe_id' => $recipe3->id,
            'user_id' => $this->user->id,
            'rating' => 5.0
        ]);

        $this->assertCount(3, $this->user->ratings);
    }

    public function test_rating_can_have_empty_comment()
    {
        $this->rating->comment = null;
        $this->rating->save();

        $this->assertNull($this->rating->fresh()->comment);
    }
} 