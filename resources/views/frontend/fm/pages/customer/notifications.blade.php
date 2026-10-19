{{--
    FM:SECTION page-customer-notifications ------------------------------------
    Classic source : resources/views/frontend/layouts/customer/notifications.blade.php
    Controller     : Frontend\CustomerNotificationController@index()
    Variables      : $notifications = LengthAwarePaginator (paginate(20)->withQueryString())
                     $unreadCount   = int (unreadNotifications()->count())
    Sections       : fm-acct-body, fm-acct-actions
    Consumes       : $notifications, $unreadCount, Auth::guard('customer')

    WHAT THE CLASSIC VIEW LEFT ON THE TABLE
    The controller has always passed $unreadCount and the classic view never
    read it - it hardcoded a "Notification" heading whether the customer had one
    unread item or two hundred. This page uses it: the subline states the count,
    "Mark all as read" only appears when there is something to mark, and the
    per-row button only appears on unread rows.

    $n->data is normalised first: Laravel's database notifications store a JSON
    payload, but the classic view defensively handled a string there, so this
    keeps that guard.

    $notifications is a real paginator, so it renders the modern pagination
    partial rather than Laravel's Bootstrap default - see
    frontend/fm/partials/pagination.blade.php, which expects $paginator.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.account', [
    'fmAcctTitle'   => 'Notifications',
    'fmAcctSub'     => $unreadCount > 0
        ? $unreadCount . ' unread ' . (\Illuminate\Support\Str::plural('message', $unreadCount))
        : 'You are all caught up.',
    'fmAcctEyebrow' => 'Inbox',
])

@section('fm-acct-body')
    @forelse($notifications as $notification)
        @php
            $payload  = is_array($notification->data) ? $notification->data : (array) $notification->data;
            $notifTitle = (string) ($payload['title'] ?? 'Notification');
            $notifBody  = (string) ($payload['body'] ?? '');
            $notifUnread = $notification->read_at === null;
        @endphp

        <article class="fm-acct__card mb-2 {{ $notifUnread ? 'fm-acct__card--muted' : '' }}">
            <div class="fm-acct__card-head">
                <div>
                    <h2 class="fm-acct__card-title">{{ $notifTitle }}</h2>
                    <p class="fm-acct__card-meta">
                        {{ optional($notification->created_at)->format('d M Y, h:i A') }}
                    </p>
                </div>

                @if($notifUnread)
                    <span class="fm-acct__pill fm-acct__pill--amber">New</span>
                @endif
            </div>

            {{-- The body is admin-authored free text. Escaped with {{ }}. --}}
            @if($notifBody !== '')
                <p class="mb-0 text-sm text-muted" style="white-space: pre-line;">{{ $notifBody }}</p>
            @endif

            @if($notifUnread)
                <div class="fm-acct__card-actions">
                    {{-- Positional route parameter: the notification id is a
                         UUID string, never an integer, so it is not cast. --}}
                    <form action="{{ route('customer.notifications.read', $notification->id) }}"
                          method="POST">
                        @csrf
                        <button type="submit" class="btn btn-brand-outline btn-sm">
                            <i class="bi bi-check2" aria-hidden="true"></i>
                            Mark as read
                        </button>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <div class="fm-acct__empty">
            <i class="bi bi-bell-slash" aria-hidden="true"></i>
            <p>No notifications.</p>
        </div>
    @endforelse

    {{-- hasPages() first: Laravel renders an empty pager markup for a
         single-page result, and the classic view printed the links
         unconditionally. --}}
    @if($notifications->hasPages())
        <div class="mt-4">
            @include('frontend.fm.partials.pagination', ['paginator' => $notifications])
        </div>
    @endif
@endsection

@section('fm-acct-actions')
    {{-- Hidden entirely when nothing is unread, so there is no dead button. --}}
    @if($unreadCount > 0)
        <form action="{{ route('customer.notifications.readAll') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-brand-primary">
                <i class="bi bi-check2-all" aria-hidden="true"></i>
                Mark all as read
            </button>
        </form>
    @endif

    <a href="{{ route('customer.account') }}" class="btn btn-brand-outline">
        <i class="bi bi-person" aria-hidden="true"></i>
        Back to account
    </a>
@endsection
