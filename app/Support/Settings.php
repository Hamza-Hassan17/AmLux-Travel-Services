<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Agency-wide settings (shown on invoices and vouchers). Values live in the
 * `settings` table; anything not saved falls back to the defaults below.
 */
class Settings
{
    public const DEFAULTS = [
        'name' => 'AmLux Travel Services',
        'tagline' => 'Indulge in luxury experiences',
        'head_office' => 'Suite 402, Luxury Plaza, Karachi',
        'helpline' => '+92 21 111 2589',
        'mobile' => '+92 300 1234567',
        'email' => 'info@amluxtravels.com',
        'reservations_email' => 'reservations@amluxtravels.com',
        'website' => 'www.amluxtravels.com',
        'default_currency' => 'PKR',
        'tax_label' => 'GST',
        'tax_rate' => '0',
        'bank_name' => '',
        'bank_account_title' => '',
        'bank_account_no' => '',
        'bank_iban' => '',
        'invoice_terms' => 'Payment is due by the due date shown. Tickets and hotel vouchers are subject to supplier rules; changes and refunds follow the supplier policy and AmLux service charges.',
        'signatory' => '',
    ];

    protected static $cache;

    public static function all()
    {
        if (self::$cache === null) {
            try {
                $stored = Setting::pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                $stored = [];   // table not migrated yet
            }
            $stored = array_filter($stored, function ($v) {
                return $v !== null && $v !== '';
            });
            self::$cache = array_merge(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
        }

        return self::$cache;
    }

    public static function get($key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function save(array $values)
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        self::$cache = null;
    }
}
