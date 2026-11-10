/* Courier settings: connection-test buttons + copy-to-clipboard.
   Moved from backEnd/courier/settings.blade.php; CSRF now comes from the
   layout's <meta name="csrf-token"> instead of @json(csrf_token()). */
    /*
     * Vanilla JS on purpose: this block is rendered before the layout's jQuery
     * bundle, and none of this needs a library.
     */
    (function () {
        'use strict';

        var csrfEl = document.querySelector('meta[name="csrf-token"]');
        var csrf = csrfEl ? csrfEl.getAttribute('content') : '';

        function textRow(container, text, className) {
            var line = document.createElement('div');
            line.className = className || '';
            line.textContent = text;
            container.appendChild(line);
        }

        document.querySelectorAll('.js-courier-test').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.dataset.target);
                var original = button.innerHTML;

                button.disabled = true;
                button.innerHTML = 'Testing…';
                if (target) {
                    target.replaceChildren();
                    textRow(target, 'Contacting the courier…', 'text-muted small');
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
                        if (!target) { return; }

                        target.replaceChildren();
                        textRow(target, result.message || '', result.ok ? 'text-success small fw-medium' : 'text-danger small fw-medium');

                        var list = document.createElement('ul');
                        list.className = 'small mb-0 ps-3';

                        (result.steps || []).forEach(function (step) {
                            var item = document.createElement('li');
                            item.textContent = (step.ok ? '✓ ' : '✗ ') + step.label;
                            if (step.detail) {
                                var detail = document.createElement('span');
                                detail.className = 'text-muted';
                                detail.textContent = ' — ' + step.detail;
                                item.appendChild(detail);
                            }
                            list.appendChild(item);
                        });

                        target.appendChild(list);
                    })
                    .catch(function (error) {
                        if (target) {
                            target.replaceChildren();
                            textRow(target, 'Test failed: ' + error, 'text-danger small');
                        }
                    })
                    .finally(function () {
                        button.disabled = false;
                        button.innerHTML = original;
                    });
            });
        });

        document.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.copy);
                if (!input) { return; }

                input.select();
                input.setSelectionRange(0, 99999);

                var done = function () {
                    var label = button.textContent;
                    button.textContent = 'Copied';
                    setTimeout(function () { button.textContent = label; }, 1500);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value).then(done);
                } else {
                    document.execCommand('copy');
                    done();
                }
            });
        });
    })();
