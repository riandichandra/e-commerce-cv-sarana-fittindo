<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Direktur;
use App\Http\Controllers\GM;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Marketing;
use App\Http\Controllers\Pelanggan;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth Routes
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
Route::get('/', [Pelanggan\DashboardController::class, 'index'])->name('dashboard');

// Public customer product routes
Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
    Route::get('/', [Pelanggan\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', Pelanggan\ProductController::class)->only(['index', 'show']);
});

// Pelanggan routes
Route::middleware(['auth', 'role:pelanggan'])->prefix('pelanggan')->name('pelanggan.')->group(function () {
    Route::get('/', [Pelanggan\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/cart', [Pelanggan\CartController::class, 'index'])->name('cart.index');
    Route::get('/cart/checkout', [Pelanggan\CartController::class, 'checkoutForm'])->name('cart.checkout');
    Route::post('/cart/checkout', [Pelanggan\CartController::class, 'checkout'])->name('cart.checkout.process');
    Route::post('/cart/{product}', [Pelanggan\CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{cartItem}', [Pelanggan\CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{cartItem}', [Pelanggan\CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/orders', [Pelanggan\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/history', [Pelanggan\OrderController::class, 'history'])->name('orders.history');
    Route::get('/orders/{order}/payment-proof', [Pelanggan\OrderController::class, 'paymentProofForm'])->name('orders.payment-proof');
    Route::post('/orders/{order}/payment-proof', [Pelanggan\OrderController::class, 'uploadPaymentProof'])->name('orders.payment-proof.store');
    Route::patch('/orders/{order}/complete', [Pelanggan\OrderController::class, 'complete'])->name('orders.complete');
    Route::patch('/orders/{order}/cancel', [Pelanggan\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{order}', [Pelanggan\OrderController::class, 'show'])->name('orders.show');
    Route::post('/wishlist/{product}', [Pelanggan\WishlistController::class, 'toggle'])->name('wishlist.toggle');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [Admin\SettingController::class, 'index'])->name('settings.index');
    Route::resource('products', Admin\ProductController::class)->except(['show', 'destroy']);
    Route::resource('users', Admin\UserController::class)->except(['show', 'destroy']);
    Route::resource('categories', ProductCategoryController::class)->except(['show', 'destroy']);
    Route::resource('brands', Admin\ProductBrandController::class)->except(['show', 'destroy']);
    Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
    Route::resource('payments', Admin\PaymentController::class)->only(['index']);
    Route::patch('payments/{payment}/verify', [Admin\PaymentController::class, 'verify'])->name('payments.verify');
    Route::patch('payments/{payment}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');
    Route::resource('payment-methods', Admin\PaymentMethodController::class)->except(['show', 'destroy']);
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
    Route::post('/profile/addresses', [ProfileController::class, 'storeAddress'])->name('profile.addresses.store');
    Route::patch('/profile/addresses/{address}', [ProfileController::class, 'updateAddress'])->name('profile.addresses.update');
    Route::delete('/profile/addresses/{address}', [ProfileController::class, 'destroyAddress'])->name('profile.addresses.destroy');
    Route::get('/regions/provinces/{province}/regencies', [ProfileController::class, 'regenciesByProvince'])->name('regions.regencies.index');
    Route::get('/regions/regencies/{regency}/districts', [ProfileController::class, 'districtsByRegency'])->name('regions.districts.index');
    Route::get('/regions/districts/{district}/villages', [ProfileController::class, 'villagesByDistrict'])->name('regions.villages.index');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
