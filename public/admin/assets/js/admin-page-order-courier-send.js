    (function () {
        'use strict';

        /*
         * Copy the webhook token.

         * navigator.clipboard needs a secure context, so a plain-http staging
         * host falls back to select + execCommand. Selection alone leaves the
         * admin able to copy by hand if even that fails.
         */
        document.querySelectorAll('[data-copy-token]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.querySelector(button.getAttribute('data-copy-token'));
                if (!input) return;

                input.focus();
                input.select();
                input.setSelectionRange(0, 99999);

                function done() {
                    var original = button.innerHTML;
                    button.innerHTML = '<i class="ri ri-check-line"></i>';
                    setTimeout(function () { button.innerHTML = original; }, 1500);
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(input.value).then(done).catch(fallback);
                } else {
                    fallback();
                }

                function fallback() {
                    try {
                        document.execCommand('copy');
                        done();
                    } catch (e) {
                        /* the text is selected; the admin can copy manually */
                    }
                }
            });
        });

        /*
         * Second confirmation before an irreversible, billable action.
         *
         * The checkbox is the deliberate "I have read it" gate; this is the
         * "are you sure" on top, so a mis-click that happens to land on Send
         * still cannot create the consignment.
         */
        var form = document.getElementById('fmCourierForm');
        if (!form) return;

        var box = document.getElementById('courierConfirm');
        var sendBtn = form.querySelector('[data-confirm-send]');

        if (box && sendBtn) {
            // Disabled until the box is ticked. An HTML-disabled button is
            // actually un-clickable, so the click handler below is a second
            // layer rather than the only one.
            sendBtn.disabled = true;

            box.addEventListener('change', function () {
                sendBtn.disabled = !box.checked;
                box.classList.remove('is-invalid');
            });
        }
    })();
