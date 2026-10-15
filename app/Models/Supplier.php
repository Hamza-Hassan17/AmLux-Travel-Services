<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /** Amount of the opening balance not yet paid. */
    public function getOpeningDueAttribute()
    {
        $paid = PaymentAllocation::whereNull('invoice_line_id')->whereNull('refund_id')
            ->whereIn('payment_id', $this->payments()->select('id'))->sum('amount');

        return round($this->opening_balance - $paid, 2);
    }

    /** Total we currently owe this supplier (opening balance + lines - refunds - payments). Negative = they owe us. */
    public function getPayableAttribute()
    {
        $lines = $this->lines()->get();
        $due = 0;
        foreach ($lines as $line) {
            $due += $line->supplier_payable;
        }

        return round($due + $this->opening_due, 2);
    }
}
