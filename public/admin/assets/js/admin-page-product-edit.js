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
    //
    // Every new row gets its own key so new_image[<key>] and new_title[<key>]
    // stay paired even when an earlier row's file input is empty and the
    // WAF-safety hook removes its name. The first visible row is key 0.
    var nextGalleryKey = 1;
    function applyGalleryKey(scope, key) {
      var file = scope.querySelector('input[type="file"]');
      var title = scope.querySelector('input[type="text"]');
      if (file) {
        file.setAttribute('name', 'new_image[' + key + ']');
      }
      if (title) {
        title.setAttribute('name', 'new_title[' + key + ']');
      }
    }

    document.addEventListener('click', function(e) {
      if (e.target && e.target.closest('.btn-increment')) {
        e.preventDefault();
        var cloneHtml = document.querySelector('.clone').innerHTML;
        var newEl = document.createElement('div');
        newEl.innerHTML = cloneHtml;
        var firstChild = newEl.firstElementChild;
        if(firstChild) {
          applyGalleryKey(firstChild, nextGalleryKey++);
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
