<ul class="list-inline mega-menu vertical-megamenu">
    @if(isset($menucategories))
        @foreach($menucategories as $category)
            @php
                $menuCategoryIconPath = preg_replace('~^public/~', '', (string) ($category->image ?? ''));
                $menuCategoryIconPath = ltrim($menuCategoryIconPath, '/');
                if (empty($menuCategoryIconPath) || !\Illuminate\Support\Facades\File::exists(public_path($menuCategoryIconPath))) {
                    $menuCategoryIconPath = 'uploads/category/default.png';
                }
                $hasMega = isset($category->subcategories) && $category->subcategories->count() > 0;
            @endphp

            <li class="fluid-menu {{ $hasMega ? 'dropdown has-submenu' : '' }}">
                <a href="{{ route('products.category', $category->slug) }}" class="nav-link menu-item {{ $hasMega ? 'dropdown has-submenu' : '' }}">
                    <span class="menu-item-icon">
                        <img src="{{ asset($menuCategoryIconPath) }}" alt="icon">
                    </span>
                    {{ $category->name ?? '' }}

                    <span class="menu-item-arrowicon">
                        <i class="las la-angle-right"></i>
                    </span>
                </a>

                @if($hasMega)
                    <ul class="list-inline categories__mega-menu-wrap categories__mega-menu d-none d-lg-block">
                        <li>
                            <div class="categories__mega-menu-content">
                                @foreach($category->subcategories as $sub)
                                    <div class="categories__mega-menu-list">
                                        <h5 class="categories__mega-menu-title">
                                            <a href="{{ route('products.category', $sub->slug) }}">{{ $sub->subcategoryName }}</a>
                                        </h5>

                                        <ul class="list-inline cate__mega-menu-list">
                                            @php
                                                $children = $sub->childcategories ?? collect();
                                            @endphp

                                            @foreach($children as $child)
                                                <li>
                                                    <a href="{{ route('products.category', $child->slug) }}">{{ $child->childcategoryName }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                @endif
            </li>
        @endforeach
    @else
        <li class="fluid-menu">
            <a href="{{ url('/categories') }}" class="nav-link menu-item">Categories</a>
        </li>
    @endif
</ul>
