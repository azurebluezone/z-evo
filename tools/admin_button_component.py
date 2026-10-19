#!/usr/bin/env python3
"""
admin_button_component.py - rewrite the admin's 683 hand-written button class
lists as <x-admin.button> calls.

    python3 tools/admin_button_component.py           # report only
    python3 tools/admin_button_component.py --apply   # rewrite

WHAT IT GUARANTEES
------------------
The component emits the same class vocabulary the views already used, so a
rewritten call site renders the same tag. To keep that promise honest the
script does not "clean up" anything: every class it does not recognise is
copied through verbatim into the component's class prop, in its original
order. A button keeps its delete-confirm hook, its form-control, its float.

    btn btn-primary btn-square waves-effect delete-confirm
      -> <x-admin.button variant="primary" shape="square"
                         class="delete-confirm" ...>

WHAT IT REFUSES TO TOUCH
------------------------
  * <button class="btn-close">  - Bootstrap's own dismiss button, not an action
  * buttons with no class at all
  * class strings containing nothing this script recognises, because guessing
    at those would be inventing a design decision
  * the layouts themselves (backEnd/layouts/**), which own the shell

Both modes print what was held back and why, so a conversion is never silent.
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

# Filled in main(): the app must be booted before any blade can be compiled.
BLADE = None
BE = ROOT / "resources" / "views" / "backEnd"

SKIP_GLOBS = ("layouts/",)

# NOTE the hyphens inside the prefix group: the class is
# "btn-outline-secondary" = btn- + outline- + secondary. Leaving them out
# matches "btn-primary" only, and quietly leaves 100+ real buttons unrecognised.
VARIANT_RE = re.compile(r"^btn-(outline-|text-)?(primary|secondary|success|danger|warning|info|light|dark|link)$")
SIZE_RE = {"btn-sm": "sm", "btn-lg": "lg"}
SHAPE_RE = {"btn-square": "square", "btn-icon": "icon"}
RIPPLE = "waves-effect"

# Why this is hand-written and not `<button\b([^>]*)>`:
#
# A blade route() call is an attribute value that CONTAINS a '>' -
#   action="{{ route('x.update', $model->id) }}"
# so [^>]* stops in the middle of the expression and the "button" the regex
# matches runs across the form tag and swallows real markup. Five admin edit
# pages were already damaged in the repository by exactly that mistake.
#
# This scanner therefore walks the tag character by character and only accepts
# a '>' that is outside a quoted attribute value.
def find_button_spans(src: str) -> list[tuple[int, int, str, str]]:
    """(start, end, attrs, body) for each <button>...</button>."""
    spans = []
    pos = 0
    while True:
        m = re.compile(r"<button\b").search(src, pos)
        if not m:
            return spans
        i = m.end()
        quote = None
        while i < len(src):
            ch = src[i]
            if quote:
                if ch == quote:
                    quote = None
            elif ch in "\"'":
                quote = ch
            elif ch == ">":
                break
            i += 1
        if i >= len(src):
            return spans
        attrs, body_start = src[m.end():i], i + 1
        close = src.find("</button>", body_start)
        if close == -1:
            return spans
        spans.append((m.start(), close + len("</button>"), attrs, src[body_start:close]))
        pos = close + len("</button>")
CLASS_RE = re.compile(r'class="([^"]*)"')
ATTR_RE = re.compile(r'([a-zA-Z_:@\-]+)\s*=\s*"([^"]*)"')


def parse(classes: list[str]) -> tuple[dict, list[str]] | None:
    """class tokens -> (component props, extras kept verbatim)."""
    props: dict[str, str] = {}
    extras: list[str] = []
    ripple = False
    shapes: list[str] = []

    for token in classes:
        if token == "btn":
            continue
        if token in SIZE_RE:
            props.setdefault("size", SIZE_RE[token])
            continue
        if token in SHAPE_RE:
            shapes.append(SHAPE_RE[token])
            continue
        if token == RIPPLE:
            ripple = True
            continue
        m = VARIANT_RE.match(token)
        if m:
            prefix = m.group(1) or ""
            name = m.group(2)
            props.setdefault("variant", prefix + name)
            continue
        extras.append(token)

    # A ripple on its own is not enough to make something a button. The layout
    # toggles in this template (button-menu-mobile) carry "waves-effect
    # waves-light" on a class of their own; converting one would bolt .btn onto
    # a widget that never had button padding or a background. A class that
    # names no variant, no size and no shape stays a plain <button>.
    if not props and not shapes:
        return None
    if shapes:
        # "btn-icon btn-square" is one call with a two-word shape.
        props["shape"] = " ".join(shapes)
    props["ripple"] = "true" if ripple else "false"
    return props, extras


def compiles(path: Path) -> bool:
    """Does the blade file still compile after the rewrite?

    The converter edits 500+ files blind. A syntax check per file is the only
    thing standing between a typo here and a 500 on a screen a shopkeeper is
    using, so it is not optional: a file that fails is written back as it was.
    """
    try:
        from subprocess import run, DEVNULL
        from tempfile import NamedTemporaryFile

        source = path.read_text(encoding="utf-8")
        compiled = BLADE.compileString(source)
        with NamedTemporaryFile("w", suffix=".php", delete=False) as tmp:
            tmp.write(compiled)
            name = tmp.name
        result = run(["php", "-l", name], capture_output=True, text=True)
        Path(name).unlink(missing_ok=True)
        return result.returncode == 0
    except Exception:
        return False


def subprocess_run(cmd: list[str]):
    from subprocess import run
    return run(cmd, capture_output=True, text=True)


def verify_many(paths: list[Path]) -> list[Path]:
    """Compile every rewritten blade in ONE php process; return the failures.

    A python-side compile is impossible - the blade compiler lives inside the
    booted framework - so the work is done by a throwaway php script that
    writes each compiled file next to itself in a temp dir and runs `php -l`.
    """
    if not paths:
        return []

    import json
    from tempfile import TemporaryDirectory

    with TemporaryDirectory() as tmp:
        tmpdir = Path(tmp)
        listfile = tmpdir / "files.json"
        listfile.write_text(json.dumps([str(p) for p in paths]))

        runner = tmpdir / "verify.php"
        runner.write_text(
            "<?php\n"
            f"require '{ROOT}/vendor/autoload.php';\n"
            f"$app = require '{ROOT}/bootstrap/app.php';\n"
            "$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();\n"
            "$blade = app('blade.compiler');\n"
            f"$out = {tmpdir.as_posix()!r};\n"
            f"$files = json_decode(file_get_contents({str(listfile)!r}), true);\n"
            "foreach ($files as $i => $file) {\n"
            "    try {\n"
            "        file_put_contents($out . '/' . $i . '.php', $blade->compileString(file_get_contents($file)));\n"
            "    } catch (Throwable $e) {\n"
            "        echo $file, PHP_EOL;\n"
            "    }\n"
            "}\n"
        )

        compile_proc = subprocess_run(["php", str(runner)])
        broken = {Path(line) for line in compile_proc.stdout.split() if line.strip()}

        for compiled in tmpdir.glob("*.php"):
            lint = subprocess_run(["php", "-l", str(compiled)])
            if lint.returncode != 0:
                broken.add(Path(paths[int(compiled.stem)].resolve()))

        return sorted(broken)


def blade_files() -> list[Path]:
    out = []
    for path in sorted(BE.rglob("*.blade.php")):
        rel = path.relative_to(BE).as_posix()
        if any(rel.startswith(prefix) for prefix in SKIP_GLOBS):
            continue
        out.append(path)
    return out


def convert(path: Path, apply: bool, stats: dict) -> str:
    src = path.read_text(encoding="utf-8")
    spans = find_button_spans(src)
    if not spans:
        return src

    out = []
    cursor = 0
    for start, end, attrs, body in spans:
        out.append(src[cursor:start])
        out.append(rebuild(attrs, body, apply, stats))
        cursor = end
    out.append(src[cursor:])
    return "".join(out)


def rebuild(attrs: str, body: str, apply: bool, stats: dict) -> str:
    if True:

        cm = CLASS_RE.search(attrs)
        if not cm:
            stats["skipped_no_class"] += 1
            return "<button" + attrs + ">" + body + "</button>"

        classes = cm.group(1).split()
        if "btn-close" in classes:
            stats["skipped_bootstrap"] += 1
            return "<button" + attrs + ">" + body + "</button>"

        parsed = parse(classes)
        if parsed is None:
            stats["skipped_unrecognised"] += 1
            stats.setdefault("unrecognised", []).append(cm.group(1)[:80])
            return "<button" + attrs + ">" + body + "</button>"

        props, extras = parsed
        stats["converted"] += 1

        if not apply:
            return "<button" + attrs + ">" + body + "</button>"

        # Keep every other attribute, in its original order.
        others = [m.group(0) for m in ATTR_RE.finditer(attrs) if m.group(1) != "class"]

        # Only carry a variant when one was recognised. Defaulting to "primary"
        # would paint a second colour on top of a project's own .btn-blue.
        parts = []
        if "variant" in props:
            parts.append(f'variant="{props["variant"]}"')
        if props.get("size", "md") != "md":
            parts.append(f'size="{props["size"]}"')
        if props.get("shape"):
            parts.append(f'shape="{props["shape"]}"')
        if props.get("ripple") == "true":
            parts.append('ripple="true"')
        if extras:
            parts.append('class="' + " ".join(extras) + '"')
        parts += others

        return "<x-admin.button " + " ".join(parts) + ">" + body + "</x-admin.button>"


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--apply", action="store_true")
    args = ap.parse_args()

    stats = {"converted": 0, "skipped_bootstrap": 0,
             "skipped_no_class": 0, "skipped_unrecognised": 0, "unrecognised": []}

    touched, rolled_back = [], []
    for path in blade_files():
        original = path.read_text(encoding="utf-8")
        updated = convert(path, args.apply, stats)
        if updated == original:
            continue
        if args.apply:
            path.write_text(updated, encoding="utf-8")
            touched.append(path.relative_to(ROOT).as_posix())
        touched.append(path.relative_to(ROOT).as_posix())

    if args.apply and touched:
        broken = verify_many([ROOT / f for f in touched])
        for path in broken:
            backup = (ROOT / path).read_text(encoding="utf-8")
            head = subprocess_run(["git", "checkout", "--", str(path.relative_to(ROOT))])
            rolled_back.append(path.relative_to(ROOT).as_posix())

    if rolled_back:
        print(f"\nROLLED BACK (would not compile): {len(rolled_back)}")
        for f in rolled_back:
            print(f"  {f}")

    print(f"mode      : {'APPLY' if args.apply else 'report only'}")
    print(f"converted : {stats['converted']}")
    print(f"skipped   : {stats['skipped_bootstrap']} btn-close, "
          f"{stats['skipped_no_class']} no class, "
          f"{stats['skipped_unrecognised']} unrecognised")
    if stats["unrecognised"]:
        print("\n-- unrecognised class strings (left alone) --")
        for u in sorted(set(stats["unrecognised"])):
            print(f"  {u}")
    print(f"\nfiles touched: {len(touched)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())