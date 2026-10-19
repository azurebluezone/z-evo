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
            DemoStorefrontSeeder::class,
            NotificationTemplateSeeder::class,
            \Database\Seeders\DemoOrderDetailsSeeder::class,

            // Fills the storefront with banners, categories, brands,
            // products, their images and a running flash sale, and pins the
            // front-end theme to 'modern' so the redesigned home and catalog
            // pages are the ones that render. Safe to re-run - every write is
            // an upsert on a stable natural key.
            StorefrontDemoSeeder::class,
        ]);
    }
}
