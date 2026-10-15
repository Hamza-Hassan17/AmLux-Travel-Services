<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelBooking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
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

    public function getGuestAttribute()
    {
        return $this->guest_name ?: optional($this->customer)->name;
    }

    public function getNightsAttribute()
    {
        return max(1, $this->check_in->diffInDays($this->check_out));
    }

    public function getGuestsLabelAttribute()
    {
        $label = $this->adults . ' ' . ($this->adults == 1 ? 'Adult' : 'Adults');
        if ($this->children) {
            $label .= ', ' . $this->children . ' ' . ($this->children == 1 ? 'Child' : 'Children');
        }

        return $label;
    }

    public function getAmenityListAttribute()
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->amenities))));
    }
}
