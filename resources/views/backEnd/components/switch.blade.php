{{--
    backEnd::switch - an on/off control with a label that is part of the
    control, not a floating word beside it.

    The controller reads an unchecked box as false; no hidden "0" input is
    emitted, because a hidden 0 plus a checked 1 makes the request carry both
    values and leaves the interpretation to whichever one wins.
--}}
@props([
    'name',
    'label' => 'Enabled',
    'checked' => false,
    'value' => '1',
    'id' => null,
    'help' => null,
])

@php
    $fieldId = $id ?: 'switch-' . \Illuminate\Support\Str::slug($name, '-');
    $isChecked = (bool) old($name, $checked);
@endphp

<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" role="switch"
           name="{{ $name }}" id="{{ $fieldId }}" value="{{ $value }}"
           @checked($isChecked)>
    <label class="form-check-label" for="{{ $fieldId }}">{{ $label }}</label>
</div>

@if($help)
    <div class="form-text">{{ $help }}</div>
@endif
