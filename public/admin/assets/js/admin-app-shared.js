/* Combined admin app shared behaviour.
   Moved verbatim from backEnd/layouts/partials/scripts.blade.php so the
   blade carries no inline JS. Order of the concatenated IIFEs preserves
   the old execution order:
     1 CSRF for jQuery ajax   2 Quill editors   3 Select2 init
     4 file-input repeater    5 banner media toggle  6 courier API tools
     7 toastr options + auto-submit + switch toasts   8 roles check-all
*/
// ===== [0] =====
/*
 * CSRF for every jQuery AJAX call in the admin.
 *
 * The admin used to do its state changes over GET, which needs no token - and
 * is exactly why those changes could be triggered by a link in an email. Now
 * that they are POST, every $.ajax needs the token, and hand-writing it into
 * each call site is how one gets forgotten. Setting it once here covers all of
 * them, including the ones added later.
 */
(function () {
    var el = document.querySelector('meta[name="csrf-token"]');
    if (!el || typeof jQuery === 'undefined') return;

    jQuery.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': el.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });
})();
// ===== [1] =====
  (function () {
    function ensureQuill() {
      return typeof window.Quill !== 'undefined';
    }

    function buildDefaultToolbar() {
      return [
        ['bold', 'italic'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link']
      ];
    }

    function buildFullToolbar() {
      return [
        [{ font: [] }, { size: [] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ color: [] }, { background: [] }],
        [{ header: 1 }, { header: 2 }, 'blockquote', 'code-block'],
        [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
        [{ direction: 'rtl' }, { align: [] }],
        ['link', 'image'],
        ['clean']
      ];
    }

    function resolveToolbar(editorEl) {
      var toolbarSelector = editorEl.getAttribute('data-quill-toolbar');
      if (toolbarSelector) return { toolbar: toolbarSelector };

      var toolbarPreset = (editorEl.getAttribute('data-quill-toolbar-preset') || '').toLowerCase().trim();
      if (toolbarPreset === 'full') return { toolbar: buildFullToolbar() };

      // default: mini
      return { toolbar: buildDefaultToolbar() };
    }

    /*
     * Blade escapes an attribute value once with e() and then again when the
     * attribute is echoed, so data-quill-initial-html arrives as "&lt;p&gt;..."
     * rather than "<p>...".
     * Assigning that straight to innerHTML printed the tags as text in every
     * editor (create page, campaign, product, review, footer, cookie page).
     * A textarea decodes entities without executing markup, which is exactly
     * what an attribute carrying HTML needs.
     */
    function decodeHtmlEntities(value) {
      if (typeof value !== 'string' || value.indexOf('&') === -1) return value;
      var holder = document.createElement('textarea');
      holder.innerHTML = value;
      return holder.value;
    }

    function syncToTarget(targetSelectorOrId, html) {
      if (!targetSelectorOrId) return;
      var el = null;
      try {
        el = document.querySelector(targetSelectorOrId);
      } catch (e) {
        el = document.getElementById(targetSelectorOrId);
      }
      if (!el) {
        el = document.getElementById(targetSelectorOrId);
      }
      if (!el) return;
      el.value = html;
    }

    function initOneEditor(editorEl) {
      if (!editorEl || editorEl.__quill) return;
      if (!ensureQuill()) return;

      var theme = editorEl.getAttribute('data-quill-theme') || 'snow';
      var placeholder = editorEl.getAttribute('data-quill-placeholder') || 'Write here...';
      var target = editorEl.getAttribute('data-quill-target');
      var initial = decodeHtmlEntities(editorEl.getAttribute('data-quill-initial-html'));

      var modules = resolveToolbar(editorEl);

      var quill = new window.Quill(editorEl, {
        theme: theme,
        placeholder: placeholder,
        modules: modules
      });

      editorEl.__quill = quill;

      if (typeof initial === 'string' && initial.length) {
        quill.root.innerHTML = initial;
        syncToTarget(target, quill.root.innerHTML);
      } else if (target) {
        var targetEl = document.querySelector(target);
        if (targetEl && targetEl.value) {
          quill.root.innerHTML = targetEl.value;
        }
      }

      quill.on('text-change', function () {
        syncToTarget(target, quill.root.innerHTML);
      });

      var form = editorEl.closest('form');
      if (form && !form.__quillSyncBound) {
        form.addEventListener('submit', function () {
          document.querySelectorAll('.js-quill-editor').forEach(function (ed) {
            if (ed && ed.__quill) {
              var t = ed.getAttribute('data-quill-target');
              syncToTarget(t, ed.__quill.root.innerHTML);
            }
          });
        });
        form.__quillSyncBound = true;
      }
    }

    function initAllEditors() {
      if (!ensureQuill()) return;
      document.querySelectorAll('.js-quill-editor').forEach(initOneEditor);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initAllEditors);
    } else {
      initAllEditors();
    }
  })();
// ===== [2] =====
// Select2: init any .select2 or legacy selectors
document.addEventListener('DOMContentLoaded', function () {
  try {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
    window.jQuery('.select2, .select2-multiple, [data-toggle="select2"]').each(function () {
      var $el = window.jQuery(this);
      if ($el.data('select2')) return;
      $el.select2({ width: '100%' });
    });

    window.jQuery('.select2-ajax').each(function () {
      var $el = window.jQuery(this);
      if ($el.data('select2')) return;

      var ajaxUrl = $el.data('select2-ajax-url');
      var placeholder = $el.data('placeholder') || 'Search and select...';
      var closeOnSelect = String($el.data('close-on-select')) === 'true';
      // Characters the user must type before a remote search fires; the page
      // sets this per element (data-minimum-input-length), 0 = search freely.
      var minInputLength = parseInt($el.data('minimum-input-length'), 10) || 0;

      if (!ajaxUrl) {
        $el.select2({ width: '100%', closeOnSelect: closeOnSelect });
        return;
      }

      $el.select2({
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        minimumInputLength: minInputLength,
        closeOnSelect: closeOnSelect,
        ajax: {
          url: ajaxUrl,
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return { q: params.term, page: params.page || 1 };
          },
          processResults: function (data, params) {
            params.page = params.page || 1;
            // supports both {data:[{id,text}], current_page,last_page} and direct [{id,text}]
            if (Array.isArray(data)) {
              return { results: data };
            }
            var items = (data && data.data) ? data.data : [];
            var more = (data && data.current_page && data.last_page) ? (data.current_page < data.last_page) : false;
            return { results: items, pagination: { more: more } };
          },
          cache: true
        },
        escapeMarkup: function (markup) { return markup; },
        templateResult: function (data) {
          if (data.loading) return data.text;
          return '<div>' + data.text + '</div>';
        },
        templateSelection: function (data) {
          return data.text || data.id;
        }
      });
    });
  } catch (e) {
    // ignore
  }
});
// ===== [3] =====
// Generic file input repeater (campaign/review-style): .btn-increment adds .clone html, .btn-outline-danger removes row
document.addEventListener('DOMContentLoaded', function () {
  try {
    document.addEventListener('click', function (e) {
      var incBtn = e.target && (e.target.closest ? e.target.closest('.btn-increment') : null);
      if (incBtn) {
        e.preventDefault();
        var cloneEl = document.querySelector('.clone');
        if (!cloneEl) return;
        var html = cloneEl.innerHTML;
        if (!html) return;
        var wrap = incBtn.closest('.col-sm-6') || incBtn.closest('.col-12') || incBtn.parentElement;
        if (!wrap) return;
        var temp = document.createElement('div');
        temp.innerHTML = html;
        var first = temp.firstElementChild;
        if (first) {
          wrap.appendChild(first);
        }
        return;
      }

      var delBtn = e.target && (e.target.closest ? e.target.closest('.btn-outline-danger') : null);
      if (delBtn) {
        var group = delBtn.closest('.input-group') || delBtn.closest('.control-group');
        if (group) {
          e.preventDefault();
          group.remove();
        }
      }

      var legacyRemove = e.target && (e.target.closest ? e.target.closest('.btn-danger') : null);
      if (legacyRemove) {
        var legacyGroup = legacyRemove.closest('.control-group');
        if (legacyGroup) {
          e.preventDefault();
          legacyGroup.remove();
        }
      }

      var incBtn2 = e.target && (e.target.closest ? e.target.closest('.increment_btn') : null);
      if (incBtn2) {
        e.preventDefault();
        var clonePrice = document.querySelector('.clone_price');
        var insertAfter = document.querySelector('.increment_price');
        if (!clonePrice || !insertAfter) return;
        var html2 = clonePrice.innerHTML;
        if (!html2) return;
        var temp2 = document.createElement('div');
        temp2.innerHTML = html2;
        var first2 = temp2.firstElementChild;
        if (first2) {
          insertAfter.insertAdjacentElement('afterend', first2);
        }
        return;
      }

      var removeBtn2 = e.target && (e.target.closest ? e.target.closest('.remove_btn') : null);
      if (removeBtn2) {
        var incControl = removeBtn2.closest('.increment_control');
        if (incControl) {
          e.preventDefault();
          incControl.remove();
        }
      }
    });
  } catch (e) {
    // ignore
  }
});
// ===== [4] =====
// Banner create/edit: media type toggle (image/video)
// The media_type select may carry data-has-video="true" (edit page with an
// existing video persisted) - then the video input never turns required.
document.addEventListener('DOMContentLoaded', function () {
  try {
    var mediaType = document.getElementById('media_type');
    var imageWrap = document.getElementById('imageWrap');
    var videoFileWrap = document.getElementById('videoFileWrap');
    var imageInput = document.getElementById('image');
    var videoInput = document.getElementById('video');

    if (!mediaType || (!imageWrap && !videoFileWrap)) return;

    var hasCurrentVideo = mediaType.getAttribute('data-has-video') === 'true';

    function syncMediaFields() {
      var isVideo = mediaType.value === 'video';
      if (imageWrap) imageWrap.style.display = isVideo ? 'none' : '';
      if (videoFileWrap) videoFileWrap.style.display = isVideo ? '' : 'none';

      if (imageInput) {
        if (isVideo) imageInput.removeAttribute('required');
        else imageInput.setAttribute('required', 'required');
      }

      if (videoInput) {
        if (isVideo && !hasCurrentVideo) videoInput.setAttribute('required', 'required');
        else videoInput.removeAttribute('required');
      }
    }

    mediaType.addEventListener('change', syncMediaFields);
    syncMediaFields();
  } catch (e) {
    // ignore
  }
});
// ===== [5] =====
// Courier API (Steadfast/Pathao) tools: test connections + sync orders
document.addEventListener('DOMContentLoaded', function () {
  try {
    var tokenEl = document.querySelector('meta[name="csrf-token"]');
    var csrf = tokenEl ? tokenEl.getAttribute('content') : '';

    function postJson(url, data) {
      return fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify(data || {})
      }).then(function (r) {
        return r.json();
      });
    }

    function setBtnLoading(btn, isLoading, loadingHtml, normalHtml) {
      if (!btn) return;
      if (isLoading) {
        btn.disabled = true;
        if (loadingHtml) btn.innerHTML = loadingHtml;
      } else {
        btn.disabled = false;
        if (normalHtml) btn.innerHTML = normalHtml;
      }
    }

    var steadfastBtn = document.getElementById('testSteadfastBtn');
    if (steadfastBtn) {
      steadfastBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var url = steadfastBtn.getAttribute('data-test-url');
        if (!url) return;

        var apiKey = (document.getElementById('api_key') || {}).value || '';
        var secretKey = (document.getElementById('secret_key') || {}).value || '';
        var baseUrl = (document.getElementById('url_steadfast') || {}).value || '';

        if (!apiKey || !secretKey) {
          if (window.toastr) window.toastr.error('Please enter API Key and Secret Key');
          return;
        }

        setBtnLoading(steadfastBtn, true, '<i class="ri ri-loader-4-line spin me-1"></i>Testing...', steadfastBtn.innerHTML);
        postJson(url, { api_key: apiKey, secret_key: secretKey, url: baseUrl })
          .then(function (res) {
            if (res && res.success) {
              if (window.toastr) window.toastr.success(res.message + (res.balance ? ' (Balance: ৳' + res.balance + ')' : ''));
            } else {
              if (window.toastr) window.toastr.error((res && res.message) || 'Connection test failed');
            }
          })
          .catch(function () {
            if (window.toastr) window.toastr.error('Connection test failed');
          })
          .finally(function () {
            setBtnLoading(steadfastBtn, false, null, '<i class="ri ri-test-tube-line me-1"></i>Test Connection');
          });
      });
    }

    var pathaoBtn = document.getElementById('testPathaoBtn');
    if (pathaoBtn) {
      pathaoBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var url = pathaoBtn.getAttribute('data-test-url');
        if (!url) return;

        var clientId = (document.getElementById('pathao_client_id') || {}).value || '';
        var clientSecret = (document.getElementById('pathao_client_secret') || {}).value || '';
        var username = (document.getElementById('pathao_username') || {}).value || '';
        var password = (document.getElementById('pathao_password') || {}).value || '';
        var baseUrl = (document.getElementById('url_pathao') || {}).value || '';

        if (!clientId || !clientSecret || !username || !password) {
          if (window.toastr) window.toastr.error('Please enter all Pathao credentials');
          return;
        }

        setBtnLoading(pathaoBtn, true, '<i class="ri ri-loader-4-line spin me-1"></i>Testing...', pathaoBtn.innerHTML);
        postJson(url, { client_id: clientId, client_secret: clientSecret, username: username, password: password, url: baseUrl })
          .then(function (res) {
            if (res && res.success) {
              if (window.toastr) window.toastr.success(res.message + (res.token_received ? ' (Token received)' : ''));
              if (res.token) {
                var tokenInput = document.getElementById('token');
                if (tokenInput) tokenInput.value = res.token;
              }
            } else {
              if (window.toastr) window.toastr.error((res && res.message) || 'Connection test failed');
            }
          })
          .catch(function () {
            if (window.toastr) window.toastr.error('Connection test failed');
          })
          .finally(function () {
            setBtnLoading(pathaoBtn, false, null, '<i class="ri ri-test-tube-line me-1"></i>Test Connection');
          });
      });
    }

    var syncBtn = document.getElementById('syncOrdersBtn');
    if (syncBtn) {
      syncBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var url = syncBtn.getAttribute('data-sync-url');
        if (!url) return;

        var limit = (document.getElementById('syncLimit') || {}).value || 100;
        setBtnLoading(syncBtn, true, '<i class="ri ri-loader-4-line spin me-1"></i>Syncing...', syncBtn.innerHTML);

        postJson(url, { limit: limit })
          .then(function (res) {
            if (res && res.status === 'completed') {
              if (window.toastr) window.toastr.success('Synced ' + res.processed + ' orders successfully');
              if (res.results && res.results.length > 0) {
                var html = '<div class="table-responsive mt-3"><table class="table table-sm table-striped"><thead><tr><th>Order</th><th>Phone</th><th>Risk</th><th>Score</th></tr></thead><tbody>';
                res.results.forEach(function (item) {
                  var badgeClass = item.risk_label === 'high_risk' ? 'bg-danger' : item.risk_label === 'risky' ? 'bg-warning' : 'bg-success';
                  html += '<tr><td>' + item.invoice_id + '</td><td>' + item.phone + '</td><td><span class="badge ' + badgeClass + '">' + item.risk_label + '</span></td><td>' + (item.success_rate || 'N/A') + '%</td></tr>';
                });
                html += '</tbody></table></div>';
                var resEl = document.getElementById('syncResults');
                if (resEl) resEl.innerHTML = html;
              }
            } else {
              if (window.toastr) window.toastr.error('Sync failed');
            }
          })
          .catch(function () {
            if (window.toastr) window.toastr.error('Sync failed');
          })
          .finally(function () {
            setBtnLoading(syncBtn, false, null, '<i class="ri ri-refresh-line me-1"></i>Sync Orders');
          });
      });
    }
  } catch (e) {
    // ignore
  }
});
// ===== [6] =====
// jQuery-tree-free: this file runs after the Toastr vendor bundle loads, but
// guard the option assignment regardless so a missing toastr can't kill the
// rest of the shared module.
(function () {
    if (typeof toastr !== 'undefined') {
        toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    }
})();

    // Auto-submit switch handler for Status toggles
    document.addEventListener('DOMContentLoaded', function() {
        var autoSubmitSwitches = document.querySelectorAll('.js-auto-submit');
        autoSubmitSwitches.forEach(function(switchEl) {
            switchEl.addEventListener('change', function() {
                var form = this.closest('form');
                if (form) {
                    form.submit();
                }
            });
        });

        // Toast feedback for UI switches (no data mutation here; only indicates current state)
        document.querySelectorAll('.form-check-input[type="checkbox"], .switch-input[type="checkbox"]').forEach(function (el) {
            el.addEventListener('change', function () {
                if (typeof window.toastr === 'undefined') return;
                var labelEl = null;
                var wrap = el.closest('.form-check') || el.closest('.switch') || el.parentElement;
                if (wrap) {
                    labelEl = wrap.querySelector('.form-check-label');
                }
                var name = (labelEl && labelEl.textContent ? labelEl.textContent : (el.getAttribute('name') || 'Switch')).trim();
                var state = el.checked ? 'ON' : 'OFF';
                toastr.info(name + ': ' + state);
            });
        });
    });
// ===== [8] =====
// Roles/Permissions: Check All toggle (runs only if the UI exists on the page)
document.addEventListener('DOMContentLoaded', function () {
    var checkAllWrap = document.querySelector('.checkall');
    if (!checkAllWrap) return;

    checkAllWrap.addEventListener('click', function () {
        document.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            cb.checked = !cb.checked;
        });
    });
});
