@extends('backEnd.layouts.app')

@section('title', 'eCommerce Dashboard')

@section('content')
<!-- Dashboard Content -->
<div class="row g-6 mb-6">
  <!-- Gamification Card -->
  <div class="col-md-6 col-xxl-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 order-2 order-md-1">
            <div class="card-body">
              <h4 class="card-title mb-4">Congratulations <span class="fw-bold">John!</span> 🎉</h4>
              <p class="mb-0">You have done 68% 😎 more sales today.</p>
              <p>Check your new badge in your profile.</p>
              <a href="javascript:;" class="btn btn-primary">View Profile</a>
            </div>
          </div>
          <div class="col-md-6 text-center text-md-end order-1 order-md-2">
            <div class="card-body pb-0 px-0 pt-2">
              <img src="{{ asset('admin/assets/img/illustrations/illustration-john-light.png') }}" height="186" class="scaleX-n1-rtl" alt="View Profile" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Gamification Card -->

  <!-- Statistics Cards -->
  <div class="col-xxl-2 col-sm-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div class="avatar">
            <div class="avatar-initial bg-label-primary rounded">
              <i class="icon-base ri ri-shopping-bag-3-line icon-24px"></i>
            </div>
          </div>
          <div class="d-flex align-items-center">
            <p class="mb-0 text-success me-1">+12%</p>
            <i class="icon-base ri ri-arrow-up-s-line text-success"></i>
          </div>
        </div>
        <div class="card-info mt-3">
          <h5 class="mb-1">155</h5>
          <p class="mb-0">Total Orders</p>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xxl-2 col-sm-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div class="avatar">
            <div class="avatar-initial bg-label-success rounded">
              <i class="icon-base ri ri-money-dollar-circle-line icon-24px"></i>
            </div>
          </div>
          <div class="d-flex align-items-center">
            <p class="mb-0 text-success me-1">+8%</p>
            <i class="icon-base ri ri-arrow-up-s-line text-success"></i>
          </div>
        </div>
        <div class="card-info mt-3">
          <h5 class="mb-1">৳45,250</h5>
          <p class="mb-0">Total Sales</p>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xxl-2 col-sm-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div class="avatar">
            <div class="avatar-initial bg-label-info rounded">
              <i class="icon-base ri ri-user-add-line icon-24px"></i>
            </div>
          </div>
          <div class="d-flex align-items-center">
            <p class="mb-0 text-success me-1">+18%</p>
            <i class="icon-base ri ri-arrow-up-s-line text-success"></i>
          </div>
        </div>
        <div class="card-info mt-3">
          <h5 class="mb-1">1,248</h5>
          <p class="mb-0">New Customers</p>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xxl-2 col-sm-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div class="avatar">
            <div class="avatar-initial bg-label-warning rounded">
              <i class="icon-base ri ri-pie-chart-2-line icon-24px"></i>
            </div>
          </div>
          <div class="d-flex align-items-center">
            <p class="mb-0 text-danger me-1">-5%</p>
            <i class="icon-base ri ri-arrow-down-s-line text-danger"></i>
          </div>
        </div>
        <div class="card-info mt-3">
          <h5 class="mb-1">৳12,800</h5>
          <p class="mb-0">Net Profit</p>
        </div>
      </div>
    </div>
  </div>
  <!--/ Statistics Cards -->
</div>

