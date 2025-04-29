<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $userData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userData = [
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
    }

    public function test_user_can_register()
    {
        $user = User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->userData['username'], $user->username);
        $this->assertEquals($this->userData['email'], $user->email);
    }

    public function test_user_cannot_register_with_duplicate_email()
    {
        User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::create([
            'username' => 'anotheruser',
            'name' => 'Another User',
            'email' => $this->userData['email'],
            'password' => Hash::make('password456')
        ]);
    }

    public function test_user_cannot_register_with_duplicate_username()
    {
        User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::create([
            'username' => $this->userData['username'],
            'name' => 'Another User',
            'email' => 'another@example.com',
            'password' => Hash::make('password456')
        ]);
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $this->assertTrue(Hash::check($this->userData['password'], $user->password));
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        $user = User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $this->assertFalse(Hash::check('wrongpassword', $user->password));
    }

    public function test_user_can_reset_password()
    {
        $user = User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $newPassword = 'newpassword123';
        $user->password = Hash::make($newPassword);
        $user->save();

        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check($this->userData['password'], $user->password));
    }

    public function test_user_can_update_profile()
    {
        $user = User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $newName = 'Updated Name';
        $user->name = $newName;
        $user->save();

        $this->assertEquals($newName, $user->fresh()->name);
    }

    public function test_user_can_delete_account()
    {
        $user = User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);

        $userId = $user->id;
        $user->delete();

        $this->assertNull(User::find($userId));
    }

    public function test_user_can_verify_email()
    {
        $user = User::create([
            'username' => $this->userData['username'],
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password']),
            'email_verified_at' => null
        ]);

        $this->assertNull($user->email_verified_at);

        $user->email_verified_at = now();
        $user->save();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
} 