{{--
    Design block: header-primary-header
    work/2.md
    Cut by tools/split_design_sections.py - edit the design source, then re-run,
    rather than hand-editing this file.
--}}
<header class="bg-white border-b border-gray-100 sticky top-0 z-40 shadow-sm" data-purpose="primary-header">
<div class="max-w-7xl mx-auto px-4 py-3">
<div class="flex items-center justify-between gap-3 md:gap-6">
<!-- Logo -->
<div class="flex items-center shrink-0">
<a class="inline-flex items-center text-decoration-none group" href="#">
<img alt="FreshMart - Good Food Better Life" class="h-10 md:h-11 w-auto object-contain rounded-md" src="https://lh3.googleusercontent.com/aida/AEtjO1W06bg-5Yq_mdERDDCqLC4o1fc0dLPGM5pxdGflpl5TOPQzdYnRT7cbnAaXfrJQCkzIoIR02DaQNFjNasqc-RpbcVDepgC7TSmXJ7p2A569DB7m1xlY6rvNsjeJORJOvCpHSjCk8yDWbQhc8kodI1xBAYIEaIYQP8D9lE35BWW6aigQGMGAYJe8CGhPfWGskoBgVx5T8xARSZX0R_4_wKVvKoi340DX6JOVcRWoWuGVIcla6UwSVqBNLg">
</a>
</div>
<!-- Integrated Clean Search Bar with Voice, Lens & Dropdown -->
<div class="flex-grow max-w-2xl mx-auto hidden sm:block">
<form class="w-full m-0" onsubmit="event.preventDefault(); openAiDrawer();" role="search">
<div class="flex items-center rounded-full border-2 border-emerald-800/30 hover:border-emerald-800 focus-within:border-emerald-800 focus-within:ring-2 focus-within:ring-emerald-100 transition bg-white shadow-sm overflow-hidden p-0.5">
<!-- Category select dropdown -->
<div class="dropdown shrink-0">
<button aria-expanded="false" class="btn btn-light bg-transparent border-0 dropdown-toggle px-3.5 py-1.5 text-xs font-semibold text-gray-700 hover:text-emerald-900 focus:outline-none" data-bs-toggle="dropdown" id="searchCategoryBtn" type="button">
                  All Categories
                </button>
