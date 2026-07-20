<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VerifyEmailController extends Controller
{
    #[OA\Get(
        path: '/api/verify-email/sendMail/{id}',
        tags: ['Authentication'],
        summary: 'Send Mail',
        description: 'Log in using a verified account by email or phone and password',
        parameters: [
            new OA\Parameter(name: "id",description: "User ID",in: "path",required: true,schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Success'),
                        new OA\Property(property: 'message',type: 'string',example: 'An Mail has been sent to VerifyEmail'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Invalid Request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Error'),
                        new OA\Property(property: 'message',type: 'string',example: 'Invalid Request'),
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'The Email is already verified',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Error'),
                        new OA\Property(property: 'message',type: 'string',example: 'The Email is already verified'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal Server Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Error'),
                        new OA\Property(property: 'message',type: 'string',example: 'Failed to send verification email.'),
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            ),
        ]
    )]
    public function send(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid Request'
            ],404);
        }
        if($user->email_verified_at and is_null($user->otp)){
            return response()->json([
                'status' => 'Error',
                'message' => 'The Email is already verified'
            ],409);
        }

        $otp = random_int(100000,999999);
        $user->update(['otp' => Hash::make($otp)]);
        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user->email, $otp));
        } catch (\Exception $e) {
            $user->update(['otp' => null]);
            return response()->json(
                [
                    'status' => 'Error',
                    'message' => 'Failed to send verification email.',
            ],500);
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'An Mail has been sent to VerifyEmail',
        ],200);
    }

    #[OA\Post(
        path: '/api/verify-email',
        tags: ['Authentication'],
        summary: 'Verify user email using OTP',
        description: 'Verify user email before Login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp'],
                properties: [
                    new OA\Property(property: 'email',type: 'string',format: 'email',description: 'email|exists:users,email',example: 'ahmed@example.com'),
                    new OA\Property(property: 'otp',type: 'array',description: '6 digit OTP code',items: new OA\Items(type: 'integer',example: 1),example: [1, 2, 3, 4, 5, 6])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email verified successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Success'),
                        new OA\Property(property: 'message',type: 'string',example: 'The Email is Verified Successfully, You need to verify your Phone Number.'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid OTP',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Error'),
                        new OA\Property(property: 'message',type: 'string',example: 'Invalid OTP')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found or invalid request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Error'),
                        new OA\Property(property: 'message',type: 'string',example: 'Invalid Request')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 429,
                description: 'Too many attempts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Error'),
                        new OA\Property(property: 'message',type: 'string',example: 'Try again after 120 seconds')
                    ]
                )
            )
        ]
    )]
    public function verify(VerifyEmailRequest $request)
    {
        $key = 'verify-email:' . $request->ip() . ':' . $request->email;

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'status'  => 'Error',
                'message' => "Try again after {$seconds} seconds",
            ], 429);
        }
        RateLimiter::hit($key, 120);

        $user = User::where('email',$request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid Request'
            ],404);
        }

        if(!Hash::check(implode('',$request->otp),$user->otp)){
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid OTP'
            ],400);
        }
        $user->update([
            'email_verified_at' => now(),
            'otp' => null
        ]);
        RateLimiter::clear($key);

        return response()->json(
        [
            'status' => 'Success',
            'message' => "The Email is Verified Successfully, You need to verify your Phone Number.",
        ],200);
    }
}
