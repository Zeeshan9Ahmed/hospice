<?php

use App\Http\Controllers\API\AssignmentController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CoreController;
use App\Http\Controllers\API\HospiceController;
use App\Http\Controllers\API\ContentController;
use App\Http\Controllers\API\NurseController;
use App\Http\Controllers\API\PostController;
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
Route::post('uplaod', [AuthController::class, 'upload']);


/* Authentication Module */

//Registration
Route::post('signup', [AuthController::class, 'signup'])->name('signup');
Route::post('verification', [AuthController::class, 'verification'])->name('verification');
Route::post('resend-otp', [AuthController::class, 'resendOtp'])->name('resend-otp');

//Login
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('social-login', [AuthController::class, 'socialAuth'])->name('social-login');

//Reset Password
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
Route::post('forgot-password-otp-verify', [AuthController::class, 'forgotPasswordOtpVerify'])->name('forgot-password-otp-verify');
Route::post('forgot-password-resend-otp', [AuthController::class, 'forgotPasswordResendOtp'])->name('forgot-password-resend-otp');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

//Content
Route::get('content', [ContentController::class, 'content'])->name('content');

///----Cron Job
//DeleteRequest
Route::get("delete-request", [CoreController::class, 'deleteRequest']);
Route::get('delete-specific-availability', [NurseController::class, 'deleteSpecificAvailability']);


//Auth
Route::middleware('auth:sanctum')->group(function () {

    //Profile
    Route::get('user-profile', [AuthController::class, 'userProfile'])->name('user-profile');
    Route::post('complete-profile', [AuthController::class, 'completeProfile'])->name('complete-profile');
    Route::post('change-password', [AuthController::class, 'changePassword'])->name('change-password');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('delete-account', [AuthController::class, 'DeleteAccount'])->name('delete-account');

    //Hospice


    Route::post('create-route-sheet', [AssignmentController::class, 'createRouteSheet']);
    Route::post('create-assignment', [AssignmentController::class, 'createAssingment']);

    Route::get('available-cases', [AssignmentController::class, 'availableCases']);
    Route::get('inprogress-cases', [AssignmentController::class, 'inProgressCases']);
    Route::get('completed-cases', [AssignmentController::class, 'completedCases']);
    Route::get('case_detail', [AssignmentController::class, 'caseDetail']);

    Route::post('shift-request', [AssignmentController::class, 'sendShiftRequest']);

    Route::post('shift-request-status', [AssignmentController::class, 'acceptOrRejectShiftRequest']);

    Route::get('posts', [PostController::class, 'posts']);

    Route::get('post/nurses', [PostController::class, 'postNurses']);

    Route::post('edit-post', [PostController::class, 'editPost']);
    Route::post('delete-post', [PostController::class, 'deletePost']);

    Route::get('post-shifts', [PostController::class, 'postShifts']);

    Route::post('cancel-case', [PostController::class, 'cancelCase']);

    Route::post('reopen-case', [PostController::class, 'reOpenCase']);




    Route::get('case-list', [HospiceController::class, 'caseList'])->name('case-list');
    Route::post('create-case', [HospiceController::class, 'createCase'])->name('create-case');
    Route::post('assign-nurse', [HospiceController::class, 'assignNurse'])->name('assign-nurse');
    Route::get('view-posts', [HospiceController::class, 'viewPosts'])->name('view-posts');
    // Route::post('edit-post',[HospiceController::class,'editPost'])->name('edit-post');
    // Route::post('delete-post',[HospiceController::class,'deletePost'])->name('delete-post');
    Route::post('assign-shift-to-nurse', [HospiceController::class, 'assignShiftToNurse'])->name('assign-shift-to-nurse');
    Route::post('approve-reject-case-request', [HospiceController::class, 'approveRejectCaseRequest'])->name('approve-reject-case-request');
    Route::post('re-assign-shift', [HospiceController::class, 'reAssignShift'])->name('re-assign-shift');

    //Nurse
    Route::get('available-nurses', [NurseController::class, 'availableNurses']);
    Route::post('create-availability', [NurseController::class, 'createAvailability'])->name('create-availability');
    Route::post('create-specific-availability', [NurseController::class, 'createSpecificAvailability'])->name('create-specific-availability');

    Route::get('get-specific-availability', [NurseController::class, 'getSpecificAvailability'])->name('get-specific-availability');

    Route::post('accept-case', [NurseController::class, 'acceptCase'])->name('accept-case');
    Route::post('accept-case-dup', [NurseController::class, 'acceptCaseDuplicate'])->name('accept-case-dup');
    Route::post('complete-case', [NurseController::class, 'completeCase'])->name('complete-case');
    // Route::post('cancel-case', [NurseController::class, 'cancelCase'])->name('cancel-case');

    Route::get('route-sheet-list', [NurseController::class, 'routeSheetList'])->name('route-sheet-list');
    // Route::post('create-route-sheet', [NurseController::class, 'createRouteSheet'])->name('create-route-sheet');

    Route::get('user-availabilities', [NurseController::class, 'userAvailabilities'])->name('user-availabilities');


    Route::get('request-list', [NurseController::class, 'requestList'])->name('request-list');
    Route::post('accept-request', [NurseController::class, 'acceptRequest'])->name('accept-request');

    Route::get("view-details", [HospiceController::class, 'viewDetails']);

    //PastServices
    Route::get('past-services', [CoreController::class, 'pastServices']);
    Route::get('nurse/past-services', [CoreController::class, 'nursePastServices']);

    //feedback
    Route::post('complete-feedback', [CoreController::class, 'completeFeedback'])->name('complete-feedback');

    //Chat
    Route::get('chat-messages', [CoreController::class, 'chatMessages'])->name('chat-messages');

    //Notification
    Route::get('notifications', [CoreController::class, 'notifications'])->name('notifications');
    Route::get('delete-notification/{id}', [CoreController::class, 'deleteNotification'])->name('delete-notification');

    //Subscription
    Route::get('subscriptions', [CoreController::class, 'subscriptions'])->name('subscriptions');

    //HelpAndSupport
    Route::post("help-and-support", [CoreController::class, 'helpAndSupport']);

    //AddStripCard
    Route::post("add-stripe-card", [CoreController::class, 'addStripeCard']);

    //addition
    Route::get("get-user-card-list", [CoreController::class, 'getCardList']);
    Route::post("set-default-card", [CoreController::class, 'setDefaultCard']);
    Route::get("get-default-card", [CoreController::class, 'getDefaultCard']);
    Route::get("delete-card/{card_id}", [CoreController::class, 'deleteCard']);

    //ActiveCard
    Route::post('active-card', [AuthController::class, 'activeCard'])->name('active-card');
});
