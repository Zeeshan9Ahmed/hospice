<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Login

Route::get('/login', [AuthController::class,'login'])->name('admin.login');
Route::post('/login',[AuthController::class,'login_process'])->name('login-process');

Route::group(['middleware'=>'admins'],function(){
//dashboard
    Route::get('dashboard',[DashboardController::class,'dashboard'])->name('dashboard');

//Logout
    Route::get('logout',[DashboardController::class,'logout'])->name('admin.logout');

//Users
    Route::get('users/hospices',[UserController::class,'hospices'])->name('admin.users.hospices');
    Route::get('users/nurses',[UserController::class,'nurses'])->name('admin.users.nurses');
    Route::get('users/block',[UserController::class,'blockUser'])->name('admin.users.block');
    Route::get('users/approve',[UserController::class,'approveUser'])->name('admin.users.approve');

//Content
    Route::get('/contents',[ContentController::class,'index'])->name('admin.contents');
    Route::get('/edit-content/{id}',[ContentController::class,'edit_content'])->name('admin.edit-content');
    Route::post('/update-content',[ContentController::class,'update_content'])->name('admin.update-content');

    Route::get('contents/delete/{id}',[ContentController::class,'destroy'])->name('admin.contents.delete');

//Feedback
    Route::get('/feedbacks',[FeedbackController::class,'index'])->name('admin.feedbacks');

});
