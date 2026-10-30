{{--
    Social icon picker.

    A native <select> cannot render an icon font inside its options, which is
    why the old "Popular icon" dropdown looked like a list of blank names and
    broke the row alignment. This picker is a Bootstrap dropdown whose menu is
    a grid of the shared SocialIcons catalog: every choice shows its real
    Remix icon (the only icon set the admin loads), its name and its brand
    colour. The submitted value stays the Font Awesome class the storefront
    already stores in social_media.icon.

    Props
        pickerId       unique DOM id for the picker
        pickerName     form field name (default: icon)
        pickerValue    current Font Awesome class
        pickerColorTarget  optional CSS selector of a colour input to fill
--}}
@php
    $pickerId = $pickerId ?? 'social-icon-picker';
    $pickerName = $pickerName ?? 'icon';
    $pickerValue = trim((string) ($pickerValue ?? ''));
    $pickerEntry = \App\Support\SocialIcons::entryForFa($pickerValue);
    $pickerLabel = $pickerEntry['label'] ?? ($pickerValue !== '' ? $pickerValue : 'Choose a platform');
@endphp

<div class="dropdown icon-picker" id="{{ $pickerId }}" data-icon-picker
     @if(!empty($pickerColorTarget)) data-color-target="{{ $pickerColorTarget }}" @endif>
    {{-- `.form-select` gives this button the same box as the template's
         pickers (height, padding, radius, border colour, caret) so it lines up
         with the text inputs in the same row at every breakpoint. --}}
    <button type="button"
            id="{{ $pickerId }}_toggle"
            class="form-select icon-picker__toggle w-100 d-flex align-items-center gap-2"
            data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-strategy="fixed"
            aria-expanded="false" aria-haspopup="true">
        <span class="icon-picker__preview" data-icon-preview aria-hidden="true">
            <i class="{{ $pickerEntry['ri'] ?? 'ri ri-global-line' }}"></i>
        </span>
        <span class="text-truncate" data-icon-label>{{ $pickerLabel }}</span>
    </button>

    <div class="dropdown-menu w-100 p-2 icon-picker__menu">
        <div class="row row-cols-2 row-cols-lg-3 g-1">
            @foreach($socialPresets as $preset)
                <div class="col">
                    <button type="button"
                            class="icon-picker__option @if($preset['fa'] === $pickerValue) active @endif"
                            aria-pressed="{{ $preset['fa'] === $pickerValue ? 'true' : 'false' }}"
                            data-icon-value="{{ $preset['fa'] }}"
                            data-icon-color="{{ $preset['color'] }}"
                            data-icon-label="{{ $preset['label'] }}"
                            data-icon-ri="{{ $preset['ri'] }}">
                        <span class="icon-picker__option-icon">
                            <i class="{{ $preset['ri'] }}" aria-hidden="true"></i>
                        </span>
                        <span class="text-truncate">{{ $preset['label'] }}</span>
                    </button>
                </div>
            @endforeach
        </div>

        <div class="border-top mt-2 pt-2">
            <label class="form-label small mb-1" for="{{ $pickerId }}_custom">Custom icon class</label>
            <input type="text" class="form-control form-control-sm" id="{{ $pickerId }}_custom"
                   data-icon-custom placeholder="fab fa-snapchat" value="{{ $pickerValue }}">
        </div>
    </div>

    <input type="hidden" name="{{ $pickerName }}" value="{{ $pickerValue }}" data-icon-input>
</div>
