/* Social icon pickers + footer-links repeater.
   Moved from backEnd/website-setup/footer.blade.php. Server values ride
   in on [data-footer-setup]: iconMap / colorMap (SocialIcons::all()) and
   nextFooterLinkIndex. */
(function () {
    'use strict';

        var cfgEl = document.querySelector('[data-footer-setup]');
        var CFG = cfgEl ? (function () {
            try { return JSON.parse(cfgEl.getAttribute('data-footer-setup')); }
            catch (e) { return {}; }
        })() : {};

        // Stored Font Awesome class -> admin preview class / brand colour.
        // Supplied by the controller from App\Support\SocialIcons::all().
        var iconMap = CFG.iconMap || {};
        var colorMap = CFG.colorMap || {};

        function normalizeIconClass(value) {
            return String(value || '').toLowerCase().trim();
        }

        function resolveRi(value) {
            return iconMap[normalizeIconClass(value)] || 'ri ri-global-line';
        }

        function setActiveOption(picker, value) {
            var needle = normalizeIconClass(value);
            picker.querySelectorAll('.icon-picker__option').forEach(function (option) {
                var active = normalizeIconClass(option.dataset.iconValue) === needle;
                option.classList.toggle('active', active);
                option.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        function applyIcon(picker, value, options) {
            options = options || {};
            var input = picker.querySelector('[data-icon-input]');
            var preview = picker.querySelector('[data-icon-preview]');
            var label = picker.querySelector('[data-icon-label]');
            var custom = picker.querySelector('[data-icon-custom]');
            var toggle = picker.querySelector('.icon-picker__toggle');
            var cleaned = String(value || '').trim();
            var needle = normalizeIconClass(cleaned);
            var presetLabel = '';

            picker.querySelectorAll('.icon-picker__option').forEach(function (option) {
                if (normalizeIconClass(option.dataset.iconValue) === needle) {
                    presetLabel = option.dataset.iconLabel || '';
                }
            });

            if (input) {
                input.value = cleaned;
            }
            if (preview) {
                preview.innerHTML = '<i class="' + resolveRi(cleaned) + '"></i>';
            }
            if (label) {
                label.textContent = presetLabel || cleaned || 'Choose a platform';
            }
            if (custom && options.syncCustom !== false && custom.value !== cleaned) {
                custom.value = cleaned;
            }
            if (toggle && cleaned) {
                toggle.classList.remove('is-invalid');
            }

            setActiveOption(picker, cleaned);

            var colorTarget = picker.getAttribute('data-color-target');
            if (colorTarget && options.applyColor !== false) {
                var colorInput = document.querySelector(colorTarget);
                var presetColor = colorMap[needle];
                if (colorInput && presetColor) {
                    colorInput.value = presetColor;
                }
            }
        }

        function hidePicker(picker) {
            var toggle = picker.querySelector('[data-bs-toggle="dropdown"]');
            if (!toggle || !window.bootstrap || !window.bootstrap.Dropdown) {
                return;
            }
            var dropdown = window.bootstrap.Dropdown.getInstance(toggle);
            if (dropdown) {
                dropdown.hide();
            }
        }

        function initIconPicker(picker) {
            if (!picker || picker.__iconPicker) {
                return;
            }
            picker.__iconPicker = true;

            picker.querySelectorAll('.icon-picker__option').forEach(function (option) {
                option.addEventListener('click', function () {
                    applyIcon(picker, option.dataset.iconValue || '');
                    hidePicker(picker);
                });
            });

            var custom = picker.querySelector('[data-icon-custom]');
            if (custom) {
                custom.addEventListener('input', function () {
                    applyIcon(picker, custom.value, { syncCustom: false });
                });
                custom.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') {
                        return;
                    }
                    event.preventDefault();
                    applyIcon(picker, custom.value);
                    hidePicker(picker);
                });
            }
        }

        document.querySelectorAll('[data-icon-picker]').forEach(initIconPicker);

        // The add form needs a chosen platform before it can post.
        var createForm = document.getElementById('socialCreateForm');
        if (createForm) {
            createForm.addEventListener('submit', function (event) {
                var picker = document.getElementById('social_new_picker');
                var input = picker ? picker.querySelector('[data-icon-input]') : null;
                if (input && !input.value.trim()) {
                    event.preventDefault();
                    var toggle = picker.querySelector('.icon-picker__toggle');
                    if (toggle) {
                        toggle.classList.add('is-invalid');
                        toggle.focus();
                    }
                }
            });
        }

        // --- Footer about text: Quill + visible-length guard ---------------
        var aboutEditor = document.getElementById('footer-about-editor');
        var aboutField = document.getElementById('footer_about_text');
        var aboutCount = document.getElementById('footer-about-count');
        var aboutAlert = document.getElementById('footer-about-limit-alert');
        var aboutForm = document.getElementById('footerContentAdminForm');
        var aboutLimit = 400;

        function visibleFooterLength(html) {
            var box = document.createElement('div');
            box.innerHTML = String(html || '');
            return (box.textContent || '').replace(/\s+/g, ' ').trim().length;
        }

        function refreshFooterLength() {
            var html = aboutField ? aboutField.value : '';

            if (aboutEditor && aboutEditor.__quill) {
                html = aboutEditor.__quill.root.innerHTML;
            }

            var length = visibleFooterLength(html);

            if (aboutCount) {
                aboutCount.textContent = String(length);
                aboutCount.parentElement.classList.toggle('text-danger', length > aboutLimit);
                aboutCount.parentElement.classList.toggle('fw-semibold', length > aboutLimit);
            }

            if (aboutAlert) {
                aboutAlert.classList.toggle('d-none', length <= aboutLimit);
            }

            return length;
        }

        if (aboutEditor) {
            var startAboutCounter = function () {
                refreshFooterLength();

                if (aboutEditor.__quill) {
                    aboutEditor.__quill.on('text-change', refreshFooterLength);
                } else {
                    var root = aboutEditor.querySelector('.ql-editor');
                    if (root && window.MutationObserver) {
                        new MutationObserver(refreshFooterLength).observe(root, {
                            childList: true,
                            characterData: true,
                            subtree: true
                        });
                    }
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    window.setTimeout(startAboutCounter, 0);
                });
            } else {
                window.setTimeout(startAboutCounter, 0);
            }
        }

        if (aboutForm) {
            aboutForm.addEventListener('submit', function (event) {
                if (refreshFooterLength() <= aboutLimit) {
                    return;
                }

                event.preventDefault();
                if (aboutAlert) {
                    aboutAlert.classList.remove('d-none');
                    aboutAlert.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            });
        }

        // --- Footer link columns: repeater ---------------------------------
        var linksTable = document.getElementById('footerLinksTable');
        var linkAdd = document.getElementById('footerLinkAdd');
        var linkTemplate = document.getElementById('footerLinkRowTemplate');
        var nextFooterLinkIndex = CFG.nextFooterLinkIndex || 0;
        var footerLinkLimit = 30;

        function footerLinkRows() {
            return linksTable ? linksTable.querySelectorAll('tbody .footer-link-row') : [];
        }

        function refreshFooterLinkState() {
            var count = footerLinkRows().length;
            var badge = document.getElementById('footerLinkCountBadge');

            if (badge) {
                badge.textContent = count + (count === 1 ? ' link' : ' links');
            }

            if (linksTable) {
                var empty = linksTable.querySelector('.footer-link-empty-state');
                if (empty) {
                    empty.classList.toggle('d-none', count > 0);
                }
            }

            if (linkAdd) {
                linkAdd.disabled = count >= footerLinkLimit;
            }
        }

        if (linksTable && linkAdd && linkTemplate) {
            linkAdd.addEventListener('click', function () {
                if (footerLinkRows().length >= footerLinkLimit) {
                    return;
                }

                var html = linkTemplate.innerHTML.replace(/__INDEX__/g, String(nextFooterLinkIndex++));
                linksTable.querySelector('tbody').insertAdjacentHTML('beforeend', html);

                var rows = footerLinkRows();
                var lastRow = rows[rows.length - 1];
                var firstInput = lastRow ? lastRow.querySelector('input[name$="[label]"]') : null;
                if (firstInput) {
                    firstInput.focus();
                }

                refreshFooterLinkState();
            });

            linksTable.addEventListener('click', function (event) {
                var button = event.target.closest('.js-footer-link-remove');
                if (!button) {
                    return;
                }

                var row = button.closest('tr');
                if (row) {
                    row.remove();
                }

                refreshFooterLinkState();
            });

            refreshFooterLinkState();
        }

        // --- Edit modal ------------------------------------------------------
        var editForm = document.getElementById('socialEditForm');
        if (!editForm) {
            return;
        }

        var editPicker = document.getElementById('social_edit_picker');

        document.querySelectorAll('.js-social-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                editForm.elements['id'].value = button.dataset.id || '';
                editForm.elements['title'].value = button.dataset.title || '';
                editForm.elements['link'].value = button.dataset.link || '';
                editForm.elements['status'].checked = button.dataset.status === '1';

                if (editPicker) {
                    applyIcon(editPicker, button.dataset.icon || '', { applyColor: false });
                }

                var color = document.getElementById('social_edit_color');
                if (color) {
                    color.value = button.dataset.color || '#111827';
                }
            });
        });
})();
