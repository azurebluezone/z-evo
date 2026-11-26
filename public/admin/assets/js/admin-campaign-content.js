// Admin campaign form: show only the content fields the selected template
// enables, and add/remove repeatable rows.
//
// Old templates put an array of field names in templates.config.extra_fields.
// New templates use an object: { fields: [...], columns: { repeater: [...] } }
// so a toggled-off repeater column is hidden and disabled.
// Vanilla JS on purpose: this page needs no jQuery/Select2 for this panel.
(function () {
    'use strict';

    var panel = document.getElementById('landingContentPanel');
    if (!panel) {
        return;
    }

    var templateSelect = document.querySelector('select[name="template_id"]');
    var fieldMap = {};

    try {
        fieldMap = JSON.parse(panel.getAttribute('data-template-fields') || '{}');
    } catch (error) {
        fieldMap = {};
    }

    function setInputsDisabled(node, disabled) {
        node.querySelectorAll('input, select, textarea').forEach(function (input) {
            input.disabled = disabled;
        });
    }

    function syncIconPreview(select) {
        if (!select) {
            return;
        }

        var column = select.closest('[data-column]') || select.parentElement;
        if (!column) {
            return;
        }

        var preview = column.querySelector('.js-icon-preview');
        var label = column.querySelector('.js-icon-preview-label');
        var option = select.options[select.selectedIndex];

        if (preview) {
            preview.className = (select.value || 'fa-solid fa-circle-check') + ' js-icon-preview';
        }

        if (label) {
            label.textContent = option ? option.text : '';
        }
    }

    function initIconPreviews() {
        panel.querySelectorAll('.js-icon-select').forEach(syncIconPreview);
    }

    function syncTemplateFields() {
        if (!templateSelect) {
            return;
        }

        var selected = String(templateSelect.value || '');
        var entry = Object.prototype.hasOwnProperty.call(fieldMap, selected) ? fieldMap[selected] : null;
        var allowed = null;
        var allowedColumns = null;

        if (Array.isArray(entry)) {
            allowed = entry;
        } else if (entry && typeof entry === 'object') {
            allowed = Array.isArray(entry.fields) ? entry.fields : [];
            allowedColumns = entry.columns && typeof entry.columns === 'object' ? entry.columns : null;
        }

        panel.querySelectorAll('[data-field]').forEach(function (node) {
            if (node.tagName === 'TEMPLATE') {
                return;
            }

            var field = node.getAttribute('data-field');
            var visible = allowed === null || allowed.indexOf(field) !== -1;

            node.classList.toggle('d-none', !visible);
            setInputsDisabled(node, !visible);

            if (!visible || !allowedColumns || !Array.isArray(allowedColumns[field])) {
                return;
            }

            var columns = allowedColumns[field];

            node.querySelectorAll('[data-column]').forEach(function (columnNode) {
                var column = columnNode.getAttribute('data-column');
                var showColumn = columns.indexOf(column) !== -1;

                columnNode.classList.toggle('d-none', !showColumn);
                setInputsDisabled(columnNode, !showColumn);
            });
        });
    }
    if (templateSelect) {
        templateSelect.addEventListener('change', syncTemplateFields);
        syncTemplateFields();
    }

    initIconPreviews();

    var nextIndex = 1000;

    panel.addEventListener('click', function (event) {
        var addButton = event.target.closest('.js-add-row');

        if (addButton) {
            var field = addButton.getAttribute('data-field');
            var rows = panel.querySelector('.js-rows[data-field="' + field + '"]');
            var template = panel.querySelector('template.js-row-template[data-template-field="' + field + '"]');

            if (!rows || !template) {
                return;
            }

            rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex)));
            nextIndex += 1;
            syncTemplateFields();
            initIconPreviews();

            if (window.AdminQuill && typeof window.AdminQuill.initAll === 'function') {
                window.AdminQuill.initAll();
            }
            return;
        }

        var removeButton = event.target.closest('.js-remove-row');

        if (removeButton) {
            var row = removeButton.closest('.js-row');
            if (row) {
                row.remove();
            }
        }
    });

    // Repeater image columns upload a file and keep the stored path in a
    // hidden field; show the chosen file immediately instead of waiting for
    // a save/reload cycle.
    panel.addEventListener('change', function (event) {
        var input = event.target;

        if (input && input.classList && input.classList.contains('js-icon-select')) {
            syncIconPreview(input);
            return;
        }

        if (!input || !input.classList || !input.classList.contains('js-repeater-image')) {
            return;
        }

        var file = input.files && input.files[0];

        if (!file) {
            return;
        }

        var column = input.closest('[data-column]');
        var preview = column ? column.querySelector('.js-repeater-image-preview') : null;

        if (!preview) {
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    });
})();
