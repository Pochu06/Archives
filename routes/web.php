<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DownloadRequestController;
use App\Http\Controllers\SubmissionController;

// Welcome / Landing
Route::get('/', function () { return view('welcome'); });

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Research Archive
Route::get('/research/public', [ResearchController::class, 'publicIndex'])->name('research.public');
Route::get('/research/public/{id}', [ResearchController::class, 'publicShow'])->name('research.public-show');
Route::get('/research/topic-suggestions', [ResearchController::class, 'topicSuggestions'])->name('research.topic-suggestions');
Route::get('/research', [ResearchController::class, 'index'])->name('research.index');
Route::get('/research/create', [ResearchController::class, 'create'])->name('research.create');
Route::get('/research/tutorial', [ResearchController::class, 'tutorial'])->name('research.tutorial');
Route::post('/research', [ResearchController::class, 'store'])->name('research.store');
Route::post('/research/save-draft', [ResearchController::class, 'saveDraft'])->name('research.save-draft');
Route::get('/research/load-draft', [ResearchController::class, 'loadDraft'])->name('research.load-draft');
Route::delete('/research/delete-draft', [ResearchController::class, 'deleteDraft'])->name('research.delete-draft');
Route::get('/research/{id}', [ResearchController::class, 'show'])->name('research.show');
Route::get('/research/{id}/edit', [ResearchController::class, 'edit'])->name('research.edit');
Route::put('/research/{id}', [ResearchController::class, 'update'])->name('research.update');
Route::delete('/research/{id}', [ResearchController::class, 'destroy'])->name('research.destroy');
Route::get('/research/{id}/download', [ResearchController::class, 'download'])->name('research.download');
Route::post('/research/upload-image', [ResearchController::class, 'uploadImage'])->name('research.upload-image');
Route::post('/research/delete-image', [ResearchController::class, 'deleteImage'])->name('research.delete-image');

// Submission Workflow
Route::get('/my-submissions', [SubmissionController::class, 'index'])->name('submissions.index');
Route::get('/college-submissions', [SubmissionController::class, 'collegeIndex'])->name('submissions.college');
Route::get('/rde-submissions', [SubmissionController::class, 'rdeIndex'])->name('submissions.rde');
Route::post('/submissions/{id}/college-approve', [SubmissionController::class, 'approveByCollege'])->name('submissions.college-approve');
Route::post('/submissions/{id}/college-revision', [SubmissionController::class, 'requestRevisionByCollege'])->name('submissions.college-revision');
Route::post('/submissions/{id}/college-reject', [SubmissionController::class, 'rejectByCollege'])->name('submissions.college-reject');
Route::post('/submissions/{id}/rde-approve', [SubmissionController::class, 'approveByRde'])->name('submissions.rde-approve');
Route::post('/submissions/{id}/rde-revision', [SubmissionController::class, 'requestRevisionByRde'])->name('submissions.rde-revision');
Route::post('/submissions/{id}/rde-reject', [SubmissionController::class, 'rejectByRde'])->name('submissions.rde-reject');

// Download Requests
Route::post('/research/{id}/request-download', [DownloadRequestController::class, 'store'])->name('download-request.store');
Route::get('/my-requests', [DownloadRequestController::class, 'myRequests'])->name('download-request.my');
Route::get('/download-requests', [DownloadRequestController::class, 'index'])->name('download-request.index');
Route::post('/download-requests/{id}/approve', [DownloadRequestController::class, 'approve'])->name('download-request.approve');
Route::post('/download-requests/{id}/reject', [DownloadRequestController::class, 'reject'])->name('download-request.reject');

// User Management
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

// College Management
Route::get('/colleges', [CollegeController::class, 'index'])->name('colleges.index');
Route::get('/colleges/create', [CollegeController::class, 'create'])->name('colleges.create');
Route::post('/colleges', [CollegeController::class, 'store'])->name('colleges.store');
Route::get('/colleges/{id}/edit', [CollegeController::class, 'edit'])->name('colleges.edit');
Route::put('/colleges/{id}', [CollegeController::class, 'update'])->name('colleges.update');
Route::delete('/colleges/{id}', [CollegeController::class, 'destroy'])->name('colleges.destroy');

// Category Management
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
