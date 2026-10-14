<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlightBookingController;
use App\Http\Controllers\HotelBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'show']);

Route::get('/flights', [FlightBookingController::class, 'index'])->name('flights.index');
Route::get('/flights/create', [FlightBookingController::class, 'create'])->name('flights.create');
Route::post('/flights', [FlightBookingController::class, 'store'])->name('flights.store');
Route::get('/flights/export', [FlightBookingController::class, 'export'])->name('flights.export');
Route::get('/flights/{booking}', [FlightBookingController::class, 'show'])->name('flights.show');
Route::get('/flights/{booking}/edit', [FlightBookingController::class, 'edit'])->name('flights.edit');
Route::put('/flights/{booking}', [FlightBookingController::class, 'update'])->name('flights.update');
Route::get('/flights/{booking}/invoice', [FlightBookingController::class, 'invoice'])->name('flights.invoice');

Route::get('/hotels', [HotelBookingController::class, 'index'])->name('hotels.index');
Route::get('/hotels/create', [HotelBookingController::class, 'create'])->name('hotels.create');
Route::post('/hotels', [HotelBookingController::class, 'store'])->name('hotels.store');
Route::get('/hotels/export', [HotelBookingController::class, 'export'])->name('hotels.export');
Route::get('/hotels/{booking}', [HotelBookingController::class, 'show'])->name('hotels.show');
Route::get('/hotels/{booking}/edit', [HotelBookingController::class, 'edit'])->name('hotels.edit');
Route::put('/hotels/{booking}', [HotelBookingController::class, 'update'])->name('hotels.update');
Route::get('/hotels/{booking}/invoice', [HotelBookingController::class, 'invoice'])->name('hotels.invoice');
