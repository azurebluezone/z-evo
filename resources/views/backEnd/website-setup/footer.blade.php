@extends('backEnd.layouts.app')
@section('title','Website Footer')

@section('content')

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Website Footer</h4>
        <p class="text-muted mb-0 small">
            The content and social links shown in the storefront footer.
        </p>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>The footer was not saved.</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="ri ri-layout-bottom-line me-2"></i>Footer content</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.website-setup.footer.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-12">
                    <x-admin.textarea name="footer_about_text" label="Footer about text"
                                      :value="$settings->footer_about_text ?? ''"
                                      rows="4" maxlength="400"
                                      help="Short shop description shown beside the logo. Maximum 400 characters - long text is clipped so the homepage layout stays intact." />
                </div>

                <div class="col-12 col-lg-6">
                    <x-admin.input name="footer_payment_image" label="Footer payment image" type="file"
                                   accept="image/*"
                                   help="PNG, JPG, GIF or WebP up to 2 MB." />

                    @php
                        $payImg = (string) ($settings->footer_payment_image ?? '');
                        $payImg = preg_replace('~^public/~', '', $payImg);
                        $payImg = ltrim($payImg, '/');
                    @endphp
                    @if(!empty($payImg) && \Illuminate\Support\Facades\File::exists(public_path($payImg)))
                        <div class="mt-2">
                            <img src="{{ asset($payImg) }}" alt="Current footer payment image" class="img-fluid" style="height:42px">
                        </div>
                    @endif
                </div>

                <div class="col-12 col-lg-6">
                    <x-admin.input name="copyright" label="Copyright"
                                   :value="$settings->copyright ?? ''"
                                   placeholder="© {{ date('Y') }} Your shop" />
                </div>
            </div>

            <h6 class="mt-4 mb-3"><i class="ri ri-contacts-book-line me-1"></i>Footer contact info</h6>
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <x-admin.input name="contact_address" label="Location"
                                   :value="$contact->address ?? ''"
                                   placeholder="House 666, Road 5555, Barishal"
                                   help="Shown in the footer's Contact Us list." />
                </div>
                <div class="col-12 col-lg-4">
                    <x-admin.input name="contact_phone" label="Phone number"
                                   :value="$contact->phone ?? ''"
                                   placeholder="+ (001) 001 001"
                                   help="Becomes the footer's tel: link." />
                </div>
                <div class="col-12 col-lg-4">
                    <x-admin.input name="contact_email" label="Email" type="email"
                                   :value="$contact->email ?? ''"
                                   placeholder="info@example.com"
                                   help="Becomes the footer's mailto: link." />
                </div>
            </div>

            <div class="mt-3">
                <x-admin.button variant="primary" type="submit">
                    <i class="ri ri-save-line me-1"></i>Save footer content
                </x-admin.button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="mb-0"><i class="ri ri-share-line me-2"></i>Social icons</h5>
            <p class="text-muted small mb-0">Shown in the footer, in the order listed here.</p>
        </div>
        <span class="badge bg-label-secondary">{{ $socials->count() }} total</span>
    </div>

    <div class="card-body">
        <div class="border rounded p-3 mb-4">
            <h6 class="mb-3">Add a social icon</h6>

            <form action="{{ route('socialmedias.store') }}" method="POST">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <x-admin.input name="title" label="Name" placeholder="Facebook" required />
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="social_preset_icon" class="form-label">Popular icon</label>
                        <select id="social_preset_icon" class="form-select" data-social-preset>
                            <option value="">Custom (type the class below)</option>
                            <option value="fab fa-facebook-f|#1877f2">Facebook</option>
                            <option value="fab fa-instagram|#E4405F">Instagram</option>
                            <option value="fab fa-x-twitter|#000000">X (Twitter)</option>
                            <option value="fab fa-youtube|#FF0000">YouTube</option>
                            <option value="fab fa-tiktok|#010101">TikTok</option>
                            <option value="fab fa-whatsapp|#25D366">WhatsApp</option>
                            <option value="fab fa-linkedin-in|#0A66C2">LinkedIn</option>
                            <option value="fab fa-pinterest|#BD081C">Pinterest</option>
                            <option value="fab fa-telegram|#26A5E4">Telegram</option>
                        </select>
                        <div class="form-text">Fills the icon class and brand colour below. Leave on Custom for anything else.</div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <x-admin.input name="icon" label="Icon class" placeholder="fab fa-facebook"
                                       help="A Font Awesome class, e.g. fab fa-facebook." required />
                    </div>
                    <div class="col-12 col-lg-4">
                        <x-admin.input name="link" label="Link" type="url"
                                       placeholder="https://facebook.com/yourshop" required />
                    </div>

                    <div class="col-12 col-lg-3">
                        <label for="social_new_color" class="form-label">Brand colour</label>
                        <input type="color" name="color" id="social_new_color" class="form-control form-control-color"
                               value="{{ old('color', '#1877f2') }}">
                    </div>

                    <div class="col-12 col-lg-5 d-flex align-items-end">
                        <x-admin.switch name="status" label="Show in footer" :checked="true" />
                    </div>

                    <div class="col-12 col-lg-4 d-flex align-items-end justify-content-lg-end">
                        <x-admin.button variant="primary" type="submit">
                            <i class="ri ri-add-line me-1"></i>Add icon
                        </x-admin.button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:56px;">SL</th>
                        <th>Name</th>
                        <th>Icon</th>
                        <th>Link</th>
                        <th>Status</th>
                        <th class="text-end" style="width:190px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($socials as $social)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-medium">{{ $social->title }}</td>
                            <td>
                                <i class="{{ $social->icon }} me-1" aria-hidden="true"></i>
                                <code class="small">{{ $social->icon }}</code>
                            </td>
                            <td>
                                @if($social->link)
                                    <a href="{{ $social->link }}" target="_blank" rel="noopener">
                                        {{ \Illuminate\Support\Str::limit($social->link, 42) }}
                                    </a>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </td>
                            <td>
                                @if($social->status == 1)
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <x-admin.button variant="outline-secondary" size="sm" type="button"
                                                    class="js-social-edit"
                                                    data-bs-toggle="modal" data-bs-target="#socialEditModal"
                                                    data-id="{{ $social->id }}"
                                                    data-title="{{ $social->title }}"
                                                    data-icon="{{ $social->icon }}"
                                                    data-link="{{ $social->link }}"
                                                    data-color="{{ $social->color ?: '#111827' }}"
                                                    data-status="{{ (int) ($social->status == 1) }}">
                                        Edit
                                    </x-admin.button>

                                    @if($social->status == 1)
                                        <form method="post" action="{{ route('socialmedias.inactive') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                            <input type="hidden" name="hidden_id" value="{{ $social->id }}">
                                            <x-admin.button variant="outline-warning" size="sm"
                                                            class="change-confirm" type="button"
                                                            data-confirm-title="Hide this icon?"
                                                            data-confirm-text="It stays saved but disappears from the footer.">
                                                Hide
                                            </x-admin.button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('socialmedias.active') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                            <input type="hidden" name="hidden_id" value="{{ $social->id }}">
                                            <x-admin.button variant="outline-success" size="sm"
                                                            class="change-confirm" type="button">
                                                Show
                                            </x-admin.button>
                                        </form>
                                    @endif

                                    <form method="post" action="{{ route('socialmedias.destroy') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                        <input type="hidden" name="hidden_id" value="{{ $social->id }}">
                                        <x-admin.button variant="outline-danger" size="sm"
                                                        class="delete-confirm" type="submit"
                                                        data-confirm-title="Delete this social icon?"
                                                        data-confirm-text="The link is removed from the footer immediately.">
                                            Delete
                                        </x-admin.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ri ri-share-circle-line icon-32px text-muted d-block mb-2" aria-hidden="true"></i>
                                <span class="text-muted">No social icons yet. Add the first one above.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- One edit form, filled from the row's data-* attributes. A <details> block
     inside a table cell pushed the row apart and made the table unreadable. --}}
