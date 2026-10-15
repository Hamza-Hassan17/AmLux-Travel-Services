<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $guarded = [];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function line()
    {
        return $this->belongsTo(InvoiceLine::class, 'invoice_line_id');
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }
}
