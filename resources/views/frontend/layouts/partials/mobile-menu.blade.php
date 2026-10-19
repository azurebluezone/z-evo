<div class="site-sidebar mobile-menu sidebar-nav d-lg-none">
    <button type="button" class="sidebar-close-btn">
        <i class="las la-times"></i>
    </button>

    <div class="mobile-menu-header">
        <div class="d-block d-lg-none">
            <div class="dropdown dropdown--lang">
                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img class="dropdown-flag lazyload" src="{{ asset('assets/images/language/67076f5911ee81728540505.png') }}" alt="image">
                    <span>{{ app()->getLocale() == 'en' ? 'English' : (app()->getLocale() == 'bn' ? 'Bangla' : 'Hindi') }}</span>
                </button>

                <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('language.change', 'bn') }}">
                        <img class="dropdown-flag lazyload" src="{{ asset('assets/images/language/67076f5911ee81728540505.png') }}" alt="country">
                        <span>Bangla</span>
                    </a>
                    <a class="dropdown-item" href="{{ route('language.change', 'hi') }}">
                        <img class="dropdown-flag lazyload" src="{{ asset('assets/images/language/67076f5911ee81728540505.png') }}" alt="country">
                        <span>Hindi</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-menu-body">
        <style>
            .mobile-menu-category-list {
                list-style: none;
                padding: 0;
                margin: 0 0 12px 0;
            }
            .mobile-menu-category-list > li {
                border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            }
            .mobile-menu-category-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 12px;
            }
            .mobile-menu-category-item a {
                color: inherit;
                text-decoration: none;
                flex: 1;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                min-width: 0;
                order: 1;
            }
            .mobile-menu-category-item .mobile-menu-toggle-btn {
                margin-left: auto;
                order: 2;
            }
            .mobile-menu-category-item img {
                width: 26px;
                height: 26px;
                object-fit: cover;
                border-radius: 6px;
                flex-shrink: 0;
            }
            .mobile-menu-toggle-btn {
                width: 28px;
                height: 28px;
                border: 1px solid rgba(0, 0, 0, 0.12);
                background: #fff;
                border-radius: 6px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .mobile-menu-sublist {
                list-style: none;
                padding: 0;
                margin: 0;
                display: none;
                background: rgba(0, 0, 0, 0.02);
            }
            .mobile-menu-sublist.is-open {
                display: block;
            }
            .mobile-menu-sublist .mobile-menu-category-item {
                padding-left: 44px;
            }
            .mobile-menu-sublist .mobile-menu-sublist .mobile-menu-category-item {
                padding-left: 68px;
            }
        </style>

        @php
            $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
            $headerLinks = [];
            if (!empty($generalsetting->header_links)) {
                $headerLinks = json_decode($generalsetting->header_links, true) ?: [];
            }
            if (empty($headerLinks)) {
                $aboutUrl = $generalsetting->about_url ?? null;
                $contactUrl = $generalsetting->contact_url ?? null;
                $headerLinks = [
                    ['title' => 'About Us', 'url' => $aboutUrl ?: route('about.us')],
                    ['title' => 'Contact', 'url' => $contactUrl ?: route('contact')],
                ];
            }
        @endphp

        @if(isset($menucategories) && $menucategories->count() > 0)
            <ul class="mobile-menu-category-list" data-mobile-category-menu="1">
                @foreach($menucategories as $category)
                    @php
                        $iconRaw = (string) ($category->image ?? '');
                        $iconIsUrl = preg_match('~^https?://~i', $iconRaw) === 1;
                        $iconPath = $iconIsUrl ? $iconRaw : ltrim(preg_replace('~^public/~', '', $iconRaw), '/');
                        $iconSrc = !empty($iconPath) ? ($iconIsUrl ? $iconPath : asset($iconPath)) : asset('uploads/category/default.png');
                        $hasSubs = isset($category->subcategories) && $category->subcategories->count() > 0;
                    @endphp
                    <li>
                        <div class="mobile-menu-category-item">
                            @if($hasSubs)
                                <button type="button" class="mobile-menu-toggle-btn" data-mobile-menu-toggle="category" aria-expanded="false">
                                    <i class="las la-plus"></i>
                                </button>
                            @else
                                <span class="mobile-menu-toggle-btn" style="opacity:.0;border-color:transparent;background:transparent;"></span>
                            @endif
                            <a href="{{ route('products.category', $category->slug) }}">
                                <img src="{{ $iconSrc }}" alt="{{ $category->name ?? '' }}">
                                <span class="text-truncate">{{ $category->name ?? '' }}</span>
                            </a>
                        </div>

                        @if($hasSubs)
                            <ul class="mobile-menu-sublist" data-mobile-menu-sublist="category">
                                @foreach($category->subcategories as $sub)
                                    @php
                                        $hasChildren = isset($sub->childcategories) && $sub->childcategories->count() > 0;
                                    @endphp
                                    <li>
                                        <div class="mobile-menu-category-item">
                                            @if($hasChildren)
                                                <button type="button" class="mobile-menu-toggle-btn" data-mobile-menu-toggle="subcategory" aria-expanded="false">
                                                    <i class="las la-plus"></i>
                                                </button>
                                            @else
                                                <span class="mobile-menu-toggle-btn" style="opacity:.0;border-color:transparent;background:transparent;"></span>
                                            @endif
                                            <a href="{{ route('products.category', $sub->slug) }}">
                                                <span class="text-truncate">{{ $sub->subcategoryName ?? '' }}</span>
                                            </a>
                                        </div>

                                        @if($hasChildren)
                                            <ul class="mobile-menu-sublist" data-mobile-menu-sublist="subcategory">
                                                @foreach($sub->childcategories as $child)
                                                    <li>
                                                        <div class="mobile-menu-category-item">
                                                            <span class="mobile-menu-toggle-btn" style="opacity:.0;border-color:transparent;background:transparent;"></span>
                                                            <a href="{{ route('products.category', $child->slug) }}">
                                                                <span class="text-truncate">{{ $child->childcategoryName ?? '' }}</span>
                                                            </a>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <ul class="menu">
            <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
            <li><a href="{{ url('/products') }}" class="{{ request()->is('products*') ? 'active' : '' }}">Products</a></li>
            @foreach($headerLinks as $link)
                @php
                    $linkTitle = trim((string) ($link['title'] ?? ''));
                    $linkUrl = trim((string) ($link['url'] ?? ''));
                @endphp
                @if($linkTitle !== '' && $linkUrl !== '')
                    <li><a href="{{ $linkUrl }}">{{ $linkTitle }}</a></li>
                @endif
            @endforeach
            <li><a href="{{ url('/categories') }}">All Categories</a></li>
        </ul>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const root = document.querySelector('[data-mobile-category-menu="1"]');
                if (!root) return;

                const setIcon = (btn, open) => {
                    if (!btn) return;
                    const i = btn.querySelector('i');
                    if (!i) return;
                    i.classList.toggle('la-plus', !open);
                    i.classList.toggle('la-minus', !!open);
                };

                root.addEventListener('click', function (e) {
                    const btn = e.target && e.target.closest ? e.target.closest('[data-mobile-menu-toggle]') : null;
                    if (!btn || !root.contains(btn)) return;
                    e.preventDefault();

                    const item = btn.closest('li');
                    if (!item) return;

                    const type = btn.getAttribute('data-mobile-menu-toggle');
                    const sublist = item.querySelector(`[data-mobile-menu-sublist="${type}"]`);
                    if (!sublist) return;

                    const open = !sublist.classList.contains('is-open');
                    sublist.classList.toggle('is-open', open);
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    setIcon(btn, open);
                });
            });
        </script>
    </div>
</div>
