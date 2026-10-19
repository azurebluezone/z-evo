@extends('backEnd.layouts.app')
@section('title', $order_status->name . ' Order Management')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">{{$order_status->name}} Order ({{isset($order_status->orders_count) ? $order_status->orders_count : 0}})</h4>
  </div>
  <div class="d-flex gap-2">
    <a href="{{route('admin.order.create')}}" class="btn btn-primary btn-square waves-effect">
      <i class="icon-base ri ri-add-line me-2"></i>Add Order
    </a>
  </div>
</div>

<!-- Order Statistics -->
<div class="row g-3 mb-4">
  <!-- Pending Payment / Orders -->
  <div class="col-sm-6 col-lg-3">
    <div class="card rounded-0 h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ri ri-time-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">{{ isset($order_kpis) ? $order_kpis->pending : 0 }}</h4>
        </div>
        <h6 class="mb-0 fw-normal">Pending</h6>
      </div>
    </div>
  </div>

  <!-- Completed / Delivered -->
  <div class="col-sm-6 col-lg-3">
    <div class="card rounded-0 h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-success">
              <i class="icon-base ri ri-check-double-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">{{ isset($order_kpis) ? $order_kpis->delivered : 0 }}</h4>
        </div>
        <h6 class="mb-0 fw-normal">Delivered</h6>
      </div>
    </div>
  </div>

  <!-- Total Orders -->
  <div class="col-sm-6 col-lg-3">
    <div class="card rounded-0 h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-info">
              <i class="icon-base ri ri-file-list-3-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">{{ isset($order_kpis) ? $order_kpis->total : 0 }}</h4>
        </div>
        <h6 class="mb-0 fw-normal">Total Orders</h6>
      </div>
    </div>
  </div>

  <!-- Today Orders -->
  <div class="col-sm-6 col-lg-3">
    <div class="card rounded-0 h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ri ri-calendar-todo-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">{{ isset($order_kpis) ? $order_kpis->today : 0 }}</h4>
        </div>
        <h6 class="mb-0 fw-normal">Today</h6>
      </div>
    </div>
  </div>
</div>