<!-- Recent Orders Table -->
<div class="row g-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Orders</h5>
        <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>#ORD-001</td>
                <td>
                  <div class="d-flex align-items-center">
                    <img src="{{ asset('admin/assets/img/avatars/1.png') }}" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                    <span>Rahim Ahmed</span>
                  </div>
                </td>
                <td>iPhone 14 Pro</td>
                <td>৳125,000</td>
                <td><span class="badge bg-label-success">Completed</span></td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon"><i class="icon-base ri ri-eye-line"></i></x-admin.button>
                </td>
              </tr>
              <tr>
                <td>#ORD-002</td>
                <td>
                  <div class="d-flex align-items-center">
                    <img src="{{ asset('admin/assets/img/avatars/5.png') }}" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                    <span>Karim Hassan</span>
                  </div>
                </td>
                <td>Samsung S23</td>
                <td>৳95,000</td>
                <td><span class="badge bg-label-warning">Processing</span></td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon"><i class="icon-base ri ri-eye-line"></i></x-admin.button>
                </td>
              </tr>
              <tr>
                <td>#ORD-003</td>
                <td>
                  <div class="d-flex align-items-center">
                    <img src="{{ asset('admin/assets/img/avatars/8.png') }}" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                    <span>Fatima Begum</span>
                  </div>
                </td>
                <td>MacBook Air</td>
                <td>৳110,000</td>
                <td><span class="badge bg-label-info">Shipped</span></td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon"><i class="icon-base ri ri-eye-line"></i></x-admin.button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============================================ -->
<!-- UI COMPONENT LIBRARY - COMPLETE REFERENCE -->
<!-- ============================================ -->

<!-- ROW 1: BUTTONS & BADGES -->
<div class="row g-6 mb-6">
  <!-- Button Variations -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Button Components</h5>
      </div>
      <div class="card-body">
        <p class="mb-3"><strong>Primary Actions:</strong></p>
        <x-admin.button variant="primary" class="me-2" type="button">Save</x-admin.button>
        <x-admin.button variant="secondary" class="me-2" type="button">Cancel</x-admin.button>
        <x-admin.button variant="success" class="me-2" type="button">Approve</x-admin.button>
        <x-admin.button variant="danger" class="me-2" type="button">Delete</x-admin.button>
        <x-admin.button variant="warning" class="me-2" type="button">Warning</x-admin.button>
        <x-admin.button variant="info" type="button">Info</x-admin.button>
        
        <hr class="my-4">
        <p class="mb-3"><strong>Outline Buttons:</strong></p>
        <x-admin.button variant="outline-primary" class="me-2" type="button">Primary</x-admin.button>
        <x-admin.button variant="outline-secondary" class="me-2" type="button">Secondary</x-admin.button>
        <x-admin.button variant="outline-success" class="me-2" type="button">Success</x-admin.button>
        <x-admin.button variant="outline-danger" type="button">Danger</x-admin.button>
        
        <hr class="my-4">
        <p class="mb-3"><strong>Button Sizes:</strong></p>
        <x-admin.button variant="primary" size="lg" class="me-2" type="button">Large</x-admin.button>
        <x-admin.button variant="primary" class="me-2" type="button">Normal</x-admin.button>
        <x-admin.button variant="primary" size="sm" type="button">Small</x-admin.button>
        
        <hr class="my-4">
        <p class="mb-3"><strong>Icon Buttons:</strong></p>
        <x-admin.button variant="primary" shape="icon" class="me-2" type="button"><i class="icon-base ri ri-add-line"></i></x-admin.button>
        <x-admin.button variant="success" shape="icon" class="me-2" type="button"><i class="icon-base ri ri-check-line"></i></x-admin.button>
        <x-admin.button variant="danger" shape="icon" class="me-2" type="button"><i class="icon-base ri ri-delete-bin-line"></i></x-admin.button>
        <x-admin.button variant="info" shape="icon" type="button"><i class="icon-base ri ri-edit-line"></i></x-admin.button>
      </div>
    </div>
  </div>

  <!-- Badge Variations -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Badge Components</h5>
      </div>
      <div class="card-body">
        <p class="mb-3"><strong>Status Badges:</strong></p>
        <span class="badge bg-label-primary me-2">Primary</span>
        <span class="badge bg-label-success me-2">Success</span>
        <span class="badge bg-label-danger me-2">Danger</span>
        <span class="badge bg-label-warning me-2">Warning</span>
        <span class="badge bg-label-info me-2">Info</span>
        <span class="badge bg-label-secondary">Secondary</span>
        
        <hr class="my-4">
        <p class="mb-3"><strong>Solid Badges:</strong></p>
        <span class="badge bg-primary me-2">Primary</span>
        <span class="badge bg-success me-2">Success</span>
        <span class="badge bg-danger me-2">Danger</span>
        <span class="badge bg-warning text-dark me-2">Warning</span>
        <span class="badge bg-info text-dark">Info</span>
        
        <hr class="my-4">
        <p class="mb-3"><strong>Order Status (Bangladesh Context):</strong></p>
        <span class="badge bg-label-success me-2">Completed</span>
        <span class="badge bg-label-warning me-2">Processing</span>
        <span class="badge bg-label-info me-2">Shipped</span>
        <span class="badge bg-label-danger me-2">Cancelled</span>
        <span class="badge bg-label-primary">Pending</span>
        
        <hr class="my-4">
        <p class="mb-3"><strong>Payment Status:</strong></p>
        <span class="badge bg-success me-2">Paid</span>
        <span class="badge bg-warning text-dark me-2">COD</span>
        <span class="badge bg-danger">Unpaid</span>
      </div>
    </div>
  </div>
