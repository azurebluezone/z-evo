    /*
     * Password visibility toggle.
     *
     * The icon classes are the MASK-based Remix build, so the base `ri` class is
     * load-bearing: `className = 'ri-eye-line'` alone drops it and the glyph
     * collapses to 0x0 (the empty button this control used to be).
     *
     * aria-pressed + aria-label are updated too, because a screen reader cannot
     * see an eye icon change: the button must say what it is about to do.
     */
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        var target = document.querySelector(btn.getAttribute('data-target'));
        if (!target) return;

        var icon = btn.querySelector('i');
        var label = btn.getAttribute('data-label') || 'password';

        btn.addEventListener('click', function () {
            var reveal = target.type === 'password';

            target.type = reveal ? 'text' : 'password';
            icon.className = reveal ? 'ri ri-eye-line' : 'ri ri-eye-off-line';
            btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            btn.setAttribute('aria-label', (reveal ? 'Hide ' : 'Show ') + label);

            // Keep the caret where the user was typing.
            target.focus({ preventScroll: true });
        });
    });
