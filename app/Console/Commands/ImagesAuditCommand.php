<?php

namespace App\Console\Commands;

use App\Support\ImageUsage;
use App\Support\MediaPath;
use Illuminate\Console\Command;

/**
 * `php artisan images:audit`
 *
 * Prints every place a Blade view renders an image, grouped by template set,
 * together with the fallback image that answers when the stored path is
 * missing. The fallback is the image uploaded on
 * Website Setup -> Appearance (frontend_empty_image), or the bundled
 * placeholder when nothing was uploaded.
 *
 * Options:
 *   --area=frontend|backEnd  limit the report to one template set
 *   --json                   machine-readable output (file:line preserved)
 *   --limit=N                cap the rows printed per area (0 = all)
 */
class ImagesAuditCommand extends Command
{
    protected $signature = 'images:audit
        {--area=all : frontend, backEnd or all}
        {--json : print JSON instead of tables}
        {--limit=0 : maximum rows per area (0 = all)}';

    protected $description = 'List every image usage point in the Blade views and the fallback image it resolves to';

    public function handle(): int
    {
        $area = (string) $this->option('area');
        $area = $area === 'all' ? null : $area;

        if ($area !== null && ! in_array($area, ['frontend', 'backEnd'], true)) {
            $this->error('Unknown area. Use frontend, backEnd or all.');
            return self::FAILURE;
        }

        $fallbackPath = MediaPath::fallbackPath();
        $fallbackUrl = MediaPath::fallbackUrl();
        $uploaded = $fallbackPath !== MediaPath::BUNDLED_FALLBACK;

        $scan = ImageUsage::scan($area);
        $counts = ImageUsage::counts($area);
        $limit = max(0, (int) $this->option('limit'));

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'fallback' => [
                    'path'     => $fallbackPath,
                    'url'      => $fallbackUrl,
                    'uploaded' => $uploaded,
                ],
                'counts' => $counts,
                'places' => $scan,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Image fallback');
        $this->line('  source: ' . ($uploaded ? 'uploaded on Appearance' : 'bundled placeholder (nothing uploaded yet)'));
        $this->line('  path:   ' . $fallbackPath);
        $this->line('  url:    ' . $fallbackUrl);

        $this->newLine();
        $this->info('Usage counts');
        foreach ($counts as $label => $row) {
            $this->line(sprintf(
                '  %-9s <img>=%d  MediaPath=%d  central-fallback=%d',
                $label,
                $row['img'],
                $row['helper'],
                $row['central']
            ));
        }

        foreach ($scan as $label => $rows) {
            $this->newLine();
            $this->info(strtoupper($label) . ' — ' . count($rows) . ' place(s)');

            if ($rows === []) {
                $this->line('  none');
                continue;
            }

            $shown = $limit > 0 ? array_slice($rows, 0, $limit) : $rows;

            $this->table(
                ['Kind', 'Fallback', 'Location', 'Code'],
                array_map(fn ($row) => [
                    $row['kind'],
                    $row['central'] ? 'central' : 'inline',
                    $row['file'] . ':' . $row['line'],
                    $row['code'],
                ], $shown)
            );

            if ($limit > 0 && count($rows) > $limit) {
                $this->line('  … ' . (count($rows) - $limit) . ' more row(s); raise --limit to see them');
            }
        }

        $this->newLine();
        $this->line('Report only: nothing was changed.');
        return self::SUCCESS;
    }
}
