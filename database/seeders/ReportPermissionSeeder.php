<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Report permissions — one permission per report surface.
 *
 * Idempotent by design: firstOrCreate for the permission and
 * givePermissionTo() for the role. syncPermissions() is deliberately never
 * used here: it would delete every permission the role already holds
 * (Admin currently holds 72 links) and replace them with these eight.
 */
class ReportPermissionSeeder extends Seeder
{
    /**
     * permission => roles that receive it.
     *
     * report-profit-view is Admin-only on purpose: net profit and margin are
     * the most sensitive numbers in the admin.
     */
    public const PERMISSIONS = [
        'report-sales-view' => ['Admin', 'Manager'],
        'report-profit-view' => ['Admin'],
        'report-stock-view' => ['Admin', 'Manager'],
        'report-inventory-ledger-view' => ['Admin', 'Manager'],
        'report-payment-view' => ['Admin'],
        'report-courier-view' => ['Admin', 'Manager'],
        'report-returns-view' => ['Admin', 'Manager'],
        'report-coupon-view' => ['Admin', 'Manager'],
        'report-settings-manage' => ['Admin'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => $roleNames) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );

            foreach ($roleNames as $roleName) {
                $role = Role::query()->where('name', $roleName)->first();
                if ($role === null) {
                    continue;
                }
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
