<?php

namespace Tests\Feature;

use App\Enums\RolesEnum;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;
    protected User $owner;
    protected User $admin;
    protected User $backend;
    protected User $frontend;
    protected User $ui;
    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        foreach (RolesEnum::cases() as $roleEnum) {
            Role::factory()->create(['name' => $roleEnum->value]);
        }

        $this->owner = $this->createUserWithRole(RolesEnum::OWNER);
        $this->admin = $this->createUserWithRole(RolesEnum::ADMIN);
        $this->backend = $this->createUserWithRole(RolesEnum::BACKEND);
        $this->frontend = $this->createUserWithRole(RolesEnum::FRONTEND);
        $this->ui = $this->createUserWithRole(RolesEnum::UI);

        // ─── مستخدم هدف للتجارب ───
        $this->targetUser = $this->createUserWithRole(RolesEnum::FRONTEND);
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    protected function createUserWithRole(RolesEnum $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $role->value)->first());
        return $user;
    }

    protected function assertSuccessResponse($response, string $message): void
    {
        $response->assertOk()
            ->assertExactJson([
                'status' => 'success',
                'message' => $message,
            ]);
    }

    protected function assertUnauthorizedResponse($response): void
    {
        $response->assertForbidden()
            ->assertExactJson([
                'status' => 'error',
                'message' => 'Unauthorized',
            ]);
    }
    public function test_loggedOut_user(): void
    {
        // ✅ تسجيل خروج أولاً
        Auth::logout();
        // أو: $this->actingAs(null);

        $response = $this->getJson('/api/users');
        $response->assertUnauthorized(); // 401
    }

    // ═══════════════════════════════════════════════════════════════
    // INDEX - GET /api/users
    // ═══════════════════════════════════════════════════════════════

    public function test_owner_can_list_all_users(): void
    {
        $this->actingAs($this->owner);

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'data',
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'links',
                ],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Users retrieved successfully')
            ->assertJsonPath('data.per_page', 15);
    }

    public function test_admin_can_list_all_users(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_non_admin_non_owner_cannot_list_users(): void
    {
        $this->actingAs($this->backend);

        $response = $this->getJson('/api/users');

        $this->assertUnauthorizedResponse($response);
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        Auth::logout();
        $response = $this->getJson('/api/users');
        $response->assertUnauthorized();
    }

    public function test_index_returns_paginated_results(): void
    {
        $this->actingAs($this->owner);

        User::factory()->count(20)->create();

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.per_page', 15)
            ->assertJsonPath('data.last_page', 2)
            ->assertJsonPath('data.total', 27);
    }

    public function test_index_supports_page_navigation(): void
    {
        $this->actingAs($this->owner);

        User::factory()->count(20)->create();

        $response = $this->getJson('/api/users?page=2');

        $response->assertOk()
            ->assertJsonPath('data.current_page', 2);
    }

    // ═══════════════════════════════════════════════════════════════
    // USERS BY ROLE - GET /api/users/{role}
    // ═══════════════════════════════════════════════════════════════

    public function test_owner_can_get_users_by_valid_role(): void
    {
        $this->actingAs($this->owner);

        $response = $this->getJson('/api/users/role/backend');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Users retrieved successfully');
    }

    public function test_admin_can_get_users_by_valid_role(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson('/api/users/role/frontend');

        $response->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_non_admin_non_owner_cannot_get_users_by_role(): void
    {
        $this->actingAs($this->ui);

        $response = $this->getJson('/api/users/role/admin');

        $this->assertUnauthorizedResponse($response);
    }

    public function test_users_by_role_with_invalid_role_returns_validation_error(): void
    {
        $this->actingAs($this->owner);

        $response = $this->getJson('/api/users/role/invalid-role');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_users_by_role_is_case_sensitive(): void
    {
        $this->actingAs($this->owner);

        $response = $this->getJson('/api/users/role/Admin');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    // ═══════════════════════════════════════════════════════════════
    // CHANGE ROLE - POST /api/users/{id}
    // ═══════════════════════════════════════════════════════════════

    public function test_owner_can_change_user_role(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => [RolesEnum::BACKEND->value],
        ]);

        $this->assertSuccessResponse($response, 'User role updated successfully');

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->targetUser->id,
            'role_id' => Role::where('name', RolesEnum::BACKEND->value)->first()->id,
        ]);
    }

    public function test_admin_can_change_user_role(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => [RolesEnum::UI->value],
        ]);

        $this->assertSuccessResponse($response, 'User role updated successfully');
    }

    public function test_change_role_replaces_existing_roles(): void
    {
        $this->actingAs($this->owner);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->targetUser->id,
            'role_id' => Role::where('name', RolesEnum::FRONTEND->value)->first()->id,
        ]);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => [RolesEnum::BACKEND->value],
        ]);

        $this->assertSuccessResponse($response, 'User role updated successfully');

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->targetUser->id,
            'role_id' => Role::where('name', RolesEnum::FRONTEND->value)->first()->id,
        ]);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->targetUser->id,
            'role_id' => Role::where('name', RolesEnum::BACKEND->value)->first()->id,
        ]);
    }

    public function test_can_assign_multiple_roles(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => [
                RolesEnum::BACKEND->value,
                RolesEnum::UI->value,
            ],
        ]);

        $this->assertSuccessResponse($response, 'User role updated successfully');

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->targetUser->id,
            'role_id' => Role::where('name', RolesEnum::BACKEND->value)->first()->id,
        ]);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->targetUser->id,
            'role_id' => Role::where('name', RolesEnum::UI->value)->first()->id,
        ]);
    }

    public function test_admin_cannot_change_role_of_another_admin(): void
    {
        $this->actingAs($this->admin);

        $anotherAdmin = $this->createUserWithRole(RolesEnum::ADMIN);

        $response = $this->postJson("/api/users/{$anotherAdmin->id}", [
            'names' => [RolesEnum::FRONTEND->value],
        ]);

        $this->assertUnauthorizedResponse($response);
    }

    public function test_admin_cannot_change_role_of_owner(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson("/api/users/{$this->owner->id}", [
            'names' => [RolesEnum::FRONTEND->value],
        ]);

        $this->assertUnauthorizedResponse($response);
    }

    public function test_owner_can_change_admin_role(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson("/api/users/{$this->admin->id}", [
            'names' => [RolesEnum::FRONTEND->value],
        ]);

        $this->assertSuccessResponse($response, 'User role updated successfully');
    }

    public function test_non_admin_non_owner_cannot_change_role(): void
    {
        $this->actingAs($this->backend);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => [RolesEnum::UI->value],
        ]);

        $this->assertUnauthorizedResponse($response);
    }

    public function test_change_role_requires_names_field(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['names']);
    }

    public function test_change_role_requires_names_to_be_array(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['names']);
    }

    public function test_change_role_requires_valid_enum_values(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => ['invalid-role'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['names.0']);
    }

    public function test_change_role_with_mixed_valid_invalid_roles_fails(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => [
                RolesEnum::BACKEND->value,
                'invalid-role',
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['names.1']);
    }

    public function test_change_role_fails_for_nonexistent_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson('/api/users/99999', [
            'names' => [RolesEnum::BACKEND->value],
        ]);

        $response->assertNotFound();
    }

    public function test_change_role_with_empty_array_fails(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson("/api/users/{$this->targetUser->id}", [
            'names' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['names']);
    }

    // ═══════════════════════════════════════════════════════════════
    // ACTIVATE - PUT /api/users/activate/{id}
    // ═══════════════════════════════════════════════════════════════

    public function test_owner_can_activate_banned_user(): void
    {
        $this->actingAs($this->owner);

        $this->targetUser->delete();

        $this->assertSoftDeleted('users', ['id' => $this->targetUser->id]);

        $response = $this->putJson("/api/users/activate/{$this->targetUser->id}");

        $this->assertSuccessResponse($response, 'User Activated successfully');

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_activate_banned_user(): void
    {
        $this->actingAs($this->admin);

        $this->targetUser->delete();

        $response = $this->putJson("/api/users/activate/{$this->targetUser->id}");

        $this->assertSuccessResponse($response, 'User Activated successfully');
    }

    public function test_admin_cannot_activate_another_admin(): void
    {
        $this->actingAs($this->admin);

        $anotherAdmin = $this->createUserWithRole(RolesEnum::ADMIN);
        $anotherAdmin->delete();

        $response = $this->putJson("/api/users/activate/{$anotherAdmin->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_admin_cannot_activate_owner(): void
    {
        $this->actingAs($this->admin);

        $this->owner->delete();

        $response = $this->putJson("/api/users/activate/{$this->owner->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_owner_can_activate_admin(): void
    {
        $this->actingAs($this->owner);

        $this->admin->delete();

        $response = $this->putJson("/api/users/activate/{$this->admin->id}");

        $this->assertSuccessResponse($response, 'User Activated successfully');
    }

    public function test_non_admin_non_owner_cannot_activate_user(): void
    {
        $this->actingAs($this->backend);

        $this->targetUser->delete();

        $response = $this->putJson("/api/users/activate/{$this->targetUser->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_activate_fails_for_nonexistent_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->putJson('/api/users/activate/99999');

        $response->assertNotFound();
    }

    public function test_can_activate_already_active_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->putJson("/api/users/activate/{$this->targetUser->id}");

        $this->assertSuccessResponse($response, 'User Activated successfully');
    }

    // ═══════════════════════════════════════════════════════════════
    // BAN - DELETE /api/users/ban/{id}
    // ═══════════════════════════════════════════════════════════════

    public function test_owner_can_ban_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->deleteJson("/api/users/ban/{$this->targetUser->id}");

        $this->assertSuccessResponse($response, 'User Baned successfully');

        $this->assertSoftDeleted('users', ['id' => $this->targetUser->id]);
    }

    public function test_admin_can_ban_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->deleteJson("/api/users/ban/{$this->targetUser->id}");

        $this->assertSuccessResponse($response, 'User Baned successfully');
    }

    public function test_admin_cannot_ban_another_admin(): void
    {
        $this->actingAs($this->admin);

        $anotherAdmin = $this->createUserWithRole(RolesEnum::ADMIN);

        $response = $this->deleteJson("/api/users/ban/{$anotherAdmin->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_admin_cannot_ban_owner(): void
    {
        $this->actingAs($this->admin);

        $response = $this->deleteJson("/api/users/ban/{$this->owner->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_owner_can_ban_admin(): void
    {
        $this->actingAs($this->owner);

        $response = $this->deleteJson("/api/users/ban/{$this->admin->id}");

        $this->assertSuccessResponse($response, 'User Baned successfully');
    }

    public function test_non_admin_non_owner_cannot_ban_user(): void
    {
        $this->actingAs($this->frontend);

        $response = $this->deleteJson("/api/users/ban/{$this->targetUser->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_ban_fails_for_nonexistent_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->deleteJson('/api/users/ban/99999');

        $response->assertNotFound();
    }

    public function test_cannot_ban_already_banned_user(): void
    {
        $this->actingAs($this->owner);

        $this->targetUser->delete();

        $response = $this->deleteJson("/api/users/ban/{$this->targetUser->id}");

        $response->assertNotFound();
    }

    // ═══════════════════════════════════════════════════════════════
    // DELETE (Force Delete) - DELETE /api/users/destroy/{id}
    // ═══════════════════════════════════════════════════════════════

    public function test_owner_can_force_delete_banned_user(): void
    {
        $this->actingAs($this->owner);

        $this->targetUser->delete();

        $response = $this->deleteJson("/api/users/destroy/{$this->targetUser->id}");

        $this->assertSuccessResponse($response, 'User deleted successfully');

        $this->assertDatabaseMissing('users', ['id' => $this->targetUser->id]);
    }

    public function test_admin_can_force_delete_banned_user(): void
    {
        $this->actingAs($this->admin);

        $this->targetUser->delete();

        $response = $this->deleteJson("/api/users/destroy/{$this->targetUser->id}");

        $this->assertSuccessResponse($response, 'User deleted successfully');
    }

    public function test_owner_can_force_delete_active_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->deleteJson("/api/users/destroy/{$this->targetUser->id}");

        $this->assertSuccessResponse($response, 'User deleted successfully');

        $this->assertDatabaseMissing('users', ['id' => $this->targetUser->id]);
    }

    public function test_admin_cannot_force_delete_another_admin(): void
    {
        $this->actingAs($this->admin);

        $anotherAdmin = $this->createUserWithRole(RolesEnum::ADMIN);
        $anotherAdmin->delete();

        $response = $this->deleteJson("/api/users/destroy/{$anotherAdmin->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_admin_cannot_force_delete_owner(): void
    {
        $this->actingAs($this->admin);

        $this->owner->delete();

        $response = $this->deleteJson("/api/users/destroy/{$this->owner->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_owner_can_force_delete_admin(): void
    {
        $this->actingAs($this->owner);

        $this->admin->delete();

        $response = $this->deleteJson("/api/users/destroy/{$this->admin->id}");

        $this->assertSuccessResponse($response, 'User deleted successfully');
    }

    public function test_non_admin_non_owner_cannot_force_delete_user(): void
    {
        $this->actingAs($this->ui);

        $this->targetUser->delete();

        $response = $this->deleteJson("/api/users/destroy/{$this->targetUser->id}");

        $this->assertUnauthorizedResponse($response);
    }

    public function test_force_delete_fails_for_nonexistent_user(): void
    {
        $this->actingAs($this->owner);

        $response = $this->deleteJson('/api/users/destroy/99999');

        $response->assertNotFound();
    }

    // ═══════════════════════════════════════════════════════════════
    // USER RESOURCE STRUCTURE
    // ═══════════════════════════════════════════════════════════════

    public function test_user_resource_returns_correct_structure(): void
    {
        $this->actingAs($this->owner);

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                            'created_at',
                            'updated_at',
                            'roles',
                            'projects'
                        ],
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_user_resource_includes_loaded_relations(): void
    {
        $this->actingAs($this->owner);

        $response = $this->getJson('/api/users');

        $response->assertOk();

        $firstUser = $response->json('data.data.0');
        $this->assertNotNull($firstUser);
        $this->assertArrayHasKey('roles', $firstUser);
        $this->assertArrayHasKey('projects', $firstUser);
    }

    // ═══════════════════════════════════════════════════════════════
    // EDGE CASES
    // ═══════════════════════════════════════════════════════════════

    public function test_owner_can_manage_all_roles(): void
    {
        $this->actingAs($this->owner);

        foreach ([$this->admin, $this->backend, $this->frontend, $this->ui] as $user) {
            $response = $this->postJson("/api/users/{$user->id}", [
                'names' => [RolesEnum::UI->value],
            ]);
            $this->assertSuccessResponse($response, 'User role updated successfully');
        }
    }

    public function test_cannot_access_with_invalid_http_method(): void
    {
        $this->actingAs($this->owner);

        $response = $this->putJson('/api/users');

        $response->assertMethodNotAllowed();
    }

    public function test_user_factory_creates_valid_user(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);

        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertEquals('user_favicon.jpg', $user->favicon);
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
