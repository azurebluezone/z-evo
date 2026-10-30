<?php

namespace App\Support;

/**
 * SocialIcons
 * -----------
 * One catalog for the social icons the shop supports, so the admin screen,
 * the classic storefront and the modern (fm) storefront cannot drift apart.
 *
 * One social row stores the Font Awesome class in `social_media.icon` (the
 * storefront's historical contract). Each catalog entry additionally carries:
 *
 *   ri  the Remix Icon class the ADMIN renders (the admin loads iconify's
 *       Remix set, not Font Awesome - an <i class="fab fa-..."> is invisible
 *       there)
 *   bi  the Bootstrap Icons class the fm storefront renders
 *   fa  the class saved to the database and rendered by the classic theme
 *   color  the brand colour used by the admin preview / preset picker
 */
final class SocialIcons
{
    /**
     * @return array<int, array<string, string>>
     */
    public static function all(): array
    {
        return [
            [
                'key'   => 'facebook',
                'label' => 'Facebook',
                'fa'    => 'fab fa-facebook-f',
                'ri'    => 'ri ri-facebook-circle-line',
                'bi'    => 'bi bi-facebook',
                'color' => '#1877f2',
            ],
            [
                'key'   => 'instagram',
                'label' => 'Instagram',
                'fa'    => 'fab fa-instagram',
                'ri'    => 'ri ri-instagram-line',
                'bi'    => 'bi bi-instagram',
                'color' => '#E4405F',
            ],
            [
                'key'   => 'x-twitter',
                'label' => 'X (Twitter)',
                'fa'    => 'fab fa-x-twitter',
                'ri'    => 'ri ri-twitter-x-line',
                'bi'    => 'bi bi-twitter-x',
                'color' => '#000000',
            ],
            [
                'key'   => 'youtube',
                'label' => 'YouTube',
                'fa'    => 'fab fa-youtube',
                'ri'    => 'ri ri-youtube-line',
                'bi'    => 'bi bi-youtube',
                'color' => '#FF0000',
            ],
            [
                'key'   => 'tiktok',
                'label' => 'TikTok',
                'fa'    => 'fab fa-tiktok',
                'ri'    => 'ri ri-tiktok-line',
                'bi'    => 'bi bi-tiktok',
                'color' => '#010101',
            ],
            [
                'key'   => 'whatsapp',
                'label' => 'WhatsApp',
                'fa'    => 'fab fa-whatsapp',
                'ri'    => 'ri ri-whatsapp-line',
                'bi'    => 'bi bi-whatsapp',
                'color' => '#25D366',
            ],
            [
                'key'   => 'linkedin',
                'label' => 'LinkedIn',
                'fa'    => 'fab fa-linkedin-in',
                'ri'    => 'ri ri-linkedin-box-line',
                'bi'    => 'bi bi-linkedin',
                'color' => '#0A66C2',
            ],
            [
                'key'   => 'pinterest',
                'label' => 'Pinterest',
                'fa'    => 'fab fa-pinterest',
                'ri'    => 'ri ri-pinterest-line',
                'bi'    => 'bi bi-pinterest',
                'color' => '#BD081C',
            ],
            [
                'key'   => 'telegram',
                'label' => 'Telegram',
                'fa'    => 'fab fa-telegram',
                'ri'    => 'ri ri-telegram-line',
                'bi'    => 'bi bi-telegram',
                'color' => '#26A5E4',
            ],
            [
                'key'   => 'snapchat',
                'label' => 'Snapchat',
                'fa'    => 'fab fa-snapchat',
                'ri'    => 'ri ri-snapchat-line',
                'bi'    => 'bi bi-snapchat',
                'color' => '#FFB300',
            ],
            [
                'key'   => 'discord',
                'label' => 'Discord',
                'fa'    => 'fab fa-discord',
                'ri'    => 'ri ri-discord-line',
                'bi'    => 'bi bi-discord',
                'color' => '#5865F2',
            ],
            [
                'key'   => 'reddit',
                'label' => 'Reddit',
                'fa'    => 'fab fa-reddit-alien',
                'ri'    => 'ri ri-reddit-line',
                'bi'    => 'bi bi-reddit',
                'color' => '#FF4500',
            ],
            [
                'key'   => 'github',
                'label' => 'GitHub',
                'fa'    => 'fab fa-github',
                'ri'    => 'ri ri-github-line',
                'bi'    => 'bi bi-github',
                'color' => '#181717',
            ],
            [
                'key'   => 'google',
                'label' => 'Google',
                'fa'    => 'fab fa-google',
                'ri'    => 'ri ri-google-line',
                'bi'    => 'bi bi-google',
                'color' => '#EA4335',
            ],
            [
                'key'   => 'dribbble',
                'label' => 'Dribbble',
                'fa'    => 'fab fa-dribbble',
                'ri'    => 'ri ri-dribbble-line',
                'bi'    => 'bi bi-dribbble',
                'color' => '#EA4C89',
            ],
            [
                'key'   => 'behance',
                'label' => 'Behance',
                'fa'    => 'fab fa-behance',
                'ri'    => 'ri ri-behance-line',
                'bi'    => 'bi bi-behance',
                'color' => '#1769FF',
            ],
        ];
    }

    /** Remix Icon class for the admin UI; unknown classes get a globe. */
    public static function adminIcon(?string $faClass): string
    {
        return self::entryForFa($faClass)['ri'] ?? 'ri ri-global-line';
    }

    /** Bootstrap Icons class for the fm storefront. */
    public static function storefrontIcon(?string $faClass): string
    {
        return self::entryForFa($faClass)['bi'] ?? 'bi bi-globe2';
    }

    /** Brand colour for the admin chrome; neutral grey when unknown. */
    public static function color(?string $faClass): string
    {
        return self::entryForFa($faClass)['color'] ?? '#6b7280';
    }

    /** The catalog row whose stored Font Awesome class matches. */
    public static function entryForFa(?string $faClass): ?array
    {
        $needle = strtolower(trim((string) $faClass));

        if ($needle === '') {
            return null;
        }

        foreach (self::all() as $entry) {
            if (strtolower($entry['fa']) === $needle) {
                return $entry;
            }
        }

        // Accept the short form people type by hand ("fa-facebook-f").
        foreach (self::all() as $entry) {
            $short = strtolower(preg_replace('~^fab\s+~', '', $entry['fa']) ?? '');
            if ($short !== '' && (str_ends_with($needle, $short) || $needle === $short)) {
                return $entry;
            }
        }

        return null;
    }
}
