<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\AdviserController;

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

// Research Management
Route::get('/research', [ResearchController::class, 'index'])->name('research.index');
Route::get('/research/create', [ResearchController::class, 'create'])->name('research.create');
Route::post('/research', [ResearchController::class, 'store'])->name('research.store');
Route::get('/research/{id}', [ResearchController::class, 'show'])->name('research.show');
Route::get('/research/{id}/edit', [ResearchController::class, 'edit'])->name('research.edit');
Route::put('/research/{id}', [ResearchController::class, 'update'])->name('research.update');
Route::delete('/research/{id}', [ResearchController::class, 'destroy'])->name('research.destroy');
Route::post('/research/{id}/approve', [ResearchController::class, 'approve'])->name('research.approve');
Route::post('/research/{id}/reject', [ResearchController::class, 'reject'])->name('research.reject');
Route::get('/research/{id}/download', [ResearchController::class, 'download'])->name('research.download');

// My Submissions (Student)
Route::get('/my-submissions', [SubmissionController::class, 'index'])->name('submissions.index');

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

// Adviser Panel
Route::get('/adviser/submissions', [AdviserController::class, 'submissions'])->name('adviser.submissions');
Route::get('/adviser/students', [AdviserController::class, 'students'])->name('adviser.students');
