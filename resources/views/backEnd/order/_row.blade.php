{{--
    backEnd/order/_row - one row of the orders table.

    WHY A PARTIAL
    ------------
    This markup is needed twice: once in the ordinary blade table, and once per
    row in the DataTables JSON endpoint. Before it was extracted, the two
    copies could drift - an action button added to one and not the other, which
    is exactly the kind of bug that only shows up once the table is ajax driven.

    The row therefore says what it needs and nothing else:

        $value        App\Models\Order, with shipping and status already loaded
        $orderstatus  the status list, for the bulk-status <select>

    Props are read with ?? so a caller that only has an order still renders
    (the select simply has no options).

    ---------------------------------------------------------------------------
    THE COURIER COLUMN
    ---------------------------------------------------------------------------
    Risk was already stored per order - order_save() calls
    CourierFraudService::checkAndAttachToOrder(), which writes risk_label,
    risk_score_percent, risk_last_checked_at and risk_source onto the row. Only
    a bare "High Risk" badge was ever displayed, so the number the admin
    actually wants - "how often does this buyer receive parcels?" - was in the
    database and not on screen.

    risk_score_percent is the DELIVERY SUCCESS RATE: the share of this phone
    number's past consignments that were delivered rather than cancelled. It is
    null when the number has no courier history at all, which is the normal case
    for a first-time customer and must not read as "0% success".

    Bands, chosen so a glance sorts the list:
        >= 90  good    green
        >= 60  fair    amber
        <  60  poor    red
    A phone with no history shows a neutral "new" chip instead of a percentage.
--}}

@php
    $rawPhone = (string) ($value->shipping?->phone ?? '');
    $currentStatusName = $value->status?->name ?? '';

    $badgeClass = 'bg-label-primary';
    if (stripos($currentStatusName, 'complete') !== false || stripos($currentStatusName, 'deliver') !== false) $badgeClass = 'bg-label-success';
    elseif (stripos($currentStatusName, 'process') !== false) $badgeClass = 'bg-label-warning';
    elseif (stripos($currentStatusName, 'ship') !== false) $badgeClass = 'bg-label-info';
    elseif (stripos($currentStatusName, 'cancel') !== false || stripos($currentStatusName, 'return') !== false) $badgeClass = 'bg-label-danger';

    // ---- Courier success rate ------------------------------------------------
    // risk_score_percent is a nullable tinyint. null means "we checked and this
    // phone has no courier history" - a fact about the CUSTOMER, not a score,
    // so it must not be rendered as 0%.
    $courierRate   = ($value->risk_score_percent !== null) ? (int) $value->risk_score_percent : null;
    $courierLabel  = (string) ($value->risk_label ?? '');
    $courierSource = (string) ($value->risk_source ?? '');
    $courierAt     = $value->risk_last_checked_at ?? null;

    if ($courierRate === null) {
        $courierChipClass = 'bg-label-secondary';
        $courierChipText  = 'New';
        $courierTitle     = $courierLabel === 'unknown'
            ? 'No courier delivery history for this phone number yet.'
            : 'Not scored yet.';
    } elseif ($courierRate >= 90) {
        $courierChipClass = 'bg-label-success';
        $courierChipText  = $courierRate . '%';
        $courierTitle     = 'Delivered successfully on ' . $courierRate . '% of past parcels.';
    } elseif ($courierRate >= 60) {
        $courierChipClass = 'bg-label-warning';
        $courierChipText  = $courierRate . '%';
        $courierTitle     = 'Delivered on ' . $courierRate . '% of past parcels - some returns.';
    } else {
        $courierChipClass = 'bg-label-danger';
        $courierChipText  = $courierRate . '%';
        $courierTitle     = 'Only ' . $courierRate . '% of past parcels were delivered. Confirm with the customer before shipping.';
    }

    if ($courierAt !== null) {
        $courierTitle .= ' Checked ' . $courierAt->format('d M Y, h:i A') . '.';
    }
    if ($courierSource !== '' && $courierSource !== 'internal') {
        $courierTitle .= ' Source: ' . $courierSource . '.';
    }
