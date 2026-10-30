<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * ImageUsage
 * ----------
 * Read-only inventory of every place a Blade view renders an image.
 *
 * Why a scanner instead of a hand-maintained list: the views change often,
 * and a list that drifts silently is worse than no list. This class walks
 * resources/views on every run, so `php artisan images:audit` always answers
 * from the files that are actually on disk.
 *
 * Two kinds of rows are reported:
 *
 *   img     an <img> tag in the markup
 *   helper  a MediaPath:: call (url / product / fallbackUrl), i.e. a place
 *           that already goes through the central fallback resolver
 *
 * A row is marked "central" when the same statement resolves through
 * MediaPath, which means the admin-uploaded fallback image answers there.
 * An <img> tag without MediaPath keeps its own inline default - the audit
 * shows those so they can be converted deliberately, not blindly.
 */
final class ImageUsage
{
    /** View subtrees and the label the report uses for them. */
    private const AREAS = [
        'frontend' => 'resources/views/frontend',
        'backEnd'  => 'resources/views/backEnd',
    ];

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function scan(?string $area = null): array
    {
        $areas = $area !== null && isset(self::AREAS[$area])
            ? [$area => self::AREAS[$area]]
            : self::AREAS;

        $result = [];

        foreach ($areas as $label => $relative) {
            $root = base_path($relative);

            if (! File::isDirectory($root)) {
                $result[$label] = [];
                continue;
            }

            $rows = [];

            foreach (File::allFiles($root) as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $lines = @file($file->getPathname()) ?: [];
                $relativeFile = str_replace('\\', '/', $file->getRelativePathname());

                foreach ($lines as $index => $line) {
                    $kind = null;

                    if (preg_match('/<img\b/i', $line) === 1) {
                        $kind = 'img';
                    } elseif (preg_match('/MediaPath::(?:url|product|fallbackUrl|fallbackPath)\s*\(/', $line) === 1) {
                        $kind = 'helper';
                    }

                    if ($kind === null) {
                        continue;
                    }

                    // MediaPath may sit a line above the <img> it feeds
                    // ($src = MediaPath::url(...) then <img src="{{ $src }}">).
                    $context = implode('', array_slice($lines, max(0, $index - 2), 3));

                    $rows[] = [
                        'area'    => $label,
                        'file'    => $relativeFile,
                        'line'    => $index + 1,
                        'kind'    => $kind,
                        'central' => str_contains($context, 'MediaPath::'),
                        'code'    => trim(mb_substr(trim($line), 0, 140)),
                    ];
                }
            }

            $result[$label] = $rows;
        }

        return $result;
    }

    /**
     * Summary counts per area.
     *
     * @return array<string, array{img:int, helper:int, central:int}>
     */
    public static function counts(?string $area = null): array
    {
        $out = [];

        foreach (self::scan($area) as $label => $rows) {
            $out[$label] = [
                'img'     => count(array_filter($rows, fn ($row) => $row['kind'] === 'img')),
                'helper'  => count(array_filter($rows, fn ($row) => $row['kind'] === 'helper')),
                'central' => count(array_filter($rows, fn ($row) => $row['central'] === true)),
            ];
        }

        return $out;
    }
}
