#!/usr/bin/env python3
"""Extract inline CSS/JS from campaign Blade templates into cacheable assets.

Each template gets one CSS file and (when it has inline scripts) one JS file
under public/frontend/landing/. The shared responsive.css is linked after the
template CSS. A manifest records the source, byte counts and SHA-256 so drift is
visible.

Blade expressions inside a <style> or inline <script> block are an error: move
the value to a data-* attribute or a CSS custom property first. This keeps the
generated assets static and cacheable.

Usage:
    python3 tools/extract_landing_assets.py            # write assets + rewrite Blade
    python3 tools/extract_landing_assets.py --check    # verify manifest and templates
"""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEW_DIR = ROOT / "resources" / "views" / "templates"
OUT_DIR = ROOT / "public" / "frontend" / "landing"
MANIFEST = OUT_DIR / "manifest.json"

TEMPLATES = [
    "default",
    "herbal-health",
    "sample-template",
    "template-1",
    "template-2",
    "template-3",
]

STYLE_RE = re.compile(r"<style\b[^>]*>(.*?)</style>", re.S | re.I)
SCRIPT_RE = re.compile(r"<script\b(?![^>]*\bsrc=)([^>]*)>(.*?)</script>", re.S | re.I)
BLADE_RE = re.compile(r"@if|@else|@endif|@foreach|@endforeach|@php|@endphp|\{\{|\{!!")


def sha256(text: str) -> str:
    return hashlib.sha256(text.encode("utf-8")).hexdigest()


def header(kind: str, template: str, source: str) -> str:
    return (
        f"/* Generated from {source} by tools/extract_landing_assets.py. */\n"
        f"/* Do not edit this {kind} by hand; edit the Blade source or responsive.css. */\n\n"
        if kind == "CSS"
        else f"// Generated from {source} by tools/extract_landing_assets.py.\n"
        f"// Do not edit this JS by hand; edit the Blade source.\n\n"
    )


def asset_tag(kind: str, filename: str) -> str:
    if kind == "css":
        return (
            '<link rel="stylesheet" '
            f"href=\"{{{{ asset('frontend/landing/{filename}') }}}}"
            f"?v={{{{ filemtime(public_path('frontend/landing/{filename}')) }}}}\">"
        )
    return (
        "<script "
        f"src=\"{{{{ asset('frontend/landing/{filename}') }}}}"
        f"?v={{{{ filemtime(public_path('frontend/landing/{filename}')) }}}}\"></script>"
    )


def extract_template(template: str, write: bool) -> dict:
    source_rel = f"resources/views/templates/{template}.blade.php"
    source_path = ROOT / source_rel
    if not source_path.exists():
        raise SystemExit(f"missing template: {source_rel}")

    blade = source_path.read_text(encoding="utf-8")
    styles = list(STYLE_RE.finditer(blade))
    scripts = list(SCRIPT_RE.finditer(blade))

    for match in styles:
        if BLADE_RE.search(match.group(1)):
            raise SystemExit(
                f"{source_rel}: Blade expression inside <style>; move it to a data attribute "
                "or CSS custom property before extracting."
            )
    for match in scripts:
        if BLADE_RE.search(match.group(2)):
            raise SystemExit(
                f"{source_rel}: Blade expression inside inline <script>; move it to a data-* "
                "attribute before extracting."
            )

    css_file = f"{template}.css"
    js_file = f"{template}.js"
    assets: dict[str, str] = {}
    entry = {"css": False, "js": False, "cssBytes": 0, "jsBytes": 0}

    css_body = "\n\n".join(m.group(1).strip() for m in styles).strip()
    if css_body:
        css = header("CSS", template, source_rel) + css_body + "\n"
        assets[css_file] = css
        entry.update(css=True, cssBytes=len(css_body))

    js_body = "\n\n".join(m.group(2).strip() for m in scripts).strip()
    if js_body:
        js = header("JS", template, source_rel) + js_body + "\n"
        assets[js_file] = js
        entry.update(js=True, jsBytes=len(js_body))

    if write:
        OUT_DIR.mkdir(parents=True, exist_ok=True)

    # Apply every replacement against the ORIGINAL offsets, newest first.
    # Mutating left-to-right shifts later offsets and is how the first version
    # appended the script tag after </html> while leaving the inline block in
    # place.
    operations: list[tuple[int, int, str]] = []
    if styles:
        operations.append(
            (
                styles[0].start(),
                styles[0].end(),
                asset_tag("css", css_file) + "\n    " + asset_tag("css", "responsive.css"),
            )
        )
        for match in styles[1:]:
            operations.append((match.start(), match.end(), ""))
    if scripts:
        operations.append((scripts[0].start(), scripts[0].end(), asset_tag("js", js_file)))
        for match in scripts[1:]:
            operations.append((match.start(), match.end(), ""))

    new_blade = blade
    for start, end, replacement in sorted(operations, key=lambda item: item[0], reverse=True):
        new_blade = new_blade[:start] + replacement + new_blade[end:]

    if write and new_blade != blade:
        source_path.write_text(new_blade, encoding="utf-8")

    return {"entry": entry, "assets": assets, "blade": new_blade, "styles": len(styles), "scripts": len(scripts)}


