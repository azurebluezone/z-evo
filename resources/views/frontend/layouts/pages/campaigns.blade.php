@extends('frontend.layouts.app')
@section('title', 'Campaigns - Special Offers')
@push('seo')
<meta name="app-url" content="" />
<meta name="robots" content="index, follow" />
<meta name="description" content="Discover our exclusive campaigns and limited-time offers" />
<meta name="keywords" content="campaigns, offers, deals, special offers" />

<!-- Open Graph data -->
<meta property="og:title" content="Campaigns - Special Offers" />
<meta property="og:type" content="website" />
<meta property="og:url" content="" />
<meta property="og:image" content="{{ asset($generalsetting->white_logo ?? 'default-logo.png') }}" />
<meta property="og:description" content="Discover our exclusive campaigns and limited-time offers" />
@endpush

@push('css')
<link rel="stylesheet" href="{{ asset('frontEnd/css/owl.carousel.min.css') }}" />
<link rel="stylesheet" href="{{ asset('frontEnd/css/owl.theme.default.min.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<!-- ===== CAMPAIGN HERO SECTION ===== -->
<section class="campaign-hero" style="background: linear-gradient(135deg, #d4a574 0%, #c19660 100%); min-height: 60vh; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="hero-content text-center" style="position: relative; z-index: 2; padding: 80px 0;">
                    <h1 style="font-size: 3.5rem; font-weight: 900; color: white; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                        Special Campaigns
                    </h1>
                    <p style="font-size: 1.3rem; color: rgba(255,255,255,0.9); margin-bottom: 30px; font-weight: 500;">
                        Discover our exclusive offers and limited-time deals
                    </p>
                    <a href="{{ route('landing.index') }}" style="
                        background: #2c2c2c;
                        color: white;
                        padding: 15px 40px;
                        font-size: 1.2rem;
                        font-weight: 600;
                        text-decoration: none;
                        border-radius: 30px;
                        display: inline-block;
                        transition: all 0.3s ease;
                        border: 2px solid transparent;
                    " onmouseover="this.style.background='white'; this.style.color='#2c2c2c'; this.style.borderColor='#2c2c2c'" onmouseout="this.style.background='#2c2c2c'; this.style.color='white'; this.style.borderColor='transparent'">
                        <i class="fas fa-shopping-cart me-2"></i>View All Campaigns
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CAMPAIGN GRID SECTION ===== -->
@if($campaigns->count() > 0)
<section class="campaign-grid-section" style="background: #f8f9fa; padding: 80px 0;">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title text-center mb-5">
                    <h2 style="font-size: 2.5rem; font-weight: 700; color: #2c2c2c; margin-bottom: 15px;">Featured Campaigns</h2>
                    <p style="font-size: 1.1rem; color: #6c757d; margin-bottom: 0;">Handpicked campaigns for you</p>
                </div>
            </div>
        </div>

        <div class="row">
            @foreach($campaigns as $campaign)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="campaign-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1); transition: all 0.3s ease; height: 100%;">
                    <div class="campaign-image" style="position: relative; overflow: hidden; height: 280px;">
                        @if($campaign->image_one)
                        <img src="{{ asset($campaign->image_one) }}" alt="{{ $campaign->name }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                        @else
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #d4a574 0%, #c19660 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image" style="font-size: 4rem; color: white; opacity: 0.5;"></i>
                        </div>
                        @endif
                        <div class="campaign-badge" style="position: absolute; top: 20px; right: 20px; background: #e74c3c; color: white; padding: 8px 20px; border-radius: 25px; font-size: 0.9rem; font-weight: 600; box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);">
                            Campaign
                        </div>
                    </div>

                    <div class="campaign-content" style="padding: 30px;">
                        <h3 style="font-size: 1.4rem; font-weight: 700; color: #2c2c2c; margin-bottom: 15px; line-height: 1.3; min-height: 60px;">
                            {{ $campaign->name }}
                        </h3>

                        <p style="color: #6c757d; margin-bottom: 25px; line-height: 1.6; min-height: 60px;">
                            {{ Str::limit($campaign->short_description, 120) }}
                        </p>

                        @if($campaign->product)
                        <div class="campaign-price" style="margin-bottom: 25px;">
                            <span style="text-decoration: line-through; color: #999; font-size: 1.1rem;">৳{{ number_format($campaign->product->old_price) }}</span>
                            <span style="color: #e74c3c; font-size: 1.5rem; font-weight: 700; margin-left: 15px;">৳{{ number_format($campaign->product->new_price) }}</span>
                        </div>
                        @endif

                        <a href="{{ route('campaign', $campaign->slug) }}" class="campaign-btn" style="
                            background: linear-gradient(135deg, #d4a574 0%, #c19660 100%);
                            color: white;
                            padding: 15px 30px;
                            border-radius: 30px;
                            text-decoration: none;
                            font-weight: 600;
                            display: inline-block;
                            transition: all 0.3s ease;
                            border: none;
                            width: 100%;
                            text-align: center;
                            box-shadow: 0 5px 15px rgba(212, 165, 116, 0.3);
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(212, 165, 116, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 15px rgba(212, 165, 116, 0.3)'">
                            <i class="fas fa-shopping-cart me-2"></i>View Campaign
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($campaigns->count() > 6)
        <div class="row">
            <div class="col-12 text-center mt-5">
                <a href="{{ route('landing.index') }}" class="view-all-btn" style="
                    background: #2c2c2c;
                    color: white;
                    padding: 18px 50px;
                    border-radius: 35px;
                    text-decoration: none;
                    font-weight: 600;
                    display: inline-block;
                    transition: all 0.3s ease;
                    font-size: 1.1rem;
                    box-shadow: 0 5px 15px rgba(44, 44, 44, 0.3);
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(44, 44, 44, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 15px rgba(44, 44, 44, 0.3)'">
                    <i class="fas fa-th-large me-2"></i>View All Campaigns
                </a>
            </div>
        </div>
        @endif
    </div>
</section>
@else
<!-- No Campaigns Section -->
<section class="no-campaigns-section" style="background: #f8f9fa; padding: 100px 0; text-align: center;">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <i class="fas fa-campaign" style="font-size: 4rem; color: #d4a574; margin-bottom: 30px;"></i>
                <h2 style="font-size: 2.5rem; font-weight: 700; color: #2c2c2c; margin-bottom: 20px;">No Campaigns Available</h2>
                <p style="font-size: 1.2rem; color: #6c757d; margin-bottom: 30px;">Check back later for exciting campaigns and offers!</p>
                <a href="{{ route('landing.index') }}" style="
                    background: #d4a574;
                    color: white;
                    padding: 15px 40px;
                    border-radius: 30px;
                    text-decoration: none;
                    font-weight: 600;
                    display: inline-block;
                    transition: all 0.3s ease;
                " onmouseover="this.style.background='#c19660'" onmouseout="this.style.background='#d4a574'">
                    <i class="fas fa-refresh me-2"></i>Refresh Page
                </a>
            </div>
        </div>
    </div>
</section>
@endif

@endsection

@push('script')
<script>
    // Campaign card hover effects
    document.addEventListener('DOMContentLoaded', function() {
        const campaignCards = document.querySelectorAll('.campaign-card');

        campaignCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
                this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 15px 35px rgba(0,0,0,0.1)';
            });
        });
    });
</script>
@endpush