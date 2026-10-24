#!/usr/bin/env python3
"""
Frontend seed pipeline — Shinjuku Halal Food -> z-evo demo store.

Why this shape?
---------------
The source site sits behind Cloudflare: plain `curl`/`requests` gets a 403
"Just a moment..." on both /shop/ and /wp-json/wc/store/v1/products. A real
browser session passes the challenge, so the product *snapshot* is captured
once through the browser (see work/t/data/shinjuku_products_20.json) and this
script owns everything that can be automated safely:

  1. download + verify every product image the snapshot points at
  2. pull a few freely licensed banner/slider photos from Wikimedia Commons
     and crop them to the exact sizes the storefront CSS expects
  3. write one manifest (work/t/data/frontend_seed.json) that the PHP seeder
     consumes - paths, hashes, licences, provenance all in one place

Sizes (measured against public/frontend/css):
  hero slider   .fm-hero-banner  height: clamp(260px, 38vw, 520px) -> 1920x720
  promo banner  .fm-promo__media img aspect-ratio: 520/420          -> 1240x1000

Usage:
    python3 tools/frontend_seed/build_frontend_seed.py [--force] [--offline]

--offline re-uses already downloaded files and only rebuilds the manifest.
"""
from __future__ import annotations

import argparse
import hashlib
import io
import json
import re
import sys
import time
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SNAPSHOT = ROOT / "work/t/data/shinjuku_products_20.json"
MANIFEST = ROOT / "work/t/data/frontend_seed.json"
MEDIA = ROOT / "public/uploads/frontend-seed"

UA = "ZombieCoder-frontend-seed/1.0 (+https://zombiecoder.my.id; content migration with owner permission)"
DELAY_S = 1.2  # polite: one request at a time, well above the site's crawl-delay floor

SLIDER_SIZE = (1920, 720)
PROMO_SIZE = (1240, 1000)


def log(msg: str) -> None:
    print(f"[seed] {msg}", flush=True)