def check() -> int:
    problems: list[str] = []

    if not MANIFEST.exists():
        print("FAIL: manifest missing; run without --check first.")
        return 1

    manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))

    for name, expected in manifest.get("assets", {}).items():
        path = OUT_DIR / name
        if not path.exists():
            problems.append(f"missing asset: {name}")
            continue
        actual = sha256(path.read_text(encoding="utf-8"))
        if actual != expected:
            problems.append(f"asset drift: {name} (manifest {expected[:12]} != file {actual[:12]})")

    for template in TEMPLATES:
        source_rel = f"resources/views/templates/{template}.blade.php"
        blade = (ROOT / source_rel).read_text(encoding="utf-8")
        if STYLE_RE.search(blade):
            problems.append(f"{source_rel}: inline <style> still present")
        if SCRIPT_RE.search(blade):
            problems.append(f"{source_rel}: inline <script> still present")

        entry = manifest.get("templates", {}).get(template)
        if not entry:
            problems.append(f"{source_rel}: missing manifest entry")
            continue
        if entry.get("css") and f"frontend/landing/{template}.css" not in blade:
            problems.append(f"{source_rel}: missing link to {template}.css")
        if entry.get("css") and "frontend/landing/responsive.css" not in blade:
            problems.append(f"{source_rel}: missing link to responsive.css")
        if entry.get("js") and f"frontend/landing/{template}.js" not in blade:
            problems.append(f"{source_rel}: missing script tag for {template}.js")

    for name in manifest.get("assets", {}):
        asset = OUT_DIR / name
        if asset.exists() and BLADE_RE.search(asset.read_text(encoding="utf-8")):
            problems.append(f"generated asset still contains Blade tokens: {name}")

    if problems:
        print("LANDING_ASSETS_FAIL")
        for problem in problems:
            print(" -", problem)
        return 1

    print(f"LANDING_ASSETS_CHECK_OK ({len(manifest['assets'])} assets, {len(TEMPLATES)} templates)")
    return 0


def write_all() -> int:
    if not (OUT_DIR / "responsive.css").exists():
        raise SystemExit("responsive.css is missing; it is hand-written and must exist before extraction.")

    # Re-running the writer after extraction must not erase the manifest
    # entries: the Blade files no longer have inline blocks, so a naive pass
    # would record css=false and stop checking the existing files.
    previous: dict = {}
    if MANIFEST.exists():
        try:
            previous = json.loads(MANIFEST.read_text(encoding="utf-8")).get("templates", {})
        except json.JSONDecodeError:
            previous = {}

    manifest = {
        "generatedAt": datetime.now(timezone.utc).isoformat(),
        "templates": {},
        "assets": {},
    }
    total_css = 0
    total_js = 0

    for template in TEMPLATES:
        result = extract_template(template, write=True)
        entry = result["entry"]
        old = previous.get(template, {})

        if not entry["css"] and old.get("css"):
            existing = OUT_DIR / f"{template}.css"
            if existing.exists() and f"frontend/landing/{template}.css" in result["blade"]:
                entry["css"] = True
                entry["cssBytes"] = old.get("cssBytes", 0)
                result["assets"][f"{template}.css"] = existing.read_text(encoding="utf-8")

        if not entry["js"] and old.get("js"):
            existing = OUT_DIR / f"{template}.js"
            if existing.exists() and f"frontend/landing/{template}.js" in result["blade"]:
                entry["js"] = True
                entry["jsBytes"] = old.get("jsBytes", 0)
                result["assets"][f"{template}.js"] = existing.read_text(encoding="utf-8")

        manifest["templates"][template] = entry
        for name, content in result["assets"].items():
            path = OUT_DIR / name
            if not path.exists() or path.read_text(encoding="utf-8") != content:
                path.write_text(content, encoding="utf-8")
            manifest["assets"][name] = sha256(content)
        total_css += result["entry"]["cssBytes"]
        total_js += result["entry"]["jsBytes"]
        print(
            f"{template}: style blocks={result['styles']} script blocks={result['scripts']} "
            f"css={result['entry']['cssBytes']}B js={result['entry']['jsBytes']}B"
        )

    responsive = OUT_DIR / "responsive.css"
    manifest["assets"]["responsive.css"] = sha256(responsive.read_text(encoding="utf-8"))
    MANIFEST.write_text(json.dumps(manifest, indent=2) + "\n", encoding="utf-8")

    print(f"assets written: {len(manifest['assets'])}; inline CSS={total_css}B JS={total_js}B")
    print("LANDING_ASSETS_WRITE_OK")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check", action="store_true", help="verify generated assets and Blade links")
    args = parser.parse_args()
    return check() if args.check else write_all()


if __name__ == "__main__":
    raise SystemExit(main())
