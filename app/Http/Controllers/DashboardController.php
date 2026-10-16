<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FlightBooking;
use App\Models\HotelBooking;
use App\Models\Invoice;
use App\Models\Supplier;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();

        $month = Invoice::whereBetween('issued_on', [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()])->get();
        $unpaid = Invoice::with('customer')->whereIn('payment_status', ['unpaid', 'partial'])->orderBy('due_date')->orderBy('id')->get();

        $payable = 0;
        foreach (Supplier::all() as $supplier) {
            $payable += max(0, $supplier->payable);
        }

        return view('dashboard', [
            'kpis' => [
                'sales' => $month->sum('total'),
                'invoices' => $month->count(),
                'receivable' => $unpaid->sum(function ($i) {
                    return $i->balance;
                }),
                'overdue' => $unpaid->filter(function ($i) use ($now) {
                    return $i->due_date && $i->due_date->lt($now->copy()->startOfDay());
                })->count(),
                'payable' => $payable,
            ],
            'accounts' => Account::active()->get(),
            'unpaid' => $unpaid->take(8),
            'upcoming' => FlightBooking::with(['customer', 'invoice'])->where('status', '!=', 'cancelled')
                ->whereBetween('departure_at', [$now, $now->copy()->addDays(14)])->orderBy('departure_at')->limit(8)->get(),
            'stays' => HotelBooking::with(['customer', 'invoice'])->where('status', '!=', 'cancelled')
                ->whereBetween('check_in', [$now->toDateString(), $now->copy()->addDays(14)->toDateString()])->orderBy('check_in')->limit(6)->get(),
        ]);
    }
}
