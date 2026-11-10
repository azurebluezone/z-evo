  document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery) return;
    var $ = window.jQuery;

    function toast(icon, title) {
      if (!window.Swal || !window.Swal.fire) return;
      try {
        window.Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          icon: icon,
          title: title
        });
      } catch (e) {
        // ignore
      }
    }

    $(".checkall").on('change', function () {
      $(".checkbox").prop('checked', $(this).is(":checked"));
    });

    $(document).on('click', '.hotdeal_update', function (e) {
      e.preventDefault();
      var url = $(this).attr('href');
      var product = $('input.checkbox:checked').map(function () {
        return $(this).val();
      });
      var product_ids = product.get();
      if (product_ids.length == 0) {
        toast('error', 'Please Select A Product First !');
        return;
      }
      $.ajax({
        type: 'GET',
        url: url,
        data: { product_ids },
        success: function (res) {
          if (res.status == 'success') {
            toast('success', res.message);
            window.location.reload();
          } else {
            toast('error', 'Failed something wrong');
          }
        }
      });
    });

    $(document).on('click', '.update_status', function (e) {
      e.preventDefault();
      var url = $(this).attr('href');
      var product = $('input.checkbox:checked').map(function () {
        return $(this).val();
      });
      var product_ids = product.get();
      if (product_ids.length == 0) {
        toast('error', 'Please Select A Product First !');
        return;
      }
      $.ajax({
        type: 'GET',
        url: url,
        data: { product_ids },
        success: function (res) {
          if (res.status == 'success') {
            toast('success', res.message);
            window.location.reload();
          } else {
            toast('error', 'Failed something wrong');
          }
        }
      });
    });
  });
