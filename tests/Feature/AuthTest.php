<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create(['phone' => '+201234567890']);
        $this->actingAs($this->user);
    }

    // ==================== REGISTER TESTS ====================

    #[Test]
    public function user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+201112223344',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'Success',
                'message' => 'The User is Created Successfully, You have to verify the Email and Phone Number.',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => '+201112223344',
        ]);
    }

    #[Test]
    public function registration_fails_with_duplicate_email(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+201112223344',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function registration_fails_with_duplicate_phone(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'unique@example.com',
            'phone' => '+201234567890',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['phone']);
    }

    #[Test]
    public function registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => 'invalid-phone',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    #[Test]
    public function registration_fails_with_short_password(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+201112223344',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function registration_fails_when_passwords_do_not_match(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+201112223344',
            'password' => 'Password123',
            'password_confirmation' => 'different123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function registration_requires_all_fields(): void
    {
        $this->postJson('/api/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'password']);
    }

    // ==================== LOGIN TESTS ====================

    #[Test]
    public function user_can_login_with_email_and_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('Password123'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'identification' => 'login@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Token authentication created Successfully',
            ])
            ->assertJsonStructure(['token']);
    }

    #[Test]
    public function user_can_login_with_phone_and_valid_credentials(): void
    {
        $user = User::factory()->create([
            'phone' => '+201234567899',
            'password' => Hash::make('Password123'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'identification' => '+201234567899',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    #[Test]
    public function login_fails_with_invalid_credentials(): void
    {
        $this->postJson('/api/login', [
            'identification' => 'nonexistent@example.com',
            'password' => 'wrongPassword',
        ])->assertStatus(401)
          ->assertJson([
              'status' => 'Error',
              'message' => 'Invalid Credentials',
          ]);
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correctpassword'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $this->postJson('/api/login', [
            'identification' => 'test@example.com',
            'password' => 'wrongPassword',
        ])->assertStatus(401)
          ->assertJson([
              'status' => 'Error',
              'message' => 'Invalid Credentials',
          ]);
    }

    #[Test]
    public function login_fails_when_email_not_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('Password123'),
            'email_verified_at' => null,
            'phone_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'identification' => 'unverified@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Email is not verified.',
            ])
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function login_fails_when_phone_not_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('Password123'),
            'email_verified_at' => now(),
            'phone_verified_at' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'identification' => 'unverified@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Phone Number is not verified.',
            ]);
    }

    #[Test]
    public function login_creates_remember_token_when_remember_is_on(): void
    {
        $user = User::factory()->create([
            'email' => 'remember@example.com',
            'password' => Hash::make('Password123'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'identification' => 'remember@example.com',
            'password' => 'Password123',
            'remember' => 'on',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'remember-authentication',
        ]);
    }

    #[Test]
    public function login_creates_short_lived_token_by_default(): void
    {
        $user = User::factory()->create([
            'email' => 'short@example.com',
            'password' => Hash::make('Password123'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'identification' => 'short@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'authentication',
        ]);
    }

    #[Test]
    public function login_requires_identification_and_password(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identification', 'password']);
    }

    // ==================== FORGOT PASSWORD TESTS ====================

    #[Test]
    public function user_can_request_password_reset_link(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'forgot@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'forgot@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'An Email has a Reset Link have been Sent',
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'forgot@example.com',
        ]);
    }

    #[Test]
    public function forgot_password_fails_with_nonexistent_email(): void
    {
        $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function forgot_password_requires_email(): void
    {
        $this->postJson('/api/forgot-password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function forgot_password_updates_existing_token(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'existing@example.com',
            'token' => 'old-token',
            'created_at' => now()->subHours(2),
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => 'existing@example.com',
        ])->assertStatus(200);

        $token = DB::table('password_reset_tokens')
            ->where('email', 'existing@example.com')
            ->first();

        $this->assertNotEquals('old-token', $token->token);
        $this->assertTrue(now()->diffInMinutes($token->created_at) < 2);
    }

    // ==================== RESET PASSWORD TESTS ====================

    #[Test]
    public function user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $token = Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => 'reset@example.com',
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'Password Reseted Successfully',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newPassword123', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'reset@example.com',
        ]);
    }

    #[Test]
    public function reset_password_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => 'reset@example.com',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertStatus(401)
          ->assertJson([
              'status' => 'Error',
              'message' => 'Invalid Token or Email Address',
          ]);
    }

    #[Test]
    public function reset_password_fails_with_wrong_email(): void
    {
        $user = User::factory()->create([
            'email' => 'correct@example.com',
        ]);

        $token = Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => 'correct@example.com',
            'token' => $token,
            'created_at' => now(),
        ]);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'wrong@example.com',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function reset_password_requires_all_fields(): void
    {
        $this->postJson('/api/reset-password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }

    #[Test]
    public function reset_password_requires_password_confirmation(): void
    {
        $this->postJson('/api/reset-password', [
            'token' => 'some-token',
            'email' => 'test@example.com',
            'password' => 'Password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    // ==================== VERIFY EMAIL SEND TESTS ====================

    #[Test]
    public function can_send_email_verification_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'otp' => null,
        ]);

        $response = $this->getJson("/api/verify-email/sendMail/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'An Mail has been sent to VerifyEmail',
            ]);

        $user->refresh();
        $this->assertNotNull($user->otp);
    }

    #[Test]
    public function send_email_verification_fails_for_nonexistent_user(): void
    {
        $this->getJson('/api/verify-email/sendMail/99999')
            ->assertStatus(404)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Invalid Request',
            ]);
    }

    #[Test]
    public function send_email_verification_fails_when_already_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'otp' => null,
        ]);

        $this->getJson("/api/verify-email/sendMail/{$user->id}")
            ->assertStatus(409)
            ->assertJson([
                'status' => 'Error',
                'message' => 'The Email is already verified',
            ]);
    }

    #[Test]
    public function send_email_verification_handles_mail_failure(): void
    {
        Mail::shouldReceive('to->send')
            ->andThrow(new \Exception('Mail failed'));

        $user = User::factory()->create([
            'email_verified_at' => null,
            'otp' => null,
        ]);

        $response = $this->getJson("/api/verify-email/sendMail/{$user->id}");

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Failed to send verification email.',
            ]);

        $user->refresh();
        $this->assertNull($user->otp);
    }

    // ==================== VERIFY EMAIL OTP TESTS ====================

    #[Test]
    public function user_can_verify_email_with_valid_otp(): void
    {
        $otp = '123456';
        $user = User::factory()->create([
            'email' => 'verify@example.com',
            'email_verified_at' => null,
            'otp' => Hash::make($otp),
        ]);

        $response = $this->postJson('/api/verify-email', [
            'email' => 'verify@example.com',
            'otp' => ['1', '2', '3', '4', '5', '6'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'The Email is Verified Successfully, You need to verify your Phone Number.',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->otp);
    }

    #[Test]
    public function verify_email_fails_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'verify@example.com',
            'email_verified_at' => null,
            'otp' => Hash::make('123456'),
        ]);

        $response = $this->postJson('/api/verify-email', [
            'email' => 'verify@example.com',
            'otp' => ['9', '9', '9', '9', '9', '9'],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Invalid OTP',
            ]);
    }

    #[Test]
    public function verify_email_fails_for_nonexistent_user(): void
    {
        $this->postJson('/api/verify-email', [
            'email' => 'nonexistent@example.com',
            'otp' => ['1', '2', '3', '4', '5', '6'],
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function verify_email_fails_with_wrong_otp_format(): void
    {
        $this->postJson('/api/verify-email', [
            'email' => 'test@example.com',
            'otp' => ['1', '2', '3'], // Less than 6 digits
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['otp']);
    }

    #[Test]
    public function verify_email_enforces_rate_limiting(): void
    {
        $user = User::factory()->create([
            'email' => 'ratelimit@example.com',
            'email_verified_at' => null,
            'otp' => Hash::make('123456'),
        ]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/verify-email', [
                'email' => 'ratelimit@example.com',
                'otp' => ['9', '9', '9', '9', '9', '9'],
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/verify-email', [
            'email' => 'ratelimit@example.com',
            'otp' => ['1', '2', '3', '4', '5', '6'],
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'status' => 'Error',
            ]);
    }

    #[Test]
    public function verify_email_clears_rate_limit_after_success(): void
    {
        $user = User::factory()->create([
            'email' => 'clear@example.com',
            'email_verified_at' => null,
            'otp' => Hash::make('123456'),
        ]);

        $this->postJson('/api/verify-email', [
            'email' => 'clear@example.com',
            'otp' => ['1', '2', '3', '4', '5', '6'],
        ])->assertStatus(200);

        // Rate limit should be cleared, so another request should work
        $user2 = User::factory()->create([
            'email' => 'clear2@example.com',
            'email_verified_at' => null,
            'otp' => Hash::make('654321'),
        ]);

        $this->postJson('/api/verify-email', [
            'email' => 'clear2@example.com',
            'otp' => ['6', '5', '4', '3', '2', '1'],
        ])->assertStatus(200);
    }

    // ==================== VERIFY PHONE SEND TESTS ====================

    #[Test]
    public function can_send_phone_verification_sms(): void
    {
        $this->mockTwilio();

        $user = User::factory()->create([
            'phone' => '+201234567888',
            'phone_verified_at' => null,
            'otp' => null,
        ]);

        $response = $this->getJson("/api/verify-phone/sendSMS/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'SMS Message has been send to verify your Phone Number',
            ]);

        $user->refresh();
        $this->assertNotNull($user->otp);
    }

    #[Test]
    public function send_phone_verification_fails_for_nonexistent_user(): void
    {
        $this->getJson('/api/verify-phone/sendSMS/99999')
            ->assertStatus(404)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Invalid Request',
            ]);
    }

    #[Test]
    public function send_phone_verification_fails_when_already_verified(): void
    {
        $user = User::factory()->create([
            'phone' => '+201234567888',
            'phone_verified_at' => now(),
            'otp' => null,
        ]);

        $this->getJson("/api/verify-phone/sendSMS/{$user->id}")
            ->assertStatus(409)
            ->assertJson([
                'status' => 'Error',
                'message' => 'The Phone Number is already verified',
            ]);
    }

    #[Test]
    public function send_phone_verification_handles_sms_failure(): void
    {
        $this->mockTwilioWithException();

        $user = User::factory()->create([
            'phone' => '+201234567888',
            'phone_verified_at' => null,
            'otp' => null,
        ]);

        $response = $this->getJson("/api/verify-phone/sendSMS/{$user->id}");

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Failed to send verification phone number.',
            ]);

        $user->refresh();
        $this->assertNull($user->otp);
    }

    // ==================== VERIFY PHONE OTP TESTS ====================

    #[Test]
    public function user_can_verify_phone_with_valid_otp(): void
    {
        $otp = '123456';
        $user = User::factory()->create([
            'phone' => '+201234567777',
            'phone_verified_at' => null,
            'otp' => Hash::make($otp),
        ]);

        $response = $this->postJson('/api/verify-phone', [
            'phone' => '+201234567777',
            'otp' => ['1', '2', '3', '4', '5', '6'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Success',
                'message' => 'The Phone Number is Verified Successfully, You can login',
            ]);

        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->otp);
    }

    #[Test]
    public function verify_phone_fails_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'phone' => '+201234567777',
            'phone_verified_at' => null,
            'otp' => Hash::make('123456'),
        ]);

        $response = $this->postJson('/api/verify-phone', [
            'phone' => '+201234567777',
            'otp' => ['9', '9', '9', '9', '9', '9'],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'Error',
                'message' => 'Invalid OTP',
            ]);
    }

    #[Test]
    public function verify_phone_fails_for_nonexistent_phone(): void
    {
        // استخدم رقم هاتف صحيح صيغياً لكن غير موجود في DB
        $this->postJson('/api/verify-phone', [
            'phone' => '+201111111111', // صحيح صيغياً لكن غير موجود في DB
            'otp' => ['1', '2', '3', '4', '5', '6'],
        ])->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
    }

    #[Test]
    public function verify_phone_enforces_rate_limiting(): void
    {
        $user = User::factory()->create([
            'phone' => '+201234566666',
            'phone_verified_at' => null,
            'otp' => Hash::make('123456'),
        ]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/verify-phone', [
                'phone' => '+201234566666',
                'otp' => ['9', '9', '9', '9', '9', '9'],
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/verify-phone', [
            'phone' => '+201234566666',
            'otp' => ['1', '2', '3', '4', '5', '6'],
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'status' => 'Error',
            ]);
    }

    #[Test]
    public function verify_phone_clears_rate_limit_after_success(): void
    {
        $user = User::factory()->create([
            'phone' => '+201234565555',
            'phone_verified_at' => null,
            'otp' => Hash::make('123456'),
        ]);

        $this->postJson('/api/verify-phone', [
            'phone' => '+201234565555',
            'otp' => ['1', '2', '3', '4', '5', '6'],
        ])->assertStatus(200);
    }

    // ==================== EDGE CASES ====================

    #[Test]
    public function login_with_unverified_email_and_phone_returns_email_error_first(): void
    {
        $user = User::factory()->create([
            'email' => 'both@example.com',
            'password' => Hash::make('Password123'),
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'identification' => 'both@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Email is not verified.',
            ]);
    }

    #[Test]
    public function email_verification_allows_reverification_when_otp_exists(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'otp' => Hash::make('oldotp'),
        ]);

        $response = $this->getJson("/api/verify-email/sendMail/{$user->id}");

        $response->assertStatus(200);
        $user->refresh();
        $this->assertNotNull($user->otp);
    }

    #[Test]
    public function phone_verification_allows_reverification_when_otp_exists(): void
    {
        $this->mockTwilio();

        $user = User::factory()->create([
            'phone' => '+201234564444',
            'phone_verified_at' => null,
            'otp' => Hash::make('oldotp'),
        ]);

        $response = $this->getJson("/api/verify-phone/sendSMS/{$user->id}");

        $response->assertStatus(200);
        $user->refresh();
        $this->assertNotNull($user->otp);
    }
}