def http_get(url: str, timeout: int = 45) -> bytes:
    req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": "*/*"})
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        if resp.status != 200:
            raise RuntimeError(f"HTTP {resp.status} for {url}")
        return resp.read()


def sha256_bytes(blob: bytes) -> str:
    return hashlib.sha256(blob).hexdigest()


def image_size(blob: bytes) -> tuple[int, int]:
    from PIL import Image

    with Image.open(io.BytesIO(blob)) as im:
        return im.size


def download_product_images(products: list[dict], force: bool) -> int:
    """Download every product gallery image; annotate the product dict in place."""
    from PIL import Image

    count = 0
    for product in products:
        slug = product["slug"]
        out_dir = MEDIA / "products" / slug
        out_dir.mkdir(parents=True, exist_ok=True)
        kept: list[dict] = []

        for index, url in enumerate(product.get("imgs", []), start=1):
            ext = Path(urllib.parse.urlparse(url).path).suffix.lower() or ".jpg"
            if ext not in {".jpg", ".jpeg", ".png", ".webp"}:
                ext = ".jpg"
            target = out_dir / f"{index:02d}{ext}"

            if force or not target.exists():
                try:
                    blob = http_get(url)
                except Exception as exc:  # network flake -> report, keep going
                    log(f"  MISS {slug} #{index}: {exc}")
                    time.sleep(DELAY_S)
                    continue
                size = image_size(blob)
                if size[0] < 300 or size[1] < 300:
                    log(f"  SKIP {slug} #{index}: {size[0]}x{size[1]} too small")
                    time.sleep(DELAY_S)
                    continue
                target.write_bytes(blob)
                time.sleep(DELAY_S)
                count += 1

            blob = target.read_bytes()
            with Image.open(io.BytesIO(blob)) as im:
                width, height = im.size
            kept.append(
                {
                    "rel": target.relative_to(ROOT / "public").as_posix(),
                    "src": url,
                    "sha256": sha256_bytes(blob),
                    "width": width,
                    "height": height,
                    "bytes": len(blob),
                }
            )

        # renumber contiguous (01, 02, ...) so the gallery order is stable
        product["images"] = kept

    return count


def commons_search(query: str, limit: int = 12) -> list[dict]:
    params = {
        "action": "query",
        "format": "json",
        "generator": "search",
        "gsrsearch": f"filetype:bitmap {query}",
        "gsrnamespace": "6",
        "gsrlimit": str(limit),
        "prop": "imageinfo",
        "iiprop": "url|size|extmetadata",
        "iiurlwidth": "2400",
    }
    api = "https://commons.wikimedia.org/w/api.php?" + urllib.parse.urlencode(params)
    payload = json.loads(http_get(api))
    pages = (payload.get("query") or {}).get("pages") or {}
    return sorted(pages.values(), key=lambda p: p.get("index", 0))


def pick_licensed(pages: list[dict]) -> dict | None:
    """First page that is big enough and has a reusable licence."""
    good_licences = re.compile(r"(cc[- ]?by|cc0|public domain|pd-|gfdl)", re.I)
    for page in pages:
        info = (page.get("imageinfo") or [{}])[0]
        meta = info.get("extmetadata") or {}
        licence = (meta.get("LicenseShortName") or {}).get("value", "")
        width, height = info.get("width", 0), info.get("height", 0)
        if width >= 1600 and good_licences.search(licence):
            author = re.sub(r"<[^>]+>", "", (meta.get("Artist") or {}).get("value", "")).strip()
            return {
                "title": page.get("title", ""),
                "url": info.get("thumburl") or info.get("url"),
                "page": info.get("descriptionurl", ""),
                "licence": re.sub(r"<[^>]+>", "", licence).strip(),
                "author": author[:160],
                "width": width,
                "height": height,
            }
    return None


def cover_crop(blob: bytes, target_size: tuple[int, int]) -> bytes:
    """Scale to cover, then centre-crop - never stretch."""
    from PIL import Image

    with Image.open(io.BytesIO(blob)) as im:
        im = im.convert("RGB")
        tw, th = target_size
        scale = max(tw / im.width, th / im.height)
        new = (round(im.width * scale), round(im.height * scale))
        im = im.resize(new, Image.LANCZOS)
        left = (im.width - tw) // 2
        top = (im.height - th) // 2
        im = im.crop((left, top, left + tw, top + th))
        buf = io.BytesIO()
        im.save(buf, "JPEG", quality=88, optimize=True, progressive=True)
        return buf.getvalue()


def fetch_wikimedia_group(group: str, queries: list[str], size: tuple[int, int], force: bool) -> list[dict]:
    """group: 'banners' | 'promo' - saves <group>/<n>-<slug>.jpg + manifest rows."""
    out_dir = MEDIA / group
    out_dir.mkdir(parents=True, exist_ok=True)
    rows: list[dict] = []
    used_hashes: set[str] = set()

    for query in queries:
        if len(rows) >= 3:
            break
        try:
            pages = commons_search(query)
        except Exception as exc:
            log(f"  WIKI search failed ({query}): {exc}")
            continue
        picked = pick_licensed(pages)
        if not picked:
            log(f"  WIKI no licensed hit for: {query}")
            continue

        slug = re.sub(r"[^a-z0-9]+", "-", picked["title"].split(":", 1)[-1].rsplit(".", 1)[0].lower()).strip("-")[:60]
        target = out_dir / f"{len(rows) + 1:02d}-{slug}.jpg"

        try:
            blob = target.read_bytes() if target.exists() and not force else http_get(picked["url"])
        except Exception as exc:
            log(f"  WIKI download failed ({picked['title']}): {exc}")
            continue

        digest = sha256_bytes(blob)
        if digest in used_hashes:
            log(f"  WIKI duplicate skipped: {picked['title']}")
            continue
        used_hashes.add(digest)

        cropped = cover_crop(blob, size)
        target.write_bytes(cropped)
        rows.append(
            {
                "rel": target.relative_to(ROOT / "public").as_posix(),
                "source": picked["page"],
                "artist": picked["author"],
                "licence": picked["licence"],
                "width": size[0],
                "height": size[1],
                "sha256": sha256_bytes(cropped),
                "query": query,
            }
        )
        log(f"  WIKI ok: {target.name} ({picked['licence']})")
        time.sleep(DELAY_S)

    return rows


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--force", action="store_true", help="re-download everything")
    parser.add_argument("--offline", action="store_true", help="only rebuild the manifest")
    args = parser.parse_args()

    if not SNAPSHOT.exists():
        log(f"missing snapshot: {SNAPSHOT}")
        return 2

    snapshot = json.loads(SNAPSHOT.read_text())
    products = snapshot["products"]
    MEDIA.mkdir(parents=True, exist_ok=True)

    downloaded = 0
    if not args.offline:
        log(f"product images -> {MEDIA / 'products'}")
        downloaded = download_product_images(products, args.force)
    else:
        # offline: trust existing files, derive rel paths from disk
        for product in products:
            folder = MEDIA / "products" / product["slug"]
            files = sorted(folder.glob("*")) if folder.exists() else []
            product["images"] = [
                {
                    "rel": f.relative_to(ROOT / "public").as_posix(),
                    "src": "",
                    "sha256": sha256_bytes(f.read_bytes()),
                    "width": image_size(f.read_bytes())[0],
                    "height": image_size(f.read_bytes())[1],
                    "bytes": f.stat().st_size,
                }
                for f in files
                if f.suffix.lower() in {".jpg", ".jpeg", ".png", ".webp"}
            ]

    banners = {"slider": [], "promo": []}
    if not args.offline:
        banners["slider"] = fetch_wikimedia_group(
            "banners",
            [
                "halal food grocery shop",
                "fresh vegetables market stall",
                "spices market bowls",
            ],
            SLIDER_SIZE,
            args.force,
        )
        banners["promo"] = fetch_wikimedia_group(
            "promo",
            [
                "lentils dal bowl",
                "raw meat butcher shop",
                "rice grains sack",
            ],
            PROMO_SIZE,
            args.force,
        )
    else:
        for group in ("banners", "promo"):
            for f in sorted((MEDIA / group).glob("*.jpg")):
                blob = f.read_bytes()
                banners["slider" if group == "banners" else "promo"].append(
                    {"rel": f.relative_to(ROOT / "public").as_posix(), "sha256": sha256_bytes(blob), "offline": True}
                )

    manifest = {
        "generated_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "generator": "tools/frontend_seed/build_frontend_seed.py",
        "source": snapshot["source"],
        "sizes": {"slider": SLIDER_SIZE, "promo": PROMO_SIZE},
        "products": products,
        "banners": banners,
    }
    MANIFEST.write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n")

    product_images = sum(len(p.get("images", [])) for p in products)
    log(f"downloaded now: {downloaded}")
    log(f"products: {len(products)} | product images: {product_images}")
    log(f"slider banners: {len(banners['slider'])} | promo banners: {len(banners['promo'])}")
    log(f"manifest: {MANIFEST.relative_to(ROOT)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
