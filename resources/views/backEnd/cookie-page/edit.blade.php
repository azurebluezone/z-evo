{{--
    Admin editor for the public cookie page.

    The retention card is not part of the form. It is printed from
    App\Support\Consent::RETENTION_NOTICE, which is the same constant the
    storefront renders, so what the admin sees here is exactly what a visitor
    cannot have hidden from them.
--}}
@extends('backEnd.layouts.app')
@section('title', 'Cookie Page')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card rounded-0 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Cookie Page</h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('consent.page') }}" target="_blank" rel="noopener"
                       class="btn btn-outline-secondary btn-square waves-effect">
                        <i class="ri ri-external-link-line me-1"></i> View page
                    </a>
                    <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="cookiePageForm">
                        <i class="ri ri-save-line me-1"></i> Update
                    </x-admin.button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('cookie-page.update') }}" method="POST" id="cookiePageForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="title" class="form-label">Page title *</label>
                                <input type="text"
                                       class="form-control @error('title') is-invalid @enderror"
                                       name="title" id="title"
                                       value="{{ old('title', $cookiePage->title) }}"
                                       maxlength="120" required>
                                @error('title')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label d-block">Last updated</label>
                                <p class="form-control-plaintext mb-0">
                                    {{ $cookiePage->updated_at?->format('d M Y, H:i') ?? 'Not saved yet' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group mb-3">
                                <label for="intro" class="form-label">Intro</label>
                                <textarea class="form-control @error('intro') is-invalid @enderror"
                                          name="intro" id="intro" rows="3" maxlength="1000"
                                          placeholder="One or two sentences shown under the page title.">{{ old('intro', $cookiePage->intro) }}</textarea>
                                @error('intro')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group mb-3">
                                <label for="body" class="form-label">Page content</label>
                                <textarea class="form-control d-none @error('body') is-invalid @enderror"
                                          name="body" id="body" rows="8">{{ old('body', $cookiePage->body) }}</textarea>
                                <div id="body-editor"
                                     class="js-quill-editor"
                                     data-quill-theme="snow"
                                     data-quill-toolbar-preset="full"
                                     data-quill-target="#body"
                                     data-quill-placeholder="Write the cookie page content..."
                                     data-quill-initial-html="{{ e((string) old('body', $cookiePage->body)) }}"
                                     style="min-height: 300px;"></div>
                                @error('body')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                <div class="form-text">
                                    Explain the shop's cookie policy in your own words. The consent controls and the
                                    cookie table below this content are rendered by the theme, not from this field.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning d-flex gap-3 mb-3" role="alert" id="retention-notice">
                        <i class="ri ri-lock-2-line fs-4" aria-hidden="true"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Data retention - locked</h6>
                            <p class="mb-0">{{ $retentionNotice }}</p>
                            <p class="mb-0 mt-2 small">
                                This statement comes from code (<code>App\Support\Consent::RETENTION_NOTICE</code>)
                                and has no editable field. Business policy can be written around it; the truth cannot
                                be removed.
                            </p>
                        </div>
                    </div>

                    <x-admin.button variant="success" type="submit">
                        <i class="ri ri-save-line me-1"></i> Save cookie page
                    </x-admin.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
