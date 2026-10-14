<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\FlightBooking;
use App\Models\HotelBooking;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.app', function ($view) {
            $view->with([
                'navFlights' => FlightBooking::count(),
                'navHotels' => HotelBooking::count(),
                'navCustomers' => Customer::count(),
            ]);
        });

        Schema::defaultStringLength(191);
    }
}
