@extends('frontend.layouts.app')

@section('title', 'Products')
@section('meta_description', 'Browse products')

@push('css')
<link rel="stylesheet" href="{{ asset('assets/templates/basic/css/products-page.css') }}">
@endpush

@section('content')
<main>
    <div class="py-60">
        <div class="container">
            <ul class="mb-4 breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><span>@yield('breadcrumb_title', 'Products')</span></li>
            </ul>

            <div class="product-shop">
                <aside class="category-sidebar">
                    <button type="button" class="close-sidebar text--secondary d-xl-none"><i class="las la-times"></i></button>
                    <form action="" method="get" id="filterForm" class="product-filter-form">
                        <div class="widget">
                            <h5 class="title">Filter by Price</h5>
                            <div class="widget-body">
                                <div class="price-range d-flex flex-wrap mb-4">
                                    <div class="widget-check-group">
                                        <input type="checkbox" class="form-check-input" id="priceCheck">
                                        <label class="form-check-label" for="priceCheck">Price Filter</label>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" step="any" class="form-control" min="0" name="min_price" value="{{ request('min_price') }}" disabled>
                                        <input type="number" step="any" class="form-control" min="0" name="max_price" value="{{ request('max_price') }}" disabled>
                                    </div>
                                </div>
                                <div id="slider-range"></div>
                            </div>
                        </div>

                        <div class="widget">
                            <h5 class="title">Ratings</h5>
                            <div class="widget-body">
                                <div class="product-filter__card">
                                    <ul class="list">
                                        @for($r = 5; $r >= 1; $r--)
                                            <li>
                                                <div class="widget-check-group">
                                                    <input class="form-check-input rating" name="rating" value="{{ $r }}" type="radio" id="rating-{{ $r }}" {{ (string)request('rating') === (string)$r ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="rating-{{ $r }}">
                                                        <span class="list list--row rating-list">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <span class="rating-list__icon {{ $i <= $r ? 'rating-list__icon-active' : 'rating-list__icon-disable' }}">
                                                                    <i class="fas fa-star"></i>
                                                                </span>
                                                            @endfor
                                                            @if($r < 5)
                                                                <span class="rating-up-text">and Up</span>
                                                            @endif
                                                        </span>
                                                    </label>
                                                </div>
                                            </li>
                                        @endfor
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </form>
                </aside>

                <div class="products-container w-100">
                    <div class="filter-category-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex gap-2 align-items-center flex-grow-1 flex-md-grow-0 justify-content-between">
                            <div class="filter-select-item d-xl-none pl-0">
                                <button class="filter-in flex-shrink-0" type="button">
                                    <i class="las la-sliders-h"></i> Filter
                                </button>
                            </div>
                            <span class="small text-muted flex-shrink-0">
                                Total <span class="fw-semibold totalProducts">{{ isset($products) ? (is_object($products) ? $products->count() : count($products)) : 0 }}</span> products found
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-3 filter-select-right flex-grow-1">
                            <div class="d-flex gap-2 justify-content-between">
                                <div class="filter-select-item d-flex gap-1 align-items-center">
                                    <p class="mb-0">Sort By :</p>
                                    <div class="select-item">
                                        <select class="form--control form--select" name="sort_by" form="filterForm">
                                            <option value="" {{ request('sort_by') == '' ? 'selected' : '' }}>Default</option>
                                            <option value="price_htl" {{ request('sort_by') == 'price_htl' ? 'selected' : '' }}>Price High to Low</option>
                                            <option value="price_lth" {{ request('sort_by') == 'price_lth' ? 'selected' : '' }}>Price Low to High</option>
                                            <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>Latest</option>
                                            <option value="oldest" {{ request('sort_by') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="filter-select-item d-flex gap-1 align-items-center">
                                    <p class="mb-0">Show :</p>
                                    <div class="select-item">
                                        <select class="form--control form--select" name="per_page" form="filterForm">
                                            @foreach([4,8,16,24,32,64,80,100] as $pp)
                                                <option value="{{ $pp }}" {{ (int)request('per_page', 24) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-select-item">
                                <ul class="view-style d-flex gap-2 align-items-center">
                                    <li><a href="javascript:void(0)" class="active view-grid-style"><i class="las la-th-large"></i></a></li>
                                    <li><a href="javascript:void(0)" class="view-list-style"><i class="las la-list-ul"></i></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="position-relative">
                        <div id="overlay" style="display:none;">
                            <div class="cv-spinner"><span class="spinner"></span></div>
                        </div>
                        <div class="overlay-2" id="overlay2" style="display:none;"></div>

                        <div class="page-main-content">
                            <div class="row g-3" id="grid-view">
                                @if(isset($products) && (is_object($products) ? $products->count() > 0 : count($products) > 0))
                                    @foreach(is_object($products) ? $products->items() : $products as $product)
                                        <div class="col-6 grid-control col-md-4 col-lg-3">
                                            <div class="product-card" data-in-stock="{{ (isset($product) && method_exists($product, 'isInStock') && $product->isInStock()) ? 1 : 0 }}">
                                                <div class="product-thumb">
                                                    <ul class="product-card-buttons">
                                                        <li class="product-wishlist-btn">
                                                            <button type="button" class="addToWishlist" data-id="{{ $product->id }}"><i class="lar la-heart"></i></button>
                                                        </li>
                                                    </ul>

                                                    <a href="{{ route('products.show', $product->slug) }}">
                                                        @php
                                                            $thumbRaw = (string) ($product->thumbnail_image ?? optional($product->image)->image ?? '');
                                                            $thumbPath = preg_replace('~^public/~', '', $thumbRaw);
                                                            $thumbPath = ltrim($thumbPath, '/');
                                                            if (!empty($thumbPath) && !str_contains($thumbPath, '/') && !str_contains($thumbPath, '\\')) {
                                                                $thumbPath = 'uploads/product/' . $thumbPath;
                                                            }
                                                            $thumbUrl = !empty($thumbPath) ? asset($thumbPath) : asset('uploads/default/placeholder.png');
                                                            $thumbOnError = "this.onerror=null;this.src='" . asset('uploads/default/no-image.png') . "';";
                                                        @endphp
                                                        <img
                                                            src="{{ asset('uploads/default/placeholder.png') }}"
                                                            class="lazyload"
                                                            data-src="{{ $thumbUrl }}"
                                                            alt="product"
                                                            width="400"
                                                            height="400"
                                                            style="width:100%;height:auto;aspect-ratio:1/1;object-fit:contain;"
                                                            onerror="{{ $thumbOnError }}"
                                                        >
                                                    </a>
                                                </div>

                                                <div class="product-content">
                                                    <div class="product-before-content">
                                                        <h6 class="title">
                                                            <a data-src="{{ asset($product->thumbnail_image ?? 'assets/images/default.png') }}" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                                        </h6>

                                                        <div class="single_content__info">
                                                            <div class="price">{{ $product->price }}</div>
                                                            <div class="ratings-area">
                                                                <span class="ratings">
                                                                    @for($i=1;$i<=5;$i++)
                                                                        <i class="lar la-star"></i>
                                                                    @endfor
                                                                </span>
                                                                <span class="rating-count">({{ $product->reviews_count ?? 0 }})</span>
                                                            </div>
                                                        </div>

                                                        @if(!empty($product->short_description))
                                                            <div class="single_content">
                                                                <p>{{ strip_tags($product->short_description) }}</p>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="button" class="addToCart add-to-cart-btn" data-id="{{ $product->id }}" data-product_type="{{ $product->product_type ?? 1 }}">
                                                        <i class="las la-shopping-bag"></i> Add to Cart
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="col-12">
                                        <div class="alert alert-info mb-0">No products found.</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(isset($products) && (is_object($products) ? $products->hasPages() : false))
                            <div class="text-center mt-4 mb-4">
                                <a href="{{ request()->fullUrlWithQuery(['per_page' => 9999]) }}" 
                                   class="btn btn-outline-primary"
                                   style="padding:8px 24px;border:1px solid #ef0000;background:#ef0000;color:#fff;border-radius:4px;font-weight:500;text-decoration:none;transition:all 0.3s ease;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="las la-eye"></i>
                                    View All Products
                                </a>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection

@push('script')
<script>
    (function () {
        var filterForm = document.getElementById('filterForm');
        if (!filterForm) return;

        var selects = filterForm.querySelectorAll('select[name="sort_by"], select[name="per_page"]');
        var externalSelects = document.querySelectorAll('select[form="filterForm"][name="sort_by"], select[form="filterForm"][name="per_page"]');
        selects = Array.prototype.slice.call(selects || []).concat(Array.prototype.slice.call(externalSelects || []));
        if (!selects || selects.length === 0) return;

        selects.forEach(function (el) {
            el.addEventListener('change', function () {
                filterForm.submit();
            });
        });
    })();
</script>
@endpush
