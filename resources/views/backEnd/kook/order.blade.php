@extends('backEnd.layouts.app')
@section('title', 'Order Management')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="fw-bold mb-1">Order Management</h4>
    <p class="text-muted mb-0">Manage customer orders and track deliveries</p>
  </div>
  <div class="d-flex gap-2">
    <x-admin.button variant="primary" data-bs-toggle="modal" data-bs-target="#addOrderModal">
      <i class="icon-base ri ri-add-line me-2"></i>Add Order
    </x-admin.button>
  </div>
</div>

<!-- Order Statistics -->
<div class="row g-6 mb-6">
  <!-- Pending Payment -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ri ri-calendar-2-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">56</h4>
        </div>
        <h6 class="mb-0 fw-normal">Pending Payment</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-warning">+12%</span>
          <small class="text-body-secondary">than last week</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Completed -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-success">
              <i class="icon-base ri ri-check-double-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">12,689</h4>
        </div>
        <h6 class="mb-0 fw-normal">Completed</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+8%</span>
          <small class="text-body-secondary">than last week</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Refunded -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ri ri-wallet-3-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">124</h4>
        </div>
        <h6 class="mb-0 fw-normal">Refunded</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-danger">-5%</span>
          <small class="text-body-secondary">than last week</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Failed -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-danger">
              <i class="icon-base ri ri-error-warning-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">32</h4>
        </div>
        <h6 class="mb-0 fw-normal">Failed</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-danger">+2%</span>
          <small class="text-body-secondary">than last week</small>
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Order List -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Order List</h5>
    <div class="d-flex gap-2">
      <x-admin.button variant="primary" data-bs-toggle="modal" data-bs-target="#addOrderModal">
        <i class="icon-base ri ri-add-line me-2"></i>Add Order
      </x-admin.button>
      <div class="dropdown">
        <x-admin.button variant="outline-secondary" class="dropdown-toggle" data-bs-toggle="dropdown">
          <i class="icon-base ri ri-filter-3-line me-2"></i>Filter
        </x-admin.button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="javascript:void(0);">All Orders</a></li>
          <li><a class="dropdown-item" href="javascript:void(0);">Pending</a></li>
          <li><a class="dropdown-item" href="javascript:void(0);">Completed</a></li>
          <li><a class="dropdown-item" href="javascript:void(0);">Cancelled</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Products</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><a href="javascript:void(0);" class="fw-medium">#ORD-001</a></td>
            <td>05 Apr 2026</td>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://ui-avatars.com/api/?name=Rahim+Ahmed&background=7367f0&color=fff" class="rounded-circle me-2" width="32" height="32">
                <span>Rahim Ahmed</span>
              </div>
            </td>
            <td>iPhone 14 Pro</td>
            <td>৳125,000</td>
            <td><span class="badge bg-label-success">Paid</span></td>
            <td><span class="badge bg-label-success">Completed</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewOrderModal" onclick="loadOrderView('ORD-001')">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <a href="/order/ORD-001/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                <i class="icon-base ri ri-edit-line"></i>
              </a>
              <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" onclick="setDeleteOrder('#ORD-001')">
                <i class="icon-base ri ri-delete-bin-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td><a href="javascript:void(0);" class="fw-medium">#ORD-002</a></td>
            <td>04 Apr 2026</td>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://ui-avatars.com/api/?name=Karim+Hassan&background=28c76f&color=fff" class="rounded-circle me-2" width="32" height="32">
                <span>Karim Hassan</span>
              </div>
            </td>
            <td>Samsung S23, AirPods</td>
            <td>৳120,000</td>
            <td><span class="badge bg-label-warning">COD</span></td>
            <td><span class="badge bg-label-warning">Processing</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewOrderModal" onclick="loadOrderView('ORD-002')">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <a href="/order/ORD-002/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                <i class="icon-base ri ri-edit-line"></i>
              </a>
              <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" onclick="setDeleteOrder('#ORD-002')">
                <i class="icon-base ri ri-delete-bin-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td><a href="javascript:void(0);" class="fw-medium">#ORD-003</a></td>
            <td>03 Apr 2026</td>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://ui-avatars.com/api/?name=Fatima+Begum&background=ff9f43&color=fff" class="rounded-circle me-2" width="32" height="32">
                <span>Fatima Begum</span>
              </div>
            </td>
            <td>MacBook Air</td>
            <td>৳110,000</td>
            <td><span class="badge bg-label-success">Paid</span></td>
            <td><span class="badge bg-label-info">Shipped</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewOrderModal" onclick="loadOrderView('ORD-003')">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <a href="/order/ORD-003/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                <i class="icon-base ri ri-edit-line"></i>
              </a>
              <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" onclick="setDeleteOrder('#ORD-003')">
                <i class="icon-base ri ri-delete-bin-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td><a href="javascript:void(0);" class="fw-medium">#ORD-004</a></td>
            <td>02 Apr 2026</td>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://ui-avatars.com/api/?name=Nasima+Aktar&background=ea5455&color=fff" class="rounded-circle me-2" width="32" height="32">
                <span>Nasima Aktar</span>
              </div>
            </td>
            <td>iPad Air, Pencil</td>
            <td>৳85,000</td>
            <td><span class="badge bg-label-danger">Failed</span></td>
            <td><span class="badge bg-label-danger">Cancelled</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewOrderModal" onclick="loadOrderView('ORD-004')">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <a href="/order/ORD-004/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                <i class="icon-base ri ri-edit-line"></i>
              </a>
              <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" onclick="setDeleteOrder('#ORD-004')">
                <i class="icon-base ri ri-delete-bin-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td><a href="javascript:void(0);" class="fw-medium">#ORD-005</a></td>
            <td>01 Apr 2026</td>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://ui-avatars.com/api/?name=Sohag+Hasan&background=00cfe8&color=fff" class="rounded-circle me-2" width="32" height="32">
                <span>Sohag Hasan</span>
              </div>
            </td>
            <td>AirPods Pro</td>
            <td>৳28,000</td>
            <td><span class="badge bg-label-success">Paid</span></td>
            <td><span class="badge bg-label-success">Completed</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewOrderModal" onclick="loadOrderView('ORD-005')">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <a href="/order/ORD-005/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                <i class="icon-base ri ri-edit-line"></i>
              </a>
              <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" onclick="setDeleteOrder('#ORD-005')">
                <i class="icon-base ri ri-delete-bin-line"></i>
              </x-admin.button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-end mb-0">
        <li class="page-item prev">
          <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-left-s-line"></i></a>
        </li>
        <li class="page-item active">
          <a class="page-link waves-effect waves-light" href="javascript:void(0);">1</a>
        </li>
        <li class="page-item">
          <a class="page-link waves-effect" href="javascript:void(0);">2</a>
        </li>
        <li class="page-item">
          <a class="page-link waves-effect" href="javascript:void(0);">3</a>
        </li>
        <li class="page-item next">
          <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-right-s-line"></i></a>
        </li>
      </ul>
    </nav>
  </div>
