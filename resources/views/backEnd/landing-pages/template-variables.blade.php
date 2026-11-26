@extends('backEnd.layouts.app')

@section('title')
    Template Variables
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('admin/assets/css/admin-page-template-variables.css') }}" />
@endsection

@section('content')
@php
    $sourceColors = [
        'product' => 'primary',
        'campaign' => 'info',
        'system' => 'secondary',
        'content' => 'success',
        'auto' => 'warning',
        'removed' => 'danger',
    ];
@endphp

<form method="POST" action="{{ route('landing-pages.template-variables.update', $template->id) }}">
    @csrf

    <div class="card rounded-0">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1 fw-bold">{{ $displayName }}</h5>
                <div class="text-muted small">{{ count($enabled) }} of {{ $totalVariables }} variables enabled</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('landing-pages.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ri ri-save-line me-1"></i> Save
                </button>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Checked variables are saved in this template's config and appear in the campaign form.</p>

            <div class="row g-3">
                @foreach($groups as $groupKey => $group)
                    <div class="col-12">
                        <div class="card rounded-0 template-variables-card">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label d-block fw-semibold mb-2" style="margin-bottom:0 !important;" for="group_{{ $groupKey }}">
                                        {{ $group['label'] ?? $groupKey }}
                                    </label>
                                    <span class="badge bg-dark">{{ count((array) ($group['variables'] ?? [])) }}</span>
                                </div>
                                <div class="form-check form-switch custom-switch-sharp template-variable-switch mb-0">
                                    <input type="checkbox" class="form-check-input rounded-0 js-group-toggle"
                                           id="group_{{ $groupKey }}" data-group="{{ $groupKey }}">
                                    <label class="form-check-label visually-hidden" for="group_{{ $groupKey }}">Enable</label>
                                </div>
                            </div>
                            <div class="card-body">
                                @foreach((array) ($group['variables'] ?? []) as $key => $definition)
                                    @php
                                        $isEnabled = in_array($key, $enabled, true);
                                        $source = $definition['source'] ?? 'content';
                                        $isRemoved = $source === 'removed';
                                        $repeater = $definition['repeater'] ?? null;
                                    @endphp
                                    <div class="template-variable-row">
                                        <label class="template-variable-label" for="var_{{ $key }}"
                                               title="source: {{ $source }}{{ $repeater ? ' · row: ' . $repeater : '' }}">
                                            <span class="fw-semibold">{{ $definition['label'] ?? $key }}</span>
                                        </label>
                                        <div class="form-check form-switch custom-switch-sharp template-variable-switch mb-0">
                                            <input type="checkbox"
                                                   class="form-check-input rounded-0 js-variable"
                                                   id="var_{{ $key }}"
                                                   name="enabled_variables[]"
                                                   value="{{ $key }}"
                                                   data-group="{{ $groupKey }}"
                                                   aria-label="{{ $definition['label'] ?? $key }}"
                                                   {{ $isEnabled ? 'checked' : '' }}
                                                   {{ $isRemoved ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ri ri-save-line me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('script')
<script>
    (function () {
        'use strict';

        function syncGroup(groupKey) {
            var group = document.querySelector('.js-group-toggle[data-group="' + groupKey + '"]');
            var variables = document.querySelectorAll('.js-variable[data-group="' + groupKey + '"]:not(:disabled)');

            if (!group || !variables.length) {
                return;
            }

            var checked = 0;
            variables.forEach(function (input) {
                if (input.checked) {
                    checked += 1;
                }
            });

            group.checked = checked === variables.length;
            group.indeterminate = checked > 0 && checked < variables.length;
        }

        document.querySelectorAll('.js-group-toggle').forEach(function (group) {
            var groupKey = group.getAttribute('data-group');

            group.addEventListener('change', function () {
                document.querySelectorAll('.js-variable[data-group="' + groupKey + '"]:not(:disabled)').forEach(function (input) {
                    input.checked = group.checked;
                });
                group.indeterminate = false;
            });

            syncGroup(groupKey);
        });

        document.querySelectorAll('.js-variable').forEach(function (input) {
            input.addEventListener('change', function () {
                syncGroup(input.getAttribute('data-group'));
            });
        });
    })();
</script>
@endsection
