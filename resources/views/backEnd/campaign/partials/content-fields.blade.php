{{--
    Template-specific landing content.

    Fields come from config/landing_templates.php through
    LandingTemplateVars::contentFieldsFor(): only content variables enabled for
    the selected template are shown, and repeater columns follow the same
    toggle list. Product, campaign and system values are resolved by the
    template and are never typed here.

    The request is whitelisted again by App\Services\CampaignContent.
--}}
@php
    $registry = (array) config('landing_templates', []);

    $storedExtra = old('extra_fields', $edit_data->extra_fields ?? []);
    if (!is_array($storedExtra)) {
        $storedExtra = [];
    }

    $supportedFields = array_merge(
        \App\Services\CampaignContent::TEXT_FIELDS,
        array_keys(\App\Services\CampaignContent::ROW_FIELDS)
    );

    $templateFieldMap = $templates->mapWithKeys(function ($template) use ($supportedFields) {
        $fields = \App\Support\LandingTemplateVars::contentFieldsFor($template);
        $fieldKeys = [];
        $columns = [];

        foreach ($fields as $field => $definition) {
            $fieldKeys[] = $field;

            if (($definition['type'] ?? '') === 'rows') {
                $columns[$field] = array_keys((array) ($definition['columns'] ?? []));
            }
        }

        return [(string) $template->id => [
            'fields' => array_values(array_intersect($fieldKeys, $supportedFields)),
            'columns' => $columns,
        ]];
    });

    // Scalar content fields plus one definition per repeater. The registry is
    // the only source: legacy hard-coded Feature/Benefit/Testimonial rows are
    // no longer part of any active design.
    $textDefinitions = [];
    $rowDefinitions = [];

    foreach ((array) ($registry['groups'] ?? []) as $group) {
        foreach ((array) ($group['variables'] ?? []) as $key => $definition) {
            if (($definition['source'] ?? '') !== 'content') {
                continue;
            }

            $repeater = $definition['repeater'] ?? null;

            if ($repeater) {
                if (!isset($rowDefinitions[$repeater])) {
                    $rowDefinitions[$repeater] = [
                        'label' => $registry['repeaters'][$repeater]['label'] ?? $repeater,
                        'columns' => [],
                    ];
                }

                $column = $definition['column'] ?? null;
                if ($column && !isset($rowDefinitions[$repeater]['columns'][$column])) {
                    $columnDefinition = $registry['repeaters'][$repeater]['columns'][$column] ?? [];
                    $rowDefinitions[$repeater]['columns'][$column] = [
                        'key' => $column,
                        'label' => $columnDefinition['label'] ?? ($definition['label'] ?? $column),
                        'type' => $columnDefinition['type'] ?? 'text',
                        'class' => $columnDefinition['class'] ?? 'col-md-4',
                    ] + (isset($columnDefinition['step']) ? ['step' => $columnDefinition['step']] : []);
                }

                continue;
            }

            $textDefinitions[$key] = [
                'label' => $definition['label'] ?? $key,
                'type' => ($definition['type'] ?? 'text') === 'textarea' ? 'textarea' : ($definition['type'] ?? 'text'),
            ];
        }
    }
@endphp

<div class="col-12" id="landingContentPanel" data-template-fields='@json($templateFieldMap)'>
    <div class="border rounded p-3 mb-3">
        <h6 class="text-uppercase fw-bold mb-3">Landing Content</h6>

        <p class="text-muted small mb-0 d-none" id="landingContentEmpty">
            This template does not use extra content fields.
        </p>

        <div class="row g-3 mb-2">
            @foreach($textDefinitions as $field => $definition)
                @php
                    $value = $storedExtra[$field] ?? '';
                    $value = is_scalar($value) ? (string) $value : '';
                    if ($field === 'countdown_date' && $value !== '') {
                        $value = str_replace(' ', 'T', substr($value, 0, 16));
                    }
                @endphp
                <div class="col-md-4 landing-content-field" data-field="{{ $field }}">
                    <label class="form-label" for="extra_{{ $field }}">{{ $definition['label'] }}</label>
                    @if(($definition['type'] ?? 'text') === 'textarea')
                        <textarea
                            class="form-control rounded-0 @error('extra_fields.' . $field) is-invalid @enderror"
                            id="extra_{{ $field }}"
                            name="extra_fields[{{ $field }}]"
                            rows="2"
                        >{{ $value }}</textarea>
                    @else
                        <input
                            type="{{ $definition['type'] }}"
                            class="form-control rounded-0 @error('extra_fields.' . $field) is-invalid @enderror"
                            id="extra_{{ $field }}"
                            name="extra_fields[{{ $field }}]"
                            value="{{ $value }}"
                        >
                    @endif
                    @error('extra_fields.' . $field)
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            @endforeach
        </div>

        @foreach($rowDefinitions as $field => $definition)
            <div class="landing-content-field border rounded p-3 mb-3" data-field="{{ $field }}">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <h6 class="mb-0">{{ $definition['label'] }}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary js-add-row" data-field="{{ $field }}">
                        Add row
                    </button>
                </div>

                <div class="js-rows" data-field="{{ $field }}">
                    @php $rows = $storedExtra[$field] ?? []; @endphp
                    @if(is_array($rows))
                        @foreach($rows as $index => $row)
                            @continue(!is_array($row))
                            <div class="row g-2 align-items-end border rounded p-2 mb-2 js-row">
                                @foreach($definition['columns'] as $column)
                                    @php $cell = $row[$column['key']] ?? ''; @endphp
                                    <div class="{{ $column['class'] }}" data-column="{{ $column['key'] }}">
                                        <label class="form-label small mb-1">{{ $column['label'] }}</label>
                                        @if($column['type'] === 'textarea')
                                            <textarea
                                                rows="2"
                                                class="form-control rounded-0"
                                                name="extra_fields[{{ $field }}][{{ $index }}][{{ $column['key'] }}]"
                                            >{{ is_scalar($cell) ? $cell : '' }}</textarea>
                                        @else
                                            <input
                                                type="{{ $column['type'] }}"
                                                @isset($column['step']) step="{{ $column['step'] }}" @endisset
                                                class="form-control rounded-0"
                                                name="extra_fields[{{ $field }}][{{ $index }}][{{ $column['key'] }}]"
                                                value="{{ is_scalar($cell) ? $cell : '' }}"
                                            >
                                        @endif
                                    </div>
                                @endforeach
                                <div class="col-auto">
                                    <button type="button" class="btn btn-sm btn-outline-danger js-remove-row">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <template class="js-row-template" data-template-field="{{ $field }}">
                    <div class="row g-2 align-items-end border rounded p-2 mb-2 js-row">
                        @foreach($definition['columns'] as $column)
                            <div class="{{ $column['class'] }}" data-column="{{ $column['key'] }}">
                                <label class="form-label small mb-1">{{ $column['label'] }}</label>
                                @if($column['type'] === 'textarea')
                                    <textarea
                                        rows="2"
                                        class="form-control rounded-0"
                                        name="extra_fields[{{ $field }}][__INDEX__][{{ $column['key'] }}]"
                                    ></textarea>
                                @else
                                    <input
                                        type="{{ $column['type'] }}"
                                        @isset($column['step']) step="{{ $column['step'] }}" @endisset
                                        class="form-control rounded-0"
                                        name="extra_fields[{{ $field }}][__INDEX__][{{ $column['key'] }}]"
                                        value=""
                                    >
                                @endif
                            </div>
                        @endforeach
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-danger js-remove-row">Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        @endforeach
    </div>
</div>
