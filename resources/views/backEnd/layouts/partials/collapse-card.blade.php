<div class="card rounded-0 mb-4">
  <div class="card-header border-bottom d-flex align-items-center justify-content-between">
    <h5 class="card-title mb-0 fw-semibold">{{ $title }}</h5>
    <button
      class="btn btn-outline-secondary btn-sm btn-square"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#{{ $collapseId }}"
      aria-expanded="false"
      aria-controls="{{ $collapseId }}">
      {{ $toggleText ?? 'Show' }}
    </button>
  </div>
  <div class="collapse" id="{{ $collapseId }}">
    <div class="card-body mt-4">
      {{ $slot }}
    </div>
  </div>
</div>
