{{--
    FM:PARTIAL home/category-card
    =========================================================================
    One tile shared by the desktop 6x2 grid and the phone swipe rail. Both
    marks are spans because the whole tile is the link: the icon is decorative
    (aria-hidden) and the visible label is the accessible name.

    $fmTile
        name   string  category name, printed verbatim (escaped)
        url    string  category route (or the full catalog for "More")
        icon   string  Bootstrap Icons class from the section's palette map
        bg,fg  string  plate background / icon colour
        image  ?string real category image; null keeps the icon plate
    =========================================================================
--}}
<a class="fm-cat-card" href="{{ $fmTile['url'] }}">
    <span class="fm-cat-card__icon" style="background-color: {{ $fmTile['bg'] }}; color: {{ $fmTile['fg'] }};" aria-hidden="true">
        @if (! empty($fmTile['image']))
            <img src="{{ $fmTile['image'] }}" alt="" loading="lazy" decoding="async" width="52" height="52">
        @else
            <i class="bi {{ $fmTile['icon'] }}"></i>
        @endif
    </span>

    <span class="fm-cat-card__label">{{ $fmTile['name'] }}</span>
</a>
