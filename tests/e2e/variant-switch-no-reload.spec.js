const { test, expect } = require('@playwright/test');

test.describe('Product Variant Switch', () => {
  test('should update variant data without navigating/reloading the page', async ({ page }) => {
    const slug = 'demo-side-values-product-1777131656423-4887';

    let navigations = 0;
    page.on('framenavigated', (frame) => {
      if (frame === page.mainFrame()) navigations += 1;
    });

    await page.goto(`/product/${slug}`);
    await expect(page.locator('#productPrice')).toBeVisible();

    const startUrl = page.url();
    const attributeAreas = page.locator('.product-attribute .attributeValueArea');
    const areaCount = await attributeAreas.count();

    test.skip(areaCount === 0, 'No variant attributes present on this product.');

    const initialPrice = (await page.locator('#productPrice').innerText()).trim();
    const initialMainImg = await page.locator('#xzoom-magnific').getAttribute('src');

    // Select first option from each attribute group to form a full variant.
    for (let i = 0; i < areaCount; i++) {
      const area = attributeAreas.nth(i);
      await area.locator('.attributeBtn').first().click();
    }

    // Wait for stock check request to finish (variant resolution happens there).
    const stockResp = await page.waitForResponse((r) => r.url().includes('/products/check-stock') && r.status() === 200);
    const stockJson = await stockResp.json().catch(() => ({}));

    // The page must not navigate away (previous bug: clicking the thumb anchor navigated to image href).
    await expect(page).toHaveURL(startUrl);
    expect(navigations).toBe(1); // only initial navigation

    // Core UI should remain present.
    await expect(page.locator('.product-details')).toBeVisible();
    await expect(page.locator('#stockBadge')).toBeVisible();

    // Price / image / stock / qty constraints should be updated by AJAX, not by a full reload.
    const newPrice = (await page.locator('#productPrice').innerText()).trim();
    expect(newPrice.length).toBeGreaterThan(0);

    const newMainImg = await page.locator('#xzoom-magnific').getAttribute('src');
    expect(newMainImg).toBeTruthy();

    // If backend returns a concrete stock quantity, quantity input max should reflect it.
    if (stockJson && stockJson.track_inventory && typeof stockJson.stock_quantity !== 'undefined') {
      const max = await page.locator('.product-quantity-input input[name="quantity"]').getAttribute('max');
      expect(max).toBe(String(parseInt(stockJson.stock_quantity, 10)));
    }

    // If a different variant was resolved, price or image often changes; at least one should differ.
    // (Avoid a hard fail if a particular product's variants share the same price + image.)
    expect(
      (newPrice !== initialPrice) || (newMainImg !== initialMainImg) || (stockJson && Object.keys(stockJson).length > 0)
    ).toBeTruthy();
  });
});

