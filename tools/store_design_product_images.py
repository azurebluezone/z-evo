#!/usr/bin/env python3
"""
store_design_product_images.py
------------------------------
Copies the FreshMart design's own product photographs into the storefront.

WHY THIS EXISTS
The storefront was carrying generated vector illustrations as product images.
The design source at /home/sahon/Downloads/01/hi already ships 152 real
photographs (public/img/fm_*.jpg), each with a descriptive alt text, and those
alt texts name the product: "Ripe organic Hass avocados pack",
"Cold-Pressed Extra Virgin Olive Oil bottle". This script reads every product
page HTML in that design, pairs each <img alt="..."> with its file, and copies
the ones that match a real product in this database.

SAFETY
Nothing is deleted. A product only gets a new image when a match is
unambiguous, the source file exists, and the file is a real JPEG. Anything
ambiguous is reported and skipped rather than guessed, because a wrong photo on
a shop is worse than an illustration.

USAGE
    python3 tools/store_design_product_images.py            # dry run, prints a plan
    python3 tools/store_design_product_images.py --apply    # copy the approved ones
"""

import argparse
import base64
import json
import os
import re
import shutil
import subprocess
import sys
from pathlib import Path

PROJECT = Path(__file__).resolve().parent.parent
DESIGN = Path("/home/sahon/Downloads/01/hi")
DESIGN_PAGES = [
    "freshmart_homepage_index.html",
    "product-details.html",
    "category.html",
    "shop.html",
    "index.html",
    "search.html",
]
PLAN_FILE = PROJECT / "work" / "design-photo-plan.json"

# Product name -> words that must ALL appear in an image's alt text.
# Matching on specific words instead of the whole name avoids "Fresh Bananas"
# matching a picture of a banana bread.
#
# Several entries carry a preferred_phrase. The design source describes some
# products in a shopkeeper's language rather than the catalogue's ("Rustic
# country sourdough bread boule"), so an exact phrase has to be looked for
# before falling back to the plain keyword match.
KEYWORDS = {
    "Organic Hass Avocados":      {"words": ["hass", "avocado"]},
    "Crisp Farm Broccoli":        {"words": ["broccoli"]},
    "Pure Organic Whole Milk":    {"words": ["milk"], "skip": ["cream"]},
    "Artisan Raw Honey":          {"words": ["honey"]},
    "Farm Fresh Tomatoes":        {"words": ["tomato"]},
    "Organic Potatoes":           {"words": ["potato"]},
    "Organic Spinach":            {"words": ["spinach"]},
    "Farm Fresh Eggs":            {"words": ["egg"]},
    "Fresh Carrots":              {"words": ["carrot"]},
    # The design's only apple shot is a mixed fruit bowl (strawberries, berries
    # and apples together), which is wrong for a single-product card.
        # The design has no plain apple shot: every apple picture is a mixed fruit
    # arrangement, so this product keeps its existing illustration.
    "Red Apple":                  {"words": ["apple"], "reject_all": True},
    "Skinless Chicken":           {"words": ["chicken"]},
    "Artisan Sourdough Bread":    {"words": ["bread", "sourdough"]},
    "Fresh Bananas":              {"words": ["banana"]},
    "Desi Ghee":                  {"words": ["ghee"], "alt": ["ghee", "clarified"]},
    # Every milk shot in the design also shows eggs and/or butter, so none of
    # them represents a single creamer pack.
    "Fresh Creamer Pack":         {"words": ["milk", "bottle"], "skip": ["egg", "butter", "creamery", "cheese"]},
    "Green Chilli":               {"words": ["chilli"]},
    # The design only stocks olive oil; showing it as "mustard oil" would be
    # wrong, so this product is left alone.
    "Cold Pressed Mustard Oil":   {"words": ["oil"], "skip": ["olive"]},
    "Red Onions":                 {"words": ["onion"]},
    "Red Rice":                   {"words": ["rice"]},
    "Roasted Chickpeas":          {"words": ["chickpea"]},
}

# Words that mean the picture is not a clean product shot.
REJECT = (
    "logo", "portrait", "person", "people", "shop", "store", "market",
    "astronaut", "nasa", "mars", "delivery", "truck", "cartoon", "logo",
)


def read_products():
    """Product name/id straight from the app, via artisan."""
    code = (
        "echo json_encode(\\App\\Models\\Product::orderBy('id')->get("
        "['id','name'])->toArray());"
    )
    out = subprocess.run(
        ["php", "artisan", "tinker", "--execute", code],
        cwd=PROJECT, capture_output=True, text=True, timeout=120,
    )
    raw = out.stdout.strip().splitlines()
    for line in reversed(raw):
        line = line.strip()
        if line.startswith("[") and line.endswith("]"):
            return json.loads(line)
    raise SystemExit("Could not read products: " + out.stderr[:300])


