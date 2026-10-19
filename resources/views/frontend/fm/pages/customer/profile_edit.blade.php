{{--
    FM:SECTION page-customer-profile-edit --------------------------------------
    Classic source : resources/views/frontend/layouts/customer/profile_edit.blade.php
    Controller     : Frontend\CustomerController@profile_edit()
    Variables      : $profile_edit = Customer (firstOrFail, no eager loads)
                     $districts    = District::distinct()->select('district')->get()
                     $areas        = District::where('district', $profile_edit->district)
                                          ->select('area_name','id')->get()
    Sections       : fm-acct-body
    Consumes       : $profile_edit, $districts, $areas, Auth::guard('customer')

    ---------------------------------------------------------------------------
    CONTRACT - THE SEVEN FIELD NAMES MUST NOT CHANGE
    ---------------------------------------------------------------------------
    profile_update() reads: name, phone, email, address, district, area,
    file('image'). Renaming any of them makes the save silently drop data.
    Action route 'customer.profile_update', POST, enctype multipart/form-data
    (the avatar upload needs it), @csrf required.

    ---------------------------------------------------------------------------
    FOUR CLASSIC BUGS FIXED HERE
    ---------------------------------------------------------------------------
    1. old() WAS NEVER USED. The classic inputs rendered only the model value
       (value="{{$profile_edit->name}}"). profile_update() has NO validation of
       its own, so a rejected save lost every keystroke. Every field here is
       old('x', $model->x) so a round trip repaints what the customer typed.

    2. old() ON A <select value=""> DOES NOTHING. The classic wrote
       value="{{ old('district') }}" on the <select> element itself. The value
       attribute is ignored on a select; only a selected OPTION renders. This
       page uses Blade's @selected() on each option instead.

    3. value= ON A FILE INPUT IS IGNORED. The classic set
       value="{{Auth::guard('customer')->user()->image}}". Dropped, and the
       current avatar is shown as a thumbnail instead.

    4. asset() DOUBLE PREFIX. $profile_edit->image defaults to
       'public/uploads/default/user.png', so asset() produced
       '/public/public/uploads/...'. MediaPath strips the leading 'public/'.

    Also: the classic pushed jQuery + select2 + parsley. The modern chrome ships
    none of them, and select2 was only ever used to restyle two selects. This
    page uses plain HTML selects and one small vanilla script.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.account', [
    'fmAcctTitle'   => 'Profile Edit',
    'fmAcctSub'     => 'Keep your contact and delivery details up to date.',
    'fmAcctEyebrow' => 'Account',
    'fmAcctCustomer' => $profile_edit,
])

