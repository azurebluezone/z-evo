@extends('frontend.layouts.app') 
@section('title',$childcategory->meta_title) 
@push('css')
<link rel="stylesheet" href="{{asset('frontEnd/css/jquery-ui.css')}}" />
<link href="https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
<style>
    :root {
        --zombie-primary: #555555;
        --zombie-secondary: #000000;
        --zombie-light: #ffffff;
        --zombie-dark: #333333;
        --zombie-success: #27ae60;
    }
    
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
    }
    
    .zombie-product-title {
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        font-weight: 400;
        color: var(--zombie-primary);
        margin-bottom: 0.1em;
        margin-top: 0.1em;
        box-sizing: border-box;
    }
    
    .zombie-product-price {
        color: var(--zombie-primary);
        font-weight: 400;
        font-size: 14px;
        margin: 0.5rem 0;
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
        margin-top: 1rem;
        gap: 0;
    }
    
    .zombie-btn-buy, .zombie-btn-cart {
        flex: 1;
        padding: 10px 5px;
        border: none;
        font-family: 'Anek Bangla', sans-serif;
        font-size: 14px;
        font-weight: 500;
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
        background-color: var(--zombie-secondary);
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
    
    .zombie-cart-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: var(--zombie-success);
        color: white;
        padding: 1rem 2rem;
        border-radius: 50px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        display: none;
        z-index: 1000;
        animation: zombie-fadeIn 0.5s ease;
    }
    
    @keyframes zombie-fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .zombie-cart-count {
        background-color: var(--zombie-secondary);
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        position: absolute;
        top: -8px;
        right: -8px;
    }
    
    .zombie-cart-icon {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: white;
        color: var(--zombie-primary);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        z-index: 999;
        cursor: pointer;
    }
    
    .zombiecoder-section-title {
        background-color: #2c2c2c;
        color: #f4c430;
        padding: 15px 40px;
        font-size: 1.8rem;
        font-weight: bold;
        letter-spacing: 2px;
        position: relative;
        display: inline-block;
        margin-bottom: 50px;
        clip-path: polygon(0 0, calc(100% - 30px) 0, 100% 100%, 30px 100%);
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
        
        .zombie-btn-buy, .zombie-btn-cart {
            font-size: 12px;
            padding: 8px 3px;
        }
        
        .zombie-product-card {
            margin-bottom: 30px;
        }
        
        .zombiecoder-section-title {
            font-size: 1.2rem;
            padding: 10px 25px;
            margin-bottom: 30px;
        }
    }
</style>
@endpush 
@push('seo')
<meta name="app-url" content="{{route('products',$childcategory->slug)}}" />
<meta name="robots" content="index, follow" />
<meta name="description" content="{{ $childcategory->meta_description}}" />
<meta name="keywords" content="{{ $childcategory->slug }}" />

<!-- Twitter Card data -->
<meta name="twitter:card" content="product" />
<meta name="twitter:site" content="{{$childcategory->childcategoryName}}" />
<meta name="twitter:title" content="{{$childcategory->childcategoryName}}" />
<meta name="twitter:description" content="{{ $childcategory->meta_description}}" />
<meta name="twitter:creator" content="gomobd.com" />
<meta property="og:url" content="{{route('products',$childcategory->slug)}}" />
<meta name="twitter:image" content="{{asset($childcategory->image)}}" />

