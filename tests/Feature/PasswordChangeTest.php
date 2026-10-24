<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Both password screens already existed in the code, but neither was covered
 * by a test. These verify the two flows end to end: the admin (web guard) and
 * the storefront customer (customer guard), including the wrong-old-password
 * branch, and that the stored hash actually changes.
 */
class PasswordChangeTest extends TestCase
{
    use DatabaseTransactions;

    private function temporaryAdmin(string $password = 'old-secret-123'): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'pw-test-' . Str::random(8) . '@example.test',
            'password' => Hash::make($password),
            'status' => 1,
        ]);
    }

    private function temporaryCustomer(string $password = 'old-secret-123'): Customer
    {
        $id = DB::table('customers')->insertGetId([
            'name' => 'Test Customer',
            'slug' => 'pw-test-' . Str::random(8),
            'phone' => '017' . random_int(10000000, 99999999),
            'email' => 'pw-customer-' . Str::random(8) . '@example.test',
            'password' => Hash::make($password),
            'verify' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Customer::findOrFail($id);
    }

    public function test_admin_change_password_page_loads(): void
    {
        $this->actingAs($this->temporaryAdmin())
            ->get('/admin/change-password')
            ->assertOk()
            ->assertSee('Change Password');
    }

    public function test_admin_can_change_password_and_wrong_old_password_is_rejected(): void
    {
        $admin = $this->temporaryAdmin('old-secret-123');

        $this->actingAs($admin)
            ->post('/admin/new-password', [
                'old_password' => 'wrong-password',
                'new_password' => 'brand-new-456',
                'confirm_password' => 'brand-new-456',
            ])
            ->assertSessionHasErrors('old_password');

        $this->assertTrue(Hash::check('old-secret-123', $admin->fresh()->password));

        $this->actingAs($admin)
            ->post('/admin/new-password', [
                'old_password' => 'old-secret-123',
                'new_password' => 'brand-new-456',
                'confirm_password' => 'brand-new-456',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(Hash::check('brand-new-456', $admin->fresh()->password));
    }

    public function test_customer_change_password_page_loads(): void
    {
        $this->actingAs($this->temporaryCustomer(), 'customer')
            ->get('/customer/change-password')
            ->assertOk()
            ->assertSee('Change Password');
    }

    public function test_customer_can_change_password_and_wrong_old_password_is_rejected(): void
    {
        $customer = $this->temporaryCustomer('old-secret-123');

        $this->actingAs($customer, 'customer')
            ->post('/customer/password-update', [
                'old_password' => 'wrong-password',
                'new_password' => 'brand-new-456',
                'confirm_password' => 'brand-new-456',
            ])
            ->assertSessionHasErrors('old_password');

        $this->assertTrue(Hash::check('old-secret-123', $customer->fresh()->password));

        $this->actingAs($customer, 'customer')
            ->post('/customer/password-update', [
                'old_password' => 'old-secret-123',
                'new_password' => 'brand-new-456',
                'confirm_password' => 'brand-new-456',
            ]);

        $this->assertTrue(Hash::check('brand-new-456', $customer->fresh()->password));
    }
}