@endphp
          <tr class="@if(isset($value->risk_label) && $value->risk_label === 'high_risk') table-danger @endif">
            <td class="text-center"><input type="checkbox" class="checkbox" value="{{$value->id}}"></td>
            <td><a href="{{route('admin.order.invoice',['invoice_id'=>$value->invoice_id])}}" class="fw-medium">#{{$value->invoice_id}}</a></td>
            <td>{{date('d M Y', strtotime($value->created_at))}}</td>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://ui-avatars.com/api/?name={{urlencode($value->shipping?$value->shipping->name:'Customer')}}&background=random&color=fff" class="rounded-circle me-2" width="32" height="32">
                <span>{{$value->shipping?$value->shipping->name:''}}</span>
              </div>
            </td>
            <td>
              <span class="d-block">{{$rawPhone}}</span>

              {{-- Courier success rate. The tooltip carries the detail (bands,
                   when it was checked, which provider) so the cell itself stays
                   narrow enough for a table. --}}
              <span class="badge {{ $courierChipClass }}"
                    title="{{ $courierTitle }}"
                    data-bs-toggle="tooltip">
                <i class="icon-base ri-bar-chart-line icon-16px me-1"></i>{{ $courierChipText }}
              </span>

              @if(isset($value->risk_label) && $value->risk_label === 'high_risk')
                <span class="badge bg-danger">High Risk</span>
              @endif
            </td>
            <td>৳{{$value->amount}}</td>
            <td>
              @php
                $statusColor = '#696cff'; $statusBg = 'rgba(105,108,255,.16)'; // default primary
                if(stripos($currentStatusName,'complete')!==false||stripos($currentStatusName,'deliver')!==false) { $statusColor='#71dd37'; $statusBg='rgba(113,221,55,.16)'; }
                elseif(stripos($currentStatusName,'process')!==false||stripos($currentStatusName,'way')!==false) { $statusColor='#ffab00'; $statusBg='rgba(255,171,0,.16)'; }
                elseif(stripos($currentStatusName,'ship')!==false) { $statusColor='#03c3ec'; $statusBg='rgba(3,195,236,.16)'; }
                elseif(stripos($currentStatusName,'cancel')!==false||stripos($currentStatusName,'return')!==false||stripos($currentStatusName,'unpaid')!==false) { $statusColor='#ff3e1d'; $statusBg='rgba(255,62,29,.16)'; }
                elseif(stripos($currentStatusName,'hold')!==false) { $statusColor='#ffab00'; $statusBg='rgba(255,171,0,.16)'; }
              @endphp
              <form action="{{route('admin.order.status')}}" method="POST" class="d-inline row-status-form">
                  @csrf
                  <input type="hidden" name="order_ids[]" value="{{$value->id}}">
                  <select name="order_status" onchange="this.form.submit()" class="row-status-select" style="background-color:{{$statusBg}};color:{{$statusColor}};border:1px solid {{$statusColor}}44;border-radius:30px;padding:3px 10px 3px 10px;font-size:0.775rem;font-weight:600;cursor:pointer;appearance:none;-webkit-appearance:none;outline:none;min-width:90px;text-align:center;">
                    @if(isset($orderstatus))
                      @foreach($orderstatus as $st)
                        <option class="text-dark bg-white" value="{{$st->id}}" @if(isset($value->status) && $value->status->id == $st->id) selected @endif>{{$st->name}}</option>
                      @endforeach
                    @endif
                  </select>
              </form>
            </td>
            <td>
              <div class="d-flex align-items-center gap-1">
                  <a href="{{route('admin.order.invoice',['invoice_id'=>$value->invoice_id])}}" class="btn btn-sm btn-icon btn-text-secondary waves-effect" title="Invoice">
                    <i class="icon-base ri ri-printer-line icon-22px"></i>
                  </a>
                  <a href="{{route('admin.order.edit',['invoice_id'=>$value->invoice_id])}}" class="btn btn-sm btn-icon btn-text-secondary waves-effect" title="Edit">
                    <i class="icon-base ri ri-edit-line icon-22px"></i>
                  </a>

                  {{-- Send to courier. Only offered when the order has not been
                       booked yet, so the admin cannot create a second billable
                       consignment from this screen.

                       courierShipments is a hasMany, so first() is the newest
                       row; any row carrying a consignment_id means the parcel
                       already exists upstream. --}}
                  @php $bookedShipment = $value->courierShipments?->firstWhere('consignment_id', '!=', null); @endphp
                  @if(empty($bookedShipment) || empty($bookedShipment->consignment_id))
                    <a href="{{route('admin.order.courier.show',['provider'=>'steadfast','order'=>$value->id])}}" class="btn btn-sm btn-icon btn-text-primary waves-effect" title="Send to courier">
                      <i class="icon-base ri ri-truck-line icon-22px"></i>
                    </a>
                  @endif

                  <form method="post" action="{{route('admin.order.destroy')}}" class="d-inline" id="deleteForm-{{$value->id}}">
                      @csrf
                      <input type="hidden" value="{{$value->id}}" name="id">
                      <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" type="button" onclick="triggerDeleteModal('deleteForm-{{$value->id}}')" title="Delete">
                        <i class="icon-base ri ri-delete-bin-line icon-22px"></i>
                      </x-admin.button>
                  </form>
              </div>
            </td>
          </tr>