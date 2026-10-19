{{--
    FM PAGE product-details
    =========================================================================
    Modern product page, mapped from frontend.pages.product-details by
    ThemeViewFinder when the admin "Front-end Template" value is "modern".
    The classic file stays untouched, so switching back needs no file move.

    Route:  GET product/{id}  ->  FrontendController@details
    Design: hi/product-details.html, <main> area (breadcrumbs, 12-column
            detail, tab strip, related rail, newsletter tail).

    Data notes (documented here so nobody hunts for them later):
      - $productReviews is queried in the controller: published rows only,
        newest first. Product::reviews() only selects ids (counts).
      - No product carries short_description, pro_unit or a video, so the
        design's narrative/price-per-unit/nutrition content is replaced by
        real data: a description teaser, conditional video/document tabs
        and an empty state for reviews.
      - No pill is pre-selected: the cart must keep falling back to the
        DEFAULT variant until the shopper picks one, exactly like classic.
      - Variants get one pill group per attribute (first group uses the
        design's weight grid, further groups the inline "thickness" row).
        Pills carry a price only when a single attribute exists, otherwise
        the price comes from products/check-stock after a complete pick.

    Load order: fm-product.css is pushed after the shared sheets (see the
    CSS order block in fm/layouts.blade.php); the page script is pushed to
    the shared 'script' stack.
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@php
    /* --------------------------------------------------------------------
     * Media: main photo plus a de-duplicated thumbnail strip. The strip
     * only renders for products with more than one photo; its column
     * count follows the number of photos (2/3/4 per row).
     * ---------------------------------------------------------------- */
    $fmMainImage = \App\Support\MediaPath::product($product);
    $fmGallery = [];

    /*
     * Non-image files are skipped.
     *
     * The admin Gallery Images input is `image[]` with no accept filter, and
     * the controller's own validation only covers `image.*` - so an .mp4 (or a
     * .pdf, or .zip) can end up in the productimages table. FrontendController
     * already separates video out for its own player, but it stays in
     * $product_images, so this loop used to wrap a video URL in an <img>
     * thumbnail: a browser-level broken image on the product page, with no
     * server-side hint that anything was wrong.
     *
     * Extension testing is all that can be done here - the column is a plain
     * string with no mime type recorded.
     */
    $fmImageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'bmp', 'svg'];

    foreach ($product_images ?? [] as $fmImgRaw) {
        $fmImgUrl = \App\Support\MediaPath::url($fmImgRaw, 'product');

        // Skip anything that is not an image, with or without a query string.
        $fmPath = (string) parse_url($fmImgUrl, PHP_URL_PATH);
        $fmExt  = strtolower((string) pathinfo($fmPath, PATHINFO_EXTENSION));

        if (!in_array($fmExt, $fmImageExtensions, true)) {
            continue;
        }

        if ($fmImgUrl !== $fmMainImage && !in_array($fmImgUrl, $fmGallery, true)) {
            $fmGallery[] = $fmImgUrl;
        }
    }
    $fmThumbs = array_merge([$fmMainImage], $fmGallery);
    $fmThumbGrid = match (true) {
        count($fmThumbs) >= 4 => 'grid grid-cols-4',
        count($fmThumbs) === 3 => 'grid grid-cols-3',
        default => 'grid grid-cols-2',
    };

    /* --------------------------------------------------------------------
     * Price, discount and the save badge. Whole floats print cleanly
     * (100.0 -> "100") and match what products/check-stock returns.
     * ---------------------------------------------------------------- */
    $fmNewPrice = (float) ($product->new_price ?? 0);
    $fmOldPrice = (float) ($product->old_price ?? 0);
    $fmDetailsOldPrice = $fmOldPrice > 0 && $fmOldPrice > $fmNewPrice;
    $fmSaveAmount = $fmDetailsOldPrice ? $fmOldPrice - $fmNewPrice : 0;
    $fmSavePct = $fmDetailsOldPrice ? (int) round($fmSaveAmount * 100 / $fmOldPrice) : 0;
    $fmBadge = $fmDetailsOldPrice
        ? ['text' => 'Save ' . $fmSavePct . '%', 'pct' => $fmSavePct]
        : null;

    /* --------------------------------------------------------------------
     * Stock. With variants the product level number is not per-size
     * truth, so the pill only states availability until a pick fetches
     * the real count from products/check-stock.
     * ---------------------------------------------------------------- */
    $fmStockQty = (int) $product->displayStockQuantity();
    $fmInStock = (bool) $product->isInStock();
    $fmHasVariants = !empty($productVariants);
    $fmStockText = $fmInStock
        ? ($fmHasVariants ? 'In stock' : 'In stock (' . $fmStockQty . ' left)')
        : 'Out of stock';

    /* --------------------------------------------------------------------
     * Reviews: count, average and the 5..1 distribution for the stats
     * card. The controller only ships published rows.
     * ---------------------------------------------------------------- */
    $productReviews = $productReviews ?? collect();
    $fmRatingCount = $productReviews->count();
    $fmRatingAvg = $fmRatingCount > 0 ? round((float) $productReviews->avg('ratting'), 1) : 0.0;
    $fmRatingDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($productReviews as $fmReviewRow) {
        $fmStarBucket = (int) round((float) $fmReviewRow->ratting);
        if ($fmStarBucket >= 1 && $fmStarBucket <= 5) {
            $fmRatingDist[$fmStarBucket]++;
        }
    }
    $fmHasReviews = $fmRatingCount > 0;

    /* --------------------------------------------------------------------
     * Description teaser for the summary (the design's narrative slot).
     * ---------------------------------------------------------------- */
    $fmNarrative = trim(strip_tags((string) ($product->description ?? '')));
    $fmNarrative = $fmNarrative !== '' ? \Illuminate\Support\Str::limit($fmNarrative, 200) : null;

    /* --------------------------------------------------------------------
     * Chip above the SKU: the real brand when the product carries one,
     * otherwise the category (brand_name resolves to "-" on this shop).
     * ---------------------------------------------------------------- */
    $fmBrand = trim((string) ($product->brand_name ?? ''));
    if ($fmBrand !== '' && strtoupper($fmBrand) !== 'N/A' && $fmBrand !== '-') {
        $fmBrandChip = $fmBrand;
    } else {
        $fmBrandChip = $product->category?->name;
    }

    /* --------------------------------------------------------------------
     * Breadcrumbs: home -> all products -> category chain -> product.
     * The name is plain text (the h1 lives in the summary, per design).
     * ---------------------------------------------------------------- */
    $fmCrumbs = [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'All products', 'url' => route('products.page')],
    ];
    foreach ($product_categories ?? [] as $fmCatCrumb) {
        $fmCrumbs[] = ['name' => $fmCatCrumb['name'], 'url' => $fmCatCrumb['url']];
    }
    $fmCrumbs[] = ['name' => $product->name, 'url' => null];

    /* --------------------------------------------------------------------
     * Variant pill groups. A price only joins a pill when the product
     * has a single attribute, so a label never quotes a number that a
     * different combination might contradict; multi-attribute prices
     * arrive from check-stock after a complete selection.
     * ---------------------------------------------------------------- */
    $fmVariantGroups = [];
    if (!empty($variantAttributes)) {
        $fmAttrCount = count($variantAttributes);
        foreach ($variantAttributes as $fmAttr) {
            $fmGroupValues = [];
            foreach (($fmAttr['values'] ?? []) as $fmVal) {
                $fmValPrice = null;
                if ($fmAttrCount === 1) {
                    foreach ($productVariants ?? [] as $fmPv) {
                        $fmDecoded = json_decode((string) ($fmPv->attributes_json ?? ''), true);
                        $fmIds = is_array($fmDecoded) ? ($fmDecoded['ids'] ?? []) : [];
                        if (isset($fmIds[$fmAttr['slug']]) && (int) $fmIds[$fmAttr['slug']] === (int) $fmVal['id']) {
                            if ($fmPv->price_override !== null && $fmPv->price_override !== '') {
                                $fmValPrice = (float) $fmPv->price_override;
                            }
                            break;
                        }
                    }
                }
                $fmGroupValues[] = [
                    'id' => $fmVal['id'],
                    'label' => $fmVal['label'],
                    // Null unless the admin stored a real hex for this value,
                    // so a swatch is only ever shown for a colour that exists.
                    'colour' => $fmVal['colour'] ?? null,
                    'price' => $fmValPrice,
                ];
            }
            $fmVariantGroups[] = [
                'name' => (string) ($fmAttr['name'] ?? ''),
                'slug' => (string) ($fmAttr['slug'] ?? ''),
                'values' => $fmGroupValues,
            ];
        }
    }

    /* --------------------------------------------------------------------
     * Document and video: both tabs only exist when there is something
     * to show (no active product currently has either, so the render
     * paths stay unexercised but faithful to the classic rules).
     * ---------------------------------------------------------------- */
    $fmDocPath = preg_replace('~^public/~', '', (string) ($product->document ?? ''));
    $fmDocPath = ltrim($fmDocPath, '/');
    $fmDocUrl = $fmDocPath !== '' ? asset($fmDocPath) : null;

    $fmVideos = [];
    foreach ($product_videos ?? [] as $fmVideoRaw) {
        $fmVideos[] = \App\Support\MediaPath::url($fmVideoRaw, 'product');
    }
    $fmVideoEmbed = null;
    $fmProVideo = trim((string) ($product->pro_video ?? ''));
    if ($fmProVideo !== '') {
        $fmVideoEmbed = [
            'url' => preg_match('~\.mp4(\?.*)?$~i', $fmProVideo) === 1
                ? \App\Support\MediaPath::url($fmProVideo, 'product')
                : $fmProVideo,
            'isMp4' => preg_match('~\.mp4(\?.*)?$~i', $fmProVideo) === 1,
        ];
    }
    $fmHasVideo = count($fmVideos) > 0 || $fmVideoEmbed !== null;

    /* --------------------------------------------------------------------
     * WhatsApp shortcut: same wording and digits-only number as classic.
     * ---------------------------------------------------------------- */
    $fmWhatsapp = null;
    $fmWaEnabled = (bool) ($generalsetting->order_whatsapp_enabled ?? false);
    $fmWaNumber = preg_replace('~\D~', '', (string) ($generalsetting->order_whatsapp_number ?? ''));
    if ($fmWaEnabled && $fmWaNumber !== '') {
        $fmWhatsapp = 'https://wa.me/' . $fmWaNumber . '?text=' . rawurlencode(
            'I would like to know more about this product: ' . $product->name . ' - ' . url()->current()
        );
    }

    /* --------------------------------------------------------------------
     * Related rail header: link to the product's own category.
     * ---------------------------------------------------------------- */
    $fmRelatedUrl = $product_categories[0]['url'] ?? route('products.page');
    $fmRelatedLabel = isset($product_categories[0]['name'])
        ? 'View all ' . $product_categories[0]['name']
        : 'See all products';
@endphp

@section('title', $product->name . ' | ' . ($generalsetting->name ?? ''))
{{-- MetaText: the product name and the generated narrative are plain text
     for a meta tag - @section escapes once and seo-meta escapes again. --}}
@section('meta_description', \App\Support\MetaText::limited($fmNarrative ?: ($product->name ?? ''), 160))

@push('css')
{{-- filemtime busts the browser cache, so a CSS fix is visible on reload
     without needing a hard refresh during development. --}}
<link rel="stylesheet" href="{{ asset('frontend/css/fm-product.css') }}?v={{ @filemtime(public_path('frontend/css/fm-product.css')) }}">
@endpush

@section('content')

    @include('frontend.fm.sections.detail.breadcrumbs', ['crumbs' => $fmCrumbs])

    @include('frontend.fm.sections.detail.product-main')

    {{-- FM:SECTION tabs-specifications ==================================== --}}
    @include('frontend.fm.sections.detail.tabs')
    {{-- /FM:SECTION tabs-specifications --}}

    @include('frontend.fm.sections.detail.related', [
        'products'  => $related_products ?? collect(),
        'linkUrl'   => $fmRelatedUrl,
        'linkLabel' => $fmRelatedLabel,
    ])

    {{-- FM:SECTION newsletter ============================================= --}}
    @include('frontend.fm.sections.newsletter')
    {{-- /FM:SECTION newsletter --}}

@endsection

@push('script')
{{--
    The page behaviour lives in public/frontend/js/fm-product-detail.js. It used
    to be an inline block here with nine blade expressions buried in the middle
    of it. They are now this one array, serialised into
    window.FM_DETAIL_CONFIG immediately before the file loads, so the script is
    plain JavaScript again - cacheable, readable, lintable.

    Every value carries exactly what the inline object used, so nothing about the
    script's behaviour changed.
--}}
@php
    $fmDetailConfig = [
        'productId'        => (int) $product->id,
        'productName'      => (string) ($product->name ?? ''),
        'categoryName'     => (string) ($product_categories[0]['name'] ?? ''),
        'viewPrice'        => (float) ($product->new_price ?? 0),
        'variantDetailUrl' => route('products.variant-detail', ['productId' => $product->id]),
        'currency'         => 'Tk ',
        'maxQty'           => max(1, $fmStockQty),
        'basePrice'        => $fmNewPrice,
        'baseOldPrice'     => $fmDetailsOldPrice ? $fmOldPrice : 0,
        'baseSku'          => (string) ($product->sku ?: 'N/A'),
        'baseStockOut'     => ! $fmInStock,
        'baseStockText'    => $fmStockText,
        'msg' => [
            'pickAll'        => 'Select all options',
            'unavailable'    => 'This selection is out of stock',
            'offline'        => 'Stock info could not be loaded',
            'reviewRatting'  => 'Choose a star rating',
            'reviewText'     => 'Write a review',
            'reviewFallback' => 'Try again',
        ],
    ];
@endphp
<script>
    window.FM_DETAIL_CONFIG = @json($fmDetailConfig, JSON_UNESCAPED_UNICODE);
</script>
<script src="{{ asset('frontend/js/fm-product-detail.js') }}"></script>
@endpush
