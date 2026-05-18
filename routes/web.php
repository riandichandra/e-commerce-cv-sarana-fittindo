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
use App\Http\Controllers\Admin\ProductCategoryController;

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
    Route::resource('products', Pelanggan\ProductController::class)->only(['index', 'show']);
    // Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', Admin\ProductController::class)->except(['show', 'destroy']);
    Route::resource('users', Admin\UserController::class)->except(['show', 'destroy']);
    Route::resource('categories', Admin\ProductCategoryController::class)->except(['show', 'destroy']);
    Route::resource('brands', Admin\ProductBrandController::class)->except(['show', 'destroy']);
    Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
    Route::resource('payments', Admin\PaymentController::class)->only(['index']);
    Route::patch('payments/{payment}/verify', [Admin\PaymentController::class, 'verify'])->name('payments.verify');
    Route::patch('payments/{payment}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');
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
