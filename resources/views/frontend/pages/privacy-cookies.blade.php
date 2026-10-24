{{--
    CLASSIC PAGE privacy-cookies
    =========================================================================
    Same content and the same consent controls as the modern page
    (frontend/fm/pages/privacy-cookies.blade.php), rebuilt on the classic
    Bootstrap card/table markup so the page looks native in either theme.

    The buttons talk to window.__CONSENT_UI__, which the banner already defines;
    nothing is duplicated between the two implementations.
    =========================================================================
--}}

@extends('frontend.layouts.app')

@section('title', 'Cookies and privacy')
@section('meta_description', 'Which cookies this shop sets, what each one is for, and how to change your choice at any time.')

@push('styles')
<style>
    .cookie-page { background: #f7f8fa; padding: 32px 0 48px; }
    .cookie-card { background: #fff; border: 1px solid #e6e8ec; border-radius: 14px; padding: 22px; margin-bottom: 18px; }
    .cookie-card h2 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
    .cookie-badge { display: inline-block; font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
    .cookie-badge--on { background: #dcfce7; color: #166534; }
    .cookie-badge--off { background: #eef1f5; color: #475569; }
    .cookie-badge--fixed { background: #e0f2fe; color: #075985; }
    .cookie-table thead th { background: #f3f4f6; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #4b5563; }
    .cookie-table code { color: #0f766e; }
    .cookie-note { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 16px; color: #78350f; }
</style>
@endpush

@section('content')
<main class="cookie-page">
    <div class="container">
        <div class="cookie-card">
            <h1 class="h3 mb-2">Cookies, in plain language</h1>
            <p class="text-muted mb-0">
                Necessary cookies keep the cart, the session and your login working. Everything else
                is your call, and changing it takes one click.
            </p>
        </div>

        <div class="cookie-card">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h2>Your current choice</h2>
                    <p class="mb-0">
                        Analytics:
                        <span class="cookie-badge {{ $consent['analytics'] === 'granted' ? 'cookie-badge--on' : 'cookie-badge--off' }}">{{ $consent['analytics'] === 'granted' ? 'Allowed' : 'Not allowed' }}</span>
                        &nbsp;Marketing:
                        <span class="cookie-badge {{ $consent['marketing'] === 'granted' ? 'cookie-badge--on' : 'cookie-badge--off' }}">{{ $consent['marketing'] === 'granted' ? 'Allowed' : 'Not allowed' }}</span>
                    </p>
                    @unless($consent['decided'])
                        <p class="text-muted mb-0 mt-2">You have not chosen yet, so both are off.</p>
                    @endunless
                </div>
                <div class="col-md-5 text-md-right mt-3 mt-md-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-consent="reject">Reject non-essential</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-consent="prefs">Preferences</button>
                    <button type="button" class="btn btn-primary btn-sm" data-consent="accept">Accept all</button>
                </div>
            </div>
            <noscript>
                <p class="cookie-note mt-3 mb-0">
                    These buttons need JavaScript. Without it nothing is measured or sent to any ad
                    platform — the shop still works.
                </p>
            </noscript>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="cookie-card h-100">
                    <h2>Strictly necessary <span class="cookie-badge cookie-badge--fixed">Always on</span></h2>
                    <p class="text-muted mb-0">Session, cart, CSRF protection and the anonymous visitor id our own reports use.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cookie-card h-100">
                    <h2>Analytics <span class="cookie-badge {{ $consent['analytics'] === 'granted' ? 'cookie-badge--on' : 'cookie-badge--off' }}">{{ $consent['analytics'] === 'granted' ? 'On' : 'Off' }}</span></h2>
                    <p class="text-muted mb-0">Forwards funnel steps to Google Analytics. Our first-party event log keeps working either way.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cookie-card h-100">
                    <h2>Marketing <span class="cookie-badge {{ $consent['marketing'] === 'granted' ? 'cookie-badge--on' : 'cookie-badge--off' }}">{{ $consent['marketing'] === 'granted' ? 'On' : 'Off' }}</span></h2>
                    <p class="text-muted mb-0">Loads the Meta and TikTok pixels and allows matching conversion events. Off means no ad-platform request.</p>
                </div>
            </div>
        </div>

        <div class="cookie-card">
            <h2 class="mb-3">Cookies this shop sets</h2>
            <div class="table-responsive">
                <table class="table table-sm cookie-table mb-0">
                    <thead>
                        <tr>
                            <th>Cookie</th><th>Purpose</th><th>Lifetime</th><th>Set by</th><th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>laravel_session</code></td><td>Keeps your session (cart, login).</td><td>2 hours</td><td>This site</td><td>Necessary</td></tr>
                        <tr><td><code>XSRF-TOKEN</code></td><td>Form protection against cross-site requests.</td><td>2 hours</td><td>This site</td><td>Necessary</td></tr>
                        <tr><td><code>visitor_id</code></td><td>Anonymous id for our visitor reports.</td><td>1 year</td><td>This site</td><td>Necessary</td></tr>
                        <tr><td><code>session_id</code></td><td>Groups the events of one visit.</td><td>30 minutes</td><td>This site</td><td>Necessary</td></tr>
                        <tr><td><code>consent</code></td><td>Remembers this choice.</td><td>180 days</td><td>This site</td><td>Necessary</td></tr>
                        <tr><td><code>_ga</code></td><td>Google Analytics client id.</td><td>2 years</td><td>Google</td><td>Analytics</td></tr>
                        <tr><td><code>_fbp</code></td><td>Meta browser id.</td><td>90 days</td><td>Meta</td><td>Marketing</td></tr>
                        <tr><td><code>_fbc</code></td><td>Meta click id from an ad.</td><td>90 days</td><td>Meta</td><td>Marketing</td></tr>
                        <tr><td><code>_ttp</code></td><td>TikTok browser id.</td><td>13 months</td><td>TikTok</td><td>Marketing</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="cookie-note">
            <strong>Changing your mind.</strong> Come back to this page any time and pick again.
            If you block cookies in your browser, the shop still works.
        </div>
    </div>
</main>

@push('scripts')
<script>
    (function () {
        'use strict';
        var ui = window.__CONSENT_UI__;

        if (!ui) {
            return;
        }

        document.querySelectorAll('[data-consent]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.getAttribute('data-consent');

                if (action === 'accept') {
                    ui.save(true, true);
                } else if (action === 'reject') {
                    ui.save(false, false);
                } else {
                    ui.open();
                }
            });
        });
    })();
</script>
@endpush
@endsection
