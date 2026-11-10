{{--
    backEnd/partials/stat-grid - the admin header every page shares.

    ONE TEMPLATE, THE PAGE'S OWN NUMBERS
    ------------------------------------
    The four cards look the same everywhere; the numbers come from
    App\Services\Admin\PageStats and describe the current module:

        Total <module> · Active/Added · Inactive/This month · Showing this page

    The layout renders it once, so no admin page can be missing it and the next
    page added inherits it. A module without a countable table (settings) still
    shows the shop overview, and its fourth card is an em dash - never a
    fabricated zero.

    Expects: $adminStats['cards'] from the view composer on backEnd.layouts.app.
--}}
@if($adminStats['show'] ?? false)
<div class="row g-4 mb-4" data-admin-stat-grid>
    @foreach($adminStats['cards'] as $card)
        {{-- Two per row on phones, four per row from the large breakpoint.
             Stacking four full-width cards pushed the page title and the
             actual content below the fold on a 390px screen. --}}
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                            <span class="avatar-initial rounded-3 bg-label-{{ $card['tone'] }}">
                                <i class="icon-base ri {{ $card['icon'] }} icon-24px" aria-hidden="true"></i>
                            </span>
                        </div>
                        @if($card['value'] === null)
                            <h4 class="mb-0"
                                data-stat="{{ \Illuminate\Support\Str::slug($card['label']) }}"
                                @if($card['hint']) title="{{ $card['hint'] }}" @endif>—</h4>
                        @else
                            <h4 class="mb-0"
                                data-stat="{{ \Illuminate\Support\Str::slug($card['label']) }}"
                                @if($card['hint']) title="{{ $card['hint'] }}" @endif>{{ number_format($card['value']) }}</h4>
                        @endif
                    </div>
                    <h6 class="mb-0 fw-normal" data-stat-label>{{ $card['label'] }}</h6>
                </div>
            </div>
        </div>
    @endforeach
</div>

@push('script')
<script>
/*
 * Server-rendered list pages (visitors, inventory, variants) do not go
 * through DataTables, so nobody refreshed card 4 for them. Count the rows the
 * server actually painted; DataTables pages have already written a number by
 * the time this runs, so they are skipped.
 */
document.addEventListener('DOMContentLoaded', function () {
    var card = document.querySelector('[data-stat="showing-this-page"]');
    if (!card || card.textContent.trim() !== '—') {
        return;
    }

    var table = Array.prototype.find.call(
        document.querySelectorAll('.content-wrapper table'),
        function (candidate) {
            return candidate.tBodies.length > 0 && candidate.tBodies[0].rows.length > 0;
        }
    );

    if (!table) {
        return;
    }

    var shown = table.tBodies[0].rows.length;
    card.textContent = shown.toLocaleString();
    card.title = shown.toLocaleString() + ' row(s) on this page';
});
</script>
@endpush
@endif
