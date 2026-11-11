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

        var visibleGroups = 0;

        panel.querySelectorAll('[data-field]').forEach(function (node) {
            if (node.tagName === 'TEMPLATE') {
                return;
            }

            var field = node.getAttribute('data-field');
            var visible = allowed === null || allowed.indexOf(field) !== -1;

            node.classList.toggle('d-none', !visible);
            setInputsDisabled(node, !visible);

            if (visible && node.classList.contains('landing-content-field')) {
                visibleGroups += 1;
            }

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

        var emptyState = document.getElementById('landingContentEmpty');
        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleGroups > 0);
        }
    }

    if (templateSelect) {
        templateSelect.addEventListener('change', syncTemplateFields);
        syncTemplateFields();
    }

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
})();
