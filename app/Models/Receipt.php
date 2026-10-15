<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $guarded = [];

    protected $casts = ['received_on' => 'date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function allocations()
    {
        return $this->hasMany(ReceiptAllocation::class);
    }

    public function getAllocatedAttribute()
    {
        return round($this->allocations()->sum('amount'), 2);
    }

    /** Money received but not yet matched to an invoice (customer advance). */
    public function getUnallocatedAttribute()
    {
        return round($this->amount - $this->allocated, 2);
    }
}
