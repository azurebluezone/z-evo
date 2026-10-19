@extends('backEnd.layouts.app')
@section('title', 'Courier Management')

@section('content')
<!-- Courier Fleet Statistics -->
<div class="row g-6 mb-6">
  <!-- On Route -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ri ri-truck-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">42</h4>
        </div>
        <h6 class="mb-0 fw-normal">On Route</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+18%</span>
          <small class="text-body-secondary">than last week</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Delivered Today -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-success">
              <i class="icon-base ri ri-check-double-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">156</h4>
        </div>
        <h6 class="mb-0 fw-normal">Delivered Today</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+12%</span>
          <small class="text-body-secondary">than yesterday</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Pending Pickup -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ri ri-time-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">28</h4>
        </div>
        <h6 class="mb-0 fw-normal">Pending Pickup</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-warning">Urgent</span>
          <small class="text-body-secondary">needs pickup</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Delivery Issues -->
  <div class="col-sm-6 col-lg-3">
    <div class="card card-border-shadow-danger h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-danger">
              <i class="icon-base ri ri-alert-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">8</h4>
        </div>
        <h6 class="mb-0 fw-normal">Delivery Issues</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-danger">-8%</span>
          <small class="text-body-secondary">needs attention</small>
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Courier List -->
<div class="card mb-6">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Courier Partners</h5>
    <div class="d-flex gap-2">
      <x-admin.button variant="primary" data-bs-toggle="modal" data-bs-target="#addCourierModal">
        <i class="icon-base ri ri-add-line me-2"></i>Add Courier
      </x-admin.button>
      <x-admin.button variant="success" data-bs-toggle="modal" data-bs-target="#assignDeliveryModal">
        <i class="icon-base ri ri-truck-line me-2"></i>Assign Delivery
      </x-admin.button>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Courier</th>
            <th>Contact</th>
            <th>Vehicle</th>
            <th>Active Orders</th>
            <th>Completed Today</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="{{ asset('admin/assets/img/avatars/2.png') }}" class="rounded-circle me-2" width="40" height="40">
                <div>
                  <h6 class="mb-0">Kamrul Islam</h6>
                  <small class="text-muted">ID: CR-001</small>
                </div>
              </div>
            </td>
            <td>01712345678</td>
            <td><span class="badge bg-label-primary">Bike - DHK-1234</span></td>
            <td>5</td>
            <td>12</td>
            <td><span class="badge bg-label-success">Online</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#viewCourierModal">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#trackCourierModal">
                <i class="icon-base ri ri-map-pin-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="callCourier('01712345678')">
                <i class="icon-base ri ri-phone-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="{{ asset('admin/assets/img/avatars/3.png') }}" class="rounded-circle me-2" width="40" height="40">
                <div>
                  <h6 class="mb-0">Rakib Hossain</h6>
                  <small class="text-muted">ID: CR-002</small>
                </div>
              </div>
            </td>
            <td>01723456789</td>
            <td><span class="badge bg-label-info">Van - DHK-5678</span></td>
            <td>8</td>
            <td>18</td>
            <td><span class="badge bg-label-success">Online</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#viewCourierModal">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#trackCourierModal">
                <i class="icon-base ri ri-map-pin-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="callCourier('01723456789')">
                <i class="icon-base ri ri-phone-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="{{ asset('admin/assets/img/avatars/6.png') }}" class="rounded-circle me-2" width="40" height="40">
                <div>
                  <h6 class="mb-0">Sohan Ahmed</h6>
                  <small class="text-muted">ID: CR-003</small>
                </div>
              </div>
            </td>
            <td>01734567890</td>
            <td><span class="badge bg-label-primary">Bike - DHK-9012</span></td>
            <td>3</td>
            <td>8</td>
            <td><span class="badge bg-label-warning">Break</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#viewCourierModal">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#trackCourierModal">
                <i class="icon-base ri ri-map-pin-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="callCourier('01734567890')">
                <i class="icon-base ri ri-phone-line"></i>
              </x-admin.button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Active Deliveries -->
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Active Deliveries</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Address</th>
            <th>Courier</th>
            <th>Status</th>
            <th>ETA</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><a href="javascript:void(0);" class="fw-medium">#ORD-001</a></td>
            <td>Rahim Ahmed</td>
            <td>Mirpur, Dhaka</td>
            <td>Kamrul Islam</td>
            <td><span class="badge bg-label-primary">On the way</span></td>
            <td>15 min</td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#trackDeliveryModal">
                <i class="icon-base ri ri-map-pin-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showDeliveryToast('info', 'SMS Sent', 'Delivery update SMS sent to customer')">
                <i class="icon-base ri ri-message-3-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td><a href="javascript:void(0);" class="fw-medium">#ORD-015</a></td>
            <td>Karim Hassan</td>
            <td>Gulshan, Dhaka</td>
            <td>Rakib Hossain</td>
            <td><span class="badge bg-label-info">Picked up</span></td>
            <td>30 min</td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#trackDeliveryModal">
                <i class="icon-base ri ri-map-pin-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showDeliveryToast('info', 'SMS Sent', 'Delivery update SMS sent to customer')">
                <i class="icon-base ri ri-message-3-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td><a href="javascript:void(0);" class="fw-medium">#ORD-023</a></td>
            <td>Fatima Begum</td>
            <td>Uttara, Dhaka</td>
            <td>Kamrul Islam</td>
            <td><span class="badge bg-label-warning">At hub</span></td>
            <td>45 min</td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#trackDeliveryModal">
                <i class="icon-base ri ri-map-pin-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showDeliveryToast('info', 'SMS Sent', 'Delivery update SMS sent to customer')">
                <i class="icon-base ri ri-message-3-line"></i>
              </x-admin.button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Courier Modal -->
