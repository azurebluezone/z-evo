{{--
    backEnd/coupon/_row - one row of the coupon grid.

    Extracted from coupon/index.blade.php by tools/admin_grid_page.py, verbatim.
    The blade page and the DataTables endpoint both render it, so a change to
    this row reaches both - the thing that goes wrong when the two copies live
    in separate files.

        $value  the coupon for this row
        $index  the row number to print in the first cell
--}}

<tr>
    <td>{{ $index }}</td>
    <td class="fw-medium">{{$value->code ?? 'N/A'}}</td>
    <td>{{ strtoupper($value->type) }}</td>
    <td>{{ $value->value }}</td>
    <td>{{ $value->min_amount }}</td>
    <td>{{ $value->max_discount }}</td>
    <td>{{ $value->start_at ? $value->start_at->format('Y-m-d H:i') : '' }}</td>
    <td>{{ $value->end_at ? $value->end_at->format('Y-m-d H:i') : '' }}</td>
    <td>
        @if(($value->status ?? 0) == 1)
        <form method="post" action="{{route('coupons.inactive')}}" class="d-inline">
        @csrf
        <input type="hidden" value="{{$value->id}}" name="hidden_id">
        <div class="form-check form-switch custom-switch-sharp m-0">
            <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" checked title="Active">
        </div>
        </form>
        @else
        <form method="post" action="{{route('coupons.active')}}" class="d-inline">
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
            <a href="{{route('coupons.edit',$value->id)}}" class="btn btn-sm btn-icon btn-text-secondary" title="Edit">
                <i class="icon-base ri ri-edit-line"></i>
            </a>

            <form method="post" action="{{route('coupons.destroy')}}" class="d-inline" id="deleteForm-{{ $value->id }}">
                @csrf
                <input type="hidden" value="{{$value->id}}" name="hidden_id">
                <x-admin.button variant="text-danger" size="sm" shape="icon" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $value->id }}')">
                    <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
            </form>
        </div>
    </td>
</tr>
