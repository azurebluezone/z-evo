const { test, expect } = require('@playwright/test');

/**
 * Header geometry contract (2026-10-24).
 *
 * These assertions encode the decisions in
 * docs/HEADER-STATUS-2026-10-24.md, so a later edit cannot silently bring back
 * the four-glyph-size bar, the duplicated Categories pill, an off-centre phone
 * logo or a sideways-scrolling header.
 */

const widths = [1440, 1280, 1024, 768, 390, 320];

async function headerMetrics(page) {
    return page.evaluate(() => {
        const box = (el) => {
            const b = el.getBoundingClientRect();
            return { left: b.left, right: b.right, width: b.width, height: b.height };
        };
        const row = document.querySelector('.fm-header-row');
        const logo = document.querySelector('.fm-header-logo');
        const actions = document.querySelector('.fm-header-actions');
        const links = [...document.querySelectorAll('.app-nav-menu a')];
        const icons = [...document.querySelectorAll('header[data-purpose="primary-header"] .bi')]
            .filter((i) => i.offsetParent !== null);
        return {
            row: box(row),
            logo: box(logo),
            actions: box(actions),
            help: box(document.querySelector('.fm-topbar-contact')),
            lang: box(document.querySelector('.fm-topbar-lang')),
            helpVisible: document.querySelector('.fm-topbar-contact').offsetParent !== null,
            langVisible: document.querySelector('.fm-topbar-lang').offsetParent !== null,
            navVisible: getComputedStyle(document.querySelector('[data-purpose="primary-navigation"]')).display !== 'none',
            // The active destination is allowed a different weight/colour; every
            // other link must share one set of metrics. Mixing the active link
            // into this set was the test bug that made 6 equal links look like 2
            // styles.
            navStyles: [...new Set(links.filter((a) => !a.classList.contains('active')).map((a) => {
                const s = getComputedStyle(a);
                return `${s.fontSize}|${s.fontWeight}|${s.color}|${s.height}|${s.paddingLeft}`;
            }))],
            navActiveStyles: [...new Set(links.filter((a) => a.classList.contains('active')).map((a) => {
                const s = getComputedStyle(a);
                return `${s.fontSize}|${s.fontWeight}|${s.color}|${s.height}`;
            }))],
            navCount: links.length,
            iconSizes: [...new Set(icons.map((i) => getComputedStyle(i).fontSize))],
            scrollWidth: document.documentElement.scrollWidth,
            innerWidth,
            categoriesPillInBar: !!document.querySelector('.fm-header-brand ~ div.hidden.xl\\:block'),
        };
    });
}

