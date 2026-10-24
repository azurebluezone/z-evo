{{--
    FM PAGE privacy-cookies
    =========================================================================
    The cookie page the consent banner links to.

    Deliberately not a wall of legal prose: three categories, what each one
    gates, a table of the cookies this shop actually sets, and one control that
    changes the choice. The buttons drive window.__CONSENT_UI__, the same object
    the banner uses, so the page and the banner can never disagree about state.

    Works without JS as a readable document; the controls need JS, which is
    stated on the page instead of silently doing nothing.

    To bring the old design back: flip "Front-end Template" in the admin panel.
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@section('title', 'Cookies and privacy')
@section('meta_description', 'Which cookies this shop sets, what each one is for, and how to change your choice at any time.')

@push('styles')
<style>
    .fm-consent { background: #f8fafc; }
    .fm-consent__hero { background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 55%, #ffffff 100%); border-bottom: 1px solid #e2e8f0; }
    .fm-consent__eyebrow { letter-spacing: .14em; text-transform: uppercase; font-size: 12px; font-weight: 700; color: #0f766e; }
    .fm-consent__title { font-size: clamp(28px, 4vw, 40px); line-height: 1.15; font-weight: 800; color: #0f172a; margin: 10px 0 12px; }
    .fm-consent__lead { color: #475569; max-width: 62ch; margin: 0; }
    .fm-consent__card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; height: 100%; }
    .fm-consent__card h2 { font-size: 17px; font-weight: 700; margin: 0 0 6px; color: #0f172a; }
    .fm-consent__card p { color: #475569; margin: 0 0 12px; }
    .fm-consent__badge { display: inline-block; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
    .fm-consent__badge--on { background: #dcfce7; color: #166534; }
    .fm-consent__badge--off { background: #f1f5f9; color: #475569; }
    .fm-consent__badge--fixed { background: #e0f2fe; color: #075985; }
    .fm-consent__btn { appearance: none; border-radius: 10px; padding: 11px 18px; font-weight: 600; font-size: 14px; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #0f172a; }
    .fm-consent__btn--primary { background: #0f766e; border-color: #0f766e; color: #fff; }
    .fm-consent__table thead th { background: #f1f5f9; font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .fm-consent__table td { color: #334155; vertical-align: middle; }
    .fm-consent__table code { color: #0f766e; background: #f0fdfa; padding: 1px 6px; border-radius: 6px; }
    .fm-consent__note { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 16px; color: #78350f; }
</style>
@endpush

@section('content')
<main class="fm-consent" data-purpose="cookie-settings">
    <section class="fm-consent__hero">
        <div class="container mx-auto px-4 py-5 py-lg-6">
            <p class="fm-consent__eyebrow mb-0">Privacy</p>
            <h1 class="fm-consent__title">Cookies, in plain language</h1>
            <p class="fm-consent__lead">
                Necessary cookies keep the cart, the session and your login working — those cannot be
                switched off. Everything else is your call, and changing it takes one click.
            </p>
        </div>
    </section>

    <div class="container mx-auto px-4 py-5">

        {{-- Current state + controls ------------------------------------------------ --}}
        <div class="fm-consent__card mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-7">
                    <h2 class="mb-2">Your current choice</h2>
                    <p class="mb-0">
                        Analytics:
                        <span class="fm-consent__badge {{ $consent['analytics'] === 'granted' ? 'fm-consent__badge--on' : 'fm-consent__badge--off' }}">
                            {{ $consent['analytics'] === 'granted' ? 'Allowed' : 'Not allowed' }}
                        </span>
                        &nbsp;Marketing:
                        <span class="fm-consent__badge {{ $consent['marketing'] === 'granted' ? 'fm-consent__badge--on' : 'fm-consent__badge--off' }}">
                            {{ $consent['marketing'] === 'granted' ? 'Allowed' : 'Not allowed' }}
                        </span>
                        @unless($consent['decided'])
                            <span class="d-block mt-2 text-muted">You have not chosen yet, so both are off.</span>
                        @endunless
                    </p>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        <button type="button" class="fm-consent__btn" data-consent="reject">Reject non-essential</button>
                        <button type="button" class="fm-consent__btn" data-consent="prefs">Preferences</button>
                        <button type="button" class="fm-consent__btn fm-consent__btn--primary" data-consent="accept">Accept all</button>
                    </div>
                </div>
            </div>
            <noscript>
                <p class="fm-consent__note mt-3 mb-0">
                    These buttons need JavaScript. Without it nothing is measured or sent to any ad
                    platform — the shop still works, the cart still works.
                </p>
            </noscript>
        </div>

        {{-- Categories ------------------------------------------------------------- --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="fm-consent__card">
                    <h2>Strictly necessary <span class="fm-consent__badge fm-consent__badge--fixed">Always on</span></h2>
                    <p>Session, cart, CSRF protection and the anonymous visitor id our own reports use. Without these the shop cannot take an order.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="fm-consent__card">
                    <h2>Analytics <span class="fm-consent__badge {{ $consent['analytics'] === 'granted' ? 'fm-consent__badge--on' : 'fm-consent__badge--off' }}">
                        {{ $consent['analytics'] === 'granted' ? 'On' : 'Off' }}</span></h2>
                    <p>Lets us forward page views and funnel steps to Google Analytics. Our own first-party event log keeps working either way — with your answer recorded on every row.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="fm-consent__card">
                    <h2>Marketing <span class="fm-consent__badge {{ $consent['marketing'] === 'granted' ? 'fm-consent__badge--on' : 'fm-consent__badge--off' }}">
                        {{ $consent['marketing'] === 'granted' ? 'On' : 'Off' }}</span></h2>
                    <p>Lets the Meta and TikTok pixels load and lets our server send matching conversion events. Off means no ad-platform request is made at all.</p>
                </div>
            </div>
        </div>

        {{-- Cookie table ----------------------------------------------------------- --}}
        <div class="fm-consent__card mb-4">
            <h2 class="mb-3">Cookies this shop sets</h2>
            <div class="table-responsive">
                <table class="table fm-consent__table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Cookie</th>
                            <th scope="col">Purpose</th>
                            <th scope="col">Lifetime</th>
                            <th scope="col">Set by</th>
                            <th scope="col">Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>laravel_session</code></td>
                            <td>Keeps your session (cart, login) between pages.</td>
                            <td>2 hours</td>
                            <td>This site</td>
                            <td>Necessary</td>
                        </tr>
                        <tr>
                            <td><code>XSRF-TOKEN</code></td>
                            <td>Protects forms from cross-site request forgery.</td>
                            <td>2 hours</td>
                            <td>This site</td>
                            <td>Necessary</td>
                        </tr>
                        <tr>
                            <td><code>visitor_id</code></td>
                            <td>Anonymous id for our own visitor and journey reports. Not linked to a name.</td>
                            <td>1 year</td>
                            <td>This site</td>
                            <td>Necessary</td>
                        </tr>
                        <tr>
                            <td><code>session_id</code></td>
                            <td>Groups the events of one visit; a new one starts after 30 minutes idle.</td>
                            <td>30 minutes</td>
                            <td>This site</td>
                            <td>Necessary</td>
                        </tr>
                        <tr>
                            <td><code>consent</code></td>
                            <td>Remembers the choice on this page. No personal data inside.</td>
                            <td>180 days</td>
                            <td>This site</td>
                            <td>Necessary</td>
                        </tr>
                        <tr>
                            <td><code>_ga</code></td>
                            <td>Google Analytics client id, used to group page views into a visit.</td>
                            <td>2 years</td>
                            <td>Google (via our server container)</td>
                            <td>Analytics</td>
                        </tr>
                        <tr>
                            <td><code>_fbp</code></td>
                            <td>Meta browser id, used to match a visit with an ad click.</td>
                            <td>90 days</td>
                            <td>Meta</td>
                            <td>Marketing</td>
                        </tr>
                        <tr>
                            <td><code>_fbc</code></td>
                            <td>Meta click id, stored when you arrive from a Meta ad.</td>
                            <td>90 days</td>
                            <td>Meta</td>
                            <td>Marketing</td>
                        </tr>
                        <tr>
                            <td><code>_ttp</code></td>
                            <td>TikTok browser id, used for the same purpose when TikTok ads run.</td>
                            <td>13 months</td>
                            <td>TikTok</td>
                            <td>Marketing</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fm-consent__note">
            <strong>Changing your mind.</strong> Come back to this page any time and pick again — the
            banner also reopens from the link in the footer. If you block cookies in your browser,
            the shop still works; only the anonymous visitor id is lost.
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
