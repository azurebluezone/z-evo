{{--
    FM:PARTIAL brand-tile
    =========================================================================
    One brand cell inside a brand row.

    Expects
        brand   object   required - a Brand row (slug, name, logo/image)

    Used by
        frontend/fm/sections/categories.blade.php   (brand row under the grid)
        frontend/fm/pages/home.blade.php            (own brand section)

    Links to the brand listing route, and resolves the logo through
    MediaPath so legacy "public/uploads/..." rows and remote URLs both work.
--}}

@php
    $fmBrandName = (string) ($brand->name ?? '');
    $fmBrandRaw  = $brand->logo ?? $brand->image ?? null;
    $fmBrandLink = route('products.brand', $brand->slug);
@endphp

<div class="col">
    <a class="fm-brand-tile" href="{{ $fmBrandLink }}" title="{{ $fmBrandName }}">
        <span class="fm-brand-tile__media">
            <img src="{{ \App\Support\MediaPath::url($fmBrandRaw, 'brand') }}"
                 alt="{{ $fmBrandName }}"
                 loading="lazy" decoding="async" width="140" height="66">
        </span>
        <span class="fm-brand-tile__name">{{ $fmBrandName }}</span>
    </a>
</div>
