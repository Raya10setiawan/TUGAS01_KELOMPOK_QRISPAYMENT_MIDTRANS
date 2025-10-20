<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Ini adalah routing utama untuk sistem pembayaran Midtrans.
| Cocok dengan PaymentController yang punya fungsi 'index' dan 'process'.
|
*/

// Halaman utama (form pembayaran)
Route::get('/', [PaymentController::class, 'index'])->name('payment.index');

// Proses pembayaran (dipanggil lewat fetch() di JavaScript)
Route::post('/payment/process', [PaymentController::class, 'process'])->name('payment.process');

// (Opsional) Halaman sukses setelah pembayaran berhasil
Route::get('/payment/success', function () {
    return view('success');
})->name('payment.success');

// (Opsional) Halaman gagal atau error
Route::get('/payment/failed', function () {
    return view('failed');
})->name('payment.failed');
