{{--
    FM:SECTION detail-tabs ---------------------------------------------------
    Tab strip under the product detail (hi design "TABS & SPECIFICATIONS"
    section, id kept as customer-reviews for old anchors). The design's
    nutrition and cooking tabs carry invented content, so the tab set here is
    the classic one backed by real data:

        Description | Reviews(n) | Document (when the product has one)
        | Video (when the product has one)

    Expects (computed once in pages/product-details.blade.php):
        $fmProduct        App\Models.Product        required
        $productReviews   Collection                published reviews, newest first
        $fmRatingCount    int
        $fmRatingAvg      float
        $fmRatingDist     array   5..1 => count
        $fmDocUrl         string|null
        $fmHasVideo       bool
        $fmVideos         array   direct mp4 URLs from the gallery
        $fmVideoEmbed     array|null  ['url' => ..., 'isMp4' => bool]
        $fmHasVariants    bool   (affects nothing here, kept for context)

    Review submission needs a signed in customer (the endpoint reads the
    customer guard), so guests get a login link instead of a broken form.
    New reviews are stored as pending and appear after an administrator
    approves them - the success message says exactly that.
-------------------------------------------------------------------------- --}}

@php
    $fmProduct      = $fmProduct ?? $product;
    $productReviews = $productReviews ?? collect();
    $fmRatingCount  = $fmRatingCount ?? 0;
    $fmRatingAvg    = $fmRatingAvg ?? 0;
    $fmRatingDist   = $fmRatingDist ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $fmDocUrl       = $fmDocUrl ?? null;
    $fmHasVideo     = $fmHasVideo ?? false;
    $fmVideos       = $fmVideos ?? [];
    $fmVideoEmbed   = $fmVideoEmbed ?? null;

    // Shared promises: the support card below reuses the fourth one.
    $fmServices = \App\Support\ShopPromises::all();
    $fmSupport  = $fmServices[3];
@endphp

