@extends('frontend.layouts.customer.dashboard-layout')

@section('title', 'My Orders')
@section('dashboard-title', 'Orders')

@section('dashboard-content')
    @php
        /*
         * The Classic order row mapped onto the extracted
         * Order ID / Items / Payment / Order / Action design.
         * Every cell is escaped here, and the partial prints the strings as
         * HTML on purpose, so the design can carry a link, a status pill and
         * a stacked sub-line.
         */
        $orderRows = $orders->map(function ($value) {
            $status = $value->status ? $value->status->name : 'Pending';
            $invoiceUrl = route('customer.invoice', ['id' => $value->id]);

            $payment = '<span class="classic-table-cellstack">'
                . '<span class="classic-table-amount">৳' . e(number_format((float) $value->amount, 2)) . '</span>'
                . ((float) $value->discount > 0
                    ? '<span class="classic-table-sub">Discount ৳' . e(number_format((float) $value->discount, 2)) . '</span>'
                    : '')
                . '</span>';

            $action = '<a href="' . e($invoiceUrl) . '" class="invoice_btn" title="View invoice"><i class="fa-solid fa-eye"></i></a>';
            if ($value->admin_note) {
                $action .= ' <a href="' . e(route('customer.order_note', ['id' => $value->id])) . '" class="invoice_btn bg-primary" title="Admin note"><i class="fa-solid fa-pencil"></i></a>';
            }

            return [
                '<a href="' . e($invoiceUrl) . '" class="classic-table-id">#' . e((string) $value->id) . '</a>',
                e(($value->orderdetails_count ?? 0) . ' item(s)'),
                $payment,
                '<span class="classic-table-cellstack">'
                    . '<span class="classic-table-status">' . e($status) . '</span>'
                    . '<span class="classic-table-sub">' . e(optional($value->created_at)->format('d-m-y') ?? '') . '</span>'
                    . '</span>',
                $action,
            ];
        })->all();
    @endphp

    @include('frontend.layouts.customer.partials.tables.orders', [
        'rows' => $orderRows,
        'empty' => 'No order yet',
    ])
@endsection
