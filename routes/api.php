<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CenterController;
use App\Http\Controllers\DepartmentController; 
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ZamatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/departments/{id}', [DepartmentController::class, 'show']);

Route::get('/institutes', [InstituteController::class, 'index']);
Route::get('/institutes/{id}', [InstituteController::class, 'show']);

Route::get('/zamats', [ZamatController::class, 'index']);
Route::get('/zamats/{id}', [ZamatController::class, 'show']);

Route::get('/exams', [ExamController::class, 'index']);
Route::get('/exams/last', [ExamController::class, 'showLast']);
Route::get('/exams/{id}', [ExamController::class, 'show']);

Route::get('/areas', [AreaController::class, 'index']);
Route::get('/areas/{id}', [AreaController::class, 'show']);

Route::get('/fees', [FeeController::class, 'index']);
Route::get('/fees/{id}', [FeeController::class, 'show']);
Route::get('/fee-by-exam-and-zamat', [FeeController::class, 'feeByExamAndZamat']);

Route::get('/groups', [GroupController::class, 'index']); 
Route::get('/groups/{id}', [GroupController::class, 'show']);

Route::get('/centers', [CenterController::class, 'index']); 
Route::get('/centers/{id}', [CenterController::class, 'show']);

Route::get('/students', [StudentController::class, 'index']); 
Route::get('/students/{id}', [StudentController::class, 'show']);

// Protected routes (store, update, and destroy)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/institutes', [InstituteController::class, 'store']);
    Route::put('/institutes/{id}', [InstituteController::class, 'update']);
    Route::delete('/institutes/{id}', [InstituteController::class, 'destroy']);

    Route::post('/departments', [DepartmentController::class, 'store']); 
    Route::put('/departments/{id}', [DepartmentController::class, 'update']); 
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);

    Route::post('/zamats', [ZamatController::class, 'store']);
    Route::put('/zamats/{id}', [ZamatController::class, 'update']);
    Route::delete('/zamats/{id}', [ZamatController::class, 'destroy']);

    Route::post('/exams', [ExamController::class, 'store']);
    Route::put('/exams/{id}', [ExamController::class, 'update']);
    Route::delete('/exams/{id}', [ExamController::class, 'destroy']);

    Route::post('/areas', [AreaController::class, 'store']);
    Route::put('/areas/{id}', [AreaController::class, 'update']);
    Route::delete('/areas/{id}', [AreaController::class, 'destroy']);

    Route::post('/fees', [FeeController::class, 'store']);
    Route::put('/fees/{id}', [FeeController::class, 'update']);
    Route::delete('/fees/{id}', [FeeController::class, 'destroy']);

    Route::post('/groups', [GroupController::class, 'store']);
    Route::put('/groups/{id}', [GroupController::class, 'update']); 
    Route::delete('/groups/{id}', [GroupController::class, 'destroy']);

    Route::post('/centers', [CenterController::class, 'store']);
    Route::put('/centers/{id}', [CenterController::class, 'update']); 
    Route::delete('/centers/{id}', [CenterController::class, 'destroy']);

    Route::get('/applications', [ApplicationController::class, 'index']);    
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::put('/applications/{id}', [ApplicationController::class, 'update']);
    Route::delete('/applications/{id}', [ApplicationController::class, 'destroy']);
    Route::put('/applications/{id}/update-payment-status', [ApplicationController::class, 'updatePaymentStatus']);
    Route::put('/applications/{id}/update-status', [ApplicationController::class, 'updateApplicationStatus']);

    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{id}', [StudentController::class, 'update']); 
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);
});
