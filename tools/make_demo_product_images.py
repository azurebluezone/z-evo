#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
make_demo_product_images.py
============================
Generates the demo catalogue images for the storefront, matching the visual
language of the existing blog-cover set
(~/Desktop/zevlms/blog/images/blog-covers/, 1200x630 JPEG).

Design language copied from that reference, measured not guessed:

  * 1200 x 630 canvas (16:9.5, exact match to all 54 reference files)
  * dark teal-to-green gradient base with a soft vignette
  * subtle dot-grid texture, same 26px pitch as the reference
  * rounded green pill badge in the upper left (radius = height/2)
  * large white title, left aligned, on a dark scrim so it stays readable
  * a short amber accent rule under the title
  * one-line muted subtitle
  * brand lockup bottom left: white rounded plate + wordmark + tagline
  * quality exported at JPEG quality 86, optimised, progressive

Product photography cannot be produced here (no stock imagery is bundled and
nothing is downloaded), so each frame is a graphic composition: an ingredient
motif drawn with Pillow primitives plus the product name as real type. That is
stated here rather than pretending these are photographs.

Usage
-----
    python3 tools/make_demo_product_images.py --out public/uploads/product
    python3 tools/make_demo_product_images.py --dry-run
    python3 tools/make_demo_product_images.py --only avocado,milk

Requires Pillow. Run it inside a virtualenv if the system Python is
externally managed:

    python3 -m venv .venv && .venv/bin/pip install Pillow
