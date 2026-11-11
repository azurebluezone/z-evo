<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\Template;
use Illuminate\Support\Facades\File;

/**
 * Resolves the picture shown on an admin landing-design card.
 *
 * The chain is deliberately explicit, cheapest first:
 *   1. the campaign's own hero image (editor override);
 *   2. the campaign's uploaded image_one;
 *   3. the generated <view>-hero.jpg that ships with the design;
 *   4. the image stored on Website Setup -> Appearance (MediaPath owns that
 *      lookup and its bundled default);
 *   5. public/branding/card-fallback.jpg, copied from work/zevo and tracked,
 *      so a card can never render a broken image even on a fresh checkout.
 */
class LandingCardImage
{
    public const BRAND_FALLBACK = 'branding/card-fallback.jpg';

    public static function forCampaign(?Campaign $campaign, ?Template $template = null): string
    {
        $extra = $campaign && is_array($campaign->extra_fields) ? $campaign->extra_fields : [];
        $candidates = [];

        if (!empty($extra['hero_image']) && is_scalar($extra['hero_image'])) {
            $candidates[] = (string) $extra['hero_image'];
        }

        if ($campaign && $campaign->image_one) {
            $candidates[] = (string) $campaign->image_one;
        }

        if ($template && $template->view_name) {
            $candidates[] = 'frontend/landing/pictures/generated/' . $template->view_name . '-hero.jpg';
        }

        // Appearance-controlled fallback. MediaPath returns the uploaded image
        // when it exists and the bundled placeholder otherwise.
        $candidates[] = MediaPath::fallbackPath();
        $candidates[] = self::BRAND_FALLBACK;

        foreach ($candidates as $candidate) {
            $resolved = self::resolve($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return self::BRAND_FALLBACK;
    }

    /** Turn a resolved path (or an absolute URL) into a browser URL. */
    public static function url(string $path): string
    {
        if (preg_match('~^(https?:)?//~i', $path) === 1) {
            return $path;
        }

        return asset(preg_replace('~^public/~', '', $path));
    }

    private static function resolve(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        if (preg_match('~^(https?:)?//~i', $raw) === 1) {
            return $raw;
        }

        $path = MediaPath::normalize($raw);
        $path = preg_replace('~^public/~', '', $path);
        $path = ltrim((string) $path, '/');

        if ($path === '') {
            return null;
        }

        return File::exists(public_path($path)) ? $path : null;
    }
}
