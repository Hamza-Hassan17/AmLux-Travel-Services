<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlightBookingController;
use App\Http\Controllers\HotelBookingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// ---- sign in / out ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:8,1')->name('login.attempt');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ---- back office (signed-in users only) ----
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'show']);

    // ---- transactions ----
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::resource('invoices', InvoiceController::class)->except(['create', 'store']);

    Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');
    Route::post('/receipts/{receipt}/allocate', [ReceiptController::class, 'allocate'])->name('receipts.allocate');
    Route::resource('receipts', ReceiptController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('/payments/{payment}/pdf', [PaymentController::class, 'pdf'])->name('payments.pdf');
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::resource('refunds', RefundController::class)->only(['index', 'create', 'store', 'destroy']);

    // ---- directory ----
    Route::resource('suppliers', SupplierController::class)->except(['destroy']);
    Route::resource('agents', AgentController::class)->except(['show', 'destroy']);
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');

    // ---- bookings (itinerary records; money lives on invoices) ----
    Route::get('/flights', [FlightBookingController::class, 'index'])->name('flights.index');
    Route::get('/flights/create', [FlightBookingController::class, 'create'])->name('flights.create');
    Route::get('/flights/export', [FlightBookingController::class, 'export'])->name('flights.export');
    Route::get('/flights/{booking}', [FlightBookingController::class, 'show'])->name('flights.show');
    Route::get('/flights/{booking}/edit', [FlightBookingController::class, 'edit'])->name('flights.edit');
    Route::put('/flights/{booking}', [FlightBookingController::class, 'update'])->name('flights.update');
    Route::get('/flights/{booking}/invoice', [FlightBookingController::class, 'invoice'])->name('flights.invoice');

    Route::get('/hotels', [HotelBookingController::class, 'index'])->name('hotels.index');
    Route::get('/hotels/create', [HotelBookingController::class, 'create'])->name('hotels.create');
    Route::get('/hotels/export', [HotelBookingController::class, 'export'])->name('hotels.export');
    Route::get('/hotels/{booking}', [HotelBookingController::class, 'show'])->name('hotels.show');
    Route::get('/hotels/{booking}/edit', [HotelBookingController::class, 'edit'])->name('hotels.edit');
    Route::put('/hotels/{booking}', [HotelBookingController::class, 'update'])->name('hotels.update');
    Route::get('/hotels/{booking}/invoice', [HotelBookingController::class, 'invoice'])->name('hotels.invoice');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/sales/export', [ReportController::class, 'salesExport'])->name('reports.sales.export');
    Route::get('/reports/profit-and-loss', [ReportController::class, 'pnl'])->name('reports.pnl');
    Route::get('/reports/profit-and-loss/export', [ReportController::class, 'pnlExport'])->name('reports.pnl.export');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/agency', [SettingsController::class, 'agency'])->name('settings.agency');
    Route::put('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
});
