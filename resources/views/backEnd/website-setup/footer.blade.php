@extends('backEnd.layouts.app')
@section('title','Website Footer')

@section('css')
<style>
    /* Social icon picker: a real icon grid instead of a text-only <select>.
       The control itself is `.form-select` (see the partial), so only the
       button-specific reset lives here. */
    .icon-picker__toggle { text-align: start; }
    .icon-picker__preview { display: inline-flex; width: 20px; justify-content: center; font-size: 1.1rem; }
    .icon-picker__toggle [data-icon-label] { min-width: 0; }
    .icon-picker__menu { max-height: 360px; overflow-y: auto; }
    .icon-picker__option {
        display: flex; align-items: center; gap: .25rem; width: 100%;
        padding: .25rem .375rem; border: 1px solid transparent; border-radius: .375rem;
        background: transparent; color: inherit; text-align: left; font-size: .8125rem;
    }
    .icon-picker__option:hover { background: rgba(var(--bs-primary-rgb), .08); }
    .icon-picker__option:focus-visible {
        outline: 2px solid rgba(var(--bs-primary-rgb), .5);
        outline-offset: 1px;
        background: rgba(var(--bs-primary-rgb), .08);
    }
    .icon-picker__option.active {
        border-color: rgba(var(--bs-primary-rgb), .45);
        background: rgba(var(--bs-primary-rgb), .10);
    }
    .icon-picker__option-icon { display: inline-flex; width: 18px; justify-content: center; font-size: 1.05rem; }
    .icon-picker__toggle.is-invalid { border-color: var(--bs-form-invalid-border-color); }
    /* The shared Quill CSS forces a 15rem editor; 400 characters need less. */
    #footer-about-editor .ql-editor { min-block-size: 7.5rem; }

    /* admin-responsive.css stacks every card header at <576px and stretches
       its children. These three headers only hold a title and a small count
       badge, so keep them on one line instead of a full-width grey bar. */
    @media (max-width: 575.98px) {
        .card-header.d-flex { flex-direction: row; align-items: center !important; }
        .card-header.d-flex > h5 { width: auto; }
    }
</style>
@endsection

@section('content')

@php
    $payImg = preg_replace('~^public/~', '', (string) ($settings->footer_payment_image ?? ''));
    $payImg = ltrim($payImg, '/');
    $payHasUpload = $payImg !== '' && \Illuminate\Support\Facades\File::exists(public_path($payImg));

    $footerLinkRows = old('footer_links', $settings->footer_links ?? []);
    if (! is_array($footerLinkRows)) {
        $footerLinkRows = [];
    }

    $footerLinkValidCount = 0;
    $footerLinkNextIndex = 0;
    foreach ($footerLinkRows as $footerLinkKey => $footerLinkRow) {
        $footerLinkNextIndex = max($footerLinkNextIndex, (int) $footerLinkKey + 1);
        if (is_array($footerLinkRow)) {
            $footerLinkValidCount++;
        }
    }

    // Short admin labels: the group value is what the storefront reads, the
    // label only has to stay readable in a narrow table cell.
    $footerLinkGroups = [
        'terms'  => 'Terms',
        'site'   => 'Site',
        'useful' => 'Useful',
        'other'  => 'Other',
    ];

    // Stored Font Awesome class -> admin preview icon / brand colour.
    // Built here instead of inside @json() to keep the Blade expression flat.
    $iconMapJson = collect($socialPresets)
        ->mapWithKeys(fn ($preset) => [$preset['fa'] => $preset['ri']])
        ->toJson();
    $colorMapJson = collect($socialPresets)
        ->mapWithKeys(fn ($preset) => [$preset['fa'] => $preset['color']])
        ->toJson();
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Website Footer</h4>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>The footer was not saved.</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ------------------------------------------------------------------
     Card 1: footer content. The footer-links card below associates its
     controls with this form through the HTML5 `form` attribute, so both
     Save buttons submit one complete request.
