<?php

namespace App\Support;

/**
 * Short-lived proof that a tracking call came from one of our own pages.
 *
 * `visitor_id` and `session_id` are HttpOnly cookies, so the browser cannot read
 * them, and the collector has to accept a body `visitor_id` as well. That body
 * field is the one thing an attacker could use to poison another visitor's
 * timeline. This token binds the request to the cookies the server issued:
 * a forged id is rejected, while a cached legacy page (no token) still works.
 *
 * The token is NOT a cookie and deliberately carries no identifiers — only the
 * expiry and an HMAC over the ids the server can read itself. Base64 is not
 * encryption, so putting the ids in the payload would hand them to JS and undo
 * the whole point of HttpOnly.
 */
final class TrackToken
{
    /** Session window, in minutes. Matches the `session_id` cookie lifetime. */
    public const TTL_MINUTES = 30;

    /**
     * @return array{token: string, exp: int}
     */
    public static function issue(string $visitorId, string $sessionId, ?int $ttlMinutes = null): array
    {
        $exp = now()->addMinutes($ttlMinutes ?? self::TTL_MINUTES)->getTimestamp();

        $payload = self::base64UrlEncode((string) json_encode(['e' => $exp]));

        return [
            'token' => $payload . '.' . self::sign($visitorId, $sessionId, $exp),
            'exp' => $exp,
        ];
    }

    /**
     * True when the token is ours, unexpired, and belongs to these cookies.
     */
    public static function verify(?string $token, ?string $visitorId, ?string $sessionId): bool
    {
        if (!is_string($token) || $token === '' || strlen($token) > 512) {
            return false;
        }

        if (!is_string($visitorId) || $visitorId === ''
            || !is_string($sessionId) || $sessionId === '') {
            return false;
        }

        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return false;
        }

        [$payload, $signature] = $parts;

        $decoded = json_decode((string) self::base64UrlDecode($payload), true);

        if (!is_array($decoded) || !isset($decoded['e'])) {
            return false;
        }

        $exp = (int) $decoded['e'];

        if ($exp < now()->getTimestamp()) {
            return false;
        }

        return hash_equals(self::sign($visitorId, $sessionId, $exp), $signature);
    }

    private static function sign(string $visitorId, string $sessionId, int $exp): string
    {
        return self::base64UrlEncode(
            hash_hmac('sha256', $visitorId . '|' . $sessionId . '|' . $exp, self::key(), true)
        );
    }

    private static function key(): string
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $key !== '' ? $key : 'zombiecoder-track-token';
    }

    private static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $encoded): string|false
    {
        $padded = str_pad(strtr($encoded, '-_', '+/'), (int) (ceil(strlen($encoded) / 4) * 4), '=');

        return base64_decode($padded, true);
    }
}
