<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login_page()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Test valid login works.
     */
    public function test_user_can_login_with_valid_credentials()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'Password123!'
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test invalid login fails.
     */
    public function test_user_cannot_login_with_invalid_password()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'WrongPassword!'
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test role access protection on settings page.
     */
    public function test_worker_is_forbidden_from_viewing_settings()
    {
        $worker = User::create([
            'name' => 'Tech Worker',
            'email' => 'worker@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker'
        ]);

        $response = $this->actingAs($worker)->get('/settings');
        $response->assertStatus(403);
    }

    /**
     * Test super-manager can view settings page.
     */
    public function test_super_manager_can_view_settings()
    {
        $admin = User::create([
            'name' => 'Super Manager',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $response = $this->actingAs($admin)->get('/settings');
        $response->assertStatus(200);
    }
}
