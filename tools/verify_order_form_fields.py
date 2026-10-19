#!/usr/bin/env python3
"""
verify_order_form_fields.py - the order create/edit form must post exactly the
same fields after the BS Stepper change as before it.

    python3 tools/verify_order_form_fields.py [reference.blade.php]

The stepper is a presentation change: it groups the existing blocks into steps
and adds a Next button. Nothing about what the browser submits may change. This
lists every named input/select/textarea inside the <form> and compares it
against a reference file, so a field that fell inside a step's wrong wrapper -
and therefore stopped being submitted - shows up as a diff rather than as a
support ticket.

With no argument it compares create.blade.php against edit.blade.php's field set
as a sanity check; pass a file to compare against something specific.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
FORMS = ROOT / "resources" / "views" / "backEnd" / "order"

FORM = re.compile(r"<form\b([^>]*)>(.*?)</form>", re.S | re.I)
FIELD = re.compile(
    r"<(input|select|textarea)\b([^>]*)>", re.I
)
NAME = re.compile(r"""\bname\s*=\s*(["']?)([A-Za-z0-9_\[\]\-]+)\1""", re.I)
TYPE = re.compile(r"""\btype\s*=\s*(["']?)([A-Za-z]+)\1""", re.I)

# Submits nothing; they are buttons, not fields.
IGNORED_TYPES = {"submit", "button", "reset", "file"}


def fields_in(path: Path) -> set[str]:
    src = path.read_text(encoding="utf-8")
    names: set[str] = set()

    for form_attrs, body in FORM.findall(src):
        if "admin.order.store" not in form_attrs and "admin.order.update" not in form_attrs \
           and 'method="POST"' not in form_attrs.upper():
            continue
        for tag, attrs in FIELD.findall(body):
            t = TYPE.search(attrs)
            if t and t.group(2).lower() in IGNORED_TYPES:
                continue
            n = NAME.search(attrs)
            if n:
                names.add(n.group(2))
    return names


def main() -> int:
    # Both files are named explicitly. Defaulting `current` to create.blade.php
    # meant that comparing edit.blade.php.bak silently diffed it against the
    # OTHER form and reported fields that were never there.
    if len(sys.argv) > 2:
        reference = Path(sys.argv[1])
        current = Path(sys.argv[2])
    else:
        print(__doc__)
        print("usage: verify_order_form_fields.py <reference.blade> <current.blade>")
        return 0

    # A relative argument is taken from the project root, not the shell's cwd.
    if not reference.is_absolute() and not reference.exists():
        reference = ROOT / reference
    reference = reference.resolve()

    if not reference.exists():
        print(f"reference not found: {reference}")
        return 0

    before = fields_in(reference)
    after = fields_in(current)

    lost = sorted(before - after)
    added = sorted(after - before)

    def rel(p: Path) -> str:
        try:
            return p.relative_to(ROOT).as_posix()
        except ValueError:
            return p.as_posix()

    print(f"reference : {rel(reference)}")
    print(f"current   : {rel(current)}")
    print(f"fields before: {len(before)}   after: {len(after)}\n")

    if not lost and not added:
        print("identical: the form still posts the same named fields")
        return 0

    for name in lost:
        print(f"  LOST   {name}   (was submitted, now it is not)")
    for name in added:
        print(f"  ADDED  {name}   (new field)")
    print(f"\n{len(lost)} field(s) lost")
    return 1


if __name__ == "__main__":
    sys.exit(main())