<!-- Open Graph data -->
<meta property="og:title" content="{{$childcategory->childcategoryName}}" />
<meta property="og:type" content="product" />
<meta property="og:url" content="{{route('products',$childcategory->slug)}}" />
<meta property="og:image" content="{{asset($childcategory->image)}}" />
<meta property="og:description" content="{{ $childcategory->meta_description}}" />
<meta property="og:site_name" content="{{$childcategory->childcategoryName}}" />
@endpush 
@section('content')
    <!-- Shopping Cart Icon -->
    <div class="zombie-cart-icon">
        <i class="fas fa-shopping-cart"></i>
        <span class="zombie-cart-count">0</span>
    </div>

    <!-- Cart Notification -->
    <div class="zombie-cart-notification">
        <i class="fas fa-check-circle me-2"></i> <span class="zombie-notification-text">Product added to cart!</span>
    </div>

    <section class="product-section">
    <div class="container">
        <div class="sorting-section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="{{ route('home') }}">Home</a>
                        <span>/</span>
                        <strong>{{ $childcategory->childcategoryName }}</strong>
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
                            <div class="mobile-filter-toggle">
                                <i class="fa fa-list-ul"></i><span>filter</span>
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
        
        <div class="row">
            <div class="col-sm-3 filter_sidebar">
                <div class="filter_close"><i class="fa fa-long-arrow-left"></i> Filter</div>
                <form action="" class="attribute-submit">
                    <div class="sidebar_item wraper__item">
                        <div class="accordion" id="category_sidebar">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseCat" aria-expanded="true" aria-controls="collapseOne">
                                        {{ $childcategory->childcategoryName }}
                                    </button>
                                </h2>
                                <div id="collapseCat" class="accordion-collapse collapse show"
                                    data-bs-parent="#category_sidebar">
                                    <div class="accordion-body cust_according_body">
                                        <ul>
                                            @foreach ($childcategories as $key => $childcat)
                                                <li>
                                                    <a href="{{ url('products/' . $childcat->slug) }}">{{ $childcat->childcategoryName }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--sidebar item end-->
                    @if($products->count() > 0)
                    <div class="sidebar_item wraper__item">
                        <div class="accordion" id="price_sidebar">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapsePrice" aria-expanded="true" aria-controls="collapseOne">
                                        Price
                                    </button>
                                </h2>
                                <div id="collapsePrice" class="accordion-collapse collapse show"
                                    data-bs-parent="#price_sidebar">
                                    <div class="accordion-body cust_according_body">
                                        <div class="category-filter-box category__wraper" id="categoryFilterBox">
                                            <div class="category-filter-item">
                                                <div class="filter-body">
                                                    <div class="slider-box">
                                                        <div class="filter-price-inputs">
                                                            <p class="min-price">৳<input type="text"
                                                                    name="min_price" id="min_price" readonly="" />
                                                            </p>
                                                            <p class="max-price">৳<input type="text"
                                                                    name="max_price" id="max_price" readonly="" />
                                                            </p>
                                                        </div>
                                                        <div id="price-range" class="slider form-attribute"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <!--sidebar item end-->
                </form>
            </div>
            <div class="col-sm-9">
                <div class="row">
                    @foreach($products as $key=>$value)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="zombie-product-card">
                            <a href="{{ route('product', $value->slug) }}" class="zombie-product-link">
                                <div class="zombie-product-img-container">
                                    @if($value->image)
                                        <img src="{{ asset($value->image->image) }}" alt="{{$value->name}}" class="zombie-product-img">
                                    @else
                                        <div class="zombie-product-img-placeholder">
                                            {{ Str::limit($value->name, 30) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="zombie-box-text">
                                    <p class="zombie-product-title">{{Str::limit($value->name,80)}}</p>
                                    <div class="zombie-product-price">
                                        @if($value->old_price)
                                            <span class="zombie-original-price">৳{{$value->old_price}}</span>
                                        @endif
                                        <span class="zombie-discounted-price">৳{{$value->new_price}}</span>
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
                                    <input type="hidden" name="id" value="{{$value->id}}" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
                                </form>
                                <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <input type="hidden" name="id" value="{{$value->id}}" />
                                    <input type="hidden" name="qty" value="1" />
                                    <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="custom_paginate">
                    {{$products->links('pagination::bootstrap-4')}}
                   
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
                    {!!$childcategory->meta_description!!}
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
        $(".form-attribute").on('change click',function(){
            $(".attribute-submit").submit();
        })
        $(".sort").change(function() {
            $(".sort-form").submit();
        })
        $(".form-checkbox").change(function() {
            $(".subcategory-submit").submit();
        })
    </script>
    
<script type="text/javascript">
  $(document).ready(function() {
    $('#add_cart_btn_home').click(function() {
        gtag("event", "add_to_cart", {
            currency: "BDT",
            value: "1.5",
           tems: [
                    @foreach (Cart::instance('shopping')->content() as $cartInfo)
                        {
                            item_id: "{{$value->id}}",
                            item_name: "{{$value->name}}",
                            price: "{{$value->new_price}}",
                            currency: "BDT",
                            quantity: {{ $cartInfo->qty ?? 0 }}
                        },
                    @endforeach
                ]
        });
    });
});

</script>    
    
    <script>
        $(function() {
            $("#price-range").slider({
                step: 5,
                range: true,
                min: {{ $min_price }},
                max: {{ $max_price }},
                values: [
                    {{ request()->get('min_price') ? request()->get('min_price') : $min_price }},
                    {{ request()->get('max_price') ? request()->get('max_price') : $max_price }}
                ],
                slide: function(event, ui) {
                    $("#min_price").val(ui.values[0]);
                    $("#max_price").val(ui.values[1]);
                }
            });
            $("#min_price").val({{ request()->get('min_price') ? request()->get('min_price') : $min_price }});
            $("#max_price").val({{ request()->get('max_price') ? request()->get('max_price') : $max_price }});
            $("#priceRange").val($("#price-range").slider("values", 0) + " - " + $("#price-range").slider("values",
                1));

            $("#mobile-price-range").slider({
                step: 5,
                range: true,
                min: {{ $min_price }},
                max: {{ $max_price }},
                values: [
                    {{ request()->get('min_price') ? request()->get('min_price') : $min_price }},
                    {{ request()->get('max_price') ? request()->get('max_price') : $max_price }}
                ],
                slide: function(event, ui) {
                    $("#min_price").val(ui.values[0]);
                    $("#max_price").val(ui.values[1]);
                }
            });
            $("#min_price").val({{ request()->get('min_price') ? request()->get('min_price') : $min_price }});
            $("#max_price").val({{ request()->get('max_price') ? request()->get('max_price') : $max_price }});
            $("#priceRange").val($("#price-range").slider("values", 0) + " - " + $("#price-range").slider("values",
                1));

        });
    function add_to_cart(button,event) {
        event.preventDefault(); 
        var form = $(button).closest('form');
        var url = form.attr('action'); 
        var submit_button = $(button);

        submit_button.val(`Please Wait...`);

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
                    const originalHtml = submit_button.val();
                    submit_button.val('ADDED');
                    submit_button.css('background-color', '#27ae60');
                    
                    // Reset button after 2 seconds
                    setTimeout(function() {
                        submit_button.val(originalHtml);
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
                submit_button.val(`Add To Cart`);
                submit_button.prop('desabled', false);
            }
        });
    }
    
    // Cart icon click functionality
    $(document).ready(function() {
        $('.zombie-cart-icon').click(function() {
            var cartCount = $('.zombie-cart-count').text();
            alert('You have ' + cartCount + ' items in your cart!');
        });
    });
    </script>
@endpush