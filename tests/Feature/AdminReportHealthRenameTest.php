<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Report Summary -> Report Health rename, report-management-final section 8
 * item 10.
 *
 * The page is a data-quality and access audit, not a business summary. These
 * tests lock the rename:
 *   - /admin/reports/health renders with the new title and sidebar label;
 *   - /admin/reports/summary answers 301 to the new URL, not 404;
 *   - the old URL stays behind the same report-sales-view permission;
 *   - the page body no longer presents itself as a business summary.
 *
 * Marker: REPORT_HEALTH_OK
 */
class AdminReportHealthRenameTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->first();

        $this->assertNotNull($user, "No user with role {$role}, so the report cannot be authenticated.");

        return $user;
    }

    public function test_health_page_renders_under_the_new_name(): void
    {
        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.health'));

        $response->assertOk();
        $response->assertSee('Report Health', false);
        $response->assertDontSee('Report Summary', false);

        // The sidebar points at the new route, so the old path must not be
        // rendered anywhere on the page.
        $this->assertStringNotContainsString(
            'reports/summary',
            (string) $response->getContent(),
            'REPORT_HEALTH_OK: the page still links the old summary URL.'
        );
    }

    public function test_old_summary_url_redirects_permanently(): void
    {
        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.summary'));

        $response->assertStatus(301);
        $response->assertRedirect(route('admin.reports.health'));
    }

    public function test_support_is_denied_on_health_and_the_old_url(): void
    {
        $support = $this->userWithRole('Support');

        $this->actingAs($support)->get(route('admin.reports.health'))->assertForbidden();
        $this->actingAs($support)->get(route('admin.reports.summary'))->assertForbidden();
    }

    public function test_manager_keeps_access_to_health_and_the_old_url(): void
    {
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)->get(route('admin.reports.health'))->assertOk();

        $this->actingAs($manager)
            ->get(route('admin.reports.summary'))
            ->assertStatus(301)
            ->assertRedirect(route('admin.reports.health'));
    }
}
