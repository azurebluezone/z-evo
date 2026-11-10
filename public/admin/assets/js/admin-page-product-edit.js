    // WAF Safety: Remove empty file inputs before form submit to prevent
    // LiteSpeed/ModSecurity 403 errors caused by empty filename + application/octet-stream
    (function () {
      var form = document.getElementById('productEditForm');
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

    // Handle dynamically adding/removing more gallery inputs
    document.addEventListener('click', function(e) {
      if (e.target && e.target.closest('.btn-increment')) {
        e.preventDefault();
        var cloneHtml = document.querySelector('.clone').innerHTML;
        var newEl = document.createElement('div');
        newEl.innerHTML = cloneHtml;
        var firstChild = newEl.firstElementChild;
        if(firstChild) {
          e.target.closest('.increment').insertAdjacentElement('afterend', firstChild);
        }
      } else if (e.target && e.target.closest('.btn-danger') && !e.target.closest('.remove-image')) {
        e.preventDefault();
        var group = e.target.closest('.control-group');
        if(group) {
          group.remove();
        }
      }
    });
