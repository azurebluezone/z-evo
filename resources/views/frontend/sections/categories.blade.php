{{-- Featured Categories Section --}}
@if(isset($generalsetting) && (int) $generalsetting->home_page_top_categories_section === 1 && isset($featured_categories) && count($featured_categories) > 0)
    <section class="my-60">
        <div class="container">
            <div class="section-header">
                <h5 class="title">{{ $sections['category']['title'] ?? 'Categories' }}</h5>
            </div>

            <div class="category-card-wrapper">
                @foreach($featured_categories as $category)
                    <div class="small-card-item text-center">
                        <a href="{{ route('products.category', $category->slug) }}" class="d-block text-center">
                            @php
                                $raw = (string) ($category->image ?? 'uploads/category/default.png');
                                $isUrl = preg_match('~^https?://~i', $raw) === 1;
                                $path = $isUrl ? $raw : ltrim(preg_replace('~^public/~', '', $raw), '/');
                                $src = !empty($path)
                                    ? ($isUrl ? $path : asset($path))
                                    : asset('uploads/category/default.png');
                            @endphp
                            <img src="{{ $src }}" 
                                 data-src="{{ $src }}" 
                                 class="w-100 lazyload owl-lazy" 
                                 alt="{{ $category->name }}"
                                 width="150" height="150" decoding="async">
                            <span class="title line-limitation-1">{{ $category->name }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
