@extends('frontend.layouts.app')
@section('title',$subcategory->meta_title)
@push('css')
<link rel="stylesheet" href="{{asset('frontEnd/css/jquery-ui.css')}}" />
<link href="https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
<style>
    :root {
        --zombie-primary: #1ca3ac;
        --zombie-secondary: #f39c12;
        --zombie-light: #ffffff;
        --zombie-dark: #333333;
        --zombie-success: #27ae60;
    }

    /* Button Styles */
    .buy-now-btn {
        background-color: var(--zombie-secondary);
        color: white;
        border: none;
        padding: 10px 15px;
        width: 100%;
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .buy-now-btn:hover {
        background-color: #e67e22;
    }

    .add_cart_btn_home {
        background-color: var(--zombie-primary);
        color: white;
        border: none;
        padding: 10px 15px;
        width: 100%;
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .add_cart_btn_home:hover {
        background-color: #178f97;
    }

    /* Sidebar Styles */
    .filter_sidebar {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .accordion-button {
        background-color: var(--zombie-primary) !important;
        color: white !important;
        border: none;
        border-radius: 4px !important;
        font-family: 'Anek Bangla', sans-serif;
    }

    .accordion-button:not(.collapsed) {
        background-color: var(--zombie-primary) !important;
        color: white !important;
    }

    .accordion-button::after {
        filter: brightness(0) invert(1);
    }

    .cust_according_body {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 0 0 4px 4px;
    }

    #price-range .ui-slider-range {
        background: var(--zombie-primary);
    }

    #price-range .ui-slider-handle {
        border-color: var(--zombie-primary);
    }

    /* Zombie Product Card Styles */
    .zombie-product-card {
        border: none;
        overflow: hidden;
        transition: opacity 0.3s, transform 0.3s, background-color 0.3s;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: 100%;
        position: relative;
        margin-bottom: 0;
        display: flex;
        flex-direction: column;
    }

    .zombie-product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .zombie-product-img-container {
        height: 300px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        position: relative;
    }

    .zombie-product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .zombie-product-img-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        background-color: #f8f9fa;
        color: #999;
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        flex-direction: column;
    }

    .zombie-product-title {
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        font-weight: 400;
        color: var(--zombie-primary);
        margin-bottom: 0.5em;
        margin-top: 0.5em;
        text-align: center;
        box-sizing: border-box;
    }

    .zombie-product-price {
        color: var(--zombie-primary);
        font-weight: 400;
        font-size: 14px;
        margin: 0.8rem 0;
        text-align: center;
    }

    .zombie-original-price {
        text-decoration: line-through;
        color: #999;
        margin-right: 10px;
    }

    .zombie-discounted-price {
        color: var(--zombie-primary);
        font-weight: 600;
    }

    .zombie-btn-group {
        display: flex;
        margin-top: 1.2rem;
        gap: 0;
        justify-content: center;
    }

    .zombie-btn-buy,
    .zombie-btn-cart {
        flex: 1;
        padding: 12px 8px;
        border: none;
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        font-weight: 500;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .zombie-btn-buy {
        background-color: #f39c12;
        color: white;
    }

    .zombie-btn-buy:hover {
        background-color: #e67e22;
        color: white;
    }

    .zombie-btn-cart {
        background-color: #000;
        color: white;
    }

    .zombie-btn-cart:hover {
        background-color: #333;
        color: white;
    }

    .zombie-product-link {
        text-decoration: none;
        color: inherit;
        display: block;
        flex: 1;
    }
    
    .zombie-product-link:hover {
        text-decoration: none;
        color: inherit;
    }

    /* Row spacing between product grids */
    .col-6.col-md-4.col-lg-3 {
        margin-top: 13px;
    }

    .zombie-box-text {
        transition: opacity 0.3s, transform 0.3s, background-color 0.3s;
        font-size: 0.9em;
        padding-top: 0.7em;
        position: relative;
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }

    /* Responsive adjustments */
    @media (max-width: 549px) {
        .zombie-box-text {
            font-size: 85%;
            padding-left: 10px;
            padding-right: 10px;
        }

        .zombie-product-img-container {
            height: 250px;
        }

        .zombie-btn-buy,
        .zombie-btn-cart {
            font-size: 12px;
            padding: 8px 3px;
        }

        .zombie-product-card {
            /* margin-bottom: 30px; */
        }
    }
</style>
@endpush
@push('seo')
<meta name="app-url" content="{{route('subcategory',$subcategory->slug)}}" />
<meta name="robots" content="index, follow" />
<meta name="description" content="{{ $subcategory->meta_description}}" />
<meta name="keywords" content="{{ $subcategory->slug }}" />

<!-- Twitter Card data -->
<meta name="twitter:card" content="product" />
<meta name="twitter:site" content="{{$subcategory->subcategoryName}}" />
<meta name="twitter:title" content="{{$subcategory->subcategoryName}}" />
<meta name="twitter:description" content="{{ $subcategory->meta_description}}" />
<meta name="twitter:creator" content="gomobd.com" />
<meta property="og:url" content="{{route('subcategory',$subcategory->slug)}}" />
<meta name="twitter:image" content="{{asset($subcategory->image)}}" />

<!-- Open Graph data -->
<meta property="og:title" content="{{$subcategory->subcategoryName}}" />
<meta property="og:type" content="product" />
<meta property="og:url" content="{{route('subcategory',$subcategory->slug)}}" />
<meta property="og:image" content="{{asset($subcategory->image)}}" />
<meta property="og:description" content="{{ $subcategory->meta_description}}" />
<meta property="og:site_name" content="{{$subcategory->subcategoryName}}" />
@endpush
@section('content')
<!-- Shopping Cart Icon -->

<!-- Product Listing Section -->
<section class="product-section">
    <div class="container">
        <div class="sorting-section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="{{ route('home') }}">Home</a>
                        <span>/</span>
                        <strong>{{ $subcategory->subcategoryName }}</strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="showing-data">
                                <span>Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} Results</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="filter_sort">
                                <div class="filter_btn">
                                    <i class="fa fa-list-ul"></i>
                                </div>
                                <div class="page-sort">
                                    <form action="" class="sort-form">
                                        <select name="sort" class="form-control form-select sort">
                                            <option value="1" @if(request()->get('sort')==1)selected @endif>Product: Latest</option>
                                            <option value="2" @if(request()->get('sort')==2)selected @endif>Product: Oldest</option>
                                            <option value="3" @if(request()->get('sort')==3)selected @endif>Price: High To Low</option>
                                            <option value="4" @if(request()->get('sort')==4)selected @endif>Price: Low To High</option>
                                            <option value="5" @if(request()->get('sort')==5)selected @endif>Name: A-Z</option>
                                            <option value="6" @if(request()->get('sort')==6)selected @endif>Name: Z-A</option>
                                        </select>
                                        <input type="hidden" name="min_price" value="{{request()->get('min_price')}}" />
                                        <input type="hidden" name="max_price" value="{{request()->get('max_price')}}" />
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    @foreach ($products as $key => $value)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="zombie-product-card">
                            <a href="{{ route('product', $value->slug) }}" class="zombie-product-link">
                                <div class="zombie-product-img-container">
                                    @if($value->image && $value->image->image)
                                    <img src="{{ asset($value->image->image) }}" alt="{{ $value->name }}" class="zombie-product-img">
                                    @else
                                    <div class="zombie-product-img-placeholder">
                                        {{ Str::limit($value->name, 30) }}
                                    </div>
                                    @endif
                                </div>
                                <div class="zombie-box-text">
                                    <p class="zombie-product-title">{{ Str::limit($value->name, 80) }}</p>
                                    <div class="zombie-product-price">
                                        @if ($value->old_price)
                                        <span class="zombie-original-price">৳ {{ $value->old_price }}</span>
                                        @endif
                                        <span class="zombie-discounted-price">৳ {{ $value->new_price }}</span>
                                    </div>
                                </div>
                            </a>
                            <div class="zombie-btn-group">
                                @if (!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                                <a href="{{ route('product', $value->slug) }}" class="zombie-btn-buy">BUY NOW</a>
                                <a href="{{ route('product', $value->slug) }}" class="zombie-btn-cart">ADD TO CART</a>
                                @else
                                <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $value->id }}" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                </form>

                                <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $value->id }}" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="custom_paginate">
                            {{ $products->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="meta_des">
                    {!!$subcategory->meta_description!!}
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
@push('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
<script>
    $("#price-range").click(function() {
        $(".price-submit").submit();
    })
    $(".form-attribute").on('change click', function() {
        $(".attribute-submit").submit();
    })
    $(".sort").change(function() {
        $(".sort-form").submit();
    })
    $(".form-checkbox").change(function() {
        $(".subcategory-submit").submit();
    })
</script>
<script>
    $(function() {
        $("#price-range").slider({
            step: 5,
            range: true,
            min: {
                {
                    $min_price
                }
            },
            max: {
                {
                    $max_price
                }
            },
            values: [{
                    {
                        request() - > get('min_price') ? request() - > get('min_price') : $min_price
                    }
                },
                {
                    {
                        request() - > get('max_price') ? request() - > get('max_price') : $max_price
                    }
                }
            ],
            slide: function(event, ui) {
                $("#min_price").val(ui.values[0]);
                $("#max_price").val(ui.values[1]);
            }
        });
        $("#min_price").val({
            {
                request() - > get('min_price') ? request() - > get('min_price') : $min_price
            }
        });
        $("#max_price").val({
            {
                request() - > get('max_price') ? request() - > get('max_price') : $max_price
            }
        });
        $("#priceRange").val($("#price-range").slider("values", 0) + " - " + $("#price-range").slider("values",
            1));

        $("#mobile-price-range").slider({
            step: 5,
            range: true,
            min: {
                {
                    $min_price
                }
            },
            max: {
                {
                    $max_price
                }
            },
            values: [{
                    {
                        request() - > get('min_price') ? request() - > get('min_price') : $min_price
                    }
                },
                {
                    {
                        request() - > get('max_price') ? request() - > get('max_price') : $max_price
                    }
                }
            ],
            slide: function(event, ui) {
                $("#min_price").val(ui.values[0]);
                $("#max_price").val(ui.values[1]);
            }
        });
        $("#min_price").val({
            {
                request() - > get('min_price') ? request() - > get('min_price') : $min_price
            }
        });
        $("#max_price").val({
            {
                request() - > get('max_price') ? request() - > get('max_price') : $max_price
            }
        });
        $("#priceRange").val($("#price-range").slider("values", 0) + " - " + $("#price-range").slider("values",
            1));

    });

    function add_to_cart(button, event) {
        event.preventDefault();
        var form = $(button).closest('form');
        var url = form.attr('action');
        var submit_button = $(button);

        submit_button.prop('disabled', true);

        $.ajax({
            url: url,
            method: form.attr('method'),
            data: form.serialize(),
            success: function(response) {
                if (response.success == true) {
                    toastr.success(response.message);
                    var cart_count = response.cart_count;
                    $('#cart_count').text(cart_count);

                    // Update zombie cart count
                    $('.zombie-cart-count').text(cart_count);

                    // Show notification
                    $('.zombie-notification-text').text("Product added to cart!");
                    $('.zombie-cart-notification').fadeIn();

                    // Hide notification after 3 seconds
                    setTimeout(function() {
                        $('.zombie-cart-notification').fadeOut();
                    }, 3000);

                    // Button animation
                    submit_button.html('ADDED');
                    submit_button.css('background-color', '#27ae60');

                    // Reset button after 2 seconds
                    setTimeout(function() {
                        submit_button.html('ADD TO CART');
                        submit_button.css('background-color', '#000000');
                    }, 2000);
                }
                if (response.success == false) {
                    toastr.error(response.message);
                }
            },
            error: function(response) {
                toastr.error('Something went wrong!');
            },
            complete: function() {
                submit_button.prop('disabled', false);
            }
        });
    }
</script>

<script>
    // $(".sort").change(function(){
    //   $('#loading').show();
    //   $(".sort-form").submit();
    // })
</script>
@endpush