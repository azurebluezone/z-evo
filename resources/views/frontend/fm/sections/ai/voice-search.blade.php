{{--
    FM:COMPONENT ai/voice-search
    =========================================================================
    Voice-search trigger, split out of the header bar so the bar can stay a
    list of includes instead of one long markup block.

    Cut from the reference header (work/2.md, #global-voice-search-btn) with
    tools/split_design_sections.py, then rewired to the site:

      - the design called openAiDrawer('voice') from an inline onclick, which
        only exists inside the static mockup. Every trigger in the running
        site goes through data-fm-open, which fm-ai.js binds once on the
        document (so a trigger still works if the bar is re-rendered).
      - the design used Material Symbols; the shipped UI uses Bootstrap Icons
        throughout, so the glyph matches the rest of the header.

    Props
        variant string  'inline' (default, sits inside the search field)
                        'pill'   (standalone button for a hero or a bar)
        label    string  accessible label, defaults to "Search with voice"

    Usage
        <x-fm::ai.voice-search />          (see components registered in
        @include('frontend.fm.sections.ai.voice-search')   AppServiceProvider)
--}}

@php
    $variant = $variant ?? 'inline';
    $label   = $label ?? 'Search with voice';
@endphp

@if ($variant === 'pill')
    <button aria-label="{{ $label }}" class="ai-voice-pill" data-fm-open="voice" title="{{ $label }}" type="button">
        <span class="ai-pulse" aria-hidden="true"></span>
        <i class="bi bi-mic-fill" aria-hidden="true"></i>
        <span class="ai-pill-text">Voice Search</span>
    </button>
@else
    <button aria-label="{{ $label }}" class="fm-ai-icon-btn ai-inline-btn" data-fm-open="voice" title="{{ $label }}" type="button">
        <i class="bi bi-mic-fill" aria-hidden="true"></i>
    </button>
@endif