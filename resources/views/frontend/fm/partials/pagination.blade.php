{{--
    FM:PARTIAL pagination
    =========================================================================
    Page navigation for the catalog, styled like the approved design: the
    visible range on the left, rounded page buttons on the right.

    Expects:
        paginator  Illuminate\Pagination\LengthAwarePaginator  required

    Plain anchor links only - paging keeps every current filter because the
    paginator was built with ->appends(request()->query()).
    =========================================================================
--}}

@php
    $fmPage = (int) $paginator->currentPage();
    $fmLastPage = (int) $paginator->lastPage();

    // A small window around the current page, always showing first and last
    // so the visitor can jump anywhere without clicking through.
    if ($fmLastPage <= 7) {
        $fmPages = range(1, $fmLastPage);
    } else {
        $fmFrom = max(2, $fmPage - 1);
        $fmTo = min($fmLastPage - 1, $fmPage + 1);

        $fmPages = [1];
        if ($fmFrom > 2) {
            $fmPages[] = 'gap';
        }
        for ($fmNumber = $fmFrom; $fmNumber <= $fmTo; $fmNumber++) {
            $fmPages[] = $fmNumber;
        }
        if ($fmTo < $fmLastPage - 1) {
            $fmPages[] = 'gap';
        }
        $fmPages[] = $fmLastPage;
    }

    $fmFirst = $paginator->firstItem();
    $fmLast = $paginator->lastItem();
@endphp

@if($fmLastPage > 1)
    <div class="fm-shop-pager">
        <span class="fm-shop-pager__info">
            @if($fmFirst !== null && $fmLast !== null)
                <strong>{{ number_format($fmFirst) }}</strong> – <strong>{{ number_format($fmLast) }}</strong>
                / {{ number_format($paginator->total()) }} products
            @endif
        </span>

        <nav aria-label="Product list pages">
            <ul class="fm-shop-pager__list">

                @if($paginator->onFirstPage())
                    <li>
                        <span class="fm-pager__btn" aria-disabled="true" aria-label="Previous page">
                            <i class="bi bi-chevron-left"></i>
                        </span>
                    </li>
                @else
                    <li>
                        <a class="fm-pager__btn" href="{{ $paginator->previousPageUrl() }}" aria-label="Previous page">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                @endif

                @foreach($fmPages as $fmPageNumber)
                    @if($fmPageNumber === 'gap')
                        <li><span class="fm-pager__gap" aria-hidden="true">…</span></li>
                    @elseif($fmPageNumber === $fmPage)
                        <li>
                            <span class="fm-pager__btn is-current" aria-current="page">
                                {{ number_format($fmPageNumber) }}
                            </span>
                        </li>
                    @else
                        <li>
                            <a class="fm-pager__btn" href="{{ $paginator->url($fmPageNumber) }}">
                                {{ number_format($fmPageNumber) }}
                            </a>
                        </li>
                    @endif
                @endforeach

                @if($paginator->hasMorePages())
                    <li>
                        <a class="fm-pager__btn" href="{{ $paginator->nextPageUrl() }}" aria-label="Next page">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                @else
                    <li>
                        <span class="fm-pager__btn" aria-disabled="true" aria-label="Next page">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </li>
                @endif

            </ul>
        </nav>
    </div>
@endif
