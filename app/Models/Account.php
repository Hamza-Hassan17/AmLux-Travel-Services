<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getBalanceAttribute()
    {
        return round($this->opening_balance + $this->receipts()->sum('amount') - $this->payments()->sum('amount'), 2);
    }
}
