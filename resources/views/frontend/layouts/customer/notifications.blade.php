@extends('frontend.layouts.master')
@section('title','Notifications')
@section('content')
<section class="customer-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-3">
                <div class="customer-sidebar">
                    @include('frontend.layouts.customer.sidebar')
                </div>
            </div>
            <div class="col-sm-9">
                <div class="customer-content">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="account-title mb-0">Notifications</h5>
                        <form method="post" action="{{ route('customer.notifications.readAll') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark all as read</button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            @if(($notifications->count() ?? 0) === 0)
                                <div class="text-muted">No notifications</div>
                            @else
                                <div class="list-group">
                                    @foreach($notifications as $n)
                                        @php
                                            $data = is_array($n->data) ? $n->data : (array) $n->data;
                                            $title = $data['title'] ?? 'Notification';
                                            $body = $data['body'] ?? '';
                                            $isUnread = $n->read_at === null;
                                        @endphp
                                        <div class="list-group-item d-flex flex-wrap align-items-start justify-content-between gap-2 {{ $isUnread ? 'bg-light' : '' }}">
                                            <div style="min-width: 0;">
                                                <div class="fw-semibold" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $title }}</div>
                                                <div class="text-muted" style="white-space: pre-wrap;">{{ $body }}</div>
                                                <div class="text-muted small mt-1">{{ $n->created_at ? $n->created_at->format('Y-m-d H:i') : '' }}</div>
                                            </div>
                                            <div>
                                                @if($isUnread)
                                                    <form method="post" action="{{ route('customer.notifications.read', $n->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary">Mark read</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-3">
                                    {{ $notifications->links() }}
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
