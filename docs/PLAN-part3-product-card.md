# Part 3 — Product card rebuild (reference: work/4db2d5a5-...png)

## What the reference shows (measured by reading the image)

| Element | Reference detail |
|---|---|
| Card shell | white, rounded-2xl, 1px light border, no visible shadow at rest |
| Card padding | generous — ~14px, and the user explicitly asked for a **slightly taller card** so text is not cramped |
| Media | aspect-square, rounded-xl, `object-cover` |
| Corner badge | top-left pill. Two variants: solid green (e.g. "Save 20%") and outlined cream/amber ("Artisan Crafted", "Free Range", "Baked Fresh") |
| Wishlist | top-right circle button, white/90, small heart |
| Title | single line, bold, dark |
| Price row | bold green price + struck-through grey old price, one line |
| Actions | **two side-by-side buttons**: `Add to Cart` (outlined, white, green border+text, cart icon) and `Order Now` (solid dark green, white text, lightning icon) |

## The user's three explicit requirements

1. Follow the reference layout and card style.
2. Keep padding + vertical spacing consistent, and make the card **slightly taller**
   so the content does not look cramped.
3. **Keep our existing extra button.** Our current card has only `+ Add`. The
   reference has `Add to Cart` + `Order Now`. The user said "আমাদের তো ডিফল্ট
   আরো একটা বাটন আছে বাই নাও" — do not lose our default extra button.

### Resolution for (3)
"আমাদের ডিফল্ট আরো একটা বাটন" is read as: the reference's *second* action
(`Order Now`) is the extra button, and it must not be dropped. So the final
card carries both, side by side, exactly as the reference shows them.

**No `order_now` route exists** — measured. Let me confirm before wiring it. If
there is no route, the button posts the existing `cart.store` form with the same
product id, which is the honest implementation (same action, primary emphasis),
and that is stated in the code comment rather than pretended to be a checkout.

## Files to change

- `resources/views/frontend/fm/components/product-card.blade.php` — markup
- `public/frontend/css/fm-products.css` — appended block (no utility class is
  invented; layout rules only, and the design's Material-3 tokens are NOT
  copied because they are absent from our Tailwind config)

## Verification plan

- 20 products must exist first, otherwise the grid is empty and nothing can be
  seen. Seeding is Part 7 but the card needs it, so seeding moves up.
- Screenshot at 1440 / 1024 / 768 / 375.
- `scrollW < innerW` at every width (no horizontal overflow).
- Buttons must both be at least 34px tall and visible (measured, not eyeballed).