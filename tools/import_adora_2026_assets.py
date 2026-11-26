#!/usr/bin/env python3
"""Build the portable Adora 2026 seed asset bundle.

The dev-first v3 seed must work on any machine the project is sold to, so the
product names and demo images are copied into the repository once:

    public/seed-assets/adora-2026/catalog.json
    public/seed-assets/adora-2026/images/<product-slug>/<nn>-<name>.jpg

At seed time App\\Support\\SeedAssets copies those files into the normal upload
directory (public/uploads/product/seed/<product-slug>/) and the catalog seeder
references the copied path. The external drive is only needed for this import.

Selection rule (deterministic): round-robin over the 12 source age categories,
lowest product id first, only products whose gallery image exists on disk, at
most N images per product (default 3). Images are resized to a bounded JPEG so
the bundle stays small enough to ship with the source code.

Usage:
    python3 tools/import_adora_2026_assets.py
    python3 tools/import_adora_2026_assets.py --limit 30 --max-images 3
"""

from __future__ import annotations

import argparse
import html
import json
import re
import sys
from collections import defaultdict
from datetime import datetime, timezone
from pathlib import Path

from PIL import Image, ImageOps

DEFAULT_SQL = Path("/media/sahon/301E3D251E3CE60E/Adora 2026/adorazstyl_sahon.sql")
DEFAULT_IMAGES = Path(
    "/media/sahon/301E3D251E3CE60E/Adora 2026/last/public/uploads/product"
)
DEFAULT_BUNDLE = Path("public/seed-assets/adora-2026")
CATEGORY_ORDER = [
    "Premature",
    "Newborn",
    "0-3 Month",
    "3-6 Month",
    "6-9 Month",
    "9-12 Month",
    "12-18 Month",
    "18-24 Month",
    "24-36 Month",
    "3-4 Years",
    "4 Years - Adults",
    "Baby Elements",
]


def insert_statements(text: str, table: str) -> list[str]:
    """Return the VALUES (...), (...); blobs of every INSERT for one table."""
    out: list[str] = []
    needle = f"INSERT INTO `{table}`"
    index = 0
    while True:
        start = text.find(needle, index)
        if start == -1:
            break
        values = text.find("VALUES", start)
        if values == -1:
            break
        i = values + len("VALUES")
        depth = 0
        quote: str | None = None
        escape = False
        while i < len(text):
            char = text[i]
            if quote:
                if escape:
                    escape = False
                elif char == "\\":
                    escape = True
                elif char == quote:
                    quote = None
            else:
                if char in ("'", '"'):
                    quote = char
                elif char == "(":
                    depth += 1
                elif char == ")":
                    depth -= 1
                elif char == ";" and depth == 0:
                    break
            i += 1
        out.append(text[values + len("VALUES"):i])
        index = i + 1
    return out


def tuples(blob: str) -> list[list[str]]:
    """Parse SQL tuple literals into a list of unquoted field strings."""
    rows: list[list[str]] = []
    i = 0
    length = len(blob)
    while i < length:
        while i < length and blob[i] != "(":
            i += 1
        if i >= length:
            break
        i += 1
        fields: list[str] = []
        current: list[str] = []
        quote: str | None = None
        escape = False
        while i < length:
            char = blob[i]
            if quote:
                if escape:
                    current.append(char)
                    escape = False
                elif char == "\\":
                    escape = True
                elif char == quote:
                    quote = None
                else:
                    current.append(char)
            else:
                if char in ("'", '"'):
                    quote = char
                elif char == ",":
                    fields.append("".join(current).strip())
                    current = []
                elif char == ")":
                    fields.append("".join(current).strip())
                    rows.append(fields)
                    i += 1
                    break
                else:
                    current.append(char)
            i += 1
    return rows


def parse_table(text: str, table: str) -> list[list[str]]:
    return [row for blob in insert_statements(text, table) for row in tuples(blob)]


def slugify(value: str, fallback: str = "product") -> str:
    value = value.strip().lower()
    value = re.sub(r"[^a-z0-9]+", "-", value)
    value = re.sub(r"-{2,}", "-", value).strip("-")
    return value or fallback


def plain_text(value: str, limit: int = 400) -> str:
    value = re.sub(r"<[^>]+>", " ", value or "")
    value = html.unescape(value)
    value = re.sub(r"\s+", " ", value).strip()
    return value[:limit]


def normalize_image(source: Path, target: Path, max_size: int, quality: int) -> tuple[int, int, int]:
    """Resize/convert one source image and return (bytes, width, height)."""
    target.parent.mkdir(parents=True, exist_ok=True)
    with Image.open(source) as image:
        image = ImageOps.exif_transpose(image)
        if image.mode in ("RGBA", "LA", "P"):
            image = image.convert("RGBA")
            background = Image.new("RGB", image.size, (255, 255, 255))
            background.paste(image, mask=image.getchannel("A"))
            image = background
        elif image.mode != "RGB":
            image = image.convert("RGB")

        image.thumbnail((max_size, max_size), Image.LANCZOS)
        image.save(target, "JPEG", quality=quality, optimize=True, progressive=True)
        return target.stat().st_size, image.width, image.height