test.describe('Header layout contract', () => {
    for (const width of widths) {
        test(`${width}px: no sideways scroll, logo never collides with actions`, async ({ page }) => {
            await page.setViewportSize({ width, height: 900 });
            await page.goto('/');
            const m = await headerMetrics(page);

            expect(m.scrollWidth).toBe(m.innerWidth);
            expect(m.actions.left).toBeGreaterThanOrEqual(m.logo.right);
        });
    }

    test('desktop: one icon size, equal nav metrics, no duplicate Categories pill', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/');
        const m = await headerMetrics(page);

        expect(m.categoriesPillInBar).toBe(false);
        expect(m.navVisible).toBe(true);
        expect(m.navCount).toBe(6);
        // One size / weight / colour / height for every non-active destination.
        expect(m.navStyles).toHaveLength(1);
        // The active link may differ in weight/colour but never in size or box.
        for (const style of m.navActiveStyles) {
            const [font, , , height] = style.split('|');
            expect(font).toBe('14px');
            expect(height).toBe('36px');
        }
        // Hamburger (hidden here), search, account, wishlist, cart, AI triggers
        // and the submit glyph all share the 20px token.
        expect(m.iconSizes).toEqual(['20px']);
        expect(await page.locator('header form[role="search"] button[type="submit"] .bi')
            .evaluate((i) => getComputedStyle(i).fontSize)).toBe('20px');
    });

    test('desktop: the grid owns the gaps, not Bootstrap .gap-3', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/');

        const g = await page.evaluate(() => {
            const row = document.querySelector('.fm-header-row');
            const s = getComputedStyle(row);
            return {
                display: s.display,
                columns: s.gridTemplateColumns,
                columnGap: s.columnGap,
                searchWidth: document.querySelector('.fm-header-search').getBoundingClientRect().width,
            };
        });

        expect(g.display).toBe('grid');
        // Bootstrap 5.3 ships `.gap-3{gap:1rem!important}`; a Tailwind `gap-3`
        // on the row used to win and squeeze the search column by 16px. The
        // row carries no gap utility now, so this is the designed 1.5rem.
        expect(g.columnGap).toBe('24px');
        expect(g.columns.split(' ')).toHaveLength(3);
        expect(g.searchWidth).toBeGreaterThan(700);
    });

    test('desktop: account and language menus open inside the viewport', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/');

        await page.click('.fm-header-user > button');
        const account = page.locator('.fm-header-user .dropdown-menu');
        await expect(account).toBeVisible();
        const a = await account.boundingBox();
        expect(a.x).toBeGreaterThanOrEqual(0);
        expect(a.x + a.width).toBeLessThanOrEqual(1440);
        await page.keyboard.press('Escape');

        await page.click('.fm-topbar-lang');
        const lang = page.locator('[data-purpose="announcement-banner"] .dropdown-menu');
        await expect(lang).toBeVisible();
        const l = await lang.boundingBox();
        expect(l.x).toBeGreaterThanOrEqual(0);
        expect(l.x + l.width).toBeLessThanOrEqual(1440);
    });

    for (const width of [768, 390, 320]) {
        test(`${width}px: logo is centred, topbar has both ends`, async ({ page }) => {
            await page.setViewportSize({ width, height: 900 });
            await page.goto('/');
            const m = await headerMetrics(page);

            const logoCentre = (m.logo.left + m.logo.right) / 2;
            const rowCentre = (m.row.left + m.row.right) / 2;
            expect(Math.abs(logoCentre - rowCentre)).toBeLessThan(1);

            expect(m.navVisible).toBe(false);
            expect(m.helpVisible).toBe(true);
            expect(m.langVisible).toBe(true);
            // The ends are pinned by the container gutter, which is 12px below
            // 576 and 24px from md up - so the assertion is symmetry plus a
            // gutter ceiling, not one hard-coded 16px that only fits phones.
            const leftGutter = m.help.left;
            const rightGutter = width - m.lang.right;
            expect(leftGutter).toBeLessThanOrEqual(32);
            expect(rightGutter).toBeLessThanOrEqual(32);
            expect(Math.abs(leftGutter - rightGutter)).toBeLessThan(2);
        });
    }

    test('phone: nav drawer carries the same six destinations, no special-offer icon', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/');
        await page.click('.fm-header-brand > button[data-bs-toggle="offcanvas"]');
        await expect(page.locator('#mobileNavDrawer')).toHaveClass(/show/);

        const labels = (await page.locator('#mobileNavDrawer .fm-drawer-links a').allTextContents())
            .map((t) => t.replace(/\s+/g, ' ').trim());
        expect(labels.slice(0, 6)).toEqual([
            'Home', 'Shop Catalog', 'Categories', 'Special offers', 'Hot deals', 'Contact',
        ]);
        expect(await page.locator('#mobileNavDrawer .bi-lightning-charge-fill').count()).toBe(0);
    });

    test('phone: search drawer opens with the same 20px controls', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/');
        await page.click('.fm-search-open');
        await expect(page.locator('#fmSearchDrawer')).toHaveClass(/show/);

        const sizes = await page.evaluate(() => {
            const drawer = document.querySelector('#fmSearchDrawer');
            // Only interactive glyphs are part of the one-size contract. The
            // magnifier in the offcanvas title is a heading ornament, not a
            // control, so it is allowed its own (smaller) size.
            return [...new Set([...drawer.querySelectorAll('.bi')]
                .filter((i) => i.offsetParent !== null && i.closest('button, a'))
                .map((i) => getComputedStyle(i).fontSize))];
        });
        expect(sizes).toEqual(['20px']);
    });
});
