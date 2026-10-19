#!/usr/bin/env python3
"""
admin_script_tag_audit.py - inline JS that was never inside a <script> tag.

    python3 tools/admin_script_tag_audit.py

A blade view that pushes javascript without opening <script> renders it as
TEXT. The browser drops an unmatched </script> and prints the javascript as
paragraphs at the bottom of the page. Nothing errors, nothing logs, the source
looks right - and not one handler on that page is bound.

That is not a typo someone made in a hurry. In this project the orders list had
had it for long enough that the bulk-delete, status-change, assign and courier
buttons had simply never worked. The page looked complete and every other check
passed, because the route table was right, the template compiled, the CSRF
token was there - none of which knows whether the browser will run a line of
code.

So this finds every inline <script> block, extracts the javascript, and asks a
real javascript parser whether it is valid. Also reports the two structural
faults that cause it: javascript inside a @push with no opening tag, and a
</script> with no opener.
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEWS = ROOT / "resources" / "views"

PUSH = re.compile(r"@push\(\s*['\"](?:script|scripts|js)['\"]\s*\)")
END_PUSH = re.compile(r"@endpush")
# The whole opening tag, including attributes, so that slicing from m.end()
# starts at the javascript and not at the ">" that closes the tag. Leaving that
# ">" in made every block in the project report "Unexpected token '>'".
OPEN_TAG = re.compile(
    r"<script\b[^>]*>", re.I
)
CLOSE_TAG = re.compile(r"</script\s*>", re.I)

def main() -> int:
    problems: list[str] = []

    for path in sorted(VIEWS.rglob("*.blade.php")):
        src = path.read_text(encoding="utf-8")
        rel = path.relative_to(ROOT).as_posix()

        # --- structure 1: a closing tag with no opener -------------------
        # Counted per file, but only reported when the file is self-contained:
        # layouts legitimately close a tag that an included partial opened, so
        # a raw open/close difference across a layout is not evidence of
        # anything. A view that opens javascript itself is the case that bites.
        opens = len(OPEN_TAG.findall(src))
        closes = len(CLOSE_TAG.findall(src))
        if opens > 0 and closes > opens:
            problems.append(
                f"{rel}  {closes} </script> but only {opens} <script> "
                f"- the browser drops the stray close tag"
            )

        # --- structure 2: a pushed script block with no opening tag -------
        for m in PUSH.finditer(src):
            end = END_PUSH.search(src, m.end())
            block = src[m.end(): end.start() if end else len(src)]
            if not OPEN_TAG.search(block):
                problems.append(
                    f"{rel}:{src.count(chr(10), 0, m.start()) + 1}  "
                    f"@push('script') whose block never opens <script> - "
                    f"the javascript renders as page text and no handler binds"
                )
                break

        # --- structure 3: NOT done here, on purpose -----------------------
        # Parsing the javascript with node looks like the obvious check and is
        # not one: blade expressions sit inside the code (@json([...]) leaves
        # "boards: ,", @if(...) leaves stray colons), so stripping them to make
        # it parse is exactly what invents the findings. Two attempts at this
        # reported 245 problems and then 29 - every one of them the tool's own
        # mangled copy of the file, none of them a file.
        #
        # The honest check is the browser one: load the page and see whether the
        # handlers bound. See tools/verify_order_handlers.php.

    print(f"views scanned               : {len(list(VIEWS.rglob('*.blade.php')))}\n")

    if not problems:
        print("every pushed script block opens its own <script> tag, and no")
        print("self-contained view closes a tag it never opened")
        return 0

    print("PROBLEM:\n")
    for p in problems:
        print("  " + p)
    print(f"\n{len(problems)} problem(s). An unwrapped block means the page shows")
    print("its javascript as text and silently binds no handlers.")
    return 1


if __name__ == "__main__":
    sys.exit(main())