------------------------------------------------------------------- --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="ri ri-layout-bottom-line me-2"></i>Footer content</h5>
    </div>
    <div class="card-body">
        <form id="footerContentAdminForm" action="{{ route('admin.website-setup.footer.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-12">
                    <label for="footer_about_text" class="form-label">About text</label>
                    {{-- Quill writes its HTML into this hidden field. The raw
                         value can exceed 400 characters because of markup, so
                         the visible-length rule is enforced below and again in
                         the controller. --}}
                    <input type="hidden" id="footer_about_text" name="footer_about_text"
                           value="{{ e((string) old('footer_about_text', $settings->footer_about_text ?? '')) }}">

                    <div id="footer-about-editor"
                         class="js-quill-editor"
                         data-quill-theme="snow"
                         data-quill-toolbar-preset="mini"
                         data-quill-target="#footer_about_text"
                         data-quill-placeholder="Short shop description shown beside the logo..."
                         data-quill-initial-html="{{ e((string) old('footer_about_text', $settings->footer_about_text ?? '')) }}"
                         style="min-height: 140px;"></div>

                    <div class="d-flex justify-content-end mt-1">
                        <span class="small text-muted"><span id="footer-about-count">0</span>/400</span>
                    </div>
                    <div class="text-danger small mt-1 d-none" id="footer-about-limit-alert" role="alert">
                        Please shorten the about text to 400 visible characters or fewer before saving.
                    </div>
                    @error('footer_about_text')
                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <x-admin.input name="footer_payment_image" label="Payment image" type="file"
                                   accept="image/*"
                                   help="PNG, JPG, GIF or WebP up to 2 MB." />

                    <div class="mt-2">
                        @if($payHasUpload)
                            <img src="{{ asset($payImg) }}" alt="Current footer payment image" class="img-fluid" style="height:38px">
                        @else
                            <span class="d-inline-flex align-items-center gap-2" role="img" aria-label="Default payment methods">
                                <img src="{{ asset('admin/assets/img/icons/payments/visa-light.png') }}" alt="Visa" style="height:24px">
                                <img src="{{ asset('admin/assets/img/icons/payments/master-light.png') }}" alt="Mastercard" style="height:24px">
                                <img src="{{ asset('admin/assets/img/icons/payments/ae-light.png') }}" alt="American Express" style="height:24px">
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <x-admin.input name="copyright" label="Copyright"
                                   :value="$settings->copyright ?? ''"
                                   placeholder="© {{ date('Y') }} Your shop" />
                </div>

                <div class="col-12">
                    <div class="border-top pt-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <x-admin.input name="contact_address" label="Location"
                                               :value="$contact->address ?? ''"
                                               placeholder="House 666, Road 5555, Barishal" />
                            </div>
                            <div class="col-12 col-md-4">
                                <x-admin.input name="contact_phone" label="Phone number"
                                               :value="$contact->phone ?? ''"
                                               placeholder="+ (001) 001 001" />
                            </div>
                            <div class="col-12 col-md-4">
                                <x-admin.input name="contact_email" label="Email" type="email"
                                               :value="$contact->email ?? ''"
                                               placeholder="info@example.com" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="ri ri-save-line me-1"></i>Save footer content
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ------------------------------------------------------------------
     Card 2: footer link columns.
