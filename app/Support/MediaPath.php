<?php

namespace App\Support;

/**
 * MediaPath
 * --------
 * Single place that turns whatever is stored in a database image column into
 * a browser usable URL.
 *
 * The project stores media in three different shapes and they are all mixed
 * across tables:
 *
 *   1. "public/uploads/product/123.jpg"  - full path with the public prefix
 *   2. "uploads/product/123.jpg"          - already relative to the web root
 *   3. "https://cdn.example.com/x.jpg"    - absolute remote URL
 *
 * On top of that some legacy rows hold a bare filename ("123.jpg") which has
 * always meant "uploads/product/123.jpg" for the product table.
 *
 * Everything in the views used to open code this logic inline. Doing that in
 * more than a dozen blades guarantees drift, so it lives here instead.
 */
class MediaPath
{
    /** Directory prefixes that legacy bare filenames are resolved against. */
    private const BARE_NAME_PREFIXES = [
        ''                  => 'uploads/product/',
        'product'           => 'uploads/product/',
        'category'          => 'uploads/category/',
        'subcategory'       => 'uploads/subcategory/',
        'brand'             => 'uploads/brand/',
        'banner'            => 'uploads/banner/',
        'promo-banner'      => 'uploads/promo-banner/',
        'settings'          => 'uploads/settings/',
        'variant'           => 'uploads/variant/',
    ];

    /** Used whenever a record has no usable image at all. */
    public const FALLBACK = 'assets/images/default.png';

    /**
     * Bundled image shown when the admin has not uploaded a fallback yet.
     * A real file ships in public/assets/images/empty.png.
     */
    public const BUNDLED_FALLBACK = 'assets/images/empty.png';

    /**
     * Request-scoped memo. Resolving the settings row once per request keeps
     * a product grid with 60 images from firing 60 identical queries.
     */
    private static ?string $fallbackPath = null;

    /**
     * Image the admin uploaded on Website Setup -> Appearance
     * (`general_settings.frontend_empty_image`), falling back to the bundled
     * placeholder when the upload was never made or the file is gone.
     *
     * Single source of truth for "what shows where an image is missing" in
     * BOTH template sets and in the admin, so the appearance screen really
     * controls every empty-image slot on the site.
     */
    public static function fallbackPath(): string
    {
        if (self::$fallbackPath !== null) {
            return self::$fallbackPath;
        }

        try {
            $settings = \App\Models\GeneralSetting::query()
                ->where('status', 1)
                ->orderBy('id')
                ->first()
                ?? \App\Models\GeneralSetting::query()->orderBy('id')->first();

            $candidate = self::normalize($settings->frontend_empty_image ?? null, 'settings');

            if (
                $candidate !== ''
                && ! self::isRemote($candidate)
                && \Illuminate\Support\Facades\File::exists(public_path($candidate))
            ) {
                return self::$fallbackPath = $candidate;
            }
        } catch (\Throwable $e) {
            // Column missing / database unreachable: never block rendering.
        }

        return self::$fallbackPath = self::BUNDLED_FALLBACK;
    }

    /** Browser URL of the active fallback image. */
    public static function fallbackUrl(): string
    {
        return asset(self::fallbackPath());
    }

    /** Drop the memoised fallback (call after the appearance form saves). */
    public static function forgetFallback(): void
    {
        self::$fallbackPath = null;
    }

    /**
     * Normalise a raw database value into a web-root relative path.
     *
     * Returns an empty string when the value is not usable as a local path.
     * Remote URLs are returned untouched so the caller can detect them.
     */
    public static function normalize(?string $raw, string $barePrefix = 'product'): string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return '';
        }

        // Absolute URL - hand it back exactly as stored.
        if (preg_match('~^(https?:)?//~i', $raw) === 1) {
            return $raw;
        }

        // data: and other inline payloads are already renderable.
        if (preg_match('~^(data|blob):~i', $raw) === 1) {
            return $raw;
        }

        // Strip the legacy "public/" prefix, Laravel serves from public/.
        $path = ltrim(preg_replace('~^public/~i', '', $raw) ?? '', '/');

        if ($path === '') {
            return '';
        }

        // A bare filename with no directory has to be qualified.
        if (!str_contains($path, '/')) {
            $key = strtolower(trim($barePrefix, '/'));
            return (self::BARE_NAME_PREFIXES[$key] ?? self::BARE_NAME_PREFIXES['']) . $path;
        }

        return $path;
    }

    /**
     * Build a fully qualified URL for a raw database value.
     *
     * @param string|null $raw         value straight from the database column
     * @param string      $barePrefix  which folder a bare filename belongs to
     * @param string      $fallback    override the default placeholder
     */
    public static function url(?string $raw, string $barePrefix = 'product', ?string $fallback = null): string
    {
        $path = self::normalize($raw, $barePrefix);

        if ($path === '') {
            return asset($fallback ?? self::fallbackPath());
        }

        // Remote URLs and inline payloads pass straight through.
        if (preg_match('~^((https?:)?//|data:|blob:)~i', $path) === 1) {
            return $path;
        }

        // A local file that does not exist is a broken <img>; the uploaded
        // fallback answers instead, matching the missing-value case above.
        if (! $fallback && ! \Illuminate\Support\Facades\File::exists(public_path($path))) {
            return self::fallbackUrl();
        }

        return asset($path);
    }

    /**
     * Resolve the best available image for a product like object.
     *
     * Checks the main image, then any gallery entry, so a product whose
     * primary image row is empty still renders something sensible.
     */
    public static function product($product, ?string $fallback = null): string
    {
        if ($product === null) {
            return $fallback ? asset($fallback) : self::fallbackUrl();
        }

        $raw = $product->image?->image ?? null;

        if (blank($raw)) {
            $raw = $product->images?->first()?->image ?? null;
        }

        if (blank($raw) && isset($product->thumbnail_image)) {
            $raw = $product->thumbnail_image;
        }

        return self::url($raw, 'product', $fallback);
    }

    /**
     * Whether the stored value points at an external host.
     */
    public static function isRemote(?string $raw): bool
    {
        return preg_match('~^(https?:)?//~i', trim((string) $raw)) === 1;
    }
}
