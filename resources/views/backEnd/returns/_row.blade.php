{{--
    backEnd/returns/_row - one row of the returns grid.

    Extracted from returns/index.blade.php by tools/admin_grid_page.py, verbatim.
    The blade page and the DataTables endpoint both render it, so a change to
    this row reaches both - the thing that goes wrong when the two copies live
    in separate files.

        $value       the order this row is about
        $returnRows  order id => the return record, as returned by the page query
        $index       the row number (unused on this screen, kept for uniformity)
--}}

<tr>
  @php($r = $returnRows[$value->id] ?? null)
  <td>#{{ $value->id }}</td>
  <td>{{ $value->invoice_id }}</td>
  <td>{{ optional($value->customer)->name }}</td>
  <td>{{ optional($value->shipping)->phone }}</td>
  <td class="text-end">{{ $value->amount }}</td>
  <td>
    @if($r)
      <span class="badge bg-label-warning">{{ $r->status }}</span>
      @if($r->restocked)
        <span class="badge bg-label-success">Restocked</span>
      @endif
    @else
      <span class="badge bg-label-secondary">None</span>
    @endif
  </td>
  <td class="text-end">
    @if(!$r || !$r->restocked)
      <form method="POST" action="{{ route('admin.returns.approve', $value->id) }}" class="d-inline">
        @csrf
        <input type="hidden" name="reason" value="Admin approved" />
        <div class="d-flex flex-wrap justify-content-end gap-2">
          <select name="return_mode" class="form-select form-select-sm" style="max-width: 150px;">
            <option value="customer_return" selected>Customer Return</option>
            <option value="rto">RTO</option>
          </select>

          <select name="restock_condition" class="form-select form-select-sm" style="max-width: 130px;">
            <option value="sellable" selected>Sellable</option>
            <option value="damaged">Damaged</option>
          </select>

          <div class="form-check form-check-inline m-0 align-self-center">
            <input class="form-check-input" type="checkbox" name="refund_required" value="1" checked>
            <label class="form-check-label">Refund</label>
          </div>

          <input type="number" step="0.01" name="refund_amount" value="{{ $value->amount }}" class="form-control form-control-sm" style="max-width: 120px;" placeholder="Refund amount" />

          <input type="text" name="refund_note" value="" class="form-control form-control-sm" style="max-width: 160px;" placeholder="Refund note" />

          <x-admin.button variant="primary" size="sm" ripple="true" type="submit">Approve Return</x-admin.button>
        </div>
      </form>
    @else
      <span class="badge bg-label-success">Done</span>
    @endif
  </td>
</tr>
