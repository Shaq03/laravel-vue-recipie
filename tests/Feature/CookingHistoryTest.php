<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Recipe;
use App\Models\CookingHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_cooking_history_entry()
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/cooking-history', [
            'recipe_id' => $recipe->id,
            'rating' => 5,
            'notes' => 'Delicious!'
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['rating' => 5, 'notes' => 'Delicious!']);
        $this->assertDatabaseHas('cooking_histories', [
            'user_id' => $user->id,
            'recipe_id' => $recipe->id,
            'rating' => 5,
            'notes' => 'Delicious!'
        ]);
    }

    public function test_user_can_fetch_their_cooking_history()
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create();
        $history = CookingHistory::factory()->create([
            'user_id' => $user->id,
            'recipe_id' => $recipe->id,
            'rating' => 4,
            'notes' => 'Nice!'
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/cooking-history');
        $response->assertStatus(200)
            ->assertJsonFragment(['rating' => 4, 'notes' => 'Nice!']);
    }

    public function test_user_can_update_their_cooking_history_entry()
    {
        $user = User::factory()->create();
        $history = CookingHistory::factory()->create(['user_id' => $user->id, 'rating' => 3]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/cooking-history/{$history->id}", [
            'rating' => 5,
            'notes' => 'Updated notes'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['rating' => 5, 'notes' => 'Updated notes']);
        $this->assertDatabaseHas('cooking_histories', [
            'id' => $history->id,
            'rating' => 5,
            'notes' => 'Updated notes'
        ]);
    }

    public function test_user_can_delete_their_cooking_history_entry()
    {
        $user = User::factory()->create();
        $history = CookingHistory::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/cooking-history/{$history->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('cooking_histories', [
            'id' => $history->id
        ]);
    }

    public function test_user_cannot_modify_others_cooking_history()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $history = CookingHistory::factory()->create(['user_id' => $other->id, 'rating' => 2]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/cooking-history/{$history->id}", [
            'rating' => 5,
            'notes' => 'Hacked!'
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/cooking-history/{$history->id}");
        $response->assertStatus(403);
    }

    public function test_validation_for_rating_field()
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/cooking-history', [
            'recipe_id' => $recipe->id,
            'rating' => 0,
            'notes' => 'Invalid rating'
        ]);
        $response->assertStatus(422);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/cooking-history', [
            'recipe_id' => $recipe->id,
            'rating' => 6,
            'notes' => 'Invalid rating'
        ]);
        $response->assertStatus(422);
    }
} 