@extends('backEnd.layouts.app')
@section('title', 'Product Management')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="fw-bold mb-1">Product Management</h4>
    <p class="text-muted mb-0">Manage your products, stock, and inventory</p>
  </div>
  <div class="d-flex gap-2">
    <a href="/product/create" class="btn btn-primary waves-effect">
      <i class="icon-base ri ri-add-line me-2"></i>Add Product
    </a>
  </div>
</div>

<!-- Product Statistics -->
<div class="row g-6 mb-6">
  <!-- Total Products -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ri ri-box-3-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">2,845</h4>
        </div>
        <h6 class="mb-0 fw-normal">Total Products</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+15%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Active Products -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-success">
              <i class="icon-base ri ri-check-double-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">2,450</h4>
        </div>
        <h6 class="mb-0 fw-normal">Active Products</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-success">+8%</span>
          <small class="text-body-secondary">than last month</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Low Stock -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ri ri-alert-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">28</h4>
        </div>
        <h6 class="mb-0 fw-normal">Low Stock Alert</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-danger">+5%</span>
          <small class="text-body-secondary">needs attention</small>
        </p>
      </div>
    </div>
  </div>

  <!-- Out of Stock -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="avatar me-4">
            <span class="avatar-initial rounded-3 bg-label-danger">
              <i class="icon-base ri ri-close-circle-line icon-24px"></i>
            </span>
          </div>
          <h4 class="mb-0">12</h4>
        </div>
        <h6 class="mb-0 fw-normal">Out of Stock</h6>
        <p class="mb-0">
          <span class="me-1 fw-medium text-danger">Urgent</span>
          <small class="text-body-secondary">restock needed</small>
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Product List -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Product List</h5>
    <div class="d-flex gap-2 flex-wrap">
      <a href="/product/create" class="btn btn-primary waves-effect">
        <i class="icon-base ri ri-add-line me-2"></i>Add Product
      </a>
      <div class="dropdown">
        <x-admin.button variant="outline-secondary" class="dropdown-toggle" data-bs-toggle="dropdown">
          <i class="icon-base ri ri-filter-3-line me-2"></i>Filter
        </x-admin.button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="javascript:void(0);">All Products</a></li>
          <li><a class="dropdown-item" href="javascript:void(0);">Active</a></li>
          <li><a class="dropdown-item" href="javascript:void(0);">Inactive</a></li>
          <li><a class="dropdown-item" href="javascript:void(0);">Low Stock</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://via.placeholder.com/50x50/7367f0/fff?text=iPhone" class="rounded me-2" width="50" height="50">
                <div>
                  <h6 class="mb-0">iPhone 14 Pro</h6>
                  <small class="text-muted">Apple</small>
                </div>
              </div>
            </td>
            <td>APL-IP14P-256</td>
            <td>Smartphones</td>
            <td><span class="badge bg-label-success">45 in stock</span></td>
            <td>৳125,000</td>
            <td><span class="badge bg-label-success">Active</span></td>
            <td>
              <div class="d-flex gap-1">
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewProductModal">
                  <i class="icon-base ri ri-eye-line"></i>
                </x-admin.button>
                <a href="/product/1/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                  <i class="icon-base ri ri-edit-line"></i>
                </a>
                <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteProductModal" onclick="setDeleteProduct('iPhone 14 Pro')">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://via.placeholder.com/50x50/28c76f/fff?text=Samsung" class="rounded me-2" width="50" height="50">
                <div>
                  <h6 class="mb-0">Samsung Galaxy S23</h6>
                  <small class="text-muted">Samsung</small>
                </div>
              </div>
            </td>
            <td>SAM-S23-128</td>
            <td>Smartphones</td>
            <td><span class="badge bg-label-warning">8 in stock</span></td>
            <td>৳95,000</td>
            <td><span class="badge bg-label-success">Active</span></td>
            <td>
              <div class="d-flex gap-1">
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewProductModal">
                  <i class="icon-base ri ri-eye-line"></i>
                </x-admin.button>
                <a href="/product/2/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                  <i class="icon-base ri ri-edit-line"></i>
                </a>
                <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteProductModal" onclick="setDeleteProduct('Samsung Galaxy S23')">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://via.placeholder.com/50x50/ea5455/fff?text=MacBook" class="rounded me-2" width="50" height="50">
                <div>
                  <h6 class="mb-0">MacBook Air M2</h6>
                  <small class="text-muted">Apple</small>
                </div>
              </div>
            </td>
            <td>APL-MBA-M2</td>
            <td>Laptops</td>
            <td><span class="badge bg-label-danger">Out of stock</span></td>
            <td>৳110,000</td>
            <td><span class="badge bg-label-danger">Inactive</span></td>
            <td>
              <div class="d-flex gap-1">
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewProductModal">
                  <i class="icon-base ri ri-eye-line"></i>
                </x-admin.button>
                <a href="/product/3/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                  <i class="icon-base ri ri-edit-line"></i>
                </a>
                <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteProductModal" onclick="setDeleteProduct('MacBook Air M2')">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://via.placeholder.com/50x50/ff9f43/fff?text=AirPods" class="rounded me-2" width="50" height="50">
                <div>
                  <h6 class="mb-0">AirPods Pro 2nd Gen</h6>
                  <small class="text-muted">Apple</small>
                </div>
              </div>
            </td>
            <td>APL-APP-2ND</td>
            <td>Accessories</td>
            <td><span class="badge bg-label-success">156 in stock</span></td>
            <td>৳28,000</td>
            <td><span class="badge bg-label-success">Active</span></td>
            <td>
              <div class="d-flex gap-1">
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewProductModal">
                  <i class="icon-base ri ri-eye-line"></i>
                </x-admin.button>
                <a href="/product/4/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                  <i class="icon-base ri ri-edit-line"></i>
                </a>
                <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteProductModal" onclick="setDeleteProduct('AirPods Pro 2nd Gen')">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <img src="https://via.placeholder.com/50x50/00cfe8/fff?text=iPad" class="rounded me-2" width="50" height="50">
                <div>
                  <h6 class="mb-0">iPad Air 5th Gen</h6>
                  <small class="text-muted">Apple</small>
                </div>
              </div>
            </td>
            <td>APL-IPA-5TH</td>
            <td>Tablets</td>
            <td><span class="badge bg-label-warning">12 in stock</span></td>
            <td>৳72,000</td>
            <td><span class="badge bg-label-success">Active</span></td>
            <td>
              <div class="d-flex gap-1">
                <x-admin.button variant="text-secondary" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#viewProductModal">
                  <i class="icon-base ri ri-eye-line"></i>
                </x-admin.button>
                <a href="/product/5/edit" class="btn btn-sm btn-icon btn-text-secondary waves-effect">
                  <i class="icon-base ri ri-edit-line"></i>
                </a>
                <x-admin.button variant="text-danger" size="sm" shape="icon" ripple="true" data-bs-toggle="modal" data-bs-target="#deleteProductModal" onclick="setDeleteProduct('iPad Air 5th Gen')">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </x-admin.button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-end mb-0">
        <li class="page-item prev">
          <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-left-s-line"></i></a>
        </li>
        <li class="page-item active">
          <a class="page-link waves-effect waves-light" href="javascript:void(0);">1</a>
        </li>
        <li class="page-item">
          <a class="page-link waves-effect" href="javascript:void(0);">2</a>
        </li>
        <li class="page-item">
          <a class="page-link waves-effect" href="javascript:void(0);">3</a>
        </li>
        <li class="page-item next">
          <a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-right-s-line"></i></a>
        </li>
      </ul>
    </nav>
  </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
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
              <label for="productSKU">SKU Code *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating form-floating-outline">
              <select class="form-select" id="productCategory">
                <option value="">Select Category</option>
                <option value="smartphones">Smartphones</option>
                <option value="laptops">Laptops</option>
                <option value="accessories">Accessories</option>
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
          <div class="col-12">
            <div class="form-floating form-floating-outline">
              <textarea class="form-control" id="productDescription" placeholder="Description" style="height: 100px"></textarea>
              <label for="productDescription">Product Description</label>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Product Images</label>
            <input class="form-control" type="file" id="productImages" multiple>
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="productActive" checked>
              <label class="form-check-label" for="productActive">Active Product</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="productFeatured">
              <label class="form-check-label" for="productFeatured">Featured Product</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showProductSuccess()">Add Product</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- View Product Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-5">
            <img src="https://via.placeholder.com/400x300/7367f0/fff?text=iPhone+14+Pro" class="img-fluid rounded mb-3">
            <div class="d-flex gap-2">
              <img src="https://via.placeholder.com/60x60/7367f0/fff?text=1" class="rounded" width="60" height="60">
              <img src="https://via.placeholder.com/60x60/7367f0/fff?text=2" class="rounded" width="60" height="60">
              <img src="https://via.placeholder.com/60x60/7367f0/fff?text=3" class="rounded" width="60" height="60">
            </div>
          </div>
          <div class="col-md-7">
            <h4>iPhone 14 Pro</h4>
            <p class="text-muted mb-2">SKU: APL-IP14P-256 | Brand: Apple</p>
            <div class="mb-3">
              <span class="badge bg-label-success me-2">Active</span>
              <span class="badge bg-label-warning">Featured</span>
            </div>
            <h3 class="text-primary mb-3">৳125,000</h3>
            <div class="row mb-3">
              <div class="col-6">
                <p class="mb-1"><strong>Stock:</strong> 45 units</p>
                <p class="mb-1"><strong>Category:</strong> Smartphones</p>
              </div>
              <div class="col-6">
                <p class="mb-1"><strong>Sold:</strong> 234 units</p>
                <p class="mb-1"><strong>Rating:</strong> 4.8/5</p>
              </div>
            </div>
            <h6>Description</h6>
            <p class="text-muted">The iPhone 14 Pro features a 6.1-inch Super Retina XDR display, A16 Bionic chip, and advanced camera system.</p>
          </div>
        </div>
        <hr class="my-4">
        <h6>Stock History</h6>
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Date</th>
              <th>Action</th>
              <th>Quantity</th>
              <th>By</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>05 Apr 2026</td>
              <td><span class="badge bg-label-success">Stock Added</span></td>
              <td>+50</td>
              <td>Admin</td>
            </tr>
            <tr>
              <td>01 Apr 2026</td>
              <td><span class="badge bg-label-danger">Stock Removed</span></td>
              <td>-5</td>
              <td>Order #ORD-001</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
        <x-admin.button variant="primary" type="button"><i class="icon-base ri ri-edit-line me-2"></i>Edit Product</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-3">
          <input type="text" class="form-control" id="editProductName" value="iPhone 14 Pro">
          <label for="editProductName">Product Name</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <input type="number" class="form-control" id="editProductPrice" value="125000">
          <label for="editProductPrice">Price (৳)</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <input type="number" class="form-control" id="editProductStock" value="45">
          <label for="editProductStock">Stock Quantity</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="editProductStatus">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <label for="editProductStatus">Status</label>
        </div>
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" id="editProductDesc" style="height: 100px">The iPhone 14 Pro features...</textarea>
          <label for="editProductDesc">Description</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showUpdateSuccess()">Save Changes</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Product Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title text-white"><i class="icon-base ri ri-alert-line me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="icon-base ri ri-delete-bin-line icon-48px text-danger mb-3"></i>
        <p class="mb-1">Are you sure you want to delete product <strong id="deleteProductName">Product Name</strong>?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="danger" ripple="true" class="waves-light" type="button" onclick="confirmDeleteProduct()">
          <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete
        </x-admin.button>
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
