{{--
    backEnd/category/_row - one row of the category grid.

    Extracted from category/index.blade.php by tools/admin_grid_page.py, verbatim.
    The blade page and the DataTables endpoint both render it, so a change to
    this row reaches both - the thing that goes wrong when the two copies live
    in separate files.

        $value  the model for this row
--}}

<tr>
  <td>
    <div class="form-check m-0">
      <input type="checkbox" class="form-check-input checkbox rounded-0" name="ids[]" value="{{ $value->id }}" form="categoriesBulkDeleteForm">
      <input type="checkbox" class="d-none checkbox" name="ids[]" value="{{ $value->id }}" form="categoriesHomePageSetOnlyForm">
    </div>
  </td>
  <td>
    @if ($value->front_view == 1)
    <span class="badge bg-dark rounded-0 px-2 py-1">{{$value->name}}</span>
    @else
    <span class="fw-medium">{{$value->name}}</span>
    @endif
  </td>
  <td>
    @php
        $catImg = $value->image ?? null;
        $catImgPath = $catImg ? preg_replace('~^public/~', '', $catImg) : null;
        $catImgPath = $catImgPath ? ltrim($catImgPath, '/') : null;
        $fallbackImg = $generalSetting->frontend_empty_image ?? 'assets/images/empty.png';
        $fallbackImg = preg_replace('~^public/~', '', $fallbackImg);
        $fallbackImg = ltrim($fallbackImg, '/');
    @endphp
    <img src="{{ $catImgPath && file_exists(public_path($catImgPath)) ? asset($catImgPath) : asset($fallbackImg) }}"
         class="backend-image rounded-0 border"
         style="max-width: 50px; max-height: 50px; object-fit: cover;"
         alt="{{ $value->name }}"
         onerror="this.src='{{ asset('assets/images/empty.png') }}'">
  </td>
  <td>
    @if(isset($value->home_page) && (int) $value->home_page === 1)
        <form method="post" action="{{ route('categories.home-page-off') }}" class="d-inline">
            @csrf
            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
            <div class="form-check form-switch" title="Shown on home page">
                <input type="checkbox" class="form-check-input js-auto-submit rounded-0" checked onchange="this.form.submit()">
            </div>
        </form>
    @else
        <form method="post" action="{{ route('categories.home-page-on') }}" class="d-inline">
            @csrf
            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
            <div class="form-check form-switch" title="Hidden from home page">
                <input type="checkbox" class="form-check-input js-auto-submit rounded-0" onchange="this.form.submit()">
            </div>
        </form>
    @endif
  </td>
  <td>
    @if((int) $value->status === 1)
        <form method="post" action="{{ route('categories.inactive') }}" class="d-inline">
            @csrf
            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
            <div class="form-check form-switch" title="Click to deactivate">
                <input type="checkbox" class="form-check-input js-auto-submit rounded-0" checked onchange="this.form.submit()">
            </div>
        </form>
    @else
        <form method="post" action="{{ route('categories.active') }}" class="d-inline">
            @csrf
            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
            <div class="form-check form-switch" title="Click to activate">
                <input type="checkbox" class="form-check-input js-auto-submit rounded-0" onchange="this.form.submit()">
            </div>
        </form>
    @endif
  </td>
  <td class="text-end">
    <div class="d-flex gap-1 justify-content-end">
        <a href="{{ route('categories.edit', $value->id) }}" class="btn btn-icon btn-text-secondary btn-sm btn-square waves-effect" title="Edit">
            <i class="icon-base ri ri-edit-line"></i>
        </a>

        <form method="post" action="{{ route('categories.destroy') }}" class="d-inline" id="deleteForm-{{ $value->id }}">
            @csrf
            <input type="hidden" value="{{ $value->id }}" name="hidden_id">
            <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="button" title="Delete" onclick="triggerDeleteModal('deleteForm-{{ $value->id }}')">
                <i class="icon-base ri ri-delete-bin-line"></i>
            </x-admin.button>
        </form>
    </div>
  </td>
</tr>