</div>
<!-- -------------------sahon--------------------- -->

<!-- ROW 2: MODALS -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Modal Components (Click to Open)</h5>
      </div>
      <div class="card-body">
        <!-- Modal Trigger Buttons -->
        <x-admin.button variant="primary" class="me-2" type="button" data-bs-toggle="modal" data-bs-target="#basicModal">Basic Modal</x-admin.button>
        <x-admin.button variant="success" class="me-2" type="button" data-bs-toggle="modal" data-bs-target="#successModal">Success Modal</x-admin.button>
        <x-admin.button variant="danger" class="me-2" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete Confirm</x-admin.button>
        <x-admin.button variant="info" class="me-2" type="button" data-bs-toggle="modal" data-bs-target="#largeModal">Large Modal</x-admin.button>
        <x-admin.button variant="secondary" type="button" data-bs-toggle="modal" data-bs-target="#scrollModal">Scrollable Modal</x-admin.button>
      </div>
    </div>
  </div>
</div>
<!-- sahon -->
 <div class="col-lg-4 col-md-6">
              <small class="fw-medium">Toggle Between Modals</small>
              <div class="mt-4">
                <x-admin.button variant="primary" ripple="true" class="waves-light" type="button" data-bs-toggle="modal" data-bs-target="#modalToggle">Launch modal</x-admin.button>

                <!-- Modal 1-->
                <div class="modal fade" id="modalToggle" aria-labelledby="modalToggleLabel" tabindex="-1" style="display: none;" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalToggleLabel">Modal 1</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">Show a second modal and hide this one with the button below.</div>
                      <div class="modal-footer">
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Modal 2-->
                <div class="modal fade" id="modalToggle2" aria-labelledby="modalToggleLabel2" tabindex="-1" style="display: none;" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalToggleLabel2">Modal 2</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
<!-- MODAL DEFINITIONS -->
<!-- Basic Modal -->
<div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-4">
          <input type="text" class="form-control" id="categoryName" placeholder="Category Name">
          <label for="categoryName">Category Name</label>
        </div>
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" id="categoryDesc" placeholder="Description" style="height: 100px"></textarea>
          <label for="categoryDesc">Description</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button">Save Category</x-admin.button>
      </div>
    </div>
  </div>
