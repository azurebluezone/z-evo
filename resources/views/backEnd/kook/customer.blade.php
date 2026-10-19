@extends('backEnd.layouts.app')
@section('title', 'Customer Management')

@section('content')
<!-- Customer Statistics -->
<div class="row g-6 mb-6">
  <!-- Total Customers -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ri ri-user-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">8,450</h4>
        </div>
        <h6 class="mb-0 fw-normal">Total Customers</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+12%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Active Customers -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-success">
              <i class="icon-base ri ri-user-follow-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">6,820</h4>
        </div>
        <h6 class="mb-0 fw-normal">Active Customers</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+8%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- New Customers -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-info">
              <i class="icon-base ri ri-user-add-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">1,248</h4>
        </div>
        <h6 class="mb-0 fw-normal">New This Month</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+18%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- VIP Customers -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ri ri-vip-crown-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">382</h4>
        </div>
        <h6 class="mb-0 fw-normal">VIP Customers</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+5%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Customer List -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Customer List</h5>
    <div class="d-flex gap-2 flex-wrap">
      <x-admin.button variant="primary" ripple="true" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
        <i class="icon-base ri ri-user-add-line me-2"></i>Add Customer
      </x-admin.button>
      <x-admin.button variant="success" ripple="true" onclick="exportCustomers()">
        <i class="icon-base ri ri-file-excel-line me-2"></i>Export
      </x-admin.button>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Customer ID</th>
            <th>Phone</th>
            <th>Location</th>
            <th>Orders</th>
            <th>Total Spent</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="{{ asset('admin/assets/img/avatars/1.png') }}" class="rounded-circle me-2" width="40" height="40">
                <div>
                  <h6 class="mb-0">Rahim Ahmed</h6>
                  <small class="text-muted">rahim@gmail.com</small>
                </div>
              </div>
            </td>
            <td>#CUST-001</td>
            <td>01712345678</td>
            <td>Mirpur, Dhaka</td>
            <td>24</td>
            <td>৳125,000</td>
            <td><span class="badge bg-label-success">Active</span></td>
            <td>
              <div class="d-flex gap-1">
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewCustomerModal">
                  <i class="icon-base ri ri-eye-line"></i>
                </x-admin.button>
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                  <i class="icon-base ri ri-edit-line"></i>
                </x-admin.button>
                <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal" onclick="setDeleteCustomer('Rahim Ahmed')">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="{{ asset('admin/assets/img/avatars/5.png') }}" class="rounded-circle me-2" width="40" height="40">
                <div>
                  <h6 class="mb-0">Karim Hassan</h6>
                  <small class="text-muted">karim@gmail.com</small>
                </div>
              </div>
            </td>
            <td>#CUST-002</td>
            <td>01723456789</td>
            <td>Gulshan, Dhaka</td>
            <td>18</td>
            <td>৳98,500</td>
            <td><span class="badge bg-label-success">Active</span></td>
            <td>
              <div class="d-flex gap-1">
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewCustomerModal">
                  <i class="icon-base ri ri-eye-line"></i>
                </x-admin.button>
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                  <i class="icon-base ri ri-edit-line"></i>
                </x-admin.button>
                <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal" onclick="setDeleteCustomer('Karim Hassan')">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="{{ asset('admin/assets/img/avatars/8.png') }}" class="rounded-circle me-2" width="40" height="40">
                <div>
                  <h6 class="mb-0">Fatima Begum</h6>
                  <small class="text-muted">fatima@gmail.com</small>
                </div>
              </div>
            </td>
            <td>#CUST-003</td>
            <td>01734567890</td>
            <td>Uttara, Dhaka</td>
            <td>32</td>
            <td>৳156,000</td>
            <td><span class="badge bg-label-warning">VIP</span></td>
            <td>
              <div class="d-flex gap-1">
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewCustomerModal">
                  <i class="icon-base ri ri-eye-line"></i>
                </x-admin.button>
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                  <i class="icon-base ri ri-edit-line"></i>
                </x-admin.button>
                <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal" onclick="setDeleteCustomer('Fatima Begum')">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
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

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="newCustomerName" placeholder="Name">
              <label for="newCustomerName">Full Name *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="email" class="form-control" id="newCustomerEmail" placeholder="Email">
              <label for="newCustomerEmail">Email Address *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="tel" class="form-control" id="newCustomerPhone" placeholder="Phone">
              <label for="newCustomerPhone">Phone Number *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <select class="form-select" id="newCustomerStatus">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="vip">VIP</option>
              </select>
              <label for="newCustomerStatus">Customer Type</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-floating form-floating-outline">
              <textarea class="form-control" id="newCustomerAddress" placeholder="Address" style="height: 80px"></textarea>
              <label for="newCustomerAddress">Full Address</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="newCustomerCity" placeholder="City">
              <label for="newCustomerCity">City</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="newCustomerPostCode" placeholder="Post Code">
              <label for="newCustomerPostCode">Post Code</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showCustomerSuccess()">Add Customer</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- View Customer Modal -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Customer Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-4 text-center mb-4">
            <img src="{{ asset('admin/assets/img/avatars/1.png') }}" class="rounded-circle mb-3" width="120" height="120">
            <h5 class="mb-1">Rahim Ahmed</h5>
            <span class="badge bg-label-success">Active Customer</span>
            <p class="text-muted mt-2">Member since Jan 2024</p>
          </div>
          <div class="col-md-8">
            <h6 class="mb-3">Contact Information</h6>
            <p class="mb-1"><strong>Email:</strong> rahim@gmail.com</p>
            <p class="mb-1"><strong>Phone:</strong> 01712345678</p>
            <p class="mb-3"><strong>Address:</strong> House 25, Road 3, Mirpur, Dhaka-1216</p>
            
            <h6 class="mb-3 mt-4">Order Statistics</h6>
            <div class="row">
              <div class="col-4">
                <div class="border rounded p-3 text-center">
                  <h4 class="mb-0 text-primary">24</h4>
                  <small class="text-muted">Total Orders</small>
                </div>
              </div>
              <div class="col-4">
                <div class="border rounded p-3 text-center">
                  <h4 class="mb-0 text-success">৳125K</h4>
                  <small class="text-muted">Total Spent</small>
                </div>
              </div>
              <div class="col-4">
                <div class="border rounded p-3 text-center">
                  <h4 class="mb-0 text-info">4.8</h4>
                  <small class="text-muted">Avg Rating</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <hr class="my-4">
        <h6 class="mb-3">Recent Orders</h6>
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Date</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>#ORD-001</td>
              <td>05 Apr 2026</td>
              <td>৳125,000</td>
              <td><span class="badge bg-label-success">Completed</span></td>
            </tr>
            <tr>
              <td>#ORD-015</td>
              <td>28 Mar 2026</td>
              <td>৳45,000</td>
              <td><span class="badge bg-label-success">Completed</span></td>
            </tr>
            <tr>
              <td>#ORD-023</td>
              <td>15 Mar 2026</td>
              <td>৳78,000</td>
              <td><span class="badge bg-label-success">Completed</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="primary" type="button"><i class="icon-base ri ri-mail-line me-2"></i>Send Email</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-3">
          <input type="text" class="form-control" id="editCustomerName" value="Rahim Ahmed">
          <label for="editCustomerName">Full Name</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <input type="email" class="form-control" id="editCustomerEmail" value="rahim@gmail.com">
          <label for="editCustomerEmail">Email</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <input type="tel" class="form-control" id="editCustomerPhone" value="01712345678">
          <label for="editCustomerPhone">Phone</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="editCustomerStatus">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
            <option value="vip">VIP</option>
          </select>
          <label for="editCustomerStatus">Status</label>
        </div>
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" id="editCustomerAddress" style="height: 100px">House 25, Road 3, Mirpur, Dhaka</textarea>
          <label for="editCustomerAddress">Address</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showUpdateSuccess()">Save Changes</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Customer Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white"><i class="icon-base ri ri-alert-line me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="icon-base ri ri-delete-bin-line icon-48px text-danger mb-3"></i>
        <p class="mb-1">Are you sure you want to delete customer <strong id="deleteCustomerName">Customer Name</strong>?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="danger" ripple="true" class="waves-light" type="button" onclick="confirmDeleteCustomer()">
          <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete
        </x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="customerToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header" id="customerToastHeader">
      <i class="icon-base ri ri-information-line me-2" id="customerToastIcon"></i>
      <strong class="me-auto" id="customerToastTitle">Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="customerToastMessage">
      Message goes here
    </div>
  </div>
</div>

@endsection
