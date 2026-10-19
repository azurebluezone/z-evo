@extends('frontend.layouts.app')

@section('title', 'Category')
@section('meta_description', 'Browse category products')

@section('content')
<main>
    <div class="py-60">
        <div class="container">
            <ul class="mb-4 breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                @if(isset($breadcrumb) && count($breadcrumb) > 0)
                    @foreach($breadcrumb as $bc)
                        <li class="breadcrumb-item">
                            @if(!empty($bc['url']))
                                <a href="{{ $bc['url'] }}">{{ $bc['title'] }}</a>
                            @else
                                <span>{{ $bc['title'] }}</span>
                            @endif
                        </li>
                    @endforeach
                @else
                    <li class="breadcrumb-item"><span>Category</span></li>
                @endif
            </ul>

            <div class="product-shop">
                <aside class="category-sidebar">
                    <button type="button" class="close-sidebar text--secondary d-xl-none"><i class="las la-times"></i></button>
                    <form action="" method="get" id="filterForm" class="product-filter-form">

                        @if(isset($sidebar_categories) && count($sidebar_categories) > 0)
                            <div class="widget">
                                <h5 class="title">Categories</h5>
                                <div class="widget-body">
                                    <ul class="filter-category filter-overflow">
                                        @foreach($sidebar_categories as $c)
                                            <li>
                                                <a href="{{ $c['url'] ?? '#' }}" class="" data-slug="{{ $c['slug'] ?? '' }}">
                                                    <i class="las la-angle-right"></i> {{ $c['title'] ?? '' }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        @if(isset($brands) && count($brands) > 0)
                            <div class="widget">
                                <h5 class="title">Filter by Brand</h5>
                                <div class="widget-body filter-overflow">
                                    @foreach($brands as $brand)
                                        <div class="widget-check-group brand-filter">
                                            <input type="checkbox" class="form-check-input brand-item" value="{{ $brand['slug'] ?? '' }}" name="brand[]" id="brand-{{ $brand['id'] ?? $loop->index }}" {{ !empty($brand['checked']) ? 'checked' : '' }}>
                                            <label for="brand-{{ $brand['id'] ?? $loop->index }}">{{ $brand['name'] ?? '' }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

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

                        @if(isset($color_attributes) && count($color_attributes) > 0)
                            <div class="widget">
                                <h5 class="title">Color</h5>
                                <div class="widget-body">
                                    <ul class="list list--row flex-wrap">
                                        @foreach($color_attributes as $color)
                                            <li>
                                                <label class="select-color" for="attr-color-val-{{ $color['id'] }}" style="color:{{ $color['hex'] }}; background-color:{{ $color['hex'] }}" data-bs-title="{{ $color['title'] }}">
                                                    <input class="select-color__input attributes" type="checkbox" name="attribute_values[]" data-type="2" value="{{ $color['id'] }}" id="attr-color-val-{{ $color['id'] }}" {{ !empty($color['checked']) ? 'checked' : '' }}>
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                    </form>
                </aside>

                <div class="products-container w-100">
                    <div class="filter-category-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex gap-2 align-items-center flex-grow-1 flex-md-grow-0 justify-content-between">
                            <div class="filter-select-item d-xl-none pl-0">
                                <button class="filter-in flex-shrink-0" type="button"><i class="las la-sliders-h"></i> Filter</button>
                            </div>
                            <span class="small text-muted flex-shrink-0">
                                Total <span class="fw-semibold totalProducts">{{ isset($products) ? $products->count() : 0 }}</span> products found
                            </span>
                        </div>
                    </div>

                    <div class="row g-3" id="grid-view">
                        @if(isset($products) && count($products) > 0)
                            @foreach($products as $product)
                                <div class="col-6 grid-control col-md-4 col-lg-3">
                                    <div class="product-card" data-in-stock="{{ (isset($product) && method_exists($product, 'isInStock') && $product->isInStock()) ? 1 : 0 }}">
                                        <div class="product-thumb">
                                            <a href="{{ route('products.show', $product->slug) }}">
                                                <img src="{{ asset('assets/images/default.png') }}" class="lazyload" data-src="{{ asset($product->thumbnail_image ?? 'assets/images/default.png') }}" alt="product">
                                            </a>
                                        </div>
                                        <div class="product-content">
                                            <div class="product-before-content">
                                                <h6 class="title">
                                                    <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                                </h6>
                                                <div class="single_content__info">
                                                    <div class="price">{{ $product->price }}</div>
                                                </div>
                                            </div>
                                            <button type="button" class="addToCart add-to-cart-btn" data-id="{{ $product->id }}" data-product_type="{{ $product->product_type ?? 1 }}">
                                                <i class="las la-shopping-bag"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-12"><div class="alert alert-info mb-0">No products found.</div></div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>
@endsection