def select_products(
    products: list[list[str]],
    image_rows: list[list[str]],
    categories: dict[str, str],
    image_root: Path,
    limit: int,
    max_images: int,
) -> list[tuple[list[str], list[str]]]:
    gallery: dict[str, list[str]] = defaultdict(list)
    for row in image_rows:
        if len(row) < 4:
            continue
        relative = row[1]
        basename = relative.split("/")[-1]
        if (image_root / basename).is_file() and basename not in gallery[row[3]]:
            gallery[row[3]].append(basename)

    grouped: dict[str, list[tuple[list[str], list[str]]]] = defaultdict(list)
    for row in products:
        if len(row) < 7:
            continue
        images = gallery.get(row[0], [])
        if not images:
            continue
        category = categories.get(row[3], row[3])
        grouped[category].append((row, images[:max_images]))

    # Keep the source order inside each category, then take one product per
    # category per round so the demo shows every age range early.
    for items in grouped.values():
        items.sort(key=lambda pair: int(pair[0][0]))

    selected: list[tuple[list[str], list[str]]] = []
    round_index = 0
    while len(selected) < limit:
        added = 0
        for category in CATEGORY_ORDER:
            if len(selected) >= limit:
                break
            items = grouped.get(category, [])
            if round_index < len(items):
                selected.append(items[round_index])
                added += 1
        if added == 0:
            break
        round_index += 1

    if len(selected) < limit:
        chosen = {pair[0][0] for pair in selected}
        for items in grouped.values():
            for pair in items:
                if len(selected) >= limit:
                    break
                if pair[0][0] not in chosen:
                    selected.append(pair)
                    chosen.add(pair[0][0])
            if len(selected) >= limit:
                break

    return selected


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--sql", type=Path, default=DEFAULT_SQL)
    parser.add_argument("--source-images", type=Path, default=DEFAULT_IMAGES)
    parser.add_argument("--bundle", type=Path, default=DEFAULT_BUNDLE)
    parser.add_argument("--limit", type=int, default=30)
    parser.add_argument("--max-images", type=int, default=3)
    parser.add_argument("--max-size", type=int, default=1400)
    parser.add_argument("--quality", type=int, default=85)
    args = parser.parse_args()

    if not args.sql.is_file():
        print(f"SQL dump not found: {args.sql}", file=sys.stderr)
        return 2
    if not args.source_images.is_dir():
        print(f"Image directory not found: {args.source_images}", file=sys.stderr)
        return 2

    raw = args.sql.read_text(encoding="utf-8", errors="replace")
    products = parse_table(raw, "products")
    image_rows = parse_table(raw, "productimages")
    category_rows = parse_table(raw, "categories")
    categories = {row[0]: row[2] for row in category_rows if len(row) > 2}

    selected = select_products(
        products,
        image_rows,
        categories,
        args.source_images,
        args.limit,
        args.max_images,
    )
    if len(selected) != args.limit:
        print(
            f"Selected {len(selected)} products, expected {args.limit}. "
            "The source backup may be incomplete.",
            file=sys.stderr,
        )
        return 3

    args.bundle.mkdir(parents=True, exist_ok=True)
    images_root = args.bundle / "images"
    images_root.mkdir(parents=True, exist_ok=True)

    catalog_products = []
    used_slugs: set[str] = set()
    used_codes: set[str] = set()
    total_bytes = 0

    for row, source_images in selected:
        source_id, name, source_slug, category_id = row[0], row[1], row[2], row[3]
        product_code = row[7] or f"AD-{source_id}"
        if product_code in used_codes:
            product_code = f"AD-{source_id}"
        used_codes.add(product_code)

        slug = slugify(source_slug or name, f"product-{source_id}")
        if slug in used_slugs:
            slug = f"{slug}-{source_id}"
        used_slugs.add(slug)

        product_dir = images_root / slug
        product_dir.mkdir(parents=True, exist_ok=True)

        catalog_images = []
        for position, basename in enumerate(source_images, start=1):
            source = args.source_images / basename
            stem = slugify(Path(basename).stem, "image")[:60]
            filename = f"{position:02d}-{stem}.jpg"
            target = product_dir / filename
            size, width, height = normalize_image(
                source, target, args.max_size, args.quality
            )
            total_bytes += size
            catalog_images.append(
                {
                    "file": filename,
                    "source": f"uploads/product/{basename}",
                    "bytes": size,
                    "width": width,
                    "height": height,
                }
            )

        catalog_products.append(
            {
                "source_id": int(source_id),
                "name": name,
                "slug": slug,
                "category": categories.get(category_id, category_id),
                "product_code": product_code,
                "purchase_price": int(row[8] or 0),
                "old_price": int(row[9] or 0),
                "new_price": int(row[10] or 0),
                "description": plain_text(row[14] if len(row) > 14 else ""),
                "images": catalog_images,
            }
        )

    catalog = {
        "version": 1,
        "bundle": "adora-2026",
        "source": str(args.sql.parent),
        "generated_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "selection": {
            "rule": "round-robin over the 12 source age categories, lowest product id first",
            "limit": args.limit,
            "max_images_per_product": args.max_images,
            "image_max_size": args.max_size,
            "image_quality": args.quality,
        },
        "products": catalog_products,
    }

    catalog_path = args.bundle / "catalog.json"
    catalog_path.write_text(
        json.dumps(catalog, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )

    readme = """# Adora 2026 seed assets

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
"""
    (args.bundle / "README.md").write_text(readme, encoding="utf-8")

    image_count = sum(len(product["images"]) for product in catalog_products)
    print(f"WROTE {catalog_path}")
    print(f"PRODUCTS {len(catalog_products)}")
    print(f"IMAGES {image_count}")
    print(f"BYTES {total_bytes}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
