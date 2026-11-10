@extends('backEnd.layouts.app')
@section('title','Campaign Manager')

@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
        <div>
            <h5 class="mb-0 text-uppercase fw-bold">Campaign Manager</h5>
            <p class="text-body-secondary small mb-0">Product landing pages built from one product, one template and a schedule.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('campaign.create') }}" class="btn btn-primary btn-square waves-effect">
                <i aria-hidden="true" class="ri ri-add-line me-1"></i> Create Campaign
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <caption class="visually-hidden">Campaigns with their product, template, schedule, status and actions</caption>
                <thead class="table-light">
                    <tr>
                        <th style="width:70px;">SL</th>
                        <th>Campaign</th>
                        <th style="width:190px;">Product</th>
                        <th style="width:150px;">Template</th>
                        <th style="width:170px;">Schedule</th>
                        <th style="width:110px;">Status</th>
                        <th style="width:220px;" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($show_data as $value)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('campaign.edit', $value->id) }}" class="fw-semibold text-body text-decoration-none">{{ $value->name }}</a>
                                <div class="text-body-secondary small text-truncate" style="max-width: 320px;" title="{{ $value->slug }}">{{ $value->slug }}</div>
                            </td>
                            <td>
                                @if($value->product)
                                    <span class="d-inline-block text-truncate align-middle" style="max-width: 170px;" title="{{ $value->product->name }}">{{ $value->product->name }}</span>
                                    @if(!$value->product->status)
                                        <span class="badge bg-label-warning rounded-0 ms-1">Inactive</span>
                                    @endif
                                @else
                                    <span class="text-body-secondary">No product</span>
                                @endif
                            </td>
                            <td>
                                @if($value->template)
                                    <span class="badge bg-label-info rounded-0">{{ $value->template->name }}</span>
                                @else
                                    <span class="text-body-secondary">No template</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($value->start_date || $value->end_date)
                                    <span class="d-block small">{{ $value->start_date ? $value->start_date->format('M d, Y') : 'Open start' }}</span>
                                    <span class="d-block small text-body-secondary">to {{ $value->end_date ? $value->end_date->format('M d, Y') : 'Open end' }}</span>
                                @else
                                    <span class="text-body-secondary small">Always on</span>
                                @endif
                            </td>
                            <td>
                                @if($value->status==1)
                                    <span class="badge bg-label-success rounded-0">Active</span>
                                @else
                                    <span class="badge bg-label-secondary rounded-0">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                    <a href="{{ route('campaign.preview', $value->id) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" target="_blank" rel="noopener" title="Preview campaign" aria-label="Preview {{ $value->name }}" data-bs-toggle="tooltip" data-bs-title="Preview">
                                        <i aria-hidden="true" class="icon-base ri ri-eye-line icon-22px"></i>
                                    </a>

                                    @if($value->status == 1 && $value->slug)
                                        <a href="{{ route('campaign', $value->slug) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" target="_blank" rel="noopener" title="View live page" aria-label="View live page for {{ $value->name }}" data-bs-toggle="tooltip" data-bs-title="View live">
                                            <i aria-hidden="true" class="icon-base ri ri-external-link-line icon-22px"></i>
                                        </a>
                                    @endif

                                    @if($value->status == 1)
                                        <form method="post" action="{{ route('campaign.inactive') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                            <x-admin.button variant="text-warning" size="sm" shape="icon square" ripple="true" class="change-confirm" type="button" title="Make Inactive" aria-label="Deactivate {{ $value->name }}" data-bs-toggle="tooltip" data-bs-title="Make Inactive">
                                                <i aria-hidden="true" class="icon-base ri ri-thumb-down-line icon-22px"></i>
                                            </x-admin.button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('campaign.active') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                            <x-admin.button variant="text-success" size="sm" shape="icon square" ripple="true" class="change-confirm" type="button" title="Make Active" aria-label="Activate {{ $value->name }}" data-bs-toggle="tooltip" data-bs-title="Make Active">
                                                <i aria-hidden="true" class="icon-base ri ri-thumb-up-line icon-22px"></i>
                                            </x-admin.button>
                                        </form>
                                    @endif

                                    <a href="{{ route('campaign.edit',$value->id) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit" aria-label="Edit {{ $value->name }}" data-bs-toggle="tooltip" data-bs-title="Edit">
                                        <i aria-hidden="true" class="icon-base ri ri-edit-box-line icon-22px"></i>
                                    </a>

                                    <form method="post" action="{{ route('campaign.destroy') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                        <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" class="delete-confirm" type="submit" title="Delete" aria-label="Delete {{ $value->name }}" data-bs-toggle="tooltip" data-bs-title="Delete">
                                            <i aria-hidden="true" class="icon-base ri ri-delete-bin-7-line icon-22px"></i>
                                        </x-admin.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6">
                                <i aria-hidden="true" class="icon-base ri ri-live-line icon-24px text-body-secondary d-block mb-2"></i>
                                <h6 class="mb-1">No campaign yet</h6>
                                <p class="text-body-secondary small mb-3">Create a landing page on an existing product, choose a template, and publish it when the offer is ready.</p>
                                <a href="{{ route('campaign.create') }}" class="btn btn-sm btn-primary btn-square waves-effect">
                                    <i aria-hidden="true" class="ri ri-add-line me-1"></i> Create the first campaign
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($show_data, 'links'))
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end pagination-rounded pagination-outline-primary">
                {{ $show_data->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
