
<!DOCTYPE html>
<html lang="bn">

<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $generalsetting->name }}</title>
        <link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" type="image/x-icon" />
        <!-- fot awesome -->
        <link rel="stylesheet" href="{{ asset('frontEnd/campaign/css') }}/all.css" />
        <!-- core css -->
        <link rel="stylesheet" href="{{ asset('frontEnd/campaign/css') }}/bootstrap.min.css" />
        <link rel="stylesheet" href="{{ asset('frontEnd/campaign/css') }}/animate.css" />
        <!-- owl carousel -->
        <link rel="stylesheet" href="{{ asset('frontEnd/campaign/css') }}/owl.theme.default.css" />
        <link rel="stylesheet" href="{{ asset('frontEnd/campaign/css') }}/owl.carousel.min.css" />
        <!-- owl carousel -->
        <link rel="stylesheet" href="{{ asset('frontEnd/campaign/css') }}/select2.min.css" />
        <!-- common css -->
        <link rel="stylesheet" href="{{ asset('frontEnd/campaign/css') }}/style.css" />
        <link rel="stylesheet" href="{{ asset('frontEnd/campaign/css') }}/responsive.css" />
        
        <meta name="app-url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta name="robots" content="index, follow" />
        <meta name="description" content="{{$campaign_data->description}}" />
        <meta name="keywords" content="{{ $campaign_data->slug }}" />
        
        <!-- Twitter Card data -->
        <meta name="twitter:card" content="product" />
        <meta name="twitter:site" content="{{$campaign_data->name}}" />
        <meta name="twitter:title" content="{{$campaign_data->name}}" />
        <meta name="twitter:description" content="{{ $campaign_data->description}}" />
        <meta name="twitter:creator" content="hellodinajpur.com" />
        <meta property="og:url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta name="twitter:image" content="{{asset($campaign_data->image_one)}}" />
        
        <!-- Open Graph data -->
        <meta property="og:title" content="{{$campaign_data->name}}" />
        <meta property="og:type" content="product" />
        <meta property="og:url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta property="og:image" content="{{asset($campaign_data->image_one)}}" />
        <meta property="og:description" content="{{ $campaign_data->description}}" />
        <meta property="og:site_name" content="{{$campaign_data->name}}" />
        
        
        
        
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    
    
    

    <!-- Google Fonts - Hind Siliguri for Bengali -->
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;700&display=swap" rel="stylesheet">

        
        
        
        
        
</head>
<body>
    @php
        $subtotal = Cart::instance('shopping')->subtotal();
        $subtotal=str_replace(',','',$subtotal);
        $subtotal=str_replace('.00', '',$subtotal);
        $shipping = Session::get('shipping')?Session::get('shipping'):0;
    @endphp


    <!-- Hero Section -->
  
    <section class="hero-section">
        <div class="hero-content container">
            <a href="{{ route('home') }}">
                @if($generalsetting->logo)
                    <img src="{{asset($generalsetting->logo)}}" alt="{{ $generalsetting->name }}" class="logo">
                @else
                    <img src="{{asset('frontEnd/campaign/images/default-logo.png')}}" alt="{{ $generalsetting->name }}" class="logo">
                @endif
            </a>
            <h1 class="hero-title" data-aos="fade-up">{{ $campaign_data->hero_title ?? $campaign_data->name }}</h1>
            @if($campaign_data->hero_subtitle)
            <p class="hero-subtitle" data-aos="fade-up">{{ $campaign_data->hero_subtitle }}</p>
            @endif
            <div class="hero-video-container mb-4" data-aos="fade-up">
                <div class="ratio ratio-16x9 shadow-lg rounded overflow-hidden">
                    @if($campaign_data->video)
                        <iframe src="{{$campaign_data->video}}?autoplay=1&mute=1" allow="autoplay" allowfullscreen></iframe>
                    @else
                        <iframe src="https://www.youtube.com/embed/F9Dl-1V4NYc" allow="autoplay" allowfullscreen></iframe>
                    @endif
                </div>
            </div>
            <div class="price-container" data-aos="fade-up">
                @if($product->old_price)
                <p class="current-price">à¦°à§‡à¦—à§à¦²à¦¾à¦° à¦ªà§à¦°à¦¾à¦‡à¦¸à¦ƒ {{$product->old_price}} à¦Ÿà¦¾à¦•à¦¾!</p>
                @endif
                <p class="current-price">à¦…à¦«à¦¾à¦° à¦ªà§à¦°à¦¾à¦‡à¦¸à¦ƒ {{$product->new_price}} à¦Ÿà¦¾à¦•à¦¾!</p>
            </div>
            <a href="#order_form" class="cta-button order-section-button" data-aos="fade-up">{{ $campaign_data->hero_button_text ?? 'à¦…à¦°à§à¦¡à¦¾à¦° à¦•à¦°à§à¦¨' }}</a>
        </div>
    </section>

    <div class="container text-center my-5">
        <div class="free-delivery fw-bold fs-4 mb-3">
            à¦¸à§€à¦®à¦¿à¦¤ à¦¸à¦®à¦¯à¦¼à§‡à¦° à¦œà¦¨à§à¦¯ à¦°à¦¯à¦¼à§‡à¦›à§‡ <span class="text-danger">à¦«à§à¦°à¦¿ à¦¡à§‡à¦²à¦¿à¦­à¦¾à¦°à¦¿!!</span>
        </div>

        @if($product->old_price)
        <div class="regular-price fs-5 mb-2">
            à¦°à§‡à¦—à§à¦²à¦¾à¦° à¦ªà§à¦°à¦¾à¦‡à¦¸: <span class="text-decoration-line-through text-muted">{{$product->old_price}}</span> à¦Ÿà¦¾à¦•à¦¾à¥¤
        </div>
        @endif

        <div class="offer-price fw-bold fs-4 mb-4">
            à¦…à¦«à¦¾à¦° à¦ªà§à¦°à¦¾à¦‡à¦¸ <span class="bg-danger text-white px-3 py-2 rounded-circle">{{$product->new_price}}</span> à¦Ÿà¦¾à¦•à¦¾à¥¤
        </div>

        <a href="#order_form" class="btn btn-success btn-lg">
            à¦…à¦°à§à¦¡à¦¾à¦° à¦•à¦°à¦¤à§‡ à¦•à§à¦²à¦¿à¦• à¦•à¦°à§à¦¨ <i class="fas fa-arrow-right"></i>
        </a>

        <p class="fw-bold mt-4 mb-2">à¦ªà§à¦°à¦¯à¦¼à§‹à¦œà¦¨à§‡ à¦•à¦² à¦•à¦°à§à¦¨</p>
        
        <div class="text-center">
            <a href="tel:{{$generalsetting->phone}}" class="fs-4 text-primary text-decoration-none">
                <i class="fas fa-phone-alt"></i> {{$generalsetting->phone}}
            </a>
        </div>
    </div>
    
    
    
    <!-- Product Slider Section -->
    <section class="slider-section">
        <div class="container">
        <div class="review-banner">
            <h2 class="review-banner-text" data-aos="fade-up">à¦•à¦¾à¦¸à§à¦Ÿà¦®à¦¾à¦° à¦«à¦¿à¦¡à¦¬à§à¦¯à¦¾à¦• à¦¦à§‡à¦–à§‡ à¦…à¦°à§à¦¡à¦¾à¦° à¦•à¦°à§à¦¨</h2>
        </div>
            <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
         
                <div class="carousel-inner yellow-bg">
                    <div class="carousel-item active">
                        <div class="d-flex justify-content-center">
                            <div class="row align-items-center">
                                <div class="col-md-6 text-center">
                                    <img src="{{asset($campaign_data->image_one)}}" class="img-fluid" alt="Wallet 1">
                                </div>
                                <div class="col-md-6 text-center">
                                    <img src="{{asset($campaign_data->image_one)}}" class="img-fluid" alt="Wallet 2">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="d-flex justify-content-center">
                            <img src="{{asset($campaign_data->image_one)}}" class="img-fluid" alt="Wallet 3">
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
            
        </div>
    </section>
    
    
    
    
    
    
    
    
    
    
    
    <!-- Features Section -->
   {{ $campaign_data->description }}    
    
    
    
    
    
    
    
  <!-- rrrrrrrrrr -->
  <section class="gallery-section" id="gallery">
    <div class="container">
        <div class="review-banner">
            <h2 class="review-banner-text" data-aos="fade-up">à¦•à¦¾à¦¸à§à¦Ÿà¦®à¦¾à¦° à¦«à¦¿à¦¡à¦¬à§à¦¯à¦¾à¦• à¦¦à§‡à¦–à§‡ à¦…à¦°à§à¦¡à¦¾à¦° à¦•à¦°à§à¦¨</h2>
        </div>

        <!-- Main Gallery -->
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="rev_inn">
                            <div class="review_slider owl-carousel">
                            @foreach($campaign_data->images as $key=>$value)
                            <div class="review_item">
                                <img src="{{asset($value->image)}}" alt="">
                            </div>
                            @endforeach
                           </div>
                            <div class="col-sm-12">
                                <div class="ord_btn">
                                    <a href="#order_form" class="cam_order_now" id="cam_order_now"> à¦…à¦°à§à¦¡à¦¾à¦° à¦•à¦°à¦¤à§‡ à¦•à§à¦²à¦¿à¦• à¦•à¦°à§à¦¨ <i class="fa-solid fa-hand-point-right"></i> </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</section>


   <!-- Return Policy Title -->
   <div class="container">
    <h1 class="header-title">à¦°à¦¿à¦Ÿà¦¾à¦°à§à¦¨ à¦ªà¦²à¦¿à¦¸à¦¿</h1>