</div>
<!-- -------------------sahon--------------------- -->

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-body text-center pt-5">
        <div class="mb-4">
          <span class="avatar avatar-lg bg-label-success rounded-circle">
            <i class="icon-base ri ri-check-line icon-32px"></i>
          </span>
        </div>
        <h5>Success!</h5>
        <p class="mb-4">Your order has been saved successfully.</p>
        <x-admin.button variant="success" type="button" data-bs-dismiss="modal">OK</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-body text-center pt-5">
        <div class="mb-4">
          <span class="avatar avatar-lg bg-label-danger rounded-circle">
            <i class="icon-base ri ri-delete-bin-line icon-32px"></i>
          </span>
        </div>
        <h5>Are you sure?</h5>
        <p class="mb-4">You won't be able to revert this!</p>
        <div class="d-flex justify-content-center gap-2">
          <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
          <x-admin.button variant="danger" type="button">Yes, Delete</x-admin.button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Large Modal -->
<div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details #ORD-001</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h6>Customer Info</h6>
            <p class="mb-1"><strong>Name:</strong> Rahim Ahmed</p>
            <p class="mb-1"><strong>Phone:</strong> 01712345678</p>
            <p class="mb-1"><strong>Email:</strong> rahim@gmail.com</p>
            <p><strong>Address:</strong> Mirpur, Dhaka</p>
          </div>
          <div class="col-md-6">
            <h6>Order Info</h6>
            <p class="mb-1"><strong>Order ID:</strong> #ORD-001</p>
            <p class="mb-1"><strong>Date:</strong> 05 Apr 2026</p>
            <p class="mb-1"><strong>Status:</strong> <span class="badge bg-label-success">Completed</span></p>
            <p><strong>Payment:</strong> <span class="badge bg-success">Paid</span></p>
          </div>
        </div>
        <hr>
        <h6>Order Items</h6>
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty</th>
              <th>Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>iPhone 14 Pro</td>
              <td>1</td>
              <td>৳125,000</td>
              <td>৳125,000</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="primary" type="button"><i class="icon-base ri ri-printer-line me-2"></i>Print Invoice</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Scrollable Modal -->
<div class="modal fade" id="scrollModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Terms & Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit...</p>
        <p>More content here to demonstrate scrolling...</p>
        <p>Even more content...</p>
        <p>Keep scrolling...</p>
        <p>Almost there...</p>
        <p>You've reached the end!</p>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="primary" type="button">I Agree</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- ROW 3: TOAST NOTIFICATIONS -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Toast Notifications (Click to Show)</h5>
      </div>
      <div class="card-body">
        <x-admin.button variant="success" class="me-2" type="button" onclick="showToast('success')">Success Toast</x-admin.button>
        <x-admin.button variant="danger" class="me-2" type="button" onclick="showToast('error')">Error Toast</x-admin.button>
        <x-admin.button variant="warning" class="me-2" type="button" onclick="showToast('warning')">Warning Toast</x-admin.button>
        <x-admin.button variant="info" type="button" onclick="showToast('info')">Info Toast</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <i class="icon-base ri ri-information-line me-2" id="toastIcon"></i>
      <strong class="me-auto" id="toastTitle">Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toastMessage">
      Message goes here
    </div>
  </div>
</div>

