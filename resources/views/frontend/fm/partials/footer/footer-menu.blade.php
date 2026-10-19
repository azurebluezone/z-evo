<div class="fm-footer-menu">
    <button
        aria-controls="{{ $fmMenuId }}"
        aria-expanded="true"
        class="fm-footer-menu__head"
        data-fm-footer-toggle
        type="button"
    >
        <span class="fm-footer-menu__title">{{ $fmMenuTitle }}</span>
        <i aria-hidden="true" class="bi bi-chevron-down fm-footer-menu__chev"></i>
    </button>

    {{-- Ships open: with JavaScript off the visitor still gets every link. --}}
    <div class="fm-footer-menu__body is-open" id="{{ $fmMenuId }}">
        <ul class="fm-footer-menu__list list-unstyled m-0">
            @foreach ($fmMenuItems as $fmMenuItem)
                @php $fmMenuItemActive = !empty($fmMenuItem['active']); @endphp
                <li>
                    <a
                        @if ($fmMenuItemActive) aria-current="page" @endif
                        class="{{ $fmMenuItemActive ? 'active' : '' }}"
                        href="{{ $fmMenuItem['url'] }}"
                    >{{ $fmMenuItem['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
