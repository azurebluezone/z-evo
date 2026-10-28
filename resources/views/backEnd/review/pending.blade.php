@extends('backEnd.layouts.app')
@section('title','Pending Reviews')
@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <div>
            <h5 class="mb-0 text-uppercase fw-bold">Pending reviews</h5>
            <p class="text-muted small mb-0">Reviews waiting for approval. Nothing here is visible on the storefront yet.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <x-admin.button variant="primary" href="{{ route('reviews.create') }}">
                <i class="ri ri-add-line me-1"></i>Add review
            </x-admin.button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:56px;">SL</th>
                        <th>Customer</th>
                        <th>Review</th>
                        <th>Product</th>
                        <th>Rating</th>
                        <th class="text-end" style="width:240px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $value)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-medium">{{ $value->name }}</div>
                                <div class="text-muted small">{{ $value->email }}</div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($value->review, 70) }}</td>
                            <td>
                                @if($value->product)
                                    <a href="{{ route('products.edit', $value->product->id) }}" class="text-body">
                                        {{ \Illuminate\Support\Str::limit($value->product->name, 40) }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php($rating = (int) ($value->ratting ?? 0))
                                <span class="d-inline-flex align-items-center gap-1" aria-label="Rating: {{ $rating }} out of 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="icon-base ri ri-star-{{ $i <= $rating ? 'fill text-warning' : 'line text-muted' }}"></i>
                                    @endfor
                                    <span class="text-muted ms-1">({{ $rating }})</span>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end align-items-center gap-1">
                                    <form method="post" action="{{ route('reviews.active') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="hidden_id" value="{{ $value->id }}">
                                        <x-admin.button variant="outline-success" size="sm"
                                                        class="change-confirm" type="button">
                                            <i class="icon-base ri ri-check-line me-1"></i>Approve
                                        </x-admin.button>
                                    </form>

                                    <x-admin.button variant="outline-secondary" size="sm"
                                                    href="{{ route('reviews.edit', $value->id) }}">
                                        Edit
                                    </x-admin.button>

                                    <form method="post" action="{{ route('reviews.destroy') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="hidden_id" value="{{ $value->id }}">
                                        <x-admin.button variant="outline-danger" size="sm"
                                                        class="delete-confirm" type="submit"
                                                        data-confirm-title="Delete this review?"
                                                        data-confirm-text="The review is removed permanently.">
                                            Delete
                                        </x-admin.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ri ri-checkbox-circle-line icon-32px text-success d-block mb-2" aria-hidden="true"></i>
                                <span class="text-muted">No reviews are waiting. Everything submitted has been moderated.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
