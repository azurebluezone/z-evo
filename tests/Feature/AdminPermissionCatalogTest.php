<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every permission a controller names must exist in the catalogue.
 *
 * Spatie's permission middleware answers 403 when the permission it names is
 * not held by anyone - and a permission that was never seeded is held by
 * nobody, including Admin. That is how `/admin/color/manage`,
 * `/admin/banner-category/manage` and two sibling modules became unreachable
 * while every test that only checked "the route exists" stayed green.
 *
 * The check is deliberately source-driven: it reads the same strings the
 * middleware will evaluate at runtime, so a new controller cannot introduce a
 * permission the seeders do not create.
 */
class AdminPermissionCatalogTest extends TestCase
{
    public function test_every_permission_named_by_a_controller_exists(): void
    {
        $files = array_merge(
            glob(app_path('Http/Controllers/Admin/*.php')) ?: [],
            glob(app_path('Http/Controllers/*.php')) ?: []
        );

        $referenced = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            if (preg_match_all("/permission:([A-Za-z0-9_\\-|, ]+)'/", $source, $matches)) {
                foreach ($matches[1] as $list) {
                    foreach (preg_split('/[|,]/', $list) as $name) {
                        $name = trim($name);
                        if ($name !== '') {
                            $referenced[$name] = basename($file);
                        }
                    }
                }
            }
        }

        $this->assertNotEmpty($referenced, 'No permission middleware found - the pattern or the paths changed.');

        $existing = DB::table('permissions')->pluck('name')->all();
        $missing = array_diff(array_keys($referenced), $existing);

        $this->assertSame([], array_values($missing), 'Permissions named in controllers but missing from the catalogue (403 for every role): ' . implode(', ', array_map(
            fn ($name) => $name . ' (' . $referenced[$name] . ')',
            $missing
        )));
    }

    public function test_the_admin_role_holds_every_permission(): void
    {
        $rolePermissions = DB::table('role_has_permissions')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->where('roles.name', 'Admin')
            ->count();

        $this->assertSame(
            DB::table('permissions')->count(),
            $rolePermissions,
            'The Admin role must hold the whole catalogue; a new permission needs a seeder re-run.'
        );
    }
}
