@extends('backEnd.layouts.app')
@section('title', 'Event Management')

@section('content')
<!-- Event Statistics -->
<div class="row g-6 mb-6">
  <!-- Upcoming Events -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ri ri-calendar-event-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">12</h4>
        </div>
        <h6 class="mb-0 fw-normal">Upcoming Events</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+3</span>
          <small class="text-body-secondary">this month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Active Registrations -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-success">
              <i class="icon-base ri ri-user-add-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">1,245</h4>
        </div>
        <h6 class="mb-0 fw-normal">Active Registrations</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+18%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Completed Events -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-info">
              <i class="icon-base ri ri-check-double-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">48</h4>
        </div>
        <h6 class="mb-0 fw-normal">Completed Events</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+12</span>
          <small class="text-body-secondary">this year</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Total Attendees -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ri ri-team-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">8,920</h4>
        </div>
        <h6 class="mb-0 fw-normal">Total Attendees</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+25%</span>
          <small class="text-body-secondary">all time</small>
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Upcoming Events List -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Upcoming Events</h5>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <div class="list-group-item p-3">
            <div class="d-flex">
              <div class="flex-shrink-0 me-3">
                <div class="bg-primary text-white text-center rounded p-2" style="min-width: 50px;">
                  <div class="fw-bold">15</div>
                  <small>APR</small>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-1">Product Launch 2026</h6>
                <p class="mb-1 text-muted small"><i class="icon-base ri ri-time-line me-1"></i>10:00 AM - 2:00 PM</p>
                <p class="mb-0 text-muted small"><i class="icon-base ri ri-map-pin-line me-1"></i>Dhaka Convention Center</p>
                <div class="mt-2">
                  <span class="badge bg-label-primary">450 Registered</span>
                </div>
              </div>
            </div>
          </div>
          <div class="list-group-item p-3">
            <div class="d-flex">
              <div class="flex-shrink-0 me-3">
                <div class="bg-success text-white text-center rounded p-2" style="min-width: 50px;">
                  <div class="fw-bold">22</div>
                  <small>APR</small>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-1">Customer Appreciation Day</h6>
                <p class="mb-1 text-muted small"><i class="icon-base ri ri-time-line me-1"></i>2:00 PM - 6:00 PM</p>
                <p class="mb-0 text-muted small"><i class="icon-base ri ri-map-pin-line me-1"></i>Online (Zoom)</p>
                <div class="mt-2">
                  <span class="badge bg-label-success">1,200 Registered</span>
                </div>
              </div>
            </div>
          </div>
          <div class="list-group-item p-3">
            <div class="d-flex">
              <div class="flex-shrink-0 me-3">
                <div class="bg-info text-white text-center rounded p-2" style="min-width: 50px;">
                  <div class="fw-bold">28</div>
                  <small>APR</small>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-1">Tech Workshop</h6>
                <p class="mb-1 text-muted small"><i class="icon-base ri ri-time-line me-1"></i>9:00 AM - 5:00 PM</p>
                <p class="mb-0 text-muted small"><i class="icon-base ri ri-map-pin-line me-1"></i>Training Center, Dhaka</p>
                <div class="mt-2">
                  <span class="badge bg-label-info">85 Registered</span>
                </div>
              </div>
            </div>
          </div>
          <div class="list-group-item p-3">
            <div class="d-flex">
              <div class="flex-shrink-0 me-3">
                <div class="bg-warning text-white text-center rounded p-2" style="min-width: 50px;">
                  <div class="fw-bold">05</div>
                  <small>MAY</small>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-1">Annual Sale Event</h6>
                <p class="mb-1 text-muted small"><i class="icon-base ri ri-time-line me-1"></i>All Day</p>
                <p class="mb-0 text-muted small"><i class="icon-base ri ri-map-pin-line me-1"></i>All Branches</p>
                <div class="mt-2">
                  <span class="badge bg-label-warning">2,500+ Expected</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Event List -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">All Events</h5>
    <div class="d-flex gap-2 flex-wrap">
      <x-admin.button variant="outline-primary" ripple="true">All</x-admin.button>
      <x-admin.button variant="outline-secondary" ripple="true">Upcoming</x-admin.button>
      <x-admin.button variant="outline-secondary" ripple="true">Past</x-admin.button>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Event</th>
            <th>Date & Time</th>
            <th>Location</th>
            <th>Registered</th>
            <th>Attended</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="bg-primary rounded p-2 me-3 text-white text-center" style="min-width: 50px;">
                  <div class="fw-bold">15</div>
                  <small>APR</small>
                </div>
                <div>
                  <h6 class="mb-0">Product Launch 2026</h6>
                  <small class="text-muted">Corporate Event</small>
                </div>
              </div>
            </td>
            <td>Apr 15, 2026<br><small class="text-muted">10:00 AM - 2:00 PM</small></td>
            <td>Dhaka Convention Center</td>
            <td>450</td>
            <td>-</td>
            <td><span class="badge bg-label-primary">Upcoming</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#viewEventModal">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#editEventModal">
                <i class="icon-base ri ri-edit-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showEventToast('success', 'Registration Link', 'Event link copied to clipboard')">
                <i class="icon-base ri ri-share-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="bg-success rounded p-2 me-3 text-white text-center" style="min-width: 50px;">
                  <div class="fw-bold">22</div>
                  <small>APR</small>
                </div>
                <div>
                  <h6 class="mb-0">Customer Appreciation Day</h6>
                  <small class="text-muted">Virtual Event</small>
                </div>
              </div>
            </td>
            <td>Apr 22, 2026<br><small class="text-muted">2:00 PM - 6:00 PM</small></td>
            <td>Online (Zoom)</td>
            <td>1,200</td>
            <td>-</td>
            <td><span class="badge bg-label-primary">Upcoming</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#viewEventModal">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#editEventModal">
                <i class="icon-base ri ri-edit-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showEventToast('success', 'Registration Link', 'Event link copied to clipboard')">
                <i class="icon-base ri ri-share-line"></i>
              </x-admin.button>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="bg-secondary rounded p-2 me-3 text-white text-center" style="min-width: 50px;">
                  <div class="fw-bold">10</div>
                  <small>MAR</small>
                </div>
                <div>
                  <h6 class="mb-0">Spring Festival Sale</h6>
                  <small class="text-muted">Promotional Event</small>
                </div>
              </div>
            </td>
            <td>Mar 10, 2026<br><small class="text-muted">All Day</small></td>
            <td>All Branches</td>
            <td>3,500</td>
            <td>2,850</td>
            <td><span class="badge bg-label-success">Completed</span></td>
            <td>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#viewEventModal">
                <i class="icon-base ri ri-eye-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" data-bs-toggle="modal" data-bs-target="#eventReportModal">
                <i class="icon-base ri ri-bar-chart-line"></i>
              </x-admin.button>
              <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showEventToast('info', 'Report', 'Event report downloaded')">
                <i class="icon-base ri ri-download-line"></i>
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
        <li class="page-item next">
          <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-right-s-line"></i></a>
        </li>
      </ul>
    </nav>
  </div>
</div>

<!-- Event Calendar (Moved to Bottom) -->
<div class="row g-6">
  <!-- Event Calendar -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Event Calendar</h5>
        <x-admin.button variant="primary" ripple="true" data-bs-toggle="modal" data-bs-target="#addEventModal">
          <i class="icon-base ri ri-add-line me-2"></i>Add Event
        </x-admin.button>
      </div>
      <div class="card-body">
        <div style="height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
          <div class="text-center">
            <i class="icon-base ri ri-calendar-todo-line icon-48px mb-3"></i>
            <h4>Interactive Event Calendar</h4>
            <p>Full calendar view with all scheduled events</p>
            <div class="mt-3">
              <span class="badge bg-label-light text-dark me-2">Today: 3 Events</span>
              <span class="badge bg-label-warning">This Week: 8 Events</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create New Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="eventName" placeholder="Event Name">
              <label for="eventName">Event Name *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <select class="form-select" id="eventType">
                <option value="">Select Type</option>
                <option value="corporate">Corporate</option>
                <option value="virtual">Virtual</option>
                <option value="promotional">Promotional</option>
                <option value="training">Training</option>
              </select>
              <label for="eventType">Event Type *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="date" class="form-control" id="eventDate">
              <label for="eventDate">Event Date *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="eventTime" placeholder="Time">
              <label for="eventTime">Time (e.g., 10:00 AM - 2:00 PM)</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="eventLocation" placeholder="Location">
              <label for="eventLocation">Location / Venue *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <input type="number" class="form-control" id="eventCapacity" placeholder="Capacity">
              <label for="eventCapacity">Maximum Capacity</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-floating form-floating-outline">
              <textarea class="form-control" id="eventDescription" placeholder="Description" style="height: 100px"></textarea>
              <label for="eventDescription">Event Description</label>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Event Banner Image</label>
            <input class="form-control" type="file" id="eventBanner">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showEventSuccess()">Create Event</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- View Event Modal -->
<div class="modal fade" id="viewEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Product Launch 2026</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
              <div class="text-center">
                <i class="icon-base ri ri-calendar-event-line icon-32px mb-2"></i>
                <h5>Event Banner</h5>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <h6 class="mb-3">Event Details</h6>
            <p class="mb-2"><i class="icon-base ri ri-calendar-line me-2 text-primary"></i><strong>Date:</strong> April 15, 2026</p>
            <p class="mb-2"><i class="icon-base ri ri-time-line me-2 text-primary"></i><strong>Time:</strong> 10:00 AM - 2:00 PM</p>
            <p class="mb-2"><i class="icon-base ri ri-map-pin-line me-2 text-primary"></i><strong>Location:</strong> Dhaka Convention Center</p>
            <p class="mb-2"><i class="icon-base ri ri-user-line me-2 text-primary"></i><strong>Capacity:</strong> 500 people</p>
            <p class="mb-0"><i class="icon-base ri ri-check-line me-2 text-primary"></i><strong>Status:</strong> <span class="badge bg-label-primary">Registration Open</span></p>
          </div>
        </div>
        <hr class="my-4">
        <h6 class="mb-3">Registration Progress</h6>
        <div class="progress mb-2" style="height: 20px;">
          <div class="progress-bar bg-primary" role="progressbar" style="width: 90%">90% Full</div>
        </div>
        <p class="text-muted">450 / 500 registered</p>
        <hr class="my-4">
        <h6 class="mb-3">Recent Registrations</h6>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Registered</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Rahim Ahmed</td>
                <td>rahim@gmail.com</td>
                <td>2 hours ago</td>
              </tr>
              <tr>
                <td>Karim Hassan</td>
                <td>karim@gmail.com</td>
                <td>5 hours ago</td>
              </tr>
              <tr>
                <td>Fatima Begum</td>
                <td>fatima@gmail.com</td>
                <td>1 day ago</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="success" type="button"><i class="icon-base ri ri-download-line me-2"></i>Export List</x-admin.button>
        <x-admin.button variant="primary" type="button"><i class="icon-base ri ri-mail-send-line me-2"></i>Send Reminder</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="eventToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header" id="eventToastHeader">
      <i class="icon-base ri ri-information-line me-2" id="eventToastIcon"></i>
      <strong class="me-auto" id="eventToastTitle">Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="eventToastMessage">
      Message goes here
    </div>
  </div>
</div>
@endsection
