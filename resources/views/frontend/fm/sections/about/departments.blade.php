{{--
    FM:PARTIAL about/departments
    =========================================================================
    "What you will find here": the live departments with their real product
    counts.

    This block replaces the reference's grower-cooperative story with the
    departments that actually carry the catalogue. The list is handed in
    already ordered by product count, so the ordering rule stays a query
    decision in the page, not a sort buried in markup.

    Props
        categories  Eloquent|Collection  departments with a products_count

    Usage
        @include('frontend.fm.sections.about.departments', ['categories' => $fmTopCategories])
--}}

{{-- ----------------------------------------------------------------------
     What we sell. Replaces the reference's grower-cooperative story
     with the departments that actually carry the catalogue.
     ---------------------------------------------------------------------- --}}
<section class="fm-about__section">
    <div class="container mx-auto px-4">
        <h2 class="fm-about__heading">What you will find here</h2>
        <p class="fm-about__sub">
            The catalogue is organised by age first, because that is how you actually shop for a child.
        </p>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-4">
            @forelse ($categories as $fmCategory)
                <a class="fm-about__dept" href="{{ route('category', $fmCategory->slug) }}">
                    <span class="fm-about__dept-name">{{ $fmCategory->name }}</span>
                    <span class="fm-about__dept-count">{{ $fmCategory->products_count }} products</span>
                </a>
            @empty
                <p class="text-sm text-gray-600">Departments are being set up.</p>
            @endforelse
        </div>
    </div>
</section>