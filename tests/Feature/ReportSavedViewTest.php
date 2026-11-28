<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2 item 4 — saved report filter sets (report_views).
 *
 * A saved view is a name plus the query string the report already reads, so
 * restoring it must land on the same URL with the same filters and therefore
 * the same totals. This suite creates its own report_views rows and removes
 * them in tearDown; it never touches another table.
 *
 * Marker: GEO_SAVED_VIEWS_OK
 */
class ReportSavedViewTest extends TestCase
{
    private array $createdIds = [];

    protected function tearDown(): void
    {
        if ($this->createdIds !== []) {
            DB::table('report_views')->whereIn('id', $this->createdIds)->delete();
        }

        parent::tearDown();
    }

    private function userWithRole(string $role): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->first();

        $this->assertNotNull($user, "No user with role {$role}, so the saved view cannot be authenticated.");

        return $user;
    }

    private function createView(User $owner, string $key, string $name, array $filters, bool $shared = false): int
    {
        $id = (int) DB::table('report_views')->insertGetId([
            'user_id' => $owner->id,
            'view_key' => $key,
            'name' => $name,
            'filters' => json_encode($filters),
            'is_shared' => $shared ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdIds[] = $id;

        return $id;
    }

    public function test_store_saves_the_filter_and_redirects_to_the_same_report_url(): void
    {
        $admin = $this->userWithRole('Admin');
        $name = 'Courier range '.Str::random(6);

        $response = $this->actingAs($admin)->post(route('admin.reports.views.store'), [
            'view_key' => 'couriers',
            'name' => $name,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_shared' => 1,
        ]);

        $response->assertRedirect(route('admin.reports.couriers', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]));

        $view = DB::table('report_views')->where('name', $name)->first();
        $this->assertNotNull($view, 'The saved view was not stored.');
        $this->createdIds[] = (int) $view->id;

        $this->assertSame('couriers', $view->view_key);
        $this->assertSame(1, (int) $view->is_shared);
        // MySQL reorders JSON object keys, so compare values, not key order.
        $this->assertEquals(
            ['start_date' => '2026-01-01', 'end_date' => '2026-12-31'],
            json_decode($view->filters, true)
        );
    }

    public function test_saved_view_link_restores_the_exact_filter_and_totals(): void
    {
        $admin = $this->userWithRole('Admin');
        $name = 'Restore '.Str::random(6);

        $this->createView($admin, 'couriers', $name, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ], true);

        $page = $this->actingAs($admin)->get(route('admin.reports.couriers'));
        $page->assertOk();
        $page->assertSee($name, false);
        $page->assertSee('start_date=2026-01-01&amp;end_date=2026-12-31', false);

        $restored = $this->actingAs($admin)->get(route('admin.reports.couriers', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]));
        $restored->assertOk();

        $national = (float) DB::table('courier_shipments')
            ->whereBetween('created_at', ['2026-01-01 00:00:00', '2026-12-31 23:59:59'])
            ->sum('shipping_cost');

        $restored->assertSee('data-geo-total="'.number_format($national, 2, '.', '').'"', false);
    }

    public function test_shared_view_is_visible_to_another_report_reader(): void
    {
        $admin = $this->userWithRole('Admin');
        $manager = $this->userWithRole('Manager');
        $name = 'Shared '.Str::random(6);

        $this->createView($admin, 'couriers', $name, [], true);

        $response = $this->actingAs($manager)->get(route('admin.reports.couriers'));
        $response->assertOk();
        $response->assertSee($name, false);
    }

    public function test_only_the_owner_can_delete_a_view(): void
    {
        $admin = $this->userWithRole('Admin');
        $manager = $this->userWithRole('Manager');
        $id = $this->createView($admin, 'couriers', 'Owner only '.Str::random(6), [], true);

        $this->actingAs($manager)
            ->post(route('admin.reports.views.destroy', $id))
            ->assertForbidden();

        $this->assertNotNull(DB::table('report_views')->where('id', $id)->first());

        $this->actingAs($admin)
            ->post(route('admin.reports.views.destroy', $id))
            ->assertRedirect();

        $this->assertNull(DB::table('report_views')->where('id', $id)->first());
    }
}
