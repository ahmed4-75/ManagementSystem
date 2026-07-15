<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create([
            'phone' => '+201234567890',
        ]);
        // $this->actingAs($this->user);
        Sanctum::actingAs($this->user);
    }
    // ==================== GET /profile ====================

    public function test_authenticated_user_can_get_profile()
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'You are in Your Profile',
            ])
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonPath('data.name', $this->user->name)
            ->assertJsonPath('data.email', $this->user->email)
            ->assertJsonPath('data.phone', $this->user->phone)
            ->assertJsonPath('notifications_count', 0);
    }

    public function test_unauthenticated_user_cannot_access_profile()
    {
        // Reset authentication
        auth()->guard('sanctum')->forgetUser();

        $response = $this->getJson('/api/profile');
        $response->assertStatus(401);
    }

    public function test_profile_returns_unread_notifications()
    {
        // Create a notification
        $this->user->notify(new NewProject('Test Project'));

        $response = $this->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonPath('notifications_count', 1)
            ->assertJsonPath('notifications.0.type', 'NewProject')
            ->assertJsonPath('notifications.0.title', 'Test Project')
            ->assertJsonPath('notifications.0.message', 'You have been assigned to a new project');
    }

    public function test_profile_marks_notifications_as_read()
    {
        // Create a notification
        $this->user->notify(new NewProject('Test Project'));

        $this->assertEquals(1, $this->user->unreadNotifications()->count());

        $response = $this->getJson('/api/profile');

        $response->assertStatus(200);

        // After fetching profile, notifications should be marked as read
        $this->assertEquals(0, $this->user->fresh()->unreadNotifications()->count());
    }

    public function test_profile_returns_multiple_notifications()
    {
        $this->user->notify(new NewProject('Project 1'));
        $this->user->notify(new NewProject('Project 2'));
        $this->user->notify(new NewProject('Project 3'));

        $response = $this->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonPath('notifications_count', 3);
    }

    // ==================== POST /profile/update ====================

    public function test_user_can_update_profile()
    {
        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+201234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'Profile Updated Successfully',
            ]);

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('updated@example.com', $this->user->email);
        $this->assertEquals('+201234567890', $this->user->phone);
    }

    public function test_profile_update_requires_name()
    {
        $response = $this->postJson('/api/profile/update', [
            'name' => '',
            'email' => 'updated@example.com',
            'phone' => '+201234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_profile_update_requires_valid_email()
    {
        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'invalid-email',
            'phone' => '+201234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_profile_update_requires_unique_email()
    {
        $anotherUser = User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '+201111111111',
        ]);

        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'existing@example.com',
            'phone' => '+201234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_profile_update_allows_same_email_for_current_user()
    {
        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => $this->user->email,
            'phone' => '+201234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'Profile Updated Successfully',
            ]);
    }

    public function test_profile_update_requires_valid_phone()
    {
        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => 'invalid-phone',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_profile_update_requires_unique_phone()
    {
        $anotherUser = User::factory()->create([
            'phone' => '+201111111111',
            'email' => 'another@example.com',
        ]);

        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+201111111111',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_profile_update_allows_same_phone_for_current_user()
    {
        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => $this->user->phone,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'Profile Updated Successfully',
            ]);
    }

    public function test_user_can_update_profile_with_favicon()
    {
        Storage::fake('b2');

        // Create a fake image file without GD extension
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+201234567890',
            'favicon' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'Profile Updated Successfully',
            ]);

        $this->user->refresh();

        $this->assertTrue(Storage::disk('b2')->exists('favicons/' . $this->user->favicon));
    }

    public function test_profile_update_rejects_invalid_favicon_type()
    {
        Storage::fake('b2');

        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+201234567890',
            'favicon' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['favicon']);
    }

    public function test_profile_update_rejects_oversized_favicon()
    {
        Storage::fake('b2');

        // Create a fake image file larger than 6120KB (approx 7MB)
        $file = UploadedFile::fake()->create('large.jpg', 7000, 'image/jpeg');

        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+201234567890',
            'favicon' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['favicon']);
    }

    public function test_profile_update_deletes_old_favicon()
    {
        Storage::fake('b2');

        // Set an existing favicon
        $this->user->update(['favicon' => 'old_favicon.jpg']);
        Storage::disk('b2')->put('favicons/old_favicon.jpg', 'old content');

        $file = UploadedFile::fake()->create('new_avatar.png', 100, 'image/png');

        $response = $this->postJson('/api/profile/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+201234567890',
            'favicon' => $file,
        ]);

        $response->assertStatus(200);

        $this->assertFalse(Storage::disk('b2')->exists('favicons/old_favicon.jpg'));
    }

    // ==================== PUT /profile/update-password ====================

    public function test_user_can_update_password_with_correct_current_password()
    {
        $response = $this->putJson('/api/profile/update-password', [
            'current_password' => 'password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'Your Password Changed Successfully',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    public function test_password_update_fails_with_incorrect_current_password()
    {
        $response = $this->putJson('/api/profile/update-password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Current Password Incorrect',
            ]);
    }

    public function test_password_update_requires_current_password()
    {
        $response = $this->putJson('/api/profile/update-password', [
            'current_password' => '',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_password_update_requires_new_password_min_length()
    {
        $response = $this->putJson('/api/profile/update-password', [
            'current_password' => 'password',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_password_update_requires_password_confirmation()
    {
        $response = $this->putJson('/api/profile/update-password', [
            'current_password' => 'password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_password_update_requires_all_fields()
    {
        $response = $this->putJson('/api/profile/update-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password', 'new_password']);
    }

    // ==================== POST /logout ====================

    public function test_user_can_logout()
    {
        // Create a token for the user
        $token = $this->user->createToken('test-token')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'Logged out',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_deletes_all_tokens()
    {
        // Create multiple tokens
        $this->user->createToken('token-1');
        $this->user->createToken('token-2');
        $this->user->createToken('token-3');

        $this->assertDatabaseCount('personal_access_tokens', 3);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ==================== DELETE /delete-new-Project-notification ====================

    public function test_user_can_delete_read_new_project_notifications()
    {
        // Create read notifications
        $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\NewProject',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Project 1', 'message' => 'Test'],
            'read_at' => now(),
        ]);

        $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\NewProject',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Project 2', 'message' => 'Test'],
            'read_at' => now(),
        ]);

        $this->assertDatabaseCount('notifications', 2);

        $response = $this->deleteJson('/api/delete-new-Project-notification');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'All New Project notifications have been marked as read is deleted.',
            ]);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_delete_notification_only_deletes_read_notifications()
    {
        // Create a read notification
        $readNotification = $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\NewProject',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Read Project', 'message' => 'Test'],
            'read_at' => now(),
        ]);

        // Create an unread notification
        $unreadNotification = $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\NewProject',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Unread Project', 'message' => 'Test'],
            'read_at' => null,
        ]);

        $response = $this->deleteJson('/api/delete-new-Project-notification');

        $response->assertStatus(200);

        // Only read notification should be deleted
        $this->assertDatabaseMissing('notifications', ['id' => $readNotification->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unreadNotification->id]);
    }

    public function test_delete_notification_only_deletes_new_project_type()
    {
        // Create a NewProject notification (read)
        $newProjectNotification = $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\NewProject',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'New Project', 'message' => 'Test'],
            'read_at' => now(),
        ]);

        // Create a different type notification (read)
        $otherNotification = $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\OtherNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Other', 'message' => 'Test'],
            'read_at' => now(),
        ]);

        $response = $this->deleteJson('/api/delete-new-Project-notification');

        $response->assertStatus(200);

        // Only NewProject notification should be deleted
        $this->assertDatabaseMissing('notifications', ['id' => $newProjectNotification->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);
    }

    public function test_delete_notification_returns_success_when_no_notifications_exist()
    {
        $response = $this->deleteJson('/api/delete-new-Project-notification');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'All New Project notifications have been marked as read is deleted.',
            ]);
    }

    // ==================== Edge Cases ====================

    public function test_profile_update_with_only_required_fields()
    {
        $response = $this->postJson('/api/profile/update', [
            'name' => 'Just Name',
            'email' => 'just@example.com',
            'phone' => '+201234567890',
        ]);

        $response->assertStatus(200);
    }

    public function test_profile_update_name_max_length()
    {
        $response = $this->postJson('/api/profile/update', [
            'name' => str_repeat('a', 51),
            'email' => 'updated@example.com',
            'phone' => '+201234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_password_update_with_same_password()
    {
        $response = $this->putJson('/api/profile/update-password', [
            'current_password' => 'password',
            'new_password' => 'password',
            'new_password_confirmation' => 'password',
        ]);

        // This should technically work since there's no rule preventing same password
        $response->assertStatus(200);
    }
}
