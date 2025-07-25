<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CenterController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExaminerController;
use App\Http\Controllers\ExamSubjectController;
use App\Http\Controllers\FeeCollectionController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\LastExamResultController;
use App\Http\Controllers\LetterDistributionCenterController;
use App\Http\Controllers\MeritPriceController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ParaGroupController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\QuranQuestionController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ResultSummaryController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZamatController;
use App\Models\LetterDistributionCenter;
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
Route::get('institutes-by-area', [InstituteController::class, 'getInstitutesByArea']);
Route::get('/institutes/counts', [InstituteController::class, 'instituteCounts']);
Route::get('/institutes-application-status-counts', [InstituteController::class, 'institutesApplicationStatusCounts']);
Route::get('/institutes-with-applications', [InstituteController::class, 'institutesWithApplications']);
Route::get('/institutes-without-applications', [InstituteController::class, 'institutesWithoutApplications']);
Route::get('/institutes/{id}', [InstituteController::class, 'show']);
Route::get('/institute-by-code/{institute_code}', [InstituteController::class, 'instituteByCode']);
Route::get('/institutes-with-phone', [InstituteController::class, 'institutesWithValidPhone']);
Route::get('/centers-with-phone', [InstituteController::class, 'institutesWithValidPhoneAndCenter']);

Route::get('/zamats', [ZamatController::class, 'index']);
Route::get('/zamats/{id}', [ZamatController::class, 'show']);

Route::get('/exams', [ExamController::class, 'index']);
Route::get('/exams/last', [ExamController::class, 'showLast']);
Route::get('/exams/{id}', [ExamController::class, 'show']);

Route::get('/areas', [AreaController::class, 'index']);
Route::get('/areas/{id}', [AreaController::class, 'show']);

Route::get('/fees', [FeeController::class, 'index']);
Route::get('/fees/latest', [FeeController::class, 'latestFee']);
Route::get('/fees/{id}', [FeeController::class, 'show']);

Route::get('/groups', [GroupController::class, 'index']);
Route::get('/groups/{id}', [GroupController::class, 'show']);

Route::get('/centers', [CenterController::class, 'index']);
Route::get('/centers/{id}', [CenterController::class, 'show']);

Route::get('/students', [StudentController::class, 'index']);
Route::get('/students-print', [StudentController::class, 'PrintStudents']);
Route::get('/students-count-center', [StudentController::class, 'centerWiseStudentCount']);
Route::get('/print-envelop', [StudentController::class, 'PrintEnvelop']);
Route::get('/students/{id}', [StudentController::class, 'show']);
Route::get('/without-roll-number', [StudentController::class, 'studentsWithoutRollNumber']);
Route::get('/with-roll-number', [StudentController::class, 'studentsWithRollNumber']);

Route::get('/students-roll-count', [StudentController::class, 'rollNumberCounts']);
Route::get('/students-with-without-roll-count', [StudentController::class, 'withoutAndWithRollNumberCount']);

Route::get('/center-wise-institute-count', [StudentController::class, 'centerWiseInstituteCount']);

Route::get('/area-wise-student-count', [StudentController::class, 'areaWiseStudentCountByZamat']);

Route::get('/notices', [NoticeController::class, 'index']);
Route::get('/notices/{id}', [NoticeController::class, 'show']);
Route::get('notices/{id}/download', [NoticeController::class, 'downloadFile']);

Route::get('/examiners', [ExaminerController::class, 'index']);
Route::post('/examiners-public', [ExaminerController::class, 'publicStore']);
Route::post('/examiners', [ExaminerController::class, 'store']);
Route::get('/examiners/{id}', [ExaminerController::class, 'show']);
Route::post('/examiner/search', [ExaminerController::class, 'search']);

