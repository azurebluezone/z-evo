#!/usr/bin/env python3
"""
Build the Z EVO storefront asset bundle from the real Adora source photos.

1. Category art: 12 real Adora category photos -> categories/<slug>.jpg.
2. Gallery art: four visibly different images per product, all derived from the
   product's own real photo (main, detail, brand frame, image-brand card).
3. Variant art: three variants per product. Colour products get Original,
   Sky Blue and Blush Pink hue recolours; size products get three age-appropriate
   sizes with different crops and a visible size chip.
4. Brand bundle: logo, white logo, favicon, banner and all work/Videos clips.

Run from the project root:

    python3 tools/build_zevo_catalog_assets.py

Deterministic and idempotent. The output bundle is committed, so the seeders
never depend on the external Adora drive.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import shutil
import subprocess
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageOps

ROOT = Path(__file__).resolve().parents[1]
DEFAULT_BUNDLE = ROOT / "public/seed-assets/adora-2026"
DEFAULT_CATALOG = DEFAULT_BUNDLE / "catalog.json"
DEFAULT_IMAGES = DEFAULT_BUNDLE / "images"
DEFAULT_CATEGORY_SOURCE = Path(
    "/media/sahon/301E3D251E3CE60E/Adora 2026/last/public/uploads/category"
)
DEFAULT_LOGO = ROOT / "work/zevo/logo.png"
DEFAULT_BANNER = ROOT / "work/zevo/BANNER SLIDER.jpeg"
DEFAULT_VIDEOS = ROOT / "work/Videos"
DEFAULT_PIPELINE = Path("/home/sahon/.config/opencode/mcp/image-brand/pipeline.py")
ZEVO_BUNDLE = ROOT / "public/seed-assets/zevo"

GALLERY_SIZE = 1200
CATEGORY_SIZE = 900
BRAND_PRIMARY = (0, 60, 158)
BRAND_ACCENT = (0, 163, 224)

CATEGORY_SOURCES = {
    "Premature": "1783531102-img-20260708-wa0016.webp",
    "Newborn": "1783531253-img-20260708-wa0019.webp",
    "0-3 Month": "1783531270-img-20260708-wa0018.webp",
    "3-6 Month": "1783531225-img-20260708-wa0020.webp",
    "6-9 Month": "1783531137-img-20260708-wa0017.webp",
    "9-12 Month": "1783499245-img-20260708-wa0005.webp",
    "12-18 Month": "1783499227-img-20260708-wa0001.webp",
    "18-24 Month": "1783497539-img-20260708-wa0000.webp",
    "24-36 Month": "1783499205-img-20260708-wa0004.webp",
    "3-4 Years": "1783499168-img-20260708-wa0002.webp",
    "4 Years - Adults": "1783499077-1783498915768.webp",
    "Baby Elements": "1783115976-whatsapp-image-2026-07-04-at-3.59.21-am.webp",
}

SIZE_SETS = {
    "Premature": ["Preemie", "0-1M", "1-3M"],
    "Newborn": ["Newborn", "0-1M", "1-3M"],
    "0-3 Month": ["0-1M", "1-2M", "2-3M"],
    "3-6 Month": ["3M", "4-5M", "6M"],
    "6-9 Month": ["6M", "7-8M", "9M"],
    "9-12 Month": ["9M", "10-11M", "12M"],
    "12-18 Month": ["12M", "15M", "18M"],
    "18-24 Month": ["18M", "21M", "24M"],
    "24-36 Month": ["2Y", "2.5Y", "3Y"],
    "3-4 Years": ["3Y", "3.5Y", "4Y"],
    "4 Years - Adults": ["4-5Y", "6-7Y", "8-10Y"],
    "Baby Elements": ["Small", "Medium", "Large"],
}

COLOR_VARIANTS = [
    ("Original", None),
    ("Sky Blue", "#4a90d9"),
    ("Blush Pink", "#e08aa0"),
]

SIZE_DELTAS = [0, 40, 80]
COLOR_DELTAS = [0, 20, 40]

VIDEO_TITLES = [
    "Z EVO - New Arrivals",
    "Z EVO - Everyday Comfort",
    "Z EVO - Little Moments",
    "Z EVO - Soft Cotton",
    "Z EVO - Play All Day",
    "Z EVO - Export Quality",
    "Z EVO - Sleep Tight",
    "Z EVO - Brand Story",
    "Z EVO - Shop the Look",
]


def slugify(value: str) -> str:
    value = value.lower().strip()
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return value.strip("-")


def latin_font(size: int, bold: bool = True) -> ImageFont.FreeTypeFont:
    candidates = [
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold
        else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    ]
    for candidate in candidates:
        if Path(candidate).is_file():
            return ImageFont.truetype(candidate, size)
    return ImageFont.load_default()


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def cover_square(image: Image.Image, size: int) -> Image.Image:
    return cover_size(image, size, size)


def cover_size(image: Image.Image, width: int, height: int) -> Image.Image:
    image = image.convert("RGB")
    scale = max(width / image.width, height / image.height)
    resized = image.resize(
        (max(1, round(image.width * scale)), max(1, round(image.height * scale))),
        Image.Resampling.LANCZOS,
    )
    left = (resized.width - width) // 2
    top = (resized.height - height) // 2
    return resized.crop((left, top, left + width, top + height))


def contain_on_canvas(image: Image.Image, size: int, background=(246, 249, 253)) -> Image.Image:
    canvas = Image.new("RGB", (size, size), background)
    inner = size - 120
    scale = min(inner / image.width, inner / image.height)
    resized = image.resize(
        (max(1, round(image.width * scale)), max(1, round(image.height * scale))),
        Image.Resampling.LANCZOS,
    )
    canvas.paste(resized, ((size - resized.width) // 2, (size - resized.height) // 2))
    return canvas


def gradient_canvas(size: int, top=(236, 244, 255), bottom=(214, 231, 250)) -> Image.Image:
    canvas = Image.new("RGB", (size, size), top)
    draw = ImageDraw.Draw(canvas)
    for y in range(size):
        ratio = y / max(1, size - 1)
        color = tuple(round(top[i] + (bottom[i] - top[i]) * ratio) for i in range(3))
        draw.line((0, y, size, y), fill=color)
    return canvas


def add_badge(image: Image.Image, logo_path: Path) -> Image.Image:
    width, height = image.size
    scale = width / GALLERY_SIZE
    badge_w = round(340 * scale)
    badge_h = round(240 * scale)
    pad = round(14 * scale)
    x0 = width - badge_w - pad
    y0 = height - badge_h - pad

    overlay = Image.new("RGBA", image.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(overlay)
    radius = max(6, round(26 * scale))

    shadow = (
        x0 + round(5 * scale), y0 + round(6 * scale),
        x0 + badge_w + round(5 * scale), y0 + badge_h + round(6 * scale),
    )
    draw.rounded_rectangle(shadow, radius=radius, fill=(0, 0, 0, 34))
    draw.rounded_rectangle(
        (x0, y0, x0 + badge_w, y0 + badge_h),
        radius=radius,
        fill=(255, 255, 255, 255),
        outline=(*BRAND_PRIMARY, 55),
        width=max(1, round(2 * scale)),
    )

    logo = Image.open(logo_path).convert("RGBA")
    logo.thumbnail((badge_w - round(40 * scale), round(140 * scale)), Image.Resampling.LANCZOS)
    overlay.alpha_composite(
        logo,
        (x0 + (badge_w - logo.width) // 2, y0 + (badge_h - logo.height) // 2 - round(10 * scale)),
    )
    draw.text(
        (x0 + badge_w // 2, y0 + badge_h - round(22 * scale)),
        "EVERYDAY ESSENTIALS",
        font=latin_font(max(9, round(13 * scale))),
        fill=(*BRAND_PRIMARY, 255),
        anchor="mm",
    )
    return Image.alpha_composite(image.convert("RGBA"), overlay).convert("RGB")


def add_chip(image: Image.Image, text: str, accent=BRAND_PRIMARY) -> Image.Image:
    width, height = image.size
    scale = width / GALLERY_SIZE
    label = text.strip()
    font = latin_font(max(14, round(34 * scale)))
    overlay = Image.new("RGBA", image.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(overlay)
    probe = ImageDraw.Draw(Image.new("RGB", (8, 8)))
    box = probe.textbbox((0, 0), label, font=font)
    chip_w = max(round(170 * scale), (box[2] - box[0]) + round(70 * scale))
    chip_h = round(78 * scale)
    x0 = round(26 * scale)
    y0 = height - chip_h - round(24 * scale)
    draw.rounded_rectangle(
        (x0, y0, x0 + chip_w, y0 + chip_h),
        radius=round(chip_h / 2),
        fill=(*accent, 238),
    )
    draw.text(
        (x0 + chip_w / 2, y0 + chip_h / 2),
        label,
        font=font,
        fill=(255, 255, 255, 255),
        anchor="mm",
    )
    return Image.alpha_composite(image.convert("RGBA"), overlay).convert("RGB")


def saturation_mask(source: Image.Image):
    probe = source.convert("HSV").resize((200, 200), Image.Resampling.BILINEAR)
    raw = np.asarray(probe).astype(np.float32)
    hue = raw[..., 0] * 360.0 / 255.0
    saturation = raw[..., 1] / 255.0
    value = raw[..., 2] / 255.0
    mask = (saturation > 0.28) & (value > 0.08) & (value < 0.95)
    return hue, saturation, value, mask


def garment_ratio(source: Image.Image) -> float:
    width, height = source.size
    box = (int(width * 0.2), int(height * 0.2), int(width * 0.8), int(height * 0.85))
    crop = source.crop(box).resize((160, 160), Image.Resampling.BILINEAR)
    _, _, _, mask = saturation_mask(crop)
    return float(mask.mean())


def dominant_hue(source: Image.Image) -> float:
    hue, _, _, mask = saturation_mask(source)
    if not mask.any():
        return 210.0
    angles = np.deg2rad(hue[mask])
    mean = np.rad2deg(np.arctan2(np.sin(angles).mean(), np.cos(angles).mean()))
    return float(mean % 360.0)


def dominant_hex(source: Image.Image) -> str:
    probe = source.convert("RGB").resize((200, 200), Image.Resampling.BILINEAR)
    raw = np.asarray(probe).astype(np.float32)
    _, _, _, mask = saturation_mask(probe)
    if not mask.any():
        return "#9aa4b2"
    mean = raw[mask].mean(axis=0).round().astype(int)
    return "#{:02x}{:02x}{:02x}".format(*mean)


def hex_to_hue(value: str) -> float:
    value = value.lstrip("#")
    r, g, b = (int(value[i:i + 2], 16) / 255.0 for i in (0, 2, 4))
    high, low = max(r, g, b), min(r, g, b)
    delta = high - low
    if delta == 0:
        return 0.0
    if high == r:
        hue = ((g - b) / delta) % 6
    elif high == g:
        hue = (b - r) / delta + 2
    else:
        hue = (r - g) / delta + 4
    return (hue * 60.0) % 360.0


def recolor_garment(source: Image.Image, target_hex: str) -> Image.Image:
    rgb = np.asarray(source.convert("RGB")).astype(np.float32) / 255.0
    hsv = np.asarray(source.convert("HSV")).astype(np.float32)
    hue = hsv[..., 0] * 360.0 / 255.0
    saturation = hsv[..., 1] / 255.0
    value = hsv[..., 2] / 255.0

    delta = (hex_to_hue(target_hex) - dominant_hue(source) + 540.0) % 360.0 - 180.0
    soft = np.clip((saturation - 0.10) / 0.20, 0.0, 1.0)
    soft *= np.clip((value - 0.04) / 0.12, 0.0, 1.0)

    new_hue = (hue + delta * soft) % 360.0
    new_sat = np.clip(saturation * (1.0 + 0.06 * soft), 0.0, 1.0)
    shifted = np.stack(
        [new_hue / 360.0 * 255.0, new_sat * 255.0, value * 255.0], axis=2
    ).astype(np.uint8)
    shifted_rgb = np.asarray(Image.fromarray(shifted, "HSV").convert("RGB")).astype(np.float32) / 255.0
    blend = soft[..., None]
    mixed = shifted_rgb * blend + rgb * (1.0 - blend)
    return Image.fromarray((np.clip(mixed, 0, 1) * 255).round().astype(np.uint8), "RGB")


def save_jpeg(image: Image.Image, path: Path, quality: int = 90) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    image.convert("RGB").save(path, "JPEG", quality=quality, subsampling=1, optimize=True)


def brand_frame(source: Image.Image, logo_path: Path, caption: str) -> Image.Image:
    canvas = gradient_canvas(GALLERY_SIZE)
    photo_w = round(GALLERY_SIZE * 0.74)
    photo = cover_square(source, photo_w)
    canvas.paste(photo, ((GALLERY_SIZE - photo_w) // 2, round(GALLERY_SIZE * 0.10)))

    logo = Image.open(logo_path).convert("RGBA")
    logo.thumbnail((round(GALLERY_SIZE * 0.30), round(GALLERY_SIZE * 0.10)), Image.Resampling.LANCZOS)
    canvas.paste(logo, (round(GALLERY_SIZE * 0.05), round(GALLERY_SIZE * 0.04)), logo)

    draw = ImageDraw.Draw(canvas)
    draw.text(
        (GALLERY_SIZE // 2, round(GALLERY_SIZE * 0.93)),
        caption.upper(),
        font=latin_font(round(GALLERY_SIZE * 0.030)),
        fill=BRAND_PRIMARY,
        anchor="mm",
    )
    draw.line(
        (
            round(GALLERY_SIZE * 0.30), round(GALLERY_SIZE * 0.895),
            round(GALLERY_SIZE * 0.70), round(GALLERY_SIZE * 0.895),
        ),
        fill=BRAND_ACCENT,
        width=max(2, round(GALLERY_SIZE * 0.004)),
    )
    return canvas


def run_image_brand(spec: dict, pipeline: Path, spec_dir: Path, name: str) -> None:
    if not pipeline.is_file():
        raise SystemExit(f"image-brand pipeline not found: {pipeline}")
    spec_path = spec_dir / f".{name}-brand-spec.json"
    spec_path.write_text(json.dumps(spec, ensure_ascii=False, indent=1), encoding="utf-8")
    result = subprocess.run(
        [sys.executable, str(pipeline), "--spec", str(spec_path)],
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        raise SystemExit(f"image-brand failed for {name}:\n{result.stdout}\n{result.stderr}")


def build_categories(category_source: Path, bundle: Path):
    target_dir = bundle / "categories"
    target_dir.mkdir(parents=True, exist_ok=True)
    rows = []

    for name, file_name in CATEGORY_SOURCES.items():
        source = category_source / file_name
        if not source.is_file():
            raise SystemExit(f"category source missing: {source}")
        slug = slugify(name)
        out_name = f"{slug}.jpg"
        image = ImageOps.exif_transpose(Image.open(source)).convert("RGB")
        image = contain_on_canvas(image, CATEGORY_SIZE, background=(255, 255, 255))
        save_jpeg(image, target_dir / out_name, quality=88)
        rows.append({"name": name, "slug": slug, "file": out_name, "source": file_name})

    return rows


def build_products(catalog: dict, images_dir: Path, logo_path: Path, pipeline: Path):
    color_products = 0
    size_products = 0

    for product in catalog["products"]:
        slug = product["slug"]
        source_file = product["images"][0]["file"]
        source_path = images_dir / slug / source_file
        if not source_path.is_file():
            raise SystemExit(f"product source missing: {source_path}")

        out_dir = images_dir / slug
        source = ImageOps.exif_transpose(Image.open(source_path)).convert("RGB")
        width, height = source.size

        main = add_badge(cover_square(source, GALLERY_SIZE), logo_path)
        save_jpeg(main, out_dir / "01-zevo-main.jpg")

        detail_box = (
            int(width * 0.21), int(height * 0.21),
            int(width * 0.79), int(height * 0.79),
        )
        detail = add_badge(cover_square(source.crop(detail_box), GALLERY_SIZE), logo_path)
        save_jpeg(detail, out_dir / "02-zevo-detail.jpg")

        styled = brand_frame(main, logo_path, product["category"])
        save_jpeg(styled, out_dir / "03-zevo-styled.jpg")

        card_path = out_dir / "04-zevo-brand-card.jpg"
        run_image_brand({
            "input": str(out_dir / "01-zevo-main.jpg"),
            "output": str(card_path),
            "title": "Z EVO",
            "subtitle": product["name"],
            "attribution": f"Z EVO - {product['category']}",
            "logo": str(logo_path),
            "crop": {"aspect": "1:1", "focus": "center"},
            "canvas": {"width": 1200, "height": 1200},
            "quality": 90,
        }, pipeline, out_dir, slug)

        gallery = [
            {"file": "01-zevo-main.jpg", "role": "main", "width": GALLERY_SIZE, "height": GALLERY_SIZE},
            {"file": "02-zevo-detail.jpg", "role": "detail", "width": GALLERY_SIZE, "height": GALLERY_SIZE},
            {"file": "03-zevo-styled.jpg", "role": "styled", "width": GALLERY_SIZE, "height": GALLERY_SIZE},
            {"file": "04-zevo-brand-card.jpg", "role": "brand-card", "width": GALLERY_SIZE, "height": GALLERY_SIZE},
        ]

        base_price = int(product["new_price"])
        base_purchase = int(product.get("purchase_price") or 0)
        ratio = garment_ratio(source)

        if ratio > 0.15:
            color_products += 1
            group_slug, group_name = "color", "Color"
            deltas = COLOR_DELTAS
            detected = dominant_hex(source)
            variants = []
            for index, (label, hex_value) in enumerate(COLOR_VARIANTS):
                if label == "Original":
                    variant_image = add_chip(main, "Original")
                    code = detected
                else:
                    assert hex_value is not None
                    variant_image = add_badge(
                        recolor_garment(cover_square(source, GALLERY_SIZE), hex_value),
                        logo_path,
                    )
                    variant_image = add_chip(variant_image, label)
                    code = hex_value
                file_name = f"variant-color-{slugify(label)}.jpg"
                save_jpeg(variant_image, out_dir / file_name)
                variants.append({
                    "sku_suffix": "DEFAULT" if index == 0 else f"V{index + 1}",
                    "title": f"{product['name']} - {label}",
                    "image": file_name,
                    "price": base_price + deltas[index],
                    "purchase_price": base_purchase,
                    "attributes": {
                        "group_slug": group_slug,
                        "group_name": group_name,
                        "value": label,
                        "code": code,
                    },
                })
            product["variant_mode"] = "color"
        else:
            size_products += 1
            group_slug, group_name = "size", "Size"
            deltas = SIZE_DELTAS
            labels = SIZE_SETS[product["category"]]
            variants = []
            for index, label in enumerate(labels):
                zoom = [1.0, 0.86, 0.72][index]
                if zoom >= 1.0:
                    cropped = main
                else:
                    box_w = int(main.width * zoom)
                    box_h = int(main.height * zoom)
                    left = (main.width - box_w) // 2
                    top = (main.height - box_h) // 2
                    cropped = add_badge(
                        cover_square(main.crop((left, top, left + box_w, top + box_h)), GALLERY_SIZE),
                        logo_path,
                    )
                variant_image = add_chip(cropped, label, accent=BRAND_ACCENT)
                file_name = f"variant-size-{index + 1}.jpg"
                save_jpeg(variant_image, out_dir / file_name)
                variants.append({
                    "sku_suffix": "DEFAULT" if index == 0 else f"V{index + 1}",
                    "title": f"{product['name']} - {label}",
                    "image": file_name,
                    "price": base_price + deltas[index],
                    "purchase_price": base_purchase,
                    "attributes": {
                        "group_slug": group_slug,
                        "group_name": group_name,
                        "value": label,
                        "code": "",
                    },
                })
            product["variant_mode"] = "size"

        product["source_image"] = source_file
        product["images"] = gallery
        product["variants"] = variants

    return color_products, size_products


def build_brand(logo_path: Path, banner_path: Path, videos_dir: Path) -> dict:
    ZEVO_BUNDLE.mkdir(parents=True, exist_ok=True)

    logo = Image.open(logo_path).convert("RGBA")
    logo.save(ZEVO_BUNDLE / "logo.png")

    white = np.asarray(logo).copy()
    white[..., 0:3] = 255
    Image.fromarray(white, "RGBA").save(ZEVO_BUNDLE / "logo-white.png")

    favicon = Image.new("RGB", (256, 256), BRAND_PRIMARY)
    draw = ImageDraw.Draw(favicon)
    draw.rounded_rectangle((0, 0, 255, 255), radius=56, fill=BRAND_PRIMARY)
    draw.text((128, 132), "Z", font=latin_font(170), fill=(255, 255, 255), anchor="mm")
    favicon.save(ZEVO_BUNDLE / "favicon.png")

    if not banner_path.is_file():
        raise SystemExit(f"banner source missing: {banner_path}")
    banner = ImageOps.exif_transpose(Image.open(banner_path)).convert("RGB")
    save_jpeg(cover_size(banner, 1920, 720), ZEVO_BUNDLE / "banner.jpg", quality=88)

    source_videos = sorted(
        [path for path in videos_dir.glob("*.mp4") if path.is_file()],
        key=lambda path: path.name,
    )
    if not source_videos:
        raise SystemExit(f"no videos found in {videos_dir}")

    (ZEVO_BUNDLE / "videos").mkdir(parents=True, exist_ok=True)
    video_rows = []
    for index, source in enumerate(source_videos, start=1):
        target = ZEVO_BUNDLE / "videos" / f"zevo-banner-{index:02d}.mp4"
        if not target.is_file() or sha256_file(target) != sha256_file(source):
            shutil.copy2(source, target)
        video_rows.append({
            "file": f"videos/{target.name}",
            "title": VIDEO_TITLES[(index - 1) % len(VIDEO_TITLES)],
            "bytes": target.stat().st_size,
        })

    manifest = {
        "version": 2,
        "name": "Z EVO",
        "tagline": "Everyday Essentials for Little Ones",
        "primary_color": "#003c9e",
        "secondary_color": "#00a3e0",
        "logo": "logo.png",
        "logo_white": "logo-white.png",
        "favicon": "favicon.png",
        "banner": "banner.jpg",
        "hero_title": "Z EVO - Everyday Essentials for Little Ones",
        "footer_about": (
            "Z EVO brings export-quality baby and kids clothing to Bangladesh: "
            "soft cotton, honest prices and sizes from premature to adults."
        ),
        "videos": video_rows,
    }
    (ZEVO_BUNDLE / "manifest.json").write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    return manifest


def main() -> int:
    parser = argparse.ArgumentParser(description="Build the Z EVO catalog asset bundle.")
    parser.add_argument("--catalog", type=Path, default=DEFAULT_CATALOG)
    parser.add_argument("--images", type=Path, default=DEFAULT_IMAGES)
    parser.add_argument("--category-source", type=Path, default=DEFAULT_CATEGORY_SOURCE)
    parser.add_argument("--logo", type=Path, default=DEFAULT_LOGO)
    parser.add_argument("--banner", type=Path, default=DEFAULT_BANNER)
    parser.add_argument("--videos", type=Path, default=DEFAULT_VIDEOS)
    parser.add_argument("--pipeline", type=Path, default=DEFAULT_PIPELINE)
    args = parser.parse_args()

    if not args.catalog.is_file():
        raise SystemExit(f"catalog missing: {args.catalog}")

    catalog = json.loads(args.catalog.read_text(encoding="utf-8"))
    if len(catalog.get("products", [])) != 30:
        raise SystemExit("catalog must contain exactly 30 products")

    categories = build_categories(args.category_source, args.catalog.parent)
    color_products, size_products = build_products(
        catalog, args.images, args.logo, args.pipeline
    )
    manifest = build_brand(args.logo, args.banner, args.videos)

    catalog["version"] = 2
    catalog["categories"] = categories
    catalog["brand"] = manifest
    args.catalog.write_text(
        json.dumps(catalog, ensure_ascii=False, indent=1) + "\n", encoding="utf-8"
    )

    print(json.dumps({
        "ok": True,
        "categories": len(categories),
        "products": len(catalog["products"]),
        "color_products": color_products,
        "size_products": size_products,
        "videos": len(manifest["videos"]),
        "catalog": str(args.catalog),
    }, ensure_ascii=False, indent=1))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
