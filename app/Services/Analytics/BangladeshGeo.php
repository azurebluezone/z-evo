<?php

namespace App\Services\Analytics;

/**
 * Bangladesh division mapping.
 *
 * MaxMind / the fallback location drivers return a *city* name (e.g. "Sylhet",
 * "Barisal") but almost never a division. The dashboard's Location section is
 * grouped by division, so every resolved city/district is normalised here.
 *
 * Keeping the map in one place means adding a new fallback or changing a spelling
 * only ever touches this class.
 */
class BangladeshGeo
{
    /**
     * The eight administrative divisions.
     *
     * @var array<string, string>
     */
    public const DIVISIONS = [
        'Barishal',
        'Chattogram',
        'Dhaka',
        'Khulna',
        'Mymensingh',
        'Rajshahi',
        'Rangpur',
        'Sylhet',
    ];

    /**
     * Division headquarters, used only to place a circle on the admin live map.
     *
     * These are the divisional capital coordinates (not a geocoder): the map
     * answers "which division is active and how many visitors", so a single
     * stable point per division is the correct level of precision. Plotting an
     * IP-derived city would pretend to a precision the geo database does not
     * have.
     *
     * @var array<string, array{0: float, 1: float}>  [lat, lng]
     */
    public const DIVISION_CENTERS = [
        'Barishal' => [22.7010, 90.3535],
        'Chattogram' => [22.3569, 91.7832],
        'Dhaka' => [23.8103, 90.4125],
        'Khulna' => [22.8456, 89.5403],
        'Mymensingh' => [24.7471, 90.4203],
        'Rajshahi' => [24.3745, 88.6042],
        'Rangpur' => [25.7439, 89.2752],
        'Sylhet' => [24.8949, 91.8687],
    ];

    /**
     * District => Division. This is the authoritative source of truth for the
     * dashboard's geo grouping; city names are matched against these values too
     * because many providers return a district-level city name.
     *
     * @var array<string, string>
     */
    public const DISTRICT_TO_DIVISION = [
        // Barishal (6)
        'barguna' => 'Barishal',
        'barishal' => 'Barishal',
        'barisal' => 'Barishal',
        'bhola' => 'Barishal',
        'jhalokati' => 'Barishal',
        'jhalokathi' => 'Barishal',
        'patuakhali' => 'Barishal',
        'pirojpur' => 'Barishal',

        // Chattogram (11)
        'bandarban' => 'Chattogram',
        'brahmanbaria' => 'Chattogram',
        'chandpur' => 'Chattogram',
        'chattogram' => 'Chattogram',
        'chittagong' => 'Chattogram',
        'cumilla' => 'Chattogram',
        'comilla' => 'Chattogram',
        'coxsbazar' => 'Chattogram',
        "cox's bazar" => 'Chattogram',
        'feni' => 'Chattogram',
        'khagrachhari' => 'Chattogram',
        'khagrachari' => 'Chattogram',
        'lakshmipur' => 'Chattogram',
        'noakhali' => 'Chattogram',
        'rangamati' => 'Chattogram',

        // Dhaka (13)
        'dhaka' => 'Dhaka',
        'faridpur' => 'Dhaka',
        'gazipur' => 'Dhaka',
        'gopalganj' => 'Dhaka',
        'kishoreganj' => 'Dhaka',
        'kishoreganj' => 'Dhaka',
        'madaripur' => 'Dhaka',
        'manikganj' => 'Dhaka',
        'munshiganj' => 'Dhaka',
        'narayanganj' => 'Dhaka',
        'narsingdi' => 'Dhaka',
        'rajbari' => 'Dhaka',
        'shariatpur' => 'Dhaka',
        'tangail' => 'Dhaka',

        // Khulna (10)
        'bagerhat' => 'Khulna',
        'chuadanga' => 'Khulna',
        'jashore' => 'Khulna',
        'jashor' => 'Khulna',
        'jhenaidah' => 'Khulna',
        'khulna' => 'Khulna',
        'kushtia' => 'Khulna',
        'kustia' => 'Khulna',
        'magura' => 'Khulna',
        'meherpur' => 'Khulna',
        'narail' => 'Khulna',
        'satkhira' => 'Khulna',

        // Mymensingh (4)
        'jamalpur' => 'Mymensingh',
        'mymensingh' => 'Mymensingh',
        'netrokona' => 'Mymensingh',
        'netrakona' => 'Mymensingh',
        'sherpur' => 'Mymensingh',

        // Rajshahi (8)
        'bogura' => 'Rajshahi',
        'bogra' => 'Rajshahi',
        'chapainawabganj' => 'Rajshahi',
        'joypurhat' => 'Rajshahi',
        'naogaon' => 'Rajshahi',
        'natore' => 'Rajshahi',
        'pabna' => 'Rajshahi',
        'rajshahi' => 'Rajshahi',
        'sirajganj' => 'Rajshahi',

        // Rangpur (8)
        'dinajpur' => 'Rangpur',
        'gaibandha' => 'Rangpur',
        'kurigram' => 'Rangpur',
        'lalmonirhat' => 'Rangpur',
        'nilphamari' => 'Rangpur',
        'panchagarh' => 'Rangpur',
        'rangpur' => 'Rangpur',
        'thakurgaon' => 'Rangpur',

        // Sylhet (4)
        'habiganj' => 'Sylhet',
        'moulvibazar' => 'Sylhet',
        'sunamganj' => 'Sylhet',
        'sylhet' => 'Sylhet',
    ];

