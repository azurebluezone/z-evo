#!/usr/bin/env python3
"""
Split a FreshMart design export into one Blade partial per landmark block.

Why this exists
---------------
The design sources (work/2.md, work/*.html, the Stitch code.html files) are
single flat documents. Every page repeats the same header, footer, filter
aside and trust strip, and each one carries its own marker: data-section,
data-purpose or an aria-label. Copy-pasting them per page is how the four
variants of the header drifted apart in the first place, so this tool cuts the
document on those markers instead.

It writes one .blade.php file per block plus a manifest, and it never edits the
source file.

Usage
-----
  split   cut every landmark into resources/views/frontend/fm/sections/design/
  extract cut ONE element out of the document, chosen by id / data-section /
          data-purpose / aria-label - this is how each AI control becomes its
          own component.

Examples
--------
  python3 tools/split_design_sections.py split work/2.md --out /tmp/cut
  python3 tools/split_design_sections.py extract work/2.md --id global-voice-search-btn
  python3 tools/split_design_sections.py extract work/2.md --data-section shop-header
  python3 tools/split_design_sections.py split work/2.md --dry-run

Exit codes: 0 ok, 1 nothing matched / bad input.
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path

# Elements that never have a closing tag. Getting this wrong makes the scanner
# treat every following block as nested and the cut lands in the wrong place.
VOID = {
    "area", "base", "br", "col", "embed", "hr", "img", "input", "link",
    "meta", "param", "source", "track", "wbr", "path", "circle", "rect",
    "line", "polygon", "polyline", "ellipse", "use", "stop",
}

# Tags we are willing to cut on. Anything else is treated as content.
LANDMARK_TAGS = {"header", "footer", "section", "aside", "nav", "main"}

# Attribute whose value names the block, best first.
NAMING_ATTRS = ("data-section", "data-purpose", "aria-label", "id")


class Tag:
    __slots__ = ("name", "start", "end", "attrs_raw", "self_closing", "is_closing")

    def __init__(self, name, start, end, attrs_raw, self_closing, is_closing):
        self.name = name
        self.start = start
        self.end = end          # index just past the closing ">"
        self.attrs_raw = attrs_raw
        self.self_closing = self_closing
        self.is_closing = is_closing

    @property
    def open_tag(self) -> str:
        return self.attrs_raw

    def attr(self, wanted: str) -> str | None:
        """Read one attribute value straight off the raw tag text."""
        pattern = r'\b' + re.escape(wanted) + r'\s*=\s*"([^"]*)"' \
            if wanted in ("data-section", "data-purpose", "aria-label", "id") \
            else r'\b' + re.escape(wanted) + r'\s*=\s*([^\s">]+)'
        m = re.search(pattern, self.attrs_raw)
        return m.group(1) if m else None


def scan_tags(text: str) -> list[Tag]:
    """
    Every start AND end tag in document order, with correct byte offsets.

    Two traps this handles:
      - a regex alone is not enough, because attribute values legitimately
        contain ">" (style="... > ..."), so quoting is tracked while reading;
      - the opening-tag regex does not match "</button>", so closing tags have
        to be matched explicitly or every block looks unbalanced and runs to
        the end of the file.
    """
    tags: list[Tag] = []
    i, n = 0, len(text)
    while i < n:
        lt = text.find("<", i)
        if lt == -1:
            break
        if text.startswith("<!--", lt):
            close = text.find("-->", lt)
            i = (close + 3) if close != -1 else n
            continue
        if text[lt + 1:lt + 2] in ("!", "?"):
            close = text.find(">", lt)
            i = (close + 1) if close != -1 else n
            continue

        m = re.match(r"<\s*(/?)\s*([A-Za-z][A-Za-z0-9:-]*)", text[lt:])
        if not m:
            i = lt + 1
            continue
        is_closing = m.group(1) == "/"
        name = m.group(2).lower()

        j = lt + m.end()
        quote = None
        while j < n:
            c = text[j]
            if quote:
                if c == quote:
                    quote = None
            elif c in ("'", '"'):
                quote = c
            elif c == ">":
                break
            j += 1
        if j >= n:
            break
        raw = text[lt:j + 1]
        self_closing = (not is_closing) and (raw.endswith("/>") or name in VOID)
        tags.append(Tag(name, lt, j + 1, raw, self_closing, is_closing))
        i = j + 1
    return tags


def block_extent(tags: list[Tag], idx: int, text_len: int) -> tuple[int, int]:
    """
    Extent of the element that OPENS at tags[idx], as (start, end).

    A void or self-closing element is its own block. Otherwise walk forward and
    return when the matching close tag appears at the same nesting depth.
    """
    open_tag = tags[idx]
    if open_tag.self_closing:
        return open_tag.start, open_tag.end

    depth = 1
    for k in range(idx + 1, len(tags)):
        t = tags[k]
        if t.name != open_tag.name or t.self_closing:
            continue
        if t.is_closing:
            depth -= 1
            if depth == 0:
                return open_tag.start, t.end
        else:
            depth += 1
    # Unbalanced (the design files are fragments) - fall back to the next
    # landmark of the same level rather than swallowing the rest of the file.
    for k in range(idx + 1, len(tags)):
        if tags[k].name in LANDMARK_TAGS and not tags[k].self_closing:
            return open_tag.start, tags[k].start
    return open_tag.start, text_len


def slugify(value: str) -> str:
    value = re.sub(r"[^a-z0-9]+", "-", value.strip().lower()).strip("-")
    return value or "block"


def name_block(tag: Tag) -> str:
    """Human-meaningful, filesystem-safe name for a landmark."""
    for attr in NAMING_ATTRS:
        val = tag.attr(attr)
        if val:
            base = slugify(val)
            # data-purpose="footer-links" on a <footer> reads better as a footer
            if attr == "data-purpose" and tag.name in ("footer", "header"):
                return f"{tag.name}-{base}"
            return base
    return tag.name


def top_level_landmarks(tags: list[Tag], text_len: int):
    """
    Landmarks that are not nested inside another landmark.

    The design files are fragments, so depth is measured against the other
    landmarks rather than the whole document: a <section> inside an <aside>
    belongs to that aside, not to the page.
    """
    depth = 0
    seen_open: dict[str, int] = {}
    for idx, tag in enumerate(tags):
        if tag.name not in LANDMARK_TAGS:
            continue
        if tag.is_closing:
            if seen_open.get(tag.name, 0) > 0:
                seen_open[tag.name] -= 1
                depth -= 1
            continue
        if tag.self_closing:
            continue
        if depth == 0:
            yield idx, name_block(tag)
        seen_open[tag.name] = seen_open.get(tag.name, 0) + 1
        depth += 1


def find_element(tags: list[Tag], text_len: int, *, by_id=None,
                 by_data_section=None, by_data_purpose=None, by_aria_label=None):
    """Locate the first element matching one selector-ish criterion."""
    wanted = {
        "id": by_id,
        "data-section": by_data_section,
        "data-purpose": by_data_purpose,
        "aria-label": by_aria_label,
    }
    wanted = {k: v for k, v in wanted.items() if v}
    if not wanted:
        raise SystemExit("extract needs one of --id / --data-section / --data-purpose / --aria-label")

    for idx, tag in enumerate(tags):
        if tag.is_closing:
            continue
        ok = True
        for attr, val in wanted.items():
            if tag.attr(attr) != val:
                ok = False
                break
        if ok:
            return idx, block_extent(tags, idx, text_len)
    return None, None


BLADE_HEADER = (
    "{{--\n"
    "    {title}\n"
    "    {source}\n"
    "    Cut by tools/split_design_sections.py - edit the design source, then re-run,\n"
    "    rather than hand-editing this file.\n"
    "--}}\n"
)


def render_header(title: str, source: str) -> str:
    # Built with replace(), not str.format(): format() would turn the Blade
    # comment opener "{{--" into "{--", which is not a Blade comment at all.
    return (BLADE_HEADER
            .replace("{title}", title)
            .replace("{source}", source))


def write_partial(path: Path, body: str, title: str, source: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    if not body.lstrip().startswith("{{--"):
        body = render_header(title, source) + body.lstrip("\n")
    path.write_text(body.rstrip() + "\n", encoding="utf-8")


def cmd_split(args) -> int:
    src = Path(args.source)
    text = src.read_text(encoding="utf-8", errors="replace")
    tags = scan_tags(text)
    out_dir = Path(args.out)
    manifest = []

    for idx, name in top_level_landmarks(tags, len(text)):
        start, end = block_extent(tags, idx, len(text))
        body = text[start:end].rstrip()
        fname = f"{name}.blade.php"
        manifest.append({
            "file": fname,
            "tag": tags[idx].name,
            "lines": body.count("\n") + 1,
            "bytes": len(body),
        })
        if not args.dry_run:
            write_partial(out_dir / fname, body, f"Design block: {name}", str(src))

    print(f"cut {len(manifest)} block(s) from {src}")
    for m in manifest:
        print(f"  {m['file']:44} <{m['tag']}> {m['lines']:>4} lines  {m['bytes']:>6} B")
    if not args.dry_run:
        (out_dir / "manifest.json").write_text(
            json.dumps({"source": str(src), "blocks": manifest}, indent=2) + "\n",
            encoding="utf-8")
        print(f"manifest -> {out_dir / 'manifest.json'}")
    return 0 if manifest else 1


def cmd_extract(args) -> int:
    src = Path(args.source)
    text = src.read_text(encoding="utf-8", errors="replace")
    tags = scan_tags(text)

    _, extent = find_element(
        tags, len(text),
        by_id=args.id,
        by_data_section=args.data_section,
        by_data_purpose=args.data_purpose,
        by_aria_label=args.aria_label,
    )
    if not extent:
        crit = args.id or args.data_section or args.data_purpose or args.aria_label
        print(f"no element matched {crit!r} in {src}", file=sys.stderr)
        return 1

    start, end = extent
    body = text[start:end].rstrip()

    if args.stdout:
        print(body)
        return 0

    name = args.name or slugify(
        args.id or args.data_section or args.data_purpose or args.aria_label or "block")
    out = Path(args.out or "tools/_split") / f"{name}.blade.php"
    if not args.dry_run:
        write_partial(out, body, f"Design block: {name}", str(src))
        print(f"wrote {out}  ({body.count(chr(10)) + 1} lines, {len(body)} B)")
    else:
        print(f"[dry-run] would write {out}  ({body.count(chr(10)) + 1} lines, {len(body)} B)")
    return 0


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__,
                                 formatter_class=argparse.RawDescriptionHelpFormatter)
    sub = ap.add_subparsers(dest="cmd", required=True)

    p_split = sub.add_parser("split", help="cut every top-level landmark")
    p_split.add_argument("source")
    p_split.add_argument("--out", default="resources/views/frontend/fm/sections/design")
    p_split.add_argument("--dry-run", action="store_true")
    p_split.set_defaults(func=cmd_split)

    p_ext = sub.add_parser("extract", help="cut one element into its own partial")
    p_ext.add_argument("source")
    p_ext.add_argument("--id")
    p_ext.add_argument("--data-section", dest="data_section")
    p_ext.add_argument("--data-purpose", dest="data_purpose")
    p_ext.add_argument("--aria-label", dest="aria_label")
    p_ext.add_argument("--name")
    p_ext.add_argument("--out")
    p_ext.add_argument("--stdout", action="store_true")
    p_ext.add_argument("--dry-run", action="store_true")
    p_ext.set_defaults(func=cmd_extract)

    args = ap.parse_args()
    if not Path(args.source).is_file():
        print(f"no such file: {args.source}", file=sys.stderr)
        return 1
    return args.func(args)


if __name__ == "__main__":
    sys.exit(main())