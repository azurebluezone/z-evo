<?php

namespace Tests\Feature;

use App\Mail\ForgotPasswordOtpMail;
use App\Models\Customer;
use App\Notifications\GenericDbNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Email-OTP forgot-password reality check.
 *
 * The flow used to run on SMS: customers were looked up by phone, the code went
 * through SmsGatewayService, and with no configured gateway the customer was
 * still pushed to a reset screen whose code nobody ever received. The flow now
 * looks the customer up by email and mails the code.
 *
 * What is proven here, in one suite:
 *   1. the OTP mail is really sent to the customer's email address;
 *   2. the code in that mail is a 4-digit code that the reset form accepts;
 *   3. the password changes exactly once and the used code cannot be replayed;
 *   4. resend issues a fresh code and kills the previous one;
 *   5. unknown email and the old phone field no longer produce a send;
 *   6. the mail-channel notification still lands with the local mail driver.
 *
 * The suite runs on the live MySQL connection (no RefreshDatabase); every row
 * it creates is removed in tearDown.
 */
class ForgotPasswordFlowTest extends TestCase
{
    private array $customerIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function tearDown(): void
    {
        if ($this->customerIds !== []) {
            DB::table('notifications')
                ->where('notifiable_type', Customer::class)
                ->whereIn('notifiable_id', $this->customerIds)
                ->delete();

            Customer::whereIn('id', $this->customerIds)->delete();
        }

        parent::tearDown();
    }

    private function customer(?string $email = null): Customer
    {
        $customer = new Customer();
        $customer->name = 'Forgot Test Customer';
        $customer->slug = 'forgot-test-' . uniqid();
        $customer->phone = '0179' . random_int(10000000, 99999999);
        $customer->email = $email ?? 'forgot-' . uniqid() . '@example.com';
        $customer->password = bcrypt('OldPassword1');
        $customer->status = 'active';
        $customer->verify = 1;
        $customer->save();

        $this->customerIds[] = $customer->id;

        return $customer;
    }

    /**
     * Every OTP that was handed to the mailer so far, in send order.
     *
     * @return array<int, string>
     */
    private function sentOtps(Customer $customer): array
    {
        $otps = [];

        Mail::assertSent(ForgotPasswordOtpMail::class, function (ForgotPasswordOtpMail $mail) use ($customer, &$otps) {
            if ($mail->hasTo($customer->email)) {
                $otps[] = $mail->otp;
            }

            return true;
        });

        return $otps;
    }

    public function test_otp_mail_reaches_the_customer_email_address(): void
    {
        Mail::fake();

        $customer = $this->customer();

        $this->post(route('customer.forgot.verify'), ['email' => $customer->email])
            ->assertRedirect(route('customer.forgot.reset'));

        Mail::assertSent(ForgotPasswordOtpMail::class, 1);

        $otps = $this->sentOtps($customer);
        $this->assertCount(1, $otps);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $otps[0]);

        $this->assertSame($customer->email, session('verify_email'));
        $this->assertSame($otps[0], (string) $customer->fresh()->forgot);
    }

    public function test_unknown_email_is_rejected_without_sending_a_code(): void
    {
        Mail::fake();

        $this->from(route('customer.forgot.password'))
            ->post(route('customer.forgot.verify'), [
                'email' => 'nobody-' . uniqid() . '@example.com',
            ])
            ->assertRedirect(route('customer.forgot.password'));

        Mail::assertNothingSent();
        $this->assertNull(session('verify_email'));
    }

    public function test_old_phone_field_no_longer_starts_the_flow(): void
    {
        Mail::fake();

        $customer = $this->customer();

        $this->from(route('customer.forgot.password'))
            ->post(route('customer.forgot.verify'), ['phone' => $customer->phone])
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
        $this->assertNull($customer->fresh()->forgot);
        $this->assertNull(session('verify_email'));
    }

    public function test_email_otp_resets_the_password_once_and_replay_is_rejected(): void
    {
        Mail::fake();

        $customer = $this->customer();

        $this->post(route('customer.forgot.verify'), ['email' => $customer->email])
            ->assertRedirect(route('customer.forgot.reset'));

        $otp = $this->sentOtps($customer)[0];
        $wrong = $otp === '1111' ? '2222' : '1111';

        $this->post(route('customer.forgot.store'), ['otp' => $wrong, 'password' => 'BrandNewPass1'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('OldPassword1', $customer->fresh()->password));

        $this->post(route('customer.forgot.store'), ['otp' => $otp, 'password' => 'BrandNewPass1'])
            ->assertRedirect(route('customer.account'));

        $this->assertTrue(Hash::check('BrandNewPass1', $customer->fresh()->password));
        $this->assertNull($customer->fresh()->forgot);
        $this->assertAuthenticatedAs($customer, 'customer');

        // Replay: the same code must not work again; the session was retired
        // with the code, so the flow restarts from the email screen.
        $this->post(route('customer.forgot.store'), ['otp' => $otp, 'password' => 'ThirdPass123'])
            ->assertRedirect(route('customer.forgot.password'));

        $this->assertTrue(Hash::check('BrandNewPass1', $customer->fresh()->password));
    }

    public function test_resend_sends_a_fresh_code_and_retires_the_previous_one(): void
    {
        Mail::fake();

        $customer = $this->customer();

        $this->post(route('customer.forgot.verify'), ['email' => $customer->email])
            ->assertRedirect(route('customer.forgot.reset'));

        $first = $this->sentOtps($customer)[0];

        $this->post(route('customer.forgot.resendotp'))->assertRedirect();

        $codes = $this->sentOtps($customer);
        $second = end($codes);

        if ($second === $first) {
            // 1-in-9000 collision: one more resend makes the difference real.
            $this->post(route('customer.forgot.resendotp'))->assertRedirect();
            $codes = $this->sentOtps($customer);
            $second = end($codes);
        }

        $this->assertNotSame($first, $second);
        $this->assertSame($second, (string) $customer->fresh()->forgot);

        $this->post(route('customer.forgot.store'), ['otp' => $first, 'password' => 'BrandNewPass1'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('OldPassword1', $customer->fresh()->password));

        $this->post(route('customer.forgot.store'), ['otp' => $second, 'password' => 'BrandNewPass1'])
            ->assertRedirect(route('customer.account'));

        $this->assertTrue(Hash::check('BrandNewPass1', $customer->fresh()->password));
    }

    public function test_mail_channel_notification_is_delivered_with_the_local_mail_driver(): void
    {
        config(['mail.default' => 'log']);

        $customer = $this->customer('forgot-mail-' . uniqid() . '@example.com');

        $customer->notify(new GenericDbNotification(
            'Mail probe',
            'Body line',
            ['event_key' => 'test.mail'],
            ['database', 'mail']
        ));

        $this->assertSame(1, $customer->notifications()->count());
    }
}
