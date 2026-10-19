# Colour variants and the duration-matched pending effect

Date: 2026-10-11

## What was asked

Two things, in the owner's words:

1. Colour variants should show a real colour swatch.
2. "If the attribute does not have the quality, do not act it out." If the shop has
   no colour data, the page must not invent one.
3. "If changing the attribute takes N seconds, play the effect for those same N
   seconds, in front of the user, so it feels like a feature and not as if the
   site is slow."

## Two real bugs found in the project before any of this

These were found while measuring, not guessed at.

### 1. The admin variant form never saves attributes

`resources/views/backEnd/variants/create.blade.php` has fields for sku, title,
price_override, weight_gram and status — but nothing for attributes. A variant
created from the admin is therefore saved with `attributes_json = null`, and the
frontend can never match it to a shopper's choice.

This is why the variant system looked "wired but never working". It is not a
frontend problem.

### 2. There is no concept of colour anywhere in the schema

- No colour type on `attributes`.
- No colour field on `product_variants`.
- `attribute_values.code` is a free-text box labelled "Code (Optional)" in
  `resources/views/backEnd/attributes/values.blade.php`.

So a colour swatch could not be drawn from data that did not exist. Rather than
invent shades, the work was to open the path and use it only when the shopkeeper
has actually entered a colour.

## What was built

### Colour comes from a real hex the shopkeeper entered

`app/Http/Controllers/Frontend/FrontendController.php` (`details()`)

```php
$valueRow = DB::table('attribute_values')->where('id', $valueId)->first(['id', 'code']);
$hex = $valueRow ? (string) ($valueRow->code ?? '') : '';
$isColour = (bool) preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', trim($hex));
```

and `'colour' => $isColour ? trim($hex) : null` on the value.

A valid `#RGB` / `#RRGGBB` in `code` is a colour. Anything else — including the
string `ZZTEST-small` that the demo data once carried — is left as text, so no
shade is ever shown that the shop does not sell.

### The chip sits beside the label, never on top of it

`resources/views/frontend/fm/sections/detail-summary.blade.php` renders
`.fm-colour-chip` as the first child of the pill; `public/frontend/css/fm-product.css`
styles it as an in-flow flex item.

This is not cosmetic. The first version positioned the chip absolutely over the
pill and it covered the first letter of every colour name ("Forest Green" read
as "orest Green"). See the mistake list below.

### The effect lasts exactly as long as the work

`resources/views/frontend/fm/pages/product-details.blade.php`, the `fmVariant`
module:

- `pendingStart()` switches `.fm-detail-pending` onto `<body>` when a request
  goes out. The CSS paints a shimmer over the photo and the price box.
- `pendingInFlight` counts outstanding requests. The effect is lifted when the
  **last** answer arrives, not the first.
- `MIN_PENDING_MS = 320` is a floor, not a duration. A 40ms answer would
  otherwise flash a shimmer and read as a glitch; above the floor the page stops
  pretending and simply shows the result.
- `pendingEnd()` is called from `resolve()` **after** `apply(bundle)`, so the
  result is on screen before the effect lifts.
- `prefers-reduced-motion: reduce` turns the shimmer into a flat wash. The state
  change still happens, it just does not animate.

## Mistakes I made, and what they cost

### Mistake 1: `first()` returns stdClass, I read it as an array

I wrote `is_array($valueRow) ? $valueRow['code'] : ''`. `DB::table()->first()`
returns a `stdClass`, so `is_array()` was always false and every colour came
back null. The page showed plain pills with no swatch and no error anywhere.

I chased this for a while through the view cache and `optimize:clear` before
finally running the query by hand and noticing the type. Cost: about ten minutes
of clearing caches that were never the problem.

Lesson: when a value is mysteriously null, print its type before touching the
cache.

### Mistake 2: My first overlap check was meaningless

I measured `chipBottom > labelTop` and reported "overlap 15px". Both elements
were side by side — I was comparing the vertical extents of two horizontally
adjacent boxes and calling it a collision. The proof passed for the wrong reason.

Fixed by testing real rectangle intersection in both axes
(`work/colour-proof.mjs`).

### Mistake 3: I claimed the effect ended before the request

A tracking harness reported `request 1300ms` against `effect 400ms` and I
concluded the effect was not covering the request. It was not true. The harness
was picking the wrong resource-timing entry, because the group-selection clicks
were still in flight and `clearResourceTimings()` had not removed the entries
already buffered. Re-measured with a MutationObserver
(`work/pending-gap.mjs`), photo, price and the effect lift all happen in the
same synchronous block: 0.0ms apart, three rounds out of three.

I nearly "fixed" a bug that did not exist. I only caught it because the numbers
were too clean to be true.