Route::post('/applications', [ApplicationController::class, 'store']);
Route::get('/applications/public-show', [ApplicationController::class, 'publicShow']);
Route::post('/applications/{application}/bkash-create-payment', [ApplicationController::class, 'bkashCreatePayment']);
Route::post('/applications/{application}/bkash-execute-payment', [ApplicationController::class, 'bkashExecutePayment']);

Route::post('/collect-fees', [FeeCollectionController::class, 'store']);
Route::post('/collect-fees/bkash/execute/{id}', [FeeCollectionController::class, 'bkashExecutePayment']);

Route::get('/sms-records', [SmsController::class, 'seeRecords']);

Route::get('letter-distribution-centers', [LetterDistributionCenterController::class, 'index']);
Route::get('/search-letter-center', [LetterDistributionCenterController::class, 'searchByInstituteCode']);
Route::get('letter-distribution-centers/{id}', [LetterDistributionCenterController::class, 'show']);

Route::get('/site-settings/scrolling-notice', [SiteSettingsController::class, 'showScrollingNotice']);
Route::get('/site-settings/director-message', [SiteSettingsController::class, 'showDirectorMessage']);
Route::get('/site-settings/secretary-message', [SiteSettingsController::class, 'showSecretaryMessage']);
Route::get('/site-settings/about-us', [SiteSettingsController::class, 'showAboutUs']);

Route::get('/subjects', [SubjectController::class, 'index']);
Route::get('/subjects/{id}', [SubjectController::class, 'show']);

Route::get('/exam-subjects', [ExamSubjectController::class, 'index']);
Route::get('/exam-subjects/{id}', [ExamSubjectController::class, 'show']);

Route::get('/quran-questions', [QuranQuestionController::class, 'index']);
Route::get('/quran-questions/{id}', [QuranQuestionController::class, 'show']);

Route::get('/last-exam-result-data', [LastExamResultController::class, 'data']);
Route::post('/last-exam-submit-mark/{zamat}/{examSubject}', [LastExamResultController::class, 'submitMarks']);
Route::get('/last-exam-results', [LastExamResultController::class, 'index']);

Route::get('/para-groups', [ParaGroupController::class, 'index']);
Route::get('/para-groups/{id}', [ParaGroupController::class, 'show']);

Route::post('/generate-mark-sheet', [PrintController::class, 'generateMarkSheet']);
Route::get('/print-envelop-final', [PrintController::class, 'PrintEnvelopFinal']);
Route::get('/print-envelop-general', [PrintController::class, 'PrintGeneralEnvelope']);
Route::get('/print-center-envelop', [PrintController::class, 'centerEnvelop']);
Route::get('/print-seat-number', [PrintController::class, 'seatNumber']);
Route::get('/center-acknowledgment', [PrintController::class, 'centerAcknowledgment']);
Route::get('/area-student-count/{exam_id}/{area_id}', [PrintController::class, 'areaStudentCount']);

Route::get('/results/student/{roll_number}', [ResultController::class, 'getStudentResult']);
Route::get('/institutes/{institute_id}/zamat/{zamat_id}/exam/{exam_id}/results', [ResultController::class, 'getInstituteResults']);
Route::get('/print/exam/{exam_id}/zamat/{zamat_id}/merit-list/{group_id?}', [ResultController::class, 'printMeritList']);

Route::get('/summary/{exam_id}', [ResultSummaryController::class, 'summaryPrint']);

