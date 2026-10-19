#!/usr/bin/env python3
"""
order_form_stepper.py - put the order create/edit form into BS Stepper steps.

    python3 tools/order_form_stepper.py            # report the block boundaries
    python3 tools/order_form_stepper.py --apply

The form is one long page of three separable blocks - the product cart, the
customer's details, and the discount/total - and reading it means scrolling
past all three to get back to any one of them.

Wrapping those blocks in BS Stepper makes it three steps. It is deliberately a
PRESENTATION change only: the <form> element is untouched, every input keeps its
name, id and value, and the submit button stays inside the same form. The proof
is tools/verify_order_form_fields.py, which fails if the set of posted field
names differs from the pre-stepper file.

The tool anchors on the comment markers already in the file (custome address,
cart total) rather than on line numbers, so it survives the file being edited
in between.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
# (file, the route its order form posts to)
TARGETS = [
    (ROOT / "resources" / "views" / "backEnd" / "order" / "create.blade.php", "admin.order.store"),
    (ROOT / "resources" / "views" / "backEnd" / "order" / "edit.blade.php", "admin.order.update"),
]

# The three steps, in the order the shopkeeper fills them in.
STEPS = [
    ("products",  "Products",   "ri-shopping-cart-line"),
    ("customer",  "Customer",   "ri-user-line"),
    ("payment",   "Payment",    "ri-money-dollar-circle-line"),
]

HEADER = """
       <!--
         Three steps, one form. The <form> element above and every input inside
         it are unchanged - only the wrapping is new, so the same data is
         posted to the same route. See tools/verify_order_form_fields.py.
       -->
       <div class="bs-stepper" data-bs-stepper data-bs-stepper-linear="true">
        <div class="bs-stepper-header">
{headers}
        </div>
        <div class="bs-stepper-content">
"""

FOOTER = """
        </div>
       </div>
"""


def build_header() -> str:
    """
    The header markup, matching what the shipped bs-stepper actually reads.

    Two of its contracts are not documented on the element and both are silent
    when missed:

      * each .step needs data-target pointing at its pane, because the library
        collects the panes by walking the headers and running that selector.
        Without it _stepsContents stays empty and no amount of clicking moves
        anything - the stepper simply stays on step one.
      * the clickable element inside a step must carry .step-trigger; that is
        the selector the library binds its click listener to.
    """
    out = []
    for i, (_, label, icon) in enumerate(STEPS, start=1):
        active = " active" if i == 1 else ""
        out.append(
            f'         <div class="step{active}" data-target="#stepper-{i}">\n'
            f'          <a class="nav-link step-trigger" href="#stepper-{i}">\n'
            f'           <i class="{icon} me-2"></i><span>{label}</span>\n'
            f'          </a>\n'
            f'         </div>'
        )
        if i != len(STEPS):
            out.append('         <div class="line"></div>')
    return "\n".join(out)


def pane_open(i: int) -> str:
    active = " active" if i == 1 else ""
    # id="stepper-N" is what the header's data-target resolves to.
    #
    # The class is "content", not "step-pane": the bs-stepper build shipped with
    # this template hides panes with `.bs-stepper .content:not(.fade)`, so a
    # pane called .step-pane is never hidden and all three steps show at once.
    return f'         <div class="content{active}" id="stepper-{i}">'


def pane_close() -> str:
    return "         </div>"


def transform(src: str, route: str) -> str:
    # 1) open the stepper inside the ORDER form specifically.
    #
    #    Anchoring on "<form ...> @csrf" matches the page's FIRST form, and on
    #    the create page that is the "Cart Clear" button in the header, not the
    #    order itself. Wrapping that instead put the whole stepper around one
    #    submit button and left the real form untouched. Anchor on the form that
    #    posts the order.
    m = re.search(r"(<form[^>]*" + re.escape(route) + r"[^>]*>\s*@csrf\s*\n)", src)
    if not m:
        return src
    src = src[: m.end()] + HEADER.format(headers=build_header()) + pane_open(1) + "\n" + src[m.end():]

    # 2) close step 1 and open step 2 at the second block. The two forms mark
    #    their blocks differently, so both spellings are accepted rather than
    #    assuming one file's comments exist in the other.
    for pattern in (r"^(\s*)<!-- custome address -->\s*$", r"^(\s*)<!-- Customer Information -->\s*$"):
        marker = re.search(pattern, src, re.M)
        if marker:
            src = src[: marker.start()] + pane_close() + "\n" + pane_open(2) + "\n" + src[marker.start():]
            break

    # 3) close step 2 and open step 3 at the totals block
    for pattern in (r"^(\s*)<!-- cart total -->\s*$", r"^(\s*)<!-- Financial Summary -->\s*$"):
        marker = re.search(pattern, src, re.M)
        if marker:
            src = src[: marker.start()] + pane_close() + "\n" + pane_open(3) + "\n" + src[marker.start():]
            break

    # 4) close step 3 and the stepper, before the ORDER form's own </form>
    #
    #    The order of these three closing tags matters to everything that comes
    #    after: pane, then .bs-stepper-content, then .bs-stepper. The step
    #    navigation has to land inside the last of them, which is why the nav
    #    is emitted by order_form_stepper_nav.py rather than bolted on here.
    form_start = re.search(r"<form[^>]*" + re.escape(route) + r"[^>]*>", src)
    close = re.search(r"\n(\s*)</form>", src[form_start.end():]) if form_start else None
    if not close:
        return src
    at = form_start.end() + close.start()
    src = src[:at] + "\n" + pane_close() + FOOTER + src[at:]

    return src


def main() -> int:
    apply = "--apply" in sys.argv

    for path, route in TARGETS:
        if not path.exists():
            print(f"{path.name}: not found")
            continue
        src = path.read_text(encoding="utf-8")
        if "bs-stepper" in src:
            print(f"{path.name}: already stepped")
            continue

        # A dry run still has to transform cleanly, so do it and report.
        out = transform(src, route)
        panes = len(re.findall(r'class="content[ "]', out))
        headers = len(re.findall(r'<div class="step[ "]', out))
        targets = len(re.findall(r'data-target="#stepper-', out))
        print(f"{path.name}: {headers} header step(s), {panes} pane(s), {targets} data-target(s)")

        if not apply:
            continue

        path.write_text(out, encoding="utf-8")
        print(f"  written")

    if not apply:
        print("\ndry run - re-run with --apply")
    return 0


if __name__ == "__main__":
    sys.exit(main())