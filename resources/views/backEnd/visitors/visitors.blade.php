@extends('backEnd.layouts.app')
@section('title', 'All Visitors')

@section('content')
<!-- Page Header -->
<div class="row mb-6">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1"><i class="icon-base ri ri-user-search-line me-2"></i>All Visitors</h4>
                <p class="text-muted mb-0">Website visitor list and management</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.visitors.blocked') }}" class="btn btn-outline-danger waves-effect">
                    <i class="icon-base ri ri-shield-cross-line me-2"></i>Blocked Visitors
                </a>
                <a href="{{ route('admin.visitors.index') }}" class="btn btn-primary waves-effect">
                    <i class="icon-base ri ri-bar-chart-box-line me-2"></i>Analytics
                </a>
                <a href="{{ route('admin.visitors.list') }}" class="btn btn-secondary waves-effect">
                    <i class="icon-base ri ri-list-check me-2"></i>Refresh
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Search Filters -->
<div class="card mb-6">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="icon-base ri ri-filter-3-line me-2"></i>Filter & Search</h5>
        <x-admin.button variant="outline-secondary" size="sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
            <i class="icon-base ri ri-arrow-down-s-line"></i>
        </x-admin.button>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body">
            <form method="get" action="{{ route('admin.visitors.list') }}">
                <div class="row g-4">
                    <!-- Row 1 -->
                    <div class="col-sm-6 col-md-3">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="visitor_id" id="visitor_id" class="form-control" value="{{ request()->get('visitor_id') }}" placeholder="Visitor ID">
                            <label for="visitor_id">Visitor ID</label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="ip" id="ip" class="form-control" value="{{ request()->get('ip') }}" placeholder="IP Address">
                            <label for="ip">IP Address</label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="country" id="country" class="form-control" value="{{ request()->get('country') }}" placeholder="Country">
                            <label for="country">Country</label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="city" id="city" class="form-control" value="{{ request()->get('city') }}" placeholder="City">
                            <label for="city">City</label>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="col-sm-6 col-md-3">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="isp" id="isp" class="form-control" value="{{ request()->get('isp') }}" placeholder="ISP">
                            <label for="isp">ISP</label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="user_agent" id="user_agent" class="form-control" value="{{ request()->get('user_agent') }}" placeholder="User Agent">
                            <label for="user_agent">User Agent</label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <div class="form-floating form-floating-outline">
                            <input type="date" name="from" id="from" class="form-control" value="{{ request()->get('from') }}">
                            <label for="from">From Date</label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <div class="form-floating form-floating-outline">
                            <input type="date" name="to" id="to" class="form-control" value="{{ request()->get('to') }}">
                            <label for="to">To Date</label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <div class="form-floating form-floating-outline">
                            <select name="status" id="status" class="form-select">
                                <option value="">All</option>
                                <option value="1" {{ request()->get('status') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request()->get('status') === '0' ? 'selected' : '' }}>Blocked</option>
                            </select>
                            <label for="status">Status</label>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <x-admin.button variant="primary" ripple="true" type="submit">
                                <i class="icon-base ri ri-search-line me-2"></i>Search
                            </x-admin.button>
                            <a href="{{ route('admin.visitors.list') }}" class="btn btn-outline-secondary waves-effect">
                                <i class="icon-base ri ri-refresh-line me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Visitors DataTable -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h5 class="mb-0"><i class="icon-base ri ri-team-line me-2"></i>Visitor List</h5>
        <div class="d-flex gap-2">
            <span class="badge bg-label-primary">Total: {{ $visitors->total() }}</span>
            <span class="badge bg-label-success">Page: {{ $visitors->currentPage() }}/{{ $visitors->lastPage() }}</span>
        </div>
    </div>
    <div class="card-body p-0">
        <form method="post" action="" id="bulkActionForm" class="mb-0">
            @csrf
            <input type="hidden" name="_bulk_action" id="bulkAction" value="">

            <!-- Bulk Actions Toolbar -->
            <div class="p-3 border-bottom bg-light">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="d-flex gap-2 align-items-center">
                        <input type="checkbox" class="form-check-input" id="selectAll" title="Select All">
                        <label for="selectAll" class="mb-0 fw-medium">Select All</label>
                    </div>
                    <div class="d-flex gap-2">
                        <x-admin.button variant="info" size="sm" ripple="true" type="button" id="visitorInfoBtn" data-bs-toggle="modal" data-bs-target="#visitorInfoModal">
                            <i class="icon-base ri ri-eye-line me-2"></i>Visitor Info
                        </x-admin.button>
                        <x-admin.button variant="danger" size="sm" ripple="true" type="button" id="bulkBlockBtn">
                            <i class="icon-base ri ri-shield-cross-line me-2"></i>Block
                        </x-admin.button>
                        <x-admin.button variant="success" size="sm" ripple="true" type="button" id="bulkUnblockBtn">
                            <i class="icon-base ri ri-shield-check-line me-2"></i>Unblock
                        </x-admin.button>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>SL</th>
                            <th>Visitor ID</th>
                            <th>IP Address</th>
                            <th>Location</th>
                            <th>ISP</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visitors as $row)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input row-check" name="ids[]" value="{{ $row->id }}">
                                </td>
                                <td>
                                    <span class="fw-medium">{{ ($visitors->currentPage() - 1) * $visitors->perPage() + $loop->iteration }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary text-truncate d-inline-block" style="max-width: 140px;" title="{{ $row->visitor_id }}">
                                        {{ Str::limit($row->visitor_id, 14) }}
                                    </span>
                                </td>
                                <td>
                                    <code class="small">{{ $row->ip }}</code>
                                </td>
                                <td>
                                    @if($row->country || $row->city)
                                        <i class="icon-base ri ri-map-pin-line text-primary me-1"></i>
                                        <small>
                                            {{ $row->city }}{{ $row->city && $row->country ? ', ' : '' }}{{ $row->country }}
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row->isp)
                                        <small class="text-muted">{{ Str::limit($row->isp, 20) }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if((int) $row->status === 1)
                                        <span class="badge bg-label-success">Active</span>
                                    @else
                                        <span class="badge bg-label-danger">Blocked</span>
                                    @endif
                                </td>
                                <td class="text-nowrap small">
                                    {{ $row->created_at ? $row->created_at->format('d M Y') : '-' }}
                                </td>
                                <td>
                                    <span class="text-muted">-</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-center">
                                        <i class="icon-base ri ri-user-search-line icon-48px text-muted mb-3 d-block"></i>
                                        <h5 class="text-muted">No visitors found</h5>
                                        <p class="text-muted mb-0">No data found matching your search criteria</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        @if($visitors->hasPages())
            <div class="p-3 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <small class="text-muted">
                        Showing {{ $visitors->firstItem() }} to {{ $visitors->lastItem() }} of {{ $visitors->total() }}
                    </small>
                    <nav aria-label="Page navigation">
                        {{ $visitors->links('pagination::bootstrap-4') }}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Visitor Info Modal -->
<div class="modal fade" id="visitorInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="visitorInfoModalTitle">
                    <i class="icon-base ri ri-user-line me-2"></i>Visitor Info
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded" style="white-space: pre-line; font-family: 'Consolas', monospace; font-size: 0.9rem;">
                    <span id="visitorInfoModalValue"></span>
                </div>
            </div>
            <div class="modal-footer">
                <x-admin.button variant="outline-secondary" ripple="true" type="button" data-bs-dismiss="modal">Close</x-admin.button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        'use strict';

        // Store visitor data for selected rows
        const visitorData = {};
        document.querySelectorAll('.row-check').forEach(cb => {
            const row = cb.closest('tr');
            if (row) {
                const cells = row.querySelectorAll('td');
                visitorData[cb.value] = {
                    id: cb.value,
                    visitorId: cells[2]?.textContent?.trim() || 'N/A',
                    ip: cells[3]?.textContent?.trim() || 'N/A',
                    location: cells[4]?.textContent?.trim() || 'N/A',
                    isp: cells[5]?.textContent?.trim() || 'N/A',
                    status: cells[6]?.textContent?.trim() || 'N/A',
                    date: cells[7]?.textContent?.trim() || 'N/A'
                };
            }
        });

        // Update bulk action buttons state
        function updateBulkButtons() {
            const checked = document.querySelectorAll('.row-check:checked');
            const checkedCount = checked.length;
            const blockBtn = document.getElementById('bulkBlockBtn');
            const unblockBtn = document.getElementById('bulkUnblockBtn');
            const infoBtn = document.getElementById('visitorInfoBtn');

            if (blockBtn) blockBtn.disabled = checkedCount < 1;
            if (unblockBtn) unblockBtn.disabled = checkedCount < 1;
            if (infoBtn) infoBtn.disabled = checkedCount !== 1; // Only enable when exactly 1 selected

            // Update button text with count
            if (checkedCount > 0) {
                if (blockBtn) blockBtn.innerHTML = '<i class="icon-base ri ri-shield-cross-line me-2"></i>Block (' + checkedCount + ')';
                if (unblockBtn) unblockBtn.innerHTML = '<i class="icon-base ri ri-shield-check-line me-2"></i>Unblock (' + checkedCount + ')';
                if (infoBtn && checkedCount === 1) {
                    const id = checked[0].value;
                    const data = visitorData[id];
                    if (data) {
                        infoBtn.setAttribute('data-title', 'Visitor Info');
                        infoBtn.setAttribute('data-value',
                            'ID: ' + data.visitorId + '\n' +
                            'IP: ' + data.ip + '\n' +
                            'Location: ' + data.location + '\n' +
                            'ISP: ' + data.isp + '\n' +
                            'Status: ' + data.status + '\n' +
                            'Date: ' + data.date
                        );
                    }
                }
            } else {
                if (blockBtn) blockBtn.innerHTML = '<i class="icon-base ri ri-shield-cross-line me-2"></i>Block';
                if (unblockBtn) unblockBtn.innerHTML = '<i class="icon-base ri ri-shield-check-line me-2"></i>Unblock';
                if (infoBtn) {
                    infoBtn.innerHTML = '<i class="icon-base ri ri-eye-line me-2"></i>Visitor Info';
                    infoBtn.removeAttribute('data-value');
                }
            }
        }

        // Select All Checkbox
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.row-check').forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkButtons();
            });
        }

        // Individual Row Checkboxes
        document.querySelectorAll('.row-check').forEach(cb => {
            cb.addEventListener('change', function () {
                const all = document.querySelectorAll('.row-check').length;
                const checked = document.querySelectorAll('.row-check:checked').length;
                const selectAll = document.getElementById('selectAll');

                if (selectAll) {
                    selectAll.checked = all > 0 && checked === all;
                    selectAll.indeterminate = checked > 0 && checked < all;
                }
                updateBulkButtons();
            });
        });

        // Bulk Block Button
        const bulkForm = document.getElementById('bulkActionForm');
        const bulkBlockBtn = document.getElementById('bulkBlockBtn');
        const bulkUnblockBtn = document.getElementById('bulkUnblockBtn');

        if (bulkForm && bulkBlockBtn) {
            bulkBlockBtn.addEventListener('click', function () {
                if (confirm('Are you sure you want to block selected visitors?')) {
                    bulkForm.action = "{{ route('admin.visitors.bulk-block') }}";
                    bulkForm.submit();
                }
            });
        }

        // Bulk Unblock Button
        if (bulkForm && bulkUnblockBtn) {
            bulkUnblockBtn.addEventListener('click', function () {
                if (confirm('Are you sure you want to unblock selected visitors?')) {
                    bulkForm.action = "{{ route('admin.visitors.bulk-unblock') }}";
                    bulkForm.submit();
                }
            });
        }

        // Modal Data Handling
        const modalEl = document.getElementById('visitorInfoModal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const title = button?.getAttribute('data-title') || 'Info';
                const value = button?.getAttribute('data-value') || '';
                const titleEl = document.getElementById('visitorInfoModalTitle');
                const valueEl = document.getElementById('visitorInfoModalValue');

                if (titleEl) titleEl.innerHTML = '<i class="icon-base ri ri-user-line me-2"></i>' + title;
                if (valueEl) valueEl.textContent = value;
            });
        }

        // Initialize
        updateBulkButtons();
    })();
</script>
@endsection
