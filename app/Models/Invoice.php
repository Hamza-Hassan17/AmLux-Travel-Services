<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = [];

    protected $casts = ['issued_on' => 'date', 'due_date' => 'date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('id');
    }

    public function allocations()
    {
        return $this->hasMany(ReceiptAllocation::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function flightBookings()
    {
        return $this->hasMany(FlightBooking::class);
    }

    public function hotelBookings()
    {
        return $this->hasMany(HotelBooking::class);
    }

    /** What the customer still has to pay after receipts and refunds. */
    public function getBalanceAttribute()
    {
        return max(0, round($this->total - $this->refunded_amount - $this->paid_amount, 2));
    }

    public function getNetTotalAttribute()
    {
        return round($this->total - $this->refunded_amount, 2);
    }

    public function getProfitAttribute()
    {
        return round($this->total - $this->tax_total - $this->buying_total, 2);
    }

    /** Refresh cached totals and the paid/partial/unpaid state from lines, receipts and refunds. */
    public function recalc()
    {
        $this->loadMissing('lines');
        $lines = $this->lines()->get();

        $net = $lines->sum(function ($l) {
            return $l->selling_price - $l->discount + $l->service_charge;
        });
        $this->subtotal = round($net, 2);
        $this->tax_total = round($lines->sum('tax_amount'), 2);
        $this->buying_total = round($lines->sum('buying_price'), 2);
        $this->total = round($lines->sum('line_total'), 2);

        $profit = $this->total - $this->tax_total - $this->buying_total;
        if ($this->agent_id && $this->agent_commission_mode === 'percent') {
            $this->agent_commission = round(max(0, $profit) * $this->agent_commission_value / 100, 2);
        } elseif ($this->agent_id && $this->agent_commission_mode === 'fixed') {
            $this->agent_commission = round($this->agent_commission_value, 2);
        } else {
            $this->agent_commission = 0;
        }

        $this->paid_amount = round($this->allocations()->sum('amount'), 2);
        $this->refunded_amount = round($this->refunds()->sum('customer_refund'), 2);

        $due = $this->total - $this->refunded_amount;
        if ($this->refunded_amount > 0 && $due <= 0.005) {
            $this->payment_status = 'cancelled';
        } elseif ($this->paid_amount >= $due - 0.005 && $due > 0.005) {
            $this->payment_status = 'paid';
        } elseif ($this->paid_amount > 0.005) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = $due <= 0.005 ? 'paid' : 'unpaid';
        }

        $this->save();

        return $this;
    }
}
