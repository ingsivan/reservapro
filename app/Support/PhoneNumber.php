<?php

namespace App\Support;

/**
 * Phone numbers in the international E.164 format WhatsApp needs, e.g. +525512345678.
 */
class PhoneNumber
{
    /** Country code for numbers written without one (Mexico). */
    public const DEFAULT_COUNTRY_CODE = '52';

    /**
     * "55 1234 5678", "(55) 1234-5678" or "+52 55 1234 5678" become "+525512345678".
     * Returns null when the number can't be read.
     */
    public static function toE164(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        $hasPlus = str_starts_with(ltrim($phone), '+') || str_starts_with($digits, '00');
        $digits = str_starts_with($digits, '00') ? substr($digits, 2) : $digits;

        $international = match (true) {
            $hasPlus => $digits,
            strlen($digits) === 10 => self::DEFAULT_COUNTRY_CODE.$digits,
            strlen($digits) === 12 && str_starts_with($digits, self::DEFAULT_COUNTRY_CODE) => $digits,
            default => null,
        };

        return $international !== null && strlen($international) >= 10 && strlen($international) <= 15
            ? '+'.$international
            : null;
    }

    /**
     * Link that opens a WhatsApp chat with the number, or null if it can't be read.
     */
    public static function whatsappUrl(?string $phone): ?string
    {
        $number = self::toE164($phone);

        return $number ? 'https://wa.me/'.ltrim($number, '+') : null;
    }
}
