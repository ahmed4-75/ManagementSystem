<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LoginNoPasswordController extends Controller
{
    #[OA\Post(
        path: '/api/forgot-password',
        tags: ['Authentication'],
        summary: 'Forgot Password',
        description: 'Sending a token in Mail to allow Reset Password',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email',type: 'string',format: 'email',description: 'exists:users,email',maxLength: 50,example: 'user@test.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Success'),
                        new OA\Property(property: 'message',type: 'string',example: 'An Email has a Reset Link have been Sent'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            [ 'email' => $request->email ],
            [ 'token' => $token , 'created_at' => now() ]
        );
        Mail::to($request->email)->send(new ResetPasswordMail($token));
        return response()->json([
            'status' => 'Success',
            'message' => 'An Email has a Reset Link have been Sent'
        ],200);
    }

    #[OA\Post(
        path: "/api/reset-password",
        tags: ["Authentication"],
        summary: "Reset Password",
        description: "Verify the token and email, and Reset Password",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ["email", "token", "password"],
                properties: [
                    new OA\Property(property: "token", type: "string", example: "f4e7b3c9a0b1d2e3..."),
                    new OA\Property(property: "email",type: "string",format: "email",description: "exists:users,email",maxLength: 50,example: "ahmed@example.com"),
                    new OA\Property(property: 'password',type: 'string',format: 'password',minLength: 6,description: 'Min 6 characters, must contain uppercase and lowercase letters.',example: 'Password123'),
                    new OA\Property(property: 'password_confirmation',type: 'string',format: 'password',description: 'Must match the password field.',example: 'Password123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password reset successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Success"),
                        new OA\Property(property: "message", type: "string", example: "Password Reseted Successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Invalid token or validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "Error"),
                        new OA\Property(property: "message", type: "string", example: "Invalid Token or Email Address")
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request)
    {
        $result = DB::table('password_reset_tokens')
        ->where('token',$request->token)
        ->where('email',$request->email)
        ->first();

        if(!$result){
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid Token or Email Address'
            ],401);
        }
        DB::table('password_reset_tokens')->where('email',$request->email)->delete();

        $user = User::where('email',$request->email)->first();
        $user->update(["password" => Hash::make($request->password)]);

        return response()->json([
            'status' => 'Success',
            'message' => 'Password Reseted Successfully'
        ],200);
    }
}
