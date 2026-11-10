{{--
    Template-specific landing content.

    The fields are generated from templates.config.extra_fields, but the
    request is whitelisted again by App\Services\CampaignContent. Product
    title/description/image/gallery and prices are intentionally absent: the
    controller merges those under the stored content.
--}}
@php
    $storedExtra = old('extra_fields', $edit_data->extra_fields ?? []);
    if (!is_array($storedExtra)) {
        $storedExtra = [];
    }

    $supportedFields = array_merge(
        \App\Services\CampaignContent::TEXT_FIELDS,
        array_keys(\App\Services\CampaignContent::ROW_FIELDS)
    );

    $templateFieldMap = $templates->mapWithKeys(function ($template) use ($supportedFields) {
        $configured = $template->config['extra_fields'] ?? [];

        // template-3 describes the three links as one "social_links" entry;
        // the form and the template read facebook/instagram/youtube separately.
        $expanded = [];
        foreach ($configured as $field) {
            if ($field === 'social_links') {
                array_push($expanded, 'facebook', 'instagram', 'youtube');
            } else {
                $expanded[] = $field;
            }
        }

        return [(string) $template->id => array_values(array_intersect($expanded, $supportedFields))];
    });

    $textDefinitions = [
        'video_url' => ['label' => 'Video URL', 'type' => 'url', 'hint' => 'Embed URL used by template 3.'],
        'countdown_date' => ['label' => 'Countdown fallback', 'type' => 'datetime-local', 'hint' => 'Only used when the campaign has no end date.'],
        'contact_phone' => ['label' => 'Contact phone', 'type' => 'text', 'hint' => 'Falls back to the store phone.'],
        'email' => ['label' => 'Email', 'type' => 'email', 'hint' => 'Footer contact email.'],
        'facebook' => ['label' => 'Facebook URL', 'type' => 'url', 'hint' => 'Template 3 footer.'],
        'instagram' => ['label' => 'Instagram URL', 'type' => 'url', 'hint' => 'Template 3 footer.'],
        'youtube' => ['label' => 'YouTube URL', 'type' => 'url', 'hint' => 'Template 3 footer.'],
    ];

    $rowDefinitions = [
        'features' => [
            'label' => 'Features',
            'hint' => 'Template 1 and 2 use the title; template 3 and sample also render description/image.',
            'columns' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'class' => 'col-md-4'],
                ['key' => 'description', 'label' => 'Description', 'type' => 'textarea', 'class' => 'col-md-5'],
                ['key' => 'image', 'label' => 'Image URL', 'type' => 'text', 'class' => 'col-md-3'],
            ],
        ],
        'benefits' => [
            'label' => 'Benefits',
            'hint' => 'Herbal Health uses icon/title/text; other templates accept the same rows.',
            'columns' => [
                ['key' => 'icon', 'label' => 'Icon class', 'type' => 'text', 'class' => 'col-md-3'],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'class' => 'col-md-4'],
                ['key' => 'text', 'label' => 'Text', 'type' => 'textarea', 'class' => 'col-md-5'],
            ],
        ],
        'testimonials' => [
            'label' => 'Testimonials',
            'hint' => 'The comment is shown as text; rating is 1 to 5.',
            'columns' => [
                ['key' => 'name', 'label' => 'Customer name', 'type' => 'text', 'class' => 'col-md-3'],
                ['key' => 'text', 'label' => 'Comment', 'type' => 'textarea', 'class' => 'col-md-5'],
                ['key' => 'rating', 'label' => 'Rating', 'type' => 'number', 'step' => '1', 'class' => 'col-md-2'],
            ],
        ],
        'faqs' => [
            'label' => 'FAQ',
            'hint' => 'Question and answer pairs. supported by sample-template.',
            'columns' => [
                ['key' => 'question', 'label' => 'Question', 'type' => 'text', 'class' => 'col-md-4'],
                ['key' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'class' => 'col-md-7'],
            ],
        ],
        'why_us' => [
            'label' => 'Why choose us',
            'hint' => 'One short reason per row. supported by template 1 and 2.',
            'columns' => [
                ['key' => 'text', 'label' => 'Reason', 'type' => 'textarea', 'class' => 'col-md-11'],
            ],
        ],
        'packages' => [
            'label' => 'Packages',
            'hint' => 'Display-only packages for template 3. Prices are not used for checkout.',
            'columns' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'class' => 'col-md-4'],
                ['key' => 'price', 'label' => 'Price', 'type' => 'number', 'step' => '0.01', 'class' => 'col-md-3'],
                ['key' => 'image', 'label' => 'Image URL', 'type' => 'text', 'class' => 'col-md-4'],
            ],
        ],
    ];
@endphp

<div class="col-12" id="landingContentPanel" data-template-fields='@json($templateFieldMap)'>
    <div class="border rounded p-3 mb-3">
        <h6 class="text-uppercase fw-bold mb-1">Landing Content</h6>
        <p class="text-muted small mb-3">
            Fields follow the selected template. Empty rows are skipped; product title, description and images always
            come from the product record.
        </p>

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
                    <input
                        type="{{ $definition['type'] }}"
                        class="form-control @error('extra_fields.' . $field) is-invalid @enderror"
                        id="extra_{{ $field }}"
                        name="extra_fields[{{ $field }}]"
                        value="{{ $value }}"
                    >
                    <small class="text-muted">{{ $definition['hint'] }}</small>
                    @error('extra_fields.' . $field)
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            @endforeach
        </div>

        @foreach($rowDefinitions as $field => $definition)
            <div class="landing-content-field border rounded p-3 mb-3" data-field="{{ $field }}">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <h6 class="mb-0">{{ $definition['label'] }}</h6>
                        <small class="text-muted">{{ $definition['hint'] }}</small>
                    </div>
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
                                    <div class="{{ $column['class'] }}">
                                        <label class="form-label small mb-1">{{ $column['label'] }}</label>
                                        @if($column['type'] === 'textarea')
                                            <textarea
                                                rows="2"
                                                class="form-control"
                                                name="extra_fields[{{ $field }}][{{ $index }}][{{ $column['key'] }}]"
                                            >{{ is_scalar($cell) ? $cell : '' }}</textarea>
                                        @else
                                            <input
                                                type="{{ $column['type'] }}"
                                                @isset($column['step']) step="{{ $column['step'] }}" @endisset
                                                class="form-control"
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
                            <div class="{{ $column['class'] }}">
                                <label class="form-label small mb-1">{{ $column['label'] }}</label>
                                @if($column['type'] === 'textarea')
                                    <textarea
                                        rows="2"
                                        class="form-control"
                                        name="extra_fields[{{ $field }}][__INDEX__][{{ $column['key'] }}]"
                                    ></textarea>
                                @else
                                    <input
                                        type="{{ $column['type'] }}"
                                        @isset($column['step']) step="{{ $column['step'] }}" @endisset
                                        class="form-control"
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
