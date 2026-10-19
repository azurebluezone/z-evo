@extends('backEnd.layouts.app')
@section('title','General Setting Manage')
@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Manage General Settings</h5>
    </div>

    <div class="card-body p-0">
        <div class="card-datatable table-responsive">
            <table id="datatable-buttons" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Name</th>
                            <th>White Logo</th>
                            <th>Dark Logo</th>
                            <th>Favicon</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                
                
                    <tbody>
                        @foreach($show_data as $key=>$value)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$value->name}}</td>
                            <td>
                                @php
                                    $whiteLogoPath = preg_replace('~^public/~', '', (string) ($value->white_logo ?? ''));
                                    $whiteLogoPath = ltrim($whiteLogoPath, '/');
                                @endphp
                                @if($whiteLogoPath)
                                    <img src="{{ asset($whiteLogoPath) }}" class="backend-image" alt="" onerror="this.style.display='none'">
                                @endif
                            </td>
                            <td>
                                @php
                                    $darkLogoPath = preg_replace('~^public/~', '', (string) ($value->dark_logo ?? ''));
                                    $darkLogoPath = ltrim($darkLogoPath, '/');
                                @endphp
                                @if($darkLogoPath)
                                    <img src="{{ asset($darkLogoPath) }}" class="backend-image" alt="" onerror="this.style.display='none'">
                                @endif
                            </td>
                            <td>
                                @php
                                    $faviconPath = preg_replace('~^public/~', '', (string) ($value->favicon ?? ''));
                                    $faviconPath = ltrim($faviconPath, '/');
                                @endphp
                                @if($faviconPath)
                                    <img src="{{ asset($faviconPath) }}" class="backend-image" alt="" onerror="this.style.display='none'">
                                @endif
                            </td>
                            <td>@if($value->status==1)<span class="badge bg-label-success rounded-0">Active</span> @else <span class="badge bg-label-secondary rounded-0">Inactive</span> @endif</td>
                            <td>
                                <div class="d-inline-flex align-items-center gap-1">
                                    @if($value->status == 1)
                                    <form method="post" action="{{route('settings.inactive')}}" class="d-inline"> 
                                    @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">       
                                    <x-admin.button variant="text-warning" size="sm" shape="icon square" ripple="true" class="change-confirm" type="button" title="Make Inactive" data-bs-toggle="tooltip" data-bs-title="Make Inactive"><i class="icon-base ri ri-thumb-down-line"></i></x-admin.button></form>
                                    @else
                                    <form method="post" action="{{route('settings.active')}}" class="d-inline">
                                        @csrf
                                    <input type="hidden" value="{{$value->id}}" name="hidden_id">        
                                    <x-admin.button variant="text-success" size="sm" shape="icon square" ripple="true" class="change-confirm" type="button" title="Make Active" data-bs-toggle="tooltip" data-bs-title="Make Active"><i class="icon-base ri ri-thumb-up-line"></i></x-admin.button></form>
                                    @endif

                                    <a href="{{route('settings.edit',$value->id)}}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit" data-bs-toggle="tooltip" data-bs-title="Edit"><i class="icon-base ri ri-edit-box-line"></i></a>
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
