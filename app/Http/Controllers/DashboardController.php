<?php

namespace App\Http\Controllers;

use App\Models\FlightBooking;
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
            'recent' => FlightBooking::with(['customer', 'invoice'])->latest()->limit(5)->get(),
        ]);
    }
}
