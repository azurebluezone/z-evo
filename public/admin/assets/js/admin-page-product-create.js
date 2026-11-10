    // WAF Safety: Remove empty file inputs before form submit to prevent
    // LiteSpeed/ModSecurity 403 errors caused by empty filename + application/octet-stream
    (function () {
      var form = document.getElementById('productForm');
      if (form) {
        form.addEventListener('submit', function () {
          var fileInputs = form.querySelectorAll('input[type="file"]');
          fileInputs.forEach(function (input) {
            if (!input.files || input.files.length === 0) {
              input.removeAttribute('name');
            }
          });
        });
      }
    })();

    (function () {
      function byId(id) { return document.getElementById(id); }

      function resetSelect(selectEl, placeholder) {
        if (!selectEl) return;
        selectEl.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder || 'Select...';
        selectEl.appendChild(opt);
      }

      function setOptionsFromMap(selectEl, mapObj, placeholder, selectedValue) {
        resetSelect(selectEl, placeholder);
        if (!selectEl || !mapObj) return;

        Object.keys(mapObj).forEach(function (key) {
          var label = mapObj[key];
          if (label === null || label === undefined) return;
          var opt = document.createElement('option');
          opt.value = String(key);
          opt.textContent = String(label);
          if (selectedValue !== null && selectedValue !== undefined && String(selectedValue) === String(key)) {
            opt.selected = true;
          }
          selectEl.appendChild(opt);
        });
      }

      function fetchJson(url) {
        return fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        }).then(function (res) {
          if (!res.ok) throw new Error('Request failed: ' + res.status);
          return res.json();
        });
      }

      function initCategoryDependentDropdowns() {
        var categoryEl = byId('category_id');
        var subcategoryEl = byId('subcategory_id');
        var childcategoryEl = byId('childcategory_id');
        if (!categoryEl || !subcategoryEl || !childcategoryEl) return;

        var oldSub = subcategoryEl.getAttribute('data-selected') || '';
        var oldChild = childcategoryEl.getAttribute('data-selected') || '';

        function loadSubcategories(categoryId, selectedSubId) {
          resetSelect(subcategoryEl, 'Loading...');
          resetSelect(childcategoryEl, 'Select Childcategory..');
          if (!categoryId) {
            resetSelect(subcategoryEl, 'Select Subcategory..');
            return Promise.resolve(null);
          }
          return fetchJson('/ajax-product-subcategory?category_id=' + encodeURIComponent(categoryId))
            .then(function (data) {
              setOptionsFromMap(subcategoryEl, data || {}, 'Select Subcategory..', selectedSubId || '');
              return data;
            })
            .catch(function () {
              resetSelect(subcategoryEl, 'Select Subcategory..');
              return null;
            });
        }

        function loadChildcategories(subcategoryId, selectedChildId) {
          resetSelect(childcategoryEl, 'Loading...');
          if (!subcategoryId) {
            resetSelect(childcategoryEl, 'Select Childcategory..');
            return Promise.resolve(null);
          }
          return fetchJson('/ajax-product-childcategory?subcategory_id=' + encodeURIComponent(subcategoryId))
            .then(function (data) {
              setOptionsFromMap(childcategoryEl, data || {}, 'Select Childcategory..', selectedChildId || '');
              return data;
            })
            .catch(function () {
              resetSelect(childcategoryEl, 'Select Childcategory..');
              return null;
            });
        }

        categoryEl.addEventListener('change', function () {
          var catId = (categoryEl.value || '').trim();
          loadSubcategories(catId, '');
        });

        subcategoryEl.addEventListener('change', function () {
          var subId = (subcategoryEl.value || '').trim();
          loadChildcategories(subId, '');
        });

        // On page load (e.g. after validation error), rehydrate dependent selects.
        var initialCategoryId = (categoryEl.value || '').trim();
        if (initialCategoryId) {
          loadSubcategories(initialCategoryId, oldSub).then(function () {
            var activeSub = (subcategoryEl.value || '').trim();
            if (activeSub) loadChildcategories(activeSub, oldChild);
          });
        } else {
          resetSelect(subcategoryEl, 'Select Subcategory..');
          resetSelect(childcategoryEl, 'Select Childcategory..');
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCategoryDependentDropdowns);
      } else {
        initCategoryDependentDropdowns();
      }
    })();

    (function () {
      function safeJsonParse(text, fallback) {
        try {
          return JSON.parse(text);
        } catch (e) {
          return fallback;
        }
      }

      function uniqBy(arr, keyFn) {
        var seen = new Set();
        var out = [];
        for (var i = 0; i < arr.length; i++) {
          var k = keyFn(arr[i]);
          if (seen.has(k)) continue;
          seen.add(k);
          out.push(arr[i]);
        }
        return out;
      }

      function cartesianProduct(groups) {
        if (!groups.length) return [];
        var result = [[]];
        for (var i = 0; i < groups.length; i++) {
          var next = [];
          for (var r = 0; r < result.length; r++) {
            for (var g = 0; g < groups[i].length; g++) {
              next.push(result[r].concat([groups[i][g]]));
            }
          }
          result = next;
        }
        return result;
      }

      function initSelect2(el) {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
        var $el = window.jQuery(el);
        if ($el.data('select2')) return;
        var placeholder = $el.data('placeholder') || $el.attr('data-placeholder') || 'Select...';
        var allowClear = String($el.data('allow-clear') || $el.attr('data-allow-clear') || 'false') === 'true';
        $el.select2({ width: '100%', closeOnSelect: false, placeholder: placeholder, allowClear: allowClear });
      }

      function initProductVariantUi() {
        var attrsSelect = document.getElementById('variant_attributes');
        var attrsJsonEl = document.getElementById('variant_attributes_json');
        var valsJsonEl = document.getElementById('variant_attribute_values_json');
        var valuesWrap = document.getElementById('variant_attribute_values_container');
        var tableWrap = document.getElementById('variants_table');
        var tbody = document.getElementById('variants_container');

        if (!attrsSelect || !attrsJsonEl || !valsJsonEl || !valuesWrap || !tableWrap || !tbody) return;

        initSelect2(attrsSelect);

        var attributes = safeJsonParse((attrsJsonEl.value || '').trim(), []);
        var attributeValues = safeJsonParse((valsJsonEl.value || '').trim(), []);

        function getSelectedAttributeIds() {
          return Array.prototype.slice.call(attrsSelect.selectedOptions || []).map(function (o) {
            return parseInt(o.value, 10);
          }).filter(function (n) { return !isNaN(n); });
        }

        // Rebuilding the value <select>s from scratch drops the current picks
        // (and Select2's state with them), so snapshot them first and mark the
        // same options selected again. Keyed by attribute id.
        function captureValueSelections() {
          var picked = {};
          valuesWrap.querySelectorAll('.js-attr-values').forEach(function (sel) {
            picked[sel.getAttribute('data-attr-id')] =
              Array.prototype.slice.call(sel.selectedOptions || []).map(function (o) {
                return String(o.value);
              });
          });
          return picked;
        }

        function renderValueSelectors() {
          var selectedAttrIds = getSelectedAttributeIds();
          var previousSelections = captureValueSelections();
          valuesWrap.innerHTML = '';

          if (!selectedAttrIds.length) return;

          var rows = [];
          selectedAttrIds.forEach(function (attrId) {
            var attr = attributes.find(function (a) { return parseInt(a.id, 10) === attrId; });
            if (!attr) return;

            var values = attributeValues.filter(function (v) {
              return parseInt(v.attribute_id, 10) === attrId;
            });

            values = uniqBy(values, function (v) { return String(v.id); });

            var html = '';
            html += '<div class="col-12 col-lg-6">';
            html += '  <div class="form-floating form-floating-outline">';
            html += '    <select class="select2 form-select form-select-sm js-attr-values" multiple data-placeholder="Select values" data-allow-clear="true" data-attr-id="' + attrId + '" data-attr-slug="' + (attr.slug || ('attr_' + attrId)) + '" data-attr-name="' + (attr.name || '') + '">';
            html += '      <option value=""></option>';
            values.forEach(function (v) {
              var label = (v.value !== undefined && v.value !== null) ? String(v.value) : (v.name ? String(v.name) : String(v.id));
              // Restore a value this attribute already had selected.
              var isPicked = (previousSelections[String(attrId)] || []).indexOf(String(v.id)) !== -1;
              html += '    <option value="' + v.id + '"' + (isPicked ? ' selected' : '') + '>' + label + '</option>';
            });
            html += '    </select>';
            html += '    <label>' + (attr.name || 'Attribute') + ' Values</label>';
            html += '  </div>';
            html += '</div>';

            rows.push(html);
          });

          if (!rows.length) return;

          valuesWrap.innerHTML = '<div class="row g-3">' + rows.join('') + '</div>';
          valuesWrap.querySelectorAll('.js-attr-values').forEach(function (el) {
            initSelect2(el);
            el.addEventListener('change', function () {
              buildVariants();
            });

            if (window.jQuery) {
              try {
                window.jQuery(el).on('select2:select select2:unselect change', function () {
                  buildVariants();
                });
              } catch (e) {
                // ignore
              }
            }
          });
        }

        // A rebuild recreates every row, so numbers already typed for a
        // combination are kept in this store, keyed by that combination's ids
        // JSON, and restored afterwards. The store outlives a temporary empty
        // table (e.g. an attribute was just added but has no value yet).
        // File inputs cannot be refilled by script and are left empty.
        var rowValuesStore = {};

        function captureRowValues() {
          Array.prototype.slice.call(tbody.querySelectorAll('tr')).forEach(function (tr) {
            var idsInput = tr.querySelector('input[name="variant_attrs_ids[]"]');
            if (!idsInput) return;
            var read = function (name) {
              var el = tr.querySelector('input[name="' + name + '"]');
              return el ? el.value : '';
            };
            rowValuesStore[idsInput.value] = {
              purchase: read('variant_purchase_price[]'),
              sale: read('variant_sale_price[]'),
              discount: read('variant_discount_price[]'),
              stock: read('variant_initial_stock[]')
            };
          });
        }

        function buildVariants() {
          var selects = Array.prototype.slice.call(valuesWrap.querySelectorAll('.js-attr-values'));
          // Snapshot before any early return clears the table.
          captureRowValues();

          if (!selects.length) {
            tbody.innerHTML = '';
            tableWrap.style.display = 'none';
            return;
          }

          var groups = [];
          var incomplete = false;
          selects.forEach(function (sel) {
            var opts = Array.prototype.slice.call(sel.selectedOptions || []);
            var attrSlug = sel.getAttribute('data-attr-slug') || 'attr';
            var attrName = sel.getAttribute('data-attr-name') || attrSlug;

            if (!opts.length) {
              incomplete = true;
              return;
            }

            var picked = opts.map(function (o) {
              return {
                attrSlug: attrSlug,
                attrName: attrName,
                valueId: parseInt(o.value, 10),
                valueText: (o.textContent || '').trim()
              };
            }).filter(function (x) { return x.valueId && x.valueText; });

            if (!picked.length) {
              incomplete = true;
              return;
            }

            groups.push(picked);
          });

          // Every selected attribute needs at least one value. Building rows
          // from a partial selection would post a combination that is missing
          // an attribute, so wait for a complete pick instead.
          if (incomplete || !groups.length) {
            tbody.innerHTML = '';
            tableWrap.style.display = 'none';
            return;
          }

          var combos = cartesianProduct(groups);
          tbody.innerHTML = '';

          combos.forEach(function (combo, idx) {
            var attrsTextMap = {};
            var attrsIdsMap = {};
            var titleParts = [];

            combo.forEach(function (item) {
              attrsTextMap[item.attrSlug] = item.valueText;
              attrsIdsMap[item.attrSlug] = item.valueId;
              titleParts.push(item.attrName + ': ' + item.valueText);
            });

            var idsJson = JSON.stringify({ ids: attrsIdsMap });
            var textJson = JSON.stringify({ attrs: attrsTextMap });
            // Values typed before this rebuild, keyed by the same ids JSON.
            var prev = rowValuesStore[idsJson] || {};

            var tr = document.createElement('tr');
            tr.innerHTML = ''
              + '<td>'
              + '  <div class="fw-semibold">' + titleParts.join(' | ') + '</div>'
              + '  <input type="hidden" name="variant_attrs_ids[]" value="' + idsJson.replace(/\"/g, '&quot;') + '">' 
              + '  <input type="hidden" name="variant_attrs_text[]" value="' + textJson.replace(/\"/g, '&quot;') + '">' 
              + '</td>'
              + '<td><input type="number" min="0" step="1" class="form-control rounded-0" name="variant_purchase_price[]" value="' + (prev.purchase || '') + '" placeholder="0"></td>'
              + '<td><input type="number" min="0" step="1" class="form-control rounded-0" name="variant_sale_price[]" value="' + (prev.sale || '') + '" placeholder="0"></td>'
              + '<td><input type="number" min="0" step="1" class="form-control rounded-0" name="variant_discount_price[]" value="' + (prev.discount || '') + '" placeholder="0"></td>'
              + '<td><input type="file" class="form-control rounded-0" name="variant_image[]" accept="image/*"></td>'
              + '<td><input type="number" min="0" step="1" class="form-control rounded-0" name="variant_initial_stock[]" value="' + (prev.stock || '') + '" placeholder="0"></td>';

            tbody.appendChild(tr);
          });

          tableWrap.style.display = '';
        }

        attrsSelect.addEventListener('change', function () {
          renderValueSelectors();
          buildVariants();
        });

        if (window.jQuery) {
          try {
            window.jQuery(attrsSelect).on('select2:select select2:unselect change', function () {
              renderValueSelectors();
              buildVariants();
            });
          } catch (e) {
            // ignore
          }
        }

        renderValueSelectors();
        buildVariants();
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductVariantUi);
      } else {
        initProductVariantUi();
      }
    })();
