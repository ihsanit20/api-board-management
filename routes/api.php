<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CenterController;
use App\Http\Controllers\DepartmentController; 
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExaminerController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
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


Route::post('/login', [AuthController::class, 'login']);

Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/departments/{id}', [DepartmentController::class, 'show']);

Route::get('/institutes', [InstituteController::class, 'index']);
Route::get('/institutes/counts', [InstituteController::class, 'instituteCounts']);
Route::get('/institutes-application-status-counts', [InstituteController::class, 'institutesApplicationStatusCounts']);
Route::get('/institutes-with-applications', [InstituteController::class, 'institutesWithApplications']);
Route::get('/institutes-without-applications', [InstituteController::class, 'institutesWithoutApplications']);
Route::get('/institutes/{id}', [InstituteController::class, 'show']);
Route::get('/institute-by-code/{institute_code}', [InstituteController::class, 'instituteByCode']);

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

Route::get('/notices', [NoticeController::class, 'index']); 
Route::get('/notices/{id}', [NoticeController::class, 'show']);

Route::get('/examiners', [ExaminerController::class, 'index']);
Route::get('/examiners/{id}', [ExaminerController::class, 'show']);

Route::post('/applications', [ApplicationController::class, 'store']);
Route::get('/applications/public-show', [ApplicationController::class, 'publicShow']);
Route::post('/applications/{application}/bkash-create-payment', [ApplicationController::class, 'bkashCreatePayment']);
Route::post('/applications/{application}/bkash-execute-payment', [ApplicationController::class, 'bkashExecutePayment']);

// Site Settings API routes
Route::prefix('site-settings')->group(function () {
    Route::get('/scrolling-notice', [SiteSettingsController::class, 'showScrollingNotice']);
    Route::get('/director-message', [SiteSettingsController::class, 'showDirectorMessage']);
    Route::get('/secretary-message', [SiteSettingsController::class, 'showSecretaryMessage']);
    Route::get('/about-us', [SiteSettingsController::class, 'showAboutUs']);
});

// Protected routes (store, update, and destroy)
Route::middleware(['auth:sanctum', 'role:Operator,Admin,Super Admin,Developer'])->group(function () {

    // Operator, Admin, Super Admin, Developer - সবার জন্য (show, index, count)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::get('/applications', [ApplicationController::class, 'index']); 
    Route::get('/applications/print', [ApplicationController::class, 'printApplications']);
    Route::get('/application-counts', [ApplicationController::class, 'getApplicationCounts']);
    Route::get('/applications/zamat-wise-counts', [ApplicationController::class, 'getZamatWiseCounts']);   
    Route::get('/applications/user-wise-counts', [ApplicationController::class, 'getUserWiseCounts']);   
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::get('/sms-logs', [SmsController::class, 'index']);
    Route::get('/sms-logs/count', [SmsController::class, 'count']);

    // Admin এর জন্য শুধুমাত্র Store করার অনুমতি
    Route::middleware('role:Admin,Super Admin,Developer')->group(function () {  
        Route::post('/institutes', [InstituteController::class, 'store']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::post('/zamats', [ZamatController::class, 'store']);
        Route::post('/exams', [ExamController::class, 'store']);
        Route::post('/areas', [AreaController::class, 'store']);
        Route::post('/fees', [FeeController::class, 'store']);
        Route::post('/groups', [GroupController::class, 'store']);
        Route::post('/centers', [CenterController::class, 'store']);
        Route::post('/students', [StudentController::class, 'store']);
        Route::post('/notices', [NoticeController::class, 'store']);
        Route::post('/examiners', [ExaminerController::class, 'store']);
        Route::post('/send-sms', [SmsController::class, 'sendSms']);
        
        Route::put('/applications/{id}/update-payment-status', [ApplicationController::class, 'updatePaymentStatus']);
        Route::put('/applications/{id}/update-registration', [ApplicationController::class, 'updateRegistrationPart']);
        Route::put('/applications/{id}/update-students', [ApplicationController::class, 'updateStudentsPart']);
    });

    // Super Admin এর জন্য Update এবং Delete করার অনুমতি
    Route::middleware('role:Super Admin,Developer')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);

        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        
        Route::put('/site-settings/scrolling-notice', [SiteSettingsController::class, 'updateScrollingNotice']);
        Route::put('/site-settings/director-message', [SiteSettingsController::class, 'updateDirectorMessage']);
        Route::put('/site-settings/secretary-message', [SiteSettingsController::class, 'updateSecretaryMessage']);
        Route::put('/site-settings/about-us', [SiteSettingsController::class, 'updateAboutUs']);
        
        Route::put('/institutes/{id}', [InstituteController::class, 'update']);
        Route::delete('/institutes/{id}', [InstituteController::class, 'destroy']);
        
        Route::put('/departments/{id}', [DepartmentController::class, 'update']);
        Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);

        Route::put('/zamats/{id}', [ZamatController::class, 'update']);
        Route::delete('/zamats/{id}', [ZamatController::class, 'destroy']);

        Route::put('/exams/{id}', [ExamController::class, 'update']);
        Route::delete('/exams/{id}', [ExamController::class, 'destroy']);

        Route::put('/areas/{id}', [AreaController::class, 'update']);
        Route::delete('/areas/{id}', [AreaController::class, 'destroy']);

        Route::put('/fees/{id}', [FeeController::class, 'update']);
        Route::delete('/fees/{id}', [FeeController::class, 'destroy']);

        Route::put('/groups/{id}', [GroupController::class, 'update']);
        Route::delete('/groups/{id}', [GroupController::class, 'destroy']);

        Route::put('/centers/{id}', [CenterController::class, 'update']);
        Route::delete('/centers/{id}', [CenterController::class, 'destroy']);

        Route::put('/students/{id}', [StudentController::class, 'update']); 
        Route::delete('/students/{id}', [StudentController::class, 'destroy']);

        Route::put('/notices/{id}', [NoticeController::class, 'update']); 
        Route::delete('/notices/{id}', [NoticeController::class, 'destroy']);

        Route::put('/examiners/{id}', [ExaminerController::class, 'update']);
        Route::delete('/examiners/{id}', [ExaminerController::class, 'destroy']);
    });

    // Developer এর জন্য বিশেষ রাউট
    Route::middleware('role:Developer')->group(function () {
        Route::get('/developer-special', function () {
            return 'Developer-specific action';
        });
    });
});

