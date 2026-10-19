#!/usr/bin/env python3
"""
order_form_stepper_nav.py - put the step navigation INSIDE the stepper.

    python3 tools/order_form_stepper_nav.py --apply

The navigation has to live inside the .bs-stepper element: that is where the
panes are, and a Next button that sits outside it has no stepper to advance -
the delegated handler looks up the nearest .bs-stepper ancestor, finds none, and
returns without doing anything.

The block is removed from wherever it is now and re-inserted after the last
</div> that closes the stepper - which is the last </div> before the order
form's own </form>, NOT the first one. On these pages there are two forms (a
"Cart Clear" GET form in the header and the order form itself) and anchoring on
the first </form> puts the buttons on the wrong form entirely.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
TARGETS = [
    (ROOT / "resources" / "views" / "backEnd" / "order" / "create.blade.php", "admin.order.store"),
    (ROOT / "resources" / "views" / "backEnd" / "order" / "edit.blade.php", "admin.order.update"),
]

NAV = '''
      {{--
        Step navigation, INSIDE the stepper: a Next button outside it has no
        stepper to advance. The submit input in the last pane above is the same
        one this form has always had - nothing about the post changed.
      --}}
      <div class="d-flex justify-content-between mt-4">
       <x-admin.button variant="outline-secondary" class="btn-prev" type="button" data-bs-stepper-prev>
        <i class="ri-arrow-left-line me-1"></i> Back
       </x-admin.button>
       <x-admin.button variant="primary" class="btn-next" type="button" data-bs-stepper-next>
        Next <i class="ri-arrow-right-line ms-1"></i>
       </x-admin.button>
      </div>
'''

EXISTING_NAV = re.compile(
    r"\n *\{\{--\n *Step navigation.*?data-bs-stepper-next.*?</x-admin\.button>\n *</div>\n",
    re.S,
)


def main() -> int:
    apply = "--apply" in sys.argv

    for path, route in TARGETS:
        src = path.read_text(encoding="utf-8")

        if "data-bs-stepper-next" not in src:
            print(f"{path.name}: no navigation block yet")
            continue

        # 1) take it out of wherever it currently sits
        src, n = EXISTING_NAV.subn("\n", src)
        if n == 0:
            print(f"{path.name}: could not find the existing block")
            continue

        # 2) find the order form
        start = re.search(r"<form[^>]*" + re.escape(route) + r"[^>]*>", src)
        if not start:
            print(f"{path.name}: order form not found")
            continue

        close = re.search(r"\n\s*</form>", src[start.end():])
        if not close:
            print(f"{path.name}: order form is never closed")
            continue

        at = start.end() + close.start()

        # 3) Put it INSIDE the .bs-stepper element. The delegated handler looks
        #    up the nearest .bs-stepper ancestor, so a button outside it has no
        #    stepper to advance and does nothing at all when clicked.
        #
        #    The three closing tags before the form's </form> are, in order: the
        #    last pane, .bs-stepper-content, then .bs-stepper. Inserting before
        #    the </form> instead lands outside all three - which is where it
        #    was, and exactly why clicking it did nothing.
        head = src[:at]
        stepper_end = head.rfind("       </div>")       # the one closing .bs-stepper
        if stepper_end < 0:
            print(f"{path.name}: could not find the .bs-stepper closing tag")
            continue

        src = src[:stepper_end] + NAV.lstrip("\n") + "\n" + src[stepper_end:]

        if apply:
            path.write_text(src, encoding="utf-8")
            print(f"{path.name}: navigation moved inside the stepper")
        else:
            print(f"{path.name}: would move navigation inside the stepper")

    if not apply:
        print("\ndry run - re-run with --apply")
    return 0


if __name__ == "__main__":
    sys.exit(main())