</div>

<!-- Add Order Modal -->
<div class="modal fade" id="addOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="customerName" placeholder="Customer Name">
              <label for="customerName">Customer Name</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="tel" class="form-control" id="customerPhone" placeholder="Phone">
              <label for="customerPhone">Phone Number</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="email" class="form-control" id="customerEmail" placeholder="Email">
              <label for="customerEmail">Email</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <select class="form-select" id="productSelect">
                <option value="">Select Product</option>
                <option value="iphone">iPhone 14 Pro</option>
                <option value="samsung">Samsung S23</option>
                <option value="macbook">MacBook Air</option>
              </select>
              <label for="productSelect">Product</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="number" class="form-control" id="quantity" placeholder="Quantity" value="1">
              <label for="quantity">Quantity</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <select class="form-select" id="paymentMethod">
                <option value="cod">Cash on Delivery</option>
                <option value="bkash">bKash</option>
                <option value="nagad">Nagad</option>
                <option value="card">Card</option>
              </select>
              <label for="paymentMethod">Payment Method</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-floating form-floating-outline">
              <textarea class="form-control" id="shippingAddress" placeholder="Address" style="height: 100px"></textarea>
              <label for="shippingAddress">Shipping Address</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showOrderSuccess()">Create Order</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details #ORD-001</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h6 class="mb-3">Customer Information</h6>
            <p class="mb-1"><strong>Name:</strong> Rahim Ahmed</p>
            <p class="mb-1"><strong>Phone:</strong> 01712345678</p>
            <p class="mb-1"><strong>Email:</strong> rahim@gmail.com</p>
            <p><strong>Address:</strong> House 25, Road 3, Mirpur, Dhaka</p>
          </div>
          <div class="col-md-6">
            <h6 class="mb-3">Order Information</h6>
            <p class="mb-1"><strong>Order ID:</strong> #ORD-001</p>
            <p class="mb-1"><strong>Date:</strong> 05 Apr 2026</p>
            <p class="mb-1"><strong>Status:</strong> <span class="badge bg-label-success">Completed</span></p>
            <p><strong>Payment:</strong> <span class="badge bg-success">Paid via bKash</span></p>
          </div>
        </div>
        <hr class="my-4">
        <h6 class="mb-3">Order Items</h6>
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty</th>
              <th>Unit Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>iPhone 14 Pro (256GB)</td>
              <td>1</td>
              <td>৳125,000</td>
              <td>৳125,000</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
              <td>৳125,000</td>
            </tr>
            <tr>
              <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
              <td>৳0</td>
            </tr>
            <tr>
              <td colspan="3" class="text-end"><strong>Total:</strong></td>
              <td><strong>৳125,000</strong></td>
            </tr>
          </tfoot>
        </table>
        <hr class="my-4">
        <h6 class="mb-3">Order Timeline</h6>
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
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="primary" type="button"><i class="icon-base ri ri-printer-line me-2"></i>Print Invoice</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Order Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="orderStatus">
            <option value="pending">Pending</option>
            <option value="processing" selected>Processing</option>
            <option value="shipped">Shipped</option>
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
          </select>
          <label for="paymentStatus">Payment Status</label>
        </div>
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" id="statusNote" placeholder="Note" style="height: 100px"></textarea>
          <label for="statusNote">Status Update Note</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showUpdateSuccess()">Update Status</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white"><i class="icon-base ri ri-alert-line me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="icon-base ri ri-delete-bin-line icon-48px text-danger mb-3"></i>
        <p class="mb-1">Are you sure you want to delete order <strong id="deleteOrderId">#ORD-XXX</strong>?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="danger" ripple="true" class="waves-light" type="button" onclick="confirmDelete()">
          <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete
        </x-admin.button>
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
