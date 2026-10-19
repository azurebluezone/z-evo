{{-- FM:SECTION testimonials ------------------------------------------------
     Real customer reviews only. Nothing is invented here: when the shop has no
     usable approved review the section hides itself instead of printing
     placeholder quotes on a live store.
     --------------------------------------------------------------------- --}}

@php
    $fmReviews = collect($testimonials ?? [])->filter(function ($review) {
        return filled($review->review) && mb_strlen(trim($review->review)) >= 20;
    });
@endphp

@if($fmReviews->isNotEmpty())
    <section class="fm-section fm-section--tint fm-testimonials" id="testimonials" data-purpose="testimonials">
        <div class="container mx-auto px-4">
            @include('frontend.fm.sections.partials._section-head', [
                'eyebrow' => 'Customer reviews',
                'heading' => 'What our buyers are saying',
                'text'    => 'From reviews written by our customers.',
                'centered'=> true,
            ])

            <div class="row g-3 g-md-4">
                @foreach($fmReviews->take(3) as $fmReview)
                    @php
                        $fmStars = max(0, min(5, (int) round((float) $fmReview->ratting)));
                        $fmInitials = collect(preg_split('/\s+/u', trim((string) $fmReview->name) ?: 'C', -1, PREG_SPLIT_NO_EMPTY))
                            ->filter()
                            ->take(2)
                            ->map(function ($part) { return mb_substr($part, 0, 1); })
                            ->implode('');
                    @endphp

                    <div class="col-12 col-md-4">
                        <figure class="fm-quote">
                            <div class="fm-quote__stars" aria-label="{{ $fmStars }} stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $fmStars ? 'bi-star-fill' : 'bi-star' }}"></i>
                                @endfor
                            </div>

                            <blockquote class="fm-quote__text">{{ $fmReview->review }}</blockquote>

                            <figcaption class="fm-quote__by">
                                <span class="fm-quote__avatar">{{ $fmInitials ?: 'C' }}</span>
                                <span>
                                    <strong>{{ $fmReview->name ?: 'Customer' }}</strong>
                                    @if($fmReview->product)
                                        <a href="{{ route('product', $fmReview->product->slug) }}">{{ $fmReview->product->name }}</a>
                                    @endif
                                </span>
                            </figcaption>
                        </figure>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
