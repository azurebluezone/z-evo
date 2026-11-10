<?php

namespace App\Support;

/**
 * One phone format for outbound ad platforms.
 *
 * Meta and TikTok both require the phone to be hashed in E.164 form: digits
 * only, country code included, no plus sign. A local `01712345678` hashes to a
 * different value than `8801712345678`, and the platform silently reports
 * "sent" while matching nothing. The shop is Bangladesh-first, so 880 is the
 * default country code; callers can pass another one.
 */
final class PhoneNormalizer
{
    public static function e164(?string $phone, string $defaultCountryCode = '880'): string
    {
        if ($phone === null) {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (!is_string($digits) || $digits === '') {
            return '';
        }

        // International prefix written as 00 instead of +.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($defaultCountryCode === '880') {
            // 01XXXXXXXXX -> 8801XXXXXXXXX
            if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                return '880' . substr($digits, 1);
            }

            // 1XXXXXXXXX -> 8801XXXXXXXXX
            if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
                return '880' . $digits;
            }
        }

        return $digits;
    }
}
