<?php

use App\Http\Controllers\AreaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\ZamatController;
use App\Models\Exam;
use App\Models\Zamat;

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

Route::get('/institutes', [InstituteController::class, 'index']);
Route::get('/institutes/{id}', [InstituteController::class, 'show']);

Route::get('/zamats', [ZamatController::class, 'index']);
Route::get('/zamats/{id}', [ZamatController::class, 'show']);

Route::get('/exams', [ExamController::class, 'index']);
Route::get('/exams/{id}', [ExamController::class, 'show']);

Route::get('/areas', [AreaController::class, 'index']);
Route::get('/areas/{id}', [AreaController::class, 'show']);


// Protected routes (store, update, and destroy)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/institutes', [InstituteController::class, 'store']);
    Route::put('/institutes/{id}', [InstituteController::class, 'update']);
    Route::delete('/institutes/{id}', [InstituteController::class, 'destroy']);

    Route::post('/zamats', [ZamatController::class, 'store']);
    Route::put('/zamats/{id}', [ZamatController::class, 'update']);
    Route::delete('/zamats/{id}', [ZamatController::class, 'destroy']);

    Route::post('/exams', [ExamController::class, 'store']);
    Route::put('/exams/{id}', [ExamController::class, 'update']);
    Route::delete('/exams/{id}', [ExamController::class, 'destroy']);

    Route::post('/areas', [AreaController::class, 'store']);
    Route::put('/areas/{id}', [AreaController::class, 'update']);
    Route::delete('/areas/{id}', [AreaController::class, 'destroy']);
});