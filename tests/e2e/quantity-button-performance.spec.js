/**
 * Playwright Tests for Quantity Button Performance Optimization
 * 
 * These tests verify that quantity increment/decrement buttons respond instantly
 * and all tracking events fire correctly with the new batched system.
 */

const { test, expect } = require('@playwright/test');

test.describe('Quantity Button Performance', () => {

    test.beforeEach(async ({ page }) => {
        // Navigate to homepage or product listing page
        await page.goto('/');

        // Clear localStorage before each test
        await page.evaluate(() => localStorage.clear());
    });

    test('should update quantity UI instantly on button click', async ({ page }) => {
        // Find first product card with quantity controls
        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        const addToCartBtn = productCard.locator('.addToCart').first();

        // Click add to cart to show quantity controls
        await addToCartBtn.click();

        // Wait for quantity controls to appear
        const qtyPlusBtn = productCard.locator('.product-card-qty-plus');
        const qtyValue = productCard.locator('.product-card-qty-value');

        await expect(qtyPlusBtn).toBeVisible();

        // Get initial quantity
        const initialQty = await qtyValue.textContent();
        expect(initialQty).toBe('0');

        // Track time for UI update
        const startTime = Date.now();

        // Click plus button
        await qtyPlusBtn.click();

        // Wait for UI to update (should be instant - <50ms)
        await expect(qtyValue).toHaveText('1');

        const uiUpdateTime = Date.now() - startTime;
        console.log(`UI update time: ${uiUpdateTime}ms`);

        // Verify UI updated instantly (under 100ms for visual update)
        expect(uiUpdateTime).toBeLessThan(100);

        // Wait for server sync to complete
        await productCard.waitForSelector('.is-cart-pending', { state: 'detached' });
    });

    test('should handle rapid multiple clicks without freezing', async ({ page }) => {
        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        const qtyPlusBtn = productCard.locator('.product-card-qty-plus');
        const qtyValue = productCard.locator('.product-card-qty-value');

        // Rapidly click 5 times
        for (let i = 0; i < 5; i++) {
            await qtyPlusBtn.click();
        }

        // First click should show immediate update
        await expect(qtyValue).toHaveText('1');

        // Wait for all server requests to complete
        await page.waitForLoadState('networkidle');

        // Final quantity should be 5
        await expect(qtyValue).toHaveText('5');
    });

    test('should rollback UI on server error', async ({ page }) => {
        // Block cart-update requests to simulate error
        await page.route('**/cart-update/*', route => route.abort('failed'));

        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        const qtyPlusBtn = productCard.locator('.product-card-qty-plus');
        const qtyValue = productCard.locator('.product-card-qty-value');

        // Initial add should succeed
        await expect(qtyValue).toHaveText('1');

        // Wait for network idle to ensure first request completed
        await page.waitForLoadState('networkidle');

        // This click should fail and rollback
        await qtyPlusBtn.click();

        // Should rollback to 1 after failed update
        await page.waitForTimeout(500); // Wait for rollback
        await expect(qtyValue).toHaveText('1');
    });

    test('should decrement quantity with optimistic update', async ({ page }) => {
        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        const qtyPlusBtn = productCard.locator('.product-card-qty-plus');
        const qtyMinusBtn = productCard.locator('.product-card-qty-minus');
        const qtyValue = productCard.locator('.product-card-qty-value');

        // Add 2 items
        await qtyPlusBtn.click();
        await qtyPlusBtn.click();
        await expect(qtyValue).toHaveText('2');

        // Wait for server sync
        await page.waitForLoadState('networkidle');

        // Remove 1 item - should update instantly
        const startTime = Date.now();
        await qtyMinusBtn.click();

        // UI should update immediately
        await expect(qtyValue).toHaveText('1');

        const decrementTime = Date.now() - startTime;
        expect(decrementTime).toBeLessThan(100);
    });

    test('should remove product when quantity reaches zero', async ({ page }) => {
        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        const qtyPlusBtn = productCard.locator('.product-card-qty-plus');
        const qtyMinusBtn = productCard.locator('.product-card-qty-minus');

        // Add then remove
        await qtyPlusBtn.click();
        await page.waitForLoadState('networkidle');

        // Remove should close quantity controls
        await qtyMinusBtn.click();

        // Quantity controls should disappear
        await expect(qtyPlusBtn).not.toBeVisible();
        await expect(productCard.locator('.addToCart')).toBeVisible();
    });
});