"""

from __future__ import annotations

import argparse
import math
import os
import sys
import unicodedata
from dataclasses import dataclass, field

try:
    from PIL import Image, ImageDraw, ImageFilter, ImageFont
except ImportError:  # pragma: no cover - guidance for the operator
    sys.stderr.write(
        "Pillow is required.\n"
        "  python3 -m venv .venv && .venv/bin/pip install Pillow\n"
        "  .venv/bin/python tools/make_demo_product_images.py\n"
    )
    raise SystemExit(1)


# --------------------------------------------------------------------------
# Canvas and palette
# --------------------------------------------------------------------------

CANVAS_W = 1200
CANVAS_H = 630

# The product card is aspect-square, so a 16:9.5 frame gets centre-cropped and
# the headline text on the left half is cut away. A square variant is rendered
# for catalogue use: the art moves to the centre, the type stays inside a safe
# margin, and nothing important sits near an edge that object-cover would
# remove. Blog-cover proportions stay the default for anything that is used as
# a banner rather than a card image.
SQUARE = 1000

# Deep teal -> forest green, the same family as the reference covers.
BG_TOP = (10, 38, 44)
BG_BOTTOM = (7, 54, 40)
ACCENT_GREEN = (34, 197, 94)
ACCENT_AMBER = (245, 178, 40)
TEXT_WHITE = (255, 255, 255)
TEXT_MUTED = (203, 222, 216)
DOT = (255, 255, 255, 16)


@dataclass
class Product:
    """One demo product: the catalogue text plus how to draw its motif."""

    slug: str
    name: str
    badge: str
    tagline: str
    unit: str
    motif: str
    tint: tuple = (60, 180, 110)


# --------------------------------------------------------------------------
# The demo catalogue
# --------------------------------------------------------------------------

PRODUCTS: list[Product] = [
    Product("organic-hass-avocado", "Organic Hass Avocado", "100% Organic",
            "Creamy flesh, harvested at peak ripeness", "500 g",
            "avocado", (86, 160, 96)),
    Product("crispy-fresh-broccoli", "Crisp Farm Broccoli", "Farm Fresh",
            "Tight green crowns, cut this morning", "1 pc",
            "broccoli", (74, 150, 88)),
    Product("pure-organic-milk", "Pure Organic Whole Milk", "Daily Dairy",
            "Full cream, from grass-fed herds", "1 L",
            "bottle", (226, 236, 232)),
    Product("artisan-raw-honey", "Artisan Wild Flower Honey", "Raw & Unfiltered",
            "Single hive harvest, nothing added", "400 g",
            "jar", (222, 158, 48)),
    Product("farm-fresh-tomatoes", "Farm Fresh Tomatoes", "Vine Ripened",
            "Picked at dawn for firm, juicy flesh", "1 kg",
            "tomato", (206, 66, 58)),
    Product("organic-potatoes", "Organic Potatoes", "Soil Grown",
            "Earthy, firm and low in moisture", "2 kg",
            "potato", (166, 132, 82)),
    Product("fresh-spinach-bundle", "Fresh Spinach Bundle", "Leafy Green",
            "Washed twice, ready to cook", "300 g",
            "leaf", (58, 132, 72)),
    Product("free-range-eggs", "Free Range Eggs", "Free Range",
            "Golden yolks from free roaming hens", "12 pcs",
            "eggs", (232, 216, 186)),
    Product("fresh-carrots", "Fresh Carrots", "Sweet Crunch",
            "Long, crisp and pulled this week", "500 g",
            "carrot", (226, 118, 40)),
    Product("red-apple", "Red Apple", "Orchard Fresh",
            "Crisp, juicy and naturally sweet", "1 kg",
            "apple", (198, 48, 52)),
    Product("skinless-chicken", "Skinless Chicken Breast", "Butcher Cut",
            "Lean fillet, chilled same day", "1 kg",
            "meat", (206, 116, 108)),
    Product("artisan-sourdough-bread", "Artisan Sourdough Bread", "Baked Fresh",
            "Slow fermented for 24 hours", "400 g",
            "bread", (196, 150, 96)),
    Product("red-onion", "Red Onions", "Everyday Kitchen",
            "Sharp, firm and stores well", "1 kg",
            "onion", (156, 84, 118)),
    Product("fresh-banana", "Fresh Bananas", "Tropical Pick",
            "Naturally sweet, ready to eat", "1 dozen",
            "banana", (236, 202, 66)),
    Product("green-chili", "Green Chili", "Farm Fresh",
            "Medium heat, picked young", "250 g",
            "chili", (110, 176, 60)),
    Product("red-rice", "Red Rice", "Staple Grain",
            "Unpolished, wholegrain and fragrant", "5 kg",
            "rice", (188, 108, 92)),
    Product("mustard-oil", "Cold Pressed Mustard Oil", "Cold Pressed",
            "Wood pressed, no chemical refining", "1 L",
            "bottle", (214, 178, 62)),
    Product("fresh-creamer-pack", "Fresh Creamer Pack", "Daily Dairy",
            "UHT treated for a long shelf life", "500 ml",
            "carton", (232, 240, 236)),
    Product("desi-ghee", "Desi Ghee", "Traditional Dairy",
            "Slow churned from cow butter", "500 g",
            "jar", (236, 198, 96)),
    Product("roasted-chickpeas", "Roasted Chickpeas", "High Protein",
            "Crunchy snack, lightly spiced", "300 g",
            "beans", (198, 158, 84)),
]

BRAND_WORD = "ZEVO"
BRAND_TAGLINE = "E-commerce"


# --------------------------------------------------------------------------
# Text helpers
# --------------------------------------------------------------------------

def _font_candidates() -> dict[str, list[str]]:
    """Font paths to try, in order of preference, per role."""
    return {
        "bold": [
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
            "/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf",
            "/usr/share/fonts/truetype/freefont/FreeSansBold.ttf",
        ],
        "regular": [
            "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
            "/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf",
            "/usr/share/fonts/truetype/freefont/FreeSans.ttf",
        ],
    }


def load_font(role: str, size: int) -> ImageFont.FreeTypeFont:
    """Load the first available font for a role, else fall back to the default."""
    for path in _font_candidates()[role]:
        if os.path.exists(path):
            return ImageFont.truetype(path, size)
    return ImageFont.load_default(size)


def strip_accents(text: str) -> str:
    """Drop combining marks so the bundled fonts never render a blank box."""
    normalised = unicodedata.normalize("NFKD", text)
    return "".join(ch for ch in normalised if not unicodedata.combining(ch))


def clamp(rgb: tuple) -> tuple:
    """
    Keep a tint inside the 0-255 range after a brightness multiplier.

    There is deliberately no minimum lift here. An earlier version raised weak
    channels to a floor of 108 to fight a dark left scrim, but the scrim no
    longer touches the artwork, and lifting the green and blue channels of
    the carrot orange (226,118,40) turned it pink.
    """
    return tuple(max(0, min(255, int(round(channel)))) for channel in rgb)


def text_width(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.FreeTypeFont) -> int:
    box = draw.textbbox((0, 0), text, font=font)
    return box[2] - box[0]


def wrap_text(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.FreeTypeFont,
              max_width: int, max_lines: int) -> list[str]:
    """
    Greedy word wrap, clipped to max_lines with an ellipsis.

    Every word is laid out first, so an ellipsis appears only when the text
    genuinely does not fit. An earlier version guessed that from the line
    count and clipped names such as "Organic Hass Avocado" to "Avocado..."
    even though the whole name fitted comfortably on the second line.
    """
    words = text.split()
    lines: list[str] = []
    current = ""
    for word in words:
        probe = f"{current} {word}".strip()
        if text_width(draw, probe, font) <= max_width or not current:
            current = probe
        else:
            lines.append(current)
            current = word
    if current:
        lines.append(current)

    if len(lines) <= max_lines:
        return lines

    lines = lines[:max_lines]
    trimmed = lines[-1]
    while trimmed and text_width(draw, f"{trimmed}...", font) > max_width:
        trimmed = trimmed[:-1]
    # Drop a trailing space left by the cut so the ellipsis reads cleanly.
    lines[-1] = trimmed.rstrip() + "..."
    return lines


# --------------------------------------------------------------------------
# Background
# --------------------------------------------------------------------------

def vertical_gradient(width: int, height: int, top: tuple, bottom: tuple) -> Image.Image:
    """A single vertical gradient, built small and scaled up for speed."""
    strip = Image.new("RGB", (1, height))
    pixels = strip.load()
    for y in range(height):
        ratio = y / max(1, height - 1)
        pixels[0, y] = tuple(
            int(round(top[channel] + (bottom[channel] - top[channel]) * ratio))
            for channel in range(3)
        )
    return strip.resize((width, height), Image.BILINEAR)


def add_dot_grid(image: Image.Image, pitch: int = 26) -> Image.Image:
    """Faint dot texture, the same decorative layer the reference covers use."""
    overlay = Image.new("RGBA", image.size, (0, 0, 0, 0))
    pen = ImageDraw.Draw(overlay)
    step = pitch
    for y in range(step // 2, image.height, step):
        for x in range(step // 2, image.width, step):
            pen.ellipse((x - 2, y - 2, x + 2, y + 2), fill=DOT)
    return Image.alpha_composite(image.convert("RGBA"), overlay)


def add_vignette(image: Image.Image) -> Image.Image:
    """Darken the corners so the white type always has contrast to sit on."""
    mask = Image.new("L", image.size, 0)
    pen = ImageDraw.Draw(mask)
    pen.ellipse(
        (-image.width * 0.30, -image.height * 0.42,
         image.width * 1.30, image.height * 1.42),
        fill=150,
    )
    mask = mask.filter(ImageFilter.GaussianBlur(image.width // 12))
    dark = Image.new("RGBA", image.size, (0, 0, 0, 255))
    dark.putalpha(mask)
    return Image.alpha_composite(image.convert("RGBA"), dark)


def add_left_scrim(image: Image.Image) -> Image.Image:
    """Left-to-right darkening so the headline column is always legible."""
    width, height = image.size
    mask = Image.new("L", (width, height), 0)
    pixels = mask.load()
    for x in range(width):
        ratio = x / max(1, width - 1)
        pixels[x, 0] = int(150 * max(0.0, 1.0 - ratio * 1.55))
    scrim = Image.new("RGBA", image.size, (0, 0, 0, 255))
    scrim.putalpha(mask)
    return Image.alpha_composite(image.convert("RGBA"), scrim)


# --------------------------------------------------------------------------
# Ingredient motifs
# --------------------------------------------------------------------------

def _sphere(draw: ImageDraw.ImageDraw, cx: int, cy: int, r: int,
            base: tuple, light: tuple) -> None:
    """A shaded ball: lighter cap toward the light, darker underside."""
    draw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=base)
    draw.ellipse((cx - r * 0.62, cy - r * 0.86, cx + r * 0.22, cy - r * 0.06),
                 fill=light)


def draw_motif(pen: ImageDraw.ImageDraw, kind: str, box: tuple, tint: tuple) -> None:
    """Draw one ingredient motif inside the given box, scaled to fit."""
    x0, y0, x1, y1 = box
    span = min(x1 - x0, y1 - y0)
    cx = (x0 + x1) / 2
    cy = (y0 + y1) / 2
    r = span * 0.34

    def shade(factor: float) -> tuple:
        return clamp([channel * factor for channel in tint])

    def lighter(factor: float = 1.28) -> tuple:
        return clamp([channel * factor for channel in tint])

    if kind == "avocado":
        # Whole fruit behind, half with the pit in front.
        _sphere(pen, int(cx - r * 0.72), int(cy - r * 0.10), int(r * 0.94), shade(0.82), lighter())
        _sphere(pen, int(cx + r * 0.62), int(cy + r * 0.16), int(r * 0.80), shade(0.92), lighter(1.16))
        pen.ellipse((cx + r * 0.10, cy - r * 0.30, cx + r * 1.18, cy + r * 0.86),
                     fill=lighter(1.05))
        pen.ellipse((cx + r * 0.34, cy - r * 0.06, cx + r * 0.94, cy + r * 0.54),
                     fill=(176, 118, 62))
    elif kind == "broccoli":
        # Crown of overlapping lobes on a stalk.
        for offset_x, offset_y, scale in (
            (-0.52, -0.42, 0.52), (0.10, -0.60, 0.60), (0.62, -0.34, 0.50),
            (-0.26, 0.02, 0.56), (0.44, 0.06, 0.48),
        ):
            rr = r * scale
            _sphere(pen, int(cx + r * offset_x), int(cy + r * offset_y), int(rr),
                    shade(0.86), lighter(1.18))
        pen.rectangle((cx - r * 0.20, cy + r * 0.30, cx + r * 0.20, cy + r * 1.30),
                      fill=lighter(1.30))
    elif kind == "tomato":
        _sphere(pen, int(cx - r * 0.58), int(cy + r * 0.16), int(r * 0.78), shade(0.92), lighter(1.24))
        _sphere(pen, int(cx + r * 0.52), int(cy - r * 0.18), int(r * 0.86), shade(1.0), lighter(1.32))
        for angle in (0, 72, 144, 216, 288):
            rad = math.radians(angle)
            pen.line((cx + r * 0.52,
                      cy - r * 0.18,
                      cx + r * 0.52 + math.cos(rad) * r * 0.62,
                      cy - r * 0.18 + math.sin(rad) * r * 0.44),
                     fill=shade(0.62), width=max(3, int(r * 0.10)))
    elif kind == "potato":
        for offset_x, offset_y, scale in ((-0.58, 0.22, 0.70), (0.46, 0.44, 0.62), (0.02, -0.36, 0.76)):
            rr = r * scale
            _sphere(pen, int(cx + r * offset_x), int(cy + r * offset_y), int(rr),
                    shade(0.94), lighter(1.18))
    elif kind == "leaf":
        # A bunch of leaves fanning from a tied stem.
        for angle in (200, 245, 290, 335, 20):
            rad = math.radians(angle)
            length = r * 1.62
            tip_x = cx + math.cos(rad) * length
            tip_y = cy + math.sin(rad) * length
            pen.ellipse((min(cx, tip_x) - r * 0.34, min(cy, tip_y) - r * 0.20,
                         max(cx, tip_x) + r * 0.34, max(cy, tip_y) + r * 0.20),
                        fill=shade(0.90 if angle < 270 else 1.02))
            pen.ellipse((min(cx, tip_x) - r * 0.34, min(cy, tip_y) - r * 0.20,
                         max(cx, tip_x) + r * 0.34, max(cy, tip_y) + r * 0.20),
                        outline=shade(0.68), width=max(2, int(r * 0.05)))
    elif kind == "eggs":
        # Three eggs, the middle one cracked into a cup.
        for offset_x in (-0.78, 0.02, 0.80):
            rr = r * 0.52
            ex, ey = cx + r * offset_x, cy - r * 0.10
            pen.ellipse((ex - rr * 0.80, ey - rr, ex + rr * 0.80, ey + rr),
                        fill=lighter(1.06), outline=shade(0.72), width=max(2, int(r * 0.04)))
        pen.rectangle((cx - r * 0.46, cy + r * 0.52, cx + r * 0.46, cy + r * 1.36),
                      fill=shade(0.88))
        pen.ellipse((cx - r * 0.30, cy + r * 0.24, cx + r * 0.30, cy + r * 0.86),
                    fill=(240, 186, 44))
    elif kind == "carrot":
        # Tapered bodies fanning downward. Screen angles around 90 degrees
        # point down; the earlier 250-294 range pointed up and drew the roots
        # outside the frame.
        for angle, scale in ((64, 1.0), (90, 0.90), (116, 0.80)):
            rad = math.radians(angle)
            length = r * 1.45 * scale
            tip_x = cx + math.cos(rad) * length
            tip_y = cy + math.sin(rad) * length
            width = r * 0.32 * scale
            pen.polygon([(cx + math.cos(rad - 1.57) * width, cy + math.sin(rad - 1.57) * width),
                         (cx + math.cos(rad + 1.57) * width, cy + math.sin(rad + 1.57) * width),
                         (tip_x, tip_y)],
                        fill=shade(0.94 if scale < 1 else 1.04))
            # Green fronds at the wide end, opposite the tip.
            frond = r * 0.92 * scale
            back = rad + math.pi
            pen.line((cx + math.cos(back) * r * 0.10, cy + math.sin(back) * r * 0.10,
                      cx + math.cos(back) * frond, cy + math.sin(back) * frond),
                     fill=(74, 150, 82), width=max(4, int(r * 0.13)))
    elif kind == "apple":
        _sphere(pen, int(cx), int(cy + r * 0.12), int(r * 1.00), shade(0.94), lighter(1.30))
        pen.line((cx, cy - r * 0.80, cx + r * 0.16, cy - r * 1.34), fill=shade(0.58),
                 width=max(3, int(r * 0.10)))
        pen.ellipse((cx + r * 0.06, cy - r * 1.62, cx + r * 0.74, cy - r * 1.10),
                    fill=(74, 150, 82))
    elif kind == "banana":
        for offset in (-0.26, 0.26):
            pts = []
            for step in range(21):
                t = step / 20
                ang = math.radians(30 + 150 * t)
                pts.append((cx + math.cos(ang) * r * (1.34 + offset),
                            cy + math.sin(ang) * r * (1.02 + offset)))
            pen.line(pts, fill=shade(1.0 if offset > 0 else 0.88),
                     width=max(6, int(r * 0.32)), joint="curve")
    elif kind == "chili":
        for angle, scale in ((300, 1.0), (330, 0.82), (18, 0.90)):
            rad = math.radians(angle)
            length = r * 1.90 * scale
            pen.polygon([(cx + math.cos(rad - 0.30) * r * 0.24, cy + math.sin(rad - 0.30) * r * 0.24),
                         (cx + math.cos(rad + 0.30) * r * 0.24, cy + math.sin(rad + 0.30) * r * 0.24),
                         (cx + math.cos(rad) * length, cy + math.sin(rad) * length)],
                        fill=shade(0.96 if scale < 1 else 1.06))
    elif kind == "rice":
        # An open sack with a grain mound.
        pen.polygon([(cx - r * 1.16, cy + r * 0.30), (cx - r * 0.94, cy + r * 1.24),
                     (cx + r * 0.94, cy + r * 1.24), (cx + r * 1.16, cy + r * 0.30)],
                    fill=shade(0.86))
        pen.ellipse((cx - r * 1.20, cy - r * 0.42, cx + r * 1.20, cy + r * 0.66),
                    fill=lighter(1.10))
    elif kind in ("bottle", "jar", "carton"):
        body = lighter(1.10)
        if kind == "bottle":
            pen.rectangle((cx - r * 0.44, cy - r * 1.28, cx + r * 0.44, cy + r * 0.10), fill=body)
            pen.rectangle((cx - r * 0.20, cy - r * 1.62, cx + r * 0.20, cy - r * 1.24), fill=body)
            pen.rectangle((cx - r * 0.24, cy - r * 1.74, cx + r * 0.24, cy - r * 1.56),
                          fill=shade(0.66))
            pen.rectangle((cx - r * 1.02, cy - r * 0.26, cx + r * 1.02, cy + r * 1.20),
                          fill=body)
        elif kind == "jar":
            pen.rectangle((cx - r * 0.82, cy - r * 0.70, cx + r * 0.82, cy + r * 1.16), fill=body)
            pen.rectangle((cx - r * 0.92, cy - r * 1.22, cx + r * 0.92, cy - r * 0.66),
                          fill=shade(0.72))
        else:
            pen.polygon([(cx - r * 0.86, cy - r * 0.94), (cx + r * 0.86, cy - r * 0.94),
                         (cx + r * 0.98, cy + r * 1.18), (cx - r * 0.98, cy + r * 1.18)],
                        fill=body)
            pen.polygon([(cx - r * 0.86, cy - r * 0.94), (cx, cy - r * 1.60),
                         (cx + r * 0.86, cy - r * 0.94)], fill=shade(0.80))
    elif kind == "bread":
        pen.ellipse((cx - r * 1.28, cy - r * 0.66, cx + r * 1.28, cy + r * 0.90), fill=lighter(1.12))
        for offset in (-0.62, 0.0, 0.62):
            pen.arc((cx + r * (offset - 0.34), cy - r * 0.30,
                     cx + r * (offset + 0.34), cy + r * 0.50),
                    start=200, end=340, fill=shade(0.70), width=max(3, int(r * 0.09)))
    elif kind == "meat":
        pen.ellipse((cx - r * 1.14, cy - r * 0.74, cx + r * 1.14, cy + r * 0.74), fill=lighter(1.14))
        pen.ellipse((cx - r * 0.72, cy - r * 0.42, cx + r * 0.72, cy + r * 0.42),
                    fill=lighter(0.92))
    elif kind == "beans":
        for row in range(3):
            for col in range(4):
                bx = cx - r * 0.90 + col * r * 0.60
                by = cy - r * 0.66 + row * r * 0.60
                pen.ellipse((bx - r * 0.26, by - r * 0.26, bx + r * 0.26, by + r * 0.26),
                            fill=shade(0.92 + 0.06 * ((row + col) % 3)))
    elif kind == "onion":
        _sphere(pen, int(cx), int(cy + r * 0.14), int(r * 1.02), shade(0.90), lighter(1.22))
        for offset in (-0.42, 0.0, 0.42):
            pen.arc((cx + r * offset - r * 0.34, cy - r * 0.80,
                     cx + r * offset + r * 0.34, cy + r * 0.60),
                    start=250, end=290, fill=shade(0.66), width=max(2, int(r * 0.07)))
    else:
        _sphere(pen, int(cx), int(cy), int(r), shade(1.0), lighter(1.24))


# --------------------------------------------------------------------------
# Frame composition
# --------------------------------------------------------------------------

def rounded_plate(pen: ImageDraw.ImageDraw, box: tuple, radius: int,
                  fill: tuple | None, outline: tuple | None = None) -> None:
    """A rounded rectangle. Passing fill=None draws the outline only."""
    pen.rounded_rectangle(box, radius=radius, fill=fill, outline=outline)


def render(product: Product, out_path: str, square: bool = False) -> dict:
    """
    Render one product frame to out_path.

    square=True produces the 1000x1000 catalogue image used by the product
    card. square=False produces the 1200x630 blog-cover proportions.
    """
    name = strip_accents(product.name)
    badge = strip_accents(product.badge)
    tagline = strip_accents(product.tagline)

    width, height = (SQUARE, SQUARE) if square else (CANVAS_W, CANVAS_H)

    canvas = vertical_gradient(width, height, BG_TOP, BG_BOTTOM).convert("RGBA")
    canvas = add_dot_grid(canvas)
    canvas = add_vignette(canvas)

    if square:
        # No left scrim: the art is centred and the benefit line sits below it.
        # The motif fills the upper two thirds because with no headline on the
        # frame it is the only subject, and a small motif left a wide empty
        # band between the picture and the text.
        art_box = (int(width * 0.02), int(height * 0.06),
                   int(width * 0.98), int(height * 0.62))
    else:
        canvas = add_left_scrim(canvas)
        art_box = (int(width * 0.50), int(height * 0.04),
                   int(width * 0.99), int(height * 0.96))

    # The product name is NOT drawn on the square catalogue frame. The card
    # already prints the name directly underneath, so baking it into the image
    # printed it twice and any future rename would leave the stale wording
    # inside the picture. Only the badge and the pack size stay, because those
    # are not repeated by the card. The 16:9 cover layout keeps its headline
    # because it is used as a standalone banner.

    # The motif is drawn AFTER the vignette and the scrim. Compositing it
    # earlier let the scrim desaturate the art and turned a bright orange
    # carrot brown.
    art = Image.new("RGBA", canvas.size, (0, 0, 0, 0))
    draw_motif(ImageDraw.Draw(art), product.motif, art_box, product.tint)
    art = art.filter(ImageFilter.GaussianBlur(1.6))
    canvas = Image.alpha_composite(canvas, art)

    pen = ImageDraw.Draw(canvas)
    left = 64

    # Badge pill, radius = half the height, the same shape as the reference.
    badge_font = load_font("bold", 26)
    badge_w = text_width(pen, badge, badge_font) + 56
    badge_h = 52
    badge_y = 56
    rounded_plate(pen, (left, badge_y, left + badge_w, badge_y + badge_h),
                  badge_h // 2, ACCENT_GREEN)
    pen.ellipse((left + 18, badge_y + badge_h // 2 - 7,
                 left + 32, badge_y + badge_h // 2 + 7), fill=TEXT_WHITE)
    pen.text((left + 44, badge_y + badge_h // 2), badge, font=badge_font,
             fill=TEXT_WHITE, anchor="lm")

    # Title block. Omitted entirely on the square frame; see the note above.
    if not square:
        title_font = load_font("bold", 62)
        title_lines = wrap_text(pen, name, title_font, int(width * 0.52), 2)
        line_height = 72
        title_top = badge_y + badge_h + 34

        for index, line in enumerate(title_lines):
            pen.text((left, title_top + index * line_height), line,
                     font=title_font, fill=TEXT_WHITE)

        accent_top = title_top + len(title_lines) * line_height + 6
        pen.rounded_rectangle((left, accent_top, left + 92, accent_top + 7),
                              radius=4, fill=ACCENT_AMBER)
        subtitle_font = load_font("regular", 27)
        pen.text((left, accent_top + 30), tagline, font=subtitle_font, fill=TEXT_MUTED)
    else:
        # A short benefit line under the art keeps the frame from looking bare
        # without repeating what the card already says.
        subtitle_font = load_font("regular", 30)
        sub_lines = wrap_text(pen, tagline, subtitle_font, int(width * 0.78), 2)
        for index, line in enumerate(sub_lines):
            pen.text((left, int(height * 0.62) + index * 38), line,
                     font=subtitle_font, fill=TEXT_MUTED)
        title_lines = sub_lines

    # Pack size chip, top right of the frame.
    #
    # Semi-transparent fills must go through their own layer and be alpha
    # composited. Drawing (255,255,255,34) straight onto the RGBA canvas
    # overwrites the alpha channel instead of blending, which made the chip
    # vanish completely.
    unit_font = load_font("bold", 24)
    unit_text = product.unit
    unit_w = text_width(pen, unit_text, unit_font) + 40
    unit_h = 44
    unit_x = width - 56 - unit_w
    unit_y = badge_y + (badge_h - unit_h) // 2
    chips = Image.new("RGBA", canvas.size, (0, 0, 0, 0))
    chip_pen = ImageDraw.Draw(chips)
    rounded_plate(chip_pen, (unit_x, unit_y, unit_x + unit_w, unit_y + unit_h),
                  unit_h // 2, (255, 255, 255, 46))
    rounded_plate(chip_pen, (unit_x, unit_y, unit_x + unit_w, unit_y + unit_h),
                  unit_h // 2, None, outline=(255, 255, 255, 90))
    canvas = Image.alpha_composite(canvas, chips)
    pen = ImageDraw.Draw(canvas)
    pen.text((unit_x + unit_w / 2, unit_y + unit_h / 2), unit_text,
             font=unit_font, fill=TEXT_WHITE, anchor="mm")

    # Brand lockup, bottom left: white rounded plate plus wordmark.
    plate_x, plate_y = left, height - 76
    plate_h = 54
    mark_font = load_font("bold", 28)
    tag_font = load_font("regular", 18)
    mark_text_w = text_width(pen, BRAND_WORD, mark_font)
    # Size the plate to its contents instead of a hard-coded width, otherwise a
    # wider tagline spills past the white plate.
    plate_w = int(26 + mark_text_w + 20 + text_width(pen, BRAND_TAGLINE, tag_font) + 26)
    rounded_plate(pen, (plate_x, plate_y, plate_x + plate_w, plate_y + plate_h),
                  plate_h // 2, (255, 255, 255, 245))
    pen.text((plate_x + 26, plate_y + plate_h / 2), BRAND_WORD,
             font=mark_font, fill=(12, 62, 46), anchor="lm")
    pen.text((plate_x + 26 + mark_text_w + 20, plate_y + plate_h / 2), BRAND_TAGLINE,
             font=tag_font, fill=(64, 96, 84), anchor="lm")

    flat = canvas.convert("RGB")
    flat.save(out_path, "JPEG", quality=86, optimize=True, progressive=True)
    return {
        "file": out_path,
        "slug": product.slug,
        "bytes": os.path.getsize(out_path),
        "title_lines": len(title_lines),
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__,
                                     formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--out", default="public/uploads/product",
                        help="output directory (default: public/uploads/product)")
    parser.add_argument("--only", default="",
                        help="comma separated slugs to render instead of all")
    parser.add_argument("--square", action="store_true",
                        help="render the square catalogue size used by the product card")
    parser.add_argument("--dry-run", action="store_true",
                        help="list what would be written and exit")
    args = parser.parse_args()

    selected = PRODUCTS
    if args.only.strip():
        wanted = {s.strip() for s in args.only.split(",") if s.strip()}
        selected = [p for p in PRODUCTS if p.slug in wanted]
        missing = wanted - {p.slug for p in selected}
        if missing:
            sys.stderr.write("unknown slug(s): %s\n" % ", ".join(sorted(missing)))

    shape = "%dx%d" % (SQUARE, SQUARE) if args.square else "%dx%d" % (CANVAS_W, CANVAS_H)

    if args.dry_run:
        for product in selected:
            print("  would write %s/%s.jpg  (%s, %s)"
                  % (args.out, product.slug, product.name, product.unit))
        print("  shape: %s, total: %d file(s)" % (shape, len(selected)))
        return 0

    os.makedirs(args.out, exist_ok=True)
    written = []
    for product in selected:
        written.append(render(product, os.path.join(args.out, product.slug + ".jpg"),
                              square=args.square))

    total_bytes = sum(item["bytes"] for item in written)
    print("  wrote %d file(s) to %s" % (len(written), args.out))
    print("  total %.1f MB, average %.0f KB (reference average: 137 KB)"
          % (total_bytes / 1048576, total_bytes / len(written) / 1024))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())