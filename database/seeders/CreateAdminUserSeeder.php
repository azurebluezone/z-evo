<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = Permission::pluck('id', 'id')->all();

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $supportRole = Role::firstOrCreate(['name' => 'Support']);

        $adminRole->syncPermissions($permissions);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            // Matches the actual local admin password (README + live login). The
            // old seeded default 123456 no longer works - it was changed to 105585.
            ['name' => 'Admin', 'password' => bcrypt('105585'), 'status' => 1]
        );
        $adminUser->assignRole([$adminRole->id]);

        $managerUser = User::firstOrCreate(
            ['email' => 'manager@gmail.com'],
            ['name' => 'Manager', 'password' => bcrypt('123456'), 'status' => 1]
        );
        $managerUser->assignRole([$managerRole->id]);

        $supportUser = User::firstOrCreate(
            ['email' => 'support@gmail.com'],
            ['name' => 'Support', 'password' => bcrypt('123456'), 'status' => 1]
        );
        $supportUser->assignRole([$supportRole->id]);
    }
}