<section class="w-full py-10 md:py-14 bg-surface-cream border-t border-b border-gray-100" data-section="tabs-specifications" id="customer-reviews">
    <div class="container mx-auto px-4 max-w-7xl">

        {{-- Tabs navigation --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-gray-200 no-scrollbar" role="tablist">
            <button type="button" class="fm-detail-tab is-active" data-fm-tab="tab-description" role="tab" aria-selected="true">
                Description
            </button>
            <button type="button" class="fm-detail-tab" data-fm-tab="tab-reviews" role="tab" aria-selected="false">
                Reviews ({{ $fmRatingCount }})
            </button>
            @if($fmDocUrl)
                <button type="button" class="fm-detail-tab" data-fm-tab="tab-document" role="tab" aria-selected="false">
                    Document
                </button>
            @endif
            @if($fmHasVideo)
                <button type="button" class="fm-detail-tab" data-fm-tab="tab-video" role="tab" aria-selected="false">
                    Video
                </button>
            @endif
        </div>

        <div class="mt-8">

            {{-- Tab: Description --}}
            <div class="fm-detail-panel" id="tab-description" role="tabpanel">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <div class="lg:col-span-7 flex flex-col gap-4">
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900">Description</h3>
                        <div class="fm-detail-desc">
                            @if(trim((string) $fmProduct->description) !== '')
                                {!! $fmProduct->description !!}
                            @else
                                <p class="text-sm md:text-base text-gray-600 leading-relaxed m-0">
                                    A detailed description has not been added yet. Contact the helpline if you have any questions.
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        {{-- Support card: the design's "sourced from" media card
                             filled with the shop's real contact promises. --}}
                        <div class="rounded-2xl overflow-hidden bg-white border border-gray-200 shadow-sm p-4 flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-bold text-gray-900 m-0">Support & delivery</h4>
                                <span class="text-xs font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded">Helpline</span>
                            </div>
                            <div class="aspect-video w-full rounded-xl bg-emerald-50 flex flex-col items-center justify-center gap-1 text-emerald-800 px-4 text-center">
                                <i class="bi {{ $fmSupport->icon }} text-4xl"></i>
                                <span class="text-sm font-bold">{{ $fmSupport->title }}</span>
                                <span class="text-xs text-emerald-700">{{ $fmSupport->text }}</span>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 pt-1">
                                <span class="flex items-center gap-1">
                                    <i class="bi bi-telephone text-emerald-700"></i>
                                    Helpline: {{ ($generalsetting->support_phone ?? '') !== '' ? $generalsetting->support_phone : 'See contact page' }}
                                </span>
                                <span class="text-emerald-800 font-bold bg-emerald-50 px-2 py-0.5 rounded-full">{{ $fmServices[0]->title }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab: Reviews --}}
            <div class="fm-detail-panel hidden" id="tab-reviews" role="tabpanel">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    {{-- Stats card --}}
                    <div class="lg:col-span-4 p-5 rounded-2xl bg-white border border-gray-200 shadow-sm flex flex-col gap-4">
                        <div class="text-center py-2">
                            <span class="text-4xl font-extrabold text-gray-900">{{ number_format($fmRatingAvg, 1) }}</span>
                            <div class="flex justify-center text-amber-500 my-1">
                                @include('frontend.fm.partials._stars', ['fmStarValue' => $fmRatingAvg, 'fmStarSize' => 'text-sm'])
                            </div>
                            <span class="text-xs text-gray-500">based on {{ $fmRatingCount }} reviews</span>
                        </div>

                        {{-- Distribution --}}
                        <div class="space-y-2 text-xs">
                            @foreach([5, 4, 3, 2, 1] as $fmStar)
                                @php
                                    $fmStarCount  = (int) ($fmRatingDist[$fmStar] ?? 0);
                                    $fmStarPct = $fmRatingCount > 0 ? (int) round($fmStarCount * 100 / $fmRatingCount) : 0;
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="w-12 text-gray-600 font-bold">{{ $fmStar }} stars</span>
                                    <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-full bg-emerald-700 rounded-full" style="width: {{ $fmStarPct }}%;"></div>
                                    </div>
                                    <span class="w-8 text-right font-semibold text-gray-700">{{ $fmStarPct }}%</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Write a review --}}
                        @auth('customer')
                            <button type="button" data-fm-review-open
                                    class="w-full py-2.5 rounded-xl bg-emerald-800 text-white text-xs font-bold shadow-sm hover:bg-emerald-900 transition-colors">
                                Write a review
                            </button>

                            <form id="fmReviewForm" class="hidden flex flex-col gap-3" method="POST" action="{{ route('customer.review') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $fmProduct->id }}">

                                <span class="text-xs font-bold text-gray-900">Your rating:</span>
                                <div class="fm-rate-stars">
                                    @for($fmR = 5; $fmR >= 1; $fmR--)
                                        <input type="radio" name="ratting" id="fmRate{{ $fmR }}" value="{{ $fmR }}" required>
                                        <label for="fmRate{{ $fmR }}" title="{{ $fmR }} stars"><i class="bi bi-star-fill"></i></label>
                                    @endfor
                                </div>

                                <textarea name="review" rows="4" required maxlength="2000"
                                          class="form-control"
                                          placeholder="Tell us how you liked this product..."></textarea>

                                <p class="fm-variant-note text-xs text-red-600 font-semibold hidden m-0" id="fmReviewFormNote"></p>

                                <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-800 text-white text-xs font-bold shadow-sm hover:bg-emerald-900 transition-colors">
                                    Submit review
                                </button>
                                <p class="text-sm text-gray-500 m-0 text-center">
                                    Your review will appear after approval.
                                </p>
                            </form>

                            <p id="fmReviewDone" class="hidden m-0 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-xl p-3 text-center">
                                Thank you! Your review has been submitted - it will appear here after approval.
                            </p>
                        @else
                            <a href="{{ route('customer.login') }}"
                               class="w-full py-2.5 rounded-xl bg-emerald-800 text-white text-xs font-bold shadow-sm hover:bg-emerald-900 transition-colors text-center">
                                Log in to write a review
                            </a>
                            <p class="text-sm text-gray-500 m-0 text-center">
                                Log in and share your experience with others.
                            </p>
                        @endauth
                    </div>

                    {{-- Review stream --}}
                    <div class="lg:col-span-8 flex flex-col gap-4">
                        @forelse($productReviews as $fmReview)
                            @php
                                $fmReviewName  = trim((string) ($fmReview->name ?? ''));
                                $fmReviewName  = ($fmReviewName !== '' && strtoupper($fmReviewName) !== 'N / A') ? $fmReviewName : 'Customer';
                                $fmReviewWords = preg_split('/\s+/', $fmReviewName) ?: [];
                                $fmReviewInitials = '';
                                foreach (array_slice($fmReviewWords, 0, 2) as $fmWord) {
                                    $fmReviewInitials .= mb_strtoupper(mb_substr($fmWord, 0, 1));
                                }
                                $fmReviewInitials = $fmReviewInitials !== '' ? $fmReviewInitials : 'C';
                            @endphp
                            <div class="p-5 rounded-2xl bg-white border border-gray-200 shadow-sm flex flex-col gap-2">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ $fmReviewInitials }}
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-900 m-0">{{ $fmReviewName }}</h5>
                                            @if($fmReview->customer_id)
                                                <span class="text-xs text-emerald-800 flex items-center gap-1 font-semibold">
                                                    <i class="bi bi-patch-check-fill text-xs"></i> Verified buyer
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400 shrink-0">
                                        {{ $fmReview->created_at ? $fmReview->created_at->format('d/m/Y') : '' }}
                                    </span>
                                </div>

                                <div class="flex text-amber-500 my-0.5">
                                    @include('frontend.fm.partials._stars', ['fmStarValue' => (float) $fmReview->ratting, 'fmStarSize' => 'text-xs'])
                                </div>

                                <p class="text-xs md:text-sm text-gray-700 leading-relaxed m-0">
                                    {!! nl2br(e((string) $fmReview->review)) !!}
                                </p>
                            </div>
                        @empty
                            <div class="fm-empty">
                                <i class="bi bi-chat-left-text"></i>
                                <p>No reviews yet. Be the first to write one!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Tab: Document --}}
            @if($fmDocUrl)
                <div class="fm-detail-panel hidden" id="tab-document" role="tabpanel">
                    <div class="max-w-2xl bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex flex-col gap-3">
                        <h3 class="text-xl font-bold text-gray-900 m-0">Product document</h3>
                        <p class="text-xs text-gray-500 m-0">Open the document for detailed information.</p>
                        <a href="{{ $fmDocUrl }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 self-start py-2.5 px-5 rounded-xl bg-emerald-800 text-white text-sm font-bold shadow-sm hover:bg-emerald-900 transition-colors">
                            <i class="bi bi-file-earmark-text"></i> Open document
                            <i class="bi bi-box-arrow-up-right text-xs"></i>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Tab: Video --}}
            @if($fmHasVideo)
                <div class="fm-detail-panel hidden" id="tab-video" role="tabpanel">
                    <div class="max-w-3xl flex flex-col gap-4">
                        @foreach($fmVideos as $fmVideoUrl)
                            <div class="w-full aspect-video rounded-xl overflow-hidden bg-black">
                                <video controls preload="metadata" class="w-full h-full object-contain">
                                    <source src="{{ $fmVideoUrl }}" type="video/mp4">
                                </video>
                            </div>
                        @endforeach

                        @if($fmVideoEmbed)
                            <div class="w-full aspect-video rounded-xl overflow-hidden bg-black">
                                @if($fmVideoEmbed['isMp4'])
                                    <video controls preload="metadata" class="w-full h-full object-contain">
                                        <source src="{{ $fmVideoEmbed['url'] }}" type="video/mp4">
                                    </video>
                                @else
                                    <iframe src="{{ $fmVideoEmbed['url'] }}" title="Product video"
                                            class="w-full h-full border-0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen></iframe>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</section>
