<?php

namespace App\Http\Middleware;

use App\Models\PhoneBlock;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stops a frontend write request when one of its phone fields is blocked.
 *
 * Why a middleware instead of a validation rule on each controller: the
 * storefront accepts a phone in registration, sign-in, checkout, the landing
 * page form and the one-page order endpoint. A rule repeated in five places is
 * a rule that will be missing from the sixth. This sits in the `web` group once
 * and checks every write request, while skipping the admin (the admin must be
 * able to manage the block list).
 *
 * It reads the request body only - no browser fingerprint, no hidden token -
 * so a guest order and a signed-in customer are treated the same way.
 */
class BlockedPhoneGuard
{
    /** Methods that can create or mutate a row. */
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->method(), self::WRITE_METHODS, true)) {
            return $next($request);
        }

        // The admin is the one doing the blocking. API/debugbar calls are not
        // storefront writes and must not be caught by a form-field rule.
        if ($request->is('admin', 'admin/*', 'api/*', '_debugbar/*')) {
            return $next($request);
        }

        $phones = $this->phones($request->all());

        if ($phones === []) {
            return $next($request);
        }

        $block = PhoneBlock::query()
            ->active()
            ->whereIn('phone', $phones)
            ->first();

        if (!$block) {
            return $next($request);
        }

        $block->forceFill([
            'hits' => (int) $block->hits + 1,
            'last_blocked_at' => now(),
        ])->save();

        Log::warning('Blocked phone attempted a storefront write', [
            'phone' => $block->phone,
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        $message = 'This phone number is blocked. Please contact support.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'blocked',
                'message' => $message,
            ], 403);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    /**
     * Collect every value that looks like a phone number, recursively.
     *
     * The fixed key list covers the forms this application ships; the value
     * scan is the safety net for a new form whose field is named differently.
     *
     * @param  array<mixed>  $input
     * @return array<int, string>
     */
    private function phones(array $input, int $depth = 0): array
    {
        if ($depth > 3) {
            return [];
        }

        $phones = [];

        foreach ($input as $value) {
            if (is_array($value)) {
                $phones = array_merge($phones, $this->phones($value, $depth + 1));
                continue;
            }

            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $digits = preg_replace('/\D+/', '', $value) ?? '';

            if ($digits === '') {
                continue;
            }

            // Match a BD mobile number in any of its stored shapes. Doing the
            // match on digits-only also catches "01712-345678".
            if (!preg_match('/(?:88)?01[3-9]\d{8}/', $digits, $matches)) {
                continue;
            }

            $normalized = PhoneBlock::normalize($matches[0]);

            if ($normalized !== null) {
                $phones[] = $normalized;
            }
        }

        return array_values(array_unique($phones));
    }
}
