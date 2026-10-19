@extends('backEnd.layouts.app')
@section('title','Website SEO')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-0">Website SEO</h4>
                </div>

                <form action="{{ route('admin.website-setup.seo.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @php
                        $model = $settings;
                    @endphp
                    @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                        @include('backEnd.layouts.partials.seo-meta-form')
                    @endcomponent

                    <div class="mt-2">
                        <x-admin.button variant="primary" type="submit">Save</x-admin.button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection
