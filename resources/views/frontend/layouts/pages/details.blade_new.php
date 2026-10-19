@extends('frontEnd.layouts.master')
@section('title', $details->name) 
@push('seo')
<meta name="app-url" content="{{ route('product', $details->slug) }}" />
<meta name="robots" content="index, follow" />
<meta name="description" content="{{ $details->meta_description }}" />
<meta name="keywords" content="{{ $details->slug }}" />

<!-- Twitter Card data -->
<meta name="twitter:card" content="product" />
<meta name="twitter:site" content="{{ $details->name }}" />
<meta name="twitter:title" content="{{ $details->name }}" />
<meta name="twitter:description" content="{{ $details->meta_description }}" />
<meta name="twitter:creator" content="gomobd.com" />
<meta property="og:url" content="{{ route('product', $details->slug) }}" />
<meta name="twitter:image" content="{{ asset($details->image->image) }}" />

<!-- Open Graph data -->
<meta property="og:title" content="{{ $details->name }}" />
<meta property="og:type" content="product" />
<meta property="og:url" content="{{ route('product', $details->slug) }}" />
<meta property="og:image" content="{{ asset($details->image->image) }}" />
<meta property="og:description" content="{{ $details->meta_description }}" />
<meta property="og:site_name" content="{{ $details->name }}" />
@endpush

@push('css')
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
        font-family: 'Poppins', 'Anek Bangla', sans-serif;
        font-size: 13px;
        font-weight: 500;
        color: #000;
        margin-bottom: 0.25em;
        margin-top: 0.35em;
        text-align: center;
        box-sizing: border-box;
        line-height: 1.35;
    }

    .zombie-product-price {
        color: #000;
        font-weight: 500;
        font-size: 13px;
        margin: 0.4rem 0;
        text-align: center;
    }

    .zombie-original-price {
        text-decoration: line-through;
        color: #ff0000;
        margin-right: 10px;
    }

    .zombie-discounted-price {
        color: #000;
        font-weight: 700;
    }

    .zombie-btn-group {
        display: flex;
        margin-top: 0.6rem;
        gap: 0;
        justify-content: center;
    }

    .zombie-btn-buy,
    .zombie-btn-cart {
        flex: 1;
        padding: 8px 6px;
        border: none;
        font-family: 'Poppins', 'Anek Bangla', sans-serif;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        white-space: nowrap;
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

    /* Responsive adjustments - Mobile & Tablet */
    @media (max-width: 768px) {
        .zombie-btn-group {
            display: flex;
            flex-wrap: nowrap;
            gap: 6px;
            justify-content: center;
            align-items: stretch;
            margin-top: 0.5rem;
        }
        .zombie-btn-group form {
            flex: 1;
            min-width: 0;
            display: flex;
        }
        .zombie-btn-group .zombie-btn-buy,
        .zombie-btn-group .zombie-btn-cart {
            flex: 1;
            min-width: 0;
            font-size: 11px;
            padding: 6px 4px;
            min-height: 36px;
        }
        .zombie-product-card {
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        .zombie-box-text {
            text-align: center;
            padding-top: 0.4em;
        }
        .zombie-product-title { font-size: 12px; margin-top: 0.2em; margin-bottom: 0.15em; }
        .zombie-product-price { margin: 0.25rem 0; font-size: 12px; }
    }

    @media (max-width: 549px) {
        .zombie-box-text {
            font-size: 85%;
            padding-left: 8px;
            padding-right: 8px;
            padding-top: 0.35em;
        }

        .zombie-product-img-container {
            height: 220px;
        }

        .zombie-btn-buy,
        .zombie-btn-cart {
            font-size: 10px;
            padding: 5px 3px;
            min-height: 32px;
        }

        .zombie-product-card {
            margin-bottom: 1rem;
        }
        .zombie-product-title { font-size: 11px; }
        .zombie-product-price { font-size: 11px; }
    }
</style>
@endpush

@section('content')
<div class="homeproduct main-details-page">
    <div class="container">
      
        <div class="row">
            <div class="col-sm-12">
                <section class="product-section">
                    <div class="container">
                        <div class="row">
                            <div class="col-sm-6 position-relative">
                                @if($details->old_price)
                                <div class="product-details-discount-badge">
                                    <div class="sale-badge">
                                        <div class="sale-badge-inner">
                                            <!--<div class="sale-badge-box">-->
                                            <!--    <span class="sale-badge-text">-->
                                            <!--        <p> @php $discount=(((($details->old_price)-($details->new_price))*100) / ($details->old_price)) @endphp {{ number_format($discount, 0) }}%</p>-->
                                            <!--        ছাড়-->
                                            <!--    </span>-->
                                            <!--</div>-->
                                        </div>
                                    </div>
                                @endif
                                <div class="details_slider owl-carousel">
                                    @foreach ($details->images as $value)
                                        <div class="dimage_item">
                                            <img src="{{ asset($value->image) }}" class="block__pic" />
                                        </div>
                                    @endforeach
                                </div>
                               
                                <div
                                    class="indicator_thumb @if ($details->images->count() > 4) thumb_slider owl-carousel @endif">
                                    @foreach ($details->images as $key => $image)
                                        <div class="indicator-item" data-id="{{ $key }}">
                                            <img src="{{ asset($image->image) }}" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="details_right">
                                    <div class="breadcrumb">
                                        <ul>
                                            <li><a href="{{ url('/') }}">Home</a></li>
                                            <li><span>/</span></li>
                                            <li><a
                                                    href="{{ url('/category/' . $details->category->slug) }}">{{ $details->category->name }}</a>
                                            </li>
                                            @if ($details->subcategory)
                                                <li><span>/</span></li>
                                                <li><a
                                                        href="#">{{ $details->subcategory ? $details->subcategory->subcategoryName : '' }}</a>
                                                </li>
                                                @endif @if ($details->childcategory)
                                                    <li><span>/</span></li>
                                                    <li><a
                                                            href="#">{{ $details->childcategory->childcategoryName }}</a>
                                                    </li>
                                                @endif
                                        </ul>
                                    </div>

                                    <div class="product">
                                        <div class="product-cart">
                                            <p class="name">{{ $details->name }}</p>
                                            <p class="details-price">
                                                @if ($details->old_price)
                                                    <del>৳{{ $details->old_price }}</del>
                                                @endif ৳{{ $details->new_price }}
                                            </p>
                                            <div class="details-ratting-wrapper">
                                            @php
                                                $averageRating = $reviews->avg('ratting');
                                                $filledStars = floor($averageRating);
                                                $emptyStars = 5 - $filledStars;
                                            @endphp
                                            
                                            @if ($averageRating >= 0 && $averageRating <= 5)
                                                @for ($i = 1; $i <= $filledStars; $i++)
                                                    <i class="fas fa-star"></i>
                                                @endfor
                                            
                                                @if ($averageRating == $filledStars)
                                                    {{-- If averageRating is an integer, don't display half star --}}
                                                @else
                                                    <i class="far fa-star-half-alt"></i>
                                                @endif
                                            
                                                @for ($i = 1; $i <= $emptyStars; $i++)
                                                    <i class="far fa-star"></i>
                                                @endfor
                                            
                                                <span>{{ number_format($averageRating, 2) }}/5</span>
                                            @else
                                                <span>Invalid rating range</span>
                                            @endif
                                            <a class="all-reviews-button" href="#writeReview">See Reviews</a>
                                            </div>
                                            <div class="product-code">
                                                <p><span>প্রোডাক্ট কোড : </span>{{ $details->product_code }}</p>
                                            </div>
                                            
                                            
                                            
                                            <form action="{{ route('cart.store') }}" method="POST" name="formName">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $details->id }}" />
                                                @if($details->images->count() > 0)
                                                    <div class="image-gallery-detailsPage">
                                                                                                            <div class="color-title-detailsPage">
                                                        <p id="selected-color-detailsPage">Color: {{ $details->images->first()->title ?? 'Color' }}</p>
                                                    </div>
                                                    <div class="image-thumbnails-detailsPage">
                                                        @foreach($details->images as $image)
                                                        <div class="image-thumbnail-detailsPage">
                                                            <input type="radio" id="image{{ $image->id }}" name="product_color" value="{{ $image->title }}"
                                                                onclick="selectImage(this, '{{ $image->title }}')" />
                                                            <label for="image{{ $image->id }}">
                                                                <img src="{{ asset($image->image) }}" alt="Product Image" class="img-thumbnail-detailsPage" />
                                                                <span class="selected-icon-detailsPage">&#10004;</span> <!-- Checkmark icon -->
                                                            </label>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    </div>
                                                @endif
                                                
                                                @if ($productcolors->count() > 0)
                                                    <div class="pro-color" style="width: 100%;">
                                                        <div class="color_inner">
                                                            <p>Color -</p>
                                                            <div class="size-container">
                                                                <div class="selector">
                                                                    @foreach ($productcolors as $procolor)
                                                                        <div class="selector-item">
                                                                            <input type="radio"
                                                                                id="fc-option{{ $procolor->id }}"
                                                                                value="{{ $procolor->color ? $procolor->color->colorName : '' }}"
                                                                                name="product_color"
                                                                                class="selector-item_radio emptyalert"
                                                                                required />
                                                                            <label for="fc-option{{ $procolor->id }}"
                                                                                style="background-color: {{ $procolor->color ? $procolor->color->color : '' }}"
                                                                                class="selector-item_label">
                                                                                <span>
                                                                                    <img src="{{ asset('public/frontEnd/images') }}/check-icon.svg"
                                                                                        alt="Checked Icon" />
                                                                                </span>
                                                                            </label>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif @if ($productsizes->count() > 0)
                                                        <div class="pro-size" style="width: 100%;">
                                                            <div class="size_inner">
                                                                <p>Size - <span class="attibute-name"></span></p>
                                                                <div class="size-container">
                                                                    <div class="selector">
                                                                        @foreach ($productsizes as $prosize)
                                                                            <div class="selector-item">
                                                                                <input type="radio"
                                                                                    id="f-option{{ $prosize->id }}"
                                                                                    value="{{ $prosize->size ? $prosize->size->sizeName : '' }}"
                                                                                    name="product_size"
                                                                                    class="selector-item_radio emptyalert"
                                                                                    required />
                                                                                <label
                                                                                    for="f-option{{ $prosize->id }}"
                                                                                    class="selector-item_label">{{ $prosize->size ? $prosize->size->sizeName : '' }}</label>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endif
                                                        @if ($details->pro_unit)
                                                            <div class="pro_unig">
                                                                <label>Unit: {{ $details->pro_unit }}</label>
                                                                <input type="hidden" name="pro_unit"
                                                                    value="{{ $details->pro_unit }}" />
                                                            </div>
                                                        @endif
                                                        <div class="pro_brand">
                                                            <p>Brand :
                                                                {{ $details->brand ? $details->brand->name : 'N/A' }}
                                                            </p>
                                                        </div>

                                                        <div class="row">
                                                            <div class="qty-cart col-sm-12">
                                                                <div class="quantity">
                                                                    <span class="minus">-</span>
                                                                    <input type="text" name="qty"
                                                                        value="1" />
                                                                    <span class="plus">+</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex single_product col-sm-12">
                                                                <!--<input type="submit" class="btn px-4 add_cart_btn"-->
                                                                <!--    onclick="return sendSuccess();" name="add_cart"-->
                                                                <!--    value="কার্টে যোগ করুন " />-->
                                                            <form action="{{ route('cart.store') }}" method="POST" name="formName" class='add_cart_btn_home_main'>
                                                                    @csrf
                                                                    <input type="hidden" name="id" value="{{ $details->id }}" />
                                                                    <input type="hidden" name="qty" value="1" />
                                                                 <input type="submit" class="add_cart_btn_home" onclick="add_to_cart_details(this,event)" name="add_cart" value="কার্টে যোগ করুন" />
                                                                </form>
<div class="pro_btn col-lg-6 col-md-6 col-sm-12 col-12" style="padding-left:0;">
    <!-- Button Design -->
    <button style="width: 100%; max-width: 100%; background-color: #f26d2b; color: white; padding: 10px; border: 2px solid #1ca3ac; cursor: pointer; margin: 5px; border-radius: 5px;" onclick="document.getElementById('buyNowForm').submit();">
        অর্ডার করুন
    </button>    
    <!-- Hidden Form to Handle Submit -->
    <form id="buyNowForm" action="{{ route('cart.store.buy') }}" method="POST" name="formName" style="display: none;">
        @csrf
        <input type="hidden" name="id" value="{{ $details->id }}" />
        <input type="hidden" name="qty" value="1" />
    </form>
</div>

                                                                
                                                             <!--<form action="{{ route('cart.store.buy') }}" method="POST" name="formName" class='order_now_btn order_now_btn_m'>-->
                                                             <!--       @csrf-->
                                                             <!--       <input type="hidden" name="id" value="{{ $details->id }}" />-->
                                                             <!--       <input type="hidden" name="qty" value="1" />-->
                                                             <!--    <input type="submit" style="background-color: #f26d2b; color:white;" class=" " onclick="" name="add_cart" value="অর্ডার করুন" />-->
                                                             <!--   </form>-->
                                                                <!--<input type="submit"-->
                                                                <!--    class="btn px-4 order_now_btn order_now_btn_m"-->
                                                                <!--    onclick="" name="order_now"-->
                                                                <!--    value="অর্ডার করুন" />-->
                                                               

                                                               
                                                               
                                                            </div>
                                                        </div>
                                                        
                                                        
                                                    
                                                        
                                                        


<div class="mt-md-2 mt-2">
    <h4 class="font-weight-bold">
        <!-- WhatsApp Button -->
        <a class="btn btn-success w-100 call_now_btn" id="order_by_whatsapp" 
            onclick="sendOrderByWhatsApp()">
            <i class="fa-brands fa-whatsapp"></i> Order by WhatsApp
        </a>
    </h4>
</div>

<script type="text/javascript">
    function sendOrderByWhatsApp() {
        // Get selected color and size (if available)
        let selectedColor = document.querySelector('input[name="product_color"]:checked');
        let selectedSize = document.querySelector('input[name="product_size"]:checked');

        // Prepare WhatsApp message with basic details
        let message = `*Order Details:*\n` +
                      `Product Name: {{ $details->name }}\n` +
                      `Price: ৳{{ $details->new_price ? $details->new_price : 'N/A' }}\n` +
                      `Brand: {{ $details->brand ? $details->brand->name : 'N/A' }}\n` +
                      `Category: {{ $details->category ? $details->category->name : 'N/A' }}`;

        // If color is selected, add to message
        if (selectedColor) {
            message += `\nColor: ${selectedColor.value}`;
        }

        // If size is selected, add to message
        if (selectedSize) {
            message += `\nSize: ${selectedSize.value}`;
        }

        // WhatsApp link with encoded message
        let whatsappURL = `https://wa.me/{{ $contact->whatsapp }}?text=${encodeURIComponent(message)}`;

        // Redirect to WhatsApp
        window.open(whatsappURL, '_blank');
    }
</script>





                                                        <div class="mt-md-2 mt-2">
                                                            <div class="del_charge_area">
                                                                <div class="alert alert-info text-xs">
                                                                    <div class="flext_area">
                                                                        <i class="fa-solid fa-cubes"></i>
                                                                        <div>

                                                                            @foreach ($shippingcharge as $key => $value)
                                                                                <span>{{ $value->name }} <br /></span>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="description-nav-wrapper">
    <div class="container">
        <div class="row">

            <div class="col-sm-12">
                <div class="description-nav">
                    <ul class="desc-nav-ul">
                        {{-- <li class="active">
                            <a href="#specification" target="_self">Specification</a>
                        </li> --}}
                        <li>
                            <a href="#description" target="_self">Description</a>
                        </li>
                        {{-- <li>
                            <a href="#question" target="_self">Questions (0)</a>
                        </li> --}}
                        <li>
                            <a href="#writeReview" target="_self">Reviews ({{ $reviews->count() }}) </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="pro_details_area">
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <div class="description tab-content details-action-box" id="description">
                    <h2>বিস্তারিত</h2>
                    <p>{!! $details->description !!}</p>
                </div>
                <div class="tab-content details-action-box" id="writeReview">
                    <div class="container">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="section-head">
                                    <div class="title">
                                        <h2>Reviews ({{ $reviews->count() }})</h2>
                                        <p>Get specific details about this product from customers who own it.</p>
                                    </div>
                                    <div class="action">
                                        <div>
                                            <button type="button" class="details-action-btn question-btn btn-overlay"
                                                data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                Write a review
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @if ($reviews->count() > 0)
                                    <div class="customer-review">
                                        <div class="row">
                                            @foreach ($reviews as $key => $review)
                                                <div class="col-sm-12 col-12">
                                                    <div class="review-card">
                                                        <p class="reviewer_name"><i data-feather="message-square"></i>
                                                            {{ $review->name }}</p>
                                                        <p class="review_data">{{ $review->created_at->format('d-m-Y') }}</p>
                                                        <p class="review_star">{!! str_repeat('<i class="fa-solid fa-star"></i>', $review->ratting) !!}</p>
                                                        <p class="review_content">{{ $review->review }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="empty-content">
                                        <i class="fa fa-clipboard-list"></i>
                                        <p class="empty-text">This product has no reviews yet. Be the first one to write a review.</p>
                                    </div>
                                @endif
                                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                                    aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="exampleModalLabel">Your review</h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="insert-review">
                                                    @if (Auth::guard('customer')->user())
                                                        <form action="{{ route('customer.review') }}" id="review-form"
                                                            method="POST">
                                                            @csrf
                                                            <input type="hidden" name="product_id" value="{{ $details->id }}">
                                                            <div class="fz-12 mb-2">
                                                                <div class="rating">
                                                                    <label title="Excelent">
                                                                        ☆
                                                                        <input required type="radio" name="ratting"
                                                                            value="5" />
                                                                    </label>
                                                                    <label title="Best">
                                                                        ☆
                                                                        <input required type="radio" name="ratting"
                                                                            value="4" />
                                                                    </label>
                                                                    <label title="Better">
                                                                        ☆
                                                                        <input required type="radio" name="ratting"
                                                                            value="3" />
                                                                    </label>
                                                                    <label title="Very Good">
                                                                        ☆
                                                                        <input required type="radio" name="ratting"
                                                                            value="2" />
                                                                    </label>
                                                                    <label title="Good">
                                                                        ☆
                                                                        <input required type="radio" name="ratting"
                                                                            value="1" />
                                                                    </label>
                                                                </div>
                                                            </div>
                
                                                            <div class="form-group">
                                                                <label for="message-text" class="col-form-label">Message:</label>
                                                                <textarea required class="form-control radius-lg" name="review" id="message-text"></textarea>
                                                                <span id="validation-message" style="color: red;"></span>
                                                            </div>
                                                            <div class="form-group">
                                                                <button class="details-review-button" type="submit">Submit
                                                                    Review</button>
                                                            </div>
                
                                                        </form>
                                                    @else
                                                        <a class="customer-login-redirect" href="{{ route('customer.login') }}">Login
                                                            to Post
                                                            Your Review</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="pro_vide">
                    <h2>ভিডিও</h2>
                    <iframe width="100%" height="315"
                        src="https://www.youtube.com/embed/{{ $details->pro_video }}" title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="related-product-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="col-12">
                    <h3 class="section-title section-title-bold-center"><b></b><span class="section-title-main" style="font-size:140%;">Related Products</span><b></b></h3>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="product-inner owl-carousel related_slider">
                    @foreach ($products as $key => $value)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="zombie-product-card">
                                <a href="{{ route('product', $value->slug) }}" class="zombie-product-link">
                                    <div class="zombie-product-img-container">
                                        @if($value->image && $value->image->image)
                                        @php $relProdImgPath = preg_replace('#^public/#', '', $value->image->image); @endphp
                                        <img src="{{ asset($relProdImgPath) }}" alt="{{ $value->name }}" class="zombie-product-img" loading="lazy">
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
            </div>
        </div>
    </div>
</section>

@endsection @push('script')
<script src="{{ asset('public/frontEnd/js/owl.carousel.min.js') }}"></script>

<script src="{{ asset('public/frontEnd/js/zoomsl.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $(".details_slider").owlCarousel({
            margin: 15,
            items: 1,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
        });
        $(".indicator-item").on("click", function() {
            var slideIndex = $(this).data("id");
            $(".details_slider").trigger("to.owl.carousel", slideIndex);
        });
    });
