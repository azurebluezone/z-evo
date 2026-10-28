{{--
    FM:PARTIAL brands-index
    =========================================================================
    The /brands page body: one card per active brand, each linking to that
    brand's product listing (route('products.brand', $slug)).

    WHY THIS EXISTS
    -------------------------------------------------------------------------
    The header nav and the footer both link to /brands ("Brands" / "All
    Brands"), but that route used to be a redirect stub to the home page - so a
    visitor clicking Brands was silently dropped on the homepage with no brand
    directory at all. This is the directory they expected.

    DESIGN
    -------------------------------------------------------------------------
    Same shell as the /categories page: the global .fm-container gutter and the
    _section-head / _page-breadcrumb partials, with a flat responsive grid of
    brand cards. The card styling is scoped to .fm-page-brands and uses the same
    emerald tokens as fm-categories.css so the two directory pages match.

    IMAGE GUARD
    -------------------------------------------------------------------------
    A brand whose stored image path no longer exists on disk gets the bundled
    default instead of a broken <img>, and the stored value is normalised the
    same way the classic index does (a leading "public/" is stripped before
    asset()).

    Props
        brands  collection  active brands (id, name, slug, image)

    Usage
        @include('frontend.fm.sections.brands-index', ['brands' => $brands])
--}}

@php
    $fmBrands = $brands ?? collect();
@endphp

{{-- FM:SECTION brands-index ============================================= --}}
<main class="fm-page-brands" data-purpose="brands-index">
    <div class="fm-container">

        @include('frontend.fm.partials._page-breadcrumb', ['current' => 'Brands'])

        @include('frontend.fm.sections.partials._section-head', [
            'eyebrow' => '',
            'heading' => 'All brands',
        ])

        <div class="fm-brand-grid">
        @forelse($fmBrands as $fmBrand)
            @php
                $fmBrandImg = preg_replace('~^public/~', '', (string) ($fmBrand->image ?? ''));
                $fmBrandImg = ltrim($fmBrandImg, '/');
                if ($fmBrandImg === '' || ! \Illuminate\Support\Facades\File::exists(public_path($fmBrandImg))) {
                    $fmBrandImg = 'assets/images/default.png';
                }
            @endphp

            <a class="fm-brand-card" href="{{ route('products.brand', $fmBrand->slug) }}">
                <span class="fm-brand-card__thumb">
                    <img alt="{{ $fmBrand->name }}" loading="lazy"
                         src="{{ asset($fmBrandImg) }}">
                </span>
                <span class="fm-brand-card__name">{{ $fmBrand->name }}</span>
            </a>
        @empty
            <div class="fm-brand-empty">
                <p>No brands have been published yet.</p>
                <a class="fm-brand-empty__cta" href="{{ route('home') }}">Back to home</a>
            </div>
        @endforelse
        </div>{{-- /.fm-brand-grid --}}
    </div>
</main>
{{-- /FM:SECTION brands-index ============================================ --}}

@push('css')
<style>
    /* Scoped to .fm-page-brands so it cannot leak into any other page. The
       tokens mirror fm-categories.css so both directory pages look the same. */
    .fm-page-brands {
        --fmb-primary: #059669;
        --fmb-primary-subtle: #ecfdf5;
        --fmb-card-border: #f1f5f9;
        --fmb-text-dark: #1e293b;
        --fmb-text-muted: #64748b;
        --fmb-radius: 0.75rem;
    }
    .fm-page-brands .fm-brand-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(180px, 100%), 1fr));
        gap: 1rem;
        padding-bottom: 2.5rem;
    }
    .fm-page-brands .fm-brand-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        padding: 1.25rem 1rem;
        background: #fff;
        border: 1px solid var(--fmb-card-border);
        border-radius: var(--fmb-radius);
        text-decoration: none;
        color: var(--fmb-text-dark);
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .fm-page-brands .fm-brand-card:hover,
    .fm-page-brands .fm-brand-card:focus-visible {
        transform: translateY(-2px);
        border-color: var(--fmb-primary);
        box-shadow: 0 6px 18px rgba(5, 150, 105, .12);
        outline: none;
    }
    .fm-page-brands .fm-brand-card__thumb {
        display: grid;
        place-items: center;
        width: 96px;
        height: 64px;
        background: var(--fmb-primary-subtle);
        border-radius: var(--fmb-radius);
        overflow: hidden;
    }
    .fm-page-brands .fm-brand-card__thumb img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .fm-page-brands .fm-brand-card__name {
        font-weight: 600;
        font-size: 0.95rem;
        text-align: center;
    }
    .fm-page-brands .fm-brand-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem 1rem;
        color: var(--fmb-text-muted);
    }
    .fm-page-brands .fm-brand-empty__cta {
        display: inline-block;
        margin-top: 0.75rem;
        color: var(--fmb-primary);
        font-weight: 600;
        text-decoration: none;
    }
</style>
@endpush
