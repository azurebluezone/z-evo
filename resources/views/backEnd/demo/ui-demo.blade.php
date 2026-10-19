@extends('backEnd.layouts.app')
@section('title','UI Demo')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <h4 class="page-title">UI Demo (Admin Blueprint)</h4>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">KPI Widget Card Style (Template)</h5>
        </div>
        <div class="card-body card-widget-separator">
          <div class="row gy-4 gy-sm-1">
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                <div>
                  <h4 class="mb-0">56</h4>
                  <p class="mb-0">Pending Payment</p>
                </div>
                <div class="avatar me-sm-6">
                  <span class="avatar-initial rounded-3 text-heading">
                    <i class="icon-base ri ri-calendar-2-line icon-26px"></i>
                  </span>
                </div>
              </div>
              <hr class="d-none d-sm-block d-lg-none me-6">
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                <div>
                  <h4 class="mb-0">12,689</h4>
                  <p class="mb-0">Completed</p>
                </div>
                <div class="avatar me-lg-6">
                  <span class="avatar-initial rounded-3 text-heading">
                    <i class="icon-base ri ri-check-double-line icon-26px"></i>
                  </span>
                </div>
              </div>
              <hr class="d-none d-sm-block d-lg-none">
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                <div>
                  <h4 class="mb-0">124</h4>
                  <p class="mb-0">Refunded</p>
                </div>
                <div class="avatar me-sm-6">
                  <span class="avatar-initial rounded-3 text-heading">
                    <i class="icon-base ri ri-wallet-3-line icon-26px"></i>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h4 class="mb-0">32</h4>
                  <p class="mb-0">Failed</p>
                </div>
                <div class="avatar">
                  <span class="avatar-initial rounded-3 text-heading">
                    <i class="icon-base ri ri-error-warning-line icon-26px"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">KPI Cards (Main Dashboard)</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
              <div class="card shadow-none border mb-0">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <div class="text-muted">Today's Revenue</div>
                      <div class="h4 mb-0" id="kpi-revenue">৳ 0</div>
                    </div>
                    <i class="icon-base ri ri-money-dollar-circle-line" style="font-size: 28px;"></i>
                  </div>
                  <small class="text-muted">Last 24 hours</small>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
              <div class="card shadow-none border mb-0">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <div class="text-muted">Total Orders</div>
                      <div class="h4 mb-0" id="kpi-orders">0</div>
                    </div>
                    <i class="icon-base ri ri-shopping-cart-2-line" style="font-size: 28px;"></i>
                  </div>
                  <small class="text-muted">Today</small>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
              <div class="card shadow-none border mb-0">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <div class="text-muted">New Visitors</div>
                      <div class="h4 mb-0" id="kpi-visitors">0</div>
                    </div>
                    <i class="icon-base ri ri-user-3-line" style="font-size: 28px;"></i>
                  </div>
                  <small class="text-muted">Today</small>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
              <div class="card shadow-none border mb-0">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <div class="text-muted">Pending Payments</div>
                      <div class="h4 mb-0" id="kpi-pending">0</div>
                    </div>
                    <i class="icon-base ri ri-time-line" style="font-size: 28px;"></i>
                  </div>
                  <small class="text-muted">Requires action</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">Sales Trend (ApexCharts)</h5>
          <div class="d-flex gap-2">
            <x-admin.button variant="outline-secondary" size="sm" type="button" data-range="7">Last 7 days</x-admin.button>
            <x-admin.button variant="outline-secondary" size="sm" type="button" data-range="30">Last 30 days</x-admin.button>
          </div>
        </div>
        <div class="card-body">
          <div id="sales-trend-chart" style="min-height: 320px;"></div>
          <small class="text-muted">Hover করে প্রতিদিনের ডেটা দেখা যাবে।</small>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Mobile Friendly Alerts & Toast</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12 col-lg-6">
              <div class="alert alert-primary mb-0" role="alert">
                Primary alert — use for informative messages.
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="alert alert-warning mb-0" role="alert">
                Warning alert — use for important actions.
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <x-admin.button variant="primary" type="button" id="btn-bs-toast">Show Toast</x-admin.button>
            <x-admin.button variant="outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#confirmModal">Confirm Modal</x-admin.button>
          </div>

          <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="demoToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
              <div class="toast-header">
                <strong class="me-auto">Admin</strong>
                <small>now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
              <div class="toast-body">Toast working (Bootstrap).</div>
            </div>
          </div>

          <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="confirmModalLabel">Confirm action</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  Are you sure you want to proceed?
                </div>
                <div class="modal-footer">
                  <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
                  <x-admin.button variant="danger" type="button" data-bs-dismiss="modal" id="btn-confirm-yes">Yes, proceed</x-admin.button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Quick Edit (Offcanvas)</h5>
        </div>
        <div class="card-body">
          <x-admin.button variant="outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#quickEditOffcanvas" aria-controls="quickEditOffcanvas">Open Quick Edit</x-admin.button>
          <div class="offcanvas offcanvas-end" tabindex="-1" id="quickEditOffcanvas" aria-labelledby="quickEditOffcanvasLabel">
            <div class="offcanvas-header">
              <h5 class="offcanvas-title" id="quickEditOffcanvasLabel">Quick Edit</h5>
              <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
              <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" value="Sample Product" />
              </div>
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select">
                  <option selected>Active</option>
                  <option>Inactive</option>
                </select>
              </div>
              <div class="d-flex gap-2">
                <x-admin.button variant="primary" type="button" id="btn-quick-save">Save</x-admin.button>
                <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="offcanvas">Close</x-admin.button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">Responsive Table + Pagination (No Plugin)</h5>
          <div class="d-flex gap-2">
            <x-admin.button variant="outline-secondary" size="sm" type="button">Export</x-admin.button>
            <x-admin.button variant="outline-secondary" size="sm" type="button">Filter</x-admin.button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Title</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @for($i=1;$i<=8;$i++)
                <tr>
                  <td>{{ $i }}</td>
                  <td>Item {{ $i }}</td>
                  <td>
                    @if($i % 3 === 0)
                      <span class="badge bg-label-warning">Pending</span>
                    @else
                      <span class="badge bg-label-success">Active</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <div class="btn-group" role="group">
                      <x-admin.button variant="outline-secondary" size="sm" ripple="true" type="button" data-bs-toggle="modal" data-bs-target="#confirmModal" data-confirm-title="Open edit?" data-confirm-message="This is a demo. In real pages, this will navigate to the edit page."><i class="icon-base ri ri-edit-line"></i></x-admin.button>
                      <x-admin.button variant="outline-danger" size="sm" type="button" data-bs-toggle="modal" data-bs-target="#confirmModal"><i class="icon-base ri ri-delete-bin-6-line"></i></x-admin.button>
                    </div>
                  </td>
                </tr>
                @endfor
              </tbody>
            </table>
          </div>

          <nav aria-label="Table pagination">
            <ul class="pagination justify-content-end mb-0">
              <li class="page-item disabled"><a class="page-link" href="javascript:void(0)">Previous</a></li>
              <li class="page-item active"><a class="page-link" href="javascript:void(0)">1</a></li>
              <li class="page-item"><a class="page-link" href="javascript:void(0)">2</a></li>
              <li class="page-item"><a class="page-link" href="javascript:void(0)">3</a></li>
              <li class="page-item"><a class="page-link" href="javascript:void(0)">Next</a></li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Form Layout (Clean + Mobile Friendly)</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <div class="divider text-start">
                <div class="divider-text">Basic Info</div>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Title</label>
              <input type="text" class="form-control" placeholder="e.g. Campaign title" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select">
                <option selected>Active</option>
                <option>Inactive</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" rows="4" placeholder="Write a short description..."></textarea>
            </div>

            <div class="col-12">
              <div class="divider text-start">
                <div class="divider-text">Meta Keywords</div>
              </div>
            </div>
            <div class="col-12 col-lg-7">
              <label class="form-label">Keywords (Checkbox)</label>
              <div class="d-flex flex-wrap gap-3">
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" checked />
                  <span class="form-check-label">New</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" />
                  <span class="form-check-label">Hot</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" />
                  <span class="form-check-label">Sale</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" />
                  <span class="form-check-label">Limited</span>
                </label>
              </div>
              <small class="text-muted d-block mt-1">Mobile-এ checkbox সবচেয়ে user-friendly।</small>
            </div>
            <div class="col-12 col-lg-5">
              <label class="form-label">Primary keyword (Dropdown)</label>
              <select class="form-select">
                <option selected>New</option>
                <option>Hot</option>
                <option>Sale</option>
                <option>Limited</option>
              </select>
            </div>

            <div class="col-12">
              <div class="divider text-start">
                <div class="divider-text">Attributes / Variants</div>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <label class="form-label">Enable attributes (Checkbox)</label>
              <div class="d-flex flex-column gap-2">
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" checked />
                  <span class="form-check-label">Size</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" checked />
                  <span class="form-check-label">Color</span>
                </label>
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" />
                  <span class="form-check-label">Material</span>
                </label>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <label class="form-label">Variant options (Dropdown)</label>
              <select class="form-select" multiple size="6">
                <option selected>S</option>
                <option selected>M</option>
                <option>L</option>
                <option selected>Red</option>
                <option>Black</option>
                <option>Blue</option>
              </select>
              <small class="text-muted d-block mt-1">Multiple select works without any JS plugin.</small>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
              <x-admin.button variant="outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#confirmModal" data-confirm-title="Delete attribute?" data-confirm-message="This will remove the attribute from the product (demo).">Delete Attribute</x-admin.button>
              <x-admin.button variant="outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#confirmModal" data-confirm-title="Disable variant?" data-confirm-message="This will disable variants for this product (demo).">Disable Variants</x-admin.button>
            </div>

            <div class="col-12">
              <div class="divider text-start">
                <div class="divider-text">Pickers</div>
              </div>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">Date</label>
              <input type="date" class="form-control" />
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">Time</label>
              <input type="time" class="form-control" />
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">Datetime</label>
              <input type="datetime-local" class="form-control" />
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">Amount</label>
              <div class="input-group">
                <span class="input-group-text">৳</span>
                <input type="number" class="form-control" placeholder="0" />
              </div>
            </div>

            <div class="col-12">
              <div class="divider text-start">
                <div class="divider-text">File Upload</div>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Main Image</label>
              <input type="file" class="form-control" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Gallery Images</label>
              <input type="file" class="form-control" multiple />
            </div>

            <div class="col-12">
              <div class="divider text-start">
                <div class="divider-text">Switches & Radios</div>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="small fw-medium mb-3">Default switches</div>
              <div class="d-flex flex-column gap-3">
                <label class="switch">
                  <input type="checkbox" class="switch-input" checked>
                  <span class="switch-toggle-slider">
                    <span class="switch-on"></span>
                    <span class="switch-off"></span>
                  </span>
                  <span class="switch-label">Default</span>
                </label>
                <label class="switch switch-square">
                  <input type="checkbox" class="switch-input">
                  <span class="switch-toggle-slider">
                    <span class="switch-on"></span>
                    <span class="switch-off"></span>
                  </span>
                  <span class="switch-label">Square</span>
                </label>
                <label class="switch">
                  <input type="checkbox" class="switch-input" checked>
                  <span class="switch-toggle-slider">
                    <span class="switch-on"><i class="icon-base ri ri-check-line"></i></span>
                    <span class="switch-off"><i class="icon-base ri ri-close-line"></i></span>
                  </span>
                  <span class="switch-label">With icon</span>
                </label>
                <label class="switch switch-square">
                  <input type="checkbox" class="switch-input">
                  <span class="switch-toggle-slider">
                    <span class="switch-on"><i class="icon-base ri ri-check-line"></i></span>
                    <span class="switch-off"><i class="icon-base ri ri-close-line"></i></span>
                  </span>
                  <span class="switch-label">Square with icon</span>
                </label>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="small fw-medium mb-3">Stacked Radio</div>
              <div class="switches-stacked">
                <label class="switch">
                  <input type="radio" class="switch-input" name="ui-demo-stacked" checked>
                  <span class="switch-toggle-slider">
                    <span class="switch-on"></span>
                    <span class="switch-off"></span>
                  </span>
                  <span class="switch-label">Option 1</span>
                </label>
                <label class="switch">
                  <input type="radio" class="switch-input" name="ui-demo-stacked">
                  <span class="switch-toggle-slider">
                    <span class="switch-on"></span>
                    <span class="switch-off"></span>
                  </span>
                  <span class="switch-label">Option 2</span>
                </label>
                <label class="switch">
                  <input type="radio" class="switch-input" name="ui-demo-stacked">
                  <span class="switch-toggle-slider">
                    <span class="switch-on"></span>
                    <span class="switch-off"></span>
                  </span>
                  <span class="switch-label">Option 3</span>
                </label>
              </div>
            </div>

            <div class="col-12 d-flex gap-2">
              <x-admin.button variant="primary" type="button" data-bs-toggle="modal" data-bs-target="#confirmModal">Save</x-admin.button>
              <x-admin.button variant="outline-secondary" type="reset">Reset</x-admin.button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Selectors (Select2, Tagify, Bootstrap Select)</h5>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-12 col-md-6">
              <div class="form-floating form-floating-outline">
                <select id="select2Basic" class="select2 form-select" data-allow-clear="true">
                  <option value="">Select an option</option>
                  <option value="AK">Alaska</option>
                  <option value="HI">Hawaii</option>
                  <option value="CA">California</option>
                  <option value="NV">Nevada</option>
                  <option value="OR">Oregon</option>
                  <option value="WA">Washington</option>
                </select>
                <label for="select2Basic">Select2 Basic</label>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="form-floating form-floating-outline">
                <select id="select2Multiple" class="select2 form-select" multiple>
                  <optgroup label="Pacific Time Zone">
                    <option value="CA">California</option>
                    <option value="NV">Nevada</option>
                    <option value="OR">Oregon</option>
                    <option value="WA">Washington</option>
                  </optgroup>
                  <optgroup label="Mountain Time Zone">
                    <option value="AZ">Arizona</option>
                    <option value="CO">Colorado</option>
                    <option value="ID">Idaho</option>
                    <option value="MT">Montana</option>
                  </optgroup>
                </select>
                <label for="select2Multiple">Select2 Multiple</label>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="form-floating form-floating-outline">
                <input id="tagifyBasic" class="form-control" name="tagifyBasic" value="Tag1, Tag2, Tag3" />
                <label for="tagifyBasic">Tagify Basic</label>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="form-floating form-floating-outline">
                <input id="tagifySuggestions" name="tagifySuggestions" class="form-control h-auto" placeholder="select technologies" value="css, html" />
                <label for="tagifySuggestions">Tagify with Suggestions</label>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="form-floating form-floating-outline">
                <select id="select2Search" class="select2 form-select" data-allow-clear="true">
                  <option value="">Search for food...</option>
                  <option value="hotdog">Hot Dog</option>
                  <option value="burger">Burger</option>
                  <option value="pizza">Pizza</option>
                  <option value="salad">Salad</option>
                  <option value="sushi">Sushi</option>
                  <option value="tacos">Tacos</option>
                </select>
                <label for="select2Search">Select2 with Search</label>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="form-floating form-floating-outline">
                <select id="select2Icons" class="select2-icons form-select">
                  <option value="wordpress" data-icon="ri-wordpress-fill">WordPress</option>
                  <option value="codepen" data-icon="ri-codepen-line">Codepen</option>
                  <option value="html5" data-icon="ri-html5-fill">HTML5</option>
                  <option value="css3" data-icon="ri-css3-fill">CSS3</option>
                </select>
                <label for="select2Icons">Select2 with Icons</label>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Variant options (Native Multiple)</label>
              <select class="form-select" multiple size="4">
                <option selected>S</option>
                <option selected>M</option>
                <option>L</option>
                <option>XL</option>
                <option selected>Red</option>
                <option>Black</option>
                <option>Blue</option>
              </select>
              <small class="text-muted d-block mt-1">Multiple select works without any JS plugin.</small>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Standard Bootstrap Select</label>
              <select class="form-select">
                <option selected>Rocky</option>
                <option>Pulp Fiction</option>
                <option>The Godfather</option>
                <option>Forrest Gump</option>
              </select>
              <small class="text-muted d-block mt-1">Native Bootstrap select with simple styling.</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Tabs & Accordion (Mobile Friendly)</h5>
        </div>
        <div class="card-body">
          <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="pills-a-tab" data-bs-toggle="pill" data-bs-target="#pills-a" type="button" role="tab" aria-controls="pills-a" aria-selected="true">Overview</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="pills-b-tab" data-bs-toggle="pill" data-bs-target="#pills-b" type="button" role="tab" aria-controls="pills-b" aria-selected="false">Details</button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="pills-a" role="tabpanel" aria-labelledby="pills-a-tab">
              <div class="alert alert-info mb-0" role="alert">Use tabs for secondary sections to keep mobile screens clean.</div>
            </div>
            <div class="tab-pane fade" id="pills-b" role="tabpanel" aria-labelledby="pills-b-tab">
              <div class="accordion" id="uiDemoAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Section 1</button>
                  </h2>
                  <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#uiDemoAccordion">
                    <div class="accordion-body">Accordion works well on mobile for long forms/settings.</div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Section 2</button>
                  </h2>
                  <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#uiDemoAccordion">
                    <div class="accordion-body">Keep sections small and readable.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <section id="basic-footer">
        <h5 class="pb-1 mb-3">Basic Footer (Component Preview)</h5>
        <footer class="footer bg-light">
          <div class="container-fluid d-flex flex-md-row flex-column justify-content-between align-items-md-center gap-1 container-p-x py-4">
            <div>
              <a href="{{ route('dashboard') }}" class="footer-brand fw-bold">{{ $generalsetting->name ?? config('app.name', 'Z-Evo-Commerce') }}</a> ©
            </div>
            <div class="d-flex flex-column flex-sm-row">
              <a href="javascript:void(0)" class="footer-link me-6">License</a>
              <a href="javascript:void(0)" class="footer-link me-6">Help</a>
              <a href="javascript:void(0)" class="footer-link me-6">Contact</a>
              <a href="javascript:void(0)" class="footer-link">Terms &amp; Conditions</a>
            </div>
          </div>
        </footer>
      </section>
    </div>
  </div>
</div>
@endsection

