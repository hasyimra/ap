<?php

use App\Http\Controllers\ApInvoiceController;
use App\Http\Controllers\ApPaymentController;
use App\Http\Controllers\Auth\DevLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SsoCallbackController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'redirect'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/sso/callback', SsoCallbackController::class)->name('sso.callback');

Route::get('/dev-login', [DevLoginController::class, 'index'])->name('dev-login.index');
Route::post('/dev-login/{user}', [DevLoginController::class, 'login'])->name('dev-login.login');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- Users (sso_admin only, enforced in controller via middleware below) ---
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('role:sso_admin,admin');

    // --- Reports ---
    Route::get('reports/open-payables', [ReportController::class, 'openPayables'])->name('reports.open-payables');
    Route::get('reports/aged-payables', [ReportController::class, 'agedPayables'])->name('reports.aged-payables');
    Route::get('reports/aged-payables-summary', [ReportController::class, 'agedPayablesSummary'])->name('reports.aged-payables-summary');
    Route::get('reports/ap-history', [ReportController::class, 'apHistory'])->name('reports.ap-history');

    // --- AP Invoices: static routes before {invoice} ---
    Route::get('ap-invoices', [ApInvoiceController::class, 'index'])->name('ap-invoices.index');
    Route::get('ap-invoices/create', [ApInvoiceController::class, 'create'])->name('ap-invoices.create')->middleware('role:admin,user');
    Route::post('ap-invoices', [ApInvoiceController::class, 'store'])->name('ap-invoices.store')->middleware('role:admin,user');
    Route::get('ap-invoices/{invoice}', [ApInvoiceController::class, 'show'])->name('ap-invoices.show');
    Route::get('ap-invoices/{invoice}/edit', [ApInvoiceController::class, 'edit'])->name('ap-invoices.edit')->middleware('role:admin,user');
    Route::put('ap-invoices/{invoice}', [ApInvoiceController::class, 'update'])->name('ap-invoices.update')->middleware('role:admin,user');
    Route::delete('ap-invoices/{invoice}', [ApInvoiceController::class, 'destroy'])->name('ap-invoices.destroy')->middleware('role:admin,user');
    Route::post('ap-invoices/{invoice}/submit', [ApInvoiceController::class, 'submit'])->name('ap-invoices.submit')->middleware('role:admin,user');
    Route::post('ap-invoices/{invoice}/approve', [ApInvoiceController::class, 'approve'])->name('ap-invoices.approve')->middleware('role:admin,approval');
    Route::post('ap-invoices/{invoice}/reject', [ApInvoiceController::class, 'reject'])->name('ap-invoices.reject')->middleware('role:admin,approval');

    // --- AP Payments ---
    Route::get('ap-payments', [ApPaymentController::class, 'index'])->name('ap-payments.index');
    Route::get('ap-payments/create', [ApPaymentController::class, 'create'])->name('ap-payments.create')->middleware('role:admin,user');
    Route::post('ap-payments', [ApPaymentController::class, 'store'])->name('ap-payments.store')->middleware('role:admin,user');
    Route::get('ap-payments/{payment}', [ApPaymentController::class, 'show'])->name('ap-payments.show');
    Route::delete('ap-payments/{payment}', [ApPaymentController::class, 'destroy'])->name('ap-payments.destroy')->middleware('role:admin,user');
    Route::post('ap-payments/{payment}/submit', [ApPaymentController::class, 'submit'])->name('ap-payments.submit')->middleware('role:admin,user');
    Route::post('ap-payments/{payment}/approve', [ApPaymentController::class, 'approve'])->name('ap-payments.approve')->middleware('role:admin,approval');
    Route::post('ap-payments/{payment}/reject', [ApPaymentController::class, 'reject'])->name('ap-payments.reject')->middleware('role:admin,approval');
    Route::post('ap-payments/{payment}/mark-reconciled', [ApPaymentController::class, 'markReconciled'])->name('ap-payments.mark-reconciled')->middleware('role:admin,user');
});
