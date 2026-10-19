@extends('backEnd.layouts.app')
@section('title', 'Edit Product')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="fw-bold mb-1">Edit Product</h4>
    <p class="text-muted mb-0">Update product information</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ url('/product') }}" class="btn btn-outline-secondary waves-effect">
      <i class="icon-base ri ri-arrow-left-line me-2"></i>Back to Products
    </a>
  </div>
</div>

<!-- Product Form -->
<form id="editProductForm" onsubmit="event.preventDefault(); updateProduct();">
  <div class="row g-6">
    <!-- Basic Information -->
    <div class="col-lg-8">
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ri ri-information-line me-2"></i>Basic Information</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="text" class="form-control" id="productName" value="iPhone 14 Pro">
                <label for="productName">Product Name *</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="text" class="form-control" id="productSKU" value="APL-IP14P-256">
                <label for="productSKU">SKU Code</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <select class="form-select" id="productCategory">
                  <option value="">Select Category</option>
                  <option value="smartphones" selected>Smartphones</option>
                  <option value="laptops">Laptops</option>
                  <option value="accessories">Accessories</option>
                </select>
                <label for="productCategory">Category *</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="text" class="form-control" id="productBrand" value="Apple">
                <label for="productBrand">Brand</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-floating form-floating-outline">
                <input type="number" class="form-control" id="productPrice" value="125000">
                <label for="productPrice">Price (৳) *</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-floating form-floating-outline">
                <input type="number" class="form-control" id="productCost" value="100000">
                <label for="productCost">Cost Price (৳)</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-floating form-floating-outline">
                <input type="number" class="form-control" id="productStock" value="45">
                <label for="productStock">Stock Quantity *</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Full Editor (Quill) -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ri ri-file-text-line me-2"></i>Product Description</h5>
        </div>
        <div class="card-body">
          <div id="full-editor">
            <p>The iPhone 14 Pro features a stunning 6.1-inch Super Retina XDR display with ProMotion technology. Powered by the A16 Bionic chip, it delivers exceptional performance for gaming, photography, and everyday tasks.</p>
            <p><br></p>
            <p><strong>Key Features:</strong></p>
            <ul>
              <li>6.1-inch Super Retina XDR display</li>
              <li>A16 Bionic chip</li>
              <li>Pro camera system with 48MP Main camera</li>
              <li>Dynamic Island</li>
              <li>All-day battery life</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Product Images -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ri ri-image-line me-2"></i>Product Images</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Upload Images</label>
            <input class="form-control" type="file" id="productImages" multiple accept="image/*">
            <small class="text-muted">You can upload multiple images. First image will be the main product image.</small>
          </div>
          <div class="row g-3" id="imagePreview">
            <div class="col-6 col-md-3">
              <div class="border rounded p-2 text-center">
                <img src="{{ asset('admin/assets/img/products/iphone.png') }}" class="img-fluid mb-2" style="max-height: 100px;">
                <small class="d-block text-muted">Main Image</small>
                <x-admin.button variant="text-danger" size="sm" class="mt-2" type="button">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sidebar Settings -->
    <div class="col-lg-4">
      <!-- Status & Visibility -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Status & Visibility</h5>
        </div>
        <div class="card-body">
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="productActive" checked>
            <label class="form-check-label" for="productActive">Active Product</label>
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="productFeatured" checked>
            <label class="form-check-label" for="productFeatured">Featured Product</label>
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="productOnSale">
            <label class="form-check-label" for="productOnSale">On Sale</label>
          </div>
          <div class="form-floating form-floating-outline">
            <select class="form-select" id="productVisibility">
              <option value="public" selected>Public</option>
              <option value="private">Private</option>
              <option value="draft">Draft</option>
            </select>
            <label for="productVisibility">Visibility</label>
          </div>
        </div>
      </div>

      <!-- SEO Settings -->
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">SEO Settings</h5>
        </div>
        <div class="card-body">
          <div class="form-floating form-floating-outline mb-3">
            <input type="text" class="form-control" id="seoTitle" value="iPhone 14 Pro - Apple">
            <label for="seoTitle">SEO Title</label>
          </div>
          <div class="form-floating form-floating-outline mb-3">
            <textarea class="form-control" id="seoDescription" style="height: 80px">Buy iPhone 14 Pro with 48MP camera and Dynamic Island. Available now in Bangladesh.</textarea>
            <label for="seoDescription">Meta Description</label>
          </div>
          <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" id="seoKeywords" value="iPhone, Apple, Smartphone, 14 Pro">
            <label for="seoKeywords">Keywords (comma separated)</label>
          </div>
        </div>
      </div>

      <!-- Save Actions -->
      <div class="card">
        <div class="card-body">
          <x-admin.button variant="primary" ripple="true" class="w-100 waves-light mb-3" type="submit">
            <i class="icon-base ri ri-save-line me-2"></i>Update Product
          </x-admin.button>
          <x-admin.button variant="outline-danger" ripple="true" class="w-100" type="button" data-bs-toggle="modal" data-bs-target="#deleteProductModal">
            <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete Product
          </x-admin.button>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white"><i class="icon-base ri ri-alert-line me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="icon-base ri ri-delete-bin-line icon-48px text-danger mb-3"></i>
        <p class="mb-1">Are you sure you want to delete <strong>iPhone 14 Pro</strong>?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <a href="{{ url('/product') }}" class="btn btn-danger waves-effect waves-light">
          <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="productToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header" id="productToastHeader">
      <i class="icon-base ri ri-information-line me-2" id="productToastIcon"></i>
      <strong class="me-auto" id="productToastTitle">Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="productToastMessage">
      Message goes here
    </div>
  </div>
