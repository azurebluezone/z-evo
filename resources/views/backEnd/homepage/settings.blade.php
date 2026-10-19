@extends('backEnd.layouts.app')
@section('title','Home Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Home Settings</h4>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="post" action="{{ route('admin.homepage.settings.update') }}" class="row g-3">
                    @csrf

                    <div class="col-12">
                        <h5 class="mb-2">Hero Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_hero_section" value="1" @if(($settings->home_hero_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_hero_title" value="{{ old('home_hero_title', $settings->home_hero_title) }}" placeholder="Hero">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Promo Banners Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_promo_section" value="1" @if(($settings->home_promo_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_promo_title" value="{{ old('home_promo_title', $settings->home_promo_title) }}" placeholder="Promo Banners">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Flash Sale Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_flash_sale_section" value="1" @if(($settings->home_flash_sale_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_flash_sale_title" value="{{ old('home_flash_sale_title', $settings->home_flash_sale_title) }}" placeholder="Flash Sale">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Featured Products Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_featured_section" value="1" @if(($settings->home_featured_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_featured_title" value="{{ old('home_featured_title', $settings->home_featured_title) }}" placeholder="Featured Products">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Collections Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_collections_section" value="1" @if(($settings->home_collections_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_collections_title" value="{{ old('home_collections_title', $settings->home_collections_title) }}" placeholder="Collections">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Top Brands Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_brands_section" value="1" @if(($settings->home_brands_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_brands_title" value="{{ old('home_brands_title', $settings->home_brands_title) }}" placeholder="Top Brands">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Service Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_service_section" value="1" @if(($settings->home_service_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_service_title" value="{{ old('home_service_title', $settings->home_service_title) }}" placeholder="Service">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Recommended Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_recommended_section" value="1" @if(($settings->home_recommended_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_recommended_title" value="{{ old('home_recommended_title', $settings->home_recommended_title) }}" placeholder="Recommended">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Best Selling Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_best_selling_section" value="1" @if(($settings->home_best_selling_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_best_selling_title" value="{{ old('home_best_selling_title', $settings->home_best_selling_title) }}" placeholder="Best Selling">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Discount Products Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_discount_products_section" value="1" @if(($settings->home_discount_products_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_discount_products_title" value="{{ old('home_discount_products_title', $settings->home_discount_products_title) }}" placeholder="Discount Products">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Category Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_category_section" value="1" @if(($settings->home_category_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_category_title" value="{{ old('home_category_title', $settings->home_category_title) }}" placeholder="Categories">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-12">
                        <h5 class="mb-2">Footer Section</h5>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label d-block">Show/Hide</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" name="home_footer_section" value="1" @if(($settings->home_footer_section ?? 1)==1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label">Enable</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Custom Title</label>
                        <input type="text" class="form-control" name="home_footer_title" value="{{ old('home_footer_title', $settings->home_footer_title) }}" placeholder="Footer">
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <x-admin.button variant="primary" type="submit">Save Changes</x-admin.button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection
