@php
    $seo = $generalsetting->seoMeta ?? null;

    /*
     * MetaText::plain() on every value a page yielded, because @section escapes
     * what it stores (ManagesLayouts::startSection calls e()) and the {{ }} below
     * escapes again. Without it a page title such as "All products & groceries"
     * is published as "All products &amp;amp; groceries", and shop text taken
     * from the database is published with its tags spelled out letter by letter.
     */
    $pageTitle = \App\Support\MetaText::plain($__env->yieldContent('title', $generalsetting->name ?? config('app.name')));
    $metaTitle = \App\Support\MetaText::plain($seo->meta_title ?? '') ?: $pageTitle;
    $pageDescription = \App\Support\MetaText::plain($__env->yieldContent('meta_description', ''));
    $pageKeywords = \App\Support\MetaText::plain($__env->yieldContent('meta_keywords', ''));
    $pageCanonical = \App\Support\MetaText::plain($__env->yieldContent('canonical', ''));

    /*
     * Description to fall back to when the page yields none and the admin has
     * not written one in SEO Meta. The modern layout passes its own generic
     * sentence; the classic layout passes nothing and keeps its historic
     * behaviour of emitting no description at all in that case.
     */
    $fallbackDescription = $fallbackDescription ?? '';

    $metaDescription = $pageDescription !== '' ? $pageDescription : ($seo->meta_description ?? $fallbackDescription);
    $metaKeywords = $pageKeywords !== '' ? $pageKeywords : \App\Support\MetaText::plain($seo->meta_keywords ?? '');
    $robots = $seo->robots ?? 'index,follow';
    $canonicalUrl = $pageCanonical !== '' ? $pageCanonical : ($seo->canonical_url ?? url()->current());

    $metaImage = $seo->meta_image ?? null;
    $ogTitle = \App\Support\MetaText::plain($seo->og_title ?? '') ?: $metaTitle;
    $ogDescription = \App\Support\MetaText::plain($seo->og_description ?? '') ?: $metaDescription;
    $ogImage = $seo->og_image ?? $metaImage;

    $twitterTitle = \App\Support\MetaText::plain($seo->twitter_title ?? '') ?: $metaTitle;
    $twitterDescription = \App\Support\MetaText::plain($seo->twitter_description ?? '') ?: $metaDescription;
    $twitterImage = $seo->twitter_image ?? $ogImage;

    $fallbackImage = $generalsetting->white_logo ?? $generalsetting->dark_logo ?? $generalsetting->favicon ?? 'assets/images/logo_icon/logo.png';

    /*
     * Media paths are stored with their "public/" prefix ("public/uploads/
     * settings/logo.png"), but asset() publishes from the document root, so the
     * prefix produced a URL one directory too deep and every og/twitter image
     * 404'd. The prefix is stripped here - once - for the admin's own uploads
     * and for the logo fallback alike.
     *
     * A social image that is not on disk is worse than none, so such a path
     * falls back to the logo, and a logo that is missing too leaves the tag out
     * entirely rather than pointing crawlers at a broken file.
     */
    $resolveImage = static function (?string $path): ?string {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $path = ltrim((string) preg_replace('~^public/~', '', $path), '/');

        return \Illuminate\Support\Facades\File::exists(public_path($path)) ? $path : null;
    };

    $finalMetaImage = $resolveImage($metaImage) ?: $resolveImage($fallbackImage);
    $finalOgImage = $resolveImage($ogImage) ?: $resolveImage($fallbackImage);
    $finalTwitterImage = $resolveImage($twitterImage) ?: $finalOgImage;
@endphp

<meta name="title" content="{{ $metaTitle }}" />
@if($metaDescription)
<meta name="description" content="{{ $metaDescription }}" />
@endif
@if($metaKeywords)
<meta name="keywords" content="{{ $metaKeywords }}" />
@endif
<meta name="robots" content="{{ $robots }}" />
<link rel="canonical" href="{{ $canonicalUrl }}" />

<meta property="og:type" content="website" />
<meta property="og:title" content="{{ $ogTitle }}" />
@if($ogDescription)
<meta property="og:description" content="{{ $ogDescription }}" />
@endif
<meta property="og:url" content="{{ url()->current() }}" />
@if($finalOgImage)
<meta property="og:image" content="{{ asset($finalOgImage) }}" />
@endif

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $twitterTitle }}" />
@if($twitterDescription)
<meta name="twitter:description" content="{{ $twitterDescription }}" />
@endif
@if($finalTwitterImage)
<meta name="twitter:image" content="{{ asset($finalTwitterImage) }}" />
@endif
