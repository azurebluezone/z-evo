{{--
    backEnd/banner/_row - one row of the banner grid.

    Extracted from banner/index.blade.php by tools/admin_grid_page.py, verbatim.
    The blade page and the DataTables endpoint both render it, so a change to
    this row reaches both - the thing that goes wrong when the two copies live
    in separate files.

        $value  the model for this row
        $index  the row number to print in the first cell
--}}

<tr>
    <td>{{ $index }}</td>
    <td class="fw-medium">{{ $value->category ? $value->category->name : '' }}</td>
    <td>
                @php
                    $mediaType = $value->media_type ?? 'image';

                    $bannerImagePath = preg_replace('~^public/~', '', (string) ($value->image ?? ''));
                    $bannerImagePath = ltrim($bannerImagePath, '/');

                    $videoRaw = (string) ($value->video ?? '');
                    $videoIsUrl = preg_match('~^https?://~i', $videoRaw) === 1;
                    $videoPath = $videoIsUrl ? $videoRaw : ltrim(preg_replace('~^public/~', '', $videoRaw), '/');
                    $hasVideo = !empty($videoRaw) && ($videoIsUrl || \Illuminate\Support\Facades\File::exists(public_path($videoPath)));
                @endphp

                @if($mediaType === 'video' && $hasVideo)
                    <video muted playsinline preload="metadata" style="display:block;width:140px;height:60px;object-fit:cover;border-radius:6px;">
                        <source src="{{ $videoIsUrl ? $videoRaw : asset($videoPath) }}" type="video/mp4">
                    </video>
                @elseif(!empty($bannerImagePath))
                    <img src="{{ asset($bannerImagePath) }}" class="backend-image" alt="">
                @endif
            </td>
            <td>
                @if((int) $value->status === 1)
                    <form method="post" action="{{ route('banners.inactive') }}" class="d-inline">
                        @csrf
                        <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                        <label class="switch" title="Active">
                            <input type="checkbox" class="switch-input js-auto-submit" checked>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                        </label>
                    </form>
                @else
                    <form method="post" action="{{ route('banners.active') }}" class="d-inline">
                        @csrf
                        <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                        <label class="switch" title="Inactive">
                            <input type="checkbox" class="switch-input js-auto-submit">
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                        </label>
                    </form>
                @endif
            </td>
            <td class="text-end">
                <div class="d-inline-flex justify-content-end align-items-center gap-1">
                    <a href="{{ route('banners.edit', $value->id) }}" class="btn btn-sm btn-icon btn-text-secondary btn-square waves-effect" title="Edit">
                        <i class="icon-base ri ri-edit-line"></i>
                    </a>
                    <form method="post" action="{{ route('banners.destroy') }}" class="d-inline" id="deleteForm-{{ $value->id }}">
                        @csrf
                        <input type="hidden" value="{{ $value->id }}" name="hidden_id">
                        <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $value->id }}')">
                            <i class="icon-base ri ri-delete-bin-line"></i>
                        </x-admin.button>
                    </form>
                </div>
            </td>
        </tr>
