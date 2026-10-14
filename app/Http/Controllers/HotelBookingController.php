<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\HotelBooking;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HotelBookingController extends Controller
{
    public function index(Request $request)
    {
        $counts = HotelBooking::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $now = now();
        $thisMonth = HotelBooking::where('created_at', '>=', $now->copy()->startOfMonth());
        $revenue = (clone $thisMonth)->where('currency', 'PKR')->where('status', '!=', 'cancelled')->sum('total');

        return view('hotels.index', [
            'bookings' => $this->filtered($request)->with(['customer', 'invoice'])->latest('check_in')->paginate(15)->withQueryString(),
            'counts' => $counts,
            'kpis' => [
                'month' => (clone $thisMonth)->count(),
                'revenue' => \App\Support\FlightStats::compact($revenue),
                'pending' => $counts->get('pending', 0),
                'upcoming' => HotelBooking::where('status', '!=', 'cancelled')
                    ->whereBetween('check_in', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])->count(),
            ],
            'cities' => HotelBooking::orderBy('city')->distinct()->pluck('city'),
            'filters' => [
                'q' => trim((string) $request->query('q')),
                'status' => $request->query('status'),
                'city' => $request->query('city'),
                'payment' => $request->query('payment'),
                'from' => $request->query('from'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->with(['customer', 'invoice'])->latest('check_in')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Confirmation', 'Guest', 'Phone', 'Hotel', 'City', 'Check-in', 'Check-out', 'Nights', 'Room', 'Rooms', 'Guests', 'Meal plan', 'Currency', 'Total', 'Payment', 'Invoice', 'Status']);
            foreach ($rows as $b) {
                fputcsv($out, [
                    $b->booking_ref, $b->confirmation_no, $b->customer->name, $b->customer->phone, $b->hotel_name, $b->city,
                    $b->check_in->format('Y-m-d'), $b->check_out->format('Y-m-d'), $b->nights, $b->room_type, $b->rooms, $b->guests_label,
                    $b->meal_plan, $b->currency, $b->total, optional($b->invoice)->payment_status, optional($b->invoice)->invoice_no, $b->status,
                ]);
            }
            fclose($out);
        }, 'hotel-bookings-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function filtered(Request $request)
    {
        $query = HotelBooking::query();

        if ($q = trim((string) $request->query('q'))) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('booking_ref', 'like', $like)
                    ->orWhere('confirmation_no', 'like', $like)
                    ->orWhere('hotel_name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('passport_no', 'like', $like);
                    });
            });
        }
        if (in_array($request->query('status'), ['confirmed', 'pending', 'cancelled'], true)) {
            $query->where('status', $request->query('status'));
        }
        if ($city = $request->query('city')) {
            $query->where('city', $city);
        }
        if (in_array($request->query('payment'), ['paid', 'unpaid'], true)) {
            $query->whereHas('invoice', function ($i) use ($request) {
                $i->where('payment_status', $request->query('payment'));
            });
        }
        if ($from = $request->query('from')) {
            try {
                $query->where('check_in', '>=', Carbon::parse($from)->toDateString());
            } catch (\Throwable $e) {
                // ignore an unparsable date filter
            }
        }

        return $query;
    }

    public function create(Request $request)
    {
        return view('hotels.form', [
            'booking' => new HotelBooking([
                'customer_id' => $request->customer_id, 'currency' => \App\Support\Settings::get('default_currency', 'PKR'), 'star_rating' => 5, 'rooms' => 1, 'adults' => 2,
                'children' => 0, 'check_in_time' => '15:00', 'check_out_time' => '12:00', 'status' => 'confirmed',
            ]),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $booking = DB::transaction(function () use ($data, $request) {
            if (empty($data['booking_ref'])) {
                $data['booking_ref'] = $this->makeRef($data['city']);
            }
            $booking = HotelBooking::create($data);
            $booking->update(['voucher_no' => 'VCH-' . date('Y') . '-' . str_pad($booking->id, 3, '0', STR_PAD_LEFT)]);

            $booking->invoice()->create([
                'invoice_no' => 'INV-HT-' . date('Y') . '-' . str_pad($booking->id, 3, '0', STR_PAD_LEFT),
                'customer_id' => $booking->customer_id,
                'total' => $booking->total,
                'currency' => $booking->currency,
                'payment_status' => $request->input('payment_status', 'paid'),
                'payment_method' => $request->input('payment_method', 'Online / Paid (100%)'),
                'issued_on' => $request->input('issued_on', now()->toDateString()),
            ]);

            return $booking;
        });

        return redirect()->route('hotels.show', $booking)->with('success', 'Hotel Booked Successfully');
    }

    public function show(HotelBooking $booking)
    {
        return view('hotels.show', ['booking' => $booking->load('customer', 'invoice')]);
    }

    public function edit(HotelBooking $booking)
    {
        return view('hotels.form', [
            'booking' => $booking->load('invoice'),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, HotelBooking $booking)
    {
        $data = $this->validated($request, $booking);
        if (empty($data['booking_ref'])) {
            $data['booking_ref'] = $booking->booking_ref;
        }

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

        return redirect()->route('hotels.show', $booking)->with('success', 'Booking updated.');
    }

    public function invoice(HotelBooking $booking)
    {
        $booking->load('customer', 'invoice');

        return PDF::loadView('pdf.hotel', ['booking' => $booking])
            ->download($booking->invoice->invoice_no . '.pdf');
    }

    private function makeRef($city)
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', Str::ascii($city)), 0, 3)) ?: 'HTL';

        do {
            $ref = 'HTL-' . $code . '-' . random_int(10000, 99999);
        } while (HotelBooking::where('booking_ref', $ref)->exists());

        return $ref;
    }

    private function validated(Request $request, HotelBooking $booking = null): array
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'booking_ref' => ['nullable', 'string', 'max:30', Rule::unique('hotel_bookings', 'booking_ref')->ignore($booking)],
            'confirmation_no' => 'nullable|string|max:40',
            'crs_code' => 'nullable|string|max:40',
            'hotel_name' => 'required|string|max:255',
            'star_rating' => 'required|integer|min:1|max:5',
            'city' => 'required|string|max:120',
            'country' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:255',
            'hotel_phone' => 'nullable|string|max:40',
            'hotel_email' => 'nullable|email|max:255',
            'check_in' => 'required|date',
            'check_out' => ['required', 'date', function ($attribute, $value, $fail) use ($request) {
                try {
                    if (Carbon::parse($value)->startOfDay()->lessThanOrEqualTo(Carbon::parse($request->input('check_in'))->startOfDay())) {
                        $fail('The check-out must be after the check-in.');
                    }
                } catch (\Throwable $e) {
                    $fail('The check-out date is not valid.');
                }
            }],
            'check_in_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'check_out_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'room_type' => 'required|string|max:255',
            'bed_config' => 'nullable|string|max:255',
            'rooms' => 'required|integer|min:1|max:20',
            'adults' => 'required|integer|min:1|max:20',
            'children' => 'nullable|integer|min:0|max:20',
            'meal_plan' => 'nullable|string|max:255',
            'special_request' => 'nullable|string|max:255',
            'amenities' => 'nullable|string|max:2000',
            'rate_per_night' => 'required|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'currency' => 'required|alpha|size:3',
            'status' => 'required|in:confirmed,pending,cancelled',
            'payment_status' => 'nullable|in:paid,unpaid',
            'payment_method' => 'nullable|string|max:255',
            'issued_on' => 'nullable|date',
        ]);

        $data['currency'] = strtoupper($data['currency']);
        $data['children'] = $data['children'] ?? 0;
        foreach (['taxes', 'discount'] as $f) {
            $data[$f] = $data[$f] ?? 0;
        }
        $nights = max(1, Carbon::parse($data['check_in'])->startOfDay()->diffInDays(Carbon::parse($data['check_out'])->startOfDay()));
        $data['total'] = max(0, $data['rate_per_night'] * $nights * $data['rooms'] + $data['taxes'] - $data['discount']);

        return array_diff_key($data, array_flip(['payment_status', 'payment_method', 'issued_on']));
    }
}
