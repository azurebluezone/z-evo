{{--
    Consent banner + preference panel.

    One implementation, injected as DOM by JS, so both front-end templates and
    every layout that already includes event-layer-head get it without touching
    their markup. Defaults come from window.__CONSENT__ (rendered server-side by
    event-layer-head), so the banner only appears when no valid decision exists.

    Rules this file follows:
      - no dark patterns: "Accept all" and "Reject non-essential" are the same
        size and weight; nothing is pre-ticked in the panel;
      - a grant that needs vendor scripts reloads the page once, because those
        scripts are printed server-side (a pixel cannot be un-printed);
      - a rejection never reloads, and nothing was loaded to undo;
      - the choice is remembered for 180 days under a versioned cookie, so a
        policy change re-asks instead of silently reusing an old answer.
--}}
<script>
    window.__CONSENT_UI__ = window.__CONSENT_UI__ || (function () {
        'use strict';

        var C = window.__CONSENT__ || {};
        var COOKIE = 'consent';
        var VERSION = C.version || 1;
        var DAYS = C.ttlDays || 180;
        var banner = null;
        var panel = null;

        function write(state) {
            try {
                var value = encodeURIComponent(JSON.stringify(state));
                document.cookie = COOKIE + '=' + value
                    + ';path=/;max-age=' + (DAYS * 86400) + ';SameSite=Lax';
            } catch (e) {
                // Storage blocked: the choice still applies to this page, it just
                // will not be remembered. Never throw at the visitor.
            }
        }

        function save(analytics, marketing) {
            var state = {
                analytics: analytics ? 'granted' : 'denied',
                marketing: marketing ? 'granted' : 'denied',
                v: VERSION,
                ts: Math.floor(Date.now() / 1000)
            };

            write(state);

            var needsReload =
                (state.analytics === 'granted' && C.serverAnalytics !== 'granted') ||
                (state.marketing === 'granted' && C.serverMarketing !== 'granted');

            C.analytics = state.analytics;
            C.marketing = state.marketing;
            C.decided = true;

            hide();

            if (needsReload) {
                try { location.reload(); } catch (e) {}
            }
        }

        function hide() {
            if (banner && banner.parentNode) banner.parentNode.removeChild(banner);
            if (panel && panel.parentNode) panel.parentNode.removeChild(panel);
            banner = null;
            panel = null;
        }

        function node(tag, style, html) {
            var el = document.createElement(tag);
            if (style) el.setAttribute('style', style);
            if (html) el.innerHTML = html;
            return el;
        }

        function button(label, primary, onClick) {
            var style = 'appearance:none;border-radius:10px;padding:10px 16px;font:600 14px/1.2 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;cursor:pointer;'
                + (primary
                    ? 'background:#0f766e;border:1px solid #0f766e;color:#fff;'
                    : 'background:#fff;border:1px solid #cbd5e1;color:#0f172a;');
            var b = node('button', style, label);
            b.setAttribute('type', 'button');
            b.addEventListener('click', onClick);
            return b;
        }

        function mountBanner() {
            if (C.decided || C.page === '/privacy/cookies') return;

            banner = node('div',
                'position:fixed;left:0;right:0;bottom:0;z-index:2147483000;background:#fff;'
                + 'border-top:1px solid #e2e8f0;box-shadow:0 -8px 24px rgba(15,23,42,.10);'
                + 'padding:14px 16px;font:400 14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#0f172a;');
            banner.setAttribute('role', 'dialog');
            banner.setAttribute('aria-label', 'Cookie choices');

            var row = node('div',
                'max-width:1100px;margin:0 auto;display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;');

            var text = node('p', 'margin:0;flex:1 1 380px;max-width:640px;',
                'We use cookies to keep the cart working and, with your permission, to measure traffic and ads. '
                + 'You can change this any time on the '
                + '<a href="/privacy/cookies" style="color:#0f766e;text-decoration:underline;">cookie page</a>.');

            var actions = node('div', 'display:flex;gap:8px;flex-wrap:wrap;');
            actions.appendChild(button('Reject non-essential', false, function () { save(false, false); }));
            actions.appendChild(button('Accept all', true, function () { save(true, true); }));
            var prefs = node('button',
                'appearance:none;background:none;border:0;color:#475569;text-decoration:underline;font:600 13px system-ui;cursor:pointer;padding:8px;',
                'Preferences');
            prefs.setAttribute('type', 'button');
            prefs.addEventListener('click', open);
            actions.appendChild(prefs);

            row.appendChild(text);
            row.appendChild(actions);
            banner.appendChild(row);
            document.body.appendChild(banner);
        }

        function open() {
            if (panel) return;

            panel = node('div',
                'position:fixed;inset:0;z-index:2147483001;background:rgba(15,23,42,.45);'
                + 'display:flex;align-items:center;justify-content:center;padding:16px;');

            var card = node('div',
                'background:#fff;border-radius:16px;max-width:520px;width:100%;padding:22px;'
                + 'font:400 14px/1.55 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#0f172a;'
                + 'box-shadow:0 24px 60px rgba(15,23,42,.25);');
            card.setAttribute('role', 'dialog');
            card.setAttribute('aria-modal', 'true');
            card.setAttribute('aria-label', 'Cookie preferences');

            card.appendChild(node('h2', 'margin:0 0 6px;font-size:19px;', 'Cookie preferences'));
            card.appendChild(node('p', 'margin:0 0 16px;color:#475569;',
                'Necessary cookies are always on: they keep the cart, the session and your login working.'));

            function toggle(id, label, help, checked) {
                var wrap = node('label',
                    'display:flex;gap:12px;align-items:flex-start;padding:12px 0;border-top:1px solid #e2e8f0;cursor:pointer;');
                var input = document.createElement('input');
                input.type = 'checkbox';
                input.id = id;
                input.checked = !!checked;
                input.style = 'margin-top:3px;width:18px;height:18px;accent-color:#0f766e;';
                var body = node('span', '',
                    '<strong style="display:block;">' + label + '</strong>'
                    + '<span style="color:#475569;">' + help + '</span>');
                wrap.appendChild(input);
                wrap.appendChild(body);
                return wrap;
            }

            card.appendChild(toggle('consent-analytics', 'Analytics',
                'Lets us see which pages are visited and where visitors drop off.', C.analytics === 'granted'));
            card.appendChild(toggle('consent-marketing', 'Marketing',
                'Lets Meta and TikTok measure and improve our ads.', C.marketing === 'granted'));

            var footer = node('div', 'display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;margin-top:18px;');
            footer.appendChild(button('Cancel', false, hide));
            footer.appendChild(button('Save choices', true, function () {
                var a = document.getElementById('consent-analytics');
                var m = document.getElementById('consent-marketing');
                save(!!(a && a.checked), !!(m && m.checked));
            }));

            card.appendChild(footer);
            panel.appendChild(card);
            panel.addEventListener('click', function (e) {
                if (e.target === panel) hide();
            });
            document.body.appendChild(panel);

            var first = document.getElementById('consent-analytics');
            if (first) first.focus();
        }

        function start() {
            try { mountBanner(); } catch (e) {}
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }

        return {
            open: open,
            close: hide,
            save: save,
            state: function () { return C; }
        };
    })();
</script>
