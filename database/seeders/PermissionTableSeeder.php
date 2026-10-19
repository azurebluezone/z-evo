<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
           'role-list',
           'role-create',
           'role-edit',
           'role-delete',
           'permission-list',
           'permission-create',
           'permission-edit',
           'permission-delete',
           'product-list',
           'product-create',
           'product-edit',
           'product-delete',
           'category-list',
           'category-create',
           'category-edit',
           'category-delete',
           'subcategory-list',
           'subcategory-create',
           'subcategory-edit',
           'subcategory-delete',
           'childcategory-list',
           'childcategory-create',
           'childcategory-edit',
           'childcategory-delete',
           'brand-list',
           'brand-create',
           'brand-edit',
           'brand-delete',
           'banner-list',
           'banner-create',
           'banner-edit',
           'banner-delete',
           'shipping-list',
           'shipping-create',
           'shipping-edit',
           'shipping-delete',
           'setting-list',
           'setting-create',
           'setting-edit',
           'setting-delete',
           'page-list',
           'page-create',
           'page-edit',
           'page-delete',
           'contact-list',
           'contact-create',
           'contact-edit',
           'contact-delete',
           'social-list',
           'social-create',
           'social-edit',
           'social-delete'
        ];
      
        foreach ($permissions as $permission) {
             Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
