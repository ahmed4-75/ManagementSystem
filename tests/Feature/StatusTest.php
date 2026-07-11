<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

        // ==================== INDEX ====================
    public function test_can_get_statuses_for_project(): void
    {
        $project = Project::factory()->create();

        Status::factory()->count(2)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/statuses/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Statuses retrieved successfully',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_index_returns_only_current_user_statuses(): void
    {
        $project = Project::factory()->create();
        $otherUser = User::factory()->create();

        Status::factory()->create([
            'name' => 'My Status',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        Status::factory()->create([
            'name' => 'Other Status',
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/statuses/{$project->id}");

        $response->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'My Status'])
            ->assertJsonMissing(['name' => 'Other Status']);
    }

    public function test_index_returns_only_project_statuses(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        Status::factory()->create([
            'name' => 'Project1 Status',
            'project_id' => $project1->id,
            'user_id' => $this->user->id,
        ]);

        Status::factory()->create([
            'name' => 'Project2 Status',
            'project_id' => $project2->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/statuses/{$project1->id}");

        $response->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Project1 Status'])
            ->assertJsonMissing(['name' => 'Project2 Status']);
    }

    public function test_index_returns_empty_when_no_statuses(): void
    {
        $project = Project::factory()->create();

        $response = $this->getJson("/api/statuses/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // ==================== STORE ====================
    public function test_can_create_status(): void
    {
        $project = Project::factory()->create();

        // ربط المشروع بالمستخدم
        $project->users()->attach($this->user->id);

        $data = [
            'name' => 'In Progress',
        ];

        $response = $this->postJson("/api/statuses/{$project->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Status created successfully',
            ]);

        $this->assertDatabaseHas('statuses', [
            'name' => 'In Progress',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);
    }

    // ====== STORE VALIDATION TESTS ======
    public function test_store_requires_name(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson("/api/statuses/{$project->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_name_must_be_string(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson("/api/statuses/{$project->id}", [
            'name' => 12345,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_name_must_not_exceed_255_chars(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson("/api/statuses/{$project->id}", [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // ==================== UPDATE ====================
    public function test_can_update_status(): void
    {
        $project = Project::factory()->create();

        $status = Status::factory()->create([
            'name' => 'Old Name',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $data = [
            'name' => 'Updated Name',
        ];

        $response = $this->putJson("/api/statuses/{$project->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Status updated successfully',
            ]);

        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'name' => 'Updated Name',
        ]);

        $this->assertDatabaseMissing('statuses', [
            'id' => $status->id,
            'name' => 'Old Name',
        ]);
    }

    public function test_update_only_affects_current_user_status(): void
    {
        $project = Project::factory()->create();
        $otherUser = User::factory()->create();

        $myStatus = Status::factory()->create([
            'name' => 'My Status',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $otherStatus = Status::factory()->create([
            'name' => 'Other Status',
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->putJson("/api/statuses/{$project->id}", [
            'name' => 'Updated',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('statuses', [
            'id' => $myStatus->id,
            'name' => 'Updated',
        ]);

        $this->assertDatabaseHas('statuses', [
            'id' => $otherStatus->id,
            'name' => 'Other Status',
        ]);
    }

    // ====== UPDATE VALIDATION TESTS ======
    public function test_update_requires_name(): void
    {
        $project = Project::factory()->create();

        Status::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/statuses/{$project->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_name_must_be_string(): void
    {
        $project = Project::factory()->create();

        Status::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/statuses/{$project->id}", [
            'name' => 12345,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_name_must_not_exceed_255_chars(): void
    {
        $project = Project::factory()->create();

        Status::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/statuses/{$project->id}", [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // ==================== DELETE ====================
    public function test_can_delete_status(): void
    {
        $status = Status::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/statuses/{$status->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Status deleted successfully',
            ]);

        $this->assertDatabaseMissing('statuses', [
            'id' => $status->id,
        ]);
    }

    public function test_cannot_delete_status_with_tasks(): void
    {
        $project = Project::factory()->create();

        $status = Status::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        // Create task with this status using DB insert to avoid factory complications
        // Task::create([
        //     'title' => 'Test Task',
        //     'description' => 'Test',
        //     'user_id' => $this->user->id,
        //     'project_id' => $project->id,
        //     'status_id' => $status->id,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);
        Task::factory()->create([
            'status_id' => $status->id,
            'user_id' => $this->user->id,
            'project_id' => $project->id,
        ]);

        $response = $this->deleteJson("/api/statuses/{$status->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Tasks are assigned to this status. You cannot delete it.',
            ]);
    }

    public function test_delete_returns_404_for_nonexistent_status(): void
    {
        $response = $this->deleteJson('/api/statuses/99999');

        $response->assertStatus(404);
    }

    // ==================== SERVICE LAYER TESTS ====================
    public function test_status_service_index_returns_user_project_statuses(): void
    {
        $project = Project::factory()->create();

        Status::factory()->create([
            'name' => 'New',
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);

        $service = app(StatusService::class);
        $result = $service->index($project->id);

        $this->assertCount(1, $result);
        $this->assertEquals('New', $result->first()->name);
    }
}
