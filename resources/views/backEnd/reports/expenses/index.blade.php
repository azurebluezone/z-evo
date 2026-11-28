@extends('backEnd.layouts.app')
@section('title', 'Expenses')

@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3" data-expense-entry>
    <div>
        <h4 class="mb-0">Expenses</h4>
        <p class="text-muted mb-0 small">
            Every taka that leaves the business is entered once and reported on the day it happened.
            A saved expense changes the dashboard Expense and Estimated Net Profit on the next load.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.dashboard') }}" class="btn btn-sm btn-outline-secondary">Report Dashboard</a>
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Total expenses recorded</p>
                <h4 class="mb-0">BDT {{ number_format($total, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Expense entries</p>
                <h4 class="mb-0">{{ number_format($expenses->total(), 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Categories</p>
                <h4 class="mb-0">{{ number_format($categories->count(), 0) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Add expense</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.reports.expenses.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="expense_category_id">Category</label>
                        <select name="expense_category_id" id="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror">
                            <option value="">Select a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('expense_category_id') == $category->id)>
                                    {{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('expense_category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="amount">Amount (BDT)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror">
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="spent_at">Spent on</label>
                        <input type="date" name="spent_at" id="spent_at" value="{{ old('spent_at', now()->toDateString()) }}" class="form-control @error('spent_at') is-invalid @enderror">
                        @error('spent_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="payment_method">Payment method</label>
                        <input type="text" name="payment_method" id="payment_method" value="{{ old('payment_method') }}" class="form-control @error('payment_method') is-invalid @enderror" placeholder="cash, bKash, bank">
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="note">Note</label>
                        <input type="text" name="note" id="note" value="{{ old('note') }}" class="form-control @error('note') is-invalid @enderror" maxlength="191" placeholder="What was this spend for?">
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">Save expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Add category</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.reports.expense-categories.store') }}" method="POST" class="row g-3">
                    @csrf
                    <input type="hidden" name="is_active" value="0">
                    <div class="col-12">
                        <label class="form-label" for="category_name">Name</label>
                        <input type="text" name="name" id="category_name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" maxlength="80" placeholder="Ads, packing, salary, rent">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="sort_order">Sort order</label>
                        <input type="number" min="0" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" class="form-control @error('sort_order') is-invalid @enderror">
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-outline-primary">Save category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Recent expenses</h5>
        <span class="text-muted small">{{ number_format($expenses->total(), 0) }} entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th class="text-end">Amount</th>
                    <th>Method</th>
                    <th>Note</th>
                    <th>Entered by</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td>{{ $expense->spent_at?->format('d M Y') }}</td>
                        <td>{{ $expense->category?->name ?? '—' }}</td>
                        <td class="text-end">BDT {{ number_format((float) $expense->amount, 2) }}</td>
                        <td>{{ $expense->payment_method ?: '—' }}</td>
                        <td>{{ $expense->note ?: '—' }}</td>
                        <td>{{ $expense->creator?->name ?? '—' }}</td>
                        <td class="text-end">
                            <form action="{{ route('admin.reports.expenses.destroy') }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense?')">
                                @csrf
                                <input type="hidden" name="expense_id" value="{{ $expense->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No expense recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($expenses->hasPages())
        <div class="card-footer">
            {{ $expenses->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<div class="card">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Categories</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Sort</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <form action="{{ route('admin.reports.expense-categories.update') }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                <input type="text" name="name" value="{{ $category->name }}" class="form-control form-control-sm" maxlength="80">
                                <input type="number" name="sort_order" value="{{ $category->sort_order }}" class="form-control form-control-sm" style="max-width: 90px" min="0">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </td>
                        <td>{{ $category->sort_order }}</td>
                        <td>
                            <span class="badge bg-label-{{ $category->is_active ? 'success' : 'secondary' }}">
                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <form action="{{ route('admin.reports.expense-categories.toggle') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $category->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                            <form action="{{ route('admin.reports.expense-categories.destroy') }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No category yet. Add the first one above.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
