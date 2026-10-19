{{--
    Design block: catalog-pagination
    work/2.md
    Cut by tools/split_design_sections.py - edit the design source, then re-run,
    rather than hand-editing this file.
--}}
<nav aria-label="Catalog Pagination">
<ul class="flex items-center space-x-1.5 text-xs font-bold">
<li>
<button class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:bg-gray-100 transition-colors" disabled="" type="button">
<i class="bi bi-chevron-left"></i>
</button>
</li>
<li>
<button class="w-8 h-8 rounded-xl flex items-center justify-center bg-[#1b5e20] text-white shadow-xs" type="button">1</button>
</li>
<li>
<button class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-700 hover:bg-emerald-50 hover:text-[#1b5e20] transition-colors" onclick="showToast('Loading page 2...')" type="button">2</button>
</li>
<li>
<button class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-700 hover:bg-emerald-50 hover:text-[#1b5e20] transition-colors" onclick="showToast('Loading page 3...')" type="button">3</button>
</li>
<li>
<button class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-700 hover:bg-emerald-50 hover:text-[#1b5e20] transition-colors" onclick="showToast('Loading page 4...')" type="button">4</button>
</li>
<li class="px-1 text-gray-400">...</li>
<li>
<button class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-700 hover:bg-emerald-50 hover:text-[#1b5e20] transition-colors" onclick="showToast('Loading page 12...')" type="button">12</button>
</li>
<li>
<button class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-700 hover:bg-gray-100 transition-colors" onclick="showToast('Loading next page...')" type="button">
<i class="bi bi-chevron-right"></i>
</button>
</li>
</ul>
</nav>
