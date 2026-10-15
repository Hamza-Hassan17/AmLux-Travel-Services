<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FlightBooking;
use App\Support\FlightStats;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FlightBookingController extends Controller
{
    public function index(Request $request)
    {
        $counts = FlightStats::counts();

        return view('flights.index', [
            'bookings' => $this->filtered($request)->with(['customer', 'invoice'])->latest('departure_at')->paginate(15)->withQueryString(),
            'counts' => $counts,
            'airlines' => FlightBooking::orderBy('airline')->distinct()->pluck('airline'),
            'kpis' => FlightStats::kpis($counts),
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

    public function create(Request $request)
    {
        return redirect()->route('invoices.create', array_filter(['service' => 'flight', 'customer_id' => $request->query('customer_id')]));
    }

    public function show(FlightBooking $booking)
    {
        return view('flights.show', ['booking' => $booking->load('customer', 'invoice', 'line')]);
    }

    public function edit(FlightBooking $booking)
    {
        return view('flights.form', ['booking' => $booking->load('customer', 'invoice')]);
    }

    /** Itinerary details only; prices are edited on the invoice. */
    public function update(Request $request, FlightBooking $booking)
    {
        $data = $request->validate([
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
            'arrival_at' => 'nullable|date|after:departure_at',
            'cabin_class' => 'required|string|max:30',
            'adults' => 'required|integer|min:1|max:9',
            'seat' => 'nullable|string|max:10',
            'baggage' => 'nullable|string|max:255',
            'meal' => 'nullable|string|max:255',
            'passenger_name' => 'nullable|string|max:255',
            'passenger_mobile' => 'nullable|string|max:30',
            'status' => 'required|in:confirmed,pending,cancelled',
        ]);
        $data['pnr'] = strtoupper($data['pnr']);
        $data['origin'] = strtoupper($data['origin']);
        $data['destination'] = strtoupper($data['destination']);

        $booking->update($data);
        if ($booking->line) {
            $booking->line->update(['reference' => $data['pnr'], 'passenger' => $data['passenger_name'] ?: $booking->customer->name]);
        }

        return redirect()->route('flights.show', $booking)->with('success', 'Booking updated.');
    }

    /** E-ticket and a summary of the invoice it was billed on. */
    public function invoice(FlightBooking $booking)
    {
        $booking->load('customer', 'invoice');

        return PDF::loadView('pdf.flight', ['booking' => $booking])
            ->download(($booking->invoice ? str_replace('/', '-', $booking->invoice->invoice_no) : 'ticket-' . $booking->pnr) . '-eticket.pdf');
    }
}
