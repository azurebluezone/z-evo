@extends('frontend.layouts.app')
@section('title',$keyword)
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
        margin-bottom: 20px;
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
                        <strong>{{ $keyword }}</strong>
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
            <div class="col-sm-12">
                <div class="category-product main_product_inner">
                    @foreach($products as $key=>$value)
                    <div class="product_item wist_item  wow fadeInDown" data-wow-duration="1.5s" data-wow-delay="0.{{$key}}s">
                        <div class="product_item_inner">
                            @if($value->old_price)
                            <div class="sale-badge">
                                <div class="sale-badge-inner">
                                    <div class="sale-badge-box">
                                        <span class="sale-badge-text">
                                            <p> @php $discount=(((($value->old_price)-($value->new_price))*100) / ($value->old_price)) @endphp {{number_format($discount,0)}}%</p>
                                            ছাড়
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="pro_img">
                                <a href="{{ route('product',$value->slug) }}">
                                    <img src="{{ asset($value->image ? $value->image->image : '') }}" alt="{{$value->name}}" />
                                </a>

                            </div>
                            <div class="pro_des">
                                <div class="pro_name">
                                    <a href="{{ route('product',$value->slug) }}">{{Str::limit($value->name,80)}}</a>
                                </div>
                                <div class="pro_price">
                                    <p>
                                        <del>৳ {{ $value->old_price}}</del>
                                        ৳ {{ $value->new_price}} @if($value->old_price) @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if(! $value->prosizes->isEmpty() || ! $value->procolors->isEmpty())
                        <div class="pro_btn">
                            <div class="cart_btn">
                                <a href="{{ route('product',$value->slug) }}" class="addcartbutton">কার্ট</a>
                            </div>
                            <div class="cart_btn order_button">
                                <a href="{{ route('product',$value->slug) }}" class="addcartbutton">অর্ডার</a>
                            </div>

                        </div>
                        @else

                        <div class="pro_btn">
                            <div class="cart_btn">
                                <a data-id="{{$value->id}}" class="addcartbutton">কার্ট</a>
                            </div>
                            <form action="{{route('cart.store')}}" method="POST">
                                @csrf
                                <input type="hidden" name="id" value="{{$value->id}}" />
                                <input type="hidden" name="qty" value="1" />
                                <button type="submit">অর্ডার</button>
                            </form>
                        </div>
                        @endif

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


@endsection
@push('script')
<script>
    $(".sort").change(function() {
        $('#loading').show();
        $(".sort-form").submit();
    })
</script>
@endpush