<!-- ROW 4: FORMS -->
<div class="row g-6 mb-6">
  <!-- Basic Form Inputs -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Form Inputs</h5>
      </div>
      <div class="card-body">
        <!-- Text Input -->
        <div class="form-floating form-floating-outline mb-3">
          <input type="text" class="form-control" id="productName" placeholder="Product Name">
          <label for="productName">Product Name</label>
        </div>
        
        <!-- Number Input -->
        <div class="form-floating form-floating-outline mb-3">
          <input type="number" class="form-control" id="price" placeholder="Price">
          <label for="price">Price (৳)</label>
        </div>
        
        <!-- Email Input -->
        <div class="form-floating form-floating-outline mb-3">
          <input type="email" class="form-control" id="email" placeholder="Email">
          <label for="email">Customer Email</label>
        </div>
        
        <!-- Password Input -->
        <div class="form-floating form-floating-outline mb-3">
          <input type="password" class="form-control" id="password" placeholder="Password">
          <label for="password">Password</label>
        </div>
        
        <!-- Textarea -->
        <div class="form-floating form-floating-outline mb-3">
          <textarea class="form-control" id="description" placeholder="Description" style="height: 100px"></textarea>
          <label for="description">Product Description</label>
        </div>
        
        <!-- Select Dropdown -->
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="category">
            <option value="">Select Category</option>
            <option value="electronics">Electronics</option>
            <option value="clothing">Clothing</option>
            <option value="food">Food</option>
          </select>
          <label for="category">Category</label>
        </div>
        
        <!-- File Upload -->
        <div class="mb-3">
          <label for="productImage" class="form-label">Product Image</label>
          <input class="form-control" type="file" id="productImage">
        </div>
        
        <!-- Checkbox -->
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="featured">
          <label class="form-check-label" for="featured">Featured Product</label>
        </div>
        
        <!-- Radio Buttons -->
        <div class="mb-3">
          <label class="d-block mb-2">Status</label>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status" id="active" value="active" checked>
            <label class="form-check-label" for="active">Active</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status" id="inactive" value="inactive">
            <label class="form-check-label" for="inactive">Inactive</label>
          </div>
        </div>
        
        <!-- Switch/Toggle -->
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="inStock" checked>
          <label class="form-check-label" for="inStock">In Stock</label>
        </div>
        
        <!-- Date Picker -->
        <div class="form-floating form-floating-outline mb-3">
          <input type="date" class="form-control" id="orderDate">
          <label for="orderDate">Order Date</label>
        </div>
        
        <!-- Range Slider -->
        <div class="mb-3">
          <label for="quantity" class="form-label">Quantity (1-100)</label>
          <input type="range" class="form-range" id="quantity" min="1" max="100" value="50">
        </div>
        
        <x-admin.button variant="primary" type="submit">Submit Form</x-admin.button>
      </div>
    </div>
  </div>


<!-- ROW 5: ALERTS -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Alert Components</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-primary" role="alert">
          <i class="icon-base ri ri-information-line me-2"></i>
          <strong>Info:</strong> This is a primary alert—check it out!
        </div>
        <div class="alert alert-success" role="alert">
          <i class="icon-base ri ri-check-line me-2"></i>
          <strong>Success:</strong> Order has been placed successfully!
        </div>
        <div class="alert alert-danger" role="alert">
          <i class="icon-base ri ri-error-warning-line me-2"></i>
          <strong>Error:</strong> Something went wrong. Please try again.
        </div>
        <div class="alert alert-warning" role="alert">
          <i class="icon-base ri ri-alert-line me-2"></i>
          <strong>Warning:</strong> Stock is running low. Please restock soon.
        </div>
        <div class="alert alert-info" role="alert">
          <i class="icon-base ri ri-lightbulb-line me-2"></i>
          <strong>Note:</strong> New features are now available.
        </div>
        
        <!-- Dismissible Alert -->
        <div class="alert alert-success alert-dismissible" role="alert">
          <i class="icon-base ri ri-check-line me-2"></i>
          Product saved successfully!
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ROW 6: DROPDOWNS & TABS -->
<div class="row g-6 mb-6">
  <!-- Dropdowns -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Dropdown Components</h5>
      </div>
      <div class="card-body">
        <p class="mb-3"><strong>Basic Dropdown:</strong></p>
        <div class="dropdown mb-4">
          <x-admin.button variant="primary" class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
            Select Action
          </x-admin.button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="javascript:void(0);"><i class="icon-base ri ri-eye-line me-2"></i>View</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);"><i class="icon-base ri ri-edit-line me-2"></i>Edit</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);"><i class="icon-base ri ri-file-copy-line me-2"></i>Duplicate</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="javascript:void(0);"><i class="icon-base ri ri-delete-bin-line me-2"></i>Delete</a></li>
          </ul>
        </div>
        
        <p class="mb-3"><strong>Split Button Dropdown:</strong></p>
        <div class="btn-group mb-4">
          <x-admin.button variant="danger" type="button">Delete</x-admin.button>
          <x-admin.button variant="danger" class="dropdown-toggle dropdown-toggle-split" type="button" data-bs-toggle="dropdown">
            <span class="visually-hidden">Toggle Dropdown</span>
          </x-admin.button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="javascript:void(0);">Archive</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);">Move to Trash</a></li>
          </ul>
        </div>
        
        <p class="mb-3"><strong>Dropdown with Icons (Order Actions):</strong></p>
        <div class="dropdown">
          <x-admin.button variant="outline-secondary" class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
            Order Status
          </x-admin.button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="javascript:void(0);"><span class="badge bg-label-warning me-2">Processing</span> Mark Processing</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);"><span class="badge bg-label-info me-2">Shipped</span> Mark Shipped</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);"><span class="badge bg-label-success me-2">Delivered</span> Mark Delivered</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);"><span class="badge bg-label-danger me-2">Cancelled</span> Cancel Order</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Tab Components</h5>
      </div>
      <div class="card-body">
        <ul class="nav nav-tabs mb-3" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1" type="button">Order Info</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2" type="button">Customer</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3" type="button">Shipping</button>
          </li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="tab1">
            <p><strong>Order #ORD-001</strong></p>
            <p>Date: 05 Apr 2026</p>
            <p>Status: <span class="badge bg-label-success">Completed</span></p>
            <p>Total: ৳125,000</p>
          </div>
          <div class="tab-pane fade" id="tab2">
            <p><strong>Rahim Ahmed</strong></p>
            <p>Email: rahim@gmail.com</p>
            <p>Phone: 01712345678</p>
            <p>Address: Mirpur, Dhaka</p>
          </div>
          <div class="tab-pane fade" id="tab3">
            <p><strong>Pathao Courier</strong></p>
            <p>Tracking: #PTH123456</p>
            <p>Estimated: 07 Apr 2026</p>
            <p>Status: In Transit</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ROW 7: PROGRESS & SPINNERS -->
