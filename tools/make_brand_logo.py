#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
make_brand_logo.py
==================
Renders the Zevo E-commerce wordmark used by the site header and favicon.

Why this exists
---------------
The shop header renders whatever file `general_settings.white_logo` points at.
That file still had the previous shop's artwork baked into it, so the name
could not be changed from the database at all. The wordmark therefore has to be
produced as an image.

Output
------
    zevo-white-logo.png   WHITE text on transparency, for a dark header
    zevo-dark-logo.png    DARK text on transparency, for a light header

The naming follows this application's convention, which is the opposite of what
the words suggest at first glance: "white_logo" is the logo drawn in white, so
it is meant for a dark background, and "dark_logo" is the one drawn in dark ink
for a light background. The first version swapped the two and produced a white
logo on the light shop header, which was invisible.

Both are written to the same folder the settings table already reads from, and
the paths are printed so they can be pasted into the admin panel.

Usage
-----
    python3 tools/make_brand_logo.py --out public/uploads/settings
    python3 tools/make_brand_logo.py --dry-run
"""

from __future__ import annotations

import argparse
import os
import sys

try:
    from PIL import Image, ImageDraw, ImageFont
except ImportError:  # pragma: no cover
    sys.stderr.write(
        "Pillow is required.\n"
        "  python3 -m venv .venv && .venv/bin/pip install Pillow\n"
    )
    raise SystemExit(1)

WORDMARK = "ZEVO"
TAGLINE = "E-commerce"

# The site's own accent, taken from the theme's primary green.
INK = (14, 74, 52)
WHITE = (255, 255, 255)

FONT_CANDIDATES = [
    "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
    "/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf",
]
TAG_FONT_CANDIDATES = [
    "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    "/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf",
]


def load_font(paths: list[str], size: int) -> ImageFont.FreeTypeFont:
    for path in paths:
        if os.path.exists(path):
            return ImageFont.truetype(path, size)
    return ImageFont.load_default(size)


def leaf(draw: ImageDraw.ImageDraw, cx: int, cy: int, size: int, colour: tuple) -> None:
    """
    A leaf mark drawn as two quarter circles.

    The first version used a four point polygon and rendered as a thin diamond,
    which read as a shard rather than a leaf. Two opposing quarter discs give
    the rounded, asymmetric silhouette of a real leaf, and the midrib line is
    the gap between them.
    """
    half = size
    # Lower-right quarter disc, then the upper-left one: the two bulges are what
    # make the outline read as a leaf instead of a lens shape.
    draw.pieslice((cx - half, cy - half, cx + half, cy + half),
                  start=0, end=90, fill=colour)
    draw.pieslice((cx - half, cy - half, cx + half, cy + half),
                  start=180, end=270, fill=colour)
    draw.line((cx - half, cy + half, cx + half, cy - half),
              fill=WHITE, width=max(2, half // 7))


def render(path: str, ink: tuple) -> dict:
    width, height = 720, 200
    canvas = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    pen = ImageDraw.Draw(canvas)

    mark_size = 58
    leaf(pen, 74, 100, mark_size, ink)

    word_font = load_font(FONT_CANDIDATES, 86)
    tag_font = load_font(TAG_FONT_CANDIDATES, 32)

    pen.text((152, 100), WORDMARK, font=word_font, fill=ink, anchor="lm")

    word_width = pen.textbbox((152, 100), WORDMARK, font=word_font, anchor="lm")[2] - 152
    pen.line((160, 132, 160 + word_width - 20, 132), fill=ink, width=3)
    pen.text((160 + word_width + 14, 100), TAGLINE, font=tag_font, fill=ink, anchor="lm")

    # Trim the transparent margin so the header can size the logo by its own
    # content instead of the canvas.
    bbox = canvas.getbbox()
    if bbox:
        canvas = canvas.crop(bbox)

    canvas.save(path, "PNG", optimize=True)
    return {"file": path, "size": canvas.size}


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__,
                                     formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--out", default="public/uploads/settings")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    if args.dry_run:
        print("  would write %s/zevo-white-logo.png (dark ink)" % args.out)
        print("  would write %s/zevo-dark-logo.png  (white ink)" % args.out)
        return 0

    os.makedirs(args.out, exist_ok=True)
    # white_logo = white ink (dark background), dark_logo = dark ink (light one).
    for name, ink in (("zevo-white-logo.png", WHITE), ("zevo-dark-logo.png", INK)):
        info = render(os.path.join(args.out, name), ink)
        print("  %s  %dx%d  %d bytes" % (info["file"], info["size"][0], info["size"][1],
                                          os.path.getsize(info["file"])))

    print("\n  Set these in the admin panel (Settings -> logo):")
    print("    white_logo = public/%s/zevo-white-logo.png" % args.out)
    print("    dark_logo  = public/%s/zevo-dark-logo.png" % args.out)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())