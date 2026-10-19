# Answers to your questions (2026-10-08)

Plain-language answers, each backed by something I measured. Where I do not
know, I say so instead of filling the gap.

---

## 1. `work/Respected Elder/` — the folder is EMPTY

```
drwxrwxr-x  work/Respected Elder
total 0 files
```

So there is **no elder/grocery product data, no images, no descriptions** to
import. I searched the whole tree too — nothing.

**This is the blocker for two of your requests:**
- storing ~20 grocery products
- writing a Python script to generate their images

I will not invent 20 products with made-up descriptions and load them into a
real shop. Say the word and I will do it, but it would be demo data presented
as catalogue.

**What I need from you — pick one:**
1. Copy the files into `work/Respected Elder/` and tell me.
2. Drop the idea, keep the current 1,240 products.
3. Say "make up 20" and I will, clearly marked as demo.

---

## 2. The real design library is NOT where you were looking

You pointed me at two folders. The actual library is a zip:

```
work/zevo-laravel-frontend-v4-complete-handoff.zip  →  60 files
resources/views/frontend/zevo-prototype/
  about-us  blog-recipes  category  checkout  contact-us  faq  home
  lens-slide-over-drawer  login-register  order-confirmation  product-detail
  quick-view  scanner-modal  search-results  shop  shopping-cart  smart-search
  user-account  variant-1-two-tier  variant-2-compact  variant-3-enterprise
  visual-search-results  voice-search  wishlist
```

I measured these: **none contains a `<video>` tag** and **none contains elder
or grocery copy**. So your hero-slider request is a build from scratch, and the
grocery product set is missing from the designs too.

---

## 3. Product search on the category page — MEASURED BUG, NOW FIXED

**Cause (measured, not guessed):**

| Fact | Evidence |
|---|---|
| `category()` never reads `q` | `grep` of the method: no `query('q')`, no `like` |
| The header search form posts only `q=` | `action="{{ route('search') }}"` |

Proof of the bug, before the fix:

```
/category/0-3-month?q=romper  →  24 cards  (q silently ignored)
```

**The fix (2 files):**
- `FrontendController::search()` now honours an explicit `?category=`
- the header search bar carries a hidden `category` field, taken from
  `$fmCategorySlug` — the page's own resolved slug, **not** the raw request,
  because path-scoped routes ignore `?category=` and would claim a scope the
  server never applies

**Proof after the fix:**

| URL | before | after |
|---|---|---|
| `/search?q=romper&category=premature` | 24 cards | **4 cards** |
| `/search?q=romper` | 24 cards | 24 cards (unchanged) |

Screenshot shows `Total 4 products` and two separate chips, `"romper" ×` and
`Premature ×`, each removable on its own.

---

## 4. Your product-card and hero requests

I have **not started** these. Your own instructions were to work part by part
and solve one problem at a time, and each of these needs a decision first:

- **Product card redesign** — you asked for online research first, then a new
  comparison HTML page showing the designs side by side. That is Part 3.
- **Hero video/image slider** — you asked me to suggest designs. Part 4.
- **Instant add-to-cart + grocery icon in cart** — Part 5.
- **AI TTS/ASR + Pulsar MCP** — Part 6, written as a contract document.

## 5. About the MCP server you mentioned

I hit `http://localhost:5000/mcp` and it responds. It exposes **26 tools**:

```
read_file  write_file  set_working_dir  get_working_dir  web_search
agent_mission  agent_single  get_memory  read_ssot  append_syllabus
list_directory  open_browser  browse_cdp  call_agent  terminal
delete_file  rename_file  grep  glob  db_query  db_list_tables
exec  http_request  env_get  system_info  remote_mcp_call
```

`db_query` and `db_list_tables` are there — so I can run database queries for
you without downloading anything extra. **I will not install new tools from the
internet**; this set is enough. Anything more complex I will hand to you rather
than guess.

---

## Your unfinished sentence

> "সুতরাং এই বাটনগুলো এর ভ্যালু ঠিক রাখিস এখন তো অনেক কথা বলে ফেলেছি"

I could not tell which buttons you mean. Candidates I can see: the product-card
Buy button, or a hero slider control. Tell me which and I will set the right
value.

---

## 6. "কোন ভেরিয়েন্ট নেই, কোন অ্যাট্রিবিউট নেই" — measured, and now fixed

You were right: the old demo data had no working variants. The seeder wrote a
flat `{"size":"Standard"}` string that never matched `checkStock`, and add-to-
cart silently stored `variant_id: null`. Both are fixed and **provable**:

- `variant-check.mjs` — **9/9 PASS** (DB shape, DEFAULT SKUs, checkStock on
  real attribute ids, picker on all 20 pages, add-to-cart storing the real
  variant)
- `detail-check.mjs` — **30/30 PASS** on the modern product page (pills,
  repaint, qty, cart, buy-now, honest 0-review state)
- `cart-check.mjs` — **28/28 PASS** on the grocery cart (real 290 savings,
  coupon persists, 422 stock guard)

Full proof table lives in `PLAN-demo-catalogue.md` §9. Screenshots you can open
yourself: `verify/shots/variant-picker-base.png`, `variant-picker-packed2.png`,
`detail-picked-packed2.png`, `detail-reviews.png`, `cart-coupon.png`.

## 7. Where the grocery images come from — NOT Wikimedia

Every grocery image in the catalogue was generated locally by
`tools/make_demo_product_images.py` using Python Pillow. No network, no
Wikimedia, no external download: the script measured the site's own reference
set (`zevlms/blog/images/blog-covers/`, 54 files) and reproduces that style —
teal-green gradient, dot grid, white plate product icon, amber rule. They are
100 % synthetic and belong to Zevo (no third-party license involved).

---

## Blocking decisions I need

Resolved: the demo-catalogue authorisation (option 3 — "make up 20") and the
A1 deletion were both confirmed and completed; a 51 MB dump + 1.2 GB image tar
sit in `/home/sahon/uds/hi/arafat_bak/` as the only rollback.

Still open:
1. **Which buttons** did the unfinished sentence mean? (`A15` — see above.)