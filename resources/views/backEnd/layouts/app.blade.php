<!doctype html>
<html
  lang="en"
  class="layout-navbar-fixed layout-menu-fixed layout-compact"
  dir="ltr"
  data-skin="default"
  data-bs-theme="light"
  data-assets-path="{{ asset('admin/assets') }}/"
  data-template="vertical-menu-template"
  data-flash-success="{{ json_encode(Session::get('success')) }}"
  data-flash-error="{{ json_encode(Session::get('error')) }}"
  data-flash-warning="{{ json_encode(Session::get('warning')) }}"
  data-flash-info="{{ json_encode(session('info')) }}"
  data-flash-validation="{{ json_encode($errors->first()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="robots" content="noindex, nofollow" />
    {{-- Read by the ajaxSetup in layouts/partials/scripts.blade.php so every
         POST from the admin carries a CSRF token. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Dashboard')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('admin/assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/" />
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />

    @include('backEnd.layouts.partials.styles')

    {{--
        A view may add page CSS / JS with @section('css') / @section('script').
        Without these two yields the section is compiled and then thrown away:
        whatever the view emits for its script never reaches the browser, so
        its buttons do nothing. The stacks (@push('css') / @push('script')) are
        the other, newer path and both are supported.
    --}}
    @yield('css')

    <!-- Helpers -->
    <script src="{{ asset('admin/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/template-customizer.js') }}"></script>
    <script src="{{ asset('admin/assets/js/config.js') }}"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

      @include('backEnd.layouts.partials.sidebar')

            <!-- Layout container -->
            <div class="layout-page">

                @include('backEnd.layouts.partials.navbar')

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-fluid flex-grow-1 container-p-y">
                        {{-- Shared header: customer totals + this module's record
                             count. Data comes from App\Services\Admin\PageStats
                             via the view composer; the dashboard opts out in
                             config/admin_stats.php. --}}
                        @include('backEnd.partials.stat-grid')

                        @yield('content')
                    </div>
                    <!-- / Content -->
                    @include('backEnd.layouts.partials.footer')

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    @include('backEnd.layouts.partials.scripts')

    <!-- ========== GLOBAL MODALS ========== -->

    <!-- Basic Modal -->
    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="basicModalTitle">Add New</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="basicModalBody">
            <!-- Dynamic content here -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="basicModalSave">Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body text-center pt-5">
            <div class="mb-4">
              <span class="avatar avatar-lg bg-label-success rounded-circle">
                <i class="icon-base ri ri-check-line icon-32px"></i>
              </span>
            </div>
            <h5>Success!</h5>
            <p class="mb-4" id="successModalMessage">Operation completed successfully.</p>
            <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error/Failed Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body text-center pt-5">
            <div class="mb-4">
              <span class="avatar avatar-lg bg-label-danger rounded-circle">
                <i class="icon-base ri ri-close-line icon-32px"></i>
              </span>
            </div>
            <h5>Error!</h5>
            <p class="mb-4" id="errorModalMessage">Something went wrong.</p>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Global Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
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
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-danger" id="globalConfirmDeleteBtn">Yes, Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Large Modal -->
    <div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="largeModalTitle">Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="largeModalBody">
            <!-- Dynamic content here -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Scrollable Modal -->
    <div class="modal fade" id="scrollModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="scrollModalTitle">Terms & Conditions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="scrollModalBody">
            <!-- Dynamic scrollable content here -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary">I Agree</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ========== END GLOBAL MODALS ========== -->
    
    <script src="{{ asset('admin/assets/js/admin-app-alerts.js') }}"></script>

    {{--
        Page scripts. Placed at the very end of <body> so a view's script sees
        the DOM it is written against, and after the layout's own scripts, so
        jQuery / Bootstrap / Select2 are already on the page.
    --}}
    @yield('script')
</body>
</html>
