<?php

namespace Tests\Feature;

use App\Models\Courierapi;
use App\Models\User;
use Tests\TestCase;

/**
 * The webhook token used to be printed into a hidden input so the Copy button
 * could read it. A hidden input is still part of the HTML: View Source, DevTools
 * and any page dump could read the secret. The settings page now prints only the
 * mask and asks the server for the value when Copy is pressed.
 */
class WebhookTokenMaskTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the settings page cannot be authenticated.');

        return $admin;
    }

    /**
     * Put a known token on the pathao row for the length of one test, then
     * restore the stored value so the live account is never altered.
     */
    private function withKnownToken(callable $assertions): void
    {
        $courier = Courierapi::query()->where('type', 'pathao')->firstOrFail();
        $original = $courier->webhook_token;

        $courier->webhook_token = 'whsec_test_' . bin2hex(random_bytes(12));
        $courier->save();

        try {
            $assertions((string) $courier->webhook_token);
        } finally {
            $courier->webhook_token = $original;
            $courier->save();
        }
    }

    public function test_settings_page_never_prints_the_stored_webhook_token(): void
    {
        config(['debugbar.enabled' => false]);

        $this->withKnownToken(function (string $token): void {
            $response = $this->actingAs($this->admin())->get(route('admin.courier.settings'));

            $response->assertOk();
            $response->assertDontSee($token, false);
            $response->assertSee('••••••••••••', false);
            $response->assertSee(
                'data-copy-url="' . route('admin.courier.webhook-token', 'pathao') . '"',
                false
            );

            $html = (string) $response->getContent();

            $this->assertMatchesRegularExpression(
                '/Webhook token<\/label>.*?Saved/s',
                $html,
                'A stored webhook token must be labelled Saved.'
            );
        });
    }

    public function test_webhook_token_endpoint_serves_the_token_to_an_admin_only(): void
    {
        $this->withKnownToken(function (string $token): void {
            // A guest must be refused. actingAs() would leak into later calls in
            // the same test, so the unauthenticated check runs first.
            $guest = $this->postJson(route('admin.courier.webhook-token', 'pathao'));

            $this->assertContains(
                $guest->getStatusCode(),
                [302, 401, 403],
                'A guest must not be able to read the webhook token.'
            );
            $this->assertStringNotContainsString($token, (string) $guest->getContent());

            // The admin gets the stored value, which the Copy button puts on the
            // clipboard without it ever entering the page HTML.
            $admin = $this->actingAs($this->admin())
                ->postJson(route('admin.courier.webhook-token', 'pathao'));

            $admin->assertOk();
            $admin->assertJson(['token' => $token]);
        });
    }
}
