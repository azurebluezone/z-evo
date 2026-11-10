    (function () {
        try {
            var input = document.getElementById('orderWhatsAppEnabled');
            var btn = document.getElementById('orderWhatsAppToggleBtn');
            if (!input || !btn) return;

            function applyState(val) {
                input.value = String(val === 1 || val === '1' ? 1 : 0);
                btn.textContent = input.value === '1' ? 'Enabled' : 'Disabled';
            }

            document.addEventListener('click', function (e) {
                var el = e.target;
                if (!el) return;
                var item = el.closest ? el.closest('[data-order-whatsapp-enabled]') : null;
                if (!item) return;
                var v = item.getAttribute('data-order-whatsapp-enabled');
                applyState(v);
            }, true);

            applyState(input.value);
        } catch (e) {
        }
    })();
