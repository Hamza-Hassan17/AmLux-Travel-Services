<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $guarded = [];

    protected $casts = ['refunded_on' => 'date'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function line()
    {
        return $this->belongsTo(InvoiceLine::class, 'invoice_line_id');
    }

    public function getOutstandingAttribute()
    {
        return round($this->payable_amount - $this->paid_amount, 2);
    }
}
