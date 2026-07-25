<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class RegisterController extends Controller
{
    #[OA\Post(
        path: '/api/register',
        summary: 'Register new user',
        tags: ['Authentication'],
        description: 'Create new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'email', 'phone', 'password', 'password_confirmation'],
                    properties: [
                        new OA\Property(property: 'name',type: 'string',maxLength: 50,example: 'Ahmed Morgan'),
                        new OA\Property(property: 'email',type: 'string',format: 'email',description: 'email|unique:users,email',example: 'ahmed@example.com'),
                        new OA\Property(property: 'password',type: 'string',format: 'password',minLength: 6,description: 'Min 6 characters, must contain uppercase and lowercase letters.',example: 'Password123'),
                        new OA\Property(property: 'password_confirmation',type: 'string',format: 'password',description: 'Must match the password field.',example: 'Password123'),
                        new OA\Property(property: 'phone',type: 'string',description: 'phone|unique:users,phone',example: '+201234567890')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status',type: 'string',example: 'Success'),
                        new OA\Property(property: 'message',type: 'string',example: 'The User is Created Successfully, You have to verify the Email and Phone Number.'),
                        new OA\Property(property: 'data',type: 'object',ref: '#/components/schemas/UserResource')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation Error'
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function __invoke(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'favicon' => 'user_favicon.jpg'
        ]);

        return response()->json(
            [
                'status' => 'Success',
                'message' => 'The User is Created Successfully, You have to verify the Email and Phone Number.',
                'data' => new UserResource($user)
        ],201);
    }
}
