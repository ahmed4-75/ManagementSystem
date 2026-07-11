<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use Twilio\Rest\Client;
use App\Http\Requests\Auth\VerifyPhoneRequest;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VerifyPhoneController extends Controller
{
    #[OA\Get(
        path: "/api/verify-phone/sendSMS/{id}",
        summary: "Send OTP to phone number",
        tags: ["Authentication"],
        description: "Send SMS verification code to the user's phone number.",
        parameters: [
            new OA\Parameter(name: "id",description: "User ID",in: "path",required: true,schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Success"),
                        new OA\Property(property: "message",type: "string",example: "SMS Message has been send to verify your Phone Number")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "User not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Error"),
                        new OA\Property(property: "message", type: "string", example: "Invalid Request")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "Phone already verified",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Error"),
                        new OA\Property(property: "message",type: "string",example: "The Phone Number is already verified")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Failed to send SMS",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Error"),
                        new OA\Property(property: "message",type: "string",example: "Failed to send verification phone number.")
                    ]
                )
            )
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
        if($user->phone_verified_at and is_null($user->otp)){
            return response()->json([
                'status' => 'Error',
                'message' => 'The Phone Number is already verified'
            ],409);
        }

        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from  = config('services.twilio.from');

        // $client = new Client($sid, $token);
        $client = app()->make(Client::class, ['username' => $sid,'password' => $token]);

        $otp = random_int(100000,999999);
        $user->update(['otp' => Hash::make($otp)]);
        try {
            $client->messages->create(
                $user->phone,
                [
                    'from' => $from,
                    'body' => 'Phone Number verification, Your OTP is: '.$otp,
                ]
            );
        } catch (\Exception $e) {
            $user->update(['otp' => null]);
            return response()->json(
                [
                    'status' => 'Error',
                    'message' => 'Failed to send verification phone number.',
            ],500);
        }

        return response()->json(
        [
            'status' => 'Success',
            'message' => "SMS Message has been send to verify your Phone Number",
        ],200);
    }

    #[OA\Post(
        path: "/api/verify-phone",
        summary: "Verify phone number using OTP",
        tags: ["Authentication"],
        description: "Verify user's phone number using the OTP sent by SMS.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["phone", "otp"],
                properties: [
                    new OA\Property(property: "phone",type: "string",example: "+201069485141",description: "User phone number"),
                    new OA\Property(property: "otp",type: "array",description: '6 digit OTP code',items: new OA\Items(type: "integer",example: 1),example: [1, 2, 3, 4, 5, 6])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Phone verified successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Success"),
                        new OA\Property(property: "message",type: "string",example: "The Phone Number is Verified Successfully, You can login")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid OTP",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Error"),
                        new OA\Property(property: "message", type: "string", example: "Invalid OTP")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "User not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Error"),
                        new OA\Property(property: "message", type: "string", example: "Invalid Request")
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: "Too many attempts",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Error"),
                        new OA\Property(
                            property: "message",
                            type: "string",
                            example: "Try again after 120 seconds"
                        )
                    ]
                )
            )
        ]
    )]
    public function verify(VerifyPhoneRequest $request)
    {
        $key = 'verify-email:' . $request->ip() . ':' . $request->phone;

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'status'  => 'Error',
                'message' => "Try again after {$seconds} seconds",
            ], 429);
        }
        RateLimiter::hit($key, 120);

        $user = User::where('phone',$request->phone)->first();
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
            'phone_verified_at' => now(),
            'otp' => null
        ]);
        RateLimiter::clear($key);

        return response()->json(
        [
            'status' => 'Success',
            'message' => "The Phone Number is Verified Successfully, You can login",
        ],200);
    }
}
