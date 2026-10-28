{{--
    backEnd::select - the admin's one dropdown.

    Same contract as <x-admin.input>: label tied with for/id, old() wins,
    validation message under the control. The blank option is a real option
    ("Choose…"), not a disabled placeholder, because every caller here treats
    an empty value as "no filter".

    Usage
        <x-admin.select name="status" label="Status" :options="$statuses"
                        :selected="request('status')" placeholder="All statuses" />
--}}
@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'id' => null,
    'help' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $fieldId = $id ?: 'field-' . \Illuminate\Support\Str::slug($name, '-');
    $fieldValue = old($name, $selected);
    $hasError = $errors->has($name);
@endphp

<label for="{{ $fieldId }}" class="form-label">
    {{ $label }}@if($required)<span class="text-danger ms-1" aria-hidden="true">*</span>@endif
</label>

<select
    {{ $attributes->merge([
        'name' => $name,
        'id' => $fieldId,
        'class' => 'form-select' . ($hasError ? ' is-invalid' : ''),
        'required' => $required,
        'aria-invalid' => $hasError ? 'true' : 'false',
    ]) }}
>
    @if($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @selected((string) $fieldValue === (string) $optionValue)>
            {{ $optionLabel }}
        </option>
    @endforeach
</select>

@error($name)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror

@if($help)
    <div class="form-text">{{ $help }}</div>
@endif
