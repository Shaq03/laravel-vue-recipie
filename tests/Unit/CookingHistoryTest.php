<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Recipe;
use App\Models\CookingHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Recipe $recipe;
    private CookingHistory $history;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->recipe = Recipe::factory()->create();
        $this->history = CookingHistory::factory()->create([
            'user_id' => $this->user->id,
            'recipe_id' => $this->recipe->id,
            'cooked_at' => now(),
            'rating' => 4.5,
            'notes' => 'Great cooking experience!'
        ]);
    }

    public function test_history_has_correct_attributes()
    {
        $this->assertEquals($this->user->id, $this->history->user_id);
        $this->assertEquals($this->recipe->id, $this->history->recipe_id);
        $this->assertEquals(4.5, $this->history->rating);
        $this->assertEquals('Great cooking experience!', $this->history->notes);
        $this->assertInstanceOf(\DateTime::class, $this->history->cooked_at);
    }

    public function test_history_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->history->user);
        $this->assertEquals($this->user->id, $this->history->user->id);
    }

    public function test_history_belongs_to_recipe()
    {
        $this->assertInstanceOf(Recipe::class, $this->history->recipe);
        $this->assertEquals($this->recipe->id, $this->history->recipe->id);
    }

    public function test_history_can_be_updated()
    {
        $this->history->rating = 5.0;
        $this->history->notes = 'Updated notes';
        $this->history->save();

        $this->assertEquals(5.0, $this->history->fresh()->rating);
        $this->assertEquals('Updated notes', $this->history->fresh()->notes);
    }

    public function test_history_can_be_deleted()
    {
        $historyId = $this->history->id;
        $this->history->delete();

        $this->assertNull(CookingHistory::find($historyId));
    }

    public function test_user_can_have_multiple_cooking_histories()
    {
        $recipe2 = Recipe::factory()->create();
        $recipe3 = Recipe::factory()->create();

        CookingHistory::factory()->create([
            'user_id' => $this->user->id,
            'recipe_id' => $recipe2->id,
            'cooked_at' => now(),
            'rating' => 4.0
        ]);

        CookingHistory::factory()->create([
            'user_id' => $this->user->id,
            'recipe_id' => $recipe3->id,
            'cooked_at' => now(),
            'rating' => 5.0
        ]);

        $this->assertCount(3, $this->user->cookingHistory);
    }

    public function test_recipe_can_have_multiple_cooking_histories()
    {
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        CookingHistory::factory()->create([
            'user_id' => $user2->id,
            'recipe_id' => $this->recipe->id,
            'cooked_at' => now(),
            'rating' => 4.0
        ]);

        CookingHistory::factory()->create([
            'user_id' => $user3->id,
            'recipe_id' => $this->recipe->id,
            'cooked_at' => now(),
            'rating' => 5.0
        ]);

        $this->assertCount(3, $this->recipe->cookingHistory);
    }

    public function test_history_can_have_empty_notes()
    {
        $this->history->notes = null;
        $this->history->save();

        $this->assertNull($this->history->fresh()->notes);
    }

    public function test_history_can_have_empty_rating()
    {
        $this->history->rating = null;
        $this->history->save();

        $this->assertNull($this->history->fresh()->rating);
    }
} 