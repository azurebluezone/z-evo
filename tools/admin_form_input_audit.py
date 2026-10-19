#!/usr/bin/env python3
"""
admin_form_input_audit.py - the input vocabulary of the admin.

    python3 tools/admin_form_input_audit.py            # the shape of the problem
    python3 tools/admin_form_input_audit.py --files    # which page uses what
    python3 tools/admin_form_input_audit.py --control form-control

Thirty screens, each built by hand, each reaching for its own idea of a text
input. That is not a style opinion - it is what makes a form feel wrong before
anyone can say why: one page's invalid field is red, the next page's is a
border tint, the third has no error state at all.

This reads every admin view, splits the controls by kind, and reports the
distinct class strings used for each kind. The longest list is the odd one out;
the single class that covers most of them is what a shared component would
have to emit.

It also reports the things that are missing rather than different: controls
with no label, selects with no form-select class, buttons that are not the
shared component. Those are not cosmetic - an input with no label is unusable
with a screen reader.
"""
from __future__ import annotations

import re
import sys
from collections import Counter, defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEWS = ROOT / "resources" / "views" / "backEnd"

# blade tags are not html: a ">" inside {{ }} ends no tag, and these templates
# are full of route('x', $model->id).
OPEN = re.compile(r"<(input|select|textarea|button)\b", re.I)
CLOSE = re.compile(r">")
ATTR = re.compile(r"""([\w:-]+)\s*=\s*(["'])(.*?)\2""", re.S)

# The error-state classes are the CORRECT way to write these, not a deviation:
#
#   class="form-control @error('name') is-invalid @enderror"
#
# is the intended spelling and appears on most fields. Leaving it in the class
# string makes every field look like its own vocabulary - 78 distinct strings
# for text inputs, when the real answer is a handful. So the conditional pair
# is removed before the strings are compared. What is left is the deviation:
# rounded-0, an input with no class at all, a hand-rolled border.
ERROR_CLAUSE = re.compile(r"@error\([^)]*\)\s*is-invalid\s*@enderror", re.I)
ERROR_CLAUSE_ALT = re.compile(r"@error\([^)]*\)\s*[^\"']*?@enderror", re.I)


TYPE_DEFAULT = "text"


def vocabulary(cls: str) -> str:
    """The class string with blade's conditional error clause taken out."""
    cleaned = ERROR_CLAUSE.sub("", cls)
    cleaned = ERROR_CLAUSE_ALT.sub("", cleaned)
    return " ".join(cleaned.split())
TYPE_OF = {"select": "select", "textarea": "textarea", "button": "button"}


def tags_in(src: str):
    """Yield (kind, attrs_text) for every opening control tag, brace-aware."""
    for m in OPEN.finditer(src):
        i = m.end()
        depth = 0
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
        yield m.group(1).lower(), src[m.end():i], src.count("\n", 0, m.start()) + 1


def attrs_of(text: str) -> dict:
    return {k.lower(): v for k, _, v in ATTR.findall(text)}


def classify(kind: str, a: dict) -> str:
    if kind in TYPE_OF:
        return TYPE_OF[kind]

    t = (a.get("type") or "text").lower()
    if t in ("checkbox", "radio", "range", "file"):
        return t
    if t == "hidden":
        return "hidden"
    if t in ("submit", "button", "reset", "image"):
        return "submit"
    if t == "password":
        return "password"
    if t == "email":
        return "email"
    if t == "number":
        return "number"
    if t == "date" or t.startswith("date-"):
        return "date"
    return "text"


def main() -> int:
    args = sys.argv[1:]
    only = None
    if "--control" in args:
        only = args[args.index("--control") + 1]
    show_files = "--files" in args

    classes = defaultdict(Counter)          # kind -> class string -> count
    per_file = defaultdict(set)             # file -> class strings
    files_by_kind = defaultdict(set)        # kind -> files
    unlabelled = []
    total = Counter()

    for path in sorted(VIEWS.rglob("*.blade.php")):
        src = path.read_text(encoding="utf-8")
        rel = path.relative_to(ROOT).as_posix()

        for kind, text, line in tags_in(src):
            a = attrs_of(text)
            group = classify(kind, a)

            if group == "hidden" or group == "submit":
                continue

            cls = vocabulary(a.get("class", ""))
            total[group] += 1
            classes[group][cls] += 1
            files_by_kind[group].add(rel)
            if cls:
                per_file[rel].add(cls)

            # An input a screen reader cannot name.
            if group in ("text", "password", "email", "number", "date", "select", "textarea"):
                has_id = "id" in a
                has_label = bool(re.search(
                    r"<label[^>]*for=\s*[\"']" + re.escape(a.get("id", "")) + r"[\"']",
                    src,
                )) if has_id else False
                has_aria = "aria-label" in a or "aria-labelledby" in a
                if not (has_label or has_aria) and a.get("type") not in ("hidden",):
                    unlabelled.append(f"{rel}:{line}  <{kind}> {a.get('name') or a.get('id') or '(no name)'}")

    if only:
        group = only
        counter = classes.get(group, Counter())
        print(f"class strings used for <{group}>  ({len(counter)} distinct, {total[group]} controls)\n")
        for cls, n in counter.most_common():
            print(f"  {n:5d}  {cls if cls else '(no class)'}")
        return 0

    print("admin input inventory\n")
    print(f"{'control':10s} {'count':>7s} {'distinct':>9s} {'files':>7s}   most common")
    print("-" * 84)
    for group in sorted(total, key=lambda g: -total[g]):
        c = classes[group]
        top = c.most_common(1)[0] if c else ("", 0)
        print(f"{group:10s} {total[group]:7d} {len(c):9d} {len(files_by_kind[group]):7d}   "
              f"{top[0] or '(none)'}  x{top[1]}")

    print()
    for group in ("text", "select", "textarea"):
        c = classes.get(group, Counter())
        if len(c) <= 1:
            continue
        print(f"--- {group}: {len(c)} distinct class strings ---")
        for cls, n in c.most_common():
            print(f"  {n:5d}  {cls if cls else '(no class)'}")
        print()

    if unlabelled:
        print(f"--- {len(unlabelled)} controls with no label and no aria-label ---")
        for u in unlabelled[:25]:
            print("  " + u)
        if len(unlabelled) > 25:
            print(f"  ... and {len(unlabelled) - 25} more")
        print()

    if show_files:
        print("--- class strings per file ---")
        for rel in sorted(per_file):
            print(f"{rel}")
            for cls in sorted(per_file[rel]):
                print(f"    {cls}")

    return 0


if __name__ == "__main__":
    sys.exit(main())