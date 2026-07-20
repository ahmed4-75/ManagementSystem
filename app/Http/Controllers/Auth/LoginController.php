<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class LoginController extends Controller
{
    #[OA\Post(
        path: '/api/login',
        tags: ['Authentication'],
        summary: 'User login',
        description: 'Log in using a verified account by email or phone and password',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['identification', 'password'],
                properties: [
                    new OA\Property(property: 'identification',type: 'string',maxLength: 50,description: 'Email or phone number',example: 'ahmed@example.com'),
                    new OA\Property(property: 'password',type: 'string',minLength: 6,example: '123456'),
                    new OA\Property(property: 'remember',type: 'string',enum: ['on', 'off'],example: 'off')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Success'),
                        new OA\Property(property: 'message',type: 'string',example: 'Token authentication created Successfully'),
                        new OA\Property(property: 'token',type: 'string',example: '1|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Error'),
                        new OA\Property(property: 'message',type: 'string',example: 'Invalid Credentials')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'User email or phone number not verified',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Error'),
                        new OA\Property(property: 'message',type: 'string',example: 'Email is not verified.'),
                        new OA\Property(property: 'data',type: 'object',ref: '#/components/schemas/UserResource')
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
    public function __invoke(LoginRequest $request)
    {
        $user = User::where('email',$request->identification)->orWhere('phone',$request->identification)->first();

        if($user and Hash::check($request->password,$user->password)){
            if(is_null($user->email_verified_at)){
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Email is not verified.',
                    'data' => new UserResource($user)
                ],403);
            }
            if(is_null($user->phone_verified_at)){
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Phone Number is not verified.',
                    'data' => new UserResource($user)
                ],403);
            }
            if($request->remember === 'on'){
                $token = $user->createToken('remember-authentication',['*'],Carbon::now()->addMonths(6))->plainTextToken;
            }
            else{
                $token = $user->createToken('authentication',['*'] ,Carbon::now()->addHours(2))->plainTextToken;
            }
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Token authentication created Successfully',
                    'token' => $token
            ],200);
        }

        return response()->json(
            [
                'status' => 'Error',
                'message' => 'Invalid Credentials',
        ],401);
    }
}
