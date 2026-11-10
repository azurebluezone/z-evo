{{--
    FM PAGE custom page
    =========================================================================
    Renders any CreatePage row on the modern storefront.

    FrontendController::page() still returns frontend.pages.page; FrontendTheme
    maps that to this file when the modern template is active, exactly as it
    does for products, hot deals and the cookie page. Before this file existed
    the lookup fell through to the classic layout, so Terms & Conditions opened
    in the old design while every other link stayed modern.

    The page list is $cmnmenu, shared globally by AppServiceProvider from the
    same create_pages table, so this sidebar and the classic one can never
    disagree about which pages exist.
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@section('title', $page->title)
@section('meta_description', \Illuminate\Support\Str::limit(trim(strip_tags((string) $page->description)), 160))

@push('styles')
<style>
    .fm-page { background: #f8fafc; }
    .fm-page__hero { background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 55%, #ffffff 100%); border-bottom: 1px solid #e2e8f0; }
    .fm-page__eyebrow { letter-spacing: .14em; text-transform: uppercase; font-size: 12px; font-weight: 700; color: #0f766e; }
    .fm-page__title { font-size: clamp(26px, 3.6vw, 38px); line-height: 1.18; font-weight: 800; color: #0f172a; margin: 10px 0 0; }
    .fm-page__nav { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; }
    .fm-page__nav a { display: block; padding: 9px 12px; border-radius: 10px; color: #334155; font-size: 14px; font-weight: 600; text-decoration: none; }
    .fm-page__nav a:hover { background: #f0fdfa; color: #0f766e; }
    .fm-page__nav a.is-active { background: #0f766e; color: #fff; }
    .fm-page__article { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 26px; }
    .fm-page__prose { color: #334155; font-size: 15px; line-height: 1.75; }
    .fm-page__prose h1, .fm-page__prose h2, .fm-page__prose h3, .fm-page__prose h4 { color: #0f172a; font-weight: 700; margin: 1.4em 0 .6em; line-height: 1.3; }
    .fm-page__prose h1 { font-size: 26px; }
    .fm-page__prose h2 { font-size: 22px; }
    .fm-page__prose h3 { font-size: 18px; }
    .fm-page__prose p { margin: 0 0 1em; }
    .fm-page__prose ul, .fm-page__prose ol { margin: 0 0 1em 1.2em; }
    .fm-page__prose li { margin-bottom: .35em; }
    .fm-page__prose a { color: #0f766e; text-decoration: underline; }
    .fm-page__prose img { max-width: 100%; height: auto; border-radius: 12px; }
    .fm-page__prose table { width: 100%; margin-bottom: 1em; border-collapse: collapse; }
    .fm-page__prose th, .fm-page__prose td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
    .fm-page__prose th { background: #f1f5f9; }
    @media (max-width: 991.98px) {
        .fm-page__article { padding: 20px; }
    }
</style>
@endpush

@section('content')
<main class="fm-page" data-purpose="custom-page">
    <section class="fm-page__hero">
        <div class="container mx-auto px-4 py-5 py-lg-6">
            <p class="fm-page__eyebrow mb-0">Information</p>
            <h1 class="fm-page__title">{{ $page->title }}</h1>
        </div>
    </section>

    <div class="container mx-auto px-4 py-5">
        <div class="row g-4 align-items-start">
            <aside class="col-12 col-lg-3">
                <nav class="fm-page__nav" aria-label="Information pages">
                    @foreach($cmnmenu as $menuPage)
                        <a href="{{ route('page', $menuPage->slug) }}"
                           class="{{ $menuPage->slug === $page->slug ? 'is-active' : '' }}"
                           @if($menuPage->slug === $page->slug) aria-current="page" @endif>
                            {{ $menuPage->name }}
                        </a>
                    @endforeach
                    <a href="{{ route('contact') }}">Contact us</a>
                </nav>
            </aside>

            <div class="col-12 col-lg-9">
                <article class="fm-page__article">
                    <div class="fm-page__prose page-description">
                        {!! $page->description !!}
                    </div>
                </article>
            </div>
        </div>
    </div>
</main>
@endsection
