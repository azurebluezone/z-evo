<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NormalizeMediaPathsCommand extends Command
{
    protected $signature = 'media:normalize-paths {--backup-only : Only create a backup and do not update DB}';

    protected $description = 'Back up and normalize media paths stored in DB (strip public/public, public/, leading slash)';

    public function handle(): int
    {
        $timestamp = now()->format('Ymd_His');
        $backupDir = storage_path('app/media-backups');
        File::ensureDirectoryExists($backupDir);

        $backup = [
            'timestamp' => $timestamp,
            'tables' => [],
        ];

        $tables = [
            'general_settings' => ['white_logo', 'dark_logo', 'favicon'],
            'banners' => ['image'],
            'categories' => ['image'],
            'brands' => ['image'],
            'productimages' => ['image'],
            'homepage_collections' => ['banner_image'],
        ];

        foreach ($tables as $table => $columns) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->warn("Skipping missing table: {$table}");
                continue;
            }

            $existingColumns = [];
            foreach ($columns as $col) {
                if (DB::getSchemaBuilder()->hasColumn($table, $col)) {
                    $existingColumns[] = $col;
                }
            }

            if (empty($existingColumns)) {
                $this->warn("Skipping table with no matching columns: {$table}");
                continue;
            }

            $rows = DB::table($table)->select(array_merge(['id'], $existingColumns))->get();
            $backup['tables'][$table] = $rows;
        }

        $backupPath = $backupDir . DIRECTORY_SEPARATOR . "media_paths_backup_{$timestamp}.json";
        File::put($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->info("Backup written: {$backupPath}");

        if ((bool)$this->option('backup-only')) {
            $this->info('Backup-only mode: no DB updates performed.');
            return self::SUCCESS;
        }

        $updatedTotal = 0;

        foreach ($tables as $table => $columns) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            foreach ($columns as $col) {
                if (!DB::getSchemaBuilder()->hasColumn($table, $col)) {
                    continue;
                }

                $rows = DB::table($table)->select('id', $col)->whereNotNull($col)->get();

                foreach ($rows as $row) {
                    $old = (string) $row->{$col};
                    $new = $this->normalizePath($old);

                    if ($new !== $old) {
                        DB::table($table)->where('id', $row->id)->update([$col => $new]);
                        $updatedTotal++;
                    }
                }
            }
        }

        $this->info("Normalized paths updated: {$updatedTotal}");
        return self::SUCCESS;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return $path;
        }

        $path = str_replace('\\', '/', $path);

        // strip absolute URL to just path
        $path = preg_replace('~^https?://[^/]+/~i', '', $path);

        // strip leading slash
        $path = ltrim($path, '/');

        // normalize double public segments
        $path = preg_replace('~^public/public/~i', 'public/', $path);

        // strip public/ for web-facing asset() usage
        $path = preg_replace('~^public/~i', '', $path);
        $path = ltrim($path, '/');

        // collapse duplicate slashes
        $path = preg_replace('~/+~', '/', $path);

        return $path;
    }
}
