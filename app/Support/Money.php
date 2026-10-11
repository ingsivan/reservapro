<?php

namespace App\Support;

class Money
{
    /**
     * Format an amount stored in cents, e.g. 25000 → "$250.00".
     */
    public static function format(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
