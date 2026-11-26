# Adora 2026 seed assets

This directory is the portable seed source. It is committed with the project,
so `php artisan migrate:fresh --seed` works on every machine the project is
sold to without the original external drive.

- `catalog.json` — 30 real product names, slugs, prices and image lists.
- `images/<slug>/` — resized demo images referenced by the catalog.

The seeder copies these files to `public/uploads/product/seed/<slug>/` every
time `SeedAssetSeeder` runs. Do not delete this directory when deploying; it is
the source of truth for the demo catalog images.

Rebuild the bundle from the original backup:

    python3 tools/import_adora_2026_assets.py
