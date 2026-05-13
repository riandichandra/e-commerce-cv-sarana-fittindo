<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\GM;
use App\Http\Controllers\Marketing;
use App\Http\Controllers\Direktur;
use App\Http\Controllers\Pelanggan;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth Routes
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::get('/', [Pelanggan\DashboardController::class, 'index'])->name('dashboard');

// Pelanggan routes
Route::middleware(['auth', 'role:pelanggan'])->prefix('pelanggan')->name('pelanggan.')->group(function () {
    Route::get('/', [Pelanggan\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', Admin\ProductController::class);
    Route::resource('users', Admin\UserController::class);
    // Route::resource('orders', Admin\OrderController::class);
});

// Marketing routes
Route::middleware(['auth', 'role:marketing'])->prefix('marketing')->name('marketing.')->group(function () {
    Route::get('/dashboard', [Marketing\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('promotions', Marketing\PromotionController::class);
});

// GM routes
Route::middleware(['auth', 'role:gm'])->prefix('gm')->name('gm.')->group(function () {
    Route::get('/dashboard', [GM\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [GM\ReportController::class, 'index'])->name('reports.index');
});

// Direktur routes
Route::middleware(['auth', 'role:direktur'])->prefix('direktur')->name('direktur.')->group(function () {
    Route::get('/dashboard', [Direktur\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/strategic-reports', [Direktur\ReportController::class, 'strategic'])->name('reports.strategic');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
