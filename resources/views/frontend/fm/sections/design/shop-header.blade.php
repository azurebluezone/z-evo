{{--
    Design block: shop-header
    work/2.md
    Cut by tools/split_design_sections.py - edit the design source, then re-run,
    rather than hand-editing this file.
--}}
<section class="bg-[#f4f7f2] border-b border-gray-200/70 py-5" data-section="shop-header">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<!-- Breadcrumb & Guarantee Badge -->

<!-- Title & Subtitle -->
<div class="mb-3">
<h1 class="text-2xl sm:text-3xl font-extrabold text-[#132116] tracking-tight">
        All Organic Products &amp; Groceries
      </h1>

</div>
<!-- Active Filter Tags Strip -->
<div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
<span class="text-gray-500 font-bold uppercase tracking-wider text-[11px]">Active Filters:</span>
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#1b5e20] font-semibold border border-emerald-200/80 shadow-xs" id="filter-tag-instock">
        In Stock Only
        <button class="text-gray-400 hover:text-red-600 flex items-center" onclick="document.getElementById('filter-tag-instock').remove()" type="button">
<i class="bi bi-x text-sm"></i>
</button>
</span>
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#1b5e20] font-semibold border border-emerald-200/80 shadow-xs" id="filter-tag-price">
        Under $20.00
        <button class="text-gray-400 hover:text-red-600 flex items-center" onclick="document.getElementById('filter-tag-price').remove()" type="button">
<i class="bi bi-x text-sm"></i>
</button>
</span>
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#1b5e20] font-semibold border border-emerald-200/80 shadow-xs" id="filter-tag-certified">
        100% Certified Organic
        <button class="text-gray-400 hover:text-red-600 flex items-center" onclick="document.getElementById('filter-tag-certified').remove()" type="button">
<i class="bi bi-x text-sm"></i>
</button>
</span>
<button class="text-red-600 hover:underline font-semibold ml-2 text-xs" onclick="resetActiveFilterTags()" type="button">
        Clear all
      </button>
</div>
</div>
</section>
