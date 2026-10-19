-- =====================================================================
-- Mission Barisal / arafat — Safe Data Reset
-- Generated: 2026-10-09
-- Scope   : CATALOG (product content) + LOGS (analytics/tracking)
-- KEEP    : migrations, users, roles, permissions, general_settings,
--           districts, gateways, customers, shippings, payments
-- Backup  : work/backups/adorazst_before_20261009_060951.sql.gz
-- NOTE    : Verified 0 foreign keys in schema -> TRUNCATE is safe.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------- CATALOG: product content ----------
TRUNCATE TABLE `productimages`;
TRUNCATE TABLE `product_variants`;
TRUNCATE TABLE `variant_stocks`;
TRUNCATE TABLE `productcolors`;
TRUNCATE TABLE `productsizes`;
TRUNCATE TABLE `inventory_movements`;
TRUNCATE TABLE `inventory_reservations`;
TRUNCATE TABLE `flash_sale_products`;
TRUNCATE TABLE `coupon_products`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `coupons`;

-- ---------- CATALOG: taxonomy ----------
TRUNCATE TABLE `childcategories`;
TRUNCATE TABLE `subcategories`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `attribute_values`;
TRUNCATE TABLE `attributes`;

-- ---------- CATALOG: presentation ----------
TRUNCATE TABLE `banners`;
TRUNCATE TABLE `banner_categories`;
TRUNCATE TABLE `promo_banners`;
TRUNCATE TABLE `flash_sales`;
TRUNCATE TABLE `homepage_collections`;
TRUNCATE TABLE `brands`;
TRUNCATE TABLE `services`;

-- ---------- CATALOG: marketing ----------
TRUNCATE TABLE `campaign_reviews`;
TRUNCATE TABLE `campaigns`;
TRUNCATE TABLE `landing_pages`;
TRUNCATE TABLE `reviews`;

-- ---------- LOGS: analytics & tracking ----------
TRUNCATE TABLE `event_logs`;
TRUNCATE TABLE `visitors_master`;
TRUNCATE TABLE `courier_fraud_checks`;
TRUNCATE TABLE `ecom_pixels`;
TRUNCATE TABLE `seo_meta_tags`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `contact_messages`;
TRUNCATE TABLE `newsletter_subscribers`;
TRUNCATE TABLE `service_requests`;

SET FOREIGN_KEY_CHECKS = 1;