@extends('backEnd.layouts.app')
@section('title', 'Edit Order')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="fw-bold mb-1">Edit Order <span class="text-primary">#ORD-001</span></h4>
    <p class="text-muted mb-0">Placed on 05 Apr 2026, 10:30 AM</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ url('/order') }}" class="btn btn-outline-secondary waves-effect">
      <i class="icon-base ri ri-arrow-left-line me-2"></i>Back to Orders
    </a>
    <x-admin.button variant="primary" ripple="true" type="button" onclick="printInvoice()">
      <i class="icon-base ri ri-printer-line me-2"></i>Print Invoice
    </x-admin.button>
  </div>
</div>

<form id="editOrderForm" onsubmit="event.preventDefault(); updateOrder();">
  <div class="row g-6">
    <!-- Order Information -->
    <div class="col-lg-8">
      <!-- Customer Information -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ri ri-user-line me-2"></i>Customer Information</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="text" class="form-control" id="customerName" value="Rahim Ahmed">
                <label for="customerName">Customer Name *</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="tel" class="form-control" id="customerPhone" value="01712345678">
                <label for="customerPhone">Phone Number *</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="email" class="form-control" id="customerEmail" value="rahim@gmail.com">
                <label for="customerEmail">Email Address</label>
              </div>
            </div>
            <div class="col-12">
              <div class="form-floating form-floating-outline">
                <textarea class="form-control" id="shippingAddress" style="height: 80px">House 25, Road 3, Mirpur, Dhaka-1216</textarea>
                <label for="shippingAddress">Shipping Address *</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="icon-base ri ri-shopping-bag-line me-2"></i>Order Items</h5>
          <x-admin.button variant="outline-primary" size="sm" ripple="true" type="button" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="icon-base ri ri-add-line me-1"></i>Add Item
          </x-admin.button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <img src="{{ asset('admin/assets/img/products/iphone.png') }}" class="rounded me-2" width="40" height="40">
                      <div>
                        <h6 class="mb-0">iPhone 14 Pro</h6>
                        <small class="text-muted">APL-IP14P-256</small>
                      </div>
                    </div>
                  </td>
                  <td>৳125,000</td>
                  <td>
                    <input type="number" class="form-control form-control-sm" value="1" style="width: 70px;">
                  </td>
                  <td>৳125,000</td>
                  <td>
                    <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" type="button">
                      <i class="icon-base ri ri-delete-bin-line"></i>
                    </x-admin.button>
                  </td>
                </tr>
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                  <td colspan="2">৳125,000</td>
                </tr>
                <tr>
                  <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                  <td colspan="2">
                    <div class="d-flex align-items-center">
                      <input type="number" class="form-control form-control-sm me-2" value="0" style="width: 100px;">
                      <span>৳</span>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td colspan="3" class="text-end"><strong>Discount:</strong></td>
                  <td colspan="2">
                    <div class="d-flex align-items-center">
                      <input type="number" class="form-control form-control-sm me-2" value="0" style="width: 100px;">
                      <span>৳</span>
                    </div>
                  </td>
                </tr>
                <tr class="fw-bold">
                  <td colspan="3" class="text-end"><strong>Total:</strong></td>
                  <td colspan="2" class="text-primary fs-5">৳125,000</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Order Timeline -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ri ri-history-line me-2"></i>Order Timeline</h5>
        </div>
        <div class="card-body">
          <ul class="timeline">
            <li class="timeline-item timeline-item-transparent">
              <span class="timeline-point timeline-point-success"></span>
              <div class="timeline-event">
                <div class="timeline-header mb-1">
                  <h6 class="mb-0">Order Placed</h6>
                  <small class="text-muted">05 Apr 2026, 10:30 AM</small>
                </div>
                <p class="mb-0">Order received and confirmed</p>
              </div>
            </li>
            <li class="timeline-item timeline-item-transparent">
              <span class="timeline-point timeline-point-primary"></span>
              <div class="timeline-event">
                <div class="timeline-header mb-1">
                  <h6 class="mb-0">Payment Confirmed</h6>
                  <small class="text-muted">05 Apr 2026, 10:35 AM</small>
                </div>
                <p class="mb-0">Payment received via bKash</p>
              </div>
            </li>
            <li class="timeline-item timeline-item-transparent">
              <span class="timeline-point timeline-point-info"></span>
              <div class="timeline-event">
                <div class="timeline-header mb-1">
                  <h6 class="mb-0">Shipped</h6>
                  <small class="text-muted">05 Apr 2026, 2:00 PM</small>
                </div>
                <p class="mb-0">Handed over to Pathao Courier</p>
              </div>
            </li>
            <li class="timeline-item timeline-item-transparent">
              <span class="timeline-point timeline-point-success"></span>
              <div class="timeline-event">
                <div class="timeline-header mb-1">
                  <h6 class="mb-0">Delivered</h6>
                  <small class="text-muted">06 Apr 2026, 11:00 AM</small>
                </div>
                <p class="mb-0">Order delivered successfully</p>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <!-- Order Status -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Order Status</h5>
        </div>
        <div class="card-body">
          <div class="form-floating form-floating-outline mb-3">
            <select class="form-select" id="orderStatus">
              <option value="pending">Pending</option>
              <option value="processing">Processing</option>
              <option value="shipped" selected>Shipped</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <label for="orderStatus">Order Status</label>
          </div>
          <div class="form-floating form-floating-outline mb-3">
            <select class="form-select" id="paymentStatus">
              <option value="unpaid">Unpaid</option>
              <option value="paid" selected>Paid</option>
              <option value="refunded">Refunded</option>
              <option value="partial">Partially Paid</option>
            </select>
            <label for="paymentStatus">Payment Status</label>
          </div>
          <div class="form-floating form-floating-outline">
            <select class="form-select" id="paymentMethod">
              <option value="cod">Cash on Delivery</option>
              <option value="bkash" selected>bKash</option>
              <option value="nagad">Nagad</option>
              <option value="card">Card Payment</option>
              <option value="bank">Bank Transfer</option>
            </select>
            <label for="paymentMethod">Payment Method</label>
          </div>
        </div>
      </div>

      <!-- Courier Information -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Courier Information</h5>
        </div>
        <div class="card-body">
          <div class="form-floating form-floating-outline mb-3">
            <select class="form-select" id="courierService">
              <option value="">Select Courier</option>
              <option value="pathao" selected>Pathao</option>
              <option value="redx">RedX</option>
              <option value="steadfast">SteadFast</option>
              <option value="paperfly">Paperfly</option>
            </select>
            <label for="courierService">Courier Service</label>
          </div>
          <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" id="trackingNumber" value="PA-123456789">
            <label for="trackingNumber">Tracking Number</label>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Order Notes</h5>
        </div>
        <div class="card-body">
          <div class="form-floating form-floating-outline">
            <textarea class="form-control" id="orderNotes" style="height: 100px">Customer requested gift wrapping. Please handle with care.</textarea>
            <label for="orderNotes">Notes</label>
          </div>
        </div>
      </div>

      <!-- Save Actions -->
      <div class="card">
        <div class="card-body">
          <x-admin.button variant="primary" ripple="true" class="w-100 waves-light mb-3" type="submit">
            <i class="icon-base ri ri-save-line me-2"></i>Update Order
          </x-admin.button>
          <x-admin.button variant="outline-danger" ripple="true" class="w-100" type="button" data-bs-toggle="modal" data-bs-target="#deleteOrderModal">
            <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete Order
          </x-admin.button>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Item to Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="addProductSelect">
            <option value="">Select Product</option>
            <option value="1">iPhone 14 Pro - ৳125,000</option>
            <option value="2">Samsung Galaxy S23 - ৳95,000</option>
            <option value="3">MacBook Air M2 - ৳110,000</option>
            <option value="4">AirPods Pro - ৳25,000</option>
          </select>
          <label for="addProductSelect">Product</label>
        </div>
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" id="addQuantity" value="1" min="1">
          <label for="addQuantity">Quantity</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" ripple="true" type="button" onclick="addItemToOrder()">
          <i class="icon-base ri ri-add-line me-2"></i>Add to Order
        </x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Order Modal -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white"><i class="icon-base ri ri-alert-line me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="icon-base ri ri-delete-bin-line icon-48px text-danger mb-3"></i>
        <p class="mb-1">Are you sure you want to delete order <strong>#ORD-001</strong>?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <a href="{{ url('/order') }}" class="btn btn-danger waves-effect waves-light">
          <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="orderToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header" id="orderToastHeader">
      <i class="icon-base ri ri-information-line me-2" id="orderToastIcon"></i>
      <strong class="me-auto" id="orderToastTitle">Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="orderToastMessage">
      Message goes here
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
function updateOrder() {
  showOrderToast('success', 'Order Updated', 'Order #ORD-001 has been updated successfully!');
}

