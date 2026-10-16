<?php

namespace App\Support;

class SparePartLuhn
{
    /**
     * Validate a spare code using the Luhn check digit (digits only).
     */
    public static function isValid(string $code): bool
    {
        $digits = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($digits) < 2) {
            return false;
        }

        $sum = 0;
        $parity = strlen($digits) % 2;

        for ($i = 0; $i < strlen($digits); $i++) {
            $digit = (int) $digits[$i];
            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    /**
     * Append a Luhn check digit to a body of digits.
     */
    public static function appendCheckDigit(string $bodyDigits): string
    {
        $body = preg_replace('/\D/', '', $bodyDigits) ?? '';
        if ($body === '') {
            throw new \InvalidArgumentException('Body digits are required.');
        }

        for ($check = 0; $check <= 9; $check++) {
            if (self::isValid($body.$check)) {
                return $body.$check;
            }
        }

        throw new \RuntimeException('Unable to compute Luhn check digit.');
    }

    /**
     * Generate a numeric spare code with a valid Luhn check digit.
     */
    public static function generate(int $bodyLength = 7): string
    {
        $bodyLength = max(2, $bodyLength);
        $max = (int) str_repeat('9', $bodyLength);
        $body = str_pad((string) random_int(0, $max), $bodyLength, '0', STR_PAD_LEFT);

        return self::appendCheckDigit($body);
    }
}
