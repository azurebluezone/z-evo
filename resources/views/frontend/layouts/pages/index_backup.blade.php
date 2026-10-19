@extends('frontend.layouts.master') @section('title', 'Adora_Focus on quality') @push('seo')
<meta name="app-url" content="" />
<meta name="robots" content="index, follow" />
<meta name="description" content="" />
<meta name="keywords" content="" />

<!-- Open Graph data -->
<meta property="og:title" content="" />
<meta property="og:type" content="website" />
<meta property="og:url" content="" />
<meta property="og:image" content="{{ asset($generalsetting->white_logo) }}" />
<meta property="og:description" content="" />
@endpush @push('css')
<link rel="stylesheet" href="{{ asset('frontEnd/css/owl.carousel.min.css') }}" />
<link rel="stylesheet" href="{{ asset('frontEnd/css/owl.theme.default.min.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" rel="stylesheet" />
@endpush @section('content')


<!-- ===== HERO SECTION =====  -->
<section class="sv-hero">
    <div class="container-fluid p-0">
        <div class="row g-0 align-items-center">
            <div class="col-12">
                <div class="sv-hero-content">
                    @if($sliders->count() > 0)
                        @foreach($sliders as $key => $slider)
                            <div class="sv-hero-slide {{ $key === 0 ? 'active' : '' }}" style="background-image: url('{{ asset($slider->image) }}');">
                                <div class="container">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="sv-hero-text-content">
                                                <h1 class="sv-hero-title">{{ $slider->title ?? 'INDULGE YOURSELF IN THE WORLD OF LUXURIOUS ABAYAS' }}</h1>
                                                <a href="{{ $slider->link ?? '#' }}" class="sv-hero-cta">SHOP NOW</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="sv-hero-slide active" style="background-image: url('{{ asset('uploads/default/hero-image.jpg') }}');">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="sv-hero-text-content">
                                            <div class="sv-hero-brand">SUNNAH HEAVEN</div>
                                            <a href="#" class="sv-hero-cta">SHOP NOW</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>





@if(isset($generalsetting) && $generalsetting->home_page_top_categories_section == 1)
<section class="homeproduct" style="padding:50px 0; background:#f8f9fa;">
    <div class="container" style="max-width:1200px; margin:0 auto;">
        <div class="row" style="display:flex; flex-wrap:wrap; justify-content:center;">

            <!-- Section Header -->
            <div class="col-sm-12" style="width:100%; text-align:center; margin-bottom:30px;">
                <div class="sec_title" style="position:relative; display:inline-block;">
                    <h3 class="section-title-header" style="font-size:2rem; font-weight:700; color:#222; margin:0;">
                        <div class="timer_inner" style="display:inline-block; position:relative;">
                            <span class="section-title-name" style="background:linear-gradient(90deg,#ff7e5f,#feb47b); -webkit-background-clip:text; color:transparent; padding:0 10px;">
                                Top Categories
                            </span>
                        </div>
                    </h3>
                </div>
            </div>

            <!-- Category Items -->
            <div class="col-sm-12" style="width:100%;">
                <div class="topcategory" style="display:flex; flex-wrap:wrap; justify-content:center; gap:20px;">
                    @foreach ($menucategories as $key => $value)
                        <div class="cat_item" style="flex:1 1 calc(20% - 20px); max-width:200px; text-align:center; background:#fff; border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,0.1); transition:all 0.3s ease; cursor:pointer;">
                            <div class="cat_img" style="padding:15px;">
                                <a href="{{ route('category', $value->slug) }}">
                                    <img src="{{ asset($value->image) }}" alt="" style="width:100%; height:150px; object-fit:cover; border-radius:10px;">
                                </a>
                            </div>
                            <div class="cat_name" style="padding:10px 0;">
                                <a href="{{ route('category', $value->slug) }}" style="text-decoration:none; font-weight:600; color:#333; display:block; transition:color 0.3s;">
                                    {{ $value->name }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>
@endif

<!-- Inline Hover & Responsive -->
<style>
/* Hover effect */
.cat_item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}
.cat_item a:hover {
    color: #ff7e5f;
}

/* Responsive */
@media (max-width: 992px) {
    .cat_item {
        flex: 1 1 calc(33.333% - 20px);
    }
}
@media (max-width: 768px) {
    .cat_item {
        flex: 1 1 calc(50% - 20px);
    }
}
@media (max-width: 480px) {
    .cat_item {
        flex: 1 1 100%;
    }
}
</style>


