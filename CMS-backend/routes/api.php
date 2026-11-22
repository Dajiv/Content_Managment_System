<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('users', UserController::class)->middleware('role:admin');

    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show']);
    Route::post('/assignments', [AssignmentController::class, 'store'])->middleware('role:admin,teacher');
    Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->middleware('role:admin,teacher');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->middleware('role:admin,teacher');

    Route::get('/submissions', [SubmissionController::class, 'index']);
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download']);
    Route::post('/submissions', [SubmissionController::class, 'store'])->middleware('role:student');
    Route::put('/submissions/{submission}', [SubmissionController::class, 'update'])->middleware('role:teacher,admin');

    Route::get('/contents', [ContentController::class, 'index']);
    Route::get('/contents/{content}', [ContentController::class, 'show']);
    Route::post('/contents', [ContentController::class, 'store'])->middleware('role:admin,teacher');
    Route::put('/contents/{content}', [ContentController::class, 'update'])->middleware('role:admin,teacher');
    Route::delete('/contents/{content}', [ContentController::class, 'destroy'])->middleware('role:admin,teacher');
});


