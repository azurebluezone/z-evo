<?php

namespace App\Support;

use RuntimeException;

/**
 * Publishes the portable Adora 2026 seed asset bundle into the upload tree.
 *
 * WHY A COPY STEP AT ALL
 * ----------------------
 * Product images must live under `public/uploads/product/seed/<slug>/` because
 * every storefront/admin view resolves the normal upload path. The files cannot
 * be created from the external backup on a sold copy of the project, so the
 * bundle is committed once at `public/seed-assets/adora-2026/` and this service
 * copies it into the upload directory on every seed run.
 *
 * WHAT IS PUBLISHED
 * -----------------
 *   gallery    four images per product (`01-zevo-main` .. `04-zevo-brand-card`)
 *   variants   one image per variant (`variant-color-*`, `variant-size-*`)
 *   categories twelve real category photos, stored as `uploads/category/zevo-<slug>.jpg`
 *
 * The copy is idempotent: a file whose SHA-256 already matches is left alone,
 * so re-running `db:seed` costs nothing and never rewrites a user-modified
 * upload. A missing bundle file fails fast - a silent skip would produce a
 * store full of broken images on a fresh machine.
 */
class SeedAssets
{
    /** Bundle directory relative to `public/`. */
    public const BUNDLE = 'seed-assets/adora-2026';

    /** Runtime upload directory for the copied demo product images. */
    public const UPLOAD_DIR = 'uploads/product/seed';

    /** Runtime upload directory for the copied category images. */
    public const CATEGORY_UPLOAD_DIR = 'uploads/category';

    /**
     * Absolute path inside the bundle.
     */
    public static function bundlePath(string $relative = ''): string
    {
        $relative = ltrim($relative, '/');

        return public_path(self::BUNDLE . ($relative === '' ? '' : '/' . $relative));
    }

    /**
     * Decoded `catalog.json`.
     *
     * @return array{products: array<int, array<string, mixed>>, categories?: array<int, array<string, mixed>>}
     */
    public static function catalog(): array
    {
        $path = self::bundlePath('catalog.json');

        if (!is_file($path)) {
            throw new RuntimeException("Seed asset catalog missing: {$path}");
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (!is_array($data) || !isset($data['products']) || !is_array($data['products'])) {
            throw new RuntimeException("Seed asset catalog has no product list: {$path}");
        }

        return $data;
    }

    /**
     * Copy every catalog image into the upload tree.
     *
     * @return array{products: int, gallery: int, variants: int, categories: int, copied: int, unchanged: int, bytes: int}
     */
    public static function publishAll(): array
    {
        $summary = [
            'products' => 0,
            'gallery' => 0,
            'variants' => 0,
            'categories' => 0,
            'copied' => 0,
            'unchanged' => 0,
            'bytes' => 0,
        ];

        $catalog = self::catalog();

        foreach ($catalog['products'] as $product) {
            $slug = (string) ($product['slug'] ?? '');
            $images = $product['images'] ?? [];

            if ($slug === '' || !is_array($images) || $images === []) {
                throw new RuntimeException('Seed catalog product without slug or images.');
            }

            $summary['products']++;

            foreach ($images as $image) {
                $file = self::safeFileName((string) ($image['file'] ?? ''), $slug);

                self::copyBundleFile(
                    'images/' . $slug . '/' . $file,
                    self::UPLOAD_DIR . '/' . $slug . '/' . $file,
                    $summary
                );
                $summary['gallery']++;
            }

            $variants = $product['variants'] ?? [];

            if (!is_array($variants)) {
                throw new RuntimeException("Seed catalog variants for {$slug} are not a list.");
            }

            foreach ($variants as $variant) {
                $file = self::safeFileName((string) ($variant['image'] ?? ''), $slug);

                self::copyBundleFile(
                    'images/' . $slug . '/' . $file,
                    self::UPLOAD_DIR . '/' . $slug . '/' . $file,
                    $summary
                );
                $summary['variants']++;
            }
        }

        foreach (($catalog['categories'] ?? []) as $category) {
            $file = self::safeFileName((string) ($category['file'] ?? ''), 'category');

            self::copyBundleFile(
                'categories/' . $file,
                self::categoryUploadPath($file),
                $summary
            );
            $summary['categories']++;
        }

        return $summary;
    }

    /**
     * The upload path a product image row should store for a bundle image.
     */
    public static function relativeUploadPath(string $slug, string $file): string
    {
        return self::UPLOAD_DIR . '/' . $slug . '/' . $file;
    }

    /**
     * The upload path a category row should store for a published category image.
     *
     * The `zevo-` prefix keeps the demo categories from overwriting a real
     * shop's own `uploads/category/<slug>.jpg` upload.
     */
    public static function categoryUploadPath(string $file): string
    {
        return self::CATEGORY_UPLOAD_DIR . '/zevo-' . basename($file);
    }

    /**
     * Copy one bundle file into `public/`, skipping an already identical target.
     *
     * @param array{copied: int, unchanged: int, bytes: int} $summary
     */
    private static function copyBundleFile(string $sourceRelative, string $targetRelative, array &$summary): void
    {
        $source = self::bundlePath($sourceRelative);

        if (!is_file($source)) {
            throw new RuntimeException("Seed asset missing: {$source}");
        }

        $target = public_path($targetRelative);
        $targetDir = dirname($target);

        if (is_file($target) && hash_file('sha256', $target) === hash_file('sha256', $source)) {
            $summary['unchanged']++;

            return;
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException("Cannot create seed upload directory: {$targetDir}");
        }

        if (!copy($source, $target)) {
            throw new RuntimeException("Cannot copy seed asset: {$source} -> {$target}");
        }

        touch($target, (int) filemtime($source));
        $summary['copied']++;
        $summary['bytes'] += (int) filesize($target);
    }

    /** Reject a bundle entry that could escape its product directory. */
    private static function safeFileName(string $file, string $context): string
    {
        $file = basename($file);

        if ($file === '' || $file === '.' || $file === '..') {
            throw new RuntimeException("Seed catalog image without file name for {$context}.");
        }

        return $file;
    }
}
