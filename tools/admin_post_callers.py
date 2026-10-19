#!/usr/bin/env python3
"""
admin_post_callers.py - every blade form that POSTs to an admin route must
carry a CSRF token.

    python3 tools/admin_post_callers.py

The state-changing endpoints were moved from GET to POST precisely because a
POST is checked. A form that points at one of them without @csrf is not merely
untidy - Laravel's VerifyCsrfToken answers 419, and the screen silently does
nothing. This reads each <form method="post"> in the admin views, matches the
action against the route table, and reports any that omit the token.

Forms that post to an action outside this app (a payment gateway, an upload to
a third party) are skipped: a token there is neither possible nor wanted.
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEWS = ROOT / "resources" / "views" / "backEnd"

FORM = re.compile(r"<form\b([^>]*)>(.*?)</form>", re.S | re.I)
ATTR = lambda body, name: (re.search(rf"{name}\s*=\s*([\"'])(.*?)\1", body, re.S) or [None, None, None])[2]
HAS_TOKEN = re.compile(r"@csrf|csrf_field\s*\(", re.I)


def route_table() -> list[dict]:
    out = subprocess.run(
        ["php", "artisan", "route:list", "--json"],
        cwd=ROOT, capture_output=True, text=True, timeout=300,
    )
    if out.returncode != 0:
        raise SystemExit("could not read the route table")
    return json.loads(out.stdout)


def admin_routes(table: list[dict]) -> set[str]:
    """Every admin route that answers to POST."""
    return {
        r["uri"] for r in table
        if "POST" in r.get("method", "") and r["uri"].startswith("admin")
    }


def main() -> int:
    table = route_table()
    posts = admin_routes(table)

    # route name -> uri, for the POST methods only
    name_to_uri = {
        r["name"]: r["uri"] for r in table
        if "POST" in r.get("method", "") and r["uri"].startswith("admin")
    }

    checked = 0
    problems = []

    for path in sorted(VIEWS.rglob("*.blade.php")):
        src = path.read_text(encoding="utf-8")

        for attrs, body in FORM.findall(src):
            method = (ATTR(attrs, "method") or "get").lower()
            action = ATTR(attrs, "action") or ""
            if method != "post":
                continue

            # Match the blade route() call back to a uri in the table.
            m = re.search(r"route\(\s*'([^']+)'", action)
            if not m:
                continue          # a literal or external url
            name = m.group(1)

            # The route table stores uris, not names; map by name -> uri.
            uri = None
            out = subprocess.run(
                ["php", "artisan", "route:list", "--json"],
                cwd=ROOT, capture_output=True, text=True, timeout=300,
            )
            for route in json.loads(out.stdout):
                if route.get("name") == name and "POST" in route.get("method", ""):
                    uri = route["uri"]
                    break
            if uri is None or uri not in posts:
                continue

            checked += 1
            if not HAS_TOKEN.search(body):
                rel = path.relative_to(ROOT).as_posix()
                problems.append((rel, name, uri))

    print(f"admin POST routes in the table : {len(posts)}")
    print(f"blade forms pointing at them  : {checked}")

    if not problems:
        print("\nevery form that posts to an admin route carries @csrf")
        return 0

    print("\nmissing CSRF token:")
    for rel, name, uri in problems:
        print(f"  {rel}\n      route('{name}')  ->  {uri}")
    print(f"\n{len(problems)} form(s) would be rejected with 419")
    return 1


if __name__ == "__main__":
    sys.exit(main())