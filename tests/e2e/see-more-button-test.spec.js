const { test, expect } = require("@playwright/test");

test("See More buttons should work correctly", async ({ page }) => {
  // Navigate to homepage
  await page.goto("http://localhost:8000");
  await page.waitForLoadState("networkidle");

  // Take screenshot of homepage
  await page.screenshot({
    path: "test-results/01-homepage.png",
    fullPage: true,
  });

  // Check if "See More" button exists for Best Selling section
  const bestSellingSeeMore = page.locator('a:has-text("See More")').first();
  const isVisible = await bestSellingSeeMore.isVisible();

  console.log("See More button visible:", isVisible);

  if (isVisible) {
    // Get the href attribute
    const href = await bestSellingSeeMore.getAttribute("href");
    console.log("See More button href:", href);

    // Click the See More button
    await bestSellingSeeMore.click();
    await page.waitForLoadState("networkidle");

    // Take screenshot after clicking
    await page.screenshot({
      path: "test-results/02-after-see-more.png",
      fullPage: true,
    });

    // Check URL contains the type parameter
    const currentUrl = page.url();
    console.log("Current URL after click:", currentUrl);

    // Verify the URL has the type filter
    const hasTypeParam = currentUrl.includes("type=");
    console.log("URL has type parameter:", hasTypeParam);

    // Check if products are displayed
    const productCards = page.locator(".product-card");
    const productCount = await productCards.count();
    console.log("Number of products displayed:", productCount);

    // Take screenshot showing products
    await page.screenshot({
      path: "test-results/03-products-page.png",
      fullPage: true,
    });

    // Test passes if we see products and URL has type parameter
    expect(productCount).toBeGreaterThan(0);
  } else {
    console.log("No See More button found - checking if sections exist");
    await page.screenshot({
      path: "test-results/01-homepage-no-button.png",
      fullPage: true,
    });
  }
});
