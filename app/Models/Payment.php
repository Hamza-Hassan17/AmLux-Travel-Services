<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

    protected $casts = ['paid_on' => 'date'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

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
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getPayeeNameAttribute()
    {
        return $this->payee_type === 'supplier' ? optional($this->supplier)->name : optional($this->customer)->name;
    }

    public function getUnallocatedAttribute()
    {
        return round($this->amount - $this->allocations()->sum('amount'), 2);
    }
}
