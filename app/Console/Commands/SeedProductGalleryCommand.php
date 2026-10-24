<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Productimage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Give every published product a real gallery of at least three photos.
 *
 * WHY THIS EXISTS
 * -------------------------------------------------------------------------
 * The seeded catalogue shipped one photo per product (69 of 76), so the
 * detail page never rendered its thumbnail strip and a shopper had no second
 * angle to look at. Ten products pointed at uploads/category/default.png -
 * the "IMAGE NOT AVAILABLE / Developer Zone" graphic - which made the home
 * rail and the related-products rail look broken.
 *
 * WHAT IT DOES
 * -------------------------------------------------------------------------
 *   1. Products whose main image is a placeholder get a real demo photo from
 *      the map below (only where the file exists; nothing is downloaded).
 *   2. Two derived shots are generated from the main photo with GD:
 *        02  a 4:5 portrait crop (a different framing, not a filter)
 *        03  a 55% square close-up
 *      They are crops of the SAME product photo, never another product's
 *      picture, so a gallery cannot misrepresent what is being sold.
 *   3. productimages rows are added for the new files. Existing rows are kept
 *      and the command is idempotent: a second run writes nothing.
 *
 * Files land in public/uploads/product/gallery/, never in public/uploads/product/
 * itself, so the admin's own uploads stay distinguishable from generated ones.
 *
 * Demo photo provenance (licences) is recorded in docs/DEMO-IMAGES.md.
 */
class SeedProductGalleryCommand extends Command
{
    protected $signature = 'products:gallery
                            {--dry-run : Report what would change without writing files or rows}
                            {--force : Regenerate the files that already exist (rows are kept)}
                            {--min=3 : Minimum number of photos per published product}';

    protected $description = 'Ensure every published product has at least N gallery photos (generates crops, fills placeholder images from demo sources).';

    /**
     * Placeholder main image => real demo photo, per product id.
     *
     * Only products whose current main image is a placeholder are touched, so
     * an admin upload always wins over this map.
     */
    private const DEMO_MAIN = [
        // [source path, crop top fraction, crop bottom fraction]
        // Canvas CWC-201: the store's own product mockup (same code on the box).
        1  => ['uploads/landing-pages/1770593897-cwc-201-mockupjpg.webp', 0.0, 0.0],
        // Navy hooded sweatshirt on white (Wikimedia Commons via Openverse,
        // CC0). The old source was the shop's "Subscription Bd" logo banner.
        2  => ['uploads/product/demo/winter-hoodie-navy.jpg', 0.0, 0.0],
        // Wooden regulator wall clock, white background.
        3  => ['uploads/product/demo/clock-regulator.webp', 0.0, 0.0],
        // White gift box with ribbon.
        4  => ['uploads/product/demo/gift-box-white.jpg', 0.0, 0.0],
        // Blue khadi kurta (panjabi), photographed on a hanger.
        5  => ['uploads/product/demo/mens-kurta-blue.jpg', 0.0, 0.0],
        // Black abaya, flat-lay on grey (Wikimedia Commons, CC0). The previous
        // source was a marketing composition whose "Flore Touch Zilbab" banner
        // and "Premium Quality" callout were baked into the pixels.
        6  => ['uploads/product/demo/womens-abaya-black.jpg', 0.0, 0.0],
        // Second canvas product: same category mockup, different framing.
        7  => ['uploads/landing-pages/1770593897-cwc-201-mockupjpg.webp', 0.0, 0.0],
        // Pendulum wall clock with a dog design, white background.
        8  => ['uploads/product/demo/clock-pendulum-dog.jpg', 0.0, 0.0],
        // Pink t-shirt on a hanger. The bottom ~10% carries a garment-shop
        // watermark, so it is cropped off.
        9  => ['uploads/campaign/1707538410-campaign.webp', 0.0, 0.10],
        // Two matching knit shirts = "Couple Pack". The "Organic Knit cotton"
        // headline sits above the garments, so it is cropped off the top.
        10 => ['uploads/campaign/1757101863-copy-of-copy-of-50%25_20250904_230951_0000.webp', 0.34, 0.06],
    ];

