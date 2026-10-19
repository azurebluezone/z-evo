@extends('frontend.layouts.master')

@section('title', $pageTitle ?? 'Search')
@section('meta_description', $metaDescription ?? 'Search products')

@section('content')
    <div class="container" style="padding: 40px 15px; min-height: 55vh;">
        <h1 style="font-size: 26px; font-weight: 700; margin-bottom: 6px;">{{ $pageTitle ?? 'Search' }}</h1>

        @if(!empty($term))
            <p style="color: #777; margin-bottom: 24px;">
                &ldquo;{{ $term }}&rdquo; এর জন্য {{ $products->total() ?? 0 }} টি পণ্য পাওয়া গেছে
            </p>
        @else
            <p style="color: #777; margin-bottom: 24px;">খুঁজতে চাওয়া শব্দ লিখুন।</p>
        @endif

        @if(isset($products) && $products->count())
            <div class="row" style="row-gap: 20px;">
                @foreach($products as $product)
                    @php
                        $rawImage = (string) ($product->image?->image ?? '');
                        $imageIsUrl = preg_match('~^https?://~i', $rawImage) === 1;
                        $imagePath = $imageIsUrl
                            ? $rawImage
                            : ltrim(preg_replace('~^public/~', '', $rawImage), '/');
                        if (!$imageIsUrl && $imagePath !== ''
                            && !str_contains($imagePath, '/') && !str_contains($imagePath, '\\')) {
                            $imagePath = 'uploads/product/' . $imagePath;
                        }
                        $imageSrc = $imagePath !== ''
                            ? ($imageIsUrl ? $imagePath : asset($imagePath))
                            : asset('uploads/default/placeholder.png');
                        $hasDiscount = !empty($product->old_price)
                            && (float) $product->old_price > (float) ($product->new_price ?? 0);
                    @endphp

                    <div class="col-6 col-md-4 col-lg-3">
                        <div style="border: 1px solid #eee; border-radius: 8px; overflow: hidden; height: 100%; display: flex; flex-direction: column;">
                            <a href="{{ route('product', $product->slug) }}" style="display: block; aspect-ratio: 1/1; background: #fafafa;">
                                <img src="{{ $imageSrc }}" alt="{{ $product->name }}"
                                     loading="lazy" width="400" height="400"
                                     style="width: 100%; height: 100%; object-fit: contain;">
                            </a>

                            <div style="padding: 10px 12px 14px; display: flex; flex-direction: column; flex: 1;">
                                <h3 style="font-size: 14px; font-weight: 500; margin: 0 0 6px; line-height: 1.4;">
                                    <a href="{{ route('product', $product->slug) }}"
                                       style="color: #333; text-decoration: none;">{{ $product->name }}</a>
                                </h3>

                                <div style="margin-top: auto;">
                                    @if($hasDiscount)
                                        <span style="font-size: 16px; font-weight: 700; color: #e53935;">
                                            {{ number_format((float) $product->new_price) }}
                                        </span>
                                        <del style="font-size: 13px; color: #999; margin-left: 6px;">
                                            {{ number_format((float) $product->old_price) }}
                                        </del>
                                    @else
                                        <span style="font-size: 16px; font-weight: 700; color: #333;">
                                            {{ number_format((float) ($product->new_price ?? 0)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(method_exists($products, 'links'))
                <div style="margin-top: 34px;">
                    {{ $products->links() }}
                </div>
            @endif
        @else
            <div style="background: #f9f9f9; border: 1px dashed #ddd; border-radius: 8px; padding: 48px 20px; text-align: center;">
                <p style="color: #888; margin: 0;">এই খুঁজজে কিছু পাওয়া যায়নি। অন্য শব্দ দিয়ে আবার চেষ্টা করুন।</p>
            </div>
        @endif
    </div>
@endsection
