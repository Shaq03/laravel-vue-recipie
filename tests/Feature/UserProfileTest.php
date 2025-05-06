<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create([
            'username' => 'oldname',
            'email' => 'test@example.com',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson('/api/v1/user/profile', [
            'username' => 'newname',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.username', 'newname');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'newname',
        ]);
    }

    public function test_user_can_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson('/api/v1/user/password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password updated successfully']);
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_update_profile_requires_email_and_username()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/v1/user/profile', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'email']);
    }

    public function test_update_password_requires_current_and_new_password()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/v1/user/password', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password', 'new_password']);
    }
} 