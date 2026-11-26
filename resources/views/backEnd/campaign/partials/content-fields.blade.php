{{--
    Template-specific landing content.

    Fields come from config/landing_templates.php through
    LandingTemplateVars::contentFieldsFor(): only content variables enabled for
    the selected template are shown, and repeater columns follow the same
    toggle list. Product, campaign and system values are resolved by the
    template and are never typed here.

    Image columns post a file under extra_fields_files[...] and keep the stored
    path in a hidden extra_fields[...] input; CampaignController merges the
    uploaded path before CampaignContent::normalize() runs.

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

    // Video URL and caption have a dedicated block in both campaign forms.
    // Skip them here so the same input is never rendered twice.
    $dedicatedFields = ['video_url', 'video_caption'];

    foreach ((array) ($registry['groups'] ?? []) as $group) {
        foreach ((array) ($group['variables'] ?? []) as $key => $definition) {
            if (($definition['source'] ?? '') !== 'content' || in_array($key, $dedicatedFields, true)) {
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
                        'options' => (array) ($columnDefinition['options'] ?? []),
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
        <h6 class="text-uppercase fw-bold mb-3"><i class="ri ri-layout-masonry-line me-1"></i> Landing Content</h6>

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
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <h6 class="mb-0">{{ $definition['label'] }}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary js-add-row" data-field="{{ $field }}">
                        <i class="ri ri-add-line me-1"></i> Add row
                    </button>
                </div>

                <div class="js-rows" data-field="{{ $field }}">
                    @php $rows = $storedExtra[$field] ?? []; @endphp
                    @if(is_array($rows))
                        @foreach($rows as $index => $row)
                            @continue(!is_array($row))
                            <div class="border rounded p-2 mb-2 js-row position-relative">
                                <button type="button" class="btn btn-sm btn-outline-danger js-remove-row position-absolute top-0 end-0 m-1" title="Remove row" aria-label="Remove row">
                                    <i class="ri ri-delete-bin-line"></i>
                                </button>
                                <div class="row g-2 align-items-start pe-5">
                                @foreach($definition['columns'] as $column)
                                    @php
                                        $cell = $row[$column['key']] ?? '';
                                        $cellValue = is_scalar($cell) ? trim((string) $cell) : '';
                                        $preview = '';
                                        if ($cellValue !== '') {
                                            $preview = preg_match('~^https?://~i', $cellValue)
                                                ? $cellValue
                                                : asset(preg_replace('~^public/~', '', ltrim($cellValue, '/')));
                                        }
                                    @endphp
                                    <div class="{{ $column['class'] }}" data-column="{{ $column['key'] }}">
                                        <label class="form-label small mb-1">{{ $column['label'] }}</label>
                                        @if($column['type'] === 'textarea')
                                            <input
                                                type="hidden"
                                                id="extra_{{ $field }}_{{ $index }}_{{ $column['key'] }}"
                                                name="extra_fields[{{ $field }}][{{ $index }}][{{ $column['key'] }}]"
                                                value="{{ $cellValue }}"
                                            >
                                            <div
                                                id="quill_{{ $field }}_{{ $index }}_{{ $column['key'] }}"
                                                class="js-quill-editor"
                                                data-quill-theme="snow"
                                                data-quill-toolbar-preset="mini"
                                                data-quill-target="#extra_{{ $field }}_{{ $index }}_{{ $column['key'] }}"
                                                data-quill-placeholder="{{ $column['label'] }}"
                                                data-quill-initial-html="{{ e($cellValue) }}"
                                                style="min-height: 88px;"></div>
                                        @elseif($column['type'] === 'icon')
                                            @php
                                                $iconOptions = (array) ($column['options'] ?? []);
                                                $iconValue = $cellValue;
                                                $iconPreview = $iconValue !== '' ? $iconValue : (string) array_key_first($iconOptions);
                                                $iconLabel = $iconOptions[$iconValue] ?? ($iconValue !== '' ? 'Custom: ' . $iconValue : (string) reset($iconOptions));
                                            @endphp
                                            <select
                                                class="form-select form-select-sm rounded-0 js-icon-select"
                                                name="extra_fields[{{ $field }}][{{ $index }}][{{ $column['key'] }}]"
                                            >
                                                @foreach($iconOptions as $iconClass => $iconOptionLabel)
                                                    <option value="{{ $iconClass }}" @selected($iconValue === $iconClass)>{{ $iconOptionLabel }}</option>
                                                @endforeach
                                                @if($iconValue !== '' && !array_key_exists($iconValue, $iconOptions))
                                                    <option value="{{ $iconValue }}" selected>Custom: {{ $iconValue }}</option>
                                                @endif
                                            </select>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <i class="{{ $iconPreview }} js-icon-preview" aria-hidden="true"></i>
                                                <span class="small text-body-secondary js-icon-preview-label">{{ $iconLabel }}</span>
                                            </div>
                                        @elseif($column['type'] === 'image')
                                            <input
                                                type="hidden"
                                                name="extra_fields[{{ $field }}][{{ $index }}][{{ $column['key'] }}]"
                                                value="{{ $cellValue }}"
                                            >
                                            <input
                                                type="file"
                                                class="form-control form-control-sm rounded-0 js-repeater-image"
                                                name="extra_fields_files[{{ $field }}][{{ $index }}][{{ $column['key'] }}]"
                                                accept="image/png,image/jpeg,image/gif,image/webp"
                                            >
                                            <img
                                                src="{{ $preview }}"
                                                alt=""
                                                class="js-repeater-image-preview mt-1 rounded border {{ $preview === '' ? 'd-none' : '' }}"
                                                style="height: 38px; width: 38px; object-fit: cover;"
                                            >
                                        @else
                                            <input
                                                type="{{ $column['type'] }}"
                                                @isset($column['step']) step="{{ $column['step'] }}" @endisset
                                                class="form-control form-control-sm rounded-0"
                                                name="extra_fields[{{ $field }}][{{ $index }}][{{ $column['key'] }}]"
                                                value="{{ $cellValue }}"
                                            >
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>

                <template class="js-row-template" data-template-field="{{ $field }}">
                    <div class="border rounded p-2 mb-2 js-row position-relative">
                        <button type="button" class="btn btn-sm btn-outline-danger js-remove-row position-absolute top-0 end-0 m-1" title="Remove row" aria-label="Remove row">
                            <i class="ri ri-delete-bin-line"></i>
                        </button>
                        <div class="row g-2 align-items-start pe-5">
                        @foreach($definition['columns'] as $column)
                            <div class="{{ $column['class'] }}" data-column="{{ $column['key'] }}">
                                <label class="form-label small mb-1">{{ $column['label'] }}</label>
                                @if($column['type'] === 'textarea')
                                    <input
                                        type="hidden"
                                        id="extra_{{ $field }}_{{ '__INDEX__' }}_{{ $column['key'] }}"
                                        name="extra_fields[{{ $field }}][{{ '__INDEX__' }}][{{ $column['key'] }}]"
                                        value=""
                                    >
                                    <div
                                        id="quill_{{ $field }}_{{ '__INDEX__' }}_{{ $column['key'] }}"
                                        class="js-quill-editor"
                                        data-quill-theme="snow"
                                        data-quill-toolbar-preset="mini"
                                        data-quill-target="#extra_{{ $field }}_{{ '__INDEX__' }}_{{ $column['key'] }}"
                                        data-quill-placeholder="{{ $column['label'] }}"
                                        data-quill-initial-html=""
                                        style="min-height: 88px;"></div>
                                @elseif($column['type'] === 'icon')
                                    @php
                                        $iconOptions = (array) ($column['options'] ?? []);
                                        $iconValue = '';
                                        $iconPreview = (string) array_key_first($iconOptions);
                                        $iconLabel = (string) reset($iconOptions);
                                    @endphp
                                    <select
                                        class="form-select form-select-sm rounded-0 js-icon-select"
                                        name="extra_fields[{{ $field }}][{{ '__INDEX__' }}][{{ $column['key'] }}]"
                                    >
                                        @foreach($iconOptions as $iconClass => $iconOptionLabel)
                                            <option value="{{ $iconClass }}">{{ $iconOptionLabel }}</option>
                                        @endforeach
                                    </select>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <i class="{{ $iconPreview }} js-icon-preview" aria-hidden="true"></i>
                                        <span class="small text-body-secondary js-icon-preview-label">{{ $iconLabel }}</span>
                                    </div>
                                @elseif($column['type'] === 'image')
                                    <input
                                        type="hidden"
                                        name="extra_fields[{{ $field }}][{{ '__INDEX__' }}][{{ $column['key'] }}]"
                                        value=""
                                    >
                                    <input
                                        type="file"
                                        class="form-control form-control-sm rounded-0 js-repeater-image"
                                        name="extra_fields_files[{{ $field }}][{{ '__INDEX__' }}][{{ $column['key'] }}]"
                                        accept="image/png,image/jpeg,image/gif,image/webp"
                                    >
                                    <img
                                        src=""
                                        alt=""
                                        class="js-repeater-image-preview mt-1 rounded border d-none"
                                        style="height: 38px; width: 38px; object-fit: cover;"
                                    >
                                @else
                                    <input
                                        type="{{ $column['type'] }}"
                                        @isset($column['step']) step="{{ $column['step'] }}" @endisset
                                        class="form-control form-control-sm rounded-0"
                                        name="extra_fields[{{ $field }}][{{ '__INDEX__' }}][{{ $column['key'] }}]"
                                        value=""
                                    >
                                @endif
                            </div>
                        @endforeach
                        </div>
                    </div>
                </template>
            </div>
        @endforeach
    </div>
</div>