------------------------------------------------------------------- --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0"><i class="ri ri-links-line me-2"></i>Footer links</h5>
        <span class="badge bg-label-secondary" id="footerLinkCountBadge">{{ $footerLinkValidCount }} link{{ $footerLinkValidCount === 1 ? '' : 's' }}</span>
    </div>
    <div class="card-body">
        {{-- Marker: tells the controller the repeater was on screen, so
             removing every row clears the stored links. --}}
        <input type="hidden" name="footer_links_present" value="1" form="footerContentAdminForm">

        {{-- position-relative keeps the visually-hidden header label inside the
             scroll container, so a 375px screen does not get a page-wide
             horizontal scrollbar from an absolutely positioned span. --}}
        <div class="table-responsive border rounded position-relative">
            <table class="table table-sm align-middle mb-0" id="footerLinksTable">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="min-width:170px;">Column</th>
                        <th scope="col" style="min-width:170px;">Label</th>
                        <th scope="col" style="min-width:240px;">URL</th>
                        <th scope="col" class="text-center" style="width:80px;">New tab</th>
                        <th scope="col" class="text-end" style="width:64px;"><span class="visually-hidden">Remove</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($footerLinkRows as $footerLinkIndex => $footerLinkRow)
                        @if(is_array($footerLinkRow))
                            @php
                                $footerLinkGroup = (string) ($footerLinkRow['group'] ?? 'site');
                                if (! array_key_exists($footerLinkGroup, $footerLinkGroups)) {
                                    $footerLinkGroup = 'other';
                                }
                            @endphp
                            <tr class="footer-link-row">
                                <td>
                                    <select name="footer_links[{{ $footerLinkIndex }}][group]"
                                            class="form-select form-select-sm" form="footerContentAdminForm">
                                        @foreach($footerLinkGroups as $footerLinkValue => $footerLinkLabel)
                                            <option value="{{ $footerLinkValue }}" @selected($footerLinkGroup === $footerLinkValue)>
                                                {{ $footerLinkLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="footer_links[{{ $footerLinkIndex }}][label]"
                                           class="form-control form-control-sm" maxlength="100"
                                           form="footerContentAdminForm"
                                           value="{{ $footerLinkRow['label'] ?? '' }}" placeholder="Privacy Policy">
                                </td>
                                <td>
                                    <input type="text" name="footer_links[{{ $footerLinkIndex }}][url]"
                                           class="form-control form-control-sm" maxlength="255"
                                           form="footerContentAdminForm"
                                           value="{{ $footerLinkRow['url'] ?? '' }}" placeholder="https://... or /page">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input"
                                           name="footer_links[{{ $footerLinkIndex }}][new_tab]" value="1"
                                           form="footerContentAdminForm"
                                           @checked((int) ($footerLinkRow['new_tab'] ?? 0) === 1)>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-icon btn-text-danger text-danger rounded-pill js-footer-link-remove"
                                            aria-label="Remove this link" title="Remove link">
                                        <i class="ri ri-delete-bin-line" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    <tr class="footer-link-empty-state @if($footerLinkValidCount > 0) d-none @endif">
                        <td colspan="5" class="text-center text-muted py-4">
                            No footer links yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <span class="form-text">Up to 30 links.</span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" id="footerLinkAdd">
                    <i class="ri ri-add-line me-1"></i>Add link
                </button>
                <button type="submit" form="footerContentAdminForm" class="btn btn-primary">
                    <i class="ri ri-save-line me-1"></i>Save links
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Row cloned by the Add link button. __INDEX__ is replaced with the next
     free index so every row posts a unique footer_links[N] key. --}}
