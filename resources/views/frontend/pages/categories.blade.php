@extends('frontend.layouts.app')

@section('title', 'Categories')
@section('meta_description', 'Browse all categories')

@push('css')
<link rel="stylesheet" href="{{ asset('assets/templates/basic/css/categories-page.css') }}">
@endpush

@section('content')
<main>
    <div class="pt-60 pb-60">
        <div class="container">
            <div class="category-tree">
                <div class="d-flex flex-wrap gap-4">
                    @if(isset($categories) && count($categories) > 0)
                        @foreach($categories as $category)
                            @php
                                $catImgRaw = (string) ($category->image ?? '');
                                $catImgPath = preg_replace('~^public/~', '', $catImgRaw);
                                $catImgPath = ltrim($catImgPath, '/');
                                if (!empty($catImgPath) && !str_contains($catImgPath, '/') && !str_contains($catImgPath, '\\')) {
                                    $catImgPath = 'uploads/category/' . $catImgPath;
                                }
                                $catImgUrl = !empty($catImgPath) ? asset($catImgPath) : asset('assets/images/default.png');
                            @endphp
                            <div class="category-tree-item flex-grow-1 w-50">
                                <div class="parent-category">
                                    <a href="{{ route('products.category', $category->slug) }}" class="d-block text-center">
                                        <img src="{{ asset('assets/images/default.png') }}"
                                            data-src="{{ $catImgUrl }}"
                                            class="w-100 lazyload owl-lazy" alt="category">
                                        <span class="title line-limitation-1">{{ $category->name }}</span>
                                    </a>
                                </div>

                                @if(isset($category->children) && count($category->children) > 0)
                                    <ul class="child-category">
                                        @foreach($category->children as $child)
                                            <li>
                                                <div class="text-nowrap">
                                                    @if(!empty($child->icon))
                                                        <span class="icon">
                                                            <img src="{{ asset($child->icon) }}" alt="category-icon" class="icon-img">
                                                        </span>
                                                    @endif

                                                    <a href="{{ route('products.category', $child->slug) }}" class="category-name">{{ $child->name }}</a>
                                                </div>

                                                @if(isset($child->children) && count($child->children) > 0)
                                                    <ul class="child-category">
                                                        @foreach($child->children as $sub)
                                                            <li>
                                                                <div class="text-nowrap">
                                                                    <span class="prefix">--</span>
                                                                    <a href="{{ route('products.category', $sub->slug) }}" class="category-name">{{ $sub->name }}</a>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="col-12">
                            <div class="alert alert-info mb-0">No categories found.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