<div class="row g-6 mb-6">
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Progress Bars</h5>
      </div>
      <div class="card-body">
        <p class="mb-2">Order Progress</p>
        <div class="progress mb-3">
          <div class="progress-bar" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
        </div>
        
        <p class="mb-2">Sales Target (৳45k / ৳60k)</p>
        <div class="progress mb-3">
          <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
        </div>
        
        <p class="mb-2">Stock Level (15 / 100)</p>
        <div class="progress mb-3">
          <div class="progress-bar bg-warning" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">15%</div>
        </div>
        
        <p class="mb-2">Multiple Bars</p>
        <div class="progress mb-3">
          <div class="progress-bar" role="progressbar" style="width: 40%" aria-valuenow="40">Electronics</div>
          <div class="progress-bar bg-success" role="progressbar" style="width: 35%" aria-valuenow="35">Clothing</div>
          <div class="progress-bar bg-info" role="progressbar" style="width: 25%" aria-valuenow="25">Others</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Loading Spinners</h5>
      </div>
      <div class="card-body">
        <p class="mb-3"><strong>Border Spinners:</strong></p>
        <div class="spinner-border text-primary me-2" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <div class="spinner-border text-success me-2" role="status"></div>
        <div class="spinner-border text-danger me-2" role="status"></div>
        <div class="spinner-border text-warning me-2" role="status"></div>
        <div class="spinner-border text-info" role="status"></div>
        
        <hr class="my-4">
        <p class="mb-3"><strong>Growing Spinners:</strong></p>
        <div class="spinner-grow text-primary me-2" role="status"></div>
        <div class="spinner-grow text-success me-2" role="status"></div>
        <div class="spinner-grow text-danger" role="status"></div>
        
        <hr class="my-4">
        <p class="mb-3"><strong>Button with Spinner:</strong></p>
        <x-admin.button variant="primary" type="button">
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          <span class="ms-2">Loading...</span>
        </x-admin.button>
      </div>
    </div>
  </div>
