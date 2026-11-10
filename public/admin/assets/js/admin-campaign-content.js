// Admin campaign form: show only the content fields the selected template
// declares in templates.config.extra_fields, and add/remove repeatable rows.
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

    function syncTemplateFields() {
        if (!templateSelect) {
            return;
        }

        var selected = String(templateSelect.value || '');
        var allowed = Object.prototype.hasOwnProperty.call(fieldMap, selected) ? fieldMap[selected] : null;
        var visibleGroups = 0;

        panel.querySelectorAll('[data-field]').forEach(function (node) {
            if (node.tagName === 'TEMPLATE') {
                return;
            }

            var visible = allowed === null || allowed.indexOf(node.getAttribute('data-field')) !== -1;
            node.classList.toggle('d-none', !visible);

            if (visible && node.classList.contains('landing-content-field')) {
                visibleGroups += 1;
            }
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