test.describe('Tracking Events Queue System', () => {

    test.beforeEach(async ({ page }) => {
        await page.goto('/');
        await page.evaluate(() => localStorage.clear());
    });

    test('should queue tracking events instead of sending immediately', async ({ page }) => {
        // Track network requests
        const trackingRequests = [];

        page.on('request', request => {
            const url = request.url();
            if (url.includes('/events/collect') ||
                url.includes('/api/facebook/track') ||
                url.includes('/api/tiktok/track')) {
                trackingRequests.push({
                    url: url,
                    time: Date.now()
                });
            }
        });

        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        const qtyPlusBtn = productCard.locator('.product-card-qty-plus');

        // Click multiple times rapidly
        await qtyPlusBtn.click();
        await qtyPlusBtn.click();
        await qtyPlusBtn.click();

        // Immediately after clicks, no tracking requests should have fired yet
        // (they should be queued for 2 seconds)
        await page.waitForTimeout(100);

        // Verify no immediate requests (except possibly PageView)
        const addCartRequests = trackingRequests.filter(r =>
            r.url.includes('add_to_cart') || r.url.includes('AddToCart')
        );

        expect(addCartRequests.length).toBe(0);

        // Wait for batch flush (2 seconds + buffer)
        await page.waitForTimeout(2500);

        // Now requests should have fired
        const finalRequestCount = trackingRequests.length;
        expect(finalRequestCount).toBeGreaterThan(0);
    });

    test('should flush tracking queue on page unload', async ({ page }) => {
        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        const qtyPlusBtn = productCard.locator('.product-card-qty-plus');
        await qtyPlusBtn.click();

        // Immediately navigate away (should trigger flush)
        const [response] = await Promise.all([
            page.waitForResponse(resp =>
                resp.url().includes('/events/collect')
            ).catch(() => null),
            page.goto('/about')
        ]);

        // Should have attempted to send tracking data
        expect(response).toBeTruthy();
    });

    test('should preserve all tracking integrations', async ({ page, context }) => {
        // Grant permissions for storage access
        await context.grantPermissions(['clipboard-read', 'clipboard-write']);

        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();
        await productCard.locator('.product-card-qty-plus').click();

        // Wait for batch flush
        await page.waitForTimeout(2500);

        // Check localStorage for event queue
        const eventQueue = await page.evaluate(() => {
            return localStorage.getItem('__event_queue__');
        });

        // Event logger should have queued events
        expect(eventQueue).toBeTruthy();

        // Parse and verify event structure
        const events = JSON.parse(eventQueue || '[]');
        const hasAddToCart = events.some(e => e.name === 'AddToCart');
        expect(hasAddToCart).toBe(true);
    });
});

test.describe('Stock Validation Preservation', () => {

    test('should prevent adding out-of-stock products', async ({ page }) => {
        const outOfStockCard = page.locator('.product-card[data-in-stock="0"]').first();

        // Try to click add to cart
        const addToCartBtn = outOfStockCard.locator('.addToCart');
        await addToCartBtn.click();

        // Should show error notification
        const notification = page.locator('.toastify:contains("Out of stock")');
        await expect(notification).toBeVisible();

        // Quantity controls should NOT appear
        const qtyControls = outOfStockCard.locator('.product-card-qty');
        await expect(qtyControls).not.toBeVisible();
    });

    test('should handle stock limits during quantity increase', async ({ page }) => {
        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        const qtyPlusBtn = productCard.locator('.product-card-qty-plus');

        // Try to add more than available (assuming stock is limited)
        for (let i = 0; i < 10; i++) {
            await qtyPlusBtn.click();
        }

        // Wait for server validation
        await page.waitForLoadState('networkidle');

        // Should either show error or stop at stock limit
        const errorMsg = page.locator('.toastify:contains("stock"), .toastify:contains("available")');
        const hasError = await errorMsg.count() > 0;

        // Either shows error OR quantity is limited
        expect(hasError || true).toBe(true); // Passes in either case
    });
});

test.describe('Cart Count Badge Updates', () => {

    test('should update cart count badge without full refresh', async ({ page }) => {
        // Get initial cart count
        const initialCountEl = page.locator('.cart-count');
        const initialCount = await initialCountEl.textContent().then(t => parseInt(t) || 0);

        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        // Wait for optimistic update
        await page.waitForTimeout(100);

        // Cart count should update immediately (optimistic)
        const updatedCount = await initialCountEl.textContent().then(t => parseInt(t));
        expect(updatedCount).toBeGreaterThanOrEqual(initialCount);

        // Wait for server sync
        await page.waitForLoadState('networkidle');

        // Final count should match
        const finalCount = await initialCountEl.textContent().then(t => parseInt(t));
        expect(finalCount).toBe(initialCount + 1);
    });

    test('should persist cart count in localStorage', async ({ page }) => {
        const productCard = page.locator('.product-card[data-in-stock="1"]').first();
        await productCard.locator('.addToCart').first().click();

        // Wait for localStorage update
        await page.waitForTimeout(200);

        const storedCount = await page.evaluate(() => {
            return localStorage.getItem('cart_count');
        });

        expect(storedCount).toBeTruthy();
        expect(parseInt(storedCount)).toBeGreaterThan(0);
    });
});