</div>
<!-- -------------------sahon--------------------- -->
 <nav aria-label="Page navigation">
                  <ul class="pagination pagination-rounded pagination-outline-primary">
                    <li class="page-item first">
                      <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-skip-back-mini-line icon-22px"></i></a>
                    </li>
                    <li class="page-item prev">
                      <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-left-s-line icon-22px"></i></a>
                    </li>
                    <li class="page-item">
                      <a class="page-link waves-effect" href="javascript:void(0);">1</a>
                    </li>
                    <li class="page-item">
                      <a class="page-link waves-effect" href="javascript:void(0);">2</a>
                    </li>
                    <li class="page-item active">
                      <a class="page-link waves-effect" href="javascript:void(0);">3</a>
                    </li>
                    <li class="page-item">
                      <a class="page-link waves-effect" href="javascript:void(0);">4</a>
                    </li>
                    <li class="page-item">
                      <a class="page-link waves-effect" href="javascript:void(0);">5</a>
                    </li>
                    <li class="page-item next">
                      <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-right-s-line icon-22px"></i></a>
                    </li>
                    <li class="page-item last">
                      <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-skip-forward-mini-line icon-22px"></i></a>
                    </li>
                  </ul>
                </nav>
                <!-- -------------------sahon--------------------- -->

<!-- ------sahon---------------- -->

<div class="card-body">
          <small class="fw-medium">Default</small>
          <div class="demo-inline-spacing">
            <x-admin.button variant="outline-primary" ripple="true" type="button">Primary</x-admin.button>
            <x-admin.button variant="outline-secondary" ripple="true" type="button">Secondary</x-admin.button>
            <x-admin.button variant="outline-success" ripple="true" type="button">Success</x-admin.button>
            <x-admin.button variant="outline-danger" ripple="true" type="button">Danger</x-admin.button>
            <x-admin.button variant="outline-warning" ripple="true" type="button">Warning</x-admin.button>
            <x-admin.button variant="outline-info" ripple="true" type="button">Info</x-admin.button>
            <x-admin.button variant="outline-dark" ripple="true" type="button">Dark</x-admin.button>
          </div>
        </div>
<!-- ------sahon---------------- -->

<div class="card-body">
          <div class="row">
            <div class="col-xl-6 mb-xl-0 mb-4">
              <small class="fw-medium">Button Toolbar</small>
              <div class="btn-toolbar demo-inline-spacing gap-2" role="toolbar" aria-label="Toolbar with button groups">
                <div class="btn-group" role="group" aria-label="First group">
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-notification-4-line"></i></x-admin.button>
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-calendar-line"></i></x-admin.button>
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-shield-check-line"></i></x-admin.button>
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-message-2-line"></i></x-admin.button>
                </div>
                <div class="btn-group" role="group" aria-label="Second group">
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-bold"></i></x-admin.button>
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-italic"></i></x-admin.button>
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-underline"></i></x-admin.button>
                </div>
                <div class="btn-group" role="group" aria-label="Third group">
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-volume-up-line"></i></x-admin.button>
                </div>
              </div>
            </div>
            <div class="col-xl-6">
              <small class="fw-medium">Button Nesting</small>
              <div class="mt-4">
                <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-car-line"></i></x-admin.button>
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-rocket-2-line"></i></x-admin.button>
                  <x-admin.button variant="outline-secondary" ripple="true" type="button"><i class="icon-base ri ri-lightbulb-line"></i></x-admin.button>
                  <div class="btn-group" role="group">
                    <x-admin.button variant="outline-secondary" ripple="true" class="dropdown-toggle" id="btnGroupDrop1" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-base ri ri-more-2-line icon-20px"></i><span class="d-none d-sm-block">Dropdown</span></x-admin.button>
                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                      <a class="dropdown-item waves-effect" href="javascript:void(0);">Dropdown link</a>
                      <a class="dropdown-item waves-effect" href="javascript:void(0);">Dropdown link</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- ------sahon---------------- -->

