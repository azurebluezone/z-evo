    $(document).ready(function(){
        // Delegated, because the rows above are now replaced by every draw.
        $(document).on('change', '.checkall', function () {
            $('.checkbox').prop('checked', $(this).is(':checked'));
        });

        // order assign logic
        $(document).on('submit', 'form#order_assign', function(e){
            e.preventDefault();
            var url = $(this).attr('action');
            let user_id=$(document).find('select#user_id').val();
            var order_ids = $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();
            if(order_ids.length == 0){
                if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Please Select An Order First !');
                return;
            }
            $.ajax({
               // The route is a POST: assigning a user changes a row, and as a
               // GET it was reachable by a bare link with no CSRF token. Sending
               // GET here would answer 405.
               type:'POST',
               url:url,
               data:{user_id,order_ids},
               success:function(res){
                   if(res.status=='success'){
                       if(typeof showLiveToast === 'function') showLiveToast('success', 'Success', res.message);
                       setTimeout(() => window.location.reload(), 1500);
                   } else {
                       if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Failed something wrong');
                   }
               }
            });
        });

        // order status change logic (bulk modal)
        $(document).on('submit', 'form#order_status_form', function(e){
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var submitBtn = form.find('button[type="submit"]');
            var originalBtn = submitBtn.length ? submitBtn.html() : null;
            let order_status=$(document).find('select#order_status').val();
            var order_ids = $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();

            if(order_ids.length == 0){
                if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Please Select An Order First !');
                return;
            }

            // A bulk status change can book couriers server-side, so the click
            // must show that work is happening.
            if (submitBtn.length) {
                submitBtn.prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving…');
            }

            function restoreBulk() {
                if (submitBtn.length) {
                    submitBtn.prop('disabled', false).html(originalBtn);
                }
            }

            $.ajax({
               type:'POST',
               url:url,
               data:{order_status,order_ids},
               success:function(res){
                   if(res.status=='success'){
                       if(typeof showLiveToast === 'function') showLiveToast('success', 'Success', res.message);
                       setTimeout(() => window.location.reload(), 1500);
                   } else {
                       restoreBulk();
                       if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', (res && res.message) || 'Failed something wrong');
                   }
               },
               error:function(xhr){
                   restoreBulk();
                   var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed something wrong';
                   if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', message);
               }
            });
        });

        /*
         * Per-row status dropdown.
         *
         * A <select> fires `change`, not `submit`. The inline
         * onchange="this.form.submit()" was removed when the AJAX handler was
         * added, but nothing replaced the trigger, so picking a status did
         * nothing at all. This delegated listener closes that gap; it is
         * delegated because the DataTable replaces the rows on every draw.
         */
        $(document).on('change', 'select.row-status-select', function(){
            $(this).closest('form.row-status-form').trigger('submit');
        });

        /*
         * The in-place POST with a visible saving state.
         */
        $(document).on('submit', 'form.row-status-form', function(e){
            e.preventDefault();

            var form = $(this);
            var select = form.find('select.row-status-select');
            var spinner = form.find('.row-status-spinner');

            if (form.data('saving')) return;

            if (!select.val()) {
                if (typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Select a status first.');
                return;
            }

            // Serialised before the select is disabled: a disabled field is
            // omitted from serialize(), which would drop order_status.
            var payload = form.serialize();

            form.data('saving', true);
            select.prop('disabled', true);
            spinner.removeClass('d-none');

            function restore() {
                form.data('saving', false);
                select.prop('disabled', false);
                spinner.addClass('d-none');
            }

            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: payload,
                headers: { 'Accept': 'application/json' },
                success: function(res){
                    if (res && res.status === 'success') {
                        if (typeof showLiveToast === 'function') showLiveToast('success', 'Success', res.message || 'Order status change successfully');
                        setTimeout(function(){ window.location.reload(); }, 1200);
                    } else {
                        restore();
                        if (typeof showLiveToast === 'function') showLiveToast('error', 'Error', (res && res.message) || 'Failed to change the status.');
                    }
                },
                error: function(xhr){
                    restore();
                    var message = 'Failed to change the status.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var first = Object.values(xhr.responseJSON.errors)[0];
                        if (first && first.length) message = first[0];
                    }
                    if (typeof showLiveToast === 'function') showLiveToast('error', 'Error', message);
                }
            });
        });

        // bulk delete logic
        $(document).on('click', '.order_delete', function(e){
            e.preventDefault();
            var url = $(this).data('url');
            var order_ids = $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();
            if(order_ids.length ==0){
                if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Please Select An Order First !');
                return;
            }
            // Trigger browser native confirm for safe execution since global modal is bound per block normally
            if(!confirm("Are you sure you want to delete the selected orders?")) return;
            $.ajax({
               type:'POST',
               url:url,
               data:{order_ids},
               success:function(res){
                   if(res.status=='success'){
                       if(typeof showLiveToast === 'function') showLiveToast('success', 'Success', res.message);
                       setTimeout(() => window.location.reload(), 1500);
                   } else {
                       if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Failed something wrong');
                   }
               }
            });
        });

        // bulk print logic
        $(document).on('click', '.multi_order_print', function(e){
            e.preventDefault();
            var url = $(this).attr('href');
            var order_ids = $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();
            if(order_ids.length == 0){
                if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Please Select Atleast One Order!');
                return;
            }
            $.ajax({
               type:'GET',
               url:url,
               data:{order_ids},
               success:function(res){
                   if(res.status=='success'){
                       var myWindow = window.open("", "_blank");                   
                       myWindow.document.write(res.view);
                   } else {
                       if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Failed something wrong');
                   }
               }
            });
        });

        // Courier handling logic stripped down and cleaned
        $(document).on('click', '.individual-courier-btn, .multi_order_courier', function(e){
            e.preventDefault();
            var url = $(this).data('url') || $(this).attr('href');
            var order = $('input.checkbox:checked').map(function(){ return $(this).val(); });
            var order_ids = order.get();
            
            if(order_ids.length == 0){
                if(typeof showLiveToast === 'function') showLiveToast('warning', 'Warning', 'Please select at least one order.');
                return;
            }
            
            $.ajax({
               type:'POST',
               url:url,
               data:{order_ids: order_ids},
               success:function(res){
                   /*
                    * The bulk endpoint answers {"status":"completed","results":[...]},
                    * where every result carries its own status and message. It
                    * used to be read as "status == completed means done", so a
                    * courier that refused EVERY order (401, inactive account)
                    * still showed a green "Orders Sent Successfully!" - the
                    * admin was told the parcel was booked when nothing had
                    * been created.
                    *
                    * So the per-order results decide the toast.
                    */
                   var results = (res && res.results) ? res.results : null;

                   if (Array.isArray(results) && results.length) {
                       var failed = results.filter(function (row) { return row.status !== 'success'; });
                       var sent = results.length - failed.length;

                       if (failed.length === 0) {
                           if(typeof showLiveToast === 'function') showLiveToast('success', 'Success', res.message || 'Orders Sent Successfully!');
                           setTimeout(function() { window.location.reload(); }, 1500);
                       } else {
                           var first = failed[0].message || 'The courier refused the order.';
                           if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', sent + ' of ' + results.length + ' sent. ' + first);
                       }
                   } else if(res.status == 'success' || res.status == 'completed'){
                       if(typeof showLiveToast === 'function') showLiveToast('success', 'Success', res.message || 'Orders Sent Successfully!');
                       setTimeout(function() { window.location.reload(); }, 1500);
                   } else {
                       if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', res.message || 'Failed processing orders.');
                   }
               }
            });
        });
    });

  
