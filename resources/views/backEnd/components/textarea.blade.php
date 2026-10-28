{{-- backEnd::textarea - the admin's one multiline field. --}}
@props([
    'name',
    'label',
    'value' => null,
    'id' => null,
    'help' => null,
    'required' => false,
    'rows' => 4,
])

@php
    $fieldId = $id ?: 'field-' . \Illuminate\Support\Str::slug($name, '-');
    $hasError = $errors->has($name);
@endphp

<label for="{{ $fieldId }}" class="form-label">
    {{ $label }}@if($required)<span class="text-danger ms-1" aria-hidden="true">*</span>@endif
</label>

<textarea
    {{ $attributes->merge([
        'name' => $name,
        'id' => $fieldId,
        'rows' => $rows,
        'class' => 'form-control' . ($hasError ? ' is-invalid' : ''),
        'required' => $required,
        'aria-invalid' => $hasError ? 'true' : 'false',
    ]) }}
>{{ old($name, $value) }}</textarea>

@error($name)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror

@if($help)
    <div class="form-text">{{ $help }}</div>
@endif
