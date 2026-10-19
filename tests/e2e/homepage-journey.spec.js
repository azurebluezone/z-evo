const { test, expect } = require('@playwright/test');

test.describe('Homepage User Journey', () => {
    test('Complete user journey: visit, search, cart, checkout', async ({ page }) => {
        // Capture console errors and 404s
        const consoleErrors = [];
        const responseErrors = [];

        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });

        page.on('response', response => {
            if (response.status() >= 400) {
                responseErrors.push(`${response.status()} ${response.url()}`);
            }
        });

        // 1. Visit Home Page
        await page.goto('/');
        await expect(page).toHaveTitle(/Visermart/);
        console.log('✅ Home page loaded successfully');

        // Check for console errors on home page
        expect(consoleErrors.length).toBe(0);
        expect(responseErrors.length).toBe(0);

        // 2. Search for a specific product
        const searchInput = page.locator('input[name="search"]');
        await expect(searchInput).toBeVisible();

        await searchInput.fill('soccer ball');
        await page.locator('button[type="submit"]').click();
        await page.waitForURL('**/products**search=soccer**');

        console.log('✅ Search functionality working');

        // Wait for search results to load
        await page.waitForSelector('.product-card', { timeout: 10000 });
        const productCards = page.locator('.product-card');
        await expect(productCards).toHaveCount({ min: 1 });

        // 3. Click on a product card and add it to the cart
        const firstProductCard = productCards.first();
        await expect(firstProductCard).toBeVisible();

        // Click on the product to view details
        const productName = await firstProductCard.locator('.title a').first().textContent();
        await firstProductCard.locator('.title a').first().click();

        console.log(`✅ Navigated to product: ${productName}`);

        // On product page, add to cart
        const addToCartButton = page.locator('.add-to-cart-btn').first();
        await expect(addToCartButton).toBeVisible();
        await addToCartButton.click();

        // Wait for cart notification
        await page.waitForSelector('.iziToast-message', { timeout: 5000 });
        console.log('✅ Product added to cart');

        // 4. Complete a guest checkout process (COD)
        // Go to cart page
        await page.goto('/cart');
        await expect(page).toHaveURL(/\/cart/);
        console.log('✅ Navigated to cart page');

        // Proceed to checkout
        const checkoutButton = page.locator('a[href="/checkout"]');
        await expect(checkoutButton).toBeVisible();
        await checkoutButton.click();

        console.log('✅ Proceeded to checkout');

        // On checkout page, fill in guest details
        await page.waitForURL('**/checkout**');

        // Fill guest information
        await page.locator('#first_name').fill('John');
        await page.locator('#last_name').fill('Doe');
        await page.locator('#email').fill('john.doe@example.com');
        await page.locator('#phone').fill('1234567890');
        await page.locator('#address').fill('123 Main St');
        await page.locator('#city').fill('Anytown');
        await page.locator('#zip').fill('12345');

        // Select COD payment method
        const codPaymentMethod = page.locator('input[value="cod"]');
        if (await codPaymentMethod.count() > 0) {
            await codPaymentMethod.check();
        }

        console.log('✅ Filled guest checkout information');

        // Place order
        const placeOrderButton = page.locator('button[type="submit"]');
        await expect(placeOrderButton).toBeVisible();
        await placeOrderButton.click();

        // Wait for order confirmation
        await page.waitForURL('**/checkout**success**', { timeout: 15000 });
        console.log('✅ Order placed successfully');

        // 5. Console Check: Report any JavaScript errors or 404s during the journey
        console.log('\n📋 Console Errors:', consoleErrors);
        console.log('🔗 Response Errors (400+):', responseErrors);

        expect(consoleErrors).toEqual([]);
        expect(responseErrors).toEqual([]);

        console.log('✅ No JavaScript errors or 404s detected during journey');
    });

    test('Check homepage sections load properly', async ({ page }) => {
        // Navigate to homepage
        await page.goto('/');

        // Verify key sections are present
        await expect(page.locator('.hero-slider')).toBeVisible();
        await expect(page.locator('.service-section')).toBeVisible();
        await expect(page.locator('.product-card')).toHaveCount({ min: 1 });
        await expect(page.locator('.banner-slider')).toBeVisible();

        console.log('✅ All homepage sections loaded properly');
    });

    test('Test cart functionality without login', async ({ page }) => {
        // Go to homepage
        await page.goto('/');

        // Find and click first product
        const firstProduct = page.locator('.product-card').first();
        await expect(firstProduct).toBeVisible();

        // Add to cart directly from homepage
        const addToCartBtn = firstProduct.locator('.add-to-cart-btn').first();
        await expect(addToCartBtn).toBeVisible();
        await addToCartBtn.click();

        // Check for success notification
        await expect(page.locator('.iziToast-message')).toContainText('Product added to cart');

        console.log('✅ Cart functionality works without login');
    });
});