<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Role::firstOrCreate(['name' => 'owner']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('name', 'owner')->first());
        $this->user2 = User::factory()->create();
        $this->actingAs($this->user);
    }

    // ==================== INDEX ====================
    public function test_can_get_all_projects(): void
    {
        Project::factory()->count(5)->create();

        $response = $this->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                    ]
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Projects retrieved successfully',
            ]);
    }

    // ==================== PROJECTS USER ====================
    public function test_can_get_user_projects(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        $response = $this->getJson('/api/projects/user');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Projects User retrieved successfully',
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_user_projects_returns_only_assigned_projects(): void
    {
        $assignedProject = Project::factory()->create();
        $assignedProject->users()->attach($this->user->id);

        $otherProject = Project::factory()->create();

        $response = $this->getJson('/api/projects/user');

        $response->assertJsonCount(1, 'data');
    }

    // ==================== SHOW ====================
    public function test_can_show_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                ],
                'status',
                'message',
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Project retrieved successfully',
            ]);
    }

    public function test_show_returns_404_for_nonexistent_project(): void
    {
        $response = $this->getJson('/api/projects/99999');

        $response->assertStatus(404);
    }

    // ==================== STORE ====================
    public function test_can_create_project(): void
    {
        $user2 = User::factory()->create();

        $data = [
            'title' => 'New Project',
            'description' => 'Project Description',
            'usersIds' => [$this->user->id, $user2->id],
        ];

        $response = $this->postJson('/api/projects', $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Project created successfully, and statuses have been created for each user in the project.',
            ]);

        $this->assertDatabaseHas('projects', [
            'title' => 'New Project',
            'description' => 'Project Description',
        ]);

        $project = Project::where('title', 'New Project')->first();

        $this->assertDatabaseHas('statuses', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'name' => 'New',
        ]);
        $this->assertDatabaseHas('statuses', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'name' => 'Completed',
        ]);

        $this->assertDatabaseHas('statuses', [
            'project_id' => $project->id,
            'user_id' => $user2->id,
            'name' => 'New',
        ]);
        $this->assertDatabaseHas('statuses', [
            'project_id' => $project->id,
            'user_id' => $user2->id,
            'name' => 'Completed',
        ]);
    }

    // ====== STORE VALIDATION TESTS ======
    public function test_store_requires_title(): void
    {
        $user2 = User::factory()->create();

        $response = $this->postJson('/api/projects', [
            'description' => 'Description',
            'usersIds' => [$user2->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_title_must_be_string(): void
    {
        $user2 = User::factory()->create();

        $response = $this->postJson('/api/projects', [
            'title' => 12345,
            'description' => 'Description',
            'usersIds' => [$user2->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_title_must_not_exceed_255_chars(): void
    {
        $user2 = User::factory()->create();

        $response = $this->postJson('/api/projects', [
            'title' => str_repeat('a', 256),
            'description' => 'Description',
            'usersIds' => [$user2->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_requires_description(): void
    {
        $user2 = User::factory()->create();

        $response = $this->postJson('/api/projects', [
            'title' => 'Title',
            'usersIds' => [$user2->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_store_description_must_be_string(): void
    {
        $user2 = User::factory()->create();

        $response = $this->postJson('/api/projects', [
            'title' => 'Title',
            'description' => 12345,
            'usersIds' => [$user2->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_store_requires_users_ids(): void
    {
        $response = $this->postJson('/api/projects', [
            'title' => 'Title',
            'description' => 'Description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usersIds']);
    }

    public function test_store_users_ids_must_be_array(): void
    {
        $response = $this->postJson('/api/projects', [
            'title' => 'Title',
            'description' => 'Description',
            'usersIds' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usersIds']);
    }

    public function test_store_users_ids_must_exist_in_users_table(): void
    {
        $nonExistentUserId = 99999;

        $response = $this->postJson('/api/projects', [
            'title' => 'Title',
            'description' => 'Description',
            'usersIds' => [$nonExistentUserId],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usersIds.0']);
    }

    public function test_store_all_fields_missing(): void
    {
        $response = $this->postJson('/api/projects', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'usersIds']);
    }

    public function test_store_validates_each_user_id_in_array(): void
    {
        $existingUser = User::factory()->create();
        $nonExistentUserId = 99999;

        $response = $this->postJson('/api/projects', [
            'title' => 'Title',
            'description' => 'Description',
            'usersIds' => [$existingUser->id, $nonExistentUserId],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usersIds.1']);
    }

    // ==================== UPDATE ====================
    public function test_can_update_project(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        $newUser = User::factory()->create();

        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'usersIds' => [$newUser->id],
        ];

        $response = $this->putJson("/api/projects/{$project->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Project updated successfully, if you changed the users, the statuses have been created for each new user in the project and the tasks of removed users have been deleted.',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);
    }

    public function test_update_removes_old_users_and_adds_new_users(): void
    {
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();

        $project = Project::factory()->create();
        $project->users()->attach([$oldUser->id, $this->user->id]);

        Status::create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $oldUser->id,
        ]);

        $data = [
            'title' => 'Updated Project',
            'description' => 'Updated Description',
            'usersIds' => [$newUser->id],
        ];

        $response = $this->putJson("/api/projects/{$project->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $oldUser->id,
        ]);

        $this->assertDatabaseMissing('statuses', [
            'project_id' => $project->id,
            'user_id' => $oldUser->id,
        ]);

        $this->assertDatabaseHas('statuses', [
            'project_id' => $project->id,
            'user_id' => $newUser->id,
            'name' => 'New',
        ]);
    }

    // ====== UPDATE VALIDATION TESTS ======
    public function test_update_requires_title(): void
    {
        $project = Project::factory()->create();

        $response = $this->putJson("/api/projects/{$project->id}", [
            'description' => 'Description',
            'usersIds' => [$this->user->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_update_title_must_be_string(): void
    {
        $project = Project::factory()->create();

        $response = $this->putJson("/api/projects/{$project->id}", [
            'title' => 12345,
            'description' => 'Description',
            'usersIds' => [$this->user->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_update_title_must_not_exceed_255_chars(): void
    {
        $project = Project::factory()->create();

        $response = $this->putJson("/api/projects/{$project->id}", [
            'title' => str_repeat('a', 256),
            'description' => 'Description',
            'usersIds' => [$this->user->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_update_requires_description(): void
    {
        $project = Project::factory()->create();

        $response = $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Title',
            'usersIds' => [$this->user->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_update_description_must_be_string(): void
    {
        $project = Project::factory()->create();

        $response = $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Title',
            'description' => 12345,
            'usersIds' => [$this->user->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_update_requires_users_ids(): void
    {
        $project = Project::factory()->create();

        $response = $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Title',
            'description' => 'Description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usersIds']);
    }

    public function test_update_users_ids_must_be_array(): void
    {
        $project = Project::factory()->create();

        $response = $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Title',
            'description' => 'Description',
            'usersIds' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usersIds']);
    }

    public function test_update_users_ids_must_exist_in_users_table(): void
    {
        $project = Project::factory()->create();
        $nonExistentUserId = 99999;

        $response = $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Title',
            'description' => 'Description',
            'usersIds' => [$nonExistentUserId],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usersIds.0']);
    }

    public function test_update_returns_404_for_nonexistent_project(): void
    {
        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'usersIds' => [$this->user->id],
        ];

        $response = $this->putJson('/api/projects/99999', $data);

        $response->assertStatus(404);
    }

    // ==================== DELETE ====================
    public function test_can_delete_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Project deleted successfully',
            ]);

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_cannot_delete_project_with_tasks(): void
    {
        $project = Project::factory()->create();

        $status = Status::create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'status_id' => $status->id,
        ]);

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Tasks are assigned to this project. You cannot delete it.',
            ]);
    }

    public function test_delete_returns_404_for_nonexistent_project(): void
    {
        $response = $this->deleteJson('/api/projects/99999');

        $response->assertStatus(404);
    }

    // ==================== SERVICE LAYER TESTS ====================
    public function test_project_service_index_returns_paginated_results(): void
    {
        Project::factory()->count(15)->create();

        $service = app(ProjectService::class);
        $result = $service->index();

        $this->assertEquals(10, $result->perPage());
    }

    public function test_project_service_projects_user_returns_only_user_projects(): void
    {
        $userProject = Project::factory()->create();
        $userProject->users()->attach($this->user->id);

        $otherProject = Project::factory()->create();

        $service = app(ProjectService::class);
        $result = $service->ProjectsUser();

        $this->assertCount(1, $result);
        $this->assertEquals($userProject->id, $result->first()->id);
    }
}