<!-- Order List -->
<div class="card rounded-0">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
    <h5 class="mb-0 text-uppercase fw-bold">Manage Orders</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap"> 
      
      <!-- Modern Bulk Actions Restoration -->
      <div class="dropdown">
        <x-admin.button variant="outline-secondary" shape="square" class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          Actions
        </x-admin.button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#asignUser" href="javascript:void(0)"><i class="ri ri-user-add-line me-2"></i>Assign User</a></li>
          <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeStatus" href="javascript:void(0)"><i class="ri ri-settings-3-line me-2"></i>Change Status</a></li>
          <li><hr class="dropdown-divider"></li>
          {{-- Bulk delete is a POST: the handler reads data-url and posts the checked
               ids with the CSRF token the layout's ajaxSetup attaches. --}}
          <li><button type="button" class="dropdown-item order_delete text-danger" data-url="{{route('admin.order.bulk_destroy')}}"><i class="ri ri-delete-bin-line me-2"></i>Delete Selected</button></li>
          <li><a class="dropdown-item multi_order_print text-primary" href="{{route('admin.order.order_print')}}"><i class="ri ri-printer-line me-2"></i>Print Selected</a></li>
        </ul>
      </div>

      <x-admin.button variant="outline-secondary" shape="square" type="button" data-bs-toggle="offcanvas" data-bs-target="#orderFilters" aria-controls="orderFilters">
        <i class="icon-base ri ri-filter-3-line me-1"></i>Filters
      </x-admin.button>

      <!-- Courier Actions Re-integration in Modern UI -->
      @if(isset($steadfast) && $steadfast)
      <x-admin.button variant="outline-primary" shape="square" ripple="true" class="multi_order_courier" type="button" href="{{route('admin.bulk_courier', 'steadfast')}}" data-bs-toggle="tooltip" title="Send to SteadFast">
        <i class="ri ri-truck-line me-1"></i> SteadFast
      </x-admin.button>
      @endif

      @if(isset($pathaostore) && !empty($pathaostore))
      <x-admin.button variant="outline-success" shape="square" ripple="true" class="individual-courier-btn" type="button" data-courier="pathao-auto" data-url="{{ route('admin.order.pathao.auto') }}" title="Auto-send to Pathao">
        <i class="ri ri-road-map-line me-1"></i> Pathao
      </x-admin.button>
      @endif

      <form method="get" action="{{ route('admin.orders', ['slug' => request()->route('slug') ?? 'all']) }}" class="d-flex align-items-center gap-2 m-0">
        <input type="text" name="keyword" class="form-control rounded-0" placeholder="Search invoice/phone" value="{{ request('keyword') }}" style="min-width: 220px;">
        <x-admin.button variant="info" shape="square" type="submit">Search</x-admin.button>
      </form>
    </div>
  </div>
  
  <div class="card-body p-0">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="orderFilters" aria-labelledby="orderFiltersLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="orderFiltersLabel" class="offcanvas-title">Advanced Filters</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <form method="get" action="{{ route('admin.orders', ['slug' => request()->route('slug') ?? 'all']) }}">
          <div class="form-floating form-floating-outline mb-4">
            <select class="form-select" name="status" id="filterStatus">
              <option value="">All Statuses</option>
              @if(isset($orderstatus))
                @foreach($orderstatus as $st)
                  <option value="{{ $st->id }}" @if((string)request('status') === (string)$st->id) selected @endif>{{ $st->name }}</option>
                @endforeach
              @endif
            </select>
            <label for="filterStatus">Order Status</label>
          </div>

          <div class="mb-3">
            <input type="hidden" name="keyword" value="{{ request('keyword') }}">
          </div>

          <div class="d-flex gap-2">
            <x-admin.button variant="primary" class="w-100" type="submit">Apply Filters</x-admin.button>
            <x-admin.button variant="outline-secondary" class="w-100" type="button" data-bs-dismiss="offcanvas">Close</x-admin.button>
          </div>
        </form>
      </div>
    </div>
    {{--
        The grid itself is the shared partial: columns and endpoint only. Every
        other list screen will use the same one, so a fix to the pager or the
        search box lands everywhere at once.
    --}}
    @include('backEnd.partials.datatable', [
        'id'      => 'orderTable',
        'url'     => route('admin.orders.datatable', ['slug' => request()->route('slug') ?? 'all']),
        'title'   => 'Orders',
        'empty'   => 'No orders yet.',
            'selectable' => true,
        'params'  => ['status' => request('status'), 'keyword' => request('keyword')],
        'order'   => [[2, 'desc']],
        'columns' => [
            ['label' => '', 'orderable' => false, 'searchable' => false, 'class' => 'text-center'],
            ['label' => 'Order ID'],
            ['label' => 'Date'],
            ['label' => 'Customer'],
            ['label' => 'Contact'],
            ['label' => 'Total'],
            ['label' => 'Status'],
            ['label' => 'Actions', 'orderable' => false],
        ],
    ])
  </div>
</div>

<!-- Restored Real-World Modals for Bulk Actions -->
<!-- Assign User Modal -->
<div class="modal fade" id="asignUser" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{route('admin.order.assign')}}" method="POST" id="order_assign">
        @csrf
      <div class="modal-body">
        <div class="form-floating form-floating-outline">
            <select name="user_id" id="user_id" class="form-select">
                <option value="">Select User..</option>
                @if(isset($users))
                  @foreach($users as $key=>$value)
                  <option value="{{$value->id}}">{{$value->name}}</option>
                  @endforeach
                @endif
            </select>
            <label for="user_id">Assign To</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="primary" type="submit">Submit</x-admin.button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Change Status Modal -->
<div class="modal fade" id="changeStatus" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Order Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{route('admin.order.status')}}" method="POST" id="order_status_form">
        @csrf
      <div class="modal-body">
        <div class="form-floating form-floating-outline">
            <select name="order_status" id="order_status" class="form-select">
                <option value="">Select Status..</option>
                @if(isset($orderstatus))
                  @foreach($orderstatus as $key=>$value)
                  <option value="{{$value->id}}">{{$value->name}}</option>
                  @endforeach
                @endif
            </select>
            <label for="order_status">Order Status</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="primary" type="submit">Submit</x-admin.button>
      </div>
      </form>
    </div>
  </div>
</div>

@push('script')
<script>
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

  </script>
  @endpush
  @endsection
