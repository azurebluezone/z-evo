{{--
    FM PAGE categories
    =========================================================================
    /categories - the department index, on the FM (modern) layout.

    It replaces frontend/pages/categories.blade.php, which extended
    frontend.layouts.app: the classic layout, and whose footer partial emitted
    a <footer> that the layout wrapped in a second <footer>, so the page ended
    up with two nested contentinfo landmarks. That page is still there and is
    untouched - it is simply no longer what this route renders. To bring the
    classic design back for this one page, change the view name below to
    'frontend.pages.categories'.

    The DATA is unchanged: the route closure still builds the same three-level
    tree and hands over the same $categories. Only the presentation moved, to
    the design of the product page (fm-products-v2.css), which is the shell
    this page is asked to match.

    The route and the controller were not touched, so the address bar, the
    links pointing here (footer, drawer, sitemap) and the SEO block all behave
    exactly as before.
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@section('title', 'Categories')
@section('meta_description', 'Browse every department, sub-department and product group in the shop')

@push('css')
    {{--
        Page specific sheet, pushed on the 'css' stack so it loads after every
        shared file listed in layouts.blade.php. It is scoped to
        .fm-page-categories, which only this page opens, so it cannot leak
        into the home page or the product listing.
    --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-categories.css') }}">
@endpush

@section('content')
    @include('frontend.fm.sections.categories-index', ['categories' => $categories ?? null])
@endsection
