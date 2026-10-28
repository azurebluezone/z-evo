{{--
    FM PAGE brands
    =========================================================================
    /brands - the brand directory, on the FM (modern) layout.

    It replaces the old route stub that redirected /brands to the home page,
    which silently dropped every visitor who clicked "Brands" in the header or
    footer. The page now lists each active brand as a card linking to its
    product listing, in the same design language as the /categories page.

    The route closure (routes/web.php, name `brands.page`) hands over the
    active brands; the address bar and the links pointing here are unchanged.
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@section('title', 'Brands')
@section('meta_description', 'Browse every brand we carry and shop its products')

@section('content')
    @include('frontend.fm.sections.brands-index', ['brands' => $brands ?? null])
@endsection
