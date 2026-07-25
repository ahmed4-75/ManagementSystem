<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginNoPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\VerifyPhoneController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\StatusesController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register',RegisterController::class);
    Route::post('/login',LoginController::class);
    Route::post('/forgot-password',[LoginNoPasswordController::class,'forgotPassword'],);
    Route::post('/reset-password',[LoginNoPasswordController::class,'resetPassword'],);
});

Route::middleware('throttle:verify')->group(function () {
    Route::get('/verify-email/sendMail/{id}',[VerifyEmailController::class,'send'])->name('VE-sendMail');
    Route::get('/verify-phone/sendSMS/{id}',[VerifyPhoneController::class,'send'])->name('VP-sendSMS');
});

Route::post('/verify-email',[VerifyEmailController::class,'verify'])->name('verify-email');
Route::post('/verify-phone',[VerifyPhoneController::class,'verify'])->name('verify-phone');

Route::middleware(['auth:sanctum'])->group(function(){
    Route::middleware('throttle:profile')->group(function () {
        Route::get('/profile',[ProfileController::class,'index']);
        Route::put('/profile/update-password',[ProfileController::class,'updatePassword']);
        Route::post('/profile/update',[ProfileController::class,'update']);
        Route::post('/logout',[ProfileController::class,'logout']);
        Route::delete('/delete-new-Project-notification',[ProfileController::class,'DeleteNewProjectNotification']);
    });

    Route::middleware('throttle:projects')->group(function () {
        Route::get('/projects',[ProjectsController::class,'index']);
        Route::get('/projects/user',[ProjectsController::class,'ProjectsUser']);
        Route::get('/projects/show/{id}',[ProjectsController::class,'show']);
        Route::post('/projects/create',[ProjectsController::class,'store']);
        Route::put('/projects/update/{id}',[ProjectsController::class,'update']);
        Route::delete('/projects/delete/{id}',[ProjectsController::class,'delete']);

        Route::get('/statuses/{id}',[StatusesController::class,'index']);
        Route::post('/statuses/create/{id}',[StatusesController::class,'store']);
        Route::put('/statuses/update/{id}',[StatusesController::class,'update']);
        Route::delete('/statuses/delete/{id}',[StatusesController::class,'delete']);
    });

    Route::middleware('throttle:tasks')->group(function () {
        Route::get('/tasks/project/{id}',[TasksController::class,'index']);
        Route::get('/tasks/show/{id}',[TasksController::class,'show']);
        Route::post('/tasks/create/{ProjectId}/{UserId}',[TasksController::class,'store']);
        Route::put('/tasks/change-status/{TaskId}/{StatusId}',[TasksController::class,'ChangeStatus']);
        Route::delete('/tasks/delete/{id}',[TasksController::class,'delete']);
    });

    Route::middleware('throttle:users')->group(function () {
        Route::get('/users',[UsersController::class,'index']);
        Route::get('/users/role/{roleName}',[UsersController::class,'UsersRole']);
        Route::get('/users/project/{id}',[UsersController::class,'UsersProject']);
        Route::post('/users/{id}',[UsersController::class,'ChangeRole']);
        Route::put('/users/activate/{id}',[UsersController::class,'activate']);
        Route::delete('/users/ban/{id}',[UsersController::class,'ban']);
        Route::delete('/users/destroy/{id}',[UsersController::class,'delete']);
    });
});