<div class="modal fade" id="socialEditModal" tabindex="-1" aria-labelledby="socialEditModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('socialmedias.update') }}" id="socialEditForm">
                @csrf
                <input type="hidden" name="id" id="social_edit_id">
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="socialEditModalLabel">Edit social icon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="social_edit_title" class="form-label">Name</label>
                            <input type="text" name="title" id="social_edit_title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label for="social_edit_icon" class="form-label">Icon class</label>
                            <input type="text" name="icon" id="social_edit_icon" class="form-control" required>
                            <div class="form-text">A Font Awesome class, e.g. fab fa-facebook.</div>
                        </div>
                        <div class="col-12">
                            <label for="social_edit_link" class="form-label">Link</label>
                            <input type="url" name="link" id="social_edit_link" class="form-control" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="social_edit_color" class="form-label">Brand colour</label>
                            <input type="color" name="color" id="social_edit_color" class="form-control form-control-color">
                        </div>
                        <div class="col-12 col-sm-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       name="status" id="social_edit_status" value="1">
                                <label class="form-check-label" for="social_edit_status">Show in footer</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
                    <x-admin.button variant="primary" type="submit">Save icon</x-admin.button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        'use strict';

        // Popular-icon preset: fills the icon class and brand colour so the
        // admin never has to look up a Font Awesome class by hand.
        var preset = document.querySelector('[data-social-preset]');
        if (preset) {
            preset.addEventListener('change', function () {
                var parts = (preset.value || '').split('|');
                if (!parts[0]) { return; }
                var iconInput = document.getElementById('field-icon');
                var colorInput = document.getElementById('social_new_color');
                if (iconInput) { iconInput.value = parts[0]; }
                if (colorInput && parts[1]) { colorInput.value = parts[1]; }
            });
        }

        var form = document.getElementById('socialEditForm');
        if (!form) { return; }

        document.querySelectorAll('.js-social-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                form.elements['id'].value = button.dataset.id || '';
                form.elements['title'].value = button.dataset.title || '';
                form.elements['icon'].value = button.dataset.icon || '';
                form.elements['link'].value = button.dataset.link || '';
                form.elements['color'].value = button.dataset.color || '#111827';
                form.elements['status'].checked = button.dataset.status === '1';
            });
        });
    })();
</script>
@endsection
