        // The eye button toggles the password field's type. The old view used
        // the previous theme's .password-eye span, which needs that theme's
        // app.min.js - a file this admin does not ship.
        (function () {
            var toggle = document.getElementById('toggle-password');
            var input = document.getElementById('password');
            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                toggle.innerHTML = '<i class="icon-base ri ' + (show ? 'ri-eye-off-line' : 'ri-eye-line') + '"></i>';
                input.focus();
            });
        })();
    
