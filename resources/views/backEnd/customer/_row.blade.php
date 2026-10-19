{{--
    backEnd/customer/_row - one row of the customer grid.

    Extracted from customer/index.blade.php by tools/admin_grid_page.py, verbatim.
    The blade page and the DataTables endpoint both render it, so a change to
    this row reaches both - the thing that goes wrong when the two copies live
    in separate files.

        $value  the model for this row
        $index  the row number to print in the first cell
--}}

<tr>
    <td>{{ $index }}</td>
    <td>
        <div class="d-flex align-items-center">
            @php
                $imgPath = preg_replace('~^public/~', '', (string) ($value->image ?? ''));
                $imgPath = ltrim($imgPath, '/');
                $fallbackImg = 'assets/images/empty.png';
                $imgSrc = $imgPath && file_exists(public_path($imgPath)) ? asset($imgPath) : asset($fallbackImg);
            @endphp
            <img src="{{ $imgSrc }}" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;" onerror="this.src='{{ asset($fallbackImg) }}'">
            <div>
                <h6 class="mb-0">{{$value->name}}</h6>
                <small class="text-muted">ID: #CUST-{{$value->id}}</small>
            </div>
        </div>
    </td>
    <td>{{$value->phone}}</td>
    <td>{{$value->email}}</td>
    <td>
        @if($value->status=='active')
            <form method="post" action="{{route('customers.inactive')}}" class="d-inline">
                @csrf
                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" checked onchange="this.form.submit()" title="Click to deactivate">
                </div>
            </form>
        @else
            <form method="post" action="{{route('customers.active')}}" class="d-inline">
                @csrf
                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" onchange="this.form.submit()" title="Click to activate">
                </div>
            </form>
        @endif
    </td>
    <td class="text-end">
        <div class="d-flex gap-1 justify-content-end">
            <a href="{{route('customers.profile',['id'=>$value->id])}}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="View">
                <i class="icon-base ri ri-eye-line"></i>
            </a>
            <a href="{{route('customers.edit',$value->id)}}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit">
                <i class="icon-base ri ri-edit-line"></i>
            </a>
            <form method="post" action="{{route('customers.adminlog')}}" class="d-inline" target="_blank">
                @csrf
                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                <x-admin.button variant="text-info" size="sm" shape="icon square" ripple="true" type="submit" title="Login as customer">
                    <i class="icon-base ri ri-login-box-line"></i>
                </x-admin.button>
            </form>
            <form method="post" action="{{route('customers.destroy')}}" class="d-inline" id="deleteForm-{{ $value->id }}">
                @csrf
                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $value->id }}')">
                    <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
            </form>
        </div>
    </td>
</tr>
