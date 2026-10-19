{{--
    FM:PARTIAL categories-index
    =========================================================================
    The /categories page body: one card per top-level department, each card
    listing that department's children as chips.

    DESIGN SOURCE
    -------------------------------------------------------------------------
    The product page (fm/pages/products.blade.php -> fm-products-v2.css), which
    is the design this page is asked to match. That means the same shell:

        .fm-container   the 1280px gutter every FM listing uses
        .fm-card        the bordered / hover-lift card
        .fm-section-head the eyebrow + title block

    and the same hover lift + emerald accents, rather than the Bootstrap
    .category-tree of the classic page it replaces.

    THE THREE LEVELS
    -------------------------------------------------------------------------
    The route closure hands over top-level departments with `children`
    already mapped (child -> children). All three levels get a link:

        department      route('category', $slug)              the department page
        child           route('products.category', $slug)    its listing
        grandchild      route('products.category', $slug)    its listing

    The grandchild list is collapsed behind a "more" toggle by CSS+JS rather
    than printed inline, because a department with forty grandchildren would
    otherwise push the rest of the grid a full screen down.

    IMAGE GUARD
    -------------------------------------------------------------------------
    A category whose stored image path no longer exists on disk gets the
    bundled default instead of a broken <img> - the same guard
    partials/header/nav-categories applies in the drawer.

    Props
        categories  collection  top-level departments, `children` pre-mapped

    Usage
        @include('frontend.fm.sections.categories-index', ['categories' => $categories])
--}}

@php
    $fmCats = $categories ?? collect();
    $fmCatTotal = $fmCats->isEmpty() ? 0 : $fmCats->sum(fn ($c) => 1 + $c->children->count());
@endphp

{{-- FM:SECTION categories-index ======================================== --}}
<main class="fm-page-categories" data-purpose="categories-index">
    <div class="fm-container">

        {{-- Breadcrumb, the same partial every other FM listing uses. --}}
        @include('frontend.fm.partials._page-breadcrumb', ['current' => 'Categories'])

        @include('frontend.fm.sections.partials._section-head', [
            'eyebrow' => '',
            'heading' => 'Browse all categories',
        ])

        {{--
            The grid lives on THIS wrapper, not on <main>.

            <main> holds .fm-container as its only child, so a grid declared on
            <main> would have had exactly one grid item - the container - while
            the cards, its grandchildren, were never items at all. They stacked
            in a single narrow column down the left edge instead of filling the
            page.
        --}}
        <div class="fm-cat-grid">
        @forelse($fmCats as $fmCat)
            @php
                $fmChildren = $fmCat->children ?? collect();
                $fmGrandKids = collect();

                foreach ($fmChildren as $fmChild) {
                    $fmGrandKids = $fmGrandKids->merge($fmChild->children ?? collect());
                }

                $fmPanelId = 'fmCatPanel' . $fmCat->id;
                $fmMoreId  = 'fmCatMore' . $fmCat->id;

                /*
                 * Image guard: the admin may have pointed a category at a file
                 * that was later deleted from /public, and an <img> that 404s
                 * renders as a broken box inside the card.
                 */
                $fmImgPath = preg_replace('~^public/~', '', (string) ($fmCat->image ?? ''));
                $fmImgPath = ltrim($fmImgPath, '/');
                if ($fmImgPath === '' || ! \Illuminate\Support\Facades\File::exists(public_path($fmImgPath))) {
                    $fmImgPath = 'assets/images/default.png';
                }
            @endphp

            <article class="fm-cat-card" data-fm-cat-card>
                {{-- The card head is a link to the department itself. A visitor
                     who wants the department should not have to open anything. --}}
                <a class="fm-cat-card__head" href="{{ route('category', $fmCat->slug) }}">
                    <span class="fm-cat-card__thumb">
                        <img alt="{{ $fmCat->name }}" height="44" loading="lazy" width="44"
                             src="{{ asset($fmImgPath) }}">
                    </span>
                    <span class="fm-cat-card__meta">
                        <span class="fm-cat-card__name">{{ $fmCat->name }}</span>
                        <span class="fm-cat-card__count">
                            {{ $fmChildren->count() }} {{ \Illuminate\Support\Str::plural('sub-department', $fmChildren->count()) }}
                        </span>
                    </span>
                    <i aria-hidden="true" class="bi bi-arrow-right fm-cat-card__go"></i>
                </a>

                @if ($fmChildren->isNotEmpty())
                    <ul class="fm-cat-card__list">
                        @foreach ($fmChildren as $fmChild)
                            <li>
                                <a href="{{ route('products.category', $fmChild->slug) }}">
                                    <i aria-hidden="true" class="bi bi-dot"></i>
                                    {{ $fmChild->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="fm-cat-card__empty">No sub-departments in this department yet.</p>
                @endif

                {{-- Grandchildren: hidden until "Show all" is pressed, and only
                     rendered when there are any, so an empty panel can never
                     appear. --}}
                @if ($fmGrandKids->isNotEmpty())
                    <button aria-controls="{{ $fmPanelId }}" aria-expanded="false"
                            class="fm-cat-card__more" data-bs-toggle="collapse"
                            data-bs-target="#{{ $fmPanelId }}"
                            data-fm-more-open="Show all {{ $fmGrandKids->count() }}"
                            type="button">
                        <span data-fm-more-label>Show all {{ $fmGrandKids->count() }}</span>
                        <i aria-hidden="true" class="bi bi-chevron-down"></i>
                    </button>

                    <div class="collapse" id="{{ $fmPanelId }}">
                        <div class="fm-cat-card__more-wrap" id="{{ $fmMoreId }}">
                            <ul class="fm-cat-card__list fm-cat-card__list--sub">
                                @foreach ($fmGrandKids as $fmGrand)
                                    <li>
                                        <a href="{{ route('products.category', $fmGrand->slug) }}">
                                            <i aria-hidden="true" class="bi bi-dot"></i>
                                            {{ $fmGrand->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="fm-cat-empty">
                <i aria-hidden="true" class="bi bi-grid-3x3-gap"></i>
                <p>No categories have been published yet.</p>
                <a class="fm-cat-empty__cta" href="{{ route('home') }}">Back to home</a>
            </div>
        @endforelse
        </div>{{-- /.fm-cat-grid --}}
    </div>
</main>
{{-- /FM:SECTION categories-index ======================================= --}}

{{--
    The "Show all" label has to flip to "Show less" when the panel closes.
    Bootstrap only toggles the .show class on the panel, so the label is read
    off the panel - one listener, no duplicated state to keep in sync.
--}}
@push('script')
<script>
    (function () {
        if (window.__fmCategoriesBound) { return; }
        window.__fmCategoriesBound = true;

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-fm-cat-card] .fm-cat-card__more');
            if (!button) { return; }

            var panelId = button.getAttribute('data-bs-target');
            var panel = panelId ? document.querySelector(panelId) : null;
            var label = button.querySelector('[data-fm-more-label]');
            if (!panel || !label) { return; }

            // collapse is mid-flight right now; read the class on the next
            // frame, by which time Bootstrap has written the final state.
            window.requestAnimationFrame(function () {
                label.textContent = panel.classList.contains('show') ? 'Show less' : button.dataset.fmMoreOpen;
            });
        });
    })();
</script>
@endpush
