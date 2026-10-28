{{--
    backEnd::input - the admin's one text field.

    A field is not just an <input>: it needs a label tied to the control with
    for/id, the previous value after a failed submit, the validation message
    directly beneath it, and an optional hint. Those five things were retyped
    by hand on every form, and the copies had drifted (labels without `for`,
    errors printed in a different position, ids missing entirely).

    Usage
        <x-admin.input name="api_key" label="API key" :value="$courier->api_key" required
                       help="Found in the courier portal under API settings." />

    Props
        name         string  field name; also the default id and the error key
        label        string  visible label
        type         string  text (default) | email | password | url | number | date | color | file
        value        mixed   current value; old() wins after a failed submit
        id           string  override the generated id
        help         string  small text under the control
        required     bool    adds the required attribute and a * marker
        placeholder  string
        autocomplete string

    Any other attribute (readonly, min, max, step, data-*) is passed through.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'id' => null,
    'help' => null,
    'required' => false,
    'placeholder' => null,
    'autocomplete' => null,
])

@php
    $fieldId = $id ?: 'field-' . \Illuminate\Support\Str::slug($name, '-');
    $fieldValue = old($name, $value);
    $hasError = $errors->has($name);
@endphp

<label for="{{ $fieldId }}" class="form-label">
    {{ $label }}@if($required)<span class="text-danger ms-1" aria-hidden="true">*</span>@endif
</label>

<input
    {{ $attributes->merge([
        'type' => $type,
        'name' => $name,
        'id' => $fieldId,
        'value' => $fieldValue,
        'class' => 'form-control' . ($hasError ? ' is-invalid' : ''),
        'placeholder' => $placeholder,
        'autocomplete' => $autocomplete,
        'required' => $required,
        'aria-invalid' => $hasError ? 'true' : 'false',
    ]) }}
>

@error($name)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror

@if($help)
    <div class="form-text">{{ $help }}</div>
@endif