</div>

<!-- Notice Box -->
<section class="notice-section">
    <div class="container">
        <div class="notice-box">
            <p class="mb-0"> {!! $campaign_data->short_description !!} à¦°à¦¿à¦Ÿà¦¾à¦°à§à¦¨à¥¤</p>
        </div>
    </div>
</section>
<!------------------------->


  
    <section class="form_sec">
        <div class="container">
           <div class="row">
             <div class="col-sm-12">
                <div class="form_inn">
                    <div class="col-sm-12">
                        <div class="row">
                <div class="col-sm-12">
                    <h2 class="campaign_offer">à¦…à¦«à¦¾à¦°à¦Ÿà¦¿ à¦¸à§€à¦®à¦¿à¦¤ à¦¸à¦®à§Ÿà§‡à¦° à¦œà¦¨à§à¦¯, à¦¤à¦¾à¦‡ à¦…à¦«à¦¾à¦° à¦¶à§‡à¦· à¦¹à¦“à§Ÿà¦¾à¦° à¦†à¦—à§‡à¦‡ à¦…à¦°à§à¦¡à¦¾à¦° à¦•à¦°à§à¦¨</h2>
                </div>
            </div>
            <div class="row order_by">
            <div class="col-sm-5 cus-order-2">
                <div class="checkout-shipping" id="order_form">
                    <form action="{{route('customer.ordersave')}}" method="POST" data-parsley-validate="">
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <h5 class="potro_font">à¦†à¦ªà¦¨à¦¾à¦° à¦‡à¦¨à¦«à¦°à¦®à§‡à¦¶à¦¨ à¦¦à¦¿à¦¨  </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group mb-3">
                                        <label for="name">à¦†à¦ªà¦¨à¦¾à¦° à¦¨à¦¾à¦® à¦²à¦¿à¦–à§à¦¨ * </label>
                                        <input type="text" id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{old('name')}}" placeholder="à¦¨à¦¾à¦®" required>
                                        @error('name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col-end -->
                                <div class="col-sm-12">
                                    <div class="form-group mb-3">
                                        <label for="phone">à¦†à¦ªà¦¨à¦¾à¦° à¦®à§‹à¦¬à¦¾à¦‡à¦² à¦²à¦¿à¦–à§à¦¨ *</label>
                                        <input type="number" minlength="11" id="number" maxlength="11" pattern="0[0-9]+" title="please enter number only and 0 must first character" title="Please enter an 11-digit number." id="phone" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{old('phone')}}" placeholder="+à§®à§® à¦¬à¦¾à¦¦à§‡ à§§à§§ à¦¸à¦‚à¦–à§à¦¯à¦¾ "  required>
                                        @error('phone')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col-end -->
                                <div class="col-sm-12">
                                    <div class="form-group mb-3">
                                        <label for="address">à¦†à¦ªà¦¨à¦¾à¦° à¦ à¦¿à¦•à¦¾à¦¨à¦¾ à¦²à¦¿à¦–à§à¦¨   *</label>
                                        <input type="address" id="address" class="form-control @error('address') is-invalid @enderror" placeholder="à¦œà§‡à¦²à¦¾, à¦¥à¦¾à¦¨à¦¾, à¦—à§à¦°à¦¾à¦® " name="address" value="{{old('address')}}"  required>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group mb-3">
                                        <label for="area">à¦†à¦ªà¦¨à¦¾à¦° à¦à¦°à¦¿à¦¯à¦¼à¦¾ à¦¸à¦¿à¦²à§‡à¦•à§à¦Ÿ à¦•à¦°à§à¦¨  *</label>
                                        <select type="area" id="area" class="form-control @error('area') is-invalid @enderror" name="area"   required>
                                            @foreach($shippingcharge as $key=>$value)
                                            <option value="{{$value->id}}">{{$value->name}}</option>
                                            @endforeach
                                        </select>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col-end -->
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <button class="order_place" type="submit">à¦…à¦°à§à¦¡à¦¾à¦° à¦•à¦¨à§à¦«à¦¾à¦°à§à¦® à¦•à¦°à§à¦¨ </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- card end -->
                </form>
                </div>
            </div>
            <!-- col end -->
            <div class="col-sm-7 cust-order-1">
                <div class="cart_details">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="potro_font">à¦ªà¦£à§à¦¯à§‡à¦° à¦¬à¦¿à¦¬à¦°à¦£ </h5>
                        </div>
                        <div class="card-body cartlist  table-responsive">
                            <table class="cart_table table table-bordered table-striped text-center mb-0">
                                <thead>
                                   <tr>
                                      <th style="width: 20%;">à¦¡à¦¿à¦²à¦¿à¦Ÿ</th>
                                      <th style="width: 40%;">à¦ªà§à¦°à§‹à¦¡à¦¾à¦•à§à¦Ÿ</th>
                                      <th style="width: 20%;">à¦ªà¦°à¦¿à¦®à¦¾à¦£</th>
                                      <th style="width: 20%;">à¦®à§‚à¦²à§à¦¯</th>
                                     </tr>
                                </thead>

                                <tbody>
                                    @foreach(Cart::instance('shopping')->content() as $value)
                                    <tr>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger cart_remove" data-id="{{$value->rowId}}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                        <td class="text-left">
                                             <a style="font-size: 14px;" href="{{route('product',$value->options->slug)}}"><img src="{{asset($value->options->image)}}" height="30" width="30"> {{Str::limit($value->name,20)}}</a>
                                        </td>
                                        <td width="15%" class="cart_qty">
                                            <div class="qty-cart vcart-qty">
                                                <div class="quantity">
                                                    <button type="button" class="minus cart_decrement" data-id="{{$value->rowId}}">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="text" value="{{$value->qty}}" readonly />
                                                    <button type="button" class="plus cart_increment" data-id="{{$value->rowId}}">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td>à§³{{$value->price*$value->qty}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                     <tr>
                                      <th colspan="3" class="text-end px-4">à¦®à§‹à¦Ÿ</th>
                                      <td>
                                       <span id="net_total"><span class="alinur">à§³ </span><strong>{{$subtotal}}</strong></span>
                                      </td>
                                     </tr>
                                     <tr>
                                      <th colspan="3" class="text-end px-4">à¦¡à§‡à¦²à¦¿à¦­à¦¾à¦°à¦¿ à¦šà¦¾à¦°à§à¦œ</th>
                                      <td>
                                       <span id="cart_shipping_cost"><span class="alinur">à§³ </span><strong>{{$shipping}}</strong></span>
                                      </td>
                                     </tr>
                                     <tr>
                                      <th colspan="3" class="text-end px-4">à¦¸à¦°à§à¦¬à¦®à§‹à¦Ÿ</th>
                                      <td>
                                       <span id="grand_total"><span class="alinur">à§³ </span><strong>{{$subtotal+$shipping}}</strong></span>
                                      </td>
                                     </tr>
                                    </tfoot>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
            <!-- col end -->
            </div>
                    </div>
                </div>

             </div>
            </div>
        </div>
    </section>

    <!-- Loading Spinner -->
    <div id="loading">
        <div class="spinner"></div>
    </div>

        <script src="{{ asset('frontEnd/campaign/js') }}/jquery-2.1.4.min.js"></script>
        <script src="{{ asset('frontEnd/campaign/js') }}/all.js"></script>
        <script src="{{ asset('frontEnd/campaign/js') }}/bootstrap.min.js"></script>
        <script src="{{ asset('frontEnd/campaign/js') }}/owl.carousel.min.js"></script>
        <script src="{{ asset('frontEnd/campaign/js') }}/select2.min.js"></script>
        <script src="{{ asset('frontEnd/campaign/js') }}/script.js"></script>
        <!-- bootstrap js -->
        <script>
            $(document).ready(function () {
                $(".owl-carousel").owlCarousel({
                    margin: 15,
                    loop: true,
                    dots: false,
                    autoplay: true,
                    autoplayTimeout: 6000,
                    autoplayHoverPause: true,
                    items: 1,
                    });
                $('.owl-nav').remove();
            });
        </script>
        <script>
            $(document).ready(function() {
                $('.select2').select2();
            });
        </script>
        <script>
             $("#area").on("change", function () {
                var id = $(this).val();
                $.ajax({
                    type: "GET",
                    data: { id: id },
                    url: "{{route('shipping.charge')}}",
                    dataType: "html",
                    success: function(response){
                        $('.cartlist').html(response);
                    }
                });
            });
        </script>
           <script>
            $(".cart_remove").on("click", function () {
                var id = $(this).data("id");
                $("#loading").show();
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.remove')}}",
                        success: function (data) {
                            if (data) {
                                $(".cartlist").html(data);
                                $("#loading").hide();
                                return cart_count() + mobile_cart() + cart_summary();
                            }
                        },
                    });
                }
            });
            function cart_update_row(id, quantity) {
                return $.ajax({
                    type: "POST",
                    url: `{{ url('/cart-update') }}/${encodeURIComponent(id)}`,
                    dataType: "json",
                    data: {
                        _token: "{{ csrf_token() }}",
                        quantity: quantity
                    }
                });
            }

            function get_cart_btn_qty($btn) {
                var raw = parseInt($btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty').find('input').first().val() || '1', 10);
                return Number.isFinite(raw) && raw > 0 ? raw : 1;
            }

            function set_cart_btn_qty($btn, qty) {
                $btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty').find('input').first().val(String(qty));
            }

            function set_cart_btn_pending($btn, flag) {
                var $scope = $btn.closest('.productQty, .qty-cart, .vcart-qty, .cart-item-qty');
                $scope.find('.cart_increment, .cart_decrement').prop('disabled', !!flag);
                if (flag) {
                    $scope.addClass('is-cart-pending');
                } else {
                    $scope.removeClass('is-cart-pending');
                }
            }

            $(".cart_increment").on("click", function () {
                var $btn = $(this);
                var id = $btn.data("id");
                $("#loading").show();
                if (!id || $btn.prop("disabled")) return;
                var prevQty = get_cart_btn_qty($btn);
                var nextQty = prevQty + 1;
                set_cart_btn_qty($btn, nextQty); // optimistic update
                set_cart_btn_pending($btn, true);
                cart_update_row(id, nextQty).done(function (response) {
                    if (response && response.status === "error") {
                        set_cart_btn_qty($btn, prevQty); // rollback
                    }
                }).fail(function () {
                    set_cart_btn_qty($btn, prevQty); // rollback
                }).always(function () {
                    set_cart_btn_pending($btn, false);
                    $("#loading").hide();
                    return cart_count() + mobile_cart();
                });
            });

            $(".cart_decrement").on("click", function () {
                var $btn = $(this);
                var id = $btn.data("id");
                $("#loading").show();
                if (!id || $btn.prop("disabled")) return;
                var currentQty = get_cart_btn_qty($btn);
                if (currentQty <= 1) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.remove')}}"
                    }).always(function () {
                        $("#loading").hide();
                        return cart_count() + mobile_cart();
                    });
                    return;
                }
                var prevQty = currentQty;
                var nextQty = currentQty - 1;
                set_cart_btn_qty($btn, nextQty); // optimistic update
                set_cart_btn_pending($btn, true);
                cart_update_row(id, nextQty).done(function (response) {
                    if (response && response.status === "error") {
                        set_cart_btn_qty($btn, prevQty); // rollback
                    }
                }).fail(function () {
                    set_cart_btn_qty($btn, prevQty); // rollback
                }).always(function () {
                    set_cart_btn_pending($btn, false);
                    $("#loading").hide();
                    return cart_count() + mobile_cart();
                });
            });

        </script>
        <script>
            $('.review_slider').owlCarousel({   
                dots: false,
                arrow: false,
                autoplay: true,
                loop: true,
                margin: 10,
                smartSpeed: 1000,
                mouseDrag: true,
                touchDrag: true,
                items: 6,
                responsiveClass: true,
                responsive: {
                    300: {
                        items: 1,
                    },
                    480: {
                        items: 2,
                    },
                    768: {
                        items: 5,
                    },
                    1170: {
                        items: 5,
                    },
                }
            });
        </script>

        <script>
            $('.campro_img_slider').owlCarousel({   
                dots: false,
                arrow: false,
                autoplay: true,
                loop: true,
                margin: 10,
                smartSpeed: 1000,
                mouseDrag: true,
                touchDrag: true,
                items: 3,
                responsiveClass: true,
                responsive: {
                    300: {
                        items: 1,
                    },
                    480: {
                        items: 2,
                    },
                    768: {
                        items: 3,
                    },
                    1170: {
                        items: 3,
                    },
                }
            });
        </script>
    </body>
