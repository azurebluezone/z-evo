    (function () {
        'use strict';

        var button = document.getElementById('copy-payload');
        var payload = document.getElementById('payload');
        if (!button || !payload) { return; }

        button.addEventListener('click', function () {
            var text = payload.textContent || '';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            }
            var label = button.innerHTML;
            button.innerHTML = 'Copied';
            setTimeout(function () { button.innerHTML = label; }, 1500);
        });
    })();
