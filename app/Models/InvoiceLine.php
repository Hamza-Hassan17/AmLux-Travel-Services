<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    protected $guarded = [];

    protected $casts = ['taxable_on_margin' => 'boolean'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function serviceable()
    {
        return $this->morphTo();
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function getBreakdownAttribute()
    {
        return $this->buying_breakdown ? (json_decode($this->buying_breakdown, true) ?: []) : [];
    }

    /** What we owe the supplier for this line: buying price less what they refunded, less what we paid. */
    public function getSupplierPayableAttribute()
    {
        $paid = PaymentAllocation::where('invoice_line_id', $this->id)->sum('amount');

        return round($this->buying_price - $this->refunds()->sum('supplier_refund') - $paid, 2);
    }

    /**
     * Buying price from the flight fare components (TravCount-style).
     * Commissions reduce the cost; withholding tax on them, TCS, supplier fee and its tax add to it.
     */
    public static function buyingFromBreakdown(array $b)
    {
        $f = function ($k) use ($b) {
            return (float) ($b[$k] ?? 0);
        };
        $basic = $f('basic_fare');
        $taxes = $f('taxes');
        $commission = $basic * ($f('plb_pct') + $f('iata_pct')) / 100;

        $buying = $basic + $taxes + $f('other_tax')
            + ($basic + $taxes) * $f('tcs_pct') / 100
            + $f('supplier_fee') * (1 + $f('ssf_tax_pct') / 100)
            - $commission
            + $commission * $f('iata_plb_tax_pct') / 100;

        return round(max(0, $buying), 2);
    }

    /**
     * Price a line. $in: buying_price, selling_price, discount, service_charge,
     * tax_mode (none|inclusive|exclusive), tax_rate, taxable_on_margin.
     */
    public static function price(array $in)
    {
        $buying = round((float) ($in['buying_price'] ?? 0), 2);
        $selling = round((float) ($in['selling_price'] ?? 0), 2);
        $discount = round((float) ($in['discount'] ?? 0), 2);
        $service = round((float) ($in['service_charge'] ?? 0), 2);
        $mode = $in['tax_mode'] ?? 'none';
        $rate = $mode === 'none' ? 0 : (float) ($in['tax_rate'] ?? 0);
        $onMargin = ! empty($in['taxable_on_margin']);

        $net = max(0, $selling - $discount + $service);
        $base = $onMargin ? max(0, $net - $buying) : $net;

        $tax = 0;
        $total = $net;
        if ($mode === 'inclusive' && $rate > 0) {
            $tax = $base * $rate / (100 + $rate);
        } elseif ($mode === 'exclusive' && $rate > 0) {
            $tax = $base * $rate / 100;
            $total = $net + $tax;
        }

        return [
            'buying_price' => $buying, 'selling_price' => $selling, 'discount' => $discount, 'service_charge' => $service,
            'tax_mode' => $mode, 'tax_rate' => $rate, 'taxable_on_margin' => $onMargin,
            'tax_amount' => round($tax, 2), 'line_total' => round($total, 2),
        ];
    }
}
