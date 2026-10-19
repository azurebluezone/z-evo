@extends('frontend.layouts.app')

@section('title', 'Subcategory')
@section('meta_description', 'Browse subcategory products')

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
                    <li class="breadcrumb-item"><span>Subcategory</span></li>
                @endif
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
                                        @for($r=5;$r>=1;$r--)
                                            <li>
                                                <div class="widget-check-group">
                                                    <input class="form-check-input rating" name="rating" value="{{ $r }}" type="radio" id="rating-{{ $r }}" {{ (string)request('rating') === (string)$r ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="rating-{{ $r }}">
                                                        <span class="list list--row rating-list">
                                                            @for($i=1;$i<=5;$i++)
                                                                <span class="rating-list__icon {{ $i <= $r ? 'rating-list__icon-active' : 'rating-list__icon-disable' }}"><i class="fas fa-star"></i></span>
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
                                                <h6 class="title"><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h6>
                                                <div class="single_content__info"><div class="price">{{ $product->price }}</div></div>
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
