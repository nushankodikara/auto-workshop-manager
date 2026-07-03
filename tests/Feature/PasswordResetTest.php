<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->detectEnvironment(fn() => 'testing');
        session()->forget('mock_notifications');
    }

    /**
     * Test forgot password page can be viewed.
     */
    public function test_forgot_password_page_loads()
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
        $response->assertSee('Forgot Password');
    }

    /**
     * Test valid email requests password reset code.
     */
    public function test_valid_email_generates_and_sends_verification_code()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::create([
            'name' => 'John Mechanic',
            'email' => 'john@totaldrivecare.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker'
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'john@totaldrivecare.com'
        ]);

        $response->assertRedirect(route('password.reset', ['email' => 'john@totaldrivecare.com']));
        $response->assertSessionHas('success');

        // Assert record exists in password_reset_tokens
        $record = DB::table('password_reset_tokens')->where('email', 'john@totaldrivecare.com')->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->token);

        // Assert email notification is mocked in session
        $notifications = session('mock_notifications', []);
        $this->assertCount(1, $notifications);
        $this->assertEquals('john@totaldrivecare.com', $notifications[0]['to']);
        $this->assertStringContainsString('verification code', $notifications[0]['message']);
    }

    /**
     * Test invalid email fails code generation.
     */
    public function test_invalid_email_fails_verification_code_request()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $response = $this->post(route('password.email'), [
            'email' => 'invalid@test.com'
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'invalid@test.com']);
    }

    /**
     * Test password can be reset with a valid code.
     */
    public function test_user_can_reset_password_with_valid_code()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::create([
            'name' => 'John Mechanic',
            'email' => 'john@totaldrivecare.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker'
        ]);

        $code = '555777';
        DB::table('password_reset_tokens')->insert([
            'email' => 'john@totaldrivecare.com',
            'token' => Hash::make($code),
            'created_at' => Carbon::now()
        ]);

        $response = $this->post(route('password.update'), [
            'email' => 'john@totaldrivecare.com',
            'code' => $code,
            'password' => 'NewSecurePass1!',
            'password_confirmation' => 'NewSecurePass1!'
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Token should be deleted
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'john@totaldrivecare.com']);

        // Password should be updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass1!', $user->password));
    }

    /**
     * Test password reset fails with an incorrect code.
     */
    public function test_password_reset_fails_with_incorrect_code()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::create([
            'name' => 'John Mechanic',
            'email' => 'john@totaldrivecare.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker'
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'john@totaldrivecare.com',
            'token' => Hash::make('123456'),
            'created_at' => Carbon::now()
        ]);

        $response = $this->post(route('password.update'), [
            'email' => 'john@totaldrivecare.com',
            'code' => '000000', // wrong code
            'password' => 'NewSecurePass1!',
            'password_confirmation' => 'NewSecurePass1!'
        ]);

        $response->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertFalse(Hash::check('NewSecurePass1!', $user->password));
    }

    /**
     * Test password reset fails with an expired code.
     */
    public function test_password_reset_fails_with_expired_code()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $user = User::create([
            'name' => 'John Mechanic',
            'email' => 'john@totaldrivecare.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker'
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'john@totaldrivecare.com',
            'token' => Hash::make('123456'),
            'created_at' => Carbon::now()->subMinutes(20) // expired (15 mins limit)
        ]);

        $response = $this->post(route('password.update'), [
            'email' => 'john@totaldrivecare.com',
            'code' => '123456',
            'password' => 'NewSecurePass1!',
            'password_confirmation' => 'NewSecurePass1!'
        ]);

        $response->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertFalse(Hash::check('NewSecurePass1!', $user->password));
    }

    /**
     * Test super admin can reset any password directly.
     */
    public function test_super_admin_can_reset_any_employee_password()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@totaldrivecare.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $worker = User::create([
            'name' => 'Tech Mechanic',
            'email' => 'tech@totaldrivecare.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker'
        ]);

        $response = $this->actingAs($admin)->put(route('employees.update', $worker->id), [
            'name' => 'Tech Mechanic',
            'email' => 'tech@totaldrivecare.com',
            'role' => 'worker',
            'basic_salary' => 50000,
            'required_days' => 26,
            'overtime_rate' => 300,
            'password' => 'NewAdminResetPass1!'
        ]);

        $response->assertStatus(302);
        $worker->refresh();
        $this->assertTrue(Hash::check('NewAdminResetPass1!', $worker->password));
    }

    /**
     * Test standard manager cannot reset password or assign super admin roles.
     */
    public function test_manager_cannot_reset_password_or_grant_admin_roles()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $managerRole = \App\Models\Role::where('name', 'manager')->first();
        if ($managerRole) {
            $modules = $managerRole->allowed_modules ?? [];
            $modules[] = 'payroll';
            $managerRole->update(['allowed_modules' => $modules]);
        }

        $manager = User::create([
            'name' => 'John Manager',
            'email' => 'manager@totaldrivecare.com',
            'password' => Hash::make('Password123!'),
            'role' => 'manager',
            'allowed_modules' => ['payroll']
        ]);

        $worker = User::create([
            'name' => 'Tech Mechanic',
            'email' => 'tech@totaldrivecare.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker'
        ]);

        // Attempt password reset
        $response = $this->actingAs($manager)->put(route('employees.update', $worker->id), [
            'name' => 'Tech Mechanic',
            'email' => 'tech@totaldrivecare.com',
            'role' => 'worker',
            'basic_salary' => 50000,
            'required_days' => 26,
            'overtime_rate' => 300,
            'password' => 'ForbiddenPass1!'
        ]);

        $response->assertSessionHasErrors('password');
        $worker->refresh();
        $this->assertFalse(Hash::check('ForbiddenPass1!', $worker->password));

        // Attempt promoting worker to super-manager
        $response2 = $this->actingAs($manager)->put(route('employees.update', $worker->id), [
            'name' => 'Tech Mechanic',
            'email' => 'tech@totaldrivecare.com',
            'role' => 'super-manager', // forbidden
            'basic_salary' => 50000,
            'required_days' => 26,
            'overtime_rate' => 300
        ]);

        $response2->assertSessionHasErrors('role');
        $worker->refresh();
        $this->assertEquals('worker', $worker->role);
    }
}
