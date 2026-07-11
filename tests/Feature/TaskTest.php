<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'owner']);
        $this->user->roles()->attach($role);
        $this->actingAs($this->user);
    }

     // ==================== INDEX ====================
    public function test_can_get_tasks_for_project(): void
    {
        $project = Project::factory()->create();

        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/tasks/project/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Tasks Project retrieved successfully',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_index_returns_only_project_tasks(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        Task::factory()->create([
            'title' => 'Project1 Task',
            'project_id' => $project1->id,
            'user_id' => $this->user->id,
        ]);

        Task::factory()->create([
            'title' => 'Project2 Task',
            'project_id' => $project2->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/tasks/project/{$project1->id}");

        $response->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Project1 Task'])
            ->assertJsonMissing(['title' => 'Project2 Task']);
    }

    public function test_index_returns_empty_when_no_tasks(): void
    {
        $project = Project::factory()->create();

        $response = $this->getJson("/api/tasks/project/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // ==================== SHOW ====================
    public function test_can_show_task(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_task(): void
    {
        $response = $this->getJson('/api/tasks/99999');

        $response->assertStatus(404);
    }

    // ==================== STORE ====================
    public function test_can_create_task(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        Status::create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $data = [
            'title' => 'New Task',
            'description' => 'Task Description',
        ];

        $response = $this->postJson("/api/tasks/{$project->id}/{$this->user->id}", $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task created successfully',
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'description' => 'Task Description',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_store_fails_when_user_not_assigned_to_project(): void
    {
        $project = Project::factory()->create();
        $otherUser = User::factory()->create();

        $data = [
            'title' => 'New Task',
            'description' => 'Task Description',
        ];

        $response = $this->postJson("/api/tasks/{$project->id}/{$otherUser->id}", $data);

        $response->assertStatus(404);
    }

    public function test_store_fails_when_new_status_not_found(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        // لا Status "New" مُنشأ

        $data = [
            'title' => 'New Task',
            'description' => 'Task Description',
        ];

        $response = $this->postJson("/api/tasks/{$project->id}/{$this->user->id}", $data);

        $response->assertStatus(404);
    }

    // ====== STORE VALIDATION TESTS ======
    public function test_store_requires_title(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        Status::create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/tasks/{$project->id}/{$this->user->id}", [
            'description' => 'Description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_title_must_be_string(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        Status::create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/tasks/{$project->id}/{$this->user->id}", [
            'title' => 12345,
            'description' => 'Description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_title_must_not_exceed_255_chars(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        Status::create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/tasks/{$project->id}/{$this->user->id}", [
            'title' => str_repeat('a', 256),
            'description' => 'Description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_requires_description(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        Status::create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/tasks/{$project->id}/{$this->user->id}", [
            'title' => 'Title',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_store_description_must_be_string(): void
    {
        $project = Project::factory()->create();
        $project->users()->attach($this->user->id);

        Status::create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/tasks/{$project->id}/{$this->user->id}", [
            'title' => 'Title',
            'description' => 12345,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    // ==================== CHANGE STATUS ====================
    public function test_can_change_task_status(): void
    {
        $project = Project::factory()->create();
        $status1 = Status::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);
        $status2 = Status::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'status_id' => $status1->id,
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}/{$status2->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task status updated successfully',
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status_id' => $status2->id,
        ]);
    }

    public function test_change_status_fails_for_nonexistent_task(): void
    {
        $status = Status::factory()->create();

        $response = $this->putJson("/api/tasks/99999/{$status->id}");

        $response->assertStatus(404);
    }

    public function test_change_status_fails_for_nonexistent_status(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}/99999");

        $response->assertStatus(404);
    }

    // ==================== DELETE ====================
    public function test_can_delete_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task deleted successfully',
            ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_delete_returns_404_for_nonexistent_task(): void
    {
        $response = $this->deleteJson('/api/tasks/99999');

        $response->assertStatus(404);
    }

    // ==================== SERVICE LAYER TESTS ====================
    public function test_task_service_index_returns_project_tasks(): void
    {
        $project = Project::factory()->create();

        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $service = app(TaskService::class);
        $result = $service->index($project->id);

        $this->assertCount(3, $result);
    }
}
