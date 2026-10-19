{{--
    backEnd/partials/datatable - ONE grid for every admin list screen.

    =========================================================================
    Why this exists
    ---------------
    The admin has ~30 list screens and every one of them had been built by
    hand: a table, a Laravel pager, and - where the author remembered - a search
    box that reloaded the page. Adding real sorting, searching and server-side
    paging thirty times is how you get thirty slightly different versions of
    the same idea.

    So the grid is built once, here. A page describes its columns and where its
    JSON comes from, and gets:

        * server-side search, sorting and paging (SQL does the work)
        * a length picker, an "x of y" line and a pager
        * bootstrap tooltips re-applied after every draw
        * its CSS/JS loaded once per page, and never on a page without a table

    Usage
        @include('backEnd.partials.datatable', [
            'id'      => 'productTable',
            'url'     => route('admin.products.datatable'),
            'title'   => 'Products',
            'columns' => [
                ['label' => '', 'orderable' => false, 'searchable' => false],
                ['label' => 'Name'],
                ['label' => 'Category'],
            ],
            'params'  => ['status' => request('status')],   // forwarded to the endpoint
            'empty'   => 'No products yet.',
        ])

    Props
        id       string  element id, also the key JS binds to
        url      string  JSON endpoint (the controller method that mirrors this list)
        title    string  used in the "no results" message
        columns  array   [['label'=>string, 'orderable'=>bool, 'searchable'=>bool, 'class'=>string], ...]
        params   array   extra query values forwarded on every draw (filters)
        empty    string  message when there is genuinely nothing
        selectable bool  print a select-all checkbox in the header
        pageLength int    rows per page, default 20
        order    array   [[columnIndex, 'asc'|'desc']], default first sortable desc
        inlineEdit bool   load admin-product-inline.js for click-to-edit cells

    The ENDPOINT CONTRACT
    ---------------------
    One array entry per row, one element per column, each already-rendered
    HTML. OrderController::rowHtmlToCells() is the reference implementation of
    the row side; it also checks the cell count against the column count, so a
    row and a grid that disagree fail loudly instead of shifting a column.

    Cell values must NOT include their own <td>. DataTables writes each value
    into a cell it creates, and a value carrying its own tag becomes
    <td><td>…</td></td>, which the browser then spreads across the row.
--}}

@php
    $columns = $columns ?? [];
    $params  = $params ?? [];
    $pageLength = $pageLength ?? 20;
    $emptyMessage = $empty ?: ('No ' . strtolower($title ?? 'records') . ' yet.');
    $defaultOrder = $order ?? [[1, 'desc']];

    /*
     * The grid's configuration is serialised HERE rather than inside the
     * markup.
     *
     * @json() only parses a simple expression: handing it an array_map(...)
     * over a multi-line closure fails to compile with "Unclosed '['", which
     * looks like a blade bug and is really a directive-parsing limit. Build the
     * strings once and print them raw.
     */
    $columnSpecs = array_map(static function (array $column): array {
        return [
            'orderable'  => (bool) ($column['orderable'] ?? true),
            'searchable' => (bool) ($column['searchable'] ?? true),
            'className'  => $column['class'] ?? '',
        ];
    }, $columns);

    $columnsJson = json_encode($columnSpecs, JSON_UNESCAPED_UNICODE);
    $paramsJson  = json_encode($params, JSON_UNESCAPED_UNICODE);
    $orderJson   = json_encode($defaultOrder, JSON_UNESCAPED_UNICODE);
@endphp

{{-- Assets. @once so a page with two grids still pays for one download. --}}
@once
    @push('css')
        <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
        <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    @endpush
@endonce

<div class="card-datatable table-responsive">
    {{--
        The config travels as data-* attributes rather than as inline script so
        that admin-datatable.js stays one cacheable file for the whole admin
        instead of a different script on every list page.
    --}}
    <table id="{{ $id }}"
           class="table table-hover align-middle mb-0"
           data-fm-dt
           data-fm-dt-url="{{ $url }}"
           data-fm-dt-empty="{{ $emptyMessage }}"
           data-fm-dt-params='{!! $paramsJson !!}'
           data-fm-dt-columns='{!! $columnsJson !!}'
           data-fm-dt-order='{!! $orderJson !!}'>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th @if(!empty($column['class'])) class="{{ $column['class'] }}" @endif
                        @if(!empty($column['width'])) style="width:{{ $column['width'] }}" @endif>
                        {{--
                            The select-all box goes INSIDE the first header cell,
                            not as an extra one. The rows already carry their own
                            input.checkbox in their first cell, so adding a ninth
                            <th> would leave the header one wider than the body
                            and DataTables would read the columns off by one.

                            Its class is "checkall" because the admin's own script
                            binds that name; a grid whose rows have checkboxes but
                            whose header does not is a grid where "select all"
                            silently does nothing.
                        --}}
                        @if(!empty($selectable) && $loop->first)
                            <input type="checkbox" class="checkall" title="Select all">
                        @else
                            {{ $column['label'] ?? '' }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

@once
    @push('script')
        <script src="{{ asset('admin/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
        <script src="{{ asset('admin/assets/js/admin-datatable.js') }}"></script>
        {{-- Inline price/stock editing. Product screens ask for it by passing
             'inlineEdit' => true; the file is ~1 KB and loaded only where it
             is used. --}}
        @if(!empty($inlineEdit))
            <script src="{{ asset('admin/assets/js/admin-product-inline.js') }}"></script>
        @endif
    @endpush
@endonce