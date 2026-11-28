#!/usr/bin/env node
/**
 * Home storefront acceptance check.
 *
 * The card must be the storefront's existing card, not a new design: the
 * shared actions partial may only use classes the template already ships
 * (.add-to-cart-btn, .btn--base) and the discount badge must use the theme's
 * own .badge--danger token. The headless pass then measures the live page:
 * recommended/flash sliders visible, badge contrast readable, both actions on
 * the card.
 *
 * Prints HOME_STOREFRONT_OK only when every assertion passes.
 */
'use strict';

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..', '..');
const base = process.env.APP_URL || 'http://127.0.0.1:8000';

const failures = [];
const notes = [];

function read(rel) {
    return fs.readFileSync(path.join(root, rel), 'utf8');
}

function check(condition, message) {
    if (condition) {
        notes.push('PASS ' + message);
    } else {
        failures.push(message);
    }
}

// ---------------------------------------------------------------------------
// 1. Static: every classic home rail uses the shared two-action partial.
// ---------------------------------------------------------------------------
const cardFiles = [
    'resources/views/frontend/sections/featured-products.blade.php',
    'resources/views/frontend/sections/best-selling.blade.php',
    'resources/views/frontend/sections/discount-products.blade.php',
    'resources/views/frontend/sections/flash-sale.blade.php',
    'resources/views/frontend/sections/recommended-products.blade.php',
    'resources/views/frontend/sections/partials/_home-product-cards.blade.php',
    'resources/views/frontend/sections/partials/_recommended-product-cards.blade.php',
];

for (const rel of cardFiles) {
    const body = read(rel);
    check(
        body.includes("@include('frontend.sections.partials._product-card-actions')"),
        rel + ' includes the shared card actions'
    );
    check(!body.includes('Choose Options'), rel + ' no longer renders Choose Options');
    check(!body.includes('badge bg-danger'), rel + ' no longer uses the unstyled bg-danger badge');
    if (body.includes('$discountPercent')) {
        check(body.includes('badge badge--danger'), rel + ' uses the theme danger badge for discounts');
    }
}

const actions = read('resources/views/frontend/sections/partials/_product-card-actions.blade.php');
check(actions.includes('Add to Cart'), 'card actions render Add to Cart');
check(actions.includes('Buy Now'), 'card actions render Buy Now');
check(actions.includes("route('cart.store.buy')"), 'simple products Buy Now posts to cart.store.buy');
check(actions.includes('product-card-order-now'), 'Buy Now reuses the card\'s own product-card-order-now action');
check(actions.includes('add-to-cart-btn'), 'card actions reuse the existing add-to-cart-btn style');
check(!actions.includes('buy-now-btn'), 'card actions do not invent a .buy-now-btn design');
check(!actions.includes('product-card-actions'), 'card actions do not invent a custom layout wrapper');
check(actions.includes('Out of Stock'), 'sold-out cards render Out of Stock');

// ---------------------------------------------------------------------------
// 2. Static: slider init + theme-aware categories route.
// ---------------------------------------------------------------------------
const owl = read('public/assets/templates/basic/js/owl-carousel.js');
check(owl.includes("'.flash-sell-slider'"), 'owl-carousel.js initialises .flash-sell-slider');

const routes = read('routes/web.php');
check(
    routes.includes("view('frontend.pages.categories', compact('categories'))"),
    '/categories resolves through the active theme'
);
check(
    !routes.includes("view('frontend.fm.pages.categories', compact('categories'))"),
    '/categories no longer hardcodes the modern view'
);

// ---------------------------------------------------------------------------
// 3. Live HTTP: the running server renders the new markup.
// ---------------------------------------------------------------------------
async function fetchText(url) {
    const res = await fetch(url, { headers: { Accept: 'text/html' } });
    return { status: res.status, body: await res.text() };
}

async function liveChecks() {
    let home;
    try {
        home = await fetchText(base + '/');
    } catch (error) {
        failures.push('home page unreachable at ' + base + ': ' + error.message);
        return;
    }

    check(home.status === 200, 'home page returns 200 (got ' + home.status + ')');
    check((home.body.match(/Buy Now/g) || []).length > 0, 'home cards render Buy Now');
    check((home.body.match(/Add to Cart/g) || []).length > 0, 'home cards render Add to Cart');
    check(!home.body.includes('Choose Options'), 'home cards no longer show Choose Options');
    check(home.body.includes('badge badge--danger'), 'home cards render the theme discount badge');
    check(!home.body.includes('buy-now-btn'), 'home page has no invented buy-now-btn class');

    const categories = await fetchText(base + '/categories');
    check(categories.status === 200, '/categories returns 200 (got ' + categories.status + ')');
    check(categories.body.includes('category-tree'), 'classic theme renders the classic category tree');
    check(!categories.body.includes('fm-page-categories'), 'classic theme no longer renders the modern category page');
}