</script>
<!--Data Layer Start-->
<script type="text/javascript">
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({
        ecommerce: null
    });
    dataLayer.push({
        event: "view_item",
        ecommerce: {
            items: [{
                item_name: "{{ $details->name }}",
                item_id: "{{ $details->id }}",
                price: "{{ $details->new_price }}",
                item_brand: "{{ $details->brand?$details->brand->name:'' }}",
                item_category: "{{ $details->category->name }}",
                item_variant: "{{ $details->pro_unit }}",
                currency: "BDT",
                quantity: {{ $details->stock ?? 0 }}
            }],
            impression: [
                @foreach ($products as $value)
                    {
                        item_name: "{{ $value->name }}",
                        item_id: "{{ $value->id }}",
                        price: "{{ $value->new_price }}",
                        item_brand: "{{ $details->brand?$details->brand->name:'' }}",
                        item_category: "{{ $value->category ? $value->category->name : '' }}",
                        item_variant: "{{ $value->pro_unit }}",
                        currency: "BDT",
                        quantity: {{ $value->stock ?? 0 }}
                    },
                @endforeach
            ]
        }
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
                            item_id: "{{$details->id}}",
                            item_name: "{{$details->name}}",
                            price: "{{$details->new_price}}",
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
                            item_id: "{{$details->id}}",
                            item_name: "{{$details->name}}",
                            price: "{{$details->new_price}}",
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
        $('#order_now').click(function(event) {
            // Prevent the form from submitting immediately
            event.preventDefault();

            // Fire gtag event for tracking
            gtag("event", "order_now_btn", {
                currency: "BDT",
                value: "1.5",
                items: [
                    @foreach (Cart::instance('shopping')->content() as $cartInfo)
                        {
                            item_id: "{{$details->id}}",
                            item_name: "{{$details->name}}",
                            price: "{{$details->new_price}}",
                            currency: "BDT",
                            quantity: {{ $cartInfo->qty ?? 0 }}
                        },
                    @endforeach
                ]
            });

            // Redirect to the checkout page after the event is fired
            setTimeout(function() {
                window.location.href = "{{ route('customer.checkout') }}"; // Replace with your checkout route
            }, 1000); // Delay to ensure the event is fired before redirecting
        });
    });
