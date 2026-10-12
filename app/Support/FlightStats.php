<?php

namespace App\Support;

use App\Models\FlightBooking;

class FlightStats
{
    public static function counts()
    {
        return FlightBooking::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
    }

    public static function kpis($counts = null)
    {
        $counts = $counts ?: self::counts();
        $now = now();

        $thisMonth = FlightBooking::where('created_at', '>=', $now->copy()->startOfMonth());
        $lastMonthCount = FlightBooking::whereBetween('created_at', [
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->startOfMonth(),
        ])->count();
        $monthCount = (clone $thisMonth)->count();
        $revenue = (clone $thisMonth)->where('currency', 'PKR')->where('status', '!=', 'cancelled')->sum('total');

        return [
            'month' => $monthCount,
            'trend' => $lastMonthCount ? round(($monthCount - $lastMonthCount) / $lastMonthCount * 100) : null,
            'lastMonth' => $now->copy()->subMonthNoOverflow()->format('M'),
            'revenue' => self::compact($revenue),
            'pending' => $counts->get('pending', 0),
            'pendingSoon' => FlightBooking::where('status', 'pending')->whereBetween('departure_at', [$now, $now->copy()->addDays(3)])->count(),
            'upcoming' => FlightBooking::where('status', '!=', 'cancelled')->whereBetween('departure_at', [$now, $now->copy()->addDays(7)])->count(),
        ];
    }

    public static function compact($n)
    {
        if ($n >= 1000000) {
            return rtrim(rtrim(number_format($n / 1000000, 2), '0'), '.') . 'M';
        }
        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
        }

        return number_format($n);
    }
}