</html>


<!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>


    <!-- JavaScript -->
    <script>
        function selectPackage(packageId, price) {

            $("#package" + packageId).prop("checked", true);
            let productId = "163";


            // Make an AJAX request to store the cart data
            $.ajax({
                url: "https://nbusinessmart.shop/carts",
                type: "POST",
                data: {
                    product_id: productId,
                    variation_id: packageId,
                    quantity: 1,
                    price: price,
                    _token: "RfaH0kUs32dTRSksDxF5X7YQGX27uanB3YcAIxgU"
                },
                success: function(response) {
                    //console.log("Cart updated:", response);
                },
                error: function(xhr, status, error) {
                    console.error("Error updating cart:", error);
                }
            });

            calculateTotal();
        }

        // Calculate the total price, subtotal, and delivery charge
        function calculateTotal() {
            let selectedPackagePrice = parseFloat($("input[name='package']:checked").data("price")) || 0;
            let deliveryCharge = $("input[name='delivery-area']:checked").data('charge') || 0;
            let subtotal = selectedPackagePrice;

            // Update subtotal and total
            $("#subtotal").text(`${subtotal.toFixed(0)}à§³`);
            $("#delivery-charge").text(`${deliveryCharge}à§³`);
            $("#total-price").text(`${(subtotal + parseFloat(deliveryCharge)).toFixed(0)}à§³`);
        }

        $(document).ready(function() {

            $(".order-section-button").on("click", function() {
                $("html, body").animate({
                    scrollTop: $("#order-now-section").offset().top
                }, 800);
            });

            // Select the first delivery area option by default
            if (!$("input[name='delivery-area']:checked").length) {
                $("input[name='delivery-area']:first").prop("checked", true);
            }

            // Auto-select the first package when the page loads
            let firstPackage = $(".package-radio").first();
            if (firstPackage.length) {
                firstPackage.prop("checked", true);
                let packageId = firstPackage.attr("id").replace("package", "");
                let price = firstPackage.data("price");
                selectPackage(packageId, price);
            }

            // Calculate total on page load
            calculateTotal();

            // Attach event listener to package selection
            $(".package-option").on("click", function() {
                let packageId = $(this).find("input.package-radio").attr("id").replace("package", "");
                let price = $(this).find("input.package-radio").data("price");
                selectPackage(packageId, price);
            });

            // Attach event listener to delivery area selection
            $("input[name='delivery-area']").on("change", function() {
                calculateTotal();
            });


            // Submit the order form with ajax
            $("#orderForm").submit(function(e) {
                e.preventDefault();

                var data = {
                    first_name: $("input[name='first_name']").val(),
                    mobile: $("input[name='mobile']").val(),
                    shipping_address: $("textarea[name='shipping_address']").val(),
                    ip_address: 1234,
                    payment_method: "bkash",
                    delivery_charge_id: $("input[name='delivery-area']:checked").val(),
                    _token: "RfaH0kUs32dTRSksDxF5X7YQGX27uanB3YcAIxgU"
                };

                $.ajax({
                    type: "POST",
                    url: "https://nbusinessmart.shop/checkouts",
                    data: data,
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            if (response.url) {
                                window.location.href = response.url;
                            } else {
                                console.log(response.msg);
                            }
                        } else {
                            console.log(response.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log(xhr.responseText);
                        alert("An error occurred: " + error);
                    }
                });
            });
        });
    </script>

    <!-- Initialize Scripts For Pluging -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });

            // Product Slider
            const productSwiper = new Swiper('.product-swiper', {
                effect: 'coverflow',
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: 'auto',
                coverflowEffect: {
                    rotate: 50,
                    stretch: 0,
                    depth: 100,
                    modifier: 1,
                    slideShadows: true,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });

            // Gallery
            const galleryThumbs = new Swiper('.gallery-thumbs', {
                spaceBetween: 10,
                slidesPerView: 4,
                freeMode: true,
                watchSlidesProgress: true,
                breakpoints: {
                    320: {
                        slidesPerView: 3,
                    },
                    768: {
                        slidesPerView: 4,
                    }
                }
            });

            const galleryMain = new Swiper('.gallery-main', {
                spaceBetween: 10,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                thumbs: {
                    swiper: galleryThumbs,
                },
            });
            const reviewSlider = new Swiper('.review-slider', {
                spaceBetween: 10,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    320: {
                        slidesPerView: 3,
                    },
                    768: {
                        slidesPerView: 4,
                    }
                }
            });

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();

                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);

                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });



        });
    </script>





