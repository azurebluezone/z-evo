{{--
    backEnd::button

    =========================================================================
    The admin's one button.

    WHY THIS EXISTS
    ---------------
    683 buttons outside the admin layouts carry 177 different class strings
    between them, and the same combination is typed out over and over:
    "btn btn-primary btn-square waves-effect" 65 times, "btn btn-primary" 59
    times, "btn btn-sm btn-icon btn-text-secondary" 33 times. Every one of
    those is the same three decisions - which colour, which size, which shape -
    written out longhand.

    This component owns the decision. A call site says what it MEANS:

        <x-admin.button variant="primary" shape="square">Save</x-admin.button>

    and the class list is built here, once.

    NOTHING IS RESTYLED
    -------------------
    The component emits exactly the class vocabulary the views already used -
    .btn, .btn-primary, .btn-sm, .btn-square, .btn-icon, .waves-effect - so
    converting a call site cannot change how a single button looks. That is
    deliberate: the value here is that the 683 call sites become readable and
    that the admin's primary colour can be changed in one file, not that the
    admin gets a new face.

    Usage
        <x-admin.button>Save</x-admin.button>
        <x-admin.button variant="outline-secondary" size="sm">Cancel</x-admin.button>
        <x-admin.button variant="text-danger" shape="icon" size="sm"
                        class="delete-confirm" title="Delete" onclick="return confirmDel()">
            <i class="ri-delete-bin-line"></i>
        </x-admin.button>
        <x-admin.button href="{{ route('orders.index') }}">All orders</x-admin.button>

    Props
        variant   string  primary (default) | secondary | outline-secondary |
                         success | danger | warning | info | light | dark |
                         text-danger | text-warning | text-success |
                         text-secondary | link
        size      string  sm | md (default) | lg
        shape     string  '' (default) | square | icon | "icon square"
        href      string  when set, renders an <a> instead of a <button>
        type      string  button (default) | submit | reset
        ripple    bool    default false. Materialize's .waves-effect ripple
                         animation. OFF unless a call site asked for it: adding
                         it to a button that never had it changes how that screen
                         looks, which is not what a refactor is allowed to do.
        class     string  extra classes (delete-confirm, layout utilities, ...);
                          classes a script binds to MUST go here so they keep
                          working after the conversion

    BEHAVIOUR HOOKS STAY ON THE CALL SITE
    --------------------------------------
    delete-confirm, change-confirm and any data-* attribute are wired up by the
    admin's JavaScript, not by the theme. They are passed through untouched -
    the component never invents or renames them.
--}}

{{--
    @props is what removes a named attribute from $attributes. Without it the
    component would emit variant="primary" shape="square" href="/x" into the
    rendered tag, and a class passed in would appear twice - once from
    $attributes and once from the $class variable.
--}}
@props([
    'variant' => 'primary',
    'size'    => 'md',
    'shape'   => '',
    'href'    => null,
    'type'    => 'button',
    'ripple'  => false,
    'class'   => '',
])

@php
    $adminBtnVariants = [
        'primary'          => 'btn-primary',
        'secondary'        => 'btn-secondary',
        'outline-primary'  => 'btn-outline-primary',
        'outline-secondary'=> 'btn-outline-secondary',
        'outline-success'  => 'btn-outline-success',
        'outline-danger'   => 'btn-outline-danger',
        'outline-warning'  => 'btn-outline-warning',
        'outline-info'     => 'btn-outline-info',
        'outline-light'    => 'btn-outline-light',
        'outline-dark'     => 'btn-outline-dark',
        'success'          => 'btn-success',
        'danger'           => 'btn-danger',
        'warning'          => 'btn-warning',
        'info'             => 'btn-info',
        'light'            => 'btn-light',
        'dark'             => 'btn-dark',
        'link'             => 'btn-link',
        'text-primary'     => 'btn-text-primary',
        'text-secondary'   => 'btn-text-secondary',
        'text-danger'      => 'btn-text-danger',
        'text-warning'     => 'btn-text-warning',
        'text-success'     => 'btn-text-success',
        'text-info'        => 'btn-text-info',
        'text-light'       => 'btn-text-light',
        'text-dark'        => 'btn-text-dark',
    ];

    $adminBtnSizes = ['sm' => 'btn-sm', 'md' => '', 'lg' => 'btn-lg'];
    $adminBtnShapes = ['square' => 'btn-square', 'icon' => 'btn-icon'];

    /*
     * An EMPTY variant means "print no colour class".
     *
     * A project class such as .btn-blue or .btn-label-secondary is somebody
     * else's decision about colour. When the converter meets one it must not
     * help itself to the default green on top - two colour classes on one
     * button is a fight neither wins. A misspelled variant still falls back to
     * primary, because that is a typo worth papering over; an explicitly empty
     * one is a statement.
     */
    $adminBtnVariant = $variant === ''
        ? ''
        : ($adminBtnVariants[$variant ?? 'primary'] ?? $adminBtnVariants['primary']);
    $adminBtnSize    = $adminBtnSizes[$size ?? 'md'] ?? '';

    /*
     * shape can be more than one word.
     *
     * The views carry "btn-icon btn-square" together 23 times - an icon button
     * that is also square - so a single-value shape silently dropped one of
     * the two classes and changed the button's width. "icon square" now
     * reproduces both.
     */
    $adminBtnShape = collect(preg_split('/[\s,]+/', (string) ($shape ?? '')))
        ->map(fn ($one) => $adminBtnShapes[$one] ?? '')
        ->filter()
        ->all();

    $adminBtnClasses = implode(' ', array_filter([
        'btn',
        $adminBtnVariant,
        $adminBtnSize,
        ...$adminBtnShape,
        $ripple ? 'waves-effect' : '',
        trim($class ?? ''),
    ]));
@endphp

@if (!empty($href))
    <a {{ $attributes->merge(['class' => $adminBtnClasses]) }} href="{{ $href }}">{{ $slot }}</a>
@else
    <button {{
        $attributes->merge([
            'type'     => $type ?? 'button',
            'class'    => $adminBtnClasses,
            'disabled' => $disabled ?? false,
        ])
    }}>{{ $slot }}</button>
@endif