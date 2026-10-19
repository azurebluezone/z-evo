{{--
    FM:PARTIAL header/nav-categories
    =========================================================================
    The department tree: category -> subcategory -> childcategory, the same
    three levels the classic header's mega menu and the classic mobile menu
    both render.

    WHY THIS PARTIAL EXISTS
    -------------------------------------------------------------------------
    AppServiceProvider shares $menucategories globally and eager-loads
    subcategories and childcategories for it - two extra queries whose ONLY
    consumer is this tree. The classic layout prints all three levels; the
    modern drawer printed top-level departments alone, which left all sixteen
    subcategory pages reachable only by typing their URL. The data was already
    being loaded on every page; nothing was reading two thirds of it.

    The three levels are collapsible rather than always-open: sixteen
    departments with their children inline is a wall of text in a 380px
    offcanvas, and a Bootstrap collapse needs no JavaScript of its own.

    Props
        (none) - reads the globally shared $menucategories

    Usage
        @include('frontend.fm.partials.header.nav-categories')
--}}

@php
    /*
     * The icon fallback mirrors partials/category-menu.blade.php: a category
     * whose stored image path does not exist on disk gets the shared default
     * instead of a broken <img>.
     */
    $fmNavCats = $menucategories ?? collect();
@endphp

@if ($fmNavCats->isEmpty())
    {{-- No departments yet: the drawer still has to offer a way forward. --}}
    <a class="fm-drawer-cat fm-drawer-cat--all" href="{{ route('categories.page') }}">
        <i class="bi bi-grid-3x3-gap text-emerald-600"></i>
        <span>Browse departments</span>
    </a>
@else
    <div class="fm-drawer-tree" data-fm-drawer-tree>
        @foreach ($fmNavCats as $fmNavCat)
            @php
                $fmNavSubs = $fmNavCat->subcategories ?? collect();
                $fmNavCatId = 'fmCat' . $fmNavCat->id;

                $fmNavIcon = preg_replace('~^public/~', '', (string) ($fmNavCat->image ?? ''));
                $fmNavIcon = ltrim($fmNavIcon, '/');
                if ($fmNavIcon === '' || ! \Illuminate\Support\Facades\File::exists(public_path($fmNavIcon))) {
                    $fmNavIcon = 'uploads/category/default.png';
                }
            @endphp

            <div class="fm-drawer-cat-group">
                {{-- The department itself is a link, not a toggle: a visitor
                     who wants the department page should not have to open an
                     accordion to get there. --}}
                <div class="d-flex align-items-center gap-1">
                    <a class="fm-drawer-cat flex-grow-1" href="{{ route('category', $fmNavCat->slug) }}">
                        <img alt="" class="fm-drawer-cat-icon" src="{{ asset($fmNavIcon) }}" width="20" height="20" loading="lazy">
                        <span>{{ $fmNavCat->name ?? '' }}</span>
                    </a>

                    @if ($fmNavSubs->isNotEmpty())
                        <button aria-controls="{{ $fmNavCatId }}" aria-expanded="false"
                                class="fm-drawer-cat-toggle" data-bs-toggle="collapse"
                                data-bs-target="#{{ $fmNavCatId }}" type="button">
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            <span class="visually-hidden">Show {{ $fmNavCat->name ?? '' }} sub-departments</span>
                        </button>
                    @endif
                </div>

                @if ($fmNavSubs->isNotEmpty())
                    <div class="collapse" id="{{ $fmNavCatId }}">
                        <ul class="fm-drawer-sublist list-unstyled m-0">
                            @foreach ($fmNavSubs as $fmNavSub)
                                @php
                                    $fmNavChildren = $fmNavSub->childcategories ?? collect();
                                    $fmNavSubId = $fmNavCatId . 'Sub' . ($fmNavSub->id ?? 0);
                                @endphp
                                <li>
                                    <div class="d-flex align-items-center gap-1">
                                        <a class="fm-drawer-subcat flex-grow-1" href="{{ route('products.category', $fmNavSub->slug) }}">
                                            {{ $fmNavSub->subcategoryName ?? '' }}
                                        </a>

                                        @if ($fmNavChildren->isNotEmpty())
                                            <button aria-controls="{{ $fmNavSubId }}" aria-expanded="false"
                                                    class="fm-drawer-cat-toggle fm-drawer-cat-toggle--sm"
                                                    data-bs-toggle="collapse" data-bs-target="#{{ $fmNavSubId }}" type="button">
                                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                                <span class="visually-hidden">Show children of {{ $fmNavSub->subcategoryName ?? '' }}</span>
                                            </button>
                                        @endif
                                    </div>

                                    @if ($fmNavChildren->isNotEmpty())
                                        <div class="collapse" id="{{ $fmNavSubId }}">
                                            <ul class="fm-drawer-childlist list-unstyled m-0">
                                                @foreach ($fmNavChildren as $fmNavChild)
                                                    <li>
                                                        <a class="fm-drawer-childcat" href="{{ route('products.category', $fmNavChild->slug) }}">
                                                            {{ $fmNavChild->childcategoryName ?? '' }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif