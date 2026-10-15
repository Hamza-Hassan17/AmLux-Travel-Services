<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlightBooking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function line()
    {
        return $this->morphOne(InvoiceLine::class, 'serviceable');
    }

    /** The traveller on the ticket (falls back to the billed customer). */
    public function getPassengerAttribute()
    {
        return $this->passenger_name ?: optional($this->customer)->name;
    }

    public function getDurationAttribute()
    {
        if (! $this->arrival_at) {
            return null;
        }
        $m = $this->departure_at->diffInMinutes($this->arrival_at);

        return intdiv($m, 60) . 'h ' . ($m % 60) . 'm';
    }
}