<template id="footerLinkRowTemplate">
    <tr class="footer-link-row">
        <td>
            <select name="footer_links[__INDEX__][group]" class="form-select form-select-sm" form="footerContentAdminForm">
                @foreach($footerLinkGroups as $footerLinkValue => $footerLinkLabel)
                    <option value="{{ $footerLinkValue }}">{{ $footerLinkLabel }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" name="footer_links[__INDEX__][label]" class="form-control form-control-sm"
                   maxlength="100" form="footerContentAdminForm" placeholder="Privacy Policy">
        </td>
        <td>
            <input type="text" name="footer_links[__INDEX__][url]" class="form-control form-control-sm"
                   maxlength="255" form="footerContentAdminForm" placeholder="https://... or /page">
        </td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="footer_links[__INDEX__][new_tab]" value="1"
                   form="footerContentAdminForm">
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger text-danger rounded-pill js-footer-link-remove"
                    aria-label="Remove this link" title="Remove link">
                <i class="ri ri-delete-bin-line" aria-hidden="true"></i>
            </button>
        </td>
    </tr>
</template>

{{-- ------------------------------------------------------------------
     Card 3: social icons.
------------------------------------------------------------------- --}}
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0"><i class="ri ri-share-line me-2"></i>Social icons</h5>
        <span class="badge bg-label-secondary">{{ $socials->count() }} total</span>
    </div>

    <div class="card-body">
        <form id="socialCreateForm" action="{{ route('socialmedias.store') }}" method="POST"
              class="row g-3">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

            <div class="col-12 col-md-4">
                <label for="social_new_title" class="form-label">Name</label>
                <input type="text" name="title" id="social_new_title" class="form-control"
                       placeholder="Facebook" required>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label" for="social_new_picker_toggle">Platform</label>
                @include('backEnd.website-setup.partials.social-icon-picker', [
                    'pickerId' => 'social_new_picker',
                    'pickerValue' => '',
                    'pickerColorTarget' => '#social_new_color',
                ])
            </div>

            <div class="col-12 col-md-4">
                <label for="social_new_link" class="form-label">Link</label>
                <input type="url" name="link" id="social_new_link" class="form-control"
                       placeholder="https://facebook.com/yourshop" required>
            </div>

            <div class="col-6 col-md-3">
                <label for="social_new_color" class="form-label">Brand colour</label>
                <input type="color" name="color" id="social_new_color" class="form-control form-control-color"
                       value="#1877f2">
            </div>

            <div class="col-6 col-md-5 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="status" id="social_new_status" value="1" checked>
                    <label class="form-check-label" for="social_new_status">Show in footer</label>
                </div>
            </div>

            <div class="col-12 col-md-4 d-flex align-items-end justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                    <i class="ri ri-add-line me-1"></i>Add icon
                </button>
            </div>
        </form>

        <div class="table-responsive border rounded mt-4">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width:56px;">SL</th>
                        <th scope="col">Platform</th>
                        <th scope="col">Link</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end" style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($socials as $social)
                        @php
                            $socialColor = trim((string) ($social->color ?? '')) ?: '#6b7280';
                            $socialCatalog = \App\Support\SocialIcons::entryForFa($social->icon);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-primary"
                                          style="width:32px;height:32px;">
                                        <i class="{{ \App\Support\SocialIcons::adminIcon($social->icon) }}"
                                           style="color: {{ $socialColor }};" aria-hidden="true"></i>
                                    </span>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $social->title }}</span>
                                        <small class="text-muted">
                                            {{ $socialCatalog['label'] ?? $social->icon }}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($social->link)
                                    <a href="{{ $social->link }}" target="_blank" rel="noopener"
                                       class="d-inline-flex align-items-center gap-1">
                                        {{ \Illuminate\Support\Str::limit($social->link, 44) }}
                                        <i class="ri ri-external-link-line" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </td>
                            <td>
                                @if($social->status == 1)
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-icon btn-text-secondary rounded-pill js-social-edit"
                                            data-bs-toggle="modal" data-bs-target="#socialEditModal"
                                            data-id="{{ $social->id }}"
                                            data-title="{{ $social->title }}"
                                            data-icon="{{ $social->icon }}"
                                            data-link="{{ $social->link }}"
                                            data-color="{{ $social->color ?: '#111827' }}"
                                            data-status="{{ (int) ($social->status == 1) }}"
                                            title="Edit {{ $social->title }}" aria-label="Edit {{ $social->title }}">
                                        <i class="ri ri-pencil-line" aria-hidden="true"></i>
                                    </button>

                                    @if($social->status == 1)
                                        <form method="post" action="{{ route('socialmedias.inactive') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                            <input type="hidden" name="hidden_id" value="{{ $social->id }}">
                                            <button type="submit"
                                                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill change-confirm"
                                                    data-confirm-title="Hide this icon?"
                                                    data-confirm-text="It stays saved but disappears from the footer."
                                                    title="Hide {{ $social->title }}" aria-label="Hide {{ $social->title }}">
                                                <i class="ri ri-eye-off-line" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('socialmedias.active') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                            <input type="hidden" name="hidden_id" value="{{ $social->id }}">
                                            <button type="submit"
                                                    class="btn btn-sm btn-icon btn-text-secondary rounded-pill change-confirm"
                                                    title="Show {{ $social->title }}" aria-label="Show {{ $social->title }}">
                                                <i class="ri ri-eye-line" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form method="post" action="{{ route('socialmedias.destroy') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                        <input type="hidden" name="hidden_id" value="{{ $social->id }}">
                                        <button type="submit"
                                                class="btn btn-sm btn-icon btn-text-danger text-danger rounded-pill delete-confirm"
                                                data-confirm-title="Delete this social icon?"
                                                data-confirm-text="The link is removed from the footer immediately."
                                                title="Delete {{ $social->title }}" aria-label="Delete {{ $social->title }}">
                                            <i class="ri ri-delete-bin-line" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="ri ri-share-circle-line icon-32px text-muted d-block mb-2" aria-hidden="true"></i>
                                <span class="text-muted">No social icons yet. Add the first one above.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- One edit form, filled from the row's data-* attributes. --}}
