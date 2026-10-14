<?php

namespace App\Http\Controllers;

use App\Models\FlightBooking;
use App\Models\HotelBooking;
use App\Models\Invoice;
use App\Support\FlightStats;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);
        $currency = 'PKR';

        $invoices = $this->invoices($from, $to, $currency);
        $live = $invoices->reject(function ($i) {
            return $this->cancelled($i);
        });
        $cancelled = $invoices->count() - $live->count();

        $paid = $live->where('payment_status', 'paid')->sum('total');
        $unpaid = $live->where('payment_status', '!=', 'paid')->sum('total');
        $revenue = $live->sum('total');

        $services = [];
        foreach (['flight' => FlightBooking::class, 'hotel' => HotelBooking::class] as $key => $class) {
            $set = $live->filter(function ($i) use ($class) {
                return $i->invoiceable_type === $class;
            });
            $services[$key] = [
                'count' => $set->count(),
                'revenue' => $set->sum('total'),
                'paid' => $set->where('payment_status', 'paid')->sum('total'),
                'unpaid' => $set->where('payment_status', '!=', 'paid')->sum('total'),
            ];
        }

        // six-month trend ending with the selected end date
        $trendStart = $to->copy()->startOfMonth()->subMonths(5);
        $trendInvoices = $this->invoices($trendStart, $to->copy()->endOfMonth(), $currency)->reject(function ($i) {
            return $this->cancelled($i);
        });
        $months = [];
        for ($m = $trendStart->copy(); $m->lessThanOrEqualTo($to->copy()->startOfMonth()); $m->addMonth()) {
            $key = $m->format('Y-m');
            $set = $trendInvoices->filter(function ($i) use ($key) {
                return $i->issued_on->format('Y-m') === $key;
            });
            $months[] = [
                'label' => $m->format('M Y'),
                'flight' => $set->where('invoiceable_type', FlightBooking::class)->sum('total'),
                'hotel' => $set->where('invoiceable_type', HotelBooking::class)->sum('total'),
                'total' => $set->sum('total'),
            ];
        }
        $maxMonth = max(1, collect($months)->max('total'));

        $topCustomers = $live->groupBy('customer_id')->map(function ($set) {
            return ['name' => $set->first()->customer->name, 'id' => $set->first()->customer_id, 'count' => $set->count(), 'total' => $set->sum('total')];
        })->sortByDesc('total')->take(5)->values();

        $topAirlines = $live->filter(function ($i) {
            return $i->invoiceable instanceof FlightBooking;
        })->groupBy(function ($i) {
            return $i->invoiceable->airline;
        })->map(function ($set, $name) {
            return ['name' => $name, 'count' => $set->count(), 'total' => $set->sum('total')];
        })->sortByDesc('total')->take(5)->values();

        $topHotels = $live->filter(function ($i) {
            return $i->invoiceable instanceof HotelBooking;
        })->groupBy(function ($i) {
            return $i->invoiceable->hotel_name;
        })->map(function ($set, $name) {
            return ['name' => $name, 'city' => $set->first()->invoiceable->city, 'count' => $set->count(), 'total' => $set->sum('total')];
        })->sortByDesc('total')->take(5)->values();

        $outstanding = $live->where('payment_status', '!=', 'paid')->sortBy('issued_on')->take(10)->values();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'currency' => $currency,
            'summary' => [
                'revenue' => $revenue,
                'paid' => $paid,
                'unpaid' => $unpaid,
                'count' => $live->count(),
                'cancelled' => $cancelled,
                'average' => $live->count() ? $revenue / $live->count() : 0,
                'otherCurrency' => Invoice::whereBetween('issued_on', [$from->toDateString(), $to->toDateString()])->where('currency', '!=', $currency)->count(),
            ],
            'services' => $services,
            'months' => $months,
            'maxMonth' => $maxMonth,
            'topCustomers' => $topCustomers,
            'topAirlines' => $topAirlines,
            'topHotels' => $topHotels,
            'outstanding' => $outstanding,
            'presets' => $this->presets(),
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = $this->invoices($from, $to, null);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Date', 'Service', 'Reference', 'Customer', 'Currency', 'Total', 'Payment', 'Method', 'Booking status']);
            foreach ($rows as $i) {
                $b = $i->invoiceable;
                fputcsv($out, [
                    $i->invoice_no, $i->issued_on->format('Y-m-d'),
                    $b instanceof HotelBooking ? 'Hotel' : 'Flight',
                    $b instanceof HotelBooking ? $b->booking_ref : optional($b)->pnr,
                    $i->customer->name, $i->currency, $i->total, $i->payment_status, $i->payment_method, optional($b)->status,
                ]);
            }
            fclose($out);
        }, 'amlux-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function invoices(Carbon $from, Carbon $to, $currency)
    {
        return Invoice::with(['invoiceable', 'customer'])
            ->whereBetween('issued_on', [$from->toDateString(), $to->toDateString()])
            ->when($currency, function ($q) use ($currency) {
                $q->where('currency', $currency);
            })
            ->orderBy('issued_on')
            ->get()
            ->filter(function ($i) {
                return $i->invoiceable !== null;
            })
            ->values();
    }

    private function cancelled(Invoice $i)
    {
        return optional($i->invoiceable)->status === 'cancelled';
    }

    private function range(Request $request)
    {
        try {
            $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfMonth();
            $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
        } catch (\Throwable $e) {
            $from = now()->startOfMonth();
            $to = now()->endOfDay();
        }
        if ($to->lessThan($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function presets()
    {
        $now = now();

        return [
            'This month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'Last month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'Last 30 days' => [$now->copy()->subDays(29), $now->copy()],
            'This year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        ];
    }
}
