@extends('backEnd.layouts.app')
@section('title', 'Visitor Analytics')

@section('content')
<!-- Visitor Statistics -->
<div class="row g-6 mb-6">
  <!-- Total Visitors -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ri ri-user-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">45,280</h4>
        </div>
        <h6 class="mb-0 fw-normal">Total Visitors</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+22%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Unique Visitors -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-success">
              <i class="icon-base ri ri-user-star-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">28,450</h4>
        </div>
        <h6 class="mb-0 fw-normal">Unique Visitors</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+18%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Page Views -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-info">
              <i class="icon-base ri ri-eye-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">128,900</h4>
        </div>
        <h6 class="mb-0 fw-normal">Page Views</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+35%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Bounce Rate -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ri ri-arrow-go-back-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">42.5%</h4>
        </div>
        <h6 class="mb-0 fw-normal">Bounce Rate</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">-5%</span>
          <small class="text-body-secondary">improved</small>
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Traffic Overview Chart -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Traffic Overview</h5>
        <div class="btn-group">
          <x-admin.button variant="outline-primary" class="active" type="button">Daily</x-admin.button>
          <x-admin.button variant="outline-primary" type="button">Weekly</x-admin.button>
          <x-admin.button variant="outline-primary" type="button">Monthly</x-admin.button>
        </div>
      </div>
      <div class="card-body">
        <div id="trafficChart" style="height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
          <div class="text-center">
            <i class="icon-base ri ri-bar-chart-box-line icon-48px mb-3"></i>
            <h4>Visitor Traffic Chart</h4>
            <p>Interactive chart showing daily visitor statistics</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Top Pages & Devices -->
