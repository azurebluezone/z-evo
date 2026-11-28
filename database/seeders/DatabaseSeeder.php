<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            GeneralSettingSeeder::class,
            PermissionTableSeeder::class,
            CreateAdminUserSeeder::class,
            AttributeSeeder::class,
            VisitorsMasterDemoSeeder::class,
            EventLogsDemoSeeder::class,
            NotificationTemplateSeeder::class,

            // 1. Publish the committed Adora images from public/seed-assets.
            // Runs before the catalog so every image path exists on a fresh
            // machine; idempotent by SHA-256.
            SeedAssetSeeder::class,

            // 2. The canonical product catalog: 12 age categories, 30 products,
            // their gallery images, one default variant, variant_stocks and an
            // OPENING_BALANCE ledger entry per variant. Idempotent by slug/SKU.
            DevCatalogSeeder::class,

            // 3. The three fixed delivery rates. Checkout cannot work with an
            // empty shipping_charges table, and the profit report treats these
            // as policy values; the admin screens mark them read-only.
            DevShippingChargeSeeder::class,
        ]);

        // 4. Storefront decoration only (banners, services, homepage switches,
        // theme). DevCatalogSeeder is the single owner of products and
        // categories, so this pass must not seed a second catalog.
        //
        // Called directly rather than listed in call(): call() takes class
        // names, and decorationOnly() returns the configured seeder instance.
        (new StorefrontDemoSeeder())->decorationOnly()->run();

        $this->call([
            // 4b. Z EVO brand pass: publish the committed logo/favicon/banner
            // and all nine clips, then own general_settings and the hero rows.
            // It runs after the decoration seeder so it has a banner category
            // and a settings row to update.
            ZevoBrandSeeder::class,

            // 5. The canonical orders: 30 invoices, placed through
            // OrderPlacementService and moved through OrderLifecycle, so the
            // ledger and the statuses are real. Idempotent by invoice number.
            DevOrderSeeder::class,

            // 6. Returns, coupon attribution and courier shipments. Runs after
            // the orders because every fixture hangs off one of them.
            DevReportFixtureSeeder::class,

            // Landing-page templates and two demo pages. Order matters:
            // LandingPageSeeder resolves the Default template by view_name.
            TemplateSeeder::class,
            LandingPageSeeder::class,

            // One live campaign per landing design, so the public /campaign/
            // {slug} pages and the admin campaign screens have their content.
            LandingCampaignSeeder::class,

            // The CMS pages the footer and the register form link to
            // (/terms-and-conditions resolves through create_pages).
            StaticPageSeeder::class,

            // Report permissions. Runs after the roles exist, is idempotent,
            // and only adds links (never syncPermissions).
            ReportPermissionSeeder::class,
        ]);
    }
}
