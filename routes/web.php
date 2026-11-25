<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VendorRegisterController;
use App\Http\Controllers\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman awal: arahkan sesuai status login
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route(Auth::user()->homeRoute())
        : redirect()->route('login');
});

// Login & Register (hanya untuk tamu / belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.post');

    Route::get('/register', [VendorRegisterController::class, 'showRegistrationForm'])->name('vendor.register');
    Route::post('/register', [VendorRegisterController::class, 'register'])->name('vendor.register.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Area Admin / Toko (role: admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');

    // Master data
    Route::resource('users', Admin\UserController::class)->except('show');
    Route::resource('products', Admin\ProductController::class)->except('show');
    Route::resource('locations', Admin\LocationController::class)->except('show');

    // 1. Identifikasi kebutuhan stok
    Route::get('restock', [Admin\RestockController::class, 'index'])->name('restock.index');

    // 2-5. Pesanan ke vendor, penerimaan, konfirmasi
    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [Admin\OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [Admin\OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/cancel', [Admin\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/inspect', [Admin\OrderController::class, 'inspect'])->name('orders.inspect');
    Route::post('orders/{order}/confirm', [Admin\OrderController::class, 'confirm'])->name('orders.confirm');

    // 5. Retur
    Route::get('returns', [Admin\ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{returnRequest}', [Admin\ReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{returnRequest}/complete', [Admin\ReturnController::class, 'complete'])->name('returns.complete');

    // 6-7. Stok, riwayat, penataan
    Route::get('stock', [Admin\StockController::class, 'index'])->name('stock.index');
    Route::get('stock/movements', [Admin\StockController::class, 'movements'])->name('stock.movements');
    Route::get('stock/manage', [Admin\StockController::class, 'manage'])->name('stock.manage');
    Route::post('stock/transfer', [Admin\StockController::class, 'transfer'])->name('stock.transfer');
    Route::post('stock/adjust', [Admin\StockController::class, 'adjust'])->name('stock.adjust');
});

/*
|--------------------------------------------------------------------------
| Area Vendor (role: vendor)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', Vendor\DashboardController::class)->name('dashboard');

    Route::get('orders', [Vendor\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Vendor\OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/respond', [Vendor\OrderController::class, 'respond'])->name('orders.respond');
    Route::post('orders/{order}/prepare', [Vendor\OrderController::class, 'prepare'])->name('orders.prepare');
    Route::post('orders/{order}/ship', [Vendor\OrderController::class, 'ship'])->name('orders.ship');
    Route::post('orders/{order}/track', [Vendor\OrderController::class, 'track'])->name('orders.track');

    Route::get('returns', [Vendor\ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{returnRequest}', [Vendor\ReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{returnRequest}/respond', [Vendor\ReturnController::class, 'respond'])->name('returns.respond');
});