    /** Width x height of the two generated shots. */
    private const SHOTS = [
        ['suffix' => '02', 'title' => 'Gallery 2', 'width' => 800, 'height' => 1000, 'crop' => 1.00],
        ['suffix' => '03', 'title' => 'Gallery 3', 'width' => 800, 'height' => 800,  'crop' => 0.55],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $min = max(1, (int) $this->option('min'));

        if (! function_exists('imagecreatetruecolor')) {
            $this->error('GD extension is not available; cannot generate gallery shots.');

            return self::FAILURE;
        }

        $galleryDir = public_path('uploads/product/gallery');
        if (! $dry && ! File::isDirectory($galleryDir)) {
            File::makeDirectory($galleryDir, 0775, true);
        }

        $products = Product::where('status', 1)->orderBy('id')->get(['id', 'name']);
        $stats = ['products' => 0, 'main_replaced' => 0, 'files' => 0, 'rows' => 0, 'skipped' => 0];

        foreach ($products as $product) {
            $stats['products']++;
            $id = (int) $product->id;

            $mainRow = Productimage::where('product_id', $id)->orderBy('id')->first();
            $mainPath = $mainRow?->image;

            // 1. Replace a placeholder main image with the demo photo.
            $sourcePath = public_path((string) $mainPath);
            $demoEntry = self::DEMO_MAIN[$id] ?? null;
            $generatedMain = 'uploads/product/gallery/' . $id . '-01.jpg';
            $isPlaceholder = $mainPath === null || str_contains($mainPath, 'default');

            // A main image this command generated itself is also re-derivable:
            // --force has to be able to fix a crop rule for products that were
            // already converted on an earlier run. An admin upload (any other
            // path) is never overwritten.
            if ($isPlaceholder || ($demoEntry !== null && $mainPath === $generatedMain && $force)) {
                [$demo, $cropTop, $cropBottom] = $demoEntry ?? [null, 0.0, 0.0];

                if ($demo === null || ! is_file(public_path($demo))) {
                    $this->line(sprintf('  ! P%-3d no demo source, skipped: %s', $id, $product->name));
                    $stats['skipped']++;

                    continue;
                }

                $target = 'uploads/product/gallery/' . $id . '-01.jpg';
                $absolute = public_path($target);

                if (! $dry && ($force || ! is_file($absolute))) {
                    $this->writeJpeg(public_path($demo), $absolute, 900, 900, 1.0, $cropTop, $cropBottom);
                    $stats['files']++;
                }

                if ($mainRow) {
                    if (! $dry) {
                        $mainRow->image = $target;
                        $mainRow->title = $mainRow->title ?: 'Main';
                        $mainRow->save();
                    }
                } else {
                    if (! $dry) {
                        Productimage::create(['product_id' => $id, 'image' => $target, 'title' => 'Main']);
                        $stats['rows']++;
                    }
                }

                $mainPath = $target;
                $sourcePath = $dry ? public_path($demo) : $absolute;
                $stats['main_replaced']++;
            }

            $source = $sourcePath;
            if (! is_file($source)) {
                $this->line(sprintf('  ! P%-3d main file missing (%s), skipped', $id, $mainPath));
                $stats['skipped']++;

                continue;
            }

            // 2. Make sure the product has at least $min photos.
            $existing = Productimage::where('product_id', $id)->count();

            foreach (self::SHOTS as $shot) {
                $target = 'uploads/product/gallery/' . $id . '-' . $shot['suffix'] . '.jpg';
                $absolute = public_path($target);
                $already = Productimage::where('product_id', $id)->where('image', $target)->exists();
                $fileExists = is_file($absolute);

                if ($already && $fileExists && ! $force) {
                    continue; // row and file both present
                }

                if (! $already && $existing >= $min) {
                    // --force only rewrites files that belong to an existing
                    // row. A product that already has $min photos of its own
                    // must not gain extra rows just because --force was passed.
                    continue;
                }

                if (! $dry && ($force || ! $fileExists)) {
                    // Derive from the product's own main photo (the trimmed
                    // demo shot after a placeholder replacement), never from
                    // another product's picture.
                    $this->writeJpeg($source, $absolute, $shot['width'], $shot['height'], $shot['crop'], 0.0, 0.0);
                    $stats['files']++;
                }

                if (! $already) {
                    if (! $dry) {
                        Productimage::create([
                            'product_id' => $id,
                            'image' => $target,
                            'title' => $shot['title'],
                        ]);
                    }
                    $stats['rows']++;
                    $existing++;
                }
            }
        }

        $this->info(sprintf(
            '%s products=%d main_replaced=%d files_written=%d rows_inserted=%d skipped=%d',
            $dry ? '[dry-run]' : '[done]',
            $stats['products'],
            $stats['main_replaced'],
            $stats['files'],
            $stats['rows'],
            $stats['skipped']
        ));

        return self::SUCCESS;
    }

    /**
     * Crop and resize $from into a JPEG at $to.
     *
     * $aspectCrop 1.0 means "cover the whole frame" (used when a demo photo is
     * already the right shape); anything smaller keeps the centre portion.
     * $cropBottom drops that fraction of the height first, for source images
     * that carry a promo banner under the product.
     */
    private function writeJpeg(string $from, string $to, int $outW, int $outH, float $aspectCrop, float $cropTop, float $cropBottom): void
    {
        $image = @imagecreatefromstring((string) file_get_contents($from));
        if (! $image) {
            return;
        }

        $w = imagesx($image);
        $h = imagesy($image);

        if ($cropTop > 0 || $cropBottom > 0) {
            $offsetY = (int) round($h * $cropTop);
            $keptH = max(1, (int) round($h * (1 - $cropTop - $cropBottom)));
            $trimmed = imagecrop($image, ['x' => 0, 'y' => $offsetY, 'width' => $w, 'height' => $keptH]);
            if ($trimmed !== false) {
                imagedestroy($image);
                $image = $trimmed;
                $w = imagesx($image);
                $h = imagesy($image);
            }
        }

        // Centre crop to the output aspect ratio, then scale down.
        $targetRatio = $outW / $outH;
        $cropW = $w;
        $cropH = $h;
        if (($w / $h) > $targetRatio) {
            $cropW = (int) round($h * $targetRatio);
        } else {
            $cropH = (int) round($w / $targetRatio);
        }

        // $aspectCrop shrinks the crop further (a close-up) while keeping ratio.
        $cropW = max(1, (int) round($cropW * $aspectCrop));
        $cropH = max(1, (int) round($cropH * $aspectCrop));

        $srcX = (int) max(0, ($w - $cropW) / 2);
        $srcY = (int) max(0, ($h - $cropH) / 2);

        $canvas = imagecreatetruecolor($outW, $outH);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $image, 0, 0, $srcX, $srcY, $outW, $outH, $cropW, $cropH);

        if (! File::isDirectory(dirname($to))) {
            File::makeDirectory(dirname($to), 0775, true);
        }

        imagejpeg($canvas, $to, 88);
        imagedestroy($canvas);
        imagedestroy($image);
    }
}