</script>


<!-- Data Layer End-->
<script>
    $(document).ready(function() {
        $(".related_slider").owlCarousel({
            margin: 10,
            items: 6,
            loop: true,
            dots: true,
            nav: true,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 6,
                    nav: true,
                    loop: true,
                },
            },
        });
        // $('.owl-nav').remove();
    });
</script>
<script>
    $(document).ready(function() {
        $(".minus").click(function() {
            var $input = $(this).parent().find("input");
            var count = parseInt($input.val()) - 1;
            count = count < 1 ? 1 : count;
            $input.val(count);
            $input.change();
            return false;
        });
        $(".plus").click(function() {
            var $input = $(this).parent().find("input");
            $input.val(parseInt($input.val()) + 1);
            $input.change();
            return false;
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
</script>
<script>
    $(document).ready(function() {
        $(".rating label").click(function() {
            $(".rating label").removeClass("active");
            $(this).addClass("active");
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".thumb_slider").owlCarousel({
            margin: 15,
            items: 4,
            loop: true,
            dots: false,
            nav: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
        });
    });
</script>

<script type="text/javascript">
    $(".block__pic").imagezoomsl({
        zoomrange: [3, 3]
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

<script type="text/javascript">
    $(".block__pic").imagezoomsl({
        zoomrange: [3, 3]
    });
   function add_to_cart_details(button, event) {
    event.preventDefault();
    var form = $(button).closest('form');
    var url = form.attr('action');
    var submit_button = $(button);

    // Disable button and show loading message
    submit_button.val('Please Wait...');
    submit_button.prop('disabled', true);

    $.ajax({
        url: url,
        method: form.attr('method'),
        data: form.serialize(),
        success: function(response) {
            if (response.success === true) {
                toastr.success(response.message);
                var cart_count = response.cart_count;
                $('#cart_count').text(cart_count);
            } else if (response.success === false) {
                toastr.error(response.message);
            }
        },
        error: function(response) {
            // Log the full response to debug what's wrong
            console.error('Error response:', response);
            toastr.error(response.responseText || 'Something went wrong!'); // Display a more meaningful error
        },
        complete: function() {
            // Re-enable the button and reset its text
            submit_button.val('কার্টে যোগ করুন ');
            submit_button.prop('disabled', false); // Fix typo 'desabled' -> 'disabled'
        }
    });
}

</script>

<script>
    function selectImage(element, colorTitle) {
        document.getElementById('selected-color-detailsPage').innerText = 'Color: ' + colorTitle;
    }
</script>
<script type="text/javascript">
    // Function to handle image change on thumbnail click
    function changeImageOnThumbnailClick(thumbnail, largeImageClass) {
        var newImageSrc = thumbnail.find('img').attr('src'); // Get the src of clicked thumbnail image
        $('.' + largeImageClass).find('img').attr('src', newImageSrc); // Change the src of the large image
    }

    $(document).ready(function() {
        // On thumbnail click, change the large image in dimage_item
        $('.image-thumbnail-detailsPage').click(function() {
            var thumbnail = $(this); // Get the clicked thumbnail element
            var largeImageClass = 'dimage_item'; // Class for the large image container
            
            // Change the large image
            changeImageOnThumbnailClick(thumbnail, largeImageClass);
        });
    });
</script>
<script type="text/javascript">
    function sendOrderByWhatsApp() {
        // Get selected color and size (if available)
        let selectedColor = document.querySelector('input[name="product_color"]:checked');
        let selectedSize = document.querySelector('input[name="product_size"]:checked');

        // Prepare WhatsApp message with basic details
        let message = `*Order Details:*\n` +
                      `Product Name: {{ $details->name }}\n` +
                      `Price: ৳{{ $details->new_price ? $details->new_price : 'N/A' }}\n` +
                      `Brand: {{ $details->brand ? $details->brand->name : 'N/A' }}\n` +
                      `Category: {{ $details->category ? $details->category->name : 'N/A' }}`;

        // If color is selected, add to message
        if (selectedColor) {
            message += `\nColor: ${selectedColor.value}`;
        }

        // If size is selected, add to message
        if (selectedSize) {
            message += `\nSize: ${selectedSize.value}`;
        }

        // WhatsApp link with encoded message
        let whatsappURL = `https://wa.me/{{ $contact->whatsapp }}?text=${encodeURIComponent(message)}`;

        // Redirect to WhatsApp
        window.open(whatsappURL, '_blank');
    }
</script>

@endpush
