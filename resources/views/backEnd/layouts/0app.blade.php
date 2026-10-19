<!DOCTYPE html>
<html
  lang="en"
  class="layout-navbar-fixed layout-menu-fixed layout-compact"
  dir="ltr"
  data-skin="default"
  data-bs-theme="light"
  data-assets-path="{{ rtrim(asset('admin'), '/') . '/' }}"
  data-base-url="{{ url('admin') }}"
  data-template="vertical-menu-template">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <title>@yield('title', 'Dashboard') | {{ $generalsetting->name ?? config('app.name', 'Z-Evo-Commerce') }}</title>

  <meta name="description" content="{{ $generalsetting->meta_description ?? 'Admin Dashboard' }}" />
  <meta name="keywords" content="Admin, Dashboard, Laravel" />

  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />

  @include('backEnd.layouts.partials.styles')
  @stack('css')

  <style>
    .btn,
    .btn-sm,
    .btn-lg,
    .btn-group .btn,
    .pagination .page-link,
    .pagination-rounded .page-link,
    .pagination-rounded .page-item:first-child .page-link,
    .pagination-rounded .page-item:last-child .page-link {
      border-radius: 0 !important;
    }

    .layout-page .content-wrapper .container-xxl {
      max-width: 100% !important;
    }

    @media print {
      .layout-menu,
      .layout-navbar,
      .content-footer,
      .layout-overlay,
      .drag-target,
      .no-print {
        display: none !important;
      }

      .layout-wrapper,
      .layout-container,
      .layout-page,
      .content-wrapper,
      .container-xxl,
      .container-p-y {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
      }
    }
  </style>
</head>

<body>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      @include('backEnd.layouts.partials.sidebar')

      <div class="layout-page">
        @include('backEnd.layouts.partials.navbar')

        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            @if(session('success'))
              <div class="alert alert-success alert-dismissible fade show d-none" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if(session('processing'))
              <div class="alert alert-info alert-dismissible fade show d-none" role="alert">
                {{ session('processing') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if(session('error'))
              <div class="alert alert-danger alert-dismissible fade show d-none" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if(session('warning'))
              <div class="alert alert-warning alert-dismissible fade show d-none" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if(session('info'))
              <div class="alert alert-secondary alert-dismissible fade show d-none" role="alert">
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if($errors->any())
              <div class="alert alert-danger alert-dismissible fade show d-none" role="alert">
                <ul class="mb-0">
                  @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif
            @yield('content')
          </div>

          @include('backEnd.layouts.partials.footer')
          <div class="content-backdrop fade"></div>
        </div>
      </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>
  </div>

  @include('backEnd.layouts.partials.scripts')
  @stack('script')
</body>

</html>
