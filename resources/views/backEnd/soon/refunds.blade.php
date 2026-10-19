@extends('backEnd.layouts.app')
@section('title','Refunds')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <h4 class="page-title">Refunds</h4>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="alert alert-secondary mb-0">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="fw-semibold">RefundsSoon</div>
                <div class="text-muted">এই মডিউলটি পরবর্তী ধাপে ডেভেলপ হবে (payment reconciliation + audit log)।</div>
              </div>
              <span class="badge text-bg-secondary">Soon</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
