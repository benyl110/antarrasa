<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PublicImageController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::get('/uploads/{path}', PublicImageController::class)
    ->where('path', '.*')
    ->name('uploads.public');

Route::get('/masuk/{role}', [AuthController::class, 'showLogin'])->whereIn('role', ['mitra', 'konsumen'])->name('login');
Route::post('/masuk/{role}', [AuthController::class, 'login'])->middleware('throttle:8,1')->whereIn('role', ['mitra', 'konsumen'])->name('login.store');
Route::get('/daftar/{role}', [AuthController::class, 'showRegister'])->whereIn('role', ['mitra', 'konsumen'])->name('register');
Route::post('/daftar/{role}', [AuthController::class, 'register'])->middleware('throttle:5,1')->whereIn('role', ['mitra', 'konsumen'])->name('register.store');

Route::middleware('auth')->group(function (): void {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        return redirect()->route(auth()->user()->role === 'mitra' ? 'partner.dashboard' : 'consumer.home');
    })->name('dashboard');
    Route::get('/akun/kata-sandi', [AccountController::class, 'editPassword'])->name('account.password.edit');
    Route::put('/akun/kata-sandi', [AccountController::class, 'updatePassword'])->name('account.password.update');
});

Route::middleware(['auth', 'role:konsumen'])->group(function (): void {
    Route::get('/jelajah', [StorefrontController::class, 'index'])->name('consumer.home');
    Route::get('/toko/{store}', [StorefrontController::class, 'showStore'])->name('consumer.store');
    Route::post('/menu/{product}/keranjang', [StorefrontController::class, 'addToCart'])->name('cart.add');
    Route::get('/keranjang', [StorefrontController::class, 'showCart'])->name('cart.show');
    Route::put('/keranjang/{product}', [StorefrontController::class, 'updateCart'])->name('cart.update');
    Route::delete('/keranjang/{product}', [StorefrontController::class, 'removeFromCart'])->name('cart.remove');
    Route::get('/checkout', [StorefrontController::class, 'checkoutForm'])->name('checkout');
    Route::post('/checkout', [StorefrontController::class, 'checkout'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order}/setujui-total', [OrderController::class, 'confirmDistance'])->name('orders.confirm-distance');
    Route::post('/pesanan/{order}/batal', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/pesanan/{order}/bukti-transfer', [OrderController::class, 'uploadProof'])->name('orders.payment-proof');
});

Route::prefix('mitra')->name('partner.')->middleware(['auth', 'role:mitra'])->group(function (): void {
    Route::get('/dashboard', [PartnerController::class, 'dashboard'])->name('dashboard');
    Route::get('/toko', [PartnerController::class, 'editStore'])->name('store.edit');
    Route::put('/toko', [PartnerController::class, 'updateStore'])->name('store.update');
    Route::get('/menu', [PartnerController::class, 'products'])->name('products');
    Route::get('/menu/tambah', [PartnerController::class, 'createProduct'])->name('products.create');
    Route::post('/menu', [PartnerController::class, 'storeProduct'])->name('products.store');
    Route::get('/menu/{product}/ubah', [PartnerController::class, 'editProduct'])->name('products.edit');
    Route::put('/menu/{product}', [PartnerController::class, 'updateProduct'])->name('products.update');
    Route::delete('/menu/{product}', [PartnerController::class, 'archiveProduct'])->name('products.archive');
    Route::get('/pesanan', [PartnerController::class, 'orders'])->name('orders');
    Route::get('/pesanan/{order}', [PartnerController::class, 'showOrder'])->name('orders.show');
    Route::post('/pesanan/{order}/jarak', [PartnerController::class, 'verifyDistance'])->name('orders.distance');
    Route::post('/pesanan/{order}/keputusan', [PartnerController::class, 'decideOrder'])->name('orders.decision');
    Route::post('/pesanan/{order}/status', [PartnerController::class, 'advanceOrder'])->name('orders.status');
    Route::post('/pesanan/{order}/pembatalan', [PartnerController::class, 'decideCancellation'])->name('orders.cancellation');
    Route::put('/pesanan/{order}/driver', [PartnerController::class, 'updateDriver'])->name('orders.driver');
    Route::get('/pesanan/{order}/bukti-transfer', [OrderController::class, 'downloadProof'])->name('orders.payment-proof');
    Route::post('/pesanan/{order}/verifikasi-pembayaran', [PartnerController::class, 'verifyPayment'])->name('orders.payment');
    Route::post('/pesanan/{order}/pembayaran-cod', [PartnerController::class, 'confirmCodPayment'])->name('orders.cod-payment');
    Route::post('/pesanan/{order}/refund', [PartnerController::class, 'confirmRefund'])->name('orders.refund');
});