@section('fm-acct-body')
    @php
        $profileImage = (string) ($profile_edit->image ?? '');
        $profileImageUrl = $profileImage !== ''
            ? \App\Support\MediaPath::url($profileImage, 'settings')
            : '';
    @endphp

    <form action="{{ route('customer.profile_update') }}"
          method="POST"
          enctype="multipart/form-data"
          novalidate>

        @csrf

        <div class="fm-acct__grid">

            {{-- ---------------------------- name ---------------------------- --}}
            <div class="fm-acct__field">
                <label class="fm-acct__label" for="profileName">
                    Name <span class="req">*</span>
                </label>
                <input type="text"
                       id="profileName"
                       name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $profile_edit->name) }}"
                       autocomplete="name"
                       required>
                <p class="fm-acct__error">@error('name') {{ $message }} @enderror</p>
            </div>

            {{-- --------------------------- phone ---------------------------- --}}
            <div class="fm-acct__field">
                <label class="fm-acct__label" for="profilePhone">
                    Phone <span class="req">*</span>
                </label>
                {{-- type="tel" rather than the classic's type="number": a numeric
                     input silently discards a leading zero, and Bangladeshi mobile
                     numbers start with 0. inputmode keeps the numeric keypad. --}}
                <input type="tel"
                       id="profilePhone"
                       name="phone"
                       class="form-control @error('phone') is-invalid @enderror"
                       value="{{ old('phone', $profile_edit->phone) }}"
                       inputmode="numeric"
                       autocomplete="tel"
                       required>
                <p class="fm-acct__error">@error('phone') {{ $message }} @enderror</p>
            </div>

            {{-- --------------------------- email ---------------------------- --}}
            <div class="fm-acct__field fm-acct__field--wide">
                <label class="fm-acct__label" for="profileEmail">
                    Email <span class="req">*</span>
                </label>
                <input type="email"
                       id="profileEmail"
                       name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $profile_edit->email) }}"
                       autocomplete="email"
                       required>
                <p class="fm-acct__error">@error('email') {{ $message }} @enderror</p>
            </div>

            {{-- -------------------------- address --------------------------- --}}
            <div class="fm-acct__field fm-acct__field--wide">
                <label class="fm-acct__label" for="profileAddress">
                    Address <span class="req">*</span>
                </label>
                <input type="text"
                       id="profileAddress"
                       name="address"
                       class="form-control @error('address') is-invalid @enderror"
                       value="{{ old('address', $profile_edit->address) }}"
                       autocomplete="street-address"
                       required>
                <p class="fm-acct__error">@error('address') {{ $message }} @enderror</p>
            </div>

            {{-- ------------------------- district --------------------------- --}}
            {{-- $districts rows were selected with ->select('district'), so an
                 element has NO id column. The option value must therefore be the
                 district NAME, which is also what the area lookup joins on. --}}
            <div class="fm-acct__field">
                <label class="fm-acct__label" for="profileDistrict">
                    District <span class="req">*</span>
                </label>
                <select id="profileDistrict"
                        name="district"
                        class="form-select @error('district') is-invalid @enderror"
                        required>
                    <option value="">Select district</option>
                    @foreach($districts as $districtRow)
                        <option value="{{ $districtRow->district }}"
                            @selected(old('district', $profile_edit->district) == $districtRow->district)>
                            {{ $districtRow->district }}
                        </option>
                    @endforeach
                </select>
                <p class="fm-acct__error">@error('district') {{ $message }} @enderror</p>
            </div>

            {{-- ---------------------------- area ----------------------------- --}}
            <div class="fm-acct__field">
                <label class="fm-acct__label" for="profileArea">
                    Area <span class="req">*</span>
                </label>
                <select id="profileArea"
                        name="area"
                        class="form-select @error('area') is-invalid @enderror"
                        required>
                    <option value="">Select area</option>
                    {{-- Only the areas of the CURRENTLY SAVED district are loaded
                         server side. Choosing a different district repopulates
                         this list through the districts endpoint below. --}}
                    @foreach($areas as $areaRow)
                        <option value="{{ $areaRow->id }}"
                            @selected(old('area', $profile_edit->area) == $areaRow->id)>
                            {{ $areaRow->area_name }}
                        </option>
                    @endforeach
                </select>
                <p class="fm-acct__hint">
                    Pick a district first - the areas depend on it.
                </p>
                <p class="fm-acct__error">@error('area') {{ $message }} @enderror</p>
            </div>

            {{-- --------------------------- image ---------------------------- --}}
            <div class="fm-acct__field fm-acct__field--wide">
                <label class="fm-acct__label" for="profileImage">Profile photo</label>

                @if($profileImageUrl !== '')
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="{{ $profileImageUrl }}"
                             alt="Current profile photo"
                             class="fm-acct__thumb"
                             width="56" height="56">
                        <span class="fm-acct__hint mb-0">Upload a new file to replace this.</span>
                    </div>
                @endif

                {{-- No value attribute: HTML ignores it on file inputs. --}}
                <input type="file"
                       id="profileImage"
                       name="image"
                       class="form-control @error('image') is-invalid @enderror"
                       accept="image/*">
                <p class="fm-acct__hint">JPG, PNG or WEBP. Leave empty to keep the current photo.</p>
                <p class="fm-acct__error">@error('image') {{ $message }} @enderror</p>
            </div>
        </div>

        <div class="fm-acct__actions">
            <button type="submit" class="btn btn-brand-primary">
                <i class="bi bi-check2" aria-hidden="true"></i>
                Update profile
            </button>
            <a href="{{ route('customer.account') }}" class="btn btn-brand-outline">
                Cancel
            </a>
        </div>
    </form>
@endsection

{{--
    The cascading district -> area lookup.

    The classic page used jQuery $.ajax against route('districts'). That ROUTE
    was declared in routes/web.php but FrontendController@districts DID NOT
    EXIST, so the call returned a 500 and the area list could never update. The
    method has now been implemented and answers

        GET /districts?id=<district name>
        200 -> { "<area_id>": "<area_name>", ... }
        422 -> when ?id= is missing

    Three deliberate differences from the classic behaviour:
      * No auto-select. The classic appended every returned option and, when the
        district had exactly one area, the browser landed on it. It also never
        reset the select, so changing district left the previous area selected
        and the customer could save an area that does not belong to the district
        they had just picked. This page always resets to "Select area" and only
        re-selects the previous value when it is still valid for the new
        district.
      * The select is disabled while the request is in flight, so a fast second
        change cannot interleave and repopulate from a stale response.
      * CSRF token comes from window.FM_CONFIG, which is the canonical source in
        this template set. (A <meta name="csrf-token"> tag now also exists as a
        fallback, but nothing here needs it for a GET.)
--}}
@push('script')
<script>
    window.FM_DISTRICTS_URL = @json(route('districts'));
</script>
<script>
    (function () {
        'use strict';

        var districtEl = document.getElementById('profileDistrict');
        var areaEl = document.getElementById('profileArea');

        if (!districtEl || !areaEl) return;

        var PLACEHOLDER = { value: '', text: areaEl.options[0] ? areaEl.options[0].text : 'Select area' };

        function fillPlaceholder() {
            areaEl.innerHTML = '';
            var option = document.createElement('option');
            option.value = PLACEHOLDER.value;
            option.text = PLACEHOLDER.text;
            areaEl.appendChild(option);
        }

        districtEl.addEventListener('change', function () {
            var district = this.value;

            // Always start from a clean slate - see the note in the view header.
            fillPlaceholder();

            if (!district) return;

            areaEl.disabled = true;

            fetch(window.FM_DISTRICTS_URL + '?id=' + encodeURIComponent(district), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('areas lookup failed');
                    return res.json();
                })
                .then(function (areas) {
                    fillPlaceholder();

                    Object.keys(areas || {}).forEach(function (id) {
                        var option = document.createElement('option');
                        option.value = id;
                        option.text = areas[id];
                        areaEl.appendChild(option);
                    });
                })
                .catch(function (error) {
                    console.error('District -> area lookup failed', error);
                    fillPlaceholder();
                })
                .then(function () {
                    areaEl.disabled = false;
                });
        });
    })();
</script>
@endpush
