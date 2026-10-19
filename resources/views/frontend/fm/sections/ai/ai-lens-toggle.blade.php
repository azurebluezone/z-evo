{{--
    FM:COMPONENT ai/ai-lens-toggle
    =========================================================================
    The "AI Lens" pill from the reference header (#ai-scanner-drawer-toggle):
    a pulsing status dot, the sparkle glyph and a label that hides on phones.

    The design opened the drawer with a bare openAiDrawer(). Here it carries the
    same data-fm-open hook the two inline triggers use, with the mode as a
    prop, so one drawer serves all three entry points and fm-ai.js stays the
    only thing that knows how it opens.

    Props
        mode    string  'camera' (default) or 'voice'
        label   string  visible pill text, default "AI Lens"

    The dot is decorative and hidden from assistive tech; the button carries the
    accessible name.
--}}

@php
    $mode  = $mode ?? 'camera';
    $label = $label ?? 'AI Lens';
@endphp

<button aria-label="Open {{ $label }}" class="ai-lens-toggle" data-fm-open="{{ $mode }}" title="{{ $label }}" type="button">
    <span class="ai-lens-toggle__dot" aria-hidden="true">
        <span class="ai-lens-toggle__ping"></span>
        <span class="ai-lens-toggle__core"></span>
    </span>
    <i class="bi bi-stars ai-lens-toggle__icon" aria-hidden="true"></i>
    <span class="ai-lens-toggle__text">{{ $label }}</span>
</button>