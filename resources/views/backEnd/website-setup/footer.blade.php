@extends('backEnd.layouts.app')
@section('title','Website Footer')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-0">Website Footer</h4>
                </div>

                <form action="{{ route('admin.website-setup.footer.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label class="form-label">Footer About Text</label>
                            <textarea name="footer_about_text" class="form-control" rows="4">{{ old('footer_about_text', $settings->footer_about_text ?? '') }}</textarea>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Footer Payment Image</label>
                            <input type="file" name="footer_payment_image" class="form-control" accept="image/*">
                            @php
                                $payImg = (string) ($settings->footer_payment_image ?? '');
                                $payImg = preg_replace('~^public/~', '', $payImg);
                                $payImg = ltrim($payImg, '/');
                            @endphp
                            @if(!empty($payImg) && \Illuminate\Support\Facades\File::exists(public_path($payImg)))
                                <div class="mt-2">
                                    <img src="{{ asset($payImg) }}" alt="payment" style="height:42px">
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Copyright</label>
                            <input type="text" name="copyright" class="form-control" value="{{ old('copyright', $settings->copyright ?? '') }}">
                        </div>
                    </div>

                    <div class="mt-2">
                        <x-admin.button variant="primary" type="submit">Save</x-admin.button>
                    </div>
                </form>

                <div class="mt-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Footer Social Icons</h5>
                    </div>
                    <div class="card mt-2 mb-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="mb-2">Add New Social Icon</h6>
                                </div>
                                <div class="col-12">
                                    <form action="{{ route('socialmedias.store') }}" method="POST" class="row g-2">
                                        @csrf
                                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                        <div class="col-lg-3">
                                            <input type="text" name="title" class="form-control" placeholder="Name">
                                        </div>
                                        <div class="col-lg-3">
                                            <input type="text" name="icon" class="form-control" placeholder="Icon class e.g. fab fa-facebook">
                                        </div>
                                        <div class="col-lg-4">
                                            <input type="text" name="link" class="form-control" placeholder="Link">
                                        </div>
                                        <div class="col-lg-1">
                                            <input type="color" name="color" class="form-control form-control-color" value="#111827">
                                        </div>
                                        <div class="col-lg-1 d-flex align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="status" value="1" checked id="social_status_new">
                                                <label class="form-check-label" for="social_status_new">Active</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <x-admin.button variant="primary" size="sm" type="submit">Add</x-admin.button>
                                        </div>
                                    </form>
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="mb-2">Manage Social Icons</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width:60px;">SL</th>
                                                    <th>Name</th>
                                                    <th>Icon</th>
                                                    <th>Link</th>
                                                    <th>Status</th>
                                                    <th style="width:220px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $socialRows = $socials ?? collect();
                                                @endphp
                                                @forelse($socialRows as $value)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $value->title }}</td>
                                                        <td><i class="{{ $value->icon }}"></i> <span class="text-muted">{{ $value->icon }}</span></td>
                                                        <td>
                                                            <a href="{{ $value->link ?? '#' }}" target="_blank" rel="noopener">{{ $value->link }}</a>
                                                        </td>
                                                        <td>
                                                            @if($value->status==1)
                                                                <span class="badge bg-soft-success text-success">Active</span>
                                                            @else
                                                                <span class="badge bg-soft-danger text-danger">Inactive</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                @if($value->status == 1)
                                                                    <form method="post" action="{{ route('socialmedias.inactive') }}" class="d-inline">
                                                                        @csrf
                                                                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                                                        <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                                                        <x-admin.button variant="secondary" size="sm" class="change-confirm" type="submit">Inactive</x-admin.button>
                                                                    </form>
                                                                @else
                                                                    <form method="post" action="{{ route('socialmedias.active') }}" class="d-inline">
                                                                        @csrf
                                                                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                                                        <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                                                        <x-admin.button variant="success" size="sm" class="change-confirm" type="submit">Active</x-admin.button>
                                                                    </form>
                                                                @endif

                                                                <details>
                                                                    <summary class="btn btn-sm btn-primary">Edit</summary>
                                                                    <div class="mt-2 p-2 border rounded">
                                                                        <form method="post" action="{{ route('socialmedias.update') }}" class="row g-2">
                                                                            @csrf
                                                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                                                            <input type="hidden" name="id" value="{{ $value->id }}">
                                                                            <div class="col-lg-3">
                                                                                <input type="text" name="title" class="form-control" value="{{ $value->title }}">
                                                                            </div>
                                                                            <div class="col-lg-3">
                                                                                <input type="text" name="icon" class="form-control" value="{{ $value->icon }}">
                                                                            </div>
                                                                            <div class="col-lg-4">
                                                                                <input type="text" name="link" class="form-control" value="{{ $value->link }}">
                                                                            </div>
                                                                            <div class="col-lg-1">
                                                                                <input type="color" name="color" class="form-control form-control-color" value="{{ $value->color ?? '#111827' }}">
                                                                            </div>
                                                                            <div class="col-lg-1 d-flex align-items-center">
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input" type="checkbox" name="status" value="1" {{ $value->status==1 ? 'checked' : '' }} id="social_status_{{ $value->id }}">
                                                                                    <label class="form-check-label" for="social_status_{{ $value->id }}">Active</label>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-12">
                                                                                <x-admin.button variant="success" size="sm" type="submit">Update</x-admin.button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </details>

                                                                <form method="post" action="{{ route('socialmedias.destroy') }}" class="d-inline">
                                                                    @csrf
                                                                    <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                                                    <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                                                                    <x-admin.button variant="danger" size="sm" class="delete-confirm" type="submit">Delete</x-admin.button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No social icons found.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