<div class="modal fade" id="socialEditModal" tabindex="-1" aria-labelledby="socialEditModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('socialmedias.update') }}" id="socialEditForm">
                @csrf
                <input type="hidden" name="id" id="social_edit_id">
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="socialEditModalLabel">Edit social icon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="social_edit_title" class="form-label">Name</label>
                            <input type="text" name="title" id="social_edit_title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="social_edit_picker_toggle">Platform</label>
                            @include('backEnd.website-setup.partials.social-icon-picker', [
                                'pickerId' => 'social_edit_picker',
                                'pickerValue' => '',
                                'pickerColorTarget' => '#social_edit_color',
                            ])
                        </div>
                        <div class="col-12">
                            <label for="social_edit_link" class="form-label">Link</label>
                            <input type="url" name="link" id="social_edit_link" class="form-control" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="social_edit_color" class="form-label">Brand colour</label>
                            <input type="color" name="color" id="social_edit_color" class="form-control form-control-color">
                        </div>
                        <div class="col-12 col-sm-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       name="status" id="social_edit_status" value="1">
                                <label class="form-check-label" for="social_edit_status">Show in footer</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save icon</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        'use strict';

        // Stored Font Awesome class -> admin preview class / brand colour.
        // Supplied by the controller from App\Support\SocialIcons::all().
        var iconMap = {!! $iconMapJson !!};
        var colorMap = {!! $colorMapJson !!};

        function normalizeIconClass(value) {
            return String(value || '').toLowerCase().trim();
        }

        function resolveRi(value) {
            return iconMap[normalizeIconClass(value)] || 'ri ri-global-line';
        }

        function setActiveOption(picker, value) {
            var needle = normalizeIconClass(value);
            picker.querySelectorAll('.icon-picker__option').forEach(function (option) {
                var active = normalizeIconClass(option.dataset.iconValue) === needle;
                option.classList.toggle('active', active);
                option.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        function applyIcon(picker, value, options) {
            options = options || {};
            var input = picker.querySelector('[data-icon-input]');
            var preview = picker.querySelector('[data-icon-preview]');
            var label = picker.querySelector('[data-icon-label]');
            var custom = picker.querySelector('[data-icon-custom]');
            var toggle = picker.querySelector('.icon-picker__toggle');
            var cleaned = String(value || '').trim();
            var needle = normalizeIconClass(cleaned);
            var presetLabel = '';

            picker.querySelectorAll('.icon-picker__option').forEach(function (option) {
                if (normalizeIconClass(option.dataset.iconValue) === needle) {
                    presetLabel = option.dataset.iconLabel || '';
                }
            });

            if (input) {
                input.value = cleaned;
            }
            if (preview) {
                preview.innerHTML = '<i class="' + resolveRi(cleaned) + '"></i>';
            }
            if (label) {
                label.textContent = presetLabel || cleaned || 'Choose a platform';
            }
            if (custom && options.syncCustom !== false && custom.value !== cleaned) {
                custom.value = cleaned;
            }
            if (toggle && cleaned) {
                toggle.classList.remove('is-invalid');
            }

            setActiveOption(picker, cleaned);

            var colorTarget = picker.getAttribute('data-color-target');
            if (colorTarget && options.applyColor !== false) {
                var colorInput = document.querySelector(colorTarget);
                var presetColor = colorMap[needle];
                if (colorInput && presetColor) {
                    colorInput.value = presetColor;
                }
            }
        }

        function hidePicker(picker) {
            var toggle = picker.querySelector('[data-bs-toggle="dropdown"]');
            if (!toggle || !window.bootstrap || !window.bootstrap.Dropdown) {
                return;
            }
            var dropdown = window.bootstrap.Dropdown.getInstance(toggle);
            if (dropdown) {
                dropdown.hide();
            }
        }

        function initIconPicker(picker) {
            if (!picker || picker.__iconPicker) {
                return;
            }
            picker.__iconPicker = true;

            picker.querySelectorAll('.icon-picker__option').forEach(function (option) {
                option.addEventListener('click', function () {
                    applyIcon(picker, option.dataset.iconValue || '');
                    hidePicker(picker);
                });
            });

            var custom = picker.querySelector('[data-icon-custom]');
            if (custom) {
                custom.addEventListener('input', function () {
                    applyIcon(picker, custom.value, { syncCustom: false });
                });
                custom.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') {
                        return;
                    }
                    event.preventDefault();
                    applyIcon(picker, custom.value);
                    hidePicker(picker);
                });
            }
        }

        document.querySelectorAll('[data-icon-picker]').forEach(initIconPicker);

        // The add form needs a chosen platform before it can post.
        var createForm = document.getElementById('socialCreateForm');
        if (createForm) {
            createForm.addEventListener('submit', function (event) {
                var picker = document.getElementById('social_new_picker');
                var input = picker ? picker.querySelector('[data-icon-input]') : null;
                if (input && !input.value.trim()) {
                    event.preventDefault();
                    var toggle = picker.querySelector('.icon-picker__toggle');
                    if (toggle) {
                        toggle.classList.add('is-invalid');
                        toggle.focus();
                    }
                }
            });
        }

        // --- Footer about text: Quill + visible-length guard ---------------
        var aboutEditor = document.getElementById('footer-about-editor');
        var aboutField = document.getElementById('footer_about_text');
        var aboutCount = document.getElementById('footer-about-count');
        var aboutAlert = document.getElementById('footer-about-limit-alert');
        var aboutForm = document.getElementById('footerContentAdminForm');
        var aboutLimit = 400;

        function visibleFooterLength(html) {
            var box = document.createElement('div');
            box.innerHTML = String(html || '');
            return (box.textContent || '').replace(/\s+/g, ' ').trim().length;
        }

        function refreshFooterLength() {
            var html = aboutField ? aboutField.value : '';

            if (aboutEditor && aboutEditor.__quill) {
                html = aboutEditor.__quill.root.innerHTML;
            }

            var length = visibleFooterLength(html);

            if (aboutCount) {
                aboutCount.textContent = String(length);
                aboutCount.parentElement.classList.toggle('text-danger', length > aboutLimit);
                aboutCount.parentElement.classList.toggle('fw-semibold', length > aboutLimit);
            }

            if (aboutAlert) {
                aboutAlert.classList.toggle('d-none', length <= aboutLimit);
            }

            return length;
        }

        if (aboutEditor) {
            var startAboutCounter = function () {
                refreshFooterLength();

                if (aboutEditor.__quill) {
                    aboutEditor.__quill.on('text-change', refreshFooterLength);
                } else {
                    var root = aboutEditor.querySelector('.ql-editor');
                    if (root && window.MutationObserver) {
                        new MutationObserver(refreshFooterLength).observe(root, {
                            childList: true,
                            characterData: true,
                            subtree: true
                        });
                    }
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    window.setTimeout(startAboutCounter, 0);
                });
            } else {
                window.setTimeout(startAboutCounter, 0);
            }
        }

        if (aboutForm) {
            aboutForm.addEventListener('submit', function (event) {
                if (refreshFooterLength() <= aboutLimit) {
                    return;
                }

                event.preventDefault();
                if (aboutAlert) {
                    aboutAlert.classList.remove('d-none');
                    aboutAlert.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            });
        }

        // --- Footer link columns: repeater ---------------------------------
        var linksTable = document.getElementById('footerLinksTable');
        var linkAdd = document.getElementById('footerLinkAdd');
        var linkTemplate = document.getElementById('footerLinkRowTemplate');
        var nextFooterLinkIndex = {{ $footerLinkNextIndex }};
        var footerLinkLimit = 30;

        function footerLinkRows() {
            return linksTable ? linksTable.querySelectorAll('tbody .footer-link-row') : [];
        }

        function refreshFooterLinkState() {
            var count = footerLinkRows().length;
            var badge = document.getElementById('footerLinkCountBadge');

            if (badge) {
                badge.textContent = count + (count === 1 ? ' link' : ' links');
            }

            if (linksTable) {
                var empty = linksTable.querySelector('.footer-link-empty-state');
                if (empty) {
                    empty.classList.toggle('d-none', count > 0);
                }
            }

            if (linkAdd) {
                linkAdd.disabled = count >= footerLinkLimit;
            }
        }

        if (linksTable && linkAdd && linkTemplate) {
            linkAdd.addEventListener('click', function () {
                if (footerLinkRows().length >= footerLinkLimit) {
                    return;
                }

                var html = linkTemplate.innerHTML.replace(/__INDEX__/g, String(nextFooterLinkIndex++));
                linksTable.querySelector('tbody').insertAdjacentHTML('beforeend', html);

                var rows = footerLinkRows();
                var lastRow = rows[rows.length - 1];
                var firstInput = lastRow ? lastRow.querySelector('input[name$="[label]"]') : null;
                if (firstInput) {
                    firstInput.focus();
                }

                refreshFooterLinkState();
            });

            linksTable.addEventListener('click', function (event) {
                var button = event.target.closest('.js-footer-link-remove');
                if (!button) {
                    return;
                }

                var row = button.closest('tr');
                if (row) {
                    row.remove();
                }

                refreshFooterLinkState();
            });

            refreshFooterLinkState();
        }

        // --- Edit modal ------------------------------------------------------
        var editForm = document.getElementById('socialEditForm');
        if (!editForm) {
            return;
        }

        var editPicker = document.getElementById('social_edit_picker');

        document.querySelectorAll('.js-social-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                editForm.elements['id'].value = button.dataset.id || '';
                editForm.elements['title'].value = button.dataset.title || '';
                editForm.elements['link'].value = button.dataset.link || '';
                editForm.elements['status'].checked = button.dataset.status === '1';

                if (editPicker) {
                    applyIcon(editPicker, button.dataset.icon || '', { applyColor: false });
                }

                var color = document.getElementById('social_edit_color');
                if (color) {
                    color.value = button.dataset.color || '#111827';
                }
            });
        });

    })();
</script>
@endsection