<ul class="dropdown-menu text-xs shadow-md border-0 rounded-xl p-1 mt-2" style="">
<li><a class="dropdown-item py-1.5 rounded" href="#" onclick="selectCategory('All Categories')">All Categories</a></li>
<li><a class="dropdown-item py-1.5 rounded" href="#" onclick="selectCategory('Vegetables')">Vegetables</a></li>
<li><a class="dropdown-item py-1.5 rounded" href="#" onclick="selectCategory('Fruits')">Fresh Fruits</a></li>
<li><a class="dropdown-item py-1.5 rounded" href="#" onclick="selectCategory('Dairy &amp; Eggs')">Dairy &amp; Eggs</a></li>
<li><a class="dropdown-item py-1.5 rounded" href="#" onclick="selectCategory('Organic Honey')">Organic Honey</a></li>
</ul>
</div>
<!-- Input text -->
<input aria-label="Search" class="w-full border-0 px-3 py-1.5 text-sm text-gray-800 placeholder-gray-400 focus:ring-0 focus:outline-none" id="global-search-input" placeholder="Search farm fresh produce, organics, cuts..." type="search" value="Find firm ripe Haas avocados under $6">
<!-- Quick AI Action Icons inside search bar: Voice & Lens -->
<div class="flex items-center gap-1 pr-1.5 shrink-0">
<!-- Voice Search Button -->
<button class="p-2 rounded-full text-gray-500 hover:text-emerald-800 hover:bg-emerald-50 transition" id="global-voice-search-btn" onclick="openAiDrawer('voice')" title="Voice Search with AI" type="button">
<span class="material-symbols-outlined text-lg leading-none align-middle">mic</span>
</button>
<!-- Camera / Google Lens Button -->
<button class="p-2 rounded-full text-emerald-800 hover:bg-emerald-50 transition" id="global-lens-search-btn" onclick="openAiDrawer('camera')" title="Optical Produce Scan (Google Lens)" type="button">
<span class="material-symbols-outlined text-lg leading-none align-middle">photo_camera</span>
</button>
<!-- Submit Search Button -->
<button class="h-9 w-9 bg-primary hover:bg-[#144618] text-white rounded-full flex items-center justify-center transition shadow-sm shrink-0" title="Search" type="submit">
<i class="bi bi-search text-xs"></i>
</button>
</div>
</div>
</form>
</div>
<!-- Header Quick Actions & AI Toggle -->
<div class="flex items-center space-x-2 md:space-x-3 shrink-0">
<!-- AI Scanner Toggle Button -->
<button class="inline-flex items-center gap-2 px-3 py-1.5 md:px-3.5 md:py-2 rounded-full bg-emerald-50 border border-emerald-300 text-primary font-bold text-xs hover:bg-emerald-100 hover:border-emerald-400 transition shadow-sm" id="ai-scanner-drawer-toggle" onclick="openAiDrawer()" title="Open AI Spectral Lens &amp; Telemetry">
<span class="relative flex h-2 w-2">
<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
<span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-700"></span>
</span>
<span class="material-symbols-outlined text-base text-emerald-700">auto_awesome</span>
<span class="hidden md:inline font-semibold">AI Lens</span>
</button>
<!-- Wishlist -->
<a class="relative p-2 text-gray-700 hover:text-emerald-800 transition flex items-center" href="#wishlist" title="Wishlist">
<i class="bi bi-heart text-xl"></i>
<span class="absolute top-1 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-500 rounded-full" id="wishlistCount">3</span>
</a>
<!-- User Account -->
<div class="dropdown">
<button class="p-2 text-gray-700 hover:text-emerald-800 transition flex items-center border-0 bg-transparent" data-bs-toggle="dropdown" id="user-profile-btn" title="Alex Doe" type="button">
<i class="bi bi-person-circle text-xl"></i>
</button>
<ul class="dropdown-menu dropdown-menu-end shadow-md border-0 rounded-xl p-2 text-xs">
<li><h6 class="dropdown-header text-emerald-900 font-bold">Hello, Alex Doe</h6></li>
<li><a class="dropdown-item rounded-lg py-1.5" href="#"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
<li><a class="dropdown-item rounded-lg py-1.5" href="#"><i class="bi bi-box-seam me-2"></i>My Orders</a></li>
<li><a class="dropdown-item rounded-lg py-1.5" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
<li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item text-danger rounded-lg py-1.5" href="#"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
</ul>
</div>
<!-- Cart Drawer Button -->
<button aria-controls="cartDrawer" class="btn btn-brand-primary rounded-full px-3 py-1.5 md:py-2 flex items-center shadow-sm" data-bs-target="#cartDrawer" data-bs-toggle="offcanvas" type="button">
<div class="relative me-2">
<i class="bi bi-basket2-fill text-base"></i>
<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-amber-400 text-slate-900 text-[9px]" id="cartCountBadge">4</span>
</div>
<span class="hidden sm:inline text-xs font-bold text-white tracking-wide" id="cartTotalHeader">$25.46</span>
</button>
</div>
</div>
<!-- Mobile Search Form -->
<div class="mt-2.5 sm:hidden">
<form class="w-full m-0" onsubmit="event.preventDefault(); openAiDrawer();" role="search">
<div class="flex items-center rounded-full border border-emerald-800/30 bg-white px-2 py-1 shadow-sm">
<input class="w-full border-0 px-2 py-1 text-xs text-gray-800 placeholder-gray-400 focus:ring-0 focus:outline-none" placeholder="Search farm fresh produce..." type="search">
<button class="p-1 text-emerald-800" onclick="openAiDrawer('camera')" type="button">
<span class="material-symbols-outlined text-lg">photo_camera</span>
</button>
<button class="p-1 text-gray-600" onclick="openAiDrawer('voice')" type="button">
<span class="material-symbols-outlined text-lg">mic</span>
</button>
</div>
</form>
</div>
</div>
</header>