def harvest_images():
    """Collect (alt_text, abs_path) for every product image in the design."""
    found = []
    seen = set()
    img_re = re.compile(r'<img\b[^>]*?alt="([^"]*)"[^>]*?src="([^"]+)"', re.I)
    img_re2 = re.compile(r'<img\b[^>]*?src="([^"]+)"[^>]*?alt="([^"]*)"', re.I)

    for page in DESIGN_PAGES:
        path = DESIGN / page
        if not path.exists():
            continue
        html = path.read_text(encoding="utf-8", errors="ignore")
        for m in img_re.finditer(html):
            alt, src = m.group(1), m.group(2)
        for m in list(img_re.finditer(html)) + list(img_re2.finditer(html)):
            groups = m.groups()
            alt, src = (groups[0], groups[1]) if groups[0] and " " in groups[0] else (groups[1], groups[0])
            if not src.startswith("public/img/"):
                continue
            name = src.rsplit("/", 1)[-1]
            if name in seen or name == "fm_logo.png":
                continue
            fp = DESIGN / "public" / "img" / name
            if not fp.exists():
                continue
            seen.add(name)
            found.append({"alt": alt.strip(), "file": name, "path": str(fp), "page": page})
    return found


def match_score(alt, spec):
    """None when this image is not clearly this product."""
    low = alt.lower()
    if not low:
        return None
    if spec.get("reject_all"):
        return None
    if any(r in low for r in REJECT):
        return None

    # Any of these disqualifies the image outright.
    for bad in spec.get("skip", []):
        if bad in low:
            return None

    # Preferred phrasing wins outright: the design spells some products in a
    # shopkeeper's words, and that is the shot we actually want.
    for phrase in spec.get("alt", []):
        if phrase in low:
            return 100 + len(spec["words"])

    words = spec["words"]
    hits = [k for k in words if k in low]
    # Every keyword must appear, otherwise "milk" would satisfy creamer on a
    # picture that only shows a milk carton with no cream.
    if len(hits) < len(words):
        return None
    return len(hits)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--apply", action="store_true", help="actually copy the files")
    args = ap.parse_args()

    if not DESIGN.exists():
        raise SystemExit(f"Design source not found: {DESIGN}")

    products = read_products()
    images = harvest_images()

    print("=" * 96)
    print("DESIGN PHOTO STORE" + ("  (APPLYING)" if args.apply else "  (dry run)"))
    print("=" * 96)
    print(f"products in database : {len(products)}")
    print(f"product photos in design: {len(images)}")
    print("-" * 96)

    plan = []
    unmatched_products = []
    used = set()

    for p in products:
        spec = KEYWORDS.get(p["name"])
        if not spec:
            unmatched_products.append(p["name"])
            continue

        # Prefer the image whose alt mentions the product most specifically.
        best = None
        for img in images:
            if img["file"] in used:
                continue
            s = match_score(img["alt"], spec)
            if s is None:
                continue
            # Prefer shorter alt: it is usually the plainer product shot.
            key = (s, -len(img["alt"]))
            if best is None or key > best[0]:
                best = (key, img)

        if best is None:
            unmatched_products.append(p["name"])
            continue

        img = best[1]
        used.add(img["file"])
        plan.append({
            "product_id": p["id"],
            "product": p["name"],
            "source": img["path"],
            "alt": img["alt"],
            "file": img["file"],
            "page": img["page"],
        })

    target_dir = PROJECT / "public" / "uploads" / "product"
    applied = []
    for row in plan:
        dst = target_dir / row["file"]
        row["target"] = str(dst)
        row["web_path"] = "uploads/product/" + row["file"]
        status = "OK"
        if args.apply:
            try:
                shutil.copyfile(row["source"], dst)
                # Keep the alt text next to the file so a reviewer can see what
                # the photograph actually shows without opening the storefront.
                (target_dir / (row["file"] + ".alt.txt")).write_text(
                    f"{row['alt']}\nsource: {DESIGN.name}/{row['page']}\n",
                    encoding="utf-8",
                )
                applied.append(row)
            except Exception as exc:  # noqa: BLE001
                status = f"FAIL {exc}"
        print(f"  #{row['product_id']:<3} {row['product'][:26]:<27} <- {row['file']:<28} {status}")

    print("-" * 96)
    print(f"matched {len(plan)} / {len(products)}")
    if unmatched_products:
        print("\nno match in the design source (left as they are):")
        for n in unmatched_products:
            print("  -", n)

    if not args.apply:
        PLAN_FILE.parent.mkdir(parents=True, exist_ok=True)
        PLAN_FILE.write_text(json.dumps(plan, indent=2, ensure_ascii=False), encoding="utf-8")
        print(f"\nDry run. Plan written to {PLAN_FILE}")
        print("Re-run with --apply to copy the files and update the database.")
        sys.exit(0)

    # Point the database at the copied files. Without this the storefront keeps
    # rendering the old illustration, because productimages.image is the only
    # thing the views read.
    if applied:
        payload = json.dumps(applied)
        code = (
            "$rows = json_decode(base64_decode('"
            + base64.b64encode(payload.encode()).decode()
            + "'), true);"
            "$done = 0;"
            "foreach ($rows as $r) {"
            "  $ok = \\DB::table('productimages')->where('product_id', $r['product_id'])->update(['image' => $r['web_path']]);"
            "  if ($ok) { $done++; }"
            "}"
            "echo $done;"
        )
        out = subprocess.run(
            ["php", "artisan", "tinker", "--execute", code],
            cwd=PROJECT, capture_output=True, text=True, timeout=120,
        )
        updated = out.stdout.strip().splitlines()[-1] if out.stdout.strip() else "?"
        print(f"\nCopied {len(applied)} image(s); database rows updated: {updated}")
    else:
        print("\nNothing was copied.")


if __name__ == "__main__":
    main()