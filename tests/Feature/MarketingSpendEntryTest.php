<?php

namespace Tests\Feature;

use App\Models\MarketingSpend;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3 item 1 — marketing spend entry, docs/report-completion-plan.md.
 *
 * The claim under test is not "a form renders". It is that a saved row is the
 * only source of the spend total: the page's number is recomputed here with
 * plain SQL over marketing_spends, and a row outside the filter range must not
 * leak into it. That is what makes the later ROAS figure trustworthy.
 *
 * Writes are wrapped in a transaction, so the live development rows are never
 * touched.
 *
 * Marker: MARKETING_SPEND_OK
 */
class MarketingSpendEntryTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first();

        $this->assertNotNull($admin, 'No Admin user, so the marketing spend screen cannot be authenticated.');

        return $admin;
    }

    private function totalFor(string $from, string $to): string
    {
        $sum = (float) DB::table('marketing_spends')
            ->whereDate('spent_at', '>=', $from)
            ->whereDate('spent_at', '<=', $to)
            ->sum('spent');

        return 'data-marketing-total="'.number_format($sum, 2, '.', '').'"';
    }

    public function test_saved_spend_appears_with_a_total_from_independent_sql(): void
    {
        $admin = $this->admin();
        $campaign = 'TEST CAMPAIGN '.uniqid();

        $this->actingAs($admin)
            ->post(route('admin.reports.marketing-spends.store'), [
                'channel' => 'facebook',
                'campaign_name' => $campaign,
                'spent' => '1234.56',
                'impressions' => 10000,
                'clicks' => 250,
                'spent_at' => '2099-12-31',
                'source_reference' => 'TEST-INVOICE-1',
                'notes' => 'TEST MARKETING SPEND',
            ])
            ->assertRedirect();

        $row = MarketingSpend::query()->where('campaign_name', $campaign)->first();
        $this->assertNotNull($row, 'The marketing spend row was not stored.');
        $this->assertSame('facebook', $row->channel);
        $this->assertSame('1234.56', (string) $row->spent);
        $this->assertSame(10000, (int) $row->impressions);
        $this->assertSame(250, (int) $row->clicks);

        $page = $this->actingAs($admin)->get(route('admin.reports.marketing-spends.index', [
            'start_date' => '2099-12-31',
            'end_date' => '2099-12-31',
        ]));

        $page->assertOk();
        $page->assertSee($campaign, false);
        $page->assertSee($this->totalFor('2099-12-31', '2099-12-31'), false);
    }

    public function test_date_filter_excludes_spends_outside_the_range(): void
    {
        $admin = $this->admin();
        $inside = 'INSIDE '.uniqid();
        $outside = 'OUTSIDE '.uniqid();

        foreach ([['2099-12-31', '40.00', $inside], ['2099-12-30', '900.00', $outside]] as [$day, $amount, $campaign]) {
            $this->actingAs($admin)->post(route('admin.reports.marketing-spends.store'), [
                'channel' => 'google',
                'campaign_name' => $campaign,
                'spent' => $amount,
                'spent_at' => $day,
            ])->assertRedirect();
        }

        $page = $this->actingAs($admin)->get(route('admin.reports.marketing-spends.index', [
            'start_date' => '2099-12-31',
            'end_date' => '2099-12-31',
        ]));

        $page->assertOk();
        $page->assertSee($inside, false);
        $page->assertDontSee($outside, false);
        $page->assertSee($this->totalFor('2099-12-31', '2099-12-31'), false);
    }

    public function test_unknown_channel_is_rejected(): void
    {
        $admin = $this->admin();
        $campaign = 'BAD CHANNEL '.uniqid();

        $this->actingAs($admin)
            ->post(route('admin.reports.marketing-spends.store'), [
                'channel' => 'telepathy',
                'campaign_name' => $campaign,
                'spent' => '10.00',
                'spent_at' => '2099-12-31',
            ])
            ->assertSessionHasErrors('channel');

        $this->assertSame(0, MarketingSpend::query()->where('campaign_name', $campaign)->count());
    }

    public function test_delete_removes_the_spend(): void
    {
        $admin = $this->admin();
        $campaign = 'DELETE ME '.uniqid();

        $this->actingAs($admin)->post(route('admin.reports.marketing-spends.store'), [
            'channel' => 'tiktok',
            'campaign_name' => $campaign,
            'spent' => '75.50',
            'spent_at' => '2099-12-31',
        ])->assertRedirect();

        $row = MarketingSpend::query()->where('campaign_name', $campaign)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.reports.marketing-spends.destroy'), ['spend_id' => $row->id])
            ->assertRedirect(route('admin.reports.marketing-spends.index'));

        $this->assertNull(MarketingSpend::query()->find($row->id));
    }
}