@if(isset($generalsetting) && $generalsetting->home_page_hot_deal_section == 1)
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="col-12 text-center">
                    <h2 class="sv-section-title">Hot Deal</h2>
                </div>
                  
            </div>
            <div class="col-sm-12">
                <div class="product_slider owl-carousel">
                    @foreach ($hotdeal_top as $key => $value)
                        <div class="sv-boss-product-card">
                            <div class="product_item_inner">
                                @if($value->old_price)
                                <div class="sale-badge">
                                    <div class="sale-badge-inner">
                                        <div class="sale-badge-box">
                                            <span class="sale-badge-text">
                                                <p>@php $discount=(((($value->old_price)-($value->new_price))*100) / ($value->old_price)) @endphp {{ number_format($discount, 0) }}%</p>
                                                ছাড়
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="pro_img">
                                    <a href="{{ route('product', $value->slug) }}">
                                        <img src="{{ asset($value->image ? $value->image->image : '') }}"
                                            alt="{{ $value->name }}" />
                                    </a>
                                </div>
                                <div class="pro_des">
                                    <div class="pro_name">
                                        <a
                                            href="{{ route('product', $value->slug) }}">{{ Str::limit($value->name, 80) }}</a>
                                    </div>
                                    <div class="pro_price">
                                        <p>
                                            @if ($value->old_price)
                                             <del>৳ {{ $value->old_price }}</del>
                                            @endif

                                            ৳ {{ $value->new_price }} 
                                           
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if (!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                            <div class='d-flex'>
                                 <div class="pro_btn col-lg-6 col-md-6 col-sm-6 col-6" style='padding-right:0;'>
                                   
                                    <div class="cart_btn order_button">
                                        <a href="{{ route('cart.store.buy') }}"
                                            class="addcartbutton">Buy Now </a>
                                    </div>
                                </div>
                                
                                 <div class="pro_btn col-lg-6 col-md-6 col-sm-6 col-6" style='padding-left:0;'>
                                   <form action="{{ route('cart.store') }}" method="POST" name="formName" class='add_cart_btn_home_main'>
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}" />
                                        <input type="hidden" name="qty" value="1" />
                                     <input type="submit" class="add_cart_btn_home" onclick="add_to_cart(this,event)" name="add_cart" value="Add To Cart" />
                                    </form>

                                </div>
                            </div>
                               
                            @else
                            <div class='d-flex'>
                                 <div class="pro_btn col-lg-6 col-md-6 col-sm-6  col-6" style='padding-right:0;'>
                                    
                                    <form action="{{ route('cart.store.buy') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}" />
                                        <input type="hidden" name="qty" value="1" />
                                        <button type="submit">Buy Now</button>
                                    </form>
                                </div>
                                  <div class="pro_btn col-lg-6 col-md-6 col-sm-6 col-6" style='padding-left:0;'>
                                   <form action="{{ route('cart.store') }}" method="POST" name="formName" class='add_cart_btn_home_main'>
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}" />
                                        <input type="hidden" name="qty" value="1" />
                                     <input type="submit" class="add_cart_btn_home" onclick="add_to_cart(this,event)" name="add_cart" value="Add To Cart" />
                                    </form>

                                </div>
                            </div>
                               
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
          <!--<div class="col-sm-12">-->
          <!--   <a href="{{ route('hotdeals') }}" class="view_more_btn" style="float:left">View More</a> -->
          <!-- </div>-->
        </div>
    </div>
</section>
@endif

