{{-- 
    Classic user dashboard table design: Support tickets
    Source: work/Classic u db/ticket.html (1 occurrence(s))
    Extracted columns: Ticket No. / Subject / Status / Priority / Last Reply / Action

    Design only: the caller supplies the rows. Each row is an array of
    already-escaped cell HTML in the same order as $columns. The mobile
    layout prints the column name through td[data-label].
     --}}
@php
    $columns = $columns ?? ['Ticket No.', 'Subject', 'Status', 'Priority', 'Last Reply', 'Action'];
    $rows = $rows ?? [];
    $empty = $empty ?? 'No records found';
@endphp
<div class="classic-table-wrap">
    <table class="table table--responsive--lg">
        <thead>
            <tr>
@foreach ($columns as $column)
                <th>{{ $column }}</th>
@endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
@foreach ($row as $index => $cell)
                    <td data-label="{{ $columns[$index] ?? '' }}">{!! $cell !!}</td>
@endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="classic-table-empty">{{ $empty }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
