<?php

namespace App\Support;

/**
 * MetaText
 * -------
 * Turns a shop value into the plain sentence a <meta> tag can carry.
 *
 * WHY THIS EXISTS
 * ---------------
 * Blade escapes a value passed to @section once (ManagesLayouts::startSection
 * calls e() on it), and layouts/partials/seo-meta.blade.php escapes it again
 * when it prints the tag. That is correct for a literal, but a description
 * that came out of the database often contains markup or entities - the
 * description field in this admin can hold "<b>sadf</b>" - and the pair of
 * escapes then publishes "&amp;lt;b&amp;gt;" to crawlers, which reads as the
 * literal text "<b>sadf</b>" rather than "sadf".
 *
 * The classic product page already guards against this with a bare
 * strip_tags(); every page that passes shop data needs the same treatment,
 * so it lives here instead of being re-typed per page.
 *
 * What it does, in order:
 *   1. strip_tags()      - no markup in a meta tag, ever
 *   2. html_entity_decode() - so "&amp;" becomes "&" and is escaped exactly
 *                             once on the way out
 *   3. whitespace collapse and trim - a stored description is often one long
 *                             run with the newlines a textarea left behind
 */
class MetaText
{
    /**
     * Plain-text form of a shop value, safe to hand to @section('meta_description').
     * Non-strings and blanks come back as an empty string.
     */
    public static function plain(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * $value reduced to at most $limit characters, cut on a word boundary so a
     * truncated description never ends mid-word. Used for titles and product
     * names that have no description of their own.
     */
    public static function limited(mixed $value, int $limit = 160): string
    {
        $text = self::plain($value);

        if ($limit < 1 || mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space !== false ? mb_substr($cut, 0, $space) : $cut, " ,.;:-");
    }
}