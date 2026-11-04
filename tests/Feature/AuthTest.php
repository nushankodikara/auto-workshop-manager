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

    /**
     * Test archived user cannot login even with valid password.
     */
    public function test_archived_user_cannot_login()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::create([
            'name' => 'Archived Worker',
            'email' => 'archived@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'is_archived' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'archived@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email' => 'This account has been deactivated/archived. Please contact management.']);
        $this->assertGuest();
    }

    /**
     * Test active session is invalidated immediately when user is archived.
     */
    public function test_archived_user_session_is_invalidated_by_middleware()
    {
        $user = User::create([
            'name' => 'Active Worker',
            'email' => 'active@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'is_archived' => false,
        ]);

        // User can access dashboard when active
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        // Account gets archived
        $user->update(['is_archived' => true]);

        // Subsequent request triggers EnsureUserNotArchived middleware
        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Your account has been deactivated/archived. Please contact management.']);
        $this->assertGuest();
    }

    /**
     * Test archived user visiting login page with active session is logged out.
     */
    public function test_archived_user_visiting_login_page_clears_session()
    {
        $user = User::create([
            'name' => 'Archived Staff',
            'email' => 'staff@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'is_archived' => true,
        ]);

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test archived user cannot request password reset code.
     */
    public function test_archived_user_cannot_request_password_reset()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::create([
            'name' => 'Archived User',
            'email' => 'archived_reset@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'is_archived' => true,
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'archived_reset@test.com',
        ]);

        $response->assertSessionHasErrors(['email' => 'This account has been deactivated/archived. Password reset is not permitted.']);
    }
}
