@extends('backEnd.layouts.app')
@section('title', 'Create Product')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="fw-bold mb-1">Create New Product</h4>
    <p class="text-muted mb-0">Add a new product to your inventory</p>
  </div>
  <div class="d-flex gap-2">
    <a href="/product" class="btn btn-outline-secondary waves-effect">
      <i class="icon-base ri ri-arrow-left-line me-2"></i>Back to Products
    </a>
  </div>
</div>

<!-- Product Form -->
<form id="createProductForm" onsubmit="event.preventDefault(); saveProduct();">
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
                <input type="text" class="form-control" id="productName" placeholder="Product Name">
                <label for="productName">Product Name *</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="text" class="form-control" id="productSKU" placeholder="SKU">
                <label for="productSKU">SKU Code</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <select class="form-select" id="productCategory">
                  <option value="">Select Category</option>
                  <option value="smartphones">Smartphones</option>
                  <option value="laptops">Laptops</option>
                  <option value="accessories">Accessories</option>
                  <option value="electronics">Electronics</option>
                </select>
                <label for="productCategory">Category *</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating form-floating-outline">
                <input type="text" class="form-control" id="productBrand" placeholder="Brand">
                <label for="productBrand">Brand</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-floating form-floating-outline">
                <input type="number" class="form-control" id="productPrice" placeholder="Price">
                <label for="productPrice">Price (৳) *</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-floating form-floating-outline">
                <input type="number" class="form-control" id="productCost" placeholder="Cost">
                <label for="productCost">Cost Price (৳)</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-floating form-floating-outline">
                <input type="number" class="form-control" id="productStock" placeholder="Stock">
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
            <p>Cupcake ipsum dolor sit amet. Halvah cheesecake chocolate bar gummi bears cupcake. Pie macaroon bear claw. Soufflé I love candy canes I love cotton candy I love.</p>
            <p><br></p>
            <p>Product Features:</p>
            <ul>
              <li>High quality materials</li>
              <li>1 year warranty</li>
              <li>Made in Bangladesh</li>
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
                <img src="https://via.placeholder.com/100x100/7367f0/fff?text=Product" class="img-fluid mb-2" style="max-height: 100px;">
                <small class="d-block text-muted">Main Image</small>
                <x-admin.button variant="text-danger" size="sm" class="mt-2" type="button">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="border rounded p-2 text-center border-dashed">
                <i class="icon-base ri ri-add-line icon-24px text-muted mb-2"></i>
                <small class="d-block text-muted">Add Image</small>
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
            <input class="form-check-input" type="checkbox" id="productFeatured">
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
            <input type="text" class="form-control" id="seoTitle" placeholder="SEO Title">
            <label for="seoTitle">SEO Title</label>
          </div>
          <div class="form-floating form-floating-outline mb-3">
            <textarea class="form-control" id="seoDescription" placeholder="Meta Description" style="height: 80px"></textarea>
            <label for="seoDescription">Meta Description</label>
          </div>
          <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" id="seoKeywords" placeholder="Keywords">
            <label for="seoKeywords">Keywords (comma separated)</label>
          </div>
        </div>
      </div>

      <!-- Save Actions -->
      <div class="card">
        <div class="card-body">
          <x-admin.button variant="primary" ripple="true" class="w-100 waves-light mb-3" type="submit">
            <i class="icon-base ri ri-save-line me-2"></i>Save Product
          </x-admin.button>
          <x-admin.button variant="outline-secondary" ripple="true" class="w-100" type="button" onclick="saveAsDraft()">
            <i class="icon-base ri ri-draft-line me-2"></i>Save as Draft
          </x-admin.button>
        </div>
      </div>
    </div>
  </div>
</form>

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

function saveProduct() {
  // Get Quill content
  const description = fullEditor.root.innerHTML;
  
  // Show success message
  showProductToast('success', 'Product Created', 'Product has been created successfully!');
  
  // In real implementation, this would submit the form data
  console.log('Product Description:', description);
}

function saveAsDraft() {
  showProductToast('info', 'Draft Saved', 'Product saved as draft.');
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