### Mistake 4: A wrapper around `window.fetch` broke the page

To time the request I wrapped `window.fetch`. That broke the page's own call —
the price stopped changing and one round produced a negative 16-second duration.
Abandoned in favour of Resource Timing and MutationObserver, which are recorded
by the browser and cannot be influenced by the code under test.

### Mistake 5: The effect really did drop early — found by that failure

The broken harness accidentally surfaced a real bug. Clicking a second colour
before the first answer lands leaves two requests in the air. The older, faster
answer was calling `pendingEnd()` and switching the effect off while the newer
request was still running, so the page sat waiting with nothing signalling that
it was working — exactly the feeling the owner asked to remove.

Fixed with the `pendingInFlight` counter. Before the fix: 1/3 rounds passed.
After: 3/3.

## The pending gap that really existed

After the in-flight fix, a precise observer still showed the effect lifting at
1502ms and the price changing at 1573ms — 71ms of screen that looked finished
but had not updated.

Cause: `pendingEnd()` was chained inside `request()`, so it ran one microtask
before the caller applied the result.

Fix: release from `resolve()` after `apply(bundle)`.

`work/pending-gap.mjs`, three rounds:

```
photo updated at   1554.9 ms
price updated at   1554.9 ms
effect lifted at   1554.9 ms
result leads the effect by 0.0 ms
```

## Proof

### Colour and pending together — `work/colour-proof.mjs`, 12/12

```
PASS  Colour swatches render for real hex values  — 3/5 pills have a chip
PASS  Each chip carries the admin hex  — Forest Green=#14532d, Sunset Amber=#b45309, Rosewood=#9f1239
PASS  No swatch is invented where no colour exists  — plain pills: Small, Large
PASS  No chip covers its label  — overlap -8x16px (negative = clear of)
PASS  Chip sits beside the label, not above it
PASS  Pending effect turns on when the click happens  — at +37ms
PASS  Pending effect is released after the answer  — released at +1457ms
PASS  Nothing stays stuck in the pending state
PASS  Photo follows the colour  — fm_825a1beb86d5308d.jpg -> fresh-carrots.jpg
PASS  Price follows the colour  — Tk 499 -> Tk 720
PASS  SKU follows the colour  — FC-0001 -> ZEV-3-6
PASS  No uncaught JS errors
```

### Overlapping clicks — `work/pending-overlap.mjs`, 3/3

Two clicks 300ms apart, while the endpoint really needs ~1.2s.

```
requests on the wire: 2 started, 2 answered  ["[\"3\",\"5\"]","[\"3\",\"6\"]"]
VERDICT: PASS - effect never drops while a request is open
```

### No dead screen — `work/pending-gap.mjs`, 3/3

Photo, price and the effect lift all land in the same mutation, 0.0ms apart.

### Endpoint cost, measured

```
variant-detail: 1.119898s  (http 200)
variant-detail: 1.062412s  (http 200)
variant-detail: 1.085579s  (http 200)
```

The effect covers this honestly. It is not hiding a fast endpoint behind a
decorative delay, and it is not pretending a slow one is fast.

## Screenshots

- `work/colour-selected.png` — three real swatches, the selected one ringed,
  price Tk 720, SKU ZEV-3-6.
- `work/pending-midflight.png` — the page while the request is in flight: photo
  dimmed, price box shimmering, labels read "Select size:" / "Select colour:".

## Honest limits

1. **The demo colour data is mine, not the shop's.** `attributes` rows 2 and 3
   (`size`, `colour`) and six `product_variants` on product 1 (SKU `ZEV-*`) were
   created by hand to prove the mechanism. They are demo rows. A real shop needs
   to create its own attribute and enter a hex in `attribute_values.code`.
2. **The admin has no colour picker.** The hex has to be typed by hand into the
   "Code (Optional)" box. The frontend reads it correctly, but the admin form
   does not yet guide or validate it. This is the obvious next piece of work.
3. **The admin variant form still does not save attributes** (bug 1 above). Until
   that is fixed, a shopkeeper cannot create these variants from the admin and
   the demo rows are the only source.
4. **Product photos for the demo variants are reused stock images** from the
   design source, matched by name. They are placeholders chosen to prove the
   photo follows the colour, not to represent the colour accurately.
5. **The shimmer was checked at desktop width only** (1280px). It uses the same
   classes at every width, but a mobile pass has not been run.

## Next

- Give `attribute_values.code` a real colour picker and hex validation in the
  admin, so the swatch stops depending on the shopkeeper knowing the hex format.
- Fix the admin variant form to save `attributes_json`, which is what blocks
  this whole feature from being usable without a developer.