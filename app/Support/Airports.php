<?php

namespace App\Support;

class Airports
{
    private static $cities = [
        'KHI' => 'Karachi', 'LHE' => 'Lahore', 'ISB' => 'Islamabad', 'PEW' => 'Peshawar', 'MUX' => 'Multan',
        'SKT' => 'Sialkot', 'LYP' => 'Faisalabad', 'UET' => 'Quetta', 'GWD' => 'Gwadar', 'RYK' => 'Rahim Yar Khan',
        'DXB' => 'Dubai', 'DWC' => 'Dubai', 'SHJ' => 'Sharjah', 'AUH' => 'Abu Dhabi', 'DOH' => 'Doha',
        'JED' => 'Jeddah', 'RUH' => 'Riyadh', 'DMM' => 'Dammam', 'MED' => 'Madinah', 'KWI' => 'Kuwait City',
        'BAH' => 'Manama', 'MCT' => 'Muscat', 'IST' => 'Istanbul', 'LHR' => 'London', 'MAN' => 'Manchester',
        'JFK' => 'New York', 'YYZ' => 'Toronto', 'KUL' => 'Kuala Lumpur', 'BKK' => 'Bangkok', 'SIN' => 'Singapore',
        'DEL' => 'Delhi', 'BOM' => 'Mumbai', 'DAC' => 'Dhaka', 'CMB' => 'Colombo', 'CAI' => 'Cairo',
    ];

    /** City for an IATA code, falling back to the last comma-separated part of the airport name. */
    public static function city($iata, $airportName = null)
    {
        $iata = strtoupper(trim((string) $iata));
        if (isset(self::$cities[$iata])) {
            return self::$cities[$iata];
        }
        if ($airportName && strpos($airportName, ',') !== false) {
            return trim(substr(strrchr($airportName, ','), 1));
        }

        return '';
    }

    /** Two-character airline designator from a flight number such as "EK 601" or "6E-123". */
    public static function carrierCode($flightNo, $airline = '')
    {
        if (preg_match('/^\s*([A-Za-z0-9]{2})/', (string) $flightNo, $m)) {
            return strtoupper($m[1]);
        }

        return strtoupper(mb_substr(preg_replace('/[^A-Za-z]/', '', (string) $airline), 0, 2));
    }

    public static function carrierColor($airline)
    {
        $palette = ['#DA2535', '#0B4EA2', '#0A7A5A', '#6B3FA0', '#C26A00', '#1F6F8B', '#B3262B', '#244A9A'];

        return $palette[abs(crc32(strtolower(trim((string) $airline)))) % count($palette)];
    }
}
