@extends('backEnd.layouts.app')
@section('title','Website Setup')

@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Website Setup</h5>
    </div>

    <div class="card-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === 'header' ? 'active' : '' }}" href="{{ route('admin.website-setup.index', ['tab' => 'header']) }}" role="tab">HeaderSoon</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === 'footer' ? 'active' : '' }}" href="{{ route('admin.website-setup.index', ['tab' => 'footer']) }}" role="tab">FooterSoon</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === 'appearance' ? 'active' : '' }}" href="{{ route('admin.website-setup.index', ['tab' => 'appearance']) }}" role="tab">AppearanceSoon</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === 'seo' ? 'active' : '' }}" href="{{ route('admin.website-setup.index', ['tab' => 'seo']) }}" role="tab">Seo</a>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    <div class="tab-pane fade {{ $tab === 'header' ? 'show active' : '' }}" role="tabpanel">
                        <div class="alert alert-info mb-0">
                            Header setup UI will be added here.
                        </div>
                    </div>

                    <div class="tab-pane fade {{ $tab === 'footer' ? 'show active' : '' }}" role="tabpanel">
                        <div class="alert alert-info mb-0">
                            Footer setup UI will be added here.
                        </div>
                    </div>

                    <div class="tab-pane fade {{ $tab === 'appearance' ? 'show active' : '' }}" role="tabpanel">
                        <div class="alert alert-info mb-0">
                            Appearance setup UI will be added here.
                        </div>
                    </div>

                    <div class="tab-pane fade {{ $tab === 'seo' ? 'show active' : '' }}" role="tabpanel">
                        <div class="alert alert-info mb-0">
                            Use existing SEO settings page from Website Setup > Seo menu.
                        </div>
                    </div>
                </div>

    </div>
</div>
@endsection
