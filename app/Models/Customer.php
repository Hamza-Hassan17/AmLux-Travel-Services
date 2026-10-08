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
}
