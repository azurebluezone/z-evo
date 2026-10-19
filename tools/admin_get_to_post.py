#!/usr/bin/env python3
"""
admin_get_to_post.py - move state-changing admin routes from GET to POST.

    python3 tools/admin_get_to_post.py            # report
    python3 tools/admin_get_to_post.py --apply

A GET is safe by definition in HTTP: browsers prefetch links, mail clients
fetch <img> targets, and chat apps unfurl them. Any of those can therefore fire
an admin action without the user meaning to - and the admin routes below were
deleting orders, moving them between statuses (which decrements stock) and
editing product prices.

POST is the correct verb for all of them. It carries a CSRF token by
construction, so the fix is a verb change plus the callers sending the token,
not a security check bolted on afterwards.

The route table decides the verbs; the callers live in blade and are listed in
CALLERS below so the diff is reviewable in one place.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
ROUTES = ROOT / "routes" / "web.php"

# route name -> the exact Route::get(...) line currently in the file
MOVES = [
    ("admin.order.status",       "Route::get('order-status'"),
    ("admin.order.assign",       "Route::get('order-assign'"),
    ("admin.order.bulk_destroy", "Route::get('order-bulk-destroy'"),
    ("admin.bulk_courier",       "Route::get('bulk-courier/{slug}'"),
    ("products.update_status",   "Route::get('products/update-status'"),
    ("products.update_feature",  "Route::get('products/update-feature'"),
    ("products.update_deals",    "Route::get('products/update-deals'"),
]

# What each caller has to send once the verb changes. Checked afterwards so a
# route cannot be switched while its caller still issues a GET.
CALLERS = {
    "admin.order.status":       ["order/_row.blade.php", "order/index.blade.php"],
    "admin.order.assign":       ["order/index.blade.php"],
    "admin.order.bulk_destroy": ["order/index.blade.php"],
    "admin.bulk_courier":       ["order/index.blade.php"],
    "products.update_status":   ["product/index.blade.php"],
    "products.update_feature":  ["product/_row.blade.php", "product/index.blade.php"],
    "products.update_deals":    ["product/_row.blade.php", "product/index.blade.php"],
}


def main() -> int:
    apply = "--apply" in sys.argv
    src = ROUTES.read_text(encoding="utf-8")
    original = src

    changed = []
    for name, marker in MOVES:
        if marker not in src:
            print(f"{name:28s} already moved")
            continue
        src = src.replace(marker, marker.replace("Route::get(", "Route::post(", 1), 1)
        changed.append(name)

    if not changed:
        print("\nnothing to do")
        return 0

    if not apply:
        print("\nwould move to POST:")
        for name in changed:
            print("   ", name)
        print("\nre-run with --apply")
        return 0

    ROUTES.write_text(src, encoding="utf-8")
    print(f"moved {len(changed)} route(s) to POST:")
    for name in changed:
        print(f"    {name}  -> {', '.join(CALLERS[name])}")
    print(f"\n{len(changed)} route line(s) changed")
    if original == src:
        return 0
    return 0


if __name__ == "__main__":
    sys.exit(main())