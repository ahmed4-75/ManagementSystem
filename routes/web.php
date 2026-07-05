<?php

use App\Events\EndTask;
use App\Http\Requests\Auth\LoginRequest;
// use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    $userId = 2;
    $targetUsers = User::findOrFail($userId);
    $message = 'A Task has ended, ' . $targetUsers->name.' '. $targetUsers->id;
    broadcast(new EndTask($message, $targetUsers->id));
    Log::info('✅ Broadcasted to user: ' . $targetUsers->id);

    // $projectId = 1;
    // $project = Project::with('users')->findOrFail($projectId);
    // $targetUsers = $project->users;
    // foreach ($targetUsers as $user) {
    //     $message = 'A Task has ended, ' . $user->name.' '. $user->id;
    //     try {
    //         broadcast(new EndTask($message, $user->id));
    //         Log::info('✅ Broadcasted to user: ' . $user->id);
    //     } catch (\Exception $e) {
    //         Log::error('❌ Broadcasting failed: ' . $e->getMessage());
    //     }
    // }
    return view('welcome');
});


Route::view('/test','login');
Route::post('/login',function(LoginRequest $request){
    $user = User::where('email',$request->identification)->orWhere('phone',$request->identification)->first();
    if($user and Hash::check($request->password,$user->password)){
        if(!$user->email_verified_at){
            return "You are not verified the email";
        }
        Auth::login($user,$request->filled('remember'));
        return 'You are in';
    }
    return 'Invalid Credentials';
})->name('login');
