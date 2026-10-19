#!/usr/bin/env python3
"""
admin_state_changing_gets.py - find admin GET routes that change data.

A GET is defined by HTTP to be safe: a browser may fetch it for a prefetch, a
link preview or an <img> in an email without the visitor meaning anything by
it. A route that deletes an order, moves it to another status or edits a price
therefore inherits a hole - any page that links to it can trigger it, and a
logged-in admin does not even have to click.

This prints every admin GET whose controller method writes. It is a report, not
a fixer: the method bodies decide what counts, and the route alone does not.
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

# What each verb-ish fragment in a method name implies. Deliberately generous:
# a false positive costs one look, a false negative costs a hole.
MUTATORS = [
    r"\b(create|store|save|insert|add)\b",
    r"\b(update|edit|change|modify|set|status|price)\b",
    r"\b(delete|destroy|remove|drop|bulk)\b",
    r"\b(approve|reject|cancel|activate|inactivate|toggle)\b",
    r"\b(assign|invoice|ship|deliver|restock|move|restore)\b",
]

# A bare DB::table() is a read as often as a write, and matching it turned
# every screen that lists products into a "state changing GET". Only the calls
# that end in a mutation count.
WRITE = re.compile(
    r"->(update|save|delete|forceDelete|increment|decrement|insert|insertGetId|upsert)\s*\("
    r"|\bDB::(statement|update|delete|insert|unprepared)\s*\("
    r"|::(create|updateOrCreate|firstOrCreate|insertOrIgnore)\s*\("
)


def routes() -> list[dict]:
    out = subprocess.run(
        ["php", "artisan", "route:list", "--json"],
        cwd=ROOT, capture_output=True, text=True, timeout=300,
    )
    return json.loads(out.stdout)


def method_source(class_fqcn: str, method: str) -> str | None:
    # route:list prints the fully qualified name, App\Http\..., and the
    # project root's App directory is where it lives.
    relative = class_fqcn.replace("\\", "/")
    if relative.startswith("App/"):
        relative = relative[len("App/"):]
    path = ROOT / "app" / (relative + ".php")
    if not path.exists():
        return None
    src = path.read_text(encoding="utf-8")
    m = re.search(
        r"function\s+" + re.escape(method) + r"\s*\([^)]*\)\s*\{", src
    )
    if not m:
        return None
    depth, i = 0, m.end() - 1
    for j in range(i, len(src)):
        if src[j] == "{":
            depth += 1
        elif src[j] == "}":
            depth -= 1
            if depth == 0:
                return src[i:j]
    return None


def main() -> int:
    findings = []

    for route in routes():
        if "GET|HEAD" not in route.get("method", ""):
            continue
        if not route["uri"].startswith("admin"):
            continue

        action = route["action"]
        # "App\\Http\\Controllers\\Admin\\OrderController@order_status"
        m = re.match(r"([\w\\]+)@(\w+)", action.replace("->", "@"))
        if not m:
            continue
        class_name, method = m.group(1), m.group(2)

        # \b does not break on an underscore, so order_status reads as one
        # word to the patterns above. Split the name on underscores first.
        spoken = method.replace("_", " ")
        if not any(re.search(p, spoken, re.I) for p in MUTATORS):
            continue

        body = method_source(class_name, method)
        if body is None:
            continue
        if not WRITE.search(body):
            continue

        findings.append((route["uri"], f"{class_name}@{method}", route["name"]))

    print("admin GET routes that write to the database\n")
    if not findings:
        print("  none found")
        return 0

    print(f"  {'uri':46s} {'method':34s} route name")
    print("  " + "-" * 108)
    for uri, method, name in findings:
        print(f"  {uri:46s} {method:34s} {name}")

    print(f"\n{len(findings)} state change(s) reachable by GET.")
    print("Each one can be triggered by a link, a prefetch or an <img> tag in an")
    print("email - with no confirmation, no CSRF token and no audit trail.")

    return 1


if __name__ == "__main__":
    sys.exit(main())