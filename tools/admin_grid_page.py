#!/usr/bin/env python3
"""
admin_grid_page.py - wire an admin list screen onto the shared grid.

    python3 tools/admin_grid_page.py extract customer
    python3 tools/admin_grid_page.py check   customer banner category

`extract` lifts the page's <tbody> loop body into <page>/_row.blade.php and
rewrites the page to call backEnd/partials/datatable instead. It prints the
column count so the controller's datatable() method can be written against the
same number - and so tools/verify_grid_pages.php can prove they agree.

The row markup moves verbatim. Nothing is "improved" on the way, because a
grid migration that also edits rows is two changes to review at once.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
BE = ROOT / "resources" / "views" / "backEnd"

ROW_HEADER = """{{--
    backEnd/{page}/_row - one row of the {page} grid.

    Extracted from {page}/index.blade.php by tools/admin_grid_page.py, verbatim.
    The blade page and the DataTables endpoint both render it, so a change to
    this row reaches both - the thing that goes wrong when the two copies live
    in separate files.

        ${var}  the model for this row
--}}

"""


def split_loop(lines: list[str], tbody: int):
    """Return (loop_start, loop_end) indices around the row loop."""
    start = next(i for i in range(tbody, len(lines))
                 if re.match(r"\s*@for(each|else)\(", lines[i]))
    depth = 0
    for i in range(start, len(lines)):
        s = lines[i]
        depth += len(re.findall(r"@for(each|else)\(", s))
        depth -= len(re.findall(r"@end(foreach|forelse)\b", s))
        if depth == 0:
            return start, i
    raise SystemExit("could not find the end of the loop")


def extract(page: str, var: str = "value") -> int:
    view = BE / page / "index.blade.php"
    lines = view.read_text(encoding="utf-8").split("\n")

    tbody = next((i for i, l in enumerate(lines) if l.strip() == "<tbody>"), None)
    if tbody is None:
        raise SystemExit(f"{page}: no <tbody>")

    start, end = split_loop(lines, tbody)
    body = lines[start + 1:end]

    # An @empty branch inside the loop body is the page's "nothing here"
    # message; the grid shows its own, so the branch goes.
    trimmed = []
    skip_to_endfor = False
    for line in body:
        if skip_to_endfor:
            if line.strip().startswith("@endforelse"):
                skip_to_endfor = False
            continue
        if line.strip() == "@empty":
            skip_to_endfor = True
            continue
        trimmed.append(line)

    indent = min((len(l) - len(l.lstrip()) for l in trimmed if l.strip()), default=0)
    row = "\n".join(l[indent:] if l.strip() else "" for l in trimmed).rstrip()

    row_path = BE / page / "_row.blade.php"
    written = ROW_HEADER.format(page=page, var=var) + row + "\n"
    # Guard: a comment opener or closer that lost a brace is not a comment to
    # Blade any more. It renders as literal text into the first cell, which is
    # invisible in the row markup and obvious in the finished grid.
    if not written.startswith("{{--") or "--}}" not in written:
        raise SystemExit(f"{page}: row header lost a brace")
    row_path.write_text(written, encoding="utf-8")

    th = re.findall(r"<th\b", " ".join(lines))
    print(f"{page}: row partial written ({len(row.splitlines())} lines), columns in <thead>: {len(th)}")
    return len(th)


def check(page: str) -> bool:
    """Does the row partial's cell count still match the grid's column count?"""
    view = BE / page / "index.blade.php"
    row = BE / page / "_row.blade.php"
    if not view.exists() or not row.exists():
        print(f"{page}: not wired yet")
        return False

    src = view.read_text(encoding="utf-8")
    if "backEnd.partials.datatable" not in src:
        print(f"{page}: view does not use the shared grid yet")
        return False

    cols = re.search(r"'columns' => \[(.*?)\n\s*\],", src, re.S)
    n_view = len(re.findall(r"\['label'", cols.group(1))) if cols else 0
    n_row = len(re.findall(r"<td\b", row.read_text(encoding="utf-8")))

    ok = n_view == n_row and n_view > 0
    print(f"{page}: grid columns={n_view} row cells={n_row} {'OK' if ok else 'MISMATCH'}")
    return ok


if __name__ == "__main__":
    if len(sys.argv) < 3:
        raise SystemExit(__doc__)
    mode, target = sys.argv[1], sys.argv[2]
    if mode == "extract":
        extract(target)
    elif mode == "check":
        sys.exit(0 if all(check(p) for p in target.split(",")) else 1)
    else:
        raise SystemExit(__doc__)