<!-- DataTable Component -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">DataTable Component</h5>
        <div class="d-flex gap-2">
          <x-admin.button variant="primary" ripple="true">
            <i class="icon-base ri ri-add-line me-2"></i>Add New
          </x-admin.button>
          <x-admin.button variant="outline-secondary" ripple="true">
            <i class="icon-base ri ri-download-line me-2"></i>Export
          </x-admin.button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover table-striped">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>1</td>
                <td>John Doe</td>
                <td>john@example.com</td>
                <td><span class="badge bg-label-success">Active</span></td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-eye-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-edit-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-delete-bin-line"></i>
                  </x-admin.button>
                </td>
              </tr>
              <tr>
                <td>2</td>
                <td>Jane Smith</td>
                <td>jane@example.com</td>
                <td><span class="badge bg-label-warning">Pending</span></td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-eye-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-edit-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-delete-bin-line"></i>
                  </x-admin.button>
                </td>
              </tr>
              <tr>
                <td>3</td>
                <td>Bob Johnson</td>
                <td>bob@example.com</td>
                <td><span class="badge bg-label-danger">Inactive</span></td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-eye-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-edit-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true">
                    <i class="icon-base ri ri-delete-bin-line"></i>
                  </x-admin.button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Component -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Modal Components</h5>
      </div>
      <div class="card-body">
        <div class="demo-inline-spacing">
          <x-admin.button variant="primary" ripple="true" type="button" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="icon-base ri ri-add-line me-2"></i>Add User
          </x-admin.button>
          <x-admin.button variant="info" ripple="true" type="button" data-bs-toggle="modal" data-bs-target="#viewModal">
            <i class="icon-base ri ri-eye-line me-2"></i>View Details
          </x-admin.button>
          <x-admin.button variant="warning" ripple="true" type="button" data-bs-toggle="modal" data-bs-target="#confirmModal">
            <i class="icon-base ri ri-alert-line me-2"></i>Confirm Action
          </x-admin.button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Add User -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-3">
          <input type="text" class="form-control" id="userName" placeholder="Name">
          <label for="userName">Full Name</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <input type="email" class="form-control" id="userEmail" placeholder="Email">
          <label for="userEmail">Email Address</label>
        </div>
        <div class="form-floating form-floating-outline">
          <select class="form-select" id="userRole">
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="user">User</option>
            <option value="manager">Manager</option>
          </select>
          <label for="userRole">Role</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" ripple="true" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" ripple="true" type="button">Save User</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: View Details -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h5 class="modal-title text-white">User Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3">
            <span class="avatar-initial bg-label-primary rounded-circle">
              <i class="icon-base ri ri-user-line"></i>
            </span>
          </div>
          <div>
            <h6 class="mb-0">John Doe</h6>
            <small class="text-muted">john@example.com</small>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-6 mb-2">
            <small class="text-muted">Role</small>
            <p class="mb-0">Administrator</p>
          </div>
          <div class="col-6 mb-2">
            <small class="text-muted">Status</small>
            <p class="mb-0"><span class="badge bg-label-success">Active</span></p>
          </div>
          <div class="col-6">
            <small class="text-muted">Joined</small>
            <p class="mb-0">Jan 15, 2024</p>
          </div>
          <div class="col-6">
            <small class="text-muted">Last Login</small>
            <p class="mb-0">2 hours ago</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" ripple="true" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="info" ripple="true" type="button">Edit User</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Confirm Action -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-dark">⚠️ Confirm Action</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="icon-base ri ri-alert-line icon-48px text-warning mb-3"></i>
        <p>Are you sure you want to delete this user?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <x-admin.button variant="outline-secondary" ripple="true" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="danger" ripple="true" type="button">Delete</x-admin.button>
      </div>
    </div>
  </div>
</div>

@endsection
