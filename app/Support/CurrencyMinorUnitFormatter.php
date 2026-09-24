<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use NumberFormatter;

class CurrencyMinorUnitFormatter
{
    /**
     * @var array<string, int>
     */
    private const FALLBACK_FRACTION_DIGITS = [
        'BHD' => 3,
        'BIF' => 0,
        'CLF' => 4,
        'CLP' => 0,
        'DJF' => 0,
        'GNF' => 0,
        'IQD' => 3,
        'ISK' => 0,
        'JOD' => 3,
        'JPY' => 0,
        'KMF' => 0,
        'KRW' => 0,
        'KWD' => 3,
        'LYD' => 3,
        'OMR' => 3,
        'PYG' => 0,
        'RWF' => 0,
        'TND' => 3,
        'UGX' => 0,
        'UYW' => 4,
        'UYI' => 0,
        'VND' => 0,
        'VUV' => 0,
        'XAF' => 0,
        'XOF' => 0,
        'XPF' => 0,
    ];

    public static function format(int $amountMinor, string $currency): string
    {
        if ($amountMinor < 0) {
            throw new InvalidArgumentException('Currency minor units must be non-negative.');
        }

        $currency = strtoupper($currency);
        $fractionDigits = self::fractionDigits($currency);
        $divisor = (int) (10 ** $fractionDigits);
        $wholeAmount = (string) intdiv($amountMinor, $divisor);
        $fractionalAmount = $amountMinor % $divisor;
        $groupedWholeAmount = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $wholeAmount) ?? $wholeAmount;
        $fraction = $fractionDigits === 0
            ? ''
            : '.'.str_pad((string) $fractionalAmount, $fractionDigits, '0', STR_PAD_LEFT);

        return "{$currency} {$groupedWholeAmount}{$fraction}";
    }

    private static function fractionDigits(string $currency): int
    {
        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter('en_KE', NumberFormatter::CURRENCY);

            if ($formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currency)) {
                $fractionDigits = $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS);

                if ($fractionDigits !== false) {
                    return (int) $fractionDigits;
                }
            }
        }

        return self::FALLBACK_FRACTION_DIGITS[$currency] ?? 2;
    }
}
