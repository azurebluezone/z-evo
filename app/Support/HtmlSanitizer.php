<?php

namespace App\Support;

/**
 * HtmlSanitizer
 * -------------
 * The footer about text is edited with Quill, so the saved value can contain
 * markup. The storefront prints it as HTML, which means the markup has to be
 * constrained at the boundary instead of trusted blindly.
 *
 * Policy: a short formatting allow-list for a one-paragraph shop blurb.
 * Links are allowed, but javascript:/data: hrefs and event handlers are
 * stripped. Nothing here decides what to display - it decides what is safe
 * to store.
 */
final class HtmlSanitizer
{
    /** Formatting tags a footer blurb may use. */
    private const ALLOWED_TAGS = '<b><strong><i><em><u><s><br><p><ul><ol><li><a>';

    /**
     * Clean editor HTML down to the allow-list.
     */
    public static function inline(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);

        // No inline event handlers (onclick=...), quoted or bare.
        $clean = preg_replace(
            '~\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)~i',
            '',
            $clean
        ) ?? $clean;

        // No script-bearing URL schemes; style/code blocks are not allowed
        // by strip_tags above, this is the href belt-and-braces pass.
        $clean = preg_replace(
            '~href\s*=\s*("|\')\s*(javascript|data|vbscript):[^"\']*("|\')~i',
            'href="#"',
            $clean
        ) ?? $clean;

        return trim($clean);
    }

    /** Visible character count of an HTML fragment (what the admin limits). */
    public static function visibleLength(?string $html): int
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_strlen(trim(preg_replace('~\s+~u', ' ', $text) ?? ''), 'UTF-8');
    }
}