<div class="modal fade" id="addCourierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Courier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-3">
          <input type="text" class="form-control" id="courierName" placeholder="Name">
          <label for="courierName">Full Name *</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <input type="tel" class="form-control" id="courierPhone" placeholder="Phone">
          <label for="courierPhone">Phone Number *</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="courierVehicle">
            <option value="bike">Motorcycle</option>
            <option value="van">Delivery Van</option>
            <option value="truck">Truck</option>
            <option value="bicycle">Bicycle</option>
          </select>
          <label for="courierVehicle">Vehicle Type</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <input type="text" class="form-control" id="vehicleNumber" placeholder="Vehicle Number">
          <label for="vehicleNumber">Vehicle Number</label>
        </div>
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" id="courierAddress" placeholder="Address" style="height: 80px"></textarea>
          <label for="courierAddress">Address</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showCourierSuccess()">Add Courier</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Assign Delivery Modal -->
<div class="modal fade" id="assignDeliveryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign Delivery</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="selectOrder">
            <option value="">Select Order</option>
            <option value="ord001">#ORD-001 - Rahim Ahmed - Mirpur</option>
            <option value="ord002">#ORD-002 - Karim Hassan - Gulshan</option>
            <option value="ord003">#ORD-003 - Fatima Begum - Uttara</option>
          </select>
          <label for="selectOrder">Select Order *</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="selectCourier">
            <option value="">Select Courier</option>
            <option value="cr001">Kamrul Islam - Bike</option>
            <option value="cr002">Rakib Hossain - Van</option>
            <option value="cr003">Sohan Ahmed - Bike</option>
          </select>
          <label for="selectCourier">Select Courier *</label>
        </div>
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" id="deliveryNote" placeholder="Note" style="height: 80px"></textarea>
          <label for="deliveryNote">Delivery Note</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showAssignSuccess()">Assign Delivery</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Track Courier Modal -->
<div class="modal fade" id="trackCourierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Live Tracking - Kamrul Islam</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="card bg-light">
          <div class="card-body text-center py-5">
            <i class="icon-base ri ri-map-2-line icon-48px text-primary mb-3"></i>
            <h5>Live Map View</h5>
            <p class="text-muted">Courier location: Mirpur 10, Dhaka</p>
            <p class="text-muted">Last updated: 2 minutes ago</p>
            <div class="mt-3">
              <span class="badge bg-label-success me-2">Speed: 25 km/h</span>
              <span class="badge bg-label-info">Heading: North</span>
            </div>
          </div>
        </div>
        <h6 class="mt-4 mb-3">Today's Route</h6>
        <div class="list-group">
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0">#ORD-001 - Rahim Ahmed</h6>
              <small class="text-muted">Mirpur 12, Dhaka</small>
            </div>
            <span class="badge bg-label-success">Delivered</span>
          </div>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0">#ORD-015 - Karim Hassan</h6>
              <small class="text-muted">Mirpur 10, Dhaka</small>
            </div>
            <span class="badge bg-label-primary">In Progress</span>
          </div>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0">#ORD-023 - Fatima Begum</h6>
              <small class="text-muted">Mirpur 1, Dhaka</small>
            </div>
            <span class="badge bg-label-warning">Pending</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="primary" type="button"><i class="icon-base ri ri-phone-line me-2"></i>Call Courier</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="courierToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header" id="courierToastHeader">
      <i class="icon-base ri ri-information-line me-2" id="courierToastIcon"></i>
      <strong class="me-auto" id="courierToastTitle">Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="courierToastMessage">
      Message goes here
    </div>
  </div>
</div>
@endsection