</div>

@endsection

@push('scripts')
<!-- Quill Editor -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
<script src="{{ asset('admin/assets/vendor/libs/quill/katex.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/quill/quill.js') }}"></script>
<script src="{{ asset('admin/assets/js/forms-editors.js') }}"></script>

<script>
// Initialize Quill Editor
const fullEditor = new Quill('#full-editor', {
  bounds: '#full-editor',
  placeholder: 'Type Something...',
  modules: {
    formula: true,
    toolbar: [
      [
        {
          font: []
        },
        {
          size: []
        }
      ],
      ['bold', 'italic', 'underline', 'strike'],
      [
        {
          color: []
        },
        {
          background: []
        }
      ],
      [
        {
          script: 'super'
        },
        {
          script: 'sub'
        }
      ],
      [
        {
          header: '1'
        },
        {
          header: '2'
        },
        'blockquote',
        'code-block'
      ],
      [
        {
          list: 'ordered'
        },
        {
          list: 'bullet'
        },
        {
          indent: '-1'
        },
        {
          indent: '+1'
        }
      ],
      [
        'direction',
        {
          align: []
        }
      ],
      ['link', 'image', 'video', 'formula'],
      ['clean']
    ]
  },
  theme: 'snow'
});

function updateProduct() {
  // Get Quill content
  const description = fullEditor.root.innerHTML;
  
  // Show success message
  showProductToast('success', 'Product Updated', 'Product has been updated successfully!');
  
  // In real implementation, this would submit the form data
  console.log('Product Description:', description);
}

function showProductToast(type, title, message) {
  const toast = document.getElementById('productToast');
  const header = document.getElementById('productToastHeader');
  const icon = document.getElementById('productToastIcon');
  const titleEl = document.getElementById('productToastTitle');
  const messageEl = document.getElementById('productToastMessage');
  
  header.className = 'toast-header';
  
  switch(type) {
    case 'success':
      header.classList.add('bg-success', 'text-white');
      icon.className = 'icon-base ri ri-check-line me-2';
      break;
    case 'error':
      header.classList.add('bg-danger', 'text-white');
      icon.className = 'icon-base ri ri-close-circle-line me-2';
      break;
    case 'info':
      header.classList.add('bg-info', 'text-white');
      icon.className = 'icon-base ri ri-information-line me-2';
      break;
  }
  
  titleEl.textContent = title;
  messageEl.textContent = message;
  
  const bsToast = new bootstrap.Toast(toast);
  bsToast.show();
}
</script>
@endpush
