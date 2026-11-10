    document.addEventListener('DOMContentLoaded', function () {
        // DataTable disabled - using server-side pagination (10 items per page)

        function toastSuccess(message) {
            if (window.toastr && typeof window.toastr.success === 'function') {
                window.toastr.success(message);
            }
        }

        function toastError(message) {
            if (window.toastr && typeof window.toastr.error === 'function') {
                window.toastr.error(message);
            }
        }

        function getCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function getSelectedProductIds() {
            return Array.from(document.querySelectorAll('input.checkbox:checked')).map(function (el) {
                return el.value;
            });
        }

        var checkAll = document.querySelector('.checkall');
        if (checkAll) {
            checkAll.addEventListener('change', function (e) {
                var checked = !!e.target.checked;
                document.querySelectorAll('input.checkbox').forEach(function (cb) {
                    cb.checked = checked;
                });
            });
        }

        document.addEventListener('change', function (e) {
            var target = e.target;
            if (!(target instanceof HTMLElement)) return;

            if (target.classList.contains('js-auto-submit')) {
                var form = target.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });

        document.addEventListener('change', function (e) {
            var el = e.target;
            if (!(el instanceof HTMLInputElement)) return;
            if (!el.classList.contains('js-product-flag')) return;

            var isOn = el.checked;
            var url = isOn ? el.getAttribute('data-url-on') : el.getAttribute('data-url-off');
            var productId = el.getAttribute('data-product-id');
            if (!url || !productId) return;

            var body = new URLSearchParams();
            body.append('product_ids[]', productId);
            // The flag the switch is asking for. It used to ride in the query
            // string of a GET; the endpoint is a POST now, so it is body data.
            var src = isOn ? el.getAttribute('data-status-on') : el.getAttribute('data-status-off');
            if (src !== null && src !== '') body.append('status', src);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.status === 'success') {
                        toastSuccess(res.message || 'Updated');
                    } else {
                        toastError('Failed something wrong');
                        el.checked = !isOn;
                    }
                })
                .catch(function () {
                    toastError('Failed something wrong');
                    el.checked = !isOn;
                });
        });

        document.addEventListener('click', function (e) {
            var a = e.target && (e.target.closest ? e.target.closest('.hotdeal_update, .update_status') : null);
            if (!a) return;

            e.preventDefault();

            var url = a.getAttribute('data-url');
            var status = a.getAttribute('data-status');
            var productIds = getSelectedProductIds();
            if (!productIds.length) {
                toastError('Please Select A Product First !');
                return;
            }

            var body = new URLSearchParams();
            productIds.forEach(function (id) { body.append('product_ids[]', id); });
            body.append('status', status);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.status === 'success') {
                        toastSuccess(res.message || 'Updated');
                        window.location.reload();
                    } else {
                        toastError('Failed something wrong');
                    }
                })
                .catch(function () {
                    toastError('Failed something wrong');
                });
        });
    });
