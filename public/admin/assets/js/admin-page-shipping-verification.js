    (function () {
        function recalc() {
            let totalQty = 0;
            let totalWeight = 0;

            document.querySelectorAll('table .qty').forEach(function (qtyEl) {
                const tr = qtyEl.closest('tr');
                const qty = parseFloat(qtyEl.getAttribute('data-qty') || '0') || 0;
                const weightInput = tr ? tr.querySelector('.weight-per') : null;
                const w = weightInput ? (parseFloat(weightInput.value) || 0) : 0;
                const lineW = qty * w;

                const lineEl = tr ? tr.querySelector('.line-weight') : null;
                if (lineEl) lineEl.textContent = lineW.toFixed(2);

                totalQty += qty;
                totalWeight += lineW;
            });

            const totalQtyEl = document.getElementById('totalQty');
            const totalWeightEl = document.getElementById('totalWeight');
            if (totalQtyEl) totalQtyEl.textContent = totalQty;
            if (totalWeightEl) totalWeightEl.textContent = totalWeight.toFixed(2);

            const totalQtyInput = document.getElementById('totalQtyInput');
            const totalWeightInput = document.getElementById('totalWeightInput');
            if (totalQtyInput) totalQtyInput.value = String(totalQty);
            if (totalWeightInput) totalWeightInput.value = totalWeight.toFixed(2);
        }

        document.querySelectorAll('.weight-per').forEach(function (el) {
            el.addEventListener('input', recalc);
        });

        recalc();
    })();
