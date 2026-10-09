<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'passport_no', 'nationality', 'address'];

    public function flightBookings()
    {
        return $this->hasMany(FlightBooking::class);
    }

    public function getInitialsAttribute()
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)
            ->map(function ($w) { return mb_strtoupper(mb_substr($w, 0, 1)); })
            ->implode('');
    }
}
