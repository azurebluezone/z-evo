{{-- Shared rows for the live activity table. Kept as a partial so the initial
     render and every live-refresh response produce byte-identical markup. --}}
@forelse($rows as $row)
    <tr data-visitor-id="{{ $row['visitor_id'] }}" style="cursor:pointer"
        class="{{ !$row['active'] ? 'opacity-75' : '' }} {{ $row['status'] === 0 ? 'table-danger' : '' }}">
        <td>
            <div class="fw-medium font-monospace small">{{ $row['short_uuid'] }}</div>
            <div class="text-body-secondary" style="font-size:.65rem">
                {{ $row['event_count'] }} events
            </div>
        </td>
        <td>
            <div class="small font-monospace">{{ $row['ip'] ?? '—' }}</div>
            <div class="text-body-secondary" style="font-size:.65rem">{{ $row['isp'] ?? 'Unknown ISP' }}</div>
        </td>
        <td class="small">
            @if($row['location'])
                {{ $row['location'] }}
            @else
                <span class="text-body-secondary">Unresolved</span>
            @endif
        </td>
        <td class="small">{{ $row['device'] ?: '—' }}</td>
        <td>
            <span class="badge"
                  style="background:{{ $channelPalette[$row['channel']] ?? '#8993a4' }}">
                {{ $row['channel_label'] ?? 'Unknown' }}
            </span>
        </td>
        <td>
            <span class="badge bg-label-primary">{{ $row['event'] }}</span>
            @if($row['dedup_status'] === 'primary')
                <span class="badge bg-success" title="Matched on both browser and server paths">dedup</span>
            @endif
        </td>
        <td class="small text-body-secondary text-truncate" style="max-width:180px">
            {{ $row['page_path'] ?? '—' }}
        </td>
        <td>
            @if($row['tracking_method'] === 'browser')
                <span class="badge bg-label-info">Browser</span>
            @elseif($row['tracking_method'] === 'server')
                <span class="badge bg-label-success">Server</span>
            @else
                <span class="badge bg-label-secondary">—</span>
            @endif
        </td>
        <td class="small text-body-secondary text-nowrap">
            {{ $row['time_ago'] }}
            <div style="font-size:.65rem">{{ $row['event_time'] }}</div>
        </td>
        <td>
            @if($row['status'] === 0)
                <span class="badge bg-danger">Blocked</span>
            @elseif($row['active'])
                <span class="badge bg-success">Active</span>
            @else
                <span class="badge bg-label-secondary">Idle</span>
            @endif
        </td>
        <td>
            <a href="{{ route('admin.visitors.list', ['visitor_id' => $row['visitor_id']]) }}"
               class="btn btn-sm btn-outline-secondary" title="Open in visitor manager"
               onclick="event.stopPropagation()">
                <i class="ri ri-external-link-line"></i>
            </a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="11" class="text-center py-4 text-body-secondary">
            No live events in this range. Open the storefront to generate traffic.
        </td>
    </tr>
@endforelse