@extends('backEnd.layouts.app')
@section('title','Website Header')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-0">Website Header</h4>
                </div>

                <form action="{{ route('admin.website-setup.header.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Website Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $settings->name ?? '') }}">
                        </div>

                        @php
                            $existingHeaderLinks = [];
                            if (!empty($settings->header_links)) {
                                $existingHeaderLinks = json_decode($settings->header_links, true) ?: [];
                            }
                            if (old('header_links') && is_array(old('header_links'))) {
                                $existingHeaderLinks = old('header_links');
                            }
                            if (empty($existingHeaderLinks)) {
                                $existingHeaderLinks = [
                                    ['title' => 'About Us', 'url' => $settings->about_url ?? route('about.us')],
                                    ['title' => 'Contact', 'url' => $settings->contact_url ?? route('contact')],
                                ];
                            }
                        @endphp

                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Favicon</label>
                            <input type="file" name="favicon" class="form-control" accept="image/*">
                            @php
                                $faviconPreview = preg_replace('~^public/~', '', (string) ($settings->favicon ?? ''));
                                $faviconPreview = ltrim($faviconPreview, '/');
                            @endphp
                            @if(!empty($faviconPreview) && \Illuminate\Support\Facades\File::exists(public_path($faviconPreview)))
                                <div class="mt-2">
                                    <img src="{{ asset($faviconPreview) }}" alt="favicon" style="height:40px">
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label">White Logo</label>
                            <input type="file" name="white_logo" class="form-control" accept="image/*">
                            @php
                                $whiteLogoPreview = preg_replace('~^public/~', '', (string) ($settings->white_logo ?? ''));
                                $whiteLogoPreview = ltrim($whiteLogoPreview, '/');
                            @endphp
                            @if(!empty($whiteLogoPreview) && \Illuminate\Support\Facades\File::exists(public_path($whiteLogoPreview)))
                                <div class="mt-2">
                                    <img src="{{ asset($whiteLogoPreview) }}" alt="white-logo" style="height:44px">
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Dark Logo</label>
                            <input type="file" name="dark_logo" class="form-control" accept="image/*">
                            @php
                                $darkLogoPreview = preg_replace('~^public/~', '', (string) ($settings->dark_logo ?? ''));
                                $darkLogoPreview = ltrim($darkLogoPreview, '/');
                            @endphp
                            @if(!empty($darkLogoPreview) && \Illuminate\Support\Facades\File::exists(public_path($darkLogoPreview)))
                                <div class="mt-2">
                                    <img src="{{ asset($darkLogoPreview) }}" alt="dark-logo" style="height:44px">
                                </div>
                            @endif
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Header Links</label>
                            <div class="form-repeater">
                                <div data-repeater-list="header_links">
                                    @foreach($existingHeaderLinks as $link)
                                        <div data-repeater-item>
                                            <div class="row g-2 align-items-end">
                                                <div class="col-lg-4">
                                                    <input type="text" name="title" class="form-control" value="{{ $link['title'] ?? '' }}" placeholder="Title">
                                                </div>
                                                <div class="col-lg-6">
                                                    <input type="text" name="url" class="form-control" value="{{ $link['url'] ?? '' }}" placeholder="URL">
                                                </div>
                                                <div class="col-lg-2">
                                                    <x-admin.button variant="outline-danger" class="w-100" type="button">Delete</x-admin.button>
                                                </div>
                                            </div>
                                            <hr class="my-2" />
                                        </div>
                                    @endforeach
                                </div>

                                <x-admin.button variant="primary" type="button">Add</x-admin.button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        <x-admin.button variant="primary" type="submit">Save</x-admin.button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection
