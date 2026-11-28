<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3 item 4 — cohort retention by first delivered order month.
 *
 * The claim that matters (docs/report-completion-plan.md item 6): a cohort
 * row's month-1 repeat rate matches a plain SQL count. The fixtures below live
 * in an isolated 2037 window (0 live rows there) and every expected number is
 * recomputed here with raw SQL over the same tables the page reads.
 *
 * The report joins two rules that are easy to get wrong, so both are pinned:
 *   - a customer belongs to the month of their FIRST delivered order, forever;
 *   - two delivered orders in one month still count that customer once.
 *
 * Writes are wrapped in a transaction, so live rows are untouched.
 *
 * Marker: COHORT_RETENTION_OK
 */
class CohortRetentionTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first();

        $this->assertNotNull($admin, 'No Admin user, so the cohort report cannot be authenticated.');

        return $admin;
    }

    private function insertCustomer(): int
    {
        return (int) DB::table('customers')->insertGetId([
            'name' => 'Cohort Test Customer '.Str::random(6),
            'slug' => 'cohort-test-customer-'.Str::random(8),
            'phone' => '019'.random_int(10000000, 99999999),
            'email' => 'cohort-test-'.Str::random(8).'@example.test',
            'balance' => 0,
            'password' => bcrypt('secret'),
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertDelivered(int $customerId, string $day): void
    {
        DB::table('orders')->insert([
            'invoice_id' => 'CO-'.strtoupper(Str::random(10)),
            'amount' => 100,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => $customerId,
            'order_status' => 'delivered',
            'delivered_at' => $day.' 12:00:00',
            'created_at' => $day.' 10:00:00',
            'updated_at' => $day.' 12:00:00',
        ]);
    }

    /**
     * A January 2037 cohort of two customers, one of whom returns in February
     * and March; plus a February cohort of one customer who returns in March.
     */
    private function seedCohorts(): void
    {
        $a = $this->insertCustomer();
        $b = $this->insertCustomer();
        $c = $this->insertCustomer();

        $this->insertDelivered($a, '2037-01-10');
        $this->insertDelivered($a, '2037-02-10');
        $this->insertDelivered($a, '2037-03-05');

        $this->insertDelivered($b, '2037-01-15');

        $this->insertDelivered($c, '2037-02-05');
        $this->insertDelivered($c, '2037-03-05');
    }

    private function firstDeliveredSql(): string
    {
        return "SELECT customer_id, MIN(delivered_at) AS first_at FROM orders "
            ."WHERE order_status IN ('delivered','4') AND customer_id IS NOT NULL "
            .'AND delivered_at IS NOT NULL GROUP BY customer_id';
    }

    private function cohortSize(string $month): int
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) AS c FROM ('.$this->firstDeliveredSql().') f WHERE DATE_FORMAT(f.first_at, ?) = ?',
            ['%Y-%m', $month]
        )->c;
    }

    private function repeatAt(string $cohortMonth, int $offset): int
    {
        return (int) DB::selectOne(
            'SELECT COUNT(DISTINCT o.customer_id) AS c '
            .'FROM orders o JOIN ('.$this->firstDeliveredSql().') f ON f.customer_id = o.customer_id '
            ."WHERE o.order_status IN ('delivered','4') AND DATE_FORMAT(f.first_at, '%Y-%m') = ? "
            .'AND DATE_FORMAT(o.delivered_at, ?) = DATE_FORMAT(DATE_ADD(f.first_at, INTERVAL ? MONTH), ?)',
            [$cohortMonth, '%Y-%m', $offset, '%Y-%m']
        )->c;
    }

    public function test_month_one_repeat_rate_matches_a_plain_sql_count(): void
    {
        $this->seedCohorts();

        $size = $this->cohortSize('2037-01');
        $monthOne = $this->repeatAt('2037-01', 1);

        // Fixture guard: the SQL itself must see the two-customer January cohort
        // and exactly one of them back in February.
        $this->assertSame(2, $size);
        $this->assertSame(1, $monthOne);

        $page = $this->actingAs($this->admin())->get(route('admin.reports.cohorts', [
            'from' => '2037-01-01',
            'to' => '2037-04-30',
        ]));

        $page->assertOk();

        $expectedAttributes = 'data-cohort-row="2037-01"'
            .' data-cohort-size="'.$size.'"'
            .' data-cohort-cell-0="'.$this->repeatAt('2037-01', 0).'"'
            .' data-cohort-rate-0="'.number_format($this->repeatAt('2037-01', 0) / $size * 100, 2, '.', '').'"'
            .' data-cohort-cell-1="'.$monthOne.'"'
            .' data-cohort-rate-1="'.number_format($monthOne / $size * 100, 2, '.', '').'"';

        $page->assertSee($expectedAttributes, false);
        $page->assertSee('data-cohort-rate-1="50.00"', false);
    }

    public function test_a_second_delivered_order_in_the_same_month_counts_the_customer_once(): void
    {
        $customer = $this->insertCustomer();
        $this->insertDelivered($customer, '2037-04-02');
        $this->insertDelivered($customer, '2037-04-20');

        $this->assertSame(1, $this->cohortSize('2037-04'));
        $this->assertSame(1, $this->repeatAt('2037-04', 0));

        $page = $this->actingAs($this->admin())->get(route('admin.reports.cohorts', [
            'from' => '2037-04-01',
            'to' => '2037-04-30',
        ]));

        $page->assertOk();
        $page->assertSee('data-cohort-row="2037-04" data-cohort-size="1"', false);
        $page->assertSee('data-cohort-cell-0="1" data-cohort-rate-0="100.00"', false);
    }

    public function test_a_window_without_first_deliveries_shows_the_empty_state(): void
    {
        $page = $this->actingAs($this->admin())->get(route('admin.reports.cohorts', [
            'from' => '1990-01-01',
            'to' => '1990-12-31',
        ]));

        $page->assertOk();
        $page->assertSee('No customer had a first delivered order in this window.', false);
        $page->assertDontSee('data-cohort-row=', false);
    }
}