// ---------------------------------------------------------------------------
// 4. Headless browser: real visibility, badge contrast, both card actions.
// ---------------------------------------------------------------------------
function contrastRatio(rgbA, rgbB) {
    const lum = (rgb) => {
        const [r, g, b] = rgb.map((v) => {
            const c = v / 255;
            return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };
    const a = lum(rgbA);
    const b = lum(rgbB);
    return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
}

function parseRgb(value) {
    const match = String(value).match(/rgba?\(([^)]+)\)/);
    if (!match) return null;
    const parts = match[1].split(',').map((n) => parseFloat(n.trim()));
    const alpha = parts.length >= 4 ? parts[3] : 1;
    if (alpha === 0) return null;
    return { rgb: parts.slice(0, 3), alpha };
}

/** Composite a possibly translucent colour over white (the card surface). */
function overWhite(colour) {
    if (!colour) return null;
    return colour.rgb.map((c) => Math.round(c * colour.alpha + 255 * (1 - colour.alpha)));
}

async function browserChecks() {
    let chromium;
    try {
        ({ chromium } = require('playwright'));
    } catch (error) {
        failures.push('playwright is not installed: ' + error.message);
        return;
    }

    const browser = await chromium.launch({ headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
        await page.goto(base + '/', { waitUntil: 'networkidle', timeout: 45000 });

        const sliders = await page.evaluate(() => {
            return Array.from(document.querySelectorAll('.flash-sell-slider')).map((el) => {
                const style = window.getComputedStyle(el);
                const cards = el.querySelectorAll('.product-card');
                let visible = 0;
                cards.forEach((card) => {
                    const rect = card.getBoundingClientRect();
                    if (rect.width > 0 && rect.height > 0) visible += 1;
                });
                return {
                    display: style.display,
                    cards: cards.length,
                    visible,
                    initialized: el.classList.contains('owl-loaded'),
                };
            });
        });

        check(sliders.length > 0, 'recommended/flash sliders exist');
        check(
            sliders.length > 0 && sliders.every((s) => s.display !== 'none'),
            'every .flash-sell-slider is visible (not display:none)'
        );
        check(
            sliders.length > 0 && sliders.every((s) => s.initialized),
            'every .flash-sell-slider is owl-initialised'
        );
        check(
            sliders.length > 0 && sliders.every((s) => s.visible > 0),
            'every .flash-sell-slider shows product cards'
        );

        const badge = await page.evaluate(() => {
            const el = document.querySelector('.product-card .badge.badge--danger');
            if (!el) return { found: false };
            const style = window.getComputedStyle(el);
            const card = el.closest('.product-card');
            const cardStyle = card ? window.getComputedStyle(card) : null;
            return {
                found: true,
                background: style.backgroundColor,
                color: style.color,
                cardBackground: cardStyle ? cardStyle.backgroundColor : 'rgb(255, 255, 255)',
                text: (el.textContent || '').trim(),
            };
        });

        check(badge.found, 'discount badge exists on the home page');
        if (badge.found) {
            const fg = overWhite(parseRgb(badge.color));
            const bg = overWhite(parseRgb(badge.background));
            check(!!fg && !!bg, 'discount badge paints a real text and background colour');
            if (fg && bg) {
                const ratio = contrastRatio(fg, bg);
                check(ratio >= 3, 'discount badge contrast is readable (' + ratio.toFixed(2) + ':1)');
            }
            check(/^-\d+%$/.test(badge.text), 'discount badge shows a percentage (' + badge.text + ')');
        }

        const actions = await page.evaluate(() => {
            const card = document.querySelector('.product-card');
            if (!card) return { found: false };
            const add = card.querySelector('.addToCart, .add-to-cart-btn:not(.product-card-order-now)');
            const buy = card.querySelector('.product-card-order-now');
            const addRect = add ? add.getBoundingClientRect() : null;
            const buyRect = buy ? buy.getBoundingClientRect() : null;
            return {
                found: true,
                add: !!add,
                buy: !!buy,
                buyAboveAdd: !!(addRect && buyRect) && buyRect.top < addRect.top,
                sameWidth: !!(addRect && buyRect) && Math.abs(addRect.width - buyRect.width) < 2,
            };
        });

        check(actions.found, 'a product card is rendered');
        check(actions.add, 'card offers Add to Cart');
        check(actions.buy, 'card offers Buy Now');
        check(actions.buyAboveAdd, 'Buy Now sits above Add to Cart like the /products card');
        check(actions.sameWidth, 'Buy Now and Add to Cart share the card button width');
    } finally {
        await browser.close();
    }
}

(async () => {
    await liveChecks();
    await browserChecks();

    if (failures.length > 0) {
        console.error('HOME_STOREFRONT_FAIL');
        for (const line of notes) console.error(line);
        for (const line of failures) console.error('FAIL ' + line);
        process.exit(1);
    }

    for (const line of notes) console.log(line);
    console.log('HOME_STOREFRONT_OK');
})().catch((error) => {
    console.error('HOME_STOREFRONT_FAIL');
    console.error(error && error.stack ? error.stack : error);
    process.exit(1);
});
