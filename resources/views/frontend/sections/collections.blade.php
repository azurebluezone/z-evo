@php
    $categoryCollections = (isset($homeCategoryCollections) && count($homeCategoryCollections) > 0) ? $homeCategoryCollections : collect();

    $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
    $fallbackImage = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
    $fallbackRaw = (string) $fallbackImage;
    $fallbackIsUrl = preg_match('~^https?://~i', $fallbackRaw) === 1;
    $fallbackPath = $fallbackIsUrl ? $fallbackRaw : ltrim(preg_replace('~^public/~', '', $fallbackRaw), '/');
    $fallbackImage = !empty($fallbackPath)
        ? ($fallbackIsUrl ? $fallbackPath : $fallbackPath)
        : 'assets/images/empty.png';

    if ($categoryCollections->isEmpty()) {
        $collectionsList = (isset($collections) && count($collections) > 0) ? $collections : collect();

        if ($collectionsList->isEmpty()) {
            $collectionsList = collect([
                [
                    'title' => "Men's Fashion",
                    'products' => collect(range(1, 8))->map(function () {
                        return (object) [
                            'id' => 0,
                            'slug' => '#',
                            'name' => "Coming Soon",
                            'new_price' => '-',
                            'old_price' => null,
                            'short_description' => null,
                            'product_type' => 1,
                            'image' => (object) ['image' => $fallbackImage],
                        ];
                    }),
                ],
            ]);
        }

        $categoryCollections = $collectionsList->map(function ($c) {
            $products = collect($c['products'] ?? collect());
            if ($products->isEmpty()) {
                $products = collect($c['groups'] ?? collect())->flatten(1);
            }
            return [
                'title' => $c['title'] ?? 'Category',
                'slug' => $c['slug'] ?? null,
                'products' => $products,
            ];
        })->values();
    }
@endphp

@foreach($categoryCollections as $collection)
    <div class="my-60">
        <div class="container">
            <div class="section-header left-style d-flex justify-content-between align-items-center">
                <h5 class="title">{{ $collection['title'] ?? ($sections['collections']['title'] ?? '') }}</h5>
                @if(!empty($collection['slug']))
                    <div class="section-nav" style="display:flex;gap:6px;align-items:center;">
                        <a href="{{ route('products.category', $collection['slug']) }}"
                           class="btn btn-sm btn-outline-secondary"
                           style="padding:6px 16px;border:1px solid #ef0000;background:#ef0000;color:#fff;border-radius:4px;font-size:14px;font-weight:500;text-decoration:none;transition:all 0.3s ease;">
                            See More
                        </a>
                    </div>
                @endif
            </div>

            <div class="offer-wrapper">
                <div class="product-wrapper">
                    @include('frontend.sections.partials._home-product-cards', ['products' => ($collection['products'] ?? collect())])
                </div>
            </div>
        </div>
    </div>
@endforeach
