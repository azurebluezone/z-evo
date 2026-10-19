{{--
    backEnd/brand/_row - one row of the brand grid.

    Extracted from brand/index.blade.php by tools/admin_grid_page.py, verbatim.
    The blade page and the DataTables endpoint both render it, so a change to
    this row reaches both - the thing that goes wrong when the two copies live
    in separate files.

        $value  the model for this row
        $index  the row number to print in the first cell
--}}

<tr>
    <td>{{ $index }}</td>
    <td class="fw-medium">{{$value->name ?? 'N/A'}}</td>
    <td>
        @php
            $brandImg = $value->image ?? null;
            $brandImgPath = $brandImg ? preg_replace('~^public/~', '', $brandImg) : null;
            $brandImgPath = $brandImgPath ? ltrim($brandImgPath, '/') : null;
            $fallbackImg = $generalSetting->frontend_empty_image ?? 'assets/images/empty.png';
            $fallbackImg = preg_replace('~^public/~', '', $fallbackImg);
            $fallbackImg = ltrim($fallbackImg, '/');
        @endphp
        <img
            src="{{ $brandImgPath && file_exists(public_path($brandImgPath)) ? asset($brandImgPath) : asset($fallbackImg) }}"
            class="border rounded-0" style="height: 40px; width: 40px; object-fit: cover;"
            alt="{{$value->name ?? 'Brand'}}"
            onerror="this.src='{{ asset('assets/images/empty.png') }}'">
    </td>
    <td>
        @if(($value->status ?? 0) == 1)
        <form method="post" action="{{route('brands.inactive')}}" class="d-inline"> 
        @csrf
        <input type="hidden" value="{{$value->id}}" name="hidden_id">       
        <div class="form-check form-switch custom-switch-sharp m-0">
            <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" checked title="Active">
        </div>
        </form>
        @else
        <form method="post" action="{{route('brands.active')}}" class="d-inline">
            @csrf
        <input type="hidden" value="{{$value->id}}" name="hidden_id">        
        <div class="form-check form-switch custom-switch-sharp m-0">
            <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" title="Inactive">
        </div>
        </form>
        @endif
    </td>
    <td class="text-end">
        <div class="d-inline-flex justify-content-end align-items-center gap-1">
            <a href="{{route('brands.edit',$value->id)}}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit">
                <i class="icon-base ri ri-edit-box-line"></i>
            </a>

            <form method="post" action="{{route('brands.destroy')}}" class="d-inline" id="deleteForm-{{ $value->id }}">        
                @csrf
                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $value->id }}')">
                    <i class="icon-base ri ri-delete-bin-7-line"></i>
                </x-admin.button>
            </form>
        </div>
    </td>
</tr>