Route::middleware(['auth:sanctum', 'role:Operator,Admin,Super Admin,Developer'])->group(function () {

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/print', [ApplicationController::class, 'printApplications']);
    Route::get('/application-counts', [ApplicationController::class, 'getApplicationCounts']);
    Route::get('/applications/zamat-wise-counts', [ApplicationController::class, 'getZamatWiseCounts']);
    Route::get('/applications/user-wise-counts', [ApplicationController::class, 'getUserWiseCounts']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::get('/sms-logs', [SmsController::class, 'index']);
    Route::get('/sms-logs/count-stock', [SmsController::class, 'countStock']);

    Route::get('/collect-fees', [FeeCollectionController::class, 'index']);
    Route::get('/collect-fees/{id}', [FeeCollectionController::class, 'show']);

    Route::get('/admit-card', [StudentController::class, 'studentsAdmitCard']);

    Route::post('/quran-questions', [QuranQuestionController::class, 'store']);
    Route::put('/quran-questions/{id}', [QuranQuestionController::class, 'update']);

    Route::middleware('role:Admin,Super Admin,Developer')->group(function () {
        Route::post('/institutes', [InstituteController::class, 'store']);
        Route::put('/institutes/{id}', [InstituteController::class, 'update']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::post('/centers', [CenterController::class, 'store']);

        Route::post('/notices', [NoticeController::class, 'store']);
        Route::put('/notices/{id}', [NoticeController::class, 'update']);

        Route::put('/applications/{id}/update-payment-status', [ApplicationController::class, 'updatePaymentStatus']);
        Route::put('/applications/{id}/update-registration', [ApplicationController::class, 'updateRegistrationPart']);
        Route::put('/applications/{id}/update-students', [ApplicationController::class, 'updateStudentsPart']);

        Route::post('letter-distribution-centers', [LetterDistributionCenterController::class, 'store']);
        Route::put('letter-distribution-centers/{id}', [LetterDistributionCenterController::class, 'update']);

        Route::put('/examiners/{id}', [ExaminerController::class, 'update']);

        Route::put('/students/update/{id}', [StudentController::class, 'update']);

        Route::post('/students/multiple-update', [StudentController::class, 'multipleUpdate']);

        Route::post('/subjects', [SubjectController::class, 'store']);
        Route::put('/subjects/{id}', [SubjectController::class, 'update']);

        Route::post('/exam-subjects', [ExamSubjectController::class, 'store']);
        Route::put('/exam-subjects/{id}', [ExamSubjectController::class, 'update']);

        Route::post('/para-groups', [ParaGroupController::class, 'store']);
        Route::put('/para-groups/{id}', [ParaGroupController::class, 'update']);

        Route::get('/exam/{exam_id}/zamat/{zamat_id}/merit-list/{group_id?}', [ResultController::class, 'getMeritList']);
        Route::post('/students/update-merit', [ResultController::class, 'updateMerit']);

        Route::get('/merit-prices', [MeritPriceController::class, 'index']);
        Route::post('/merit-prices', [MeritPriceController::class, 'store']);
        Route::put('/merit-prices/{meritPrice}', [MeritPriceController::class, 'update']);
        Route::get('/student-merit-price', [MeritPriceController::class, 'StudentMeritPrice']);
    });

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

        Route::delete('/institutes/{id}', [InstituteController::class, 'destroy']);

        Route::post('/zamats', [ZamatController::class, 'store']);
        Route::post('/exams', [ExamController::class, 'store']);
        Route::post('/areas', [AreaController::class, 'store']);
        Route::post('/fees', [FeeController::class, 'store']);
        Route::post('/groups', [GroupController::class, 'store']);
        Route::post('/students', [StudentController::class, 'store']);

        Route::post('/send-sms', [SmsController::class, 'sendSms']);

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

        Route::delete('/students/{id}', [StudentController::class, 'destroy']);

        Route::delete('/notices/{id}', [NoticeController::class, 'destroy']);

        Route::delete('/examiners/{id}', [ExaminerController::class, 'destroy']);

        Route::delete('letter-distribution-centers/{id}', [LetterDistributionCenterController::class, 'destroy']);

        Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

        Route::delete('/exam-subjects/{id}', [ExamSubjectController::class, 'destroy']);

        Route::delete('/quran-questions/{id}', [QuranQuestionController::class, 'destroy']);

        Route::delete('/para-groups/{id}', [ParaGroupController::class, 'destroy']);
    });

    Route::middleware('role:Developer')->group(function () {});
});