<!-- Meta Pixel Code -->
<!-- End Meta Pixel Code -->


</body>

</html>



<!-- ------------------------------ -->

<style>
    :root {
        --primary-color: #ff0000;
        --secondary-color: #008000;
        --accent-color: #FFD700;
        --text-color: #333333;
        --light-bg: #f8f9fa;
        --dark-bg: #343a40;
    }

    body {
        font-family: 'Hind Siliguri', sans-serif;
        overflow-x: hidden;
        color: var(--text-color);
    }

    .hero-section {
        position: relative;
        background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://sjc.microlink.io/DkZFF8NxGvYFVv4ZKiqbl_5t9iGTIHy3R5uGkmwZZX_I0XI7xJiD0eX-g2Z0bsYFJjn3wsOqHqLCfkv--5AkTw.jpeg');
        background-size: cover;
        background-position: center;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 80px 0;
        color: white;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .logo {
        max-height: 80px;
        margin-bottom: 30px;
        filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.5));
    }

    .hero-title {
        font-weight: bold;
        font-size: 2.5rem;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .hero-subtitle {
        font-size: 1.2rem;
        margin-bottom: 30px;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    }

    .wave-divider {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
        z-index: 2;
    }

    .wave-divider svg {
        position: relative;
        display: block;
        width: calc(100% + 1.3px);
        height: 65px;
    }

    .wave-divider .shape-fill {
        fill: #FFFFFF;
    }

    .price-section {
        background: white;
        padding: 40px 0;
        text-align: center;
        position: relative;
    }

    .free-delivery {
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 20px;
        position: relative;
        display: inline-block;
    }

    .free-delivery::after {
        content: "";
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: var(--primary-color);
        background-image: url("data:image/svg+xml,%3Csvg width='40' height='12' viewBox='0 0 40 12' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 6c2-2 5-2 7 0s5 2 7 0 5-2 7 0 5 2 7 0 5-2 7 0 5 2 7 0' fill='%23ff0000' fill-opacity='1' fill-rule='evenodd'/%3E%3C/svg%3E");
        background-size: 40px 12px;
    }

    .original-price {
        color: var(--primary-color);
        font-size: 1.5rem;
        text-decoration: line-through;
        margin-bottom: 5px;
    }

    .offer-price {
        color: var(--secondary-color);
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .product-slider {
        background: linear-gradient(45deg, #FFD700, #FFA500);
        padding: 60px 0;
        position: relative;
    }

    .product-slider::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("data:image/svg+xml,%3Csvg width='52' height='26' viewBox='0 0 52 26' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M10 10c0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6h2c0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4v2c-3.314 0-6-2.686-6-6 0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6zm25.464-1.95l8.486 8.486-1.414 1.414-8.486-8.486 1.414-1.414z' /%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .swiper {
        width: 100%;
        padding-top: 50px;
        padding-bottom: 50px;
    }

    .swiper-slide {
        background-position: center;
        background-size: cover;
        width: 300px;
        height: 300px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .swiper-slide img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .feature-section {
        padding: 60px 0;
        background-color: white;
    }

    .section-title {
        text-align: center;
        font-weight: bold;
        font-size: 2rem;
        margin-bottom: 50px;
        position: relative;
    }

    .section-title::after {
        content: "";
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: var(--primary-color);
    }

    .feature-box {
        border: 2px solid var(--primary-color);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        background: white;
        transition: all 0.3s ease;
        height: 100%;
    }

    .feature-box:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .feature-box h3 {
        color: var(--primary-color);
        font-weight: bold;
        font-size: 1.5rem;
        margin-bottom: 15px;
        text-align: center;
    }

    .feature-box p {
        color: #666;
        font-size: 1rem;
        text-align: center;
    }

    .feature-box img {
        width: 100%;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .benefits-section {
        padding: 60px 0;
        background-color: var(--light-bg);
        position: relative;
    }

    .benefits-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        z-index: 0;
    }

    .benefits-list {
        list-style: none;
        padding: 0;
        position: relative;
        z-index: 1;
    }

    .benefits-list li {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }

    .benefits-list li:hover {
        transform: translateX(10px);
    }

    .benefits-list li i {
        color: var(--primary-color);
        margin-right: 15px;
        font-size: 24px;
        flex-shrink: 0;
    }

    .gallery-section {
        padding: 60px 0;
        background-color: white;
    }

    .gallery-main {
        margin-bottom: 20px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .gallery-main .swiper-slide {
        height: 400px;
        width: 100%;
    }

    .gallery-main .swiper-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gallery-thumbs {
        height: 100px;
    }

    .gallery-thumbs .swiper-slide {
        height: 100%;
        width: 100px;
        opacity: 0.4;
        cursor: pointer;
    }

    .gallery-thumbs .swiper-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gallery-thumbs .swiper-slide-thumb-active {
        opacity: 1;
    }

    .order-section {
        padding: 60px 0;
        background-color: var(--light-bg);
    }

    .order-form {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .order-form h2 {
        text-align: center;
        margin-bottom: 30px;
        color: var(--dark-bg);
        font-weight: bold;
    }

    .package-option {
        display: flex;
        align-items: center;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 10px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .package-option:hover {
        border-color: var(--primary-color);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .package-option img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
        margin-right: 15px;
    }

    .package-option .package-details {
        flex-grow: 1;
    }

    .package-option .package-price {
        font-weight: bold;
        color: var(--primary-color);
    }

    .order-summary {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .order-summary .item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #ddd;
    }

    .order-summary .total {
        display: flex;
        justify-content: space-between;
        font-weight: bold;
        font-size: 1.2rem;
        margin-top: 15px;
    }

    .submit-btn {
        background-color: var(--primary-color);
        color: white;
        font-weight: bold;
        padding: 15px 30px;
        border: none;
        border-radius: 5px;
        font-size: 1.2rem;
        width: 100%;
        transition: all 0.3s ease;
    }

    .submit-btn:hover {
        background-color: #cc0000;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(255, 0, 0, 0.2);
    }

    footer {
        background-color: var(--dark-bg);
        color: white;
        padding: 40px 0 20px;
    }

    footer h5 {
        font-weight: bold;
        margin-bottom: 20px;
    }

    footer p,
    footer a {
        color: rgba(255, 255, 255, 0.7);
    }

    footer a:hover {
        color: white;
        text-decoration: none;
    }

    .social-icons {
        display: flex;
        gap: 15px;
    }

    .social-icons a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .social-icons a:hover {
        background: var(--primary-color);
        transform: translateY(-3px);
    }

    .copyright {
        text-align: center;
        padding-top: 20px;
        margin-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    @media (max-width: 768px) {
        .hero-section {
            min-height: auto;
            padding: 40px 0;
        }

        .hero-video-container {
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .price-text {
            font-size: 1.2rem;
        }

        .original-price {
            font-size: 1.1rem;
        }

        .current-price {
            font-size: 1.3rem;
        }

        .cta-button {
            padding: 12px 25px;
            font-size: 1rem;
        }

        .free-delivery {
            font-size: 1.5rem;
        }

        .original-price {
            font-size: 1.2rem;
        }

        .offer-price {
            font-size: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
        }

        .feature-box {
            padding: 15px;
        }

        .order-form {
            padding: 20px;
        }

        .gallery-main .swiper-slide {
            height: 300px;
        }
    }


    .title-container {
        text-align: center;
        margin-bottom: 10px;
        position: relative;
    }

    .title {
        color: #ff0000;
        font-size: 36px;
        font-weight: bold;
        margin: 0;
        padding: 10px 0;
        display: inline-block;
    }

    .wavy-line {
        display: block;
        width: 200px;
        height: 10px;
        margin: 0 auto;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 30'%3E%3Cpath fill='none' stroke='%23ff0000' stroke-width='10' d='M0,15 C50,0 50,30 100,15 C150,0 150,30 200,15 C250,0 250,30 300,15 C350,0 350,30 400,15 C450,0 450,30 500,15 C550,0 550,30 600,15' /%3E%3C/svg%3E");
        background-repeat: repeat-x;
        background-size: contain;
    }

    .policy-box {
        background-color: #ffe6e6;
        padding: 20px;
        border-radius: 5px;
        text-align: center;
        font-size: 18px;
        line-height: 1.5;
    }

    .call-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        text-align: center;
    }

    .call-cart-button {
        background-color: #ff0000;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 12px 24px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }

    .call-cart-icon {
        margin-left: 10px;
    }

    .call-heading {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #000000;
    }

    .call-phone-button {
        background-color: #006400;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 12px 24px;
        font-size: 20px;
        font-weight: bold;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .call-phone-icon {
        margin-right: 10px;
    }

    .call-divider {
        height: 1px;
        background-color: #e0e0e0;
        margin: 20px 0;
    }

    .review-banner {
        background-color: red;
        text-align: center;
        padding: 15px 0;
        width: 100%;
    }

    .review-banner-text {
        color: white;
        font-size: 24px;
        font-weight: bold;
        margin: 0;
    }
</style>
<style>
/* Custom CSS */
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap');


/* Header with wave effect */
.header {
    position: relative;
    width: 100%;
    height: 100px;
    background: url('https://i.imgur.com/2hLzN3H.png') no-repeat center;
    background-size: cover;
}

.header-title {
    color: #ff0000;
    font-weight: bold;
    text-align: center;
    font-size: 2.5rem;
    margin-top: 20px;
    text-decoration: underline;
    text-decoration-color: #ff0000;
    text-decoration-thickness: 3px;
    text-underline-offset: 8px;
}

@media (max-width: 768px) {
    .header-title {
        font-size: 1.8rem;
    }
}

.notice-box {
    background-color: #ffe6e6;
    padding: 15px;
    text-align: center;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
}


.free-delivery {
    font-size: 24px;
    font-weight: bold;
    margin: 20px 0;
}

.free-delivery span {
    color: red;
    text-decoration: underline;
}

@media (max-width: 768px) {
    .free-delivery {
        font-size: 20px;
    }
}

.regular-price {
    font-size: 22px;
    color: blue;
    font-weight: bold;
    margin: 15px 0;
}

.strike {
    text-decoration: line-through;
    color: red;
}

.offer-price {
    font-size: 22px;
    color: green;
    font-weight: bold;
    margin: 15px 0;
}

/* Circle style */
.circle {
    display: inline-block;
    padding: 10px 15px;
    border: 2px solid red;
    border-radius: 50%;
    font-weight: bold;
    position: relative;
    animation: pulse 1.5s ease-in-out infinite;
}

/* Circle animation */
@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7);
    }
    50% {
        transform: scale(1.1);
        box-shadow: 0 0 10px 5px rgba(255, 0, 0, 0.3);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 0, 0, 0);
    }
}

.order-btn {
    background-color: #ff0000;
    color: white;
    border: none;
    padding: 10px 30px;
    font-size: 1.2rem;
    border-radius: 5px;
    margin: 20px 0;
    transition: all 0.3s ease;
}

.order-btn:hover {
    background-color: #cc0000;
    transform: scale(1.05);
}

.contact-text {
    text-align: center;
    font-size: 1.5rem;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .contact-text {
        font-size: 1.2rem;
    }
}

.phone-number {
    background-color: #008000;
    color: white;
    padding: 10px 20px;
    font-size: 1.3rem;
    display: inline-block;
    border-radius: 5px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.phone-number:hover {
    background-color: #006600;
    color: white;
}

@media (max-width: 768px) {
    .phone-number {
        font-size: 1.1rem;
        padding: 8px 15px;
    }
}

.slider-section {
    background-color: #fff;
    padding: 20px 0;
    margin: 20px 0;
}

.carousel-item img {
    max-height: 400px;
    margin: 0 auto;
    object-fit: contain;
}

@media (max-width: 768px) {
    .carousel-item img {
        max-height: 250px;
    }
}

.features-title {
    background-color: #ff0000;
    color: white;
    text-align: center;
    padding: 15px;
    font-size: 1.8rem;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .features-title {
        font-size: 1.5rem;
        padding: 10px;
    }
}

.feature-card {
    border: 3px solid #ff0000;
    border-radius: 15px;
    padding: 15px;
    text-align: center;
    margin-bottom: 20px;
    background-color: #fff;
    height: 100%;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.feature-card img {
    width: 120px;
    height: 120px;
    margin: 0 auto 15px;
    display: block;
    object-fit: contain;
}

@media (max-width: 768px) {
    .feature-card img {
        width: 100px;
        height: 100px;
    }
}

.feature-card h3 {
    color: #ff0000;
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .feature-card h3 {
        font-size: 1.2rem;
    }
}

.feature-card p {
    font-size: 0.9rem;
    color: #333;
}

.yellow-bg {
    /*background-color: #ffff00;*/
    /*background-image: radial-gradient(#ffff00, #ffd700);*/
}

.pagination {
    justify-content: center;
    margin: 20px 0;
}

.pagination .page-item.active .page-link {
    background-color: #ff0000;
    border-color: #ff0000;
}

.pagination .page-link {
    color: #ff0000;
}

.divider {
    height: 1px;
    background-color: #ddd;
    margin: 30px 0;
}
</style>


<style>
    :root {
        --primary-color: #ff0000;
        --secondary-color: #008000;
        --accent-color: #FFD700;
        --text-color: #333333;
        --light-bg: #f8f9fa;
        --dark-bg: #343a40;
    }

    body {
        font-family: 'Hind Siliguri', sans-serif;
        overflow-x: hidden;
        color: var(--text-color);
    }

    .hero-section {
        padding: 60px 0 100px;
        text-align: center;
        position: relative;
        background-image: url('https://nbusinessmart.shop/1.webp');
        background-size: cover;
        background-position: center;
        color: white;
        box-shadow: inset 0 0 0 2000px rgba(0, 0, 0, 0.4);
    }
.original-price.aos-init.aos-animate {
    color: #000;
}

element.style {
    color: var(--bs-form-control-disabled-bg);
}
    .logo-container {
        margin-bottom: 30px;
    }

    .logo {
        max-height: 60px;
        filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.5));
    }

    .hero-title {
        color: white;
        font-weight: bold;
        font-size: 2rem;
        margin-bottom: 30px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .custom-video-container {
        max-width: 600px;
        margin: 0 auto 30px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        position: relative;
        background-color: #000;
    }

    .video-thumbnail {
        width: 100%;
        height: auto;
        cursor: pointer;
        position: relative;
        transition: all 0.3s ease;
    }

    .video-thumbnail:hover {
        opacity: 0.8;
    }

    .play-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 70px;
        height: 70px;
        background-color: rgba(255, 0, 0, 0.8);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 2;
    }

    .play-button:hover {
        background-color: rgba(255, 0, 0, 1);
        transform: translate(-50%, -50%) scale(1.1);
    }

    .play-button i {
        color: white;
        font-size: 30px;
        margin-left: 5px;
    }

    .video-iframe {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        display: none;
    }

    .price-text {
        color: var(--accent-color);
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .cta-button {
        background-color: var(--primary-color);
        color: white;
        font-weight: bold;
        padding: 12px 30px;
        border-radius: 5px;
        border: none;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        display: inline-block;
        margin: 0 auto;
        text-decoration: none;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .cta-button:hover {
        background-color: #cc0000;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        color: white;
        text-decoration: none;
    }

    .wave-divider {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
    }

    .wave-divider svg {
        position: relative;
        display: block;
        width: calc(100% + 1.3px);
        height: 65px;
    }

    .wave-divider .shape-fill {
        fill: #FFFFFF;
    }

    .pricing-section {
        padding: 40px 0;
        text-align: center;
        background-color: white;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .limited-offer {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 20px;
        position: relative;
        display: inline-block;
        color: var(--dark-bg);
    }

    .limited-offer::after {
        content: "";
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: var(--primary-color);
        background-image: url("data:image/svg+xml,%3Csvg width='40' height='12' viewBox='0 0 40 12' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 6c2-2 5-2 7 0s5 2 7 0 5-2 7 0 5 2 7 0 5 2 7 0' fill='%23ff0000' fill-opacity='1' fill-rule='evenodd'/%3E%3C/svg%3E");
        background-size: 40px 12px;
    }
.original-price {
    color: #ffffff;
    font-size: 1.3rem;
    font-weight: bold;
    text-decoration: line-through;
    margin-bottom: 5px;
}

    .current-price {
        color: var(--bs-yellow);
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 30px;
    }

    .product-features {
        padding: 50px 0;
        background-color: var(--light-bg);
        position: relative;
    }

    .product-features::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        z-index: 0;
    }

    .section-title {
        text-align: center;
        font-weight: bold;
        font-size: 2rem;
        margin-bottom: 40px;
        color: var(--dark-bg);
        position: relative;
    }

    .section-title::after {
        content: "";
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: var(--primary-color);
    }

    .product-image {
        max-width: 100%;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        transition: transform 0.3s ease;
    }

    .product-image:hover {
        transform: scale(1.02);
    }

    .feature-card {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .feature-image {
        width: 100%;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .feature-title {
        font-weight: bold;
        font-size: 1.2rem;
        margin-bottom: 10px;
        color: var(--dark-bg);
    }

    .feature-description {
        color: #666;
        font-size: 0.95rem;
    }

    .benefits-section {
        padding: 50px 0;
        background-color: white;
    }

    .benefit-item {
        display: flex;
        margin-bottom: 30px;
        align-items: flex-start;
    }

    .benefit-number {
        width: 40px;
        height: 40px;
        background-color: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: bold;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .benefit-content h3 {
        font-weight: bold;
        font-size: 1.2rem;
        margin-bottom: 8px;
        color: var(--dark-bg);
    }

    .benefit-content p {
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .testimonials-section {
        padding: 50px 0;
        background-color: var(--light-bg);
        position: relative;
    }

    .testimonial-card {
        background-color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        position: relative;
        z-index: 1;
    }

    .testimonial-text {
        font-style: italic;
        color: #555;
        margin-bottom: 15px;
        position: relative;
        padding-left: 25px;
    }

    .testimonial-text::before {
        content: "" ";
 position: absolute;
        left: 0;
        top: -10px;
        font-size: 40px;
        color: var(--primary-color);
        opacity: 0.5;
    }

    .testimonial-author {
        font-weight: bold;
        color: var(--dark-bg);
    }

    .testimonial-location {
        font-size: 0.85rem;
        color: #777;
    }

    .order-now-section {
        padding: 50px 0;
        text-align: center;
        background-color: white;
        box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.05);
    }

    .order-now-title {
        font-weight: bold;
        font-size: 2rem;
        margin-bottom: 20px;
        color: var(--dark-bg);
    }

    .order-now-subtitle {
        color: #666;
        font-size: 1.1rem;
        max-width: 700px;
        margin: 0 auto 30px;
    }

    .order-now-button {
        background-color: var(--primary-color);
        color: white;
        font-weight: bold;
        padding: 15px 40px;
        border-radius: 5px;
        border: none;
        font-size: 1.3rem;
        transition: all 0.3s ease;
        display: inline-block;
        margin: 0 auto;
        text-decoration: none;
        box-shadow: 0 5px 15px rgba(255, 0, 0, 0.3);
    }

    .order-now-button:hover {
        background-color: #cc0000;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(255, 0, 0, 0.4);
        color: white;
        text-decoration: none;
    }

    .guarantee-badge {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #f8f9fa;
        border-radius: 30px;
        font-weight: bold;
        color: #333;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .guarantee-badge i {
        color: var(--secondary-color);
        margin-right: 5px;
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 1.5rem;
        }

        .price-text {
            font-size: 1.2rem;
        }

        .limited-offer {
            font-size: 1.2rem;
        }

        .original-price {
            font-size: 1.1rem;
        }

        .current-price {
            font-size: 1.3rem;
        }

        .ratio::before {
            /* display: block; */
            padding-top: var(--bs-aspect-ratio);
            content: "";
        }

        .section-title {
            font-size: 1.5rem;
        }

        .order-now-title {
            font-size: 1.5rem;
        }

        .order-now-subtitle {
            font-size: 1rem;
        }

        .order-now-button {
            padding: 12px 30px;
            font-size: 1.1rem;
        }
    }
</style>

