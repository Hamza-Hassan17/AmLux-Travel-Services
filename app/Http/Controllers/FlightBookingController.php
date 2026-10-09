<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FlightBooking;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FlightBookingController extends Controller
{
    public function index(Request $request)
    {
        $counts = FlightBooking::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $now = now();

        $thisMonth = FlightBooking::where('created_at', '>=', $now->copy()->startOfMonth());
        $lastMonthCount = FlightBooking::whereBetween('created_at', [
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->startOfMonth(),
        ])->count();
        $monthCount = (clone $thisMonth)->count();
        $revenue = (clone $thisMonth)->where('currency', 'PKR')->where('status', '!=', 'cancelled')->sum('total');

        return view('flights.index', [
            'bookings' => $this->filtered($request)->with(['customer', 'invoice'])->latest('departure_at')->paginate(15)->withQueryString(),
            'counts' => $counts,
            'airlines' => FlightBooking::orderBy('airline')->distinct()->pluck('airline'),
            'kpis' => [
                'month' => $monthCount,
                'trend' => $lastMonthCount ? round(($monthCount - $lastMonthCount) / $lastMonthCount * 100) : null,
                'lastMonth' => $now->copy()->subMonthNoOverflow()->format('M'),
                'revenue' => $this->compact($revenue),
                'pending' => $counts->get('pending', 0),
                'pendingSoon' => FlightBooking::where('status', 'pending')->whereBetween('departure_at', [$now, $now->copy()->addDays(3)])->count(),
                'upcoming' => FlightBooking::where('status', '!=', 'cancelled')->whereBetween('departure_at', [$now, $now->copy()->addDays(7)])->count(),
            ],
            'filters' => [
                'q' => trim((string) $request->query('q')),
                'status' => $request->query('status'),
                'airline' => $request->query('airline'),
                'payment' => $request->query('payment'),
                'from' => $request->query('from'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->with(['customer', 'invoice'])->latest('departure_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['PNR', 'E-Ticket', 'Customer', 'Phone', 'Airline', 'Flight', 'From', 'To', 'Departure', 'Arrival', 'Class', 'Passengers', 'Currency', 'Total', 'Payment', 'Invoice', 'Status']);
            foreach ($rows as $b) {
                fputcsv($out, [
                    $b->pnr, $b->ticket_no, $b->customer->name, $b->customer->phone, $b->airline, $b->flight_no,
                    $b->origin, $b->destination, $b->departure_at->format('Y-m-d H:i'), $b->arrival_at->format('Y-m-d H:i'),
                    $b->cabin_class, $b->adults, $b->currency, $b->total,
                    optional($b->invoice)->payment_status, optional($b->invoice)->invoice_no, $b->status,
                ]);
            }
            fclose($out);
        }, 'flight-bookings-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function filtered(Request $request)
    {
        $query = FlightBooking::query();

        if ($q = trim((string) $request->query('q'))) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('pnr', 'like', $like)
                    ->orWhere('ticket_no', 'like', $like)
                    ->orWhere('flight_no', 'like', $like)
                    ->orWhere('airline', 'like', $like)
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('passport_no', 'like', $like);
                    });
            });
        }
        if (in_array($request->query('status'), ['confirmed', 'pending', 'cancelled'], true)) {
            $query->where('status', $request->query('status'));
        }
        if ($airline = $request->query('airline')) {
            $query->where('airline', $airline);
        }
        if (in_array($request->query('payment'), ['paid', 'unpaid'], true)) {
            $query->whereHas('invoice', function ($i) use ($request) {
                $i->where('payment_status', $request->query('payment'));
            });
        }
        if ($from = $request->query('from')) {
            try {
                $query->where('departure_at', '>=', Carbon::parse($from)->startOfDay());
            } catch (\Throwable $e) {
                // ignore an unparsable date filter
            }
        }

        return $query;
    }

    private function compact($n)
    {
        if ($n >= 1000000) {
            return rtrim(rtrim(number_format($n / 1000000, 2), '0'), '.') . 'M';
        }
        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
        }

        return number_format($n);
    }

    public function create(Request $request)
    {
        return view('flights.form', [
            'booking' => new FlightBooking(['customer_id' => $request->customer_id, 'currency' => 'PKR', 'cabin_class' => 'Economy', 'adults' => 1, 'status' => 'confirmed']),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $booking = DB::transaction(function () use ($data, $request) {
            $booking = FlightBooking::create($data);

            $booking->invoice()->create([
                'invoice_no' => 'INV-FL-' . date('Y') . '-' . str_pad($booking->id, 3, '0', STR_PAD_LEFT),
                'customer_id' => $booking->customer_id,
                'total' => $booking->total,
                'currency' => $booking->currency,
                'payment_status' => $request->input('payment_status', 'paid'),
                'payment_method' => $request->input('payment_method', 'Online / Paid (100%)'),
                'issued_on' => $request->input('issued_on', now()->toDateString()),
            ]);

            return $booking;
        });

        return redirect()->route('flights.show', $booking)->with('success', 'Flight Booked Successfully');
    }

    public function show(FlightBooking $booking)
    {
        return view('flights.show', ['booking' => $booking->load('customer', 'invoice')]);
    }

    public function edit(FlightBooking $booking)
    {
        return view('flights.form', [
            'booking' => $booking->load('invoice'),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, FlightBooking $booking)
    {
        $data = $this->validated($request, $booking);

        DB::transaction(function () use ($booking, $data, $request) {
            $booking->update($data);
            $booking->invoice()->update([
                'customer_id' => $booking->customer_id,
                'total' => $booking->total,
                'currency' => $booking->currency,
                'payment_status' => $request->input('payment_status', 'paid'),
                'payment_method' => $request->input('payment_method'),
                'issued_on' => $request->input('issued_on', now()->toDateString()),
            ]);
        });

        return redirect()->route('flights.show', $booking)->with('success', 'Booking updated.');
    }

    public function invoice(FlightBooking $booking)
    {
        $booking->load('customer', 'invoice');

        return PDF::loadView('pdf.flight', ['booking' => $booking])
            ->download($booking->invoice->invoice_no . '.pdf');
    }

    private function validated(Request $request, FlightBooking $booking = null): array
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'pnr' => ['required', 'string', 'max:20', Rule::unique('flight_bookings', 'pnr')->ignore($booking)],
            'ticket_no' => 'nullable|string|max:30',
            'airline' => 'required|string|max:255',
            'flight_no' => 'required|string|max:20',
            'aircraft' => 'nullable|string|max:255',
            'origin' => 'required|alpha|size:3',
            'destination' => 'required|alpha|size:3|different:origin',
            'origin_airport' => 'nullable|string|max:255',
            'destination_airport' => 'nullable|string|max:255',
            'departure_at' => 'required|date',
            'arrival_at' => ['required', 'date', function ($attribute, $value, $fail) use ($request) {
                try {
                    if (Carbon::parse($value)->lessThanOrEqualTo(Carbon::parse($request->input('departure_at')))) {
                        $fail('The arrival must be after the departure.');
                    }
                } catch (\Throwable $e) {
                    $fail('The arrival date/time is not valid.');
                }
            }],
            'cabin_class' => 'required|string|max:30',
            'adults' => 'required|integer|min:1|max:9',
            'seat' => 'nullable|string|max:10',
            'baggage' => 'nullable|string|max:255',
            'meal' => 'nullable|string|max:255',
            'base_fare' => 'required|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'fuel_surcharge' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'currency' => 'required|alpha|size:3',
            'status' => 'required|in:confirmed,pending,cancelled',
            'payment_status' => 'nullable|in:paid,unpaid',
            'payment_method' => 'nullable|string|max:255',
            'issued_on' => 'nullable|date',
        ]);

        $data['origin'] = strtoupper($data['origin']);
        $data['destination'] = strtoupper($data['destination']);
        $data['currency'] = strtoupper($data['currency']);
        foreach (['taxes', 'fuel_surcharge', 'discount'] as $f) {
            $data[$f] = $data[$f] ?? 0;
        }
        $data['total'] = max(0, $data['base_fare'] + $data['taxes'] + $data['fuel_surcharge'] - $data['discount']);

        return array_diff_key($data, array_flip(['payment_status', 'payment_method', 'issued_on']));
    }
}
