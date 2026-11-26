/*
 * Behaviour harness for public/frontend/js/fm-qty-ceiling.js
 *
 * Loads the REAL file from disk (not a copy) in a vm context with a fake
 * window, then drives the controller the same way the product page does:
 *
 *   base product 4 units -> shopper reaches 4 -> picks a 1-unit variant
 *   -> picks a sold-out variant -> clears the pick and the base ceiling is back
 *
 * Run: node tools/verify/variant_ceiling_live.mjs
 */
import { readFileSync } from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const modulePath = path.join(root, 'public/frontend/js/fm-qty-ceiling.js');
const source = readFileSync(modulePath, 'utf8');

const fakeWindow = {};
vm.runInNewContext(source, { window: fakeWindow }, { filename: modulePath });

const api = fakeWindow.FmQtyCeiling;
assert.ok(api && typeof api.create === 'function', 'fm-qty-ceiling.js must expose window.FmQtyCeiling.create');
assert.equal(typeof api.normalizeQty, 'function', 'normalizeQty must be public for the harness');

/* ---------------------------------------------------------------- *
 * normalizeQty: never invent stock
 * ---------------------------------------------------------------- */
assert.equal(api.normalizeQty(4), 4);
assert.equal(api.normalizeQty('3'), 3);
assert.equal(api.normalizeQty(0), 0);
assert.equal(api.normalizeQty(-2), 0);
assert.equal(api.normalizeQty('abc'), 0);
assert.equal(api.normalizeQty(null), 0);
assert.equal(api.normalizeQty(undefined), 0);
console.log('PASS: normalizeQty treats anything non-positive as 0');

/* ---------------------------------------------------------------- *
 * The stepper, exactly as the page drives it
 * ---------------------------------------------------------------- */
function fakeInput(value) {
    const attrs = Object.create(null);
    return {
        value: String(value),
        setAttribute(name, next) { attrs[name] = String(next); },
        attr(name) { return Object.prototype.hasOwnProperty.call(attrs, name) ? attrs[name] : null; }
    };
}

const input = fakeInput(1);
const plus = { disabled: false };
const minus = { disabled: false };
let syncs = 0;

const ceiling = api.create({
    input,
    plus,
    minus,
    sync() { syncs += 1; }
});

// 1. Page load: the product can ship 4.
ceiling.set(4);
assert.equal(ceiling.max(), 4);
assert.equal(input.attr('max'), '4');
assert.equal(input.attr('data-max-stock'), '4');
assert.equal(plus.disabled, false, 'plus must be usable below the ceiling');
assert.equal(minus.disabled, true, 'minus must be disabled at 1');
console.log('PASS: base product publishes a ceiling of 4');

// 2. Shopper walks up to the ceiling.
input.value = '4';
ceiling.refresh();
assert.equal(plus.disabled, true, 'plus must disable exactly at the ceiling');
assert.equal(minus.disabled, false);
console.log('PASS: plus disables at the ceiling');

// 3. Picking a 1-unit variant lowers the ceiling and clamps the value.
ceiling.set(1);
assert.equal(ceiling.max(), 1);
assert.equal(input.attr('max'), '1');
assert.equal(input.attr('data-max-stock'), '1');
assert.equal(input.value, '1', 'a value above the new ceiling must be clamped');
assert.equal(syncs, 1, 'the hidden form mirror must be updated when the value is clamped');
assert.equal(plus.disabled, true, 'the only unit is already selected');
console.log('PASS: variant pick 4 -> 1 clamps the value and disables plus');

// 4. A sold-out variant reports zero available without inventing one.
ceiling.set(0);
assert.equal(input.attr('data-max-stock'), '0', 'zero must be published as zero');
assert.equal(input.attr('max'), '1', 'min=1 field keeps a usable max');
assert.equal(plus.disabled, true, 'nothing available must block plus');
console.log('PASS: sold-out variant publishes 0 and blocks plus');

// 5. Clearing the pick restores the product ceiling.
ceiling.set(4);
assert.equal(ceiling.max(), 4);
assert.equal(input.attr('data-max-stock'), '4');
assert.equal(plus.disabled, false, 'a restored ceiling re-enables plus');
console.log('PASS: reset restores the base ceiling of 4');

// 6. A second pick of a 2-unit variant moves the clamp with it.
input.value = '1';
ceiling.set(2);
input.value = '3'; // what two plus clicks would write without a clamp
ceiling.refresh();
assert.equal(plus.disabled, true, 'a stale page-load ceiling must not survive');
console.log('PASS: the live ceiling, not the page-load one, drives the clamp');

console.log('VARIANT_CEILING_HARNESS_OK');
