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

        // order status change logic
        $(document).on('submit', 'form#order_status_form', function(e){
            e.preventDefault();
            var url = $(this).attr('action');
            let order_status=$(document).find('select#order_status').val();
            var order_ids = $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();
            if(order_ids.length == 0){
                if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Please Select An Order First !');
                return;
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
                       if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', 'Failed something wrong');
                   }
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
                   if(res.status == 'success' || res.status == 'completed'){
                       if(typeof showLiveToast === 'function') showLiveToast('success', 'Success', res.message || 'Orders Sent Successfully!');
                       setTimeout(function() { window.location.reload(); }, 1500);
                   } else {
                       if(typeof showLiveToast === 'function') showLiveToast('error', 'Error', res.message || 'Failed processing orders.');
                   }
               }
            });
        });
    });

  
