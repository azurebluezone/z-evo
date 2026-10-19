#!/usr/bin/env python3
"""
admin_callers_match_verb.py - every caller must use the route's actual verb.

    python3 tools/admin_callers_match_verb.py

Changing a route from GET to POST is half a change. The other half is every
place that calls it. Miss that and the screen answers 405: the button looks
right, still has its handler, and silently does nothing.

That is not hypothetical. admin.order.assign was moved to POST and its form
given method="POST" - and the ajax that submits it kept sending GET, because the
two live in different places and nothing looked at both. Every other check in
the repo passed: the route table was right, the template compiled, the CSRF
token was present on the form. None of them knows what the browser will do.

WHY THIS IS NOT A REGEX OVER <form>
------------------------------------
Three things had to be got right, each learned from a wrong answer:

  1. `route('admin.attributes.destroy', $a->id)` contains a `>`. A `<form([^>]*)>`
     pattern stops there and never sees method="POST", so it reports a mismatch
     that does not exist. Tags are scanned with {{ }} brace tracking.

  2. Most admin ajax does not name the route at all. It says `var url =
     $(this).attr('action')` and then `url: url`, because it is inside a handler
     for `form#order_assign`. So the url is resolved through the handler it
     sits in, and the route is read from that form's action.

  3. `$.ajax({...})` bodies are matched by counting braces. A non-greedy
     pattern stops at the first }, which in a nested options object is the
     wrong one - and a partial read finds no verb, which reads as "no problem".
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEWS = ROOT / "resources" / "views" / "backEnd"

WRITE_VERBS = {"POST", "PUT", "PATCH", "DELETE"}
READ_VERBS = {"GET", "HEAD"}

HANDLER = re.compile(
    r"""\$\(document\)\.on\s*\(\s*['"](\w+)['"]\s*,\s*['"]([^'"]+)['"]""", re.S
)
AJAX = re.compile(r"\$\.ajax\s*\(\s*\{")
FETCH = re.compile(r"\bfetch\s*\(")


def route_table() -> list[dict]:
    out = subprocess.run(
        ["php", "artisan", "route:list", "--json"],
        cwd=ROOT, capture_output=True, text=True, timeout=300,
    )
    if out.returncode != 0:
        raise SystemExit("could not read the route table")
    return json.loads(out.stdout)


def admin_verbs(table: list[dict]) -> dict[str, str]:
    found: dict[str, str] = {}
    for r in table:
        name = r.get("name")
        if not name or not r["uri"].startswith("admin"):
            continue
        methods = {m.strip() for m in r.get("method", "").split("|")}
        if methods & WRITE_VERBS:
            found[name] = "POST"
        elif methods & READ_VERBS:
            found.setdefault(name, "GET")
    return found


def match_brace(src: str, open_at: int, opener: str = "{", closer: str = "}") -> int:
    """Index of the brace closing the one at `open_at`, counting nested pairs."""
    depth = 0
    i = open_at
    while i < len(src):
        if src[i] == opener:
            depth += 1
        elif src[i] == closer:
            depth -= 1
            if depth == 0:
                return i
        i += 1
    return -1


def blade_tags(src: str, tag: str) -> list[tuple[int, str]]:
    """`<tag ...>` attributes, ignoring any `>` that sits inside {{ }}."""
    found = []
    for m in re.finditer(r"<" + re.escape(tag) + r"\b", src):
        i, depth = m.end(), 0
        while i < len(src):
            two = src[i:i + 2]
            if depth == 0 and src[i] == ">":
                break
            if two == "{{":
                depth += 1
                i += 2
                continue
            if two == "}}" and depth:
                depth -= 1
                i += 2
                continue
            i += 1
        found.append((src.count("\n", 0, m.start()) + 1, src[m.end():i]))
    return found


def attr(attrs: str, name: str) -> str | None:
    """
    An attribute's value, as written.

    NOT upper-cased: method="post" and id="order_assign" both come through here,
    and upper-casing an id turns order_assign into ORDER_ASSIGN - at which point
    a handler on `form#order_assign` no longer matches its own form and the
    check silently checks nothing. Callers comparing verbs upper-case there.
    """
    m = re.search(rf"""\b{re.escape(name)}\s*=\s*(["']?)([A-Za-z0-9_.-]+)\1""", attrs)
    return m.group(2) if m else None


def route_in(fragment: str) -> str | None:
    m = re.search(r"""route\(\s*['"]([^'"]+)['"]""", fragment)
    return m.group(1) if m else None


def ajax_verbs(block: str) -> list[tuple[str | None, str | None]]:
    """(url-fragment, verb) for each $.ajax / fetch inside a handler block."""
    out = []

    for m in AJAX.finditer(block):
        close = match_brace(block, block.index("{", m.end() - 1))
        body = block[m.end():close] if close > 0 else block[m.end():]

        url = re.search(r"""url\s*:\s*(['"])(.*?)\1""", body)
        url_text = url.group(2) if url else None
        if url_text is None:
            # `url: url` - a variable, usually assigned above from the form.
            v = re.search(r"""url\s*:\s*([A-Za-z_$][\w$]*)""", body)
            if v:
                name = v.group(1)
                assign = re.search(
                    rf"""\b(?:var|let|const)\s+{re.escape(name)}\s*=\s*([^;\n]+);""", block
                )
                url_text = assign.group(1).strip() if assign else None

        verb = re.search(r"""\b(?:type|method)\s*:\s*["']([A-Za-z]+)["']""", body)
        out.append((url_text, verb.group(1).upper() if verb else None))

    return out


def main() -> int:
    verbs = admin_verbs(route_table())
    checked = 0
    problems: list[str] = []

    for path in sorted(VIEWS.rglob("*.blade.php")):
        src = path.read_text(encoding="utf-8")
        rel = path.relative_to(ROOT).as_posix()

        # --- index every form by its id, so a handler can be resolved ------
        forms_by_id: dict[str, tuple[int, str, str | None, str | None]] = {}
        for line_no, attrs in blade_tags(src, "form"):
            fid = attr(attrs, "id")
            name = route_in(attrs)
            if fid and name:
                forms_by_id[fid] = (line_no, name, (attr(attrs, "method") or "GET").upper(), attrs)

            # A form with no handler submits itself: check it directly.
            if name and fid is None and name in verbs:
                checked += 1
                sent = (attr(attrs, "method") or "GET").upper()
                if sent != verbs[name]:
                    problems.append(
                        f"{rel}:{line_no}  <form> -> route('{name}')  "
                        f"route is {verbs[name]}, form sends {sent}"
                    )

        # --- delegated handlers: $(document).on(event, selector, fn) ---------
        for m in HANDLER.finditer(src):
            event, selector = m.group(1), m.group(2)

            body_start = src.find("{", m.end())
            if body_start < 0:
                continue
            close = match_brace(src, body_start)
            block = src[body_start:close] if close > 0 else src[body_start:]

            # Which form (if any) is this handler bound to?
            target = None
            fm = re.match(r"form#([\w-]+)", selector)
            if fm:
                target = forms_by_id.get(fm.group(1))

            for url_text, verb in ajax_verbs(block):
                if verb is None:
                    continue

                name = route_in(url_text) if url_text else None
                if name is None and target is not None:
                    name = target[1]          # url came from the form's action
                if name is None or name not in verbs:
                    continue

                checked += 1
                if verb != verbs[name]:
                    line_no = src.count("\n", 0, m.start()) + 1
                    problems.append(
                        f"{rel}:{line_no}  {event} on '{selector}' -> route('{name}')  "
                        f"route is {verbs[name]}, script sends {verb}"
                    )

    print(f"admin routes in the table : {len(verbs)}")
    print(f"callers matched to a verb : {checked}\n")

    if not problems:
        print("every caller uses the verb its route actually has")
        return 0

    print("MISMATCH - the route and its caller disagree:\n")
    for p in problems:
        print("  " + p)
    print(f"\n{len(problems)} mismatch(es). A GET to a POST-only route answers 405.")
    return 1


if __name__ == "__main__":
    sys.exit(main())