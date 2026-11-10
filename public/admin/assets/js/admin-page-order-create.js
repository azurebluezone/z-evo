/* Order cart ajax behaviour (POS / order create).
   Moved from backEnd/order/create.blade.php. All cart endpoints ride in on
   one JSON data attribute [data-cart-urls]; CSRF handled by jQuery ajaxSetup.
   The old inline validation/session toasts are served by admin-app-alerts.js
   (body data-flash-* attributes) instead of being per-page duplicates.
*/
    var cfgEl = document.querySelector('[data-cart-urls]');
    var CFG = cfgEl ? (function () {
        try { return JSON.parse(cfgEl.getAttribute('data-cart-urls')); }
        catch (e) { return {}; }
    })() : {};

 function cart_content() {
  $.ajax({
   type: "GET",
   url: "CFG.cart_content",
   dataType: "html",
   success: function (cartinfo) {
    $("#cartTable").html(cartinfo);
   },
  });
 }
 function cart_details() {
  $.ajax({
   type: "GET",
   url: "CFG.cart_details",
   dataType: "html",
   success: function (cartinfo) {
    $("#cart_details").html(cartinfo);
   },
  });
 }

 $("#cart_add").on("change", function (e) {
  var id = $(this).val();
  if (id) {
   $.ajax({
    cache: false,
    type: "GET",
    data: { id: id },
    url: "CFG.cart_add",
    dataType: "json",
    success: function (response) {
     if(response.status === 'success') {
      $("#cart_add").val(null).trigger("change");
      cart_content();
      cart_details();
      if(window.toastr) {
       window.toastr.success(response.message, 'Success!');
      }
     } else {
      if(window.toastr) {
       window.toastr.error(response.message || 'Product not found', 'Error!');
      }
     }
    },
    error: function(xhr) {
     if(window.toastr) {
      window.toastr.error('Failed to add product to cart', 'Error!');
     }
    }
   });
  }
 });
 $(document).on("click", ".cart_increment", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  var qty = $(this).val();
  if (id) {
   $.ajax({
    cache: false,
    data: { id: id, qty: qty },
    type: "GET",
    url: "CFG.cart_increment",
    dataType: "json",
    success: function (cartinfo) {
     cart_content(); cart_details();
    },
   });
  }
 });
 $(document).on("click", ".cart_decrement", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  var qty = $(this).val();
  if (id) {
   $.ajax({
    cache: false,
    type: "GET",
    data: { id: id, qty: qty },
    url: "CFG.cart_decrement",
    dataType: "json",
    success: function (cartinfo) {
     cart_content(); cart_details();
    },
   });
  }
 });
 $(document).on("click", ".cart_remove", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  if (id) {
   $.ajax({
    cache: false,
    type: "GET",
    data: { id: id },
    url: "CFG.cart_remove",
    dataType: "json",
    success: function (cartinfo) {
     cart_content(); cart_details();
    },
   });
  }
 });
 $(document).on("change", ".product_discount", function () {
  var id = $(this).data("id");
  var discount = $(this).val();
  $.ajax({
   cache: false,
   type: "GET",
   data: { id: id, discount: discount },
   url: "CFG.product_discount",
   dataType: "json",
   success: function (cartinfo) {
    cart_content(); cart_details();
   },
  });
 });

  $('#pos_manual_discount').on('change', function () {
   var discount = $(this).val();
   $.ajax({
    cache: false,
    type: 'GET',
    data: { discount: discount },
    url: "CFG.cart_extra_discount",
    dataType: 'json',
    success: function () {
     cart_content(); cart_details();
    },
   });
  });

  $('#pos_apply_coupon').on('click', function () {
   var coupon_code = $('#pos_coupon_code').val();
   $.ajax({
    cache: false,
    type: 'GET',
    data: { coupon_code: coupon_code },
    url: "CFG.cart_apply_coupon",
    dataType: 'json',
    success: function (res) {
     $('#pos_coupon_msg').text(res.message || '');
     cart_content(); cart_details();
    },
   });
  });

  $('#pos_remove_coupon').on('click', function () {
   $.ajax({
    cache: false,
    type: 'GET',
    url: "CFG.cart_remove_coupon",
    dataType: 'json',
    success: function (res) {
     $('#pos_coupon_msg').text(res.message || '');
     cart_content(); cart_details();
    },
   });
  });
 $(".cartclear").click(function (e) {
  e.preventDefault();
  $.ajax({
   cache: false,
   type: "GET",
   url: "CFG.cart_clear",
   dataType: "json",
   success: function (response) {
    if(response.status === 'success') {
     cart_content(); 
     cart_details();
     if(window.toastr) {
      window.toastr.success(response.message, 'Success!');
     }
    }
   },
   error: function(xhr) {
    if(window.toastr) {
     window.toastr.error('Failed to clear cart', 'Error!');
    }
   }
  });
 }); // pshippingfee from total
 $("#area").on("change", function () {
  var id = $(this).val();
  $.ajax({
   type: "GET",
   data: { id: id },
   url: "CFG.cart_shipping",
   dataType: "json",
   success: function (cartinfo) {
    cart_content(); cart_details();
   },
  });
 });
