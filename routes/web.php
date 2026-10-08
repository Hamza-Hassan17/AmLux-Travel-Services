<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FlightBookingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/flights');

Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'show']);

Route::get('/flights', [FlightBookingController::class, 'index'])->name('flights.index');
Route::get('/flights/create', [FlightBookingController::class, 'create'])->name('flights.create');
Route::post('/flights', [FlightBookingController::class, 'store'])->name('flights.store');
Route::get('/flights/{booking}', [FlightBookingController::class, 'show'])->name('flights.show');
Route::get('/flights/{booking}/edit', [FlightBookingController::class, 'edit'])->name('flights.edit');
Route::put('/flights/{booking}', [FlightBookingController::class, 'update'])->name('flights.update');
Route::get('/flights/{booking}/invoice', [FlightBookingController::class, 'invoice'])->name('flights.invoice');
