/*
 * Footer + Shop-by-Category design verification.
 *
 * Reads the real rendered storefront (no fixtures) at five viewport widths and
 * compares the measured geometry/colour against the /home/sahon/Pictures/home.html
 * reference. Run:
 *
 *   node tools/verify-footer-category.mjs
 *
 * Prints one PASS/FAIL line per assertion and FOOTER-CATEGORY-PASS only when
 * every assertion holds.
 */
import { chromium } from 'playwright';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const WIDTHS = [1440, 1000, 768, 390, 320];

const results = [];
function check(name, ok, detail = '') {
  results.push({ name, ok: Boolean(ok), detail });
  console.log(`${ok ? 'PASS' : 'FAIL'} ${name}${detail ? ' | ' + detail : ''}`);
}

function near(value, expected, tolerance) {
  return Math.abs(value - expected) <= tolerance;
}

// Let Playwright resolve its own Chromium build; /usr/bin/chromium-browser on
// this box is the snap stub, which exits before a page can open. Override with
// CHROME_PATH when a different build is installed.
const browser = await chromium.launch({
  executablePath: process.env.CHROME_PATH || undefined,
  headless: true,
  args: ['--no-sandbox'],
});

try {
  /*
   * Warm-up request. After `php artisan view:clear` the first hit compiles
   * every Blade partial; on this box that cold render can be cut short and
   * the HTML ends before the footer (status 200, title correct). Warming the
   * compiled views once keeps the gate measuring the rendered UI instead of
   * a cold-compile truncation.
   */
  {
    const warm = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await warm.goto(BASE, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
    await warm.close();
  }

  for (const width of WIDTHS) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const response = await page.goto(BASE, { waitUntil: 'networkidle', timeout: 30000 });
    const status = response ? response.status() : 'no-response';
    const title = await page.title();

    // Swiper initialises from the CDN bundle. The CSS rail still works without
    // it, but this contract checks the real slider, so give it a moment.
    if (width < 992) {
      await page
        .waitForFunction(
          () => document.querySelector('[data-fm-cat-swiper]')?.classList.contains('swiper-initialized'),
          { timeout: 10000 }
        )
        .catch(() => {});
    }

    const m = await page.evaluate(() => {
      const px = (value) => parseFloat(value) || 0;
      const rect = (el) => {
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return { w: Math.round(r.width), h: Math.round(r.height), x: Math.round(r.x), y: Math.round(r.y) };
      };
      const style = (el) => (el ? getComputedStyle(el) : null);
      const footer = document.querySelector('footer.app-footer');
      const grid = footer?.querySelector('.fm-footer-grid');
      const brand = footer?.querySelector('.fm-footer-brand__row');
      const brandText = footer?.querySelector('.fm-footer-brand__text');
      const social = footer?.querySelector('.fm-footer-brand__social');
      const socialBtn = footer?.querySelector('.fm-footer-brand__social a, .fm-footer-brand__social span');
      const socialPseudo = socialBtn ? getComputedStyle(socialBtn, '::after') : null;
      const bottom = footer?.querySelector('.fm-footer-bottom');
      const badge = footer?.querySelector('.fm-store-badge');
      const section = document.querySelector('section.fm-categories-section');
      const catGrid = section?.querySelector('.fm-cat-grid');
      const catRail = section?.querySelector('[data-fm-cat-swiper]');
      const slides = catRail ? Array.from(catRail.querySelectorAll('.swiper-slide')) : [];
      const slideCards = slides.map((slide) => slide.querySelectorAll('.fm-cat-card').length);
      const firstSlide = slides[0] || null;
      const firstSlideWidth = firstSlide ? Math.round(firstSlide.getBoundingClientRect().width) : 0;
      // Measure the card from the layout that is actually painted: the rail
      // is display:none on a desktop, so its rect would always be 0x0 there.
      const firstCard = (innerWidth >= 992 ? catGrid : catRail)?.querySelector('.fm-cat-card')
        || catGrid?.querySelector('.fm-cat-card')
        || catRail?.querySelector('.fm-cat-card');
      const icon = firstCard?.querySelector('.fm-cat-card__icon');
      const label = firstCard?.querySelector('.fm-cat-card__label');
      const bullets = catRail ? catRail.querySelectorAll('.swiper-pagination-bullet') : [];
      const bulletBox = bullets[0] ? bullets[0].getBoundingClientRect() : null;
      const bulletHeight = bulletBox ? Math.round(bulletBox.height) : 0;
      const title = section?.querySelector('.fm-cat-head__title');

      const footerGridCols = grid
        ? Array.from(grid.children).map((el) => ({
            cls: el.className,
            w: Math.round(el.getBoundingClientRect().width),
            x: Math.round(el.getBoundingClientRect().x),
            y: Math.round(el.getBoundingClientRect().y),
          }))
        : [];

      return {
        innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        footer: footer
          ? {
              bg: style(footer).backgroundColor,
              paddingTop: style(footer).paddingTop,
              paddingBottom: style(footer).paddingBottom,
              rect: rect(footer),
            }
          : null,
        footerGridCols,
        headings: footer
          ? Array.from(footer.querySelectorAll('.fm-footer-heading')).map((el) => ({
              size: style(el).fontSize,
              mb: style(el).marginBottom,
              color: style(el).color,
            }))
          : [],
        links: footer
          ? Array.from(footer.querySelectorAll('.fm-footer-linkcol a')).slice(0, 2).map((el) => ({
              size: style(el).fontSize,
              color: style(el).color,
              h: Math.round(el.getBoundingClientRect().height),
            }))
          : [],
        brand: brand
          ? {
              justify: style(brand).justifyContent,
              textAlign: style(brand).textAlign,
              rect: rect(brand),
            }
          : null,
        brandText: brandText
          ? {
              display: style(brandText).display,
              textAlign: style(brandText).textAlign,
              color: style(brandText).color,
              size: style(brandText).fontSize,
              rect: rect(brandText),
            }
          : null,
        social: social
          ? {
              justify: style(social).justifyContent,
              rect: rect(social),
            }
          : null,
        socialBtn: socialBtn
          ? {
              rect: rect(socialBtn),
              bg: socialPseudo?.backgroundColor || null,
              pseudoW: px(socialPseudo?.width),
              pseudoH: px(socialPseudo?.height),
            }
          : null,
        bottom: bottom
          ? {
              bg: style(bottom).backgroundColor,
              rect: rect(bottom),
              borderTop: style(bottom).borderTop,
              padding: style(bottom).padding,
              marginTop: style(bottom).marginTop,
            }
          : null,
        badge: badge
          ? {
              bg: style(badge).backgroundColor,
              border: style(badge).borderColor,
              radius: style(badge).borderRadius,
              rect: rect(badge),
            }
          : null,
        category: section
          ? {
              gridDisplay: catGrid ? style(catGrid).display : null,
              gridCols: catGrid ? style(catGrid).gridTemplateColumns : null,
              gridCards: catGrid ? catGrid.querySelectorAll('.fm-cat-card').length : 0,
              railDisplay: catRail ? style(catRail).display : null,
              railInitialized: catRail ? catRail.classList.contains('swiper-initialized') : false,
              slides: slides.length,
              slideCards,
              firstSlideWidth,
              bullets: bullets.length,
              bulletHeight,
              firstCard: rect(firstCard),
              icon: icon ? { rect: rect(icon), radius: style(icon).borderRadius, font: style(icon).fontSize } : null,
              label: label ? { size: style(label).fontSize, weight: style(label).fontWeight } : null,
              title: title ? { size: style(title).fontSize, weight: style(title).fontWeight, color: style(title).color } : null,
            }
          : null,
      };
    });

    const tag = `${width}px`;
    check(`${tag} no horizontal overflow`, m.scrollWidth <= m.innerWidth + 1, `scrollWidth=${m.scrollWidth} innerWidth=${m.innerWidth}`);
    check(`${tag} footer exists`, Boolean(m.footer), m.footer ? `h=${m.footer.rect.h}` : `missing | status=${status} title="${title}" url=${page.url()}`);
    if (!m.footer) {
      await page.close();
      continue;
    }

    check(`${tag} footer background #064525`, m.footer.bg === 'rgb(6, 69, 37)', m.footer.bg);
    check(`${tag} full-width bottom bar #04341b`, m.bottom?.bg === 'rgb(4, 52, 27)' && m.bottom.rect.w >= m.innerWidth - 2, `${m.bottom?.bg} w=${m.bottom?.rect.w}`);
    check(`${tag} bottom bar top border`, m.bottom?.borderTop.includes('1px') && m.bottom?.borderTop.includes('rgba(255, 255, 255, 0.12)'), m.bottom?.borderTop);
    check(`${tag} bottom bar 16px vertical padding`, m.bottom?.padding.startsWith('16px'), m.bottom?.padding);

    if (width >= 992) {
      check(`${tag} 5 footer columns`, m.footerGridCols.length === 5, `count=${m.footerGridCols.length}`);
      const brandCol = m.footerGridCols[0];
      const linkCols = m.footerGridCols.slice(1);
      check(
        `${tag} brand column 4/12`,
        brandCol && linkCols.length === 4 && brandCol.w > linkCols[0].w * 1.65 && brandCol.w < linkCols[0].w * 2.35,
        `brand=${brandCol?.w} links=${linkCols.map((c) => c.w).join(',')}`
      );
      check(
        `${tag} four equal 2/12 link columns`,
        linkCols.length === 4 && Math.max(...linkCols.map((c) => c.w)) - Math.min(...linkCols.map((c) => c.w)) <= 2,
        linkCols.map((c) => c.w).join(',')
      );
      check(`${tag} footer headings 15px/18px`, m.headings.every((h) => near(parseFloat(h.size), 15, 0.1) && near(parseFloat(h.mb), 18, 0.1)), JSON.stringify(m.headings));
      check(`${tag} footer links 13.5px/#94a3b8`, m.links.every((l) => near(parseFloat(l.size), 13.5, 0.1) && l.color === 'rgb(148, 163, 184)'), JSON.stringify(m.links));
      check(`${tag} social 44px hit box, 32px painted circle`, m.socialBtn && m.socialBtn.rect.w === 44 && m.socialBtn.rect.h === 44 && near(m.socialBtn.pseudoW, 32, 0.5) && m.socialBtn.bg === 'rgba(255, 255, 255, 0.1)', JSON.stringify(m.socialBtn));
      check(`${tag} app badge reference plate`, m.badge && m.badge.bg === 'rgba(0, 0, 0, 0.25)' && m.badge.border === 'rgba(255, 255, 255, 0.2)' && m.badge.radius === '8px', JSON.stringify(m.badge));
      check(`${tag} category desktop grid visible`, m.category?.gridDisplay === 'grid' && m.category?.railDisplay === 'none', `grid=${m.category?.gridDisplay} rail=${m.category?.railDisplay}`);
      check(`${tag} 12 category tiles, 6 columns`, m.category?.gridCards === 12 && m.category?.gridCols?.split(' ').length === 6, `cards=${m.category?.gridCards} cols=${m.category?.gridCols}`);
      check(`${tag} category icon box 52x52/12px`, m.category?.icon && m.category.icon.rect.w === 52 && m.category.icon.rect.h === 52 && m.category.icon.radius === '12px', JSON.stringify(m.category?.icon));
      check(`${tag} category label 13px`, m.category?.label && near(parseFloat(m.category.label.size), 13, 0.1), JSON.stringify(m.category?.label));
      check(`${tag} category heading 19px/800`, m.category?.title && near(parseFloat(m.category.title.size), 19, 0.1) && m.category.title.weight === '800', JSON.stringify(m.category?.title));
    } else {
      check(`${tag} brand block centred`, m.brand?.justify === 'center' && (m.brand?.textAlign === 'center' || m.brand?.textAlign === 'start'), JSON.stringify(m.brand));
      check(`${tag} about text visible and centred`, m.brandText?.display !== 'none' && m.brandText?.textAlign === 'center' && m.brandText?.rect.h > 10, JSON.stringify(m.brandText));
      check(`${tag} social row centred`, m.social?.justify === 'center', JSON.stringify(m.social));
      check(`${tag} footer link columns visible`, m.footerGridCols.filter((c) => c.cls.includes('fm-footer-linkcol')).every((c) => c.w > 100), m.footerGridCols.map((c) => `${c.cls.split(' ')[0]}:${c.w}`).join(' '));
      check(`${tag} category grid hidden`, m.category?.gridDisplay === 'none', m.category?.gridDisplay);
      check(`${tag} mobile swipe rail visible`, m.category?.railDisplay === 'block', m.category?.railDisplay);
      check(`${tag} Swiper initialised`, m.category?.railInitialized === true, String(m.category?.railInitialized));
      check(`${tag} 6 two-card slides`, m.category?.slides === 6 && m.category?.slideCards.every((n) => n === 2), `slides=${m.category?.slides} cards=${m.category?.slideCards}`);
      check(`${tag} pagination dots present`, m.category?.bullets > 0, `bullets=${m.category?.bullets}`);
      // 2.2 slides per view: a painted slide must be well under half the
      // viewport. A full-width slide means the slider silently collapsed
      // into one column (the audit width rule did this before it was scoped).
      check(`${tag} rail keeps multiple columns`, m.category?.firstSlideWidth > 0 && m.category.firstSlideWidth < m.innerWidth * 0.5, `slide=${m.category?.firstSlideWidth} viewport=${m.innerWidth}`);
      // The tap-target guard gives [role=button] a 42px min-height; dots
      // must opt out or they render as vertical pills.
      check(`${tag} pagination dot 8px tall`, m.category?.bulletHeight > 0 && m.category.bulletHeight <= 10, `h=${m.category?.bulletHeight}`);
      check(`${tag} mobile category icon 48x48`, m.category?.icon && near(m.category.icon.rect.w, 48, 1) && near(m.category.icon.rect.h, 48, 1), JSON.stringify(m.category?.icon));
      check(`${tag} mobile category card 13px label`, m.category?.label && near(parseFloat(m.category.label.size), 13, 0.1), JSON.stringify(m.category?.label));
    }

    await page.close();
  }
} finally {
  await browser.close();
}

const failed = results.filter((r) => !r.ok);
console.log(`RESULT checks=${results.length} failed=${failed.length}`);
if (failed.length === 0) {
  console.log('FOOTER-CATEGORY-PASS');
} else {
  for (const f of failed) console.log(`FAILED: ${f.name} | ${f.detail}`);
  console.log('FOOTER-CATEGORY-FAIL');
  process.exitCode = 1;
}
