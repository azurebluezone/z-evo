<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaign->name }} - Campaign Details</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .campaign-header {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7));
            color: white;
            padding: 60px 20px;
            text-align: center;
            margin-bottom: 30px;
            background-size: cover;
            background-position: center;
            @if($campaign->banner)
                background-image: url('{{ asset($campaign->banner) }}');
            @endif
            border-radius: 10px;
        }
        
        .campaign-title {
            font-size: 2.8rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .banner-title {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 20px;
        }
        
        .content-section {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .main-content {
            flex: 2;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .sidebar {
            flex: 1;
        }
        
        .product-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-image:hover img {
            transform: scale(1.05);
        }
        
        .product-name {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 1.8rem;
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .product-price del {
            color: #95a5a6;
            font-size: 1.2rem;
            margin-left: 10px;
        }
        
        .section-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .description {
            margin-bottom: 30px;
        }
        
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .gallery-item {
            height: 200px;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s, transform 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .content-section {
                flex-direction: column;
            }
            
            .campaign-title {
                font-size: 2rem;
            }
            
            .banner-title {
                font-size: 1.2rem;
            }
            
            .product-name, .product-price {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="campaign-header">
            <h1 class="campaign-title">{{ $campaign->name }}</h1>
            @if($campaign->banner_title)
                <p class="banner-title">{{ $campaign->banner_title }}</p>
            @endif
        </div>
        
        <div class="content-section">
            <div class="main-content">
                <h2 class="section-title">Campaign Details</h2>
                <div class="description">
                    {!! $campaign->description !!}
                </div>
                
                @if($campaign->short_description)
                <h2 class="section-title">Highlights</h2>
                <div class="description">
                    {!! $campaign->short_description !!}
                </div>
                @endif
                
                @if($campaign->image_one || $campaign->image_two || $campaign->image_three)
                <h2 class="section-title">Gallery</h2>
                <div class="gallery">
                    @if($campaign->image_one)
                    <div class="gallery-item">
                        <img src="{{ asset($campaign->image_one) }}" alt="{{ $campaign->name }}">
                    </div>
                    @endif
                    
                    @if($campaign->image_two)
                    <div class="gallery-item">
                        <img src="{{ asset($campaign->image_two) }}" alt="{{ $campaign->name }}">
                    </div>
                    @endif
                    
                    @if($campaign->image_three)
                    <div class="gallery-item">
                        <img src="{{ asset($campaign->image_three) }}" alt="{{ $campaign->name }}">
                    </div>
                    @endif
                    
                    @foreach($campaign->images as $image)
                    <div class="gallery-item">
                        <img src="{{ asset($image->image) }}" alt="{{ $campaign->name }}">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            
            <div class="sidebar">
                @if($campaign->product)
                <div class="product-card">
                    <div class="product-image">
                        @if($campaign->product->image)
                            <img src="{{ asset($campaign->product->image->image) }}" alt="{{ $campaign->product->name }}">
                        @endif
                    </div>
                    <h3 class="product-name">{{ $campaign->product->name }}</h3>
                    <div class="product-price">
                        ৳{{ number_format($campaign->product->new_price, 2) }}
                        @if($campaign->product->old_price)
                            <del>৳{{ number_format($campaign->product->old_price, 2) }}</del>
                        @endif
                    </div>
                    <a href="#" class="btn">Add to Cart</a>
                </div>
                @endif
                
                @if($campaign->video)
                <div class="video-container">
                    <h3 class="section-title">Video</h3>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                        <iframe src="{{ $campaign->video }}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Smooth scroll animation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Lazy loading for images
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = [].slice.call(document.querySelectorAll('img.lazy'));
            
            if ('IntersectionObserver' in window) {
                let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            let lazyImage = entry.target;
                            lazyImage.src = lazyImage.dataset.src;
                            lazyImage.classList.remove('lazy');
                            lazyImageObserver.unobserve(lazyImage);
                        }
                    });
                });

                lazyImages.forEach(function(lazyImage) {
                    lazyImageObserver.observe(lazyImage);
                });
            }
        });
    </script>
</body>
</html>