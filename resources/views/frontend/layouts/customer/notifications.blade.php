@extends('frontend.layouts.customer.dashboard-layout')

@section('title', 'Notifications')
@section('dashboard-title', 'All Notifications')

@section('dashboard-content')
    <div class="classic-account-page">
        <div class="classic-account-head">
            <div class="classic-account-head__title">
                @if (($unreadCount ?? 0) > 0)
                    <span class="classic-account-badge">{{ $unreadCount }} unread</span>
                @else
                    <span class="classic-account-badge classic-account-badge--muted">All read</span>
                @endif
            </div>

            @if (($notifications->count() ?? 0) > 0)
                <form method="post" action="{{ route('customer.notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="classic-notice__action">Mark all as read</button>
                </form>
            @endif
        </div>

        <div class="card custom--card">
            @if (($notifications->count() ?? 0) === 0)
                <div class="classic-empty">
                    <i class="la la-bell"></i>
                    <p>Data not found</p>
                </div>
            @else
                <div class="classic-notice-list">
                    @foreach ($notifications as $notification)
                        @php
                            $data = is_array($notification->data)
                                ? $notification->data
                                : (array) $notification->data;
                            $title = $data['title'] ?? 'Notification';
                            $body = $data['body'] ?? '';
                            $isUnread = $notification->read_at === null;
                        @endphp

                        <div class="classic-notice {{ $isUnread ? 'classic-notice--unread' : '' }}">
                            <span class="classic-notice__dot" @unless ($isUnread) style="visibility: hidden" @endunless></span>

                            <div class="classic-notice__body">
                                <p class="classic-notice__title">{{ $title }}</p>

                                @if ($body !== '')
                                    <p class="classic-notice__text">{{ $body }}</p>
                                @endif

                                <p class="classic-notice__time">
                                    {{ $notification->created_at ? $notification->created_at->format('d M Y, h:i A') : '' }}
                                </p>
                            </div>

                            @if ($isUnread)
                                <form method="post" action="{{ route('customer.notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="classic-notice__action">Mark read</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($notifications->hasPages())
            <div class="classic-pagination">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
