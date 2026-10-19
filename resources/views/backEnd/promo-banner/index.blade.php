@extends('backEnd.layouts.app')
@section('title','Promo Banner Manage')
@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage Promo Banners</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('promo_banners.create') }}" class="btn btn-primary btn-square waves-effect">Create</a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table class="table table-hover w-100 align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">SL</th>
                        <th style="width:18%">Image</th>
                        <th style="width:20%">Title</th>
                        <th style="width:25%">Link</th>
                        <th style="width:8%">Sort</th>
                        <th style="width:10%">Status</th>
                        <th style="width:12%" class="text-end">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($data as $value)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                                    @php
                                        $imgPath = preg_replace('~^public/~', '', (string) ($value->image ?? ''));
                                        $imgPath = ltrim($imgPath, '/');
                                    @endphp
                                    @if($imgPath)
<img src="{{ asset($imgPath) }}" 
     class="backend-image" 
     alt="Image" 
     style="max-width: 50px; max-height: 50px; object-fit: cover;" 
     onerror="this.style.display='none'">
                                    @endif
                                </td>
                                <td class="fw-medium">{{ $value->title }}</td>
                                <td>{{ $value->link }}</td>
                                <td>{{ $value->sort_order }}</td>
                                <td>
                                    @if((int) $value->status === 1)
                                        <form method="post" action="{{ route('promo_banners.inactive') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                            <label class="switch" title="Active">
                                                <input type="checkbox" class="switch-input js-auto-submit" checked>
                                                <span class="switch-toggle-slider">
                                                    <span class="switch-on"></span>
                                                    <span class="switch-off"></span>
                                                </span>
                                            </label>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('promo_banners.active') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                            <label class="switch" title="Inactive">
                                                <input type="checkbox" class="switch-input js-auto-submit">
                                                <span class="switch-toggle-slider">
                                                    <span class="switch-on"></span>
                                                    <span class="switch-off"></span>
                                                </span>
                                            </label>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                        <a href="{{ route('promo_banners.edit', $value->id) }}" class="btn btn-sm btn-icon btn-text-secondary btn-square waves-effect" title="Edit">
                                            <i class="icon-base ri ri-edit-line"></i>
                                        </a>
                                        <form method="post" action="{{ route('promo_banners.destroy') }}" class="d-inline" id="deleteForm-{{ $value->id }}">
                                            @csrf
                                            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                            <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $value->id }}')">
                                                <i class="icon-base ri ri-delete-bin-line"></i>
                                            </x-admin.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
