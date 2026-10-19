{{--
    FM:PARTIAL stars
    =========================================================================
    Five star icons for a 0-5 value (half stars round down). Used by the
    product summary, the review stats card and every review card.

    Expects:
        $fmStarValue  float              rating to draw
        $fmStarSize   string  optional   text size utility (default text-sm)
    =========================================================================
--}}

@php
    $fmStarValue = (float) ($fmStarValue ?? 0);
    $fmStarSize  = $fmStarSize ?? 'text-sm';
@endphp

@for($fmI = 1; $fmI <= 5; $fmI++)
    @if($fmStarValue >= $fmI)
        <i class="bi bi-star-fill {{ $fmStarSize }} {{ $fmI > 1 ? 'ms-0.5' : '' }}"></i>
    @elseif($fmStarValue >= $fmI - 0.5)
        <i class="bi bi-star-half {{ $fmStarSize }} {{ $fmI > 1 ? 'ms-0.5' : '' }}"></i>
    @else
        <i class="bi bi-star {{ $fmStarSize }} {{ $fmI > 1 ? 'ms-0.5' : '' }}"></i>
    @endif
@endfor
