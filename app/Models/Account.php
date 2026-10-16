<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    /** Make sure there is always a Cash and a Bank account to receive money into. */
    public static function ensureDefaults()
    {
        if (! static::query()->exists()) {
            static::create(['name' => 'Cash', 'type' => 'cash', 'opening_balance' => 0, 'is_active' => true]);
            static::create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => 0, 'is_active' => true]);
        }
    }

    /** Active accounts, creating the defaults first when none exist yet. */
    public static function active()
    {
        static::ensureDefaults();

        return static::where('is_active', true)->orderBy('id');
    }

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
