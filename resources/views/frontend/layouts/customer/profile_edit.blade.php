@extends('frontend.layouts.customer.dashboard-layout')

@section('title', 'My Profile')
@section('dashboard-title', 'My Profile')

@push('script')
    <script>
        $(document).ready(function () {
            $('.select2').select2();

            $('.district').on('change', function () {
                var id = $(this).val();
                $.ajax({
                    type: 'GET',
                    data: { id: id },
                    url: "{{ route('districts') }}",
                    success: function (res) {
                        $('.area').empty();
                        if (res) {
                            $('.area').append('<option value="">Select...</option>');
                            $.each(res, function (key, value) {
                                $('.area').append('<option value="' + key + '">' + value + '</option>');
                            });
                        }
                    }
                });
            });

            var input = document.getElementById('image');
            if (input) {
                input.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        document.getElementById('imagePreview').src = URL.createObjectURL(this.files[0]);
                    }
                });
            }
        });
    </script>
@endpush

@section('dashboard-content')
    @php
        // $profile_edit->image is stored as 'public/uploads/...'; asset() on that
        // produced '/public/public/...' and a broken <img>. MediaPath strips the
        // leading 'public/' and falls back to the bundled placeholder when the
        // file is missing.
        $avatarUrl = \App\Support\MediaPath::url($profile_edit->image ?? null, 'customer');
    @endphp

    <div class="classic-account-page">
        <div class="row gy-4">
            <div class="col-12">
                <div class="card custom--card">
                    <div class="card-body">
                        <div class="user-profile">
                            <div class="classic-avatar-wrap">
                                <img id="imagePreview"
                                     src="{{ $avatarUrl }}"
                                     alt="{{ $profile_edit->name }}"
                                     class="classic-avatar">
                                <label for="image" class="classic-avatar__edit" title="Change photo">
                                    <i class="la la-camera"></i>
                                </label>
                            </div>

                            <div class="user-profile-content">
                                <h6 class="title">{{ $profile_edit->name }}</h6>

                                <ul class="user-profile__info">
                                    <li>
                                        <span class="icon"><i class="las la-envelope"></i></span>
                                        <span class="text">{{ $profile_edit->email }}</span>
                                    </li>
                                    <li>
                                        <span class="icon"><i class="las la-phone"></i></span>
                                        <span class="text">{{ $profile_edit->phone }}</span>
                                    </li>
                                    <li>
                                        <span class="icon"><i class="las la-map-marker-alt"></i></span>
                                        <span class="text">{{ $profile_edit->address }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card custom--card">
                    <div class="card-body">
                        <h5 class="title mb-3">Update Your Profile</h5>

                        <form action="{{ route('customer.profile_update') }}"
                              method="POST"
                              enctype="multipart/form-data"
                              class="classic-form row">
                            @csrf

                            <input type="file" id="image" name="image" class="d-none" accept="image/*">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name"
                                           class="form--control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $profile_edit->name) }}" required>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="number" id="phone" name="phone"
                                           class="form--control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $profile_edit->phone) }}" required>
                                    @error('phone')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email"
                                           class="form--control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $profile_edit->email) }}" required>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address">Address *</label>
                                    <input type="text" id="address" name="address"
                                           class="form--control @error('address') is-invalid @enderror"
                                           value="{{ old('address', $profile_edit->address) }}" required>
                                    @error('address')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="district">District *</label>
                                    <select id="district" name="district"
                                            class="form--control select2 district @error('district') is-invalid @enderror" required>
                                        <option value="">Select...</option>
                                        @foreach ($districts as $district)
                                            <option value="{{ $district->district }}" @selected($profile_edit->district === $district->district)>
                                                {{ $district->district }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('district')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="area">Area *</label>
                                    <select id="area" name="area"
                                            class="form--control select2 area @error('area') is-invalid @enderror" required>
                                        <option value="">Select...</option>
                                        @foreach ($areas as $area)
                                            <option value="{{ $area->id }}" @selected((string) $profile_edit->area === (string) $area->id)>
                                                {{ $area->area_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('area')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn--base h-45">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
