/* Courier settings: connection test + copy-to-clipboard.
   One line of output per test - the admin pressed Test to learn one thing:
   did it connect. The per-step detail stays in the JSON for the CLI and logs. */
(function () {
    'use strict';

    var csrfEl = document.querySelector('meta[name="csrf-token"]');
    var csrf = csrfEl ? csrfEl.getAttribute('content') : '';

    function showLine(container, text, ok) {
        var line = document.createElement('div');
        line.className = 'd-flex align-items-center gap-2 ' + (ok ? 'text-success' : 'text-danger');

        var icon = document.createElement('i');
        icon.className = 'ri ' + (ok ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill');

        var label = document.createElement('span');
        label.textContent = text;

        line.appendChild(icon);
        line.appendChild(label);
        container.replaceChildren(line);
    }

    document.querySelectorAll('.js-courier-test').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.dataset.target);

            button.disabled = true;

            if (target) {
                showLine(target, 'Testing…', true);
            }

            fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            })
                .then(function (response) { return response.json(); })
                .then(function (result) {
                    if (target) {
                        showLine(target, result.message || '', !!result.ok);
                    }
                })
                .catch(function () {
                    if (target) {
                        showLine(target, 'The test could not be run.', false);
                    }
                })
                .finally(function () {
                    button.disabled = false;
                });
        });
    });

    function flashCopied(button, text) {
        var label = button.getAttribute('data-label') || button.textContent;
        button.setAttribute('data-label', label);
        button.textContent = text;
        setTimeout(function () { button.textContent = label; }, 1500);
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        // Fallback for browsers without the async clipboard API: copy through a
        // temporary textarea, because a hidden input cannot be selected.
        return new Promise(function (resolve, reject) {
            var helper = document.createElement('textarea');
            helper.value = text;
            helper.setAttribute('readonly', '');
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            document.body.appendChild(helper);
            helper.select();
            helper.setSelectionRange(0, 99999);
            var ok = document.execCommand('copy');
            document.body.removeChild(helper);
            ok ? resolve() : reject(new Error('copy failed'));
        });
    }

    // Plain fields: the value is already in the input (the webhook endpoint).
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.copy);
            if (!input) { return; }

            copyText(input.value)
                .then(function () { flashCopied(button, 'Copied'); })
                .catch(function () { flashCopied(button, 'Copy failed'); });
        });
    });

    // Secrets: the page holds only the mask, so the token is fetched when the
    // admin actually asks for it.
    document.querySelectorAll('[data-copy-url]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.disabled) { return; }

            button.disabled = true;

            fetch(button.dataset.copyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) { throw new Error('token request failed'); }
                    return response.json();
                })
                .then(function (result) { return copyText(result.token || ''); })
                .then(function () { flashCopied(button, 'Copied'); })
                .catch(function () { flashCopied(button, 'Copy failed'); })
                .finally(function () { button.disabled = false; });
        });
    });
})();