function addItemToOrder() {
  $('#addItemModal').modal('hide');
  showOrderToast('success', 'Item Added', 'New item has been added to the order!');
}

function printInvoice() {
  showOrderToast('info', 'Printing', 'Invoice is being printed...');
  // In real implementation, this would open the print dialog
  window.print();
}

function showOrderToast(type, title, message) {
  const toast = document.getElementById('orderToast');
  const header = document.getElementById('orderToastHeader');
  const icon = document.getElementById('orderToastIcon');
  const titleEl = document.getElementById('orderToastTitle');
  const messageEl = document.getElementById('orderToastMessage');
  
  header.className = 'toast-header';
  
  switch(type) {
    case 'success':
      header.classList.add('bg-success', 'text-white');
      icon.className = 'icon-base ri ri-check-line me-2';
      break;
    case 'error':
      header.classList.add('bg-danger', 'text-white');
      icon.className = 'icon-base ri ri-close-circle-line me-2';
      break;
    case 'info':
      header.classList.add('bg-info', 'text-white');
      icon.className = 'icon-base ri ri-information-line me-2';
      break;
  }
  
  titleEl.textContent = title;
  messageEl.textContent = message;
  
  const bsToast = new bootstrap.Toast(toast);
  bsToast.show();
}
</script>
@endsection
