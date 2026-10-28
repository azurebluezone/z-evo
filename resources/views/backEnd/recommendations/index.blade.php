@extends('backEnd.layouts.app')
@section('title', 'Product Recommendations')

@section('content')
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-1">Manage Recommended Products</h4>
      <p class="text-muted mb-0">Products recommended for homepage display</p>
    </div>
    <div class="d-flex gap-2">
      <x-admin.button variant="primary" shape="square" ripple="true" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="ri ri-add-line me-1"></i> Add Product
      </x-admin.button>
    </div>
  </div>

  @if(Session::has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ Session::get('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(Session::has('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      {{ Session::get('warning') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

<!-- Product List Card -->
<div class="card rounded-0">
  <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
    <h5 class="mb-0 text-uppercase fw-bold">Recommended Products</h5>
    <div class="d-flex gap-2 align-items-center">
      <form class="d-flex align-items-center gap-2" method="get" action="{{ route('admin.recommendations.index') }}">
        <div class="input-group">
          <span class="input-group-text"><i class="ri ri-search-line"></i></span>
          <input type="text" name="keyword" class="form-control" placeholder="Search products..." value="{{ request('keyword') }}" style="min-width: 200px;">
        </div>
        <x-admin.button variant="outline-primary" type="submit">Search</x-admin.button>
      </form>
      <x-admin.button variant="primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="ri ri-add-line me-1"></i>Add product
      </x-admin.button>
    </div>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 5%">SL</th>
            <th style="width: 10%">Image</th>
            <th style="width: 35%">Product Name</th>
            <th style="width: 20%">Category</th>
            <th style="width: 10%">Status</th>
            <th style="width: 20%" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($data as $key => $value)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
              @php
                $recImg = $value->image && $value->image->image ? $value->image->image : null;
                $recImgPath = $recImg ? preg_replace('~^public/~', '', $recImg) : null;
                $recImgPath = $recImgPath ? ltrim($recImgPath, '/') : null;
                $fallbackImg = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                $fallbackImg = preg_replace('~^public/~', '', $fallbackImg);
                $fallbackImg = ltrim($fallbackImg, '/');
              @endphp
              <img
                src="{{ $recImgPath && file_exists(public_path($recImgPath)) ? asset($recImgPath) : asset($fallbackImg) }}"
                class="rounded border" style="height: 50px; width: 50px; object-fit: cover;"
                alt="{{ $value->name }}"
                onerror="this.src='{{ asset('assets/images/empty.png') }}'">
            </td>
            <td>
              <div>
                <h6 class="mb-0 fw-medium">{{ $value->name }}</h6>
                <small class="text-muted">ID: #{{ $value->id }}</small>
              </div>
            </td>
            <td>
              <span class="badge bg-label-secondary">
                {{ $value->category ? $value->category->name : 'Uncategorized' }}
              </span>
            </td>
            <td>
              @if($value->is_recommended == 1)
                <span class="badge bg-label-success"><i class="ri ri-check-line me-1"></i>Active</span>
              @else
                <span class="badge bg-label-danger"><i class="ri ri-close-line me-1"></i>Inactive</span>
              @endif
            </td>
            <td class="text-end">
              <div class="d-inline-flex justify-content-end align-items-center gap-1">
                @if($value->is_recommended == 1)
                  <form method="post" action="{{ route('admin.recommendations.update') }}" class="d-inline" id="inactiveForm-{{ $value->id }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $value->id }}">
                    <input type="hidden" name="is_recommended" value="0">
                    <div class="form-check form-switch custom-switch-sharp m-0">
                      <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" checked title="Active" onchange="document.getElementById('inactiveForm-{{ $value->id }}').submit();">
                    </div>
                  </form>
                @else
                  <form method="post" action="{{ route('admin.recommendations.update') }}" class="d-inline" id="activeForm-{{ $value->id }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $value->id }}">
                    <input type="hidden" name="is_recommended" value="1">
                    <div class="form-check form-switch custom-switch-sharp m-0">
                      <input type="checkbox" class="form-check-input rounded-0 cursor-pointer js-auto-submit" title="Inactive" onchange="document.getElementById('activeForm-{{ $value->id }}').submit();">
                    </div>
                  </form>
                @endif

                <a href="{{ route('products.edit', $value->id) }}" class="btn btn-sm btn-icon btn-text-secondary btn-square waves-effect" title="Edit">
                  <i class="icon-base ri ri-edit-line"></i>
                </a>

                <x-admin.button variant="text-danger" size="sm" shape="icon square" ripple="true" type="button" title="Remove" data-bs-toggle="modal" data-bs-target="#deleteModal" data-product-id="{{ $value->id }}" data-product-name="{{ $value->name }}">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center py-4">
              <div class="d-flex flex-column align-items-center">
                <i class="ri ri-inbox-line ri-3x text-muted mb-2"></i>
                <p class="text-muted mb-0">No recommended products found</p>
                <x-admin.button variant="primary" size="sm" class="mt-2" data-bs-toggle="modal" data-bs-target="#addProductModal">
                  <i class="ri ri-add-line me-1"></i>Add Product
                </x-admin.button>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if(method_exists($data, 'links'))
  <div class="card-footer border-top bg-white py-3">
    <div class="d-flex justify-content-end">
      {{ $data->links('pagination::bootstrap-4') }}
    </div>
  </div>
  @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Remove from Recommendations</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-3">
          <i class="ri ri-error-warning-line ri-3x text-danger"></i>
        </div>
        <h6 class="mb-2">Are you sure you want to remove this product?</h6>
        <p class="text-muted mb-0" id="deleteProductName"></p>
      </div>
      <div class="modal-footer justify-content-center">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <form method="post" action="{{ route('admin.recommendations.update') }}" id="deleteForm">
          @csrf
          <input type="hidden" name="id" id="deleteProductId">
          <input type="hidden" name="is_recommended" value="0">
          <input type="hidden" name="recommended_sort_order" value="0">
          <x-admin.button variant="danger" type="submit">
            <i class="ri ri-delete-bin-line me-1"></i>Yes, Remove
          </x-admin.button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Product to Recommendations</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="{{ route('admin.recommendations.update') }}">
        @csrf
        <input type="hidden" name="is_recommended" value="1">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold">Search Product</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri ri-search-line"></i></span>
                <input type="text" name="product_search" class="form-control" placeholder="Type product name or ID..." id="productSearchInput" autocomplete="off">
                <input type="hidden" name="id" id="selectedProductId">
                <x-admin.button variant="outline-primary" type="button" id="searchProductBtn">
                  <i class="ri ri-search-line me-1"></i>Search
                </x-admin.button>
              </div>
              <div id="searchResults" class="list-group mt-2 border rounded" style="max-height: 250px; overflow-y: auto; display: none;"></div>
              <div id="selectedProductDisplay" class="mt-2 p-2 bg-light rounded d-none">
                <small class="text-muted">Selected:</small>
                <span class="fw-medium ms-1" id="selectedProductName"></span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Sort Order</label>
              <input type="number" min="0" name="recommended_sort_order" class="form-control" value="0" placeholder="0">
              <small class="text-muted">Lower number appears first</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
          <x-admin.button variant="primary" type="submit" id="addProductBtn">
            <i class="ri ri-add-line me-1"></i>Add to Recommendations
          </x-admin.button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var searchInput = document.getElementById('productSearchInput');
        var searchBtn = document.getElementById('searchProductBtn');
        var resultsDiv = document.getElementById('searchResults');
        var selectedIdInput = document.getElementById('selectedProductId');
        var selectedProductDisplay = document.getElementById('selectedProductDisplay');
        var selectedProductName = document.getElementById('selectedProductName');
        var addProductBtn = document.getElementById('addProductBtn');
        var searchUrl = @json(route('admin.recommendations.search'));
        
        function performSearch() {
            var term = searchInput.value.trim();
            if (!term) return;
            
            fetch(searchUrl + '?q=' + encodeURIComponent(term))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    resultsDiv.innerHTML = '';
                    resultsDiv.style.display = 'block';
                    
                    if (data.results && data.results.length) {
                        data.results.forEach(function(item) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action py-2';
                            btn.innerHTML = '<div class="d-flex justify-content-between align-items-center">' +
                                '<span>' + item.text + '</span>' +
                                '<span class="badge bg-label-primary">ID: ' + item.id + '</span>' +
                                '</div>';
                            btn.onclick = function() {
                                searchInput.value = item.text;
                                selectedIdInput.value = item.id;
                                selectedProductName.textContent = item.text + ' (ID: ' + item.id + ')';
                                selectedProductDisplay.classList.remove('d-none');
                                addProductBtn.disabled = false;
                                resultsDiv.style.display = 'none';
                            };
                            resultsDiv.appendChild(btn);
                        });
                    } else {
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted py-3 text-center"><i class="ri ri-inbox-line ri-lg me-2"></i>No products found</div>';
                    }
                })
                .catch(function() {
                    resultsDiv.innerHTML = '<div class="list-group-item text-danger py-3 text-center"><i class="ri ri-error-warning-line ri-lg me-2"></i>Search failed. Please try again.</div>';
                });
        }
        
        if (searchBtn) searchBtn.addEventListener('click', performSearch);
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') performSearch();
            });
        }
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#searchResults') && !e.target.closest('.input-group')) {
                resultsDiv.style.display = 'none';
            }
        });
        
        // Reset modal on close
        var addProductModal = document.getElementById('addProductModal');
        if (addProductModal) {
            addProductModal.addEventListener('hidden.bs.modal', function () {
                searchInput.value = '';
                selectedIdInput.value = '';
                selectedProductDisplay.classList.add('d-none');
                selectedProductName.textContent = '';
                addProductBtn.disabled = true;
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
            });
        }

        // Delete Modal Handler
        var deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var productId = button.getAttribute('data-product-id');
                var productName = button.getAttribute('data-product-name');
                
                document.getElementById('deleteProductId').value = productId;
                document.getElementById('deleteProductName').textContent = productName;
            });
        }
    })();
</script>
@endpush
