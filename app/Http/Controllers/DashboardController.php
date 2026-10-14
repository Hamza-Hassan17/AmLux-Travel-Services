<?php

namespace App\Http\Controllers;

use App\Models\FlightBooking;
use App\Models\HotelBooking;
use App\Support\FlightStats;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();

        return view('dashboard', [
            'kpis' => FlightStats::kpis(),
            'upcoming' => FlightBooking::with(['customer', 'invoice'])->where('status', '!=', 'cancelled')
                ->whereBetween('departure_at', [$now, $now->copy()->addDays(14)])->orderBy('departure_at')->limit(8)->get(),
            'stays' => HotelBooking::with(['customer', 'invoice'])->where('status', '!=', 'cancelled')
                ->whereBetween('check_in', [$now->toDateString(), $now->copy()->addDays(14)->toDateString()])->orderBy('check_in')->limit(6)->get(),
            'recent' => FlightBooking::with(['customer', 'invoice'])->latest()->limit(5)->get(),
        ]);
    }
}
