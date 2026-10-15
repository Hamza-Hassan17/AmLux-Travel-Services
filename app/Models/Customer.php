<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    protected $casts = ['date_of_birth' => 'date'];

    public function flightBookings()
    {
        return $this->hasMany(FlightBooking::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    /** Opening balance not yet settled by receipts. */
    public function getOpeningDueAttribute()
    {
        $paid = ReceiptAllocation::whereNull('invoice_id')->whereIn('receipt_id', $this->receipts()->select('id'))->sum('amount');

        return round($this->opening_balance - $paid, 2);
    }

    /** What the customer owes us: opening balance plus unpaid invoice balances. */
    public function getOutstandingAttribute()
    {
        $inv = $this->invoices()->get()->sum(function ($i) {
            return $i->balance;
        });

        return round(max(0, $this->opening_due) + $inv, 2);
    }

    /** Money received from the customer that has not been matched to an invoice. */
    public function getAdvanceAttribute()
    {
        $received = $this->receipts()->sum('amount');
        $allocated = ReceiptAllocation::whereIn('receipt_id', $this->receipts()->select('id'))->sum('amount');

        return round($received - $allocated, 2);
    }

    public function hotelBookings()
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function getInitialsAttribute()
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)
            ->map(function ($w) { return mb_strtoupper(mb_substr($w, 0, 1)); })
            ->implode('');
    }
}
