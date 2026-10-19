@extends('frontend.layouts.master')

@push('seo')
<meta name="app-url" content="{{ url('/') }}" />
<meta name="robots" content="index, follow" />
<meta name="description" content="" />
<title> {{$generalsetting->name}}</title>

<!-- Open Graph data -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<meta property="og:title" content="{{$generalsetting->name}}" />
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ asset($generalsetting->white_logo ?? 'default-logo.png') }}" />
<meta property="og:description" content="Check out our exclusive campaigns and save big on trending products." />
@endpush

@push('css')
<link rel="stylesheet" href="{{ asset('frontEnd/css/owl.carousel.min.css') }}" />
<link rel="stylesheet" href="{{ asset('frontEnd/css/owl.theme.default.min.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" rel="stylesheet" />
@endpush

@section('content')
<style>
    .sp-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .sp-header-section {
        text-align: center;
        margin: 40px 0;
    }

    .sp-header-section h1 {
        font-size: 2.5rem;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .sp-header-section p {
        color: #7f8c8d;
        font-size: 1.1rem;
    }

    .sp-campaigns-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
        margin-top: 30px;
        margin-bottom: 48px;
    }

    .sp-campaign-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .sp-campaign-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .sp-campaign-image {
        height: 200px;
        overflow: hidden;
    }

    .sp-campaign-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .sp-campaign-card:hover .sp-campaign-image img {
        transform: scale(1.05);
    }

    .sp-campaign-info {
        padding: 20px;
    }

    .sp-campaign-name {
        font-size: 1.2rem;
        margin-bottom: 10px;
        color: #2c3e50;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sp-product-price {
        font-size: 1.3rem;
        color: #e74c3c;
        font-weight: bold;
        text-align: center;
    }

    .sp-product-price del {
        color: #95a5a6;
        font-size: 0.9rem;
        margin-left: 8px;
    }

    .sp-view-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 15px auto 0;
        padding: 8px 20px;
        background: #005540;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        transition: background 0.3s;
        border: none;
        cursor: pointer;
    }

    .sp-view-btn i {
        margin-right: 8px;
    }

    .sp-view-btn:hover {
        background: #004530;
    }

    .sp-back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: #005540;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        z-index: 99;
        transition: all 0.3s;
    }

    .sp-back-to-top:hover {
        background: #004530;
        transform: translateY(-3px);
    }

    /* Tablet Styles */
    @media (max-width: 992px) {
        .sp-campaigns-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    /* Mobile Styles */
    @media (max-width: 768px) {
        .sp-header-section h1 {
            font-size: 2rem;
        }

        .sp-campaigns-grid {
            gap: 15px;
        }
    }

    /* Small Mobile Styles */
    @media (max-width: 480px) {
        .sp-campaigns-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }

        .sp-campaign-image {
            height: 150px;
        }

        .sp-campaign-name {
            font-size: 1rem;
            height: auto;
        }

        .sp-product-price {
            font-size: 1.1rem;
        }

        .sp-view-btn {
            padding: 6px 12px;
            font-size: 12px;
        }
    }
</style>
<div class="sp-container">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 15px;">
        <div style="text-align: center; margin: 40px 0; padding: 0;">
            <h1 style="
                font-size: 2.2rem;
                color: #2c3e50;
                margin: 0;
                padding: 15px 0;
                font-weight: 600;
                letter-spacing: 0.5px;
                position: relative;
                display: inline-block;
            ">
                Our Special Campaigns
                <span style="
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                    height: 2px;
                    background: #005540;
                    border-radius: 2px;
                "></span>
            </h1>
            <p style="color: #7f8c8d; font-size: 1.1rem; margin: 20px 0;">
                Discover our exclusive campaigns and limited-time offers
            </p>
            <div style="margin-top: 20px;">
                <a href="{{ route('home') }}" style="
                    background: #6c757d;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 25px;
                    text-decoration: none;
                    margin-right: 15px;
                    display: inline-block;
                    transition: all 0.3s ease;
                " onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
                <a href="{{ route('campaigns') }}" style="
                    background: #005540;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 25px;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                " onmouseover="this.style.background='#004d3a'" onmouseout="this.style.background='#005540'">
                    <i class="fas fa-shopping-cart me-2"></i>View All Campaigns
                </a>
            </div>
        </div>
    </div>

    <div class="sp-campaigns-grid">
        @foreach($campaigns as $campaign)
        @if($campaign->product)
        <div class="sp-campaign-card">
            <div class="sp-campaign-image">
                @if($campaign->image_one)
                <img src="{{ asset($campaign->image_one) }}" alt="{{ $campaign->name }}">
                @elseif($campaign->product->image)
                <img src="{{ asset($campaign->product->image->image) }}" alt="{{ $campaign->product->name }}">
                @else
                <img src="{{ asset('default.jpg') }}" alt="No Image">
                @endif
            </div>
            <div class="sp-campaign-info">
                <h3 class="sp-campaign-name">{{ $campaign->product->name }}</h3>
                <div class="sp-product-price">
                    ৳{{ number_format($campaign->product->new_price, 2) }}
                    @if($campaign->product->old_price)
                    <del>৳{{ number_format($campaign->product->old_price, 2) }}</del>
                    @endif
                </div>
                <button class="sp-view-btn" onclick="window.location.href='{{ url('campaign/' . $campaign->slug) }}'">
                    <i class="fas fa-eye"></i> View Details
                </button>
            </div>
        </div>
        @endif
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Store the current campaign page URL in localStorage
        localStorage.setItem('lastCampaignPage', window.location.href);

        // Check if we should redirect back to campaign page when logo is clicked
        document.querySelector('.logo a').addEventListener('click', function(e) {
            const lastCampaignPage = localStorage.getItem('lastCampaignPage');
            if (lastCampaignPage && lastCampaignPage !== window.location.href) {
                e.preventDefault();
                window.location.href = lastCampaignPage;
            }
        });

        // Your existing animation and back-to-top code
        const cards = document.querySelectorAll('.sp-campaign-card');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1
        });

        cards.forEach(card => {
            card.style.opacity = 0;
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });

        const backToTop = document.querySelector('.sp-back-to-top');
        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        });

        backToTop.style.display = 'none';
    });
</script>
@endsection