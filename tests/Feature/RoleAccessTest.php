<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Since we use RefreshDatabase, default migrations will run including our seed migration
    }

    /**
     * Test default roles are correctly seeded.
     */
    public function test_default_roles_exist()
    {
        $this->assertDatabaseHas('roles', ['name' => 'super-manager']);
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
        $this->assertDatabaseHas('roles', ['name' => 'worker']);
    }

    /**
     * Test super-manager has access to everything by default.
     */
    public function test_super_manager_has_all_access()
    {
        $user = User::factory()->create(['role' => 'super-manager']);

        $this->assertTrue($user->hasModuleAccess('finance'));
        $this->assertTrue($user->hasModuleAccess('settings'));
        $this->assertTrue($user->hasModuleAccess('job-cards'));
    }

    /**
     * Test custom role can be created with allowed modules.
     */
    public function test_super_admin_can_create_custom_role()
    {
        $superAdmin = User::factory()->create(['role' => 'super-manager']);

        $response = $this->actingAs($superAdmin)
            ->post(route('settings.roles.store'), [
                'name' => 'billing-agent',
                'label' => 'Billing Agent',
                'allowed_modules' => ['clients', 'billing']
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', [
            'name' => 'billing-agent',
            'label' => 'Billing Agent',
            'is_custom' => true
        ]);

        $role = Role::where('name', 'billing-agent')->first();
        $this->assertEquals(['clients', 'billing'], $role->allowed_modules);
    }

    /**
     * Test user with custom role has access only to permitted modules.
     */
    public function test_custom_role_user_has_module_access_limits()
    {
        // Create role
        Role::create([
            'name' => 'billing-agent',
            'label' => 'Billing Agent',
            'allowed_modules' => ['clients', 'billing'],
            'is_custom' => true
        ]);

        $user = User::factory()->create(['role' => 'billing-agent']);

        $this->assertTrue($user->hasModuleAccess('clients'));
        $this->assertTrue($user->hasModuleAccess('billing'));
        $this->assertFalse($user->hasModuleAccess('finance'));
        $this->assertFalse($user->hasModuleAccess('settings'));
    }

    /**
     * Test update role permissions.
     */
    public function test_super_admin_can_update_role_permissions()
    {
        $superAdmin = User::factory()->create(['role' => 'super-manager']);
        $role = Role::where('name', 'manager')->first();

        $response = $this->actingAs($superAdmin)
            ->put(route('settings.roles.update', $role), [
                'label' => 'Updated Workshop Manager',
                'allowed_modules' => ['dashboard', 'job-cards', 'settings']
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', [
            'name' => 'manager',
            'label' => 'Updated Workshop Manager'
        ]);

        $updatedRole = Role::where('name', 'manager')->first();
        $this->assertEquals(['dashboard', 'job-cards', 'settings'], $updatedRole->allowed_modules);
    }

    /**
     * Test delete custom role.
     */
    public function test_super_admin_can_delete_custom_role_if_not_in_use()
    {
        $superAdmin = User::factory()->create(['role' => 'super-manager']);
        
        $role = Role::create([
            'name' => 'cleaner',
            'label' => 'Cleaner',
            'allowed_modules' => [],
            'is_custom' => true
        ]);

        $response = $this->actingAs($superAdmin)
            ->delete(route('settings.roles.destroy', $role));

        $response->assertRedirect();
        $this->assertDatabaseMissing('roles', ['name' => 'cleaner']);
    }

    /**
     * Test delete custom role fails if in use by an active employee.
     */
    public function test_cannot_delete_custom_role_if_assigned_to_user()
    {
        $superAdmin = User::factory()->create(['role' => 'super-manager']);
        
        $role = Role::create([
            'name' => 'cleaner',
            'label' => 'Cleaner',
            'allowed_modules' => [],
            'is_custom' => true
        ]);

        // Assign user
        User::factory()->create(['role' => 'cleaner']);

        $response = $this->actingAs($superAdmin)
            ->delete(route('settings.roles.destroy', $role));

        $response->assertRedirect();
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['name' => 'cleaner']);
    }

    /**
     * Test system roles cannot be deleted.
     */
    public function test_cannot_delete_system_roles()
    {
        $superAdmin = User::factory()->create(['role' => 'super-manager']);
        $role = Role::where('name', 'manager')->first();

        $response = $this->actingAs($superAdmin)
            ->delete(route('settings.roles.destroy', $role));

        $response->assertRedirect();
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
    }

    /**
     * Test non-super managers cannot manage roles.
     */
    public function test_manager_cannot_manage_roles()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $role = Role::where('name', 'worker')->first();

        // Try to create
        $response1 = $this->actingAs($manager)
            ->post(route('settings.roles.store'), [
                'name' => 'helper',
                'label' => 'Helper',
                'allowed_modules' => []
            ]);
        $response1->assertStatus(403);

        // Try to update
        $response2 = $this->actingAs($manager)
            ->put(route('settings.roles.update', $role), [
                'label' => 'Hack Worker',
                'allowed_modules' => ['finance']
            ]);
        $response2->assertStatus(403);

        // Try to delete
        $response3 = $this->actingAs($manager)
            ->delete(route('settings.roles.destroy', $role));
        $response3->assertStatus(403);
    }
}
