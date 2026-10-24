<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Tests\TestCase;

/**
 * Admin review moderation.
 *
 * Three regressions this pins down:
 *
 *   1. Admin\ReviewController@active used to bump `products.ratting`, a column
 *      that does not exist, so approving a pending review was a 500. The
 *      storefront rating is AVG(reviews.ratting) over active rows, so there is
 *      no counter to maintain at all.
 *   2. backEnd/review/edit compared `$edit_data->status == 1`, but status is
 *      the string 'active'/'pending'. The switch rendered unchecked for an
 *      active review, so saving any edit silently sent it back to pending.
 *   3. The create form demanded a registered customer, so an admin could not
 *      record a rating for a walk-in buyer. name/email are now accepted
 *      directly; customer_id stays optional.
 *
 * Every row this test creates carries TEST_EMAIL and is removed afterwards.
 */
class AdminReviewTest extends TestCase
{
    private const TEST_EMAIL = 'qa-review-test@example.test';

    protected function tearDown(): void
    {
        Review::where('email', self::TEST_EMAIL)->delete();

        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so this test cannot authenticate.');

        return $admin;
    }

    private function product(): Product
    {
        $product = Product::query()->where('status', 1)->first();
        $this->assertNotNull($product, 'No published product in this database.');

        return $product;
    }

    private function pendingReview(): Review
    {
        return Review::create([
            'name' => 'QA Pending',
            'email' => self::TEST_EMAIL,
            'ratting' => 4,
            'review' => 'Pending row created by AdminReviewTest.',
            'product_id' => $this->product()->id,
            'customer_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_approving_a_pending_review_activates_it(): void
    {
        $review = $this->pendingReview();

        $response = $this->actingAs($this->admin())
            ->post('/admin/review/active', ['hidden_id' => $review->id]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('active', $review->fresh()->status);
    }

    public function test_edit_form_shows_an_active_review_as_active(): void
    {
        $review = $this->pendingReview();
        $review->status = 'active';
        $review->save();

        $response = $this->actingAs($this->admin())->get('/admin/review/' . $review->id . '/edit');

        $response->assertOk();
        $response->assertSee('name="status" checked', false);
    }

    public function test_index_lists_the_product_and_the_status(): void
    {
        $review = $this->pendingReview();

        $response = $this->actingAs($this->admin())->get('/admin/reviews');

        $response->assertOk();
        $response->assertSee('Product');
        $response->assertSee('Status');
        $response->assertSee($this->product()->name);
        // The product name links to the real edit screen (products.edit is
        // GET admin/products/{id}/edit); an earlier commit wrongly claimed
        // that route did not exist and printed plain text instead.
        $response->assertSee(route('products.edit', $this->product()->id), false);
        $response->assertSee('Pending');
    }

    public function test_create_form_offers_a_guest_buyer(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/review/create');

        $response->assertOk();
        $response->assertSee('Walk-in buyer (no account)');
        $response->assertSee('Buyer Name');
        $response->assertSee('Buyer Email');
    }

    public function test_admin_can_create_a_review_for_a_guest_buyer(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/review/save', [
            'product_id' => $this->product()->id,
            'name' => 'QA Guest Buyer',
            'email' => self::TEST_EMAIL,
            'ratting' => 5,
            'review' => 'Walk-in buyer rating recorded by the admin.',
            'status' => 1,
        ]);

        $response->assertRedirect('/admin/reviews');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reviews', [
            'email' => self::TEST_EMAIL,
            'name' => 'QA Guest Buyer',
            'product_id' => $this->product()->id,
            'customer_id' => null,
            'status' => 'active',
        ]);
    }
}
