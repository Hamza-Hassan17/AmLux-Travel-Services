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
        return redirect()->route('invoices.create', array_filter(['service' => 'hotel', 'customer_id' => $request->query('customer_id')]));
    }

    public function show(HotelBooking $booking)
    {
        return view('hotels.show', ['booking' => $booking->load('customer', 'invoice', 'line')]);
    }

    public function edit(HotelBooking $booking)
    {
        return view('hotels.form', ['booking' => $booking->load('customer', 'invoice')]);
    }

    /** Stay details only; prices are edited on the invoice. */
    public function update(Request $request, HotelBooking $booking)
    {
        $data = $request->validate([
            'booking_ref' => ['required', 'string', 'max:30', Rule::unique('hotel_bookings', 'booking_ref')->ignore($booking)],
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
            'check_out' => 'required|date|after:check_in',
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
            'guest_name' => 'nullable|string|max:255',
            'guest_mobile' => 'nullable|string|max:30',
            'status' => 'required|in:confirmed,pending,cancelled',
        ]);
        $data['booking_ref'] = strtoupper($data['booking_ref']);
        $data['children'] = $data['children'] ?? 0;

        $booking->update($data);
        if ($booking->line) {
            $booking->line->update(['reference' => $data['booking_ref'], 'passenger' => $data['guest_name'] ?: $booking->customer->name]);
        }

        return redirect()->route('hotels.show', $booking)->with('success', 'Booking updated.');
    }

    public function invoice(HotelBooking $booking)
    {
        $booking->load('customer', 'invoice');

        return PDF::loadView('pdf.hotel', ['booking' => $booking])
            ->download(($booking->invoice ? str_replace('/', '-', $booking->invoice->invoice_no) : 'voucher-' . $booking->booking_ref) . '-voucher.pdf');
    }
}
