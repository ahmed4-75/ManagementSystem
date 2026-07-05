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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register',RegisterController::class);
Route::get('/verify-email/sendMail/{id}',[VerifyEmailController::class,'send'])->name('VE-sendMail');
Route::post('/verify-email',[VerifyEmailController::class,'verify'])->name('verify-email');
Route::get('/verify-phone/sendSMS/{id}',[VerifyPhoneController::class,'send'])->name('VP-sendSMS');
Route::post('/verify-phone',[VerifyPhoneController::class,'verify'])->name('verify-phone');
Route::post('/login',LoginController::class);
Route::post('/forgot-password',[LoginNoPasswordController::class,'forgotPassword'],);
Route::post('/reset-password',[LoginNoPasswordController::class,'resetPassword'],);

Route::middleware(['auth:sanctum'])->group(function(){
    Route::get('/profile',[ProfileController::class,'index']);  //4|ejhZe7Z6MOZ7cixH2vImeipAeDxFDG089sDScpt4c6111c08
    Route::post('/profile/update',[ProfileController::class,'update']);
    Route::put('/profile/update-password',[ProfileController::class,'updatePassword']);
    Route::post('/logout',[ProfileController::class,'logout']);

    Route::get('/projects',[ProjectsController::class,'index']);
    Route::get('/projects/user',[ProjectsController::class,'ProjectsUser']);
    Route::get('/projects/{id}',[ProjectsController::class,'show']);
    Route::post('/projects',[ProjectsController::class,'store']);
    Route::put('/projects/{id}',[ProjectsController::class,'update']);
    Route::delete('/projects/{id}',[ProjectsController::class,'delete']);

    Route::get('/statuses/{id}',[StatusesController::class,'index']);
    Route::post('/statuses/{id}',[StatusesController::class,'store']);
    Route::put('/statuses/{id}',[StatusesController::class,'update']);
    Route::delete('/statuses/{id}',[StatusesController::class,'delete']);

    Route::get('/tasks/project/{id}',[TasksController::class,'index']);
    Route::get('/tasks/{id}',[TasksController::class,'show']);
    Route::post('/tasks/{ProjectId}/{UserId}',[TasksController::class,'store']);
    Route::put('/tasks/{TaskId}/{StatusId}',[TasksController::class,'ChangeStatus']);
    Route::delete('/tasks/{id}',[TasksController::class,'delete']);
});