<div class="row g-6 mb-6">
  <!-- Top Visited Pages -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Top Visited Pages</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Page</th>
                <th>Views</th>
                <th>Unique</th>
                <th>Avg Time</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><i class="icon-base ri ri-home-line me-2 text-primary"></i>/home</td>
                <td>45,230</td>
                <td>32,150</td>
                <td>2:45</td>
              </tr>
              <tr>
                <td><i class="icon-base ri ri-shopping-bag-line me-2 text-success"></i>/products</td>
                <td>28,450</td>
                <td>21,800</td>
                <td>3:20</td>
              </tr>
              <tr>
                <td><i class="icon-base ri ri-smartphone-line me-2 text-info"></i>/product/iphone-14</td>
                <td>18,920</td>
                <td>15,400</td>
                <td>4:15</td>
              </tr>
              <tr>
                <td><i class="icon-base ri ri-shopping-cart-line me-2 text-warning"></i>/cart</td>
                <td>12,340</td>
                <td>8,900</td>
                <td>1:50</td>
              </tr>
              <tr>
                <td><i class="icon-base ri ri-user-line me-2 text-danger"></i>/profile</td>
                <td>9,850</td>
                <td>7,200</td>
                <td>2:10</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Device Statistics -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Device Statistics</h5>
      </div>
      <div class="card-body">
        <div class="mb-4">
          <div class="d-flex justify-content-between mb-2">
            <span><i class="icon-base ri ri-smartphone-line me-2"></i>Mobile</span>
            <span class="fw-bold">65%</span>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: 65%"></div>
          </div>
        </div>
        <div class="mb-4">
          <div class="d-flex justify-content-between mb-2">
            <span><i class="icon-base ri ri-computer-line me-2"></i>Desktop</span>
            <span class="fw-bold">25%</span>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: 25%"></div>
          </div>
        </div>
        <div class="mb-4">
          <div class="d-flex justify-content-between mb-2">
            <span><i class="icon-base ri ri-tablet-line me-2"></i>Tablet</span>
            <span class="fw-bold">8%</span>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-info" role="progressbar" style="width: 8%"></div>
          </div>
        </div>
        <div class="mb-4">
          <div class="d-flex justify-content-between mb-2">
            <span><i class="icon-base ri ri-tv-line me-2"></i>Others</span>
            <span class="fw-bold">2%</span>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-secondary" role="progressbar" style="width: 2%"></div>
          </div>
        </div>
        <hr>
        <h6 class="mb-3">Browser Breakdown</h6>
        <div class="row text-center">
          <div class="col-3">
            <div class="border rounded p-2">
              <i class="icon-base ri ri-chrome-line icon-24px text-warning"></i>
              <p class="mb-0 small">Chrome</p>
              <strong>58%</strong>
            </div>
          </div>
          <div class="col-3">
            <div class="border rounded p-2">
              <i class="icon-base ri ri-firefox-line icon-24px text-danger"></i>
              <p class="mb-0 small">Firefox</p>
              <strong>12%</strong>
            </div>
          </div>
          <div class="col-3">
            <div class="border rounded p-2">
              <i class="icon-base ri ri-safari-line icon-24px text-info"></i>
              <p class="mb-0 small">Safari</p>
              <strong>18%</strong>
            </div>
          </div>
          <div class="col-3">
            <div class="border rounded p-2">
              <i class="icon-base ri ri-edge-new-line icon-24px text-primary"></i>
              <p class="mb-0 small">Edge</p>
              <strong>12%</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Geographic Distribution -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Geographic Distribution</h5>
        <x-admin.button variant="outline-primary" size="sm">Export Report</x-admin.button>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-lg-8">
            <div style="height: 300px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
              <div class="text-center">
                <i class="icon-base ri ri-map-pin-2-line icon-48px mb-3"></i>
                <h4>Visitor Location Map</h4>
                <p>Geographic distribution of visitors across Bangladesh</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <h6 class="mb-3">Top Cities</h6>
            <div class="list-group list-group-flush">
              <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="icon-base ri ri-map-pin-line me-2 text-primary"></i>Dhaka</span>
                <span class="badge bg-label-primary rounded-pill">45%</span>
              </div>
              <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="icon-base ri ri-map-pin-line me-2 text-success"></i>Chittagong</span>
                <span class="badge bg-label-success rounded-pill">18%</span>
              </div>
              <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="icon-base ri ri-map-pin-line me-2 text-info"></i>Sylhet</span>
                <span class="badge bg-label-info rounded-pill">12%</span>
              </div>
              <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="icon-base ri ri-map-pin-line me-2 text-warning"></i>Rajshahi</span>
                <span class="badge bg-label-warning rounded-pill">10%</span>
              </div>
              <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="icon-base ri ri-map-pin-line me-2 text-danger"></i>Khulna</span>
                <span class="badge bg-label-danger rounded-pill">8%</span>
              </div>
              <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="icon-base ri ri-map-pin-line me-2 text-secondary"></i>Others</span>
                <span class="badge bg-label-secondary rounded-pill">7%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Real-time Visitors -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Real-time Active Users</h5>
    <span class="badge bg-success"><i class="icon-base ri ri-radio-button-line me-1"></i>Live</span>
  </div>
  <div class="card-body">
    <div class="row text-center mb-4">
      <div class="col-md-3">
        <h2 class="text-primary mb-1">245</h2>
        <p class="text-muted mb-0">Currently Online</p>
      </div>
      <div class="col-md-3">
        <h2 class="text-success mb-1">18</h2>
        <p class="text-muted mb-0">Active Carts</p>
      </div>
      <div class="col-md-3">
        <h2 class="text-info mb-1">12</h2>
        <p class="text-muted mb-0">Checking Out</p>
      </div>
      <div class="col-md-3">
        <h2 class="text-warning mb-1">5</h2>
        <p class="text-muted mb-0">Support Chat</p>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>User</th>
            <th>Current Page</th>
            <th>Location</th>
            <th>Duration</th>
            <th>Device</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <span class="avatar avatar-sm bg-label-primary me-2">RA</span>
                <span>Guest User</span>
              </div>
            </td>
            <td>/products/iphone-14</td>
            <td>Dhaka, BD</td>
            <td>5:32</td>
            <td><i class="icon-base ri ri-smartphone-line"></i> Mobile</td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <span class="avatar avatar-sm bg-label-success me-2">KH</span>
                <span>Karim H.</span>
              </div>
            </td>
            <td>/cart</td>
            <td>Chittagong, BD</td>
            <td>2:15</td>
            <td><i class="icon-base ri ri-computer-line"></i> Desktop</td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <span class="avatar avatar-sm bg-label-info me-2">FB</span>
                <span>Fatima B.</span>
              </div>
            </td>
            <td>/checkout</td>
            <td>Sylhet, BD</td>
            <td>8:45</td>
            <td><i class="icon-base ri ri-tablet-line"></i> Tablet</td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <span class="avatar avatar-sm bg-label-warning me-2">MA</span>
                <span>Mohammad A.</span>
              </div>
            </td>
            <td>/support/chat</td>
            <td>Rajshahi, BD</td>
            <td>12:20</td>
            <td><i class="icon-base ri ri-smartphone-line"></i> Mobile</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