<!-- Individual Category Sections -->
@foreach ($homeproducts as $homecat)
    @if(isset($homecat->home_page) && $homecat->home_page == 1)
    <section class="homeproduct">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="col-12 text-center">
                        <h2 class="sv-section-title">{{ $homecat->name }}</h2>
                    </div>
                    <div class="col-12 text-center">
                        <!-- <a href="{{ route('category', $homecat->slug) }}" class="view_more_btn">View More</a> -->
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="product_sliders">
                        @foreach ($homecat->products as $key => $value)
                           <div class="sv-boss-product-card">
                                <div class="sv-boss-product-image-container">
                                    <img src="{{ asset($value->image ? $value->image->image : '') }}" 
                                         alt="{{ $value->name }}" 
                                         class="sv-boss-product-image primary">
                                    @if($value->images && $value->images->count() > 1)
                                        <img src="{{ asset($value->images[1]->image) }}" 
                                             alt="{{ $value->name }} Alternate" 
                                             class="sv-boss-product-image secondary">
                                    @else
                                        <img src="{{ asset($value->image ? $value->image->image : '') }}" 
                                             alt="{{ $value->name }} Alternate" 
                                             class="sv-boss-product-image secondary">
                                    @endif
                                    
                                    <div class="sv-boss-product-actions">
                                        <a href="#" class="sv-boss-action-btn">
                                            <i class="far fa-heart"></i>
                                        </a>
                                        <a href="#" class="sv-boss-action-btn">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="sv-boss-product-info">
                                    <h3 class="sv-boss-product-title">
                                        <a href="{{ route('product', $value->slug) }}" style="color: inherit; text-decoration: none;">
                                            {{ Str::limit($value->name, 80) }}
                                        </a>
                                    </h3>
                                    <div class="sv-boss-product-price">
                                        @if ($value->old_price)
                                            <span class="sv-boss-product-original-price">৳ {{ $value->old_price }}</span>
                                        @endif
                                        <span>৳ {{ $value->new_price }}</span>
                                    </div>
                                    <button class="btn sv-boss-add-to-cart" data-product-id="{{ $value->id }}">
                                        <i class="fas fa-shopping-cart me-2"></i>ADD TO CART
                                    </button>
                                    <button class="btn sv-boss-buy-now" data-product-id="{{ $value->id }}">
                                        <i class="fas fa-bolt me-2"></i>BUY NOW
                                    </button>
                                </div>
                            </div>

                             @if (!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
                            <div class='d-flex'>
                                 <div class="pro_btn col-lg-6 col-md-6 col-sm-6 col-6" style='padding-right:0;'>
                                   
                                    <div class="cart_btn order_button">
                                        <a href="{{ route('cart.store.buy') }}"
                                            class="addcartbutton">Buy Now </a>
                                    </div>
                                </div>
                                
                                 <div class="pro_btn col-lg-6 col-md-6 col-sm-6 col-6" style='padding-left:0;'>
                                   <form action="{{ route('cart.store') }}" method="POST" name="formName" class='add_cart_btn_home_main'>
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}" />
                                        <input type="hidden" name="qty" value="1" />
                                     <input type="submit" class="add_cart_btn_home" onclick="add_to_cart(this,event)" name="add_cart" value="Add To Cart" />
                                    </form>

                                </div>
                            </div>
                               
                            @else
                            <div class='d-flex'>
                                 <div class="pro_btn col-lg-6 col-md-6 col-sm-6  col-6" style='padding-right:0;'>
                                    
                                    <form action="{{ route('cart.store.buy') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}" />
                                        <input type="hidden" name="qty" value="1" />
                                        <button type="submit">Buy Now</button>
                                    </form>
                                </div>
                                  <div class="pro_btn col-lg-6 col-md-6 col-sm-6 col-6" style='padding-left:0;'>
                                   <form action="{{ route('cart.store') }}" method="POST" name="formName" class='add_cart_btn_home_main'>
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}" />
                                        <input type="hidden" name="qty" value="1" />
                                     <input type="submit" class="add_cart_btn_home" onclick="add_to_cart(this,event)" name="add_cart" value="Add To Cart" />
                                    </form>

                                </div>
                            </div>
                               
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
    </section>
    @endif
@endforeach

<!--brand section-->
@if(isset($generalsetting) && $generalsetting->home_page_brands_section == 1)
<div class="homeproduct" >
    <div class="container mt-2">
        <div class="col-12 text-center">
            <h2 class="sv-section-title">Hot Deal Brand</h2>
        </div>
        <div class="row">
        @foreach($brands as $brand)
            <div class="col-6 col-md-4 col-lg-2 mb-2">
                <div class="card brand-card d-flex align-items-center justify-content-center">
                    <img src="{{ $brand->image }}" class="card-img-top brand-image" alt="{{ $brand->name }}">
                </div>
            </div>
        @endforeach
    </div>
    </div>
       
</div>
@endif

@endsection @push('script')
<script src="{{ asset('frontEnd/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('frontEnd/js/jquery.syotimer.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $(".hotdeals-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 3,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 6,
                    nav: true,
                    loop: false,
                },
            },
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".category-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 5,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 8,
                    nav: true,
                    loop: false,
                },
            },
        });

        $(".product_slider").owlCarousel({
            margin: 15,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 5,
                    nav: false,
                },
                1000: {
                    items: 6,
                    nav: false,
                },
            },
        });
    });
</script>

<script>
    $("#simple_timer").syotimer({
        date: new Date(2015, 0, 1),
        layout: "hms",
        doubleNumbers: false,
        effectType: "opacity",

        periodUnit: "d",
        periodic: true,
        periodInterval: 1,
    });
</script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#add_to_cart').click(function() {
            gtag("event", "add_to_cart", {
                currency: "BDT",
                value: "1.5",
                items: [
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
<script type="text/javascript">
    $(document).ready(function() {
        $('#order_now').click(function() {
            gtag("event", "add_to_cart", {
                currency: "BDT",
                value: "1.5",
                items: [
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
    function sendSuccess() {
        // size validation
        size = document.forms["formName"]["product_size"].value;
        if (size != "") {
            // access
        } else {
            toastr.warning("Please select any size");
            return false;
        }
        color = document.forms["formName"]["product_color"].value;
        if (color != "") {
            // access
        } else {
            toastr.error("Please select any color");
            return false;
        }
    }
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
</script>

@endpush
