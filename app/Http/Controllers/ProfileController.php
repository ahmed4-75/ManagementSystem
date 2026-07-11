<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     tags={"My Profile"},
     *     summary="Show User profile",
     *     description="Returns the profile of the currently authenticated user",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Success"),
     *             @OA\Property(property="message", type="string", example="You are in Your Profile"),
     *             @OA\Property(property="data",type="object",ref="#/components/schemas/UserResource")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        $unreadNotifications = $user->unreadNotifications;
        $data = NotificationResource::collection($unreadNotifications);
        $unreadNotifications->markAsRead();

        return response()->json([
            'status' => 'Success',
            'message' => 'You are in Your Profile',
            'data' => new UserResource($user),
            'notifications' => $data,
            'notifications_count' => $data->count()
        ],200);
    }

    public function DeleteNewProjectNotification()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->notifications()->where('type', 'App\Notifications\NewProject')->whereNotNull('read_at')->delete();

        return response()->json([
            'status' => 'Success',
            'message' => 'All notifications have been marked as read is deleted'
        ],200);
    }

    /**
     * @OA\Put(
     *     path="/api/profile/update-password",
     *     tags={"My Profile"},
     *     summary="Update user password",
     *     description="Update authenticated user password ",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", minLength=6, format="password", example="123456"),
     *             @OA\Property(property="new_password", type="string", minLength=6, format="password", example="123456"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="123456")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Your Password Changed Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Success"),
     *             @OA\Property(property="message", type="string", example="Your Password Changed Successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = User::findOrFail(Auth::id());
        if(! Hash::check($request->current_password , $user->password)){
            return response()->json([
                'status' => 'Error',
                'message' => 'Current Password Incorrect'
            ],422);
        }
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
        return response()->json([
            'status' => 'Success',
            'message' => 'Your Password Changed Successfully'
        ],200);
    }


    /**
     * @OA\Post(
     *     path="/api/profile/update",
     *     tags={"My Profile"},
     *     summary="Update user profile",
     *     description="Update authenticated user profile information",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","email","phone","lang"},
     *                 @OA\Property(property="name", type="string", maxLength=50, example="Ahmed Morgan"),
     *                 @OA\Property(property="email", type="string", format="email", description="email|unique:users,email except email authenticated user", example="ahmed@example.com"),
     *                 @OA\Property(property="phone", type="string", description="phone:AUTO|unique:users,phone except phone authenticated user", example="+201012345678"),
     *                 @OA\Property(property="lang", type="string", ref="#/components/schemas/LanguagesEnum", example="en"),
     *                 @OA\Property(property="favicon", type="string", format="binary", description="file|mimes:pdf,jpeg,jpg,png|max:6120", example="user_favicon.jpg")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Success"),
     *             @OA\Property(property="message", type="string", example="Profile Updated Successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(UpdateProfileRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone
        ]);
        if($request->hasFile('favicon')){
            if (Storage::disk('local')->exists('favicons/'.$user->favicon)) {
                Storage::disk('local')->delete('favicons/'.$user->favicon);
            }
            $file = $request->file('favicon');
            $fileName = $user->id."_".Str::slug($user->name)."_favicon.".$file->getClientOriginalExtension();
            $file->storeAs("favicons",$fileName,"local");
            $user->update(['favicon' => $fileName]);
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Profile Updated Successfully'
        ],200);
    }


    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"My Profile"},
     *     summary="User Logout",
     *     description="Logout authenticated user",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Success"),
     *             @OA\Property(property="message",type="string", example="Logged out")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated."
     *     )
     * )
     */
    public function logout()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->tokens()->delete();
        return response()->json([
            'status' => 'Success',
            'message' => 'Logged out',
        ],200);
    }
}
