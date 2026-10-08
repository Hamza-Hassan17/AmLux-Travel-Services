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
        return $this->morphOne(Invoice::class, 'invoiceable');
    }

    public function getDurationAttribute()
    {
        $m = $this->departure_at->diffInMinutes($this->arrival_at);
        return intdiv($m, 60) . 'h ' . ($m % 60) . 'm';
    }
}
