<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Numbering
{
    /** Next document number like INV/26/0007 (resets every year). */
    public static function next($table, $column, $prefix)
    {
        $yy = date('y');
        $last = DB::table($table)->where($column, 'like', $prefix . '/' . $yy . '/%')->orderByDesc($column)->value($column);
        $n = $last ? ((int) substr($last, strrpos($last, '/') + 1)) + 1 : 1;

        return $prefix . '/' . $yy . '/' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}
