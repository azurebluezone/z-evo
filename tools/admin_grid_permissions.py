#!/usr/bin/env python3
"""
admin_grid_permissions.py - prove no grid endpoint sits outside the permission
check its screen sits behind.

    python3 tools/admin_grid_permissions.py

The admin controllers guard themselves with Spatie's middleware declared as

    $this->middleware('permission:product-list|...', ['only' => ['index', 'show']]);

An `only` list is an allow-list of method names. Adding a new method to a
controller does not add it to that list, and a method that is missing from the
list runs with no permission check at all - which is exactly how a data
endpoint ends up handing every row to anybody who can guess its URL while the
page that draws it says no.

So for every controller that has a datatable() feed, this checks two things:

  1. the controller really does declare a list permission, and
  2. that permission's method list actually contains 'datatable'.

Controllers with no permission middleware at all are reported separately: they
are a gap in the app's own design, not something this migration introduced, and
silently "fixing" them would change who can reach pages nobody asked about.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CTL = ROOT / "app" / "Http" / "Controllers" / "Admin"

MIDDLEWARE = re.compile(
    r"\$this->middleware\(\s*'permission:(?P<perms>[^']+)'\s*,\s*\['only'\s*=>\s*\[(?P<only>[^\]]*)\]"
)


def feeds() -> list[Path]:
    return sorted(p for p in CTL.glob("*.php") if "function datatable(" in p.read_text(encoding="utf-8"))


def main() -> int:
    rows = []
    unguarded = []

    for path in feeds():
        src = path.read_text(encoding="utf-8")
        declared = list(MIDDLEWARE.finditer(src))

        if not declared:
            unguarded.append(path.stem)
            continue

        covered = any(
            re.search(r"['\"]datatable['\"]", m.group("only"))
            for m in declared
        )

        rows.append((path.stem, covered, len(declared)))

    print("%-28s %-22s %s" % ("controller", "list-permission guards", "datatable()"))
    print("-" * 74)

    problems = 0
    for name, covered, count in rows:
        if not covered:
            problems += 1
        print("%-28s %-22s %s" % (
            name,
            f"{count} declared",
            "covered" if covered else "NOT GUARDED",
        ))

    if unguarded:
        print("\nno permission middleware in these controllers at all (pre-existing, not touched here):")
        for name in unguarded:
            print("   ", name)

    print()
    print(
        "every grid endpoint sits behind the same permission as its screen"
        if problems == 0
        else f"{problems} endpoint(s) reachable without the screen's permission"
    )

    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())