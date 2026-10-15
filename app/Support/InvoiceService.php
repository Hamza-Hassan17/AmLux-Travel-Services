<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Customer;
use App\Models\FlightBooking;
use App\Models\HotelBooking;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    /**
     * Create or update an invoice with its service lines inside one transaction.
     * Each line also creates/updates the itinerary record (flight or hotel booking).
     */
    public function save(?Invoice $invoice, array $header, array $lines, $userId = null, array $payNow = null)
    {
        return DB::transaction(function () use ($invoice, $header, $lines, $userId, $payNow) {
            $customer = $this->resolveCustomer($header, $invoice);

            $data = [
                'customer_id' => $customer->id,
                'bill_to_type' => $header['bill_to_type'],
                'issued_on' => $header['issued_on'],
                'due_date' => $header['due_date'] ?: $header['issued_on'],
                'agent_id' => $header['agent_id'] ?: null,
                'agent_commission_mode' => $header['agent_id'] ? ($header['agent_commission_mode'] ?: 'fixed') : null,
                'agent_commission_value' => $header['agent_id'] ? (float) ($header['agent_commission_value'] ?? 0) : 0,
                'notes' => $header['notes'] ?? null,
                'currency' => Settings::get('default_currency', 'PKR'),
            ];

            if ($invoice) {
                $invoice->update($data);
            } else {
                $invoice = Invoice::create($data + [
                    'invoice_no' => Numbering::next('invoices', 'invoice_no', 'INV'),
                    'total' => 0,
                    'payment_status' => 'unpaid',
                    'user_id' => $userId,
                ]);
            }

            $keep = [];
            foreach ($lines as $row) {
                $keep[] = $this->saveLine($invoice, $customer, $row)->id;
            }

            // lines removed from the form
            foreach ($invoice->lines()->whereNotIn('id', $keep ?: [0])->get() as $gone) {
                if ($gone->refunds()->exists() || \App\Models\PaymentAllocation::where('invoice_line_id', $gone->id)->exists()) {
                    throw ValidationException::withMessages(['lines' => 'A line with supplier payments or refunds cannot be removed.']);
                }
                if ($gone->serviceable) {
                    $gone->serviceable->delete();
                }
                $gone->delete();
            }

            $invoice->recalc();

            if ($payNow && ($payNow['amount'] ?? 0) > 0) {
                $this->receiveNow($invoice, $payNow, $userId);
                $invoice->recalc();
            }

            return $invoice->fresh(['lines', 'customer']);
        });
    }

    private function resolveCustomer(array $header, ?Invoice $invoice)
    {
        if ($header['bill_to_type'] === 'walk_in') {
            if ($invoice && $invoice->customer && $invoice->customer->type === 'walk_in') {
                $invoice->customer->update(['name' => $header['walk_in_name'], 'phone' => $header['walk_in_mobile'] ?? null]);

                return $invoice->customer;
            }

            return Customer::create([
                'type' => 'walk_in', 'name' => $header['walk_in_name'], 'phone' => $header['walk_in_mobile'] ?? null,
                'nationality' => 'Pakistani',
            ]);
        }

        return Customer::findOrFail($header['customer_id']);
    }

    private function saveLine(Invoice $invoice, Customer $customer, array $row)
    {
        $service = $row['service_type'];

        $breakdown = array_filter($row['breakdown'] ?? [], function ($v) {
            return $v !== null && $v !== '';
        });
        $buying = $breakdown && $service === 'flight'
            ? InvoiceLine::buyingFromBreakdown($breakdown)
            : (float) ($row['buying_price'] ?? 0);

        $price = InvoiceLine::price([
            'buying_price' => $buying,
            'selling_price' => $row['selling_price'] ?? 0,
            'discount' => $row['discount'] ?? 0,
            'service_charge' => $row['service_charge'] ?? 0,
            'tax_mode' => $row['tax_mode'] ?? 'none',
            'tax_rate' => $row['tax_rate'] ?? 0,
            'taxable_on_margin' => ! empty($row['taxable_on_margin']),
        ]);

        $line = ! empty($row['id']) ? $invoice->lines()->where('id', $row['id'])->first() : null;
        $booking = $line && $line->serviceable ? $line->serviceable : null;
        $currency = Settings::get('default_currency', 'PKR');

        if ($service === 'flight') {
            $attrs = [
                'customer_id' => $customer->id, 'invoice_id' => $invoice->id,
                'pnr' => strtoupper(trim($row['pnr'])), 'ticket_no' => $row['ticket_no'] ?? null,
                'airline' => $row['airline'], 'flight_no' => $row['flight_no'], 'aircraft' => $row['aircraft'] ?? null,
                'origin' => strtoupper($row['origin']), 'destination' => strtoupper($row['destination']),
                'origin_airport' => $row['origin_airport'] ?? null, 'destination_airport' => $row['destination_airport'] ?? null,
                'departure_at' => $row['departure_at'], 'arrival_at' => ($row['arrival_at'] ?? null) ?: null,
                'cabin_class' => ($row['cabin_class'] ?? null) ?: 'Economy', 'adults' => max(1, (int) ($row['adults'] ?? 1)),
                'seat' => $row['seat'] ?? null, 'baggage' => $row['baggage'] ?? null, 'meal' => $row['meal'] ?? null,
                'passenger_name' => $row['passenger_name'] ?? null, 'passenger_mobile' => $row['passenger_mobile'] ?? null,
                'base_fare' => $price['selling_price'], 'taxes' => 0, 'fuel_surcharge' => 0, 'discount' => $price['discount'],
                'total' => $price['line_total'], 'currency' => $currency, 'status' => ($row['status'] ?? null) ?: 'confirmed',
            ];
            $booking = $booking ? tap($booking)->update($attrs) : FlightBooking::create($attrs);
            $reference = $attrs['pnr'];
            $passenger = $attrs['passenger_name'] ?: $customer->name;
        } else {
            $checkIn = Carbon::parse($row['check_in'])->startOfDay();
            $checkOut = Carbon::parse($row['check_out'])->startOfDay();
            $nights = max(1, $checkIn->diffInDays($checkOut));
            $rooms = max(1, (int) ($row['rooms'] ?? 1));
            $ref = trim($row['booking_ref'] ?? '') ?: ($booking->booking_ref ?? $this->hotelRef($row['city']));
            $attrs = [
                'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'booking_ref' => strtoupper($ref),
                'confirmation_no' => $row['confirmation_no'] ?? null, 'hotel_name' => $row['hotel_name'],
                'star_rating' => (int) (($row['star_rating'] ?? null) ?: 5), 'city' => $row['city'], 'country' => $row['country'] ?? null,
                'address' => $row['address'] ?? null, 'hotel_phone' => $row['hotel_phone'] ?? null, 'hotel_email' => $row['hotel_email'] ?? null,
                'check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString(),
                'check_in_time' => ($row['check_in_time'] ?? null) ?: '15:00', 'check_out_time' => ($row['check_out_time'] ?? null) ?: '12:00',
                'room_type' => ($row['room_type'] ?? null) ?: 'Standard Room', 'bed_config' => $row['bed_config'] ?? null,
                'rooms' => $rooms, 'adults' => max(1, (int) ($row['adults'] ?? 1)), 'children' => (int) ($row['children'] ?? 0),
                'meal_plan' => $row['meal_plan'] ?? null, 'special_request' => $row['special_request'] ?? null,
                'amenities' => $row['amenities'] ?? null, 'guest_name' => $row['guest_name'] ?? null, 'guest_mobile' => $row['guest_mobile'] ?? null,
                'rate_per_night' => round($price['selling_price'] / ($nights * $rooms), 2), 'taxes' => 0, 'discount' => $price['discount'],
                'total' => $price['line_total'], 'currency' => $currency, 'status' => ($row['status'] ?? null) ?: 'confirmed',
            ];
            if ($booking) {
                $booking->update($attrs);
            } else {
                $booking = HotelBooking::create($attrs);
                $booking->update(['voucher_no' => 'VCH-' . date('Y') . '-' . str_pad($booking->id, 3, '0', STR_PAD_LEFT)]);
            }
            $reference = $attrs['booking_ref'];
            $passenger = $attrs['guest_name'] ?: $customer->name;
        }

        $lineData = $price + [
            'service_type' => $service,
            'supplier_id' => ($row['supplier_id'] ?? null) ?: null,
            'reference' => $reference,
            'passenger' => $passenger,
            'description' => $row['description'] ?? null,
            'buying_breakdown' => $breakdown ? json_encode($breakdown) : null,
            'serviceable_type' => get_class($booking),
            'serviceable_id' => $booking->id,
        ];

        if ($line) {
            $line->update($lineData);
        } else {
            $line = $invoice->lines()->create($lineData);
        }

        return $line;
    }

    private function hotelRef($city)
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', Str::ascii((string) $city)), 0, 3)) ?: 'HTL';
        do {
            $ref = 'HTL-' . $code . '-' . random_int(10000, 99999);
        } while (HotelBooking::where('booking_ref', $ref)->exists());

        return $ref;
    }

    /** Record a receipt for money taken at the time the invoice is created. */
    private function receiveNow(Invoice $invoice, array $payNow, $userId)
    {
        $amount = min((float) $payNow['amount'], $invoice->total);
        $account = Account::findOrFail($payNow['account_id']);

        $receipt = Receipt::create([
            'receipt_no' => Numbering::next('receipts', 'receipt_no', 'RCT'),
            'customer_id' => $invoice->customer_id, 'account_id' => $account->id,
            'amount' => $amount, 'received_on' => $invoice->issued_on, 'reference' => $payNow['reference'] ?? null,
            'remarks' => 'Received with invoice ' . $invoice->invoice_no, 'user_id' => $userId,
        ]);
        ReceiptAllocation::create(['receipt_id' => $receipt->id, 'invoice_id' => $invoice->id, 'amount' => $amount]);
        $invoice->update(['payment_method' => $account->name]);
    }
}
