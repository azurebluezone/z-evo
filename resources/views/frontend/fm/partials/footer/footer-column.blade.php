
<div class="fm-footer-col">
    {{-- On a phone this is the accordion trigger; on desktop CSS takes the
         button out of the tab order is NOT possible on a <button>, so the
         rule is handled in fm-footer.css (pointer-events:none) and the
         chevron is hidden there. aria-expanded is maintained here by
         fm-app.js, which is what a screen reader announces. --}}
    <button aria-controls="{{ $id }}" aria-expanded="true"
            class="fm-footer-col__head" data-fm-footer-toggle type="button">
        <span class="fm-footer-col__title">
            @if (!empty($icon))
                <i aria-hidden="true" class="bi {{ $icon }} me-2"></i>
            @endif
            {{ $heading }}
        </span>
        <i aria-hidden="true" class="bi bi-chevron-down fm-footer-col__chev"></i>
    </button>

    {{-- No .collapse class: that class is what Bootstrap's plugin keys off,
         and the plugin must not touch this element (see the note above). --}}
    <div class="fm-footer-col__body is-open" id="{{ $id }}">
        <ul class="fm-footer-col__list list-unstyled m-0">
            @foreach ($fmColLinks as $fmColLink)
                <li>
                    <a class="{{ !empty($fmColLink['active']) ? 'active' : '' }}"
                       href="{{ $fmColLink['url'] }}">{{ $fmColLink['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </div>
</div>