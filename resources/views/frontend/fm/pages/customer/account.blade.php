{{--
    FM:SECTION page-customer-account -------------------------------------------
    Classic source : resources/views/frontend/layouts/customer/account.blade.php
    Controller     : Frontend\CustomerController@account()  (no variables passed)
    Sections       : none of its own - it extends frontend.fm.layouts.account
                     and fills fm-acct-body.
    Consumes       : Auth::guard('customer'), and the Customer record it loads.

    WHY THE QUERY LIVES IN THE VIEW
    The controller passes nothing. The classic view ran
        Customer::with('cust_area')->find(Auth::guard('customer')->user()->id)
    itself. Moving it to the controller would be the tidier fix, but this
    migration is deliberately template-scoped: a controller edit changes a
    behaviour other pages may rely on, and nothing needs it changed to render.
    The one thing that IS worth doing is passing the loaded record down, so the
    rail does not query the customer a second time - hence $fmAcctCustomer.

    Also dropped: the classic file pushed parsley.min.js and
    form-validation.init.js for a page that contains no form. The modern chrome
    does not ship jQuery/parsley at all, and a <script> for a library that is
    never called is pure download weight.
-------------------------------------------------------------------------- --}}

@php
    $customer = \App\Models\Customer::with('cust_area')
        ->find(Auth::guard('customer')->user()->id);
@endphp

@extends('frontend.fm.layouts.account', [
    'fmAcctTitle'   => 'My Account',
    'fmAcctSub'     => 'Your details as they are stored on your account.',
    'fmAcctEyebrow' => 'Account',
    'fmAcctCustomer' => $customer,
])

@section('fm-acct-body')
    @php
        // Resolve the avatar through MediaPath, not asset(). The customers.image
        // column defaults to 'public/uploads/default/user.png', so a raw
        // asset() call produced '/public/public/uploads/...' - a double prefix
        // and a 404. MediaPath strips a leading 'public/' and also tolerates an
        // absolute URL, which is what admin uploads can leave behind.
        $accountImage = (string) ($customer->image ?? '');
        $accountImageUrl = $accountImage !== ''
            ? \App\Support\MediaPath::url($accountImage, 'settings')
            : '';
    @endphp

    {{-- Shown only for the first 30 minutes after a guest checkout created the
         account. Renders nothing at all for a normal registered customer, and
         nothing once the window has passed - see the partial's header. --}}
    @include('frontend.fm.partials.account.temp-password', ['customer' => $customer])

    {{-- One .fm-acct__row per field instead of a <table>: the classic markup
         was a seven-row table with no styling hook of its own, and it inherited
         whatever the classic layout's .table rules happened to be. --}}
    <dl class="fm-acct__list">
        <div class="fm-acct__row">
            <dt>Name</dt>
            <dd>{{ $customer->name ?? '' }}</dd>
        </div>

        <div class="fm-acct__row">
            <dt>Phone</dt>
            <dd>{{ $customer->phone ?? '' }}</dd>
        </div>

        <div class="fm-acct__row">
            <dt>Email</dt>
            <dd>{{ $customer->email ?? '' }}</dd>
        </div>

        <div class="fm-acct__row">
            <dt>Address</dt>
            <dd>{{ $customer->address ?? '' }}</dd>
        </div>

        <div class="fm-acct__row">
            <dt>District</dt>
            <dd>{{ $customer->district ?? '' }}</dd>
        </div>

        <div class="fm-acct__row">
            {{-- cust_area is a belongsTo(District) and the customer may have no
                 area set at all, so the relation is a genuine null, not an
                 empty model. The CSS prints an em dash for an empty dd. --}}
            <dt>Area</dt>
            <dd>{{ $customer->cust_area?->area_name ?? '' }}</dd>
        </div>

        <div class="fm-acct__row">
            <dt>Image</dt>
            <dd>
                @if($accountImageUrl !== '')
                    <img src="{{ $accountImageUrl }}"
                         alt="Your profile photo"
                         class="fm-acct__thumb"
                         width="56" height="56">
                @endif
            </dd>
        </div>
    </dl>
@endsection

@section('fm-acct-actions')
    <a href="{{ route('customer.profile_edit') }}" class="btn btn-brand-primary">
        <i class="bi bi-pencil-square" aria-hidden="true"></i>
        Edit profile
    </a>
    <a href="{{ route('customer.orders') }}" class="btn btn-brand-outline">
        <i class="bi bi-bag-check" aria-hidden="true"></i>
        My orders
    </a>
@endsection

@push('script')
    {{-- Countdown for the guest temporary-password notice. The script no-ops
         when the notice is absent, so this is safe to load unconditionally. --}}
    <script src="{{ asset('frontend/js/fm-temp-password.js') }}"></script>
@endpush