    /**
     * Extra free-text city names reported by the location drivers that are not
     * districts but are still unambiguous for division grouping.
     *
     * @var array<string, string>
     */
    private const CITY_ALIASES = [
        'dhaka' => 'Dhaka',
        'gulshan' => 'Dhaka',
        'banani' => 'Dhaka',
        'uttara' => 'Dhaka',
        'dhanmondi' => 'Dhaka',
        'mirpur' => 'Dhaka',
        'sylhet' => 'Sylhet',
        'ambarkhana' => 'Sylhet',
        'chattogram' => 'Chattogram',
        'cox bazar' => 'Chattogram',
        'bandarban' => 'Chattogram',
        'khulna' => 'Khulna',
        'rajshahi' => 'Rajshahi',
        'bogra' => 'Rajshahi',
        'rangpur' => 'Rangpur',
        'barisal' => 'Barishal',
        'barishal' => 'Barishal',
        'mymensingh' => 'Mymensingh',
    ];

    /**
     * ISP name normalisation.
     *
     * The location drivers return ISP strings in wildly inconsistent casing and
     * abbreviations. The dashboard groups by ISP to spot traffic bursts, so the
     * same operator must always collapse to one label.
     *
     * @var array<string, string>
     */
    private const ISP_CANONICAL = [
        'grameenphone' => 'Grameenphone',
        'grameen phone' => 'Grameenphone',
        'gp' => 'Grameenphone',
        'robi' => 'Robi',
        'banglalink' => 'Banglalink',
        'bangla link' => 'Banglalink',
        'bl' => 'Banglalink',
        'teletalk' => 'Teletalk',
        'tele talk' => 'Teletalk',
        'airtel' => 'Airtel',
        'amberit' => 'AmberIT',
        'amber it' => 'AmberIT',
        'dotlines' => 'Dotlines',
        'dotcom' => 'Dotlines',
        'exnet' => 'Exnet',
        'linkem' => 'Linkem',
        'carnival' => 'Carnival',
        'bravo' => 'Bravo',
        'starlink' => 'Starlink',
        'square' => 'Square Networks',
        'hetzner' => 'Hetzner',
        'cloudflare' => 'Cloudflare',
        'googlebot' => 'Googlebot',
    ];

    /**
     * Resolve a free-text location hint (city or district) into a division.
     *
     * Returns null when the hint cannot be mapped, which the caller should treat
     * as "Unknown" rather than guessing a division.
     */
    public static function divisionFor(?string $hint): ?string
    {
        $hint = self::normalise($hint);

        if ($hint === '') {
            return null;
        }

        if (isset(self::DIVISIONS[$hint])) {
            return self::DIVISIONS[$hint];
        }

        if (isset(self::DISTRICT_TO_DIVISION[$hint])) {
            return self::DISTRICT_TO_DIVISION[$hint];
        }

        if (isset(self::CITY_ALIASES[$hint])) {
            return self::CITY_ALIASES[$hint];
        }

        // Providers often append context: "Sylhet, Bangladesh", "Sylhet Division,
        // Bangladesh" or "Dhaka Division Bangladesh". normalise() has already
        // turned commas and slashes into spaces, so splitting on BOTH
        // separators is what makes multi-word referrers resolvable.
        $tokens = preg_split('/[,;\/\s]+/', $hint) ?: [];

        foreach ($tokens as $token) {
            $token = self::normalise($token);
            if ($token === '') {
                continue;
            }

            if (isset(self::DIVISIONS[$token])) {
                return self::DIVISIONS[$token];
            }
            if (isset(self::DISTRICT_TO_DIVISION[$token])) {
                return self::DISTRICT_TO_DIVISION[$token];
            }
            if (isset(self::CITY_ALIASES[$token])) {
                return self::CITY_ALIASES[$token];
            }

            // "Sylhet Division" / "Dhaka Division" collapsed into one token.
            if (str_ends_with($token, ' division')) {
                $trimmed = trim(substr($token, 0, -strlen(' division')));
                $resolved = self::DIVISIONS[$trimmed] ?? self::DISTRICT_TO_DIVISION[$trimmed] ?? null;
                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        return null;
    }

    /**
     * Collapse an ISP string from any provider onto a single canonical label.
     *
     * @param  string|null  $isp
     */
    public static function canonicalIsp($isp): ?string
    {
        if (!is_string($isp) || trim($isp) === '') {
            return null;
        }

        $raw = trim($isp);
        $key = self::normalise($raw);

        if ($key === '') {
            return null;
        }

        if (isset(self::ISP_CANONICAL[$key])) {
            return self::ISP_CANONICAL[$key];
        }

        // "Grameenphone Ltd." / "Robi Axiata Limited" — substring match after
        // normalisation catches the decorated variants.
        foreach (self::ISP_CANONICAL as $needle => $canonical) {
            if (strlen($needle) >= 4 && str_contains($key, $needle)) {
                return $canonical;
            }
        }

        return $raw;
    }

    /**
     * True when the resolved country is Bangladesh (case/spelling tolerant).
     */
    public static function isBangladesh(?string $country): bool
    {
        if (!is_string($country) || $country === '') {
            return false;
        }

        $key = self::normalise($country);

        return in_array($key, ['bangladesh', 'bd', 'bdl', 'bangladesh dhaka'], true);
    }

    /**
     * Strip accents, punctuation and collapse whitespace to a lower-case key.
     */
    private static function normalise(?string $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        // "Cox's Bazar" -> "cox's bazar" is handled by the map directly, but
        // curly apostrophes would break that lookup, so normalise them first.
        $value = str_replace(['’', '‘', '`'], "'", $value);

        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
            if (is_string($transliterated) && $transliterated !== '') {
                $value = $transliterated;
            }
        } else {
            $value = strtolower($value);
        }

        $value = strtolower($value);

        // Drop everything that is not a letter, digit, apostrophe or space.
        $value = preg_replace('/[^a-z0-9\' ]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}