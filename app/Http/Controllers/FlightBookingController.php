<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FlightBooking;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FlightBookingController extends Controller
{
    public function index()
    {
        return view('flights.index', [
            'bookings' => FlightBooking::with('customer')->latest()->paginate(15),
        ]);
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
            'arrival_at' => 'required|date|after:departure_at',
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
