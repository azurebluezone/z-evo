<?php

namespace App\Support;

use RuntimeException;

/**
 * Publishes the Z EVO brand bundle from `public/seed-assets/zevo/`.
 *
 * The bundle is produced by `tools/build_zevo_catalog_assets.py` from the
 * supplied brand files in `work/zevo/` and `work/Videos/`:
 *
 *   manifest.json    brand copy, colours, banner and the nine video slides
 *   logo.png         blue wordmark (used on the white storefront header)
 *   logo-white.png   white wordmark (dark surfaces, invoices)
 *   favicon.png      "Z" app icon
 *   banner.jpg       hero banner image
 *   videos/*.mp4     nine normalized slider clips
 *
 * The seeder never reads `work/` at runtime: the bundle is committed, so a sold
 * copy of the project can be seeded without the external drive.
 */
class ZevoBrandAssets
{
    /** Bundle directory relative to `public/`. */
    public const BUNDLE = 'seed-assets/zevo';

    /** Runtime upload directory for brand marks. */
    public const BRAND_UPLOAD_DIR = 'uploads/zevo';

    /** Runtime upload directory for banner media. */
    public const BANNER_UPLOAD_DIR = 'uploads/banner';

    /** Decoded `manifest.json`. */
    public static function manifest(): array
    {
        $path = self::bundlePath('manifest.json');

        if (! is_file($path)) {
            throw new RuntimeException("Z EVO brand manifest missing: {$path}");
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            throw new RuntimeException("Z EVO brand manifest is not valid JSON: {$path}");
        }

        return $data;
    }

    /**
     * Copy every file named by the manifest into the public upload tree.
     *
     * @return array{files: int, videos: int, copied: int, unchanged: int, bytes: int}
     */
    public static function publishAll(): array
    {
        $summary = ['files' => 0, 'videos' => 0, 'copied' => 0, 'unchanged' => 0, 'bytes' => 0];
        $manifest = self::manifest();

        foreach (['logo', 'logo_white', 'favicon', 'banner'] as $key) {
            $relative = (string) ($manifest[$key] ?? '');

            if ($relative === '') {
                throw new RuntimeException("Z EVO manifest is missing the '{$key}' file.");
            }

            self::copyBundleFile($relative, self::uploadPath($relative), $summary);
            $summary['files']++;
        }

        foreach (($manifest['videos'] ?? []) as $video) {
            $relative = (string) ($video['file'] ?? '');

            if ($relative === '') {
                throw new RuntimeException('Z EVO manifest has a video without a file.');
            }

            self::copyBundleFile($relative, self::uploadPath($relative), $summary);
            $summary['videos']++;
            $summary['files']++;
        }

        return $summary;
    }

    /**
     * Public upload path a bundle file should be stored at.
     *
     * `videos/name.mp4`   -> uploads/banner/videos/name.mp4
     * `banner.jpg`        -> uploads/banner/zevo-banner.jpg
     * `logo.png`          -> uploads/zevo/logo.png
     */
    public static function uploadPath(string $bundleRelative): string
    {
        $relative = ltrim($bundleRelative, '/');

        if (str_starts_with($relative, 'videos/')) {
            return self::BANNER_UPLOAD_DIR . '/videos/' . basename($relative);
        }

        if ($relative === 'banner.jpg' || str_starts_with($relative, 'banner.')) {
            return self::BANNER_UPLOAD_DIR . '/zevo-banner.jpg';
        }

        return self::BRAND_UPLOAD_DIR . '/' . basename($relative);
    }

    /** Absolute path inside the bundle. */
    public static function bundlePath(string $relative = ''): string
    {
        $relative = ltrim($relative, '/');

        return public_path(self::BUNDLE . ($relative === '' ? '' : '/' . $relative));
    }

    /**
     * @param array{copied: int, unchanged: int, bytes: int} $summary
     */
    private static function copyBundleFile(string $sourceRelative, string $targetRelative, array &$summary): void
    {
        $source = self::bundlePath($sourceRelative);

        if (! is_file($source)) {
            throw new RuntimeException("Z EVO brand asset missing: {$source}");
        }

        $target = public_path($targetRelative);
        $targetDir = dirname($target);

        if (is_file($target) && hash_file('sha256', $target) === hash_file('sha256', $source)) {
            $summary['unchanged']++;

            return;
        }

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            throw new RuntimeException("Cannot create brand upload directory: {$targetDir}");
        }

        if (! copy($source, $target)) {
            throw new RuntimeException("Cannot copy brand asset: {$source} -> {$target}");
        }

        touch($target, (int) filemtime($source));
        $summary['copied']++;
        $summary['bytes'] += (int) filesize($target);
    }
}
