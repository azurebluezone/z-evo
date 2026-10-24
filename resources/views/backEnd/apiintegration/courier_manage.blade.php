@extends('backEnd.layouts.app') 
@section('title','Courier API')
@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h4 class="mb-0">Steadfast Courier</h4>
        </div>

        <form action="{{route('courierapi.update')}}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="id" value="{{$steadfast->id}}">

          <div class="row">
            <div class="col-12 col-lg-6 mb-3">
              <label for="api_key" class="form-label">API key *</label>
              <input type="text" class="form-control @error('api_key') is-invalid @enderror" name="api_key" value="{{ $steadfast->api_key}}" id="api_key" required="" />
              @error('api_key')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <div class="col-12 col-lg-6 mb-3">
              <label for="secret_key" class="form-label">Secret key *</label>
              <input type="text" class="form-control @error('secret_key') is-invalid @enderror" name="secret_key" value="{{ $steadfast->secret_key }}" id="secret_key" required="" />
              @error('secret_key')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <div class="col-12 col-lg-8 mb-3">
              <label for="url_steadfast" class="form-label">URL *</label>
              <input type="text" class="form-control @error('url') is-invalid @enderror" name="url" value="{{ $steadfast->url }}" id="url_steadfast" placeholder="Enter base URL (Example: https://portal.packzy.com/api/v1)" required="" />
              <small class="text-muted"> <strong></strong> </small>
              @error('url')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <div class="col-12 col-lg-4 mb-3">
              <label for="status" class="d-block">Status</label>
              <label class="switch switch-square">
                <input type="checkbox" class="switch-input" value="1" @if($steadfast->status==1)checked @endif name="status" />
                <span class="switch-toggle-slider">
                  <span class="switch-on"></span>
                  <span class="switch-off"></span>
                </span>
                <span class="switch-label">Enabled</span>
              </label>
              @error('status')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>
          </div>

          <div class="mt-2 d-flex gap-2">
            <x-admin.button variant="primary" type="submit">Save</x-admin.button>
            <x-admin.button variant="outline-info" type="button" id="testSteadfastBtn" data-test-url="{{ route('admin.courier.test.steadfast') }}">
              <i class="ri ri-test-tube-line me-1"></i>Test Connection
            </x-admin.button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h4 class="mb-0">Pathao Courier</h4>
        </div>

        <form action="{{route('courierapi.update')}}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="id" value="{{ $pathao->id}}">

          <div class="row">
            <div class="col-12 col-lg-8 mb-3">
              <label for="url_pathao" class="form-label">URL *</label>
              <input type="text" class="form-control @error('url') is-invalid @enderror" name="url" value="{{ $pathao->url}}" id="url_pathao" placeholder="Enter base URL (Sandbox: https://courier-api-sandbox.pathao.com | Live: https://api-hermes.pathao.com)" required="" />
              <small class="text-muted"> <strong></strong> </small>
              @error('url')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <div class="col-12 col-lg-4 mb-3">
              <label for="status" class="d-block">Status</label>
              <label class="switch switch-square">
                <input type="checkbox" class="switch-input" value="1" @if($pathao->status==1)checked @endif name="status" />
                <span class="switch-toggle-slider">
                  <span class="switch-on"></span>
                  <span class="switch-off"></span>
                </span>
                <span class="switch-label">Enabled</span>
              </label>
              @error('status')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <div class="col-12 col-lg-6 mb-3">
              <label for="pathao_client_id" class="form-label">Client ID</label>
              <input type="text" class="form-control" name="client_id" value="{{ $pathao->client_id ?? '' }}" id="pathao_client_id" placeholder="Example: 7N1aMJQbWm" />
            </div>

            <div class="col-12 col-lg-6 mb-3">
              <label for="pathao_client_secret" class="form-label">Client Secret</label>
              <input type="text" class="form-control" name="client_secret" value="{{ $pathao->client_secret ?? '' }}" id="pathao_client_secret" placeholder="Example: wRcaibZkUdSNz2EI9ZyuXLlNrnAv0TdPUPXMnD39" />
            </div>

            <div class="col-12 col-lg-6 mb-3">
              <label for="pathao_username" class="form-label">Username</label>
              <input type="text" class="form-control" name="username" value="{{ $pathao->username ?? '' }}" id="pathao_username" placeholder="Example: test@pathao.com" />
            </div>

            <div class="col-12 col-lg-6 mb-3">
              <label for="pathao_password" class="form-label">Password</label>
              <input type="text" class="form-control" name="password" value="{{ $pathao->password ?? '' }}" id="pathao_password" placeholder="Example: lovePathao" />
              <small class="text-muted"> <strong></strong> </small>
            </div>

            <div class="col-12 col-lg-6 mb-3">
              <label for="token" class="form-label">Token</label>
              <input type="text" class="form-control @error('token') is-invalid @enderror" name="token" value="{{ $pathao->token}}" id="token" placeholder="Auto generated (or paste manually)" />
              @error('token')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>
          </div>

          <div class="mt-2 d-flex gap-2">
            <x-admin.button variant="primary" type="submit">Save</x-admin.button>
            <x-admin.button variant="outline-info" type="button" id="testPathaoBtn" data-test-url="{{ route('admin.courier.test.pathao') }}">
              <i class="ri ri-test-tube-line me-1"></i>Test Connection
            </x-admin.button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h4 class="mb-0">Sync Orders</h4>
        </div>

        <form>
          <div class="row">
            <div class="col-12 col-lg-6 mb-3">
              <label for="syncLimit" class="form-label">Sync Limit</label>
              <input type="number" class="form-control" name="syncLimit" id="syncLimit" value="100" />
            </div>
          </div>

          <div class="mt-2">
            <x-admin.button variant="primary" type="button" id="syncOrdersBtn" data-sync-url="{{ route('admin.courierapi.sync.orders') }}">Sync Orders</x-admin.button>
          </div>
        </form>

        <div id="syncResults"></div>
      </div>
    </div>
  </div>
</div>

@endsection
