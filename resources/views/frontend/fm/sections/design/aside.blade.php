{{--
    Design block: aside
    work/2.md
    Cut by tools/split_design_sections.py - edit the design source, then re-run,
    rather than hand-editing this file.
--}}
<aside class="hidden lg:block lg:col-span-4 xl:col-span-3 space-y-6">
<div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-xs space-y-6">
<!-- Filter Header -->
<div class="flex items-center justify-between border-b border-gray-100 pb-3.5">
<div class="flex items-center gap-2">
<i class="bi bi-sliders2 text-[#1b5e20] text-base"></i>
<h2 class="text-sm font-bold text-gray-900 tracking-tight">Filter Produce</h2>
</div>
<button class="text-xs font-semibold text-[#1b5e20] hover:underline" onclick="resetFormFilters()" type="button">
            Reset All
          </button>
</div>
<!-- Departments List (14px vertical item spacing) -->
<div>
<span class="text-xs font-bold text-gray-900 uppercase tracking-wider block mb-3.5">
            Departments
          </span>
<div class="space-y-[14px] text-xs text-gray-700">
<label class="flex items-center justify-between p-2 rounded-xl bg-emerald-50 text-[#1b5e20] font-bold cursor-pointer">
<span class="flex items-center gap-2.5">
<input checked="" class="text-[#1b5e20] focus:ring-0 rounded-full h-4 w-4" name="dept-filter" type="radio">
                All Departments
              </span>
<span class="text-[11px] bg-white px-2 py-0.5 rounded-full font-bold shadow-xs">48</span>
</label>
<label class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="text-[#1b5e20] focus:ring-0 rounded-full h-4 w-4" name="dept-filter" type="radio">
                Fresh Farm Vegetables
              </span>
<span class="text-[11px] text-gray-400">128</span>
</label>
<label class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="text-[#1b5e20] focus:ring-0 rounded-full h-4 w-4" name="dept-filter" type="radio">
                Crisp Organic Fruits
              </span>
<span class="text-[11px] text-gray-400">94</span>
</label>
<label class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="text-[#1b5e20] focus:ring-0 rounded-full h-4 w-4" name="dept-filter" type="radio">
                Pasture Dairy &amp; Eggs
              </span>
<span class="text-[11px] text-gray-400">45</span>
</label>
<label class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="text-[#1b5e20] focus:ring-0 rounded-full h-4 w-4" name="dept-filter" type="radio">
                Grass-Fed Meat &amp; Seafood
              </span>
<span class="text-[11px] text-gray-400">38</span>
</label>
<label class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="text-[#1b5e20] focus:ring-0 rounded-full h-4 w-4" name="dept-filter" type="radio">
                Wild Raw Honey &amp; Pantry
              </span>
<span class="text-[11px] text-gray-400">62</span>
</label>
<label class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="text-[#1b5e20] focus:ring-0 rounded-full h-4 w-4" name="dept-filter" type="radio">
                Artisan Sourdough Bakery
              </span>
<span class="text-[11px] text-gray-400">29</span>
</label>
</div>
</div>
<!-- Price Range Filter with Range Slider & clear min/max values -->
<div class="pt-3 border-t border-gray-100">
<div class="flex justify-between items-center mb-2.5">
<span class="text-xs font-bold text-gray-900 uppercase tracking-wider">Price Ceiling</span>
<span class="text-sm font-bold text-[#1b5e20]" id="priceValue">$25.00</span>
</div>
<input class="w-full bg-gray-200 h-1.5 rounded-lg appearance-none cursor-pointer accent-[#1b5e20]" id="priceRange" max="50" min="2" oninput="updatePrice(this.value)" type="range" value="25">
<div class="flex justify-between text-[11px] text-gray-400 font-semibold mt-1.5">
<span>Min: $2.00</span>
<span>Max: $50.00+</span>
</div>
<!-- 2x2 Quick Price Pills -->
<div class="grid grid-cols-2 gap-2 mt-3.5">
<button class="py-1.5 px-2 text-center rounded-lg bg-gray-100 hover:bg-emerald-100 text-xs font-semibold text-gray-700 hover:text-[#1b5e20] transition-colors" onclick="updatePrice(5)" type="button">Under $5</button>
<button class="py-1.5 px-2 text-center rounded-lg bg-gray-100 hover:bg-emerald-100 text-xs font-semibold text-gray-700 hover:text-[#1b5e20] transition-colors" onclick="updatePrice(15)" type="button">$5 to $15</button>
<button class="py-1.5 px-2 text-center rounded-lg bg-gray-100 hover:bg-emerald-100 text-xs font-semibold text-gray-700 hover:text-[#1b5e20] transition-colors" onclick="updatePrice(30)" type="button">$15 to $30</button>
<button class="py-1.5 px-2 text-center rounded-lg bg-gray-100 hover:bg-emerald-100 text-xs font-semibold text-gray-700 hover:text-[#1b5e20] transition-colors" onclick="updatePrice(50)" type="button">$30 &amp; Above</button>
</div>
</div>
<!-- Dietary & Standards (14px spacing) -->
<div class="pt-3 border-t border-gray-100">
<span class="text-xs font-bold text-gray-900 uppercase tracking-wider block mb-3.5">
            Dietary &amp; Standards
          </span>
<div class="space-y-[14px] text-xs text-gray-700">
<label class="flex items-center gap-2.5 cursor-pointer">
<input checked="" class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
<span>100% Certified Organic</span>
</label>
<label class="flex items-center gap-2.5 cursor-pointer">
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
<span>Non-GMO Project Verified</span>
</label>
<label class="flex items-center gap-2.5 cursor-pointer">
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
<span>Grass-Fed &amp; Pastured</span>
</label>
<label class="flex items-center gap-2.5 cursor-pointer">
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
<span>Gluten-Free Certified</span>
</label>
<label class="flex items-center gap-2.5 cursor-pointer">
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
<span>Plant-Based / Vegan</span>
</label>
<label class="flex items-center gap-2.5 cursor-pointer">
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
<span>Cold-Pressed Unrefined</span>
</label>
</div>
</div>
<!-- Partner Producer (14px spacing) -->
<div class="pt-3 border-t border-gray-100">
<span class="text-xs font-bold text-gray-900 uppercase tracking-wider block mb-3.5">
            Partner Producer
          </span>
<div class="space-y-[14px] text-xs text-gray-700">
<label class="flex items-center justify-between cursor-pointer">
<span class="flex items-center gap-2.5">
<input checked="" class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
                FreshMart Organics
              </span>
<span class="text-[11px] text-gray-400">24</span>
</label>
<label class="flex items-center justify-between cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
                GreenValley Farms
              </span>
<span class="text-[11px] text-gray-400">16</span>
</label>
<label class="flex items-center justify-between cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
                PurePasture Dairy
              </span>
<span class="text-[11px] text-gray-400">9</span>
</label>
<label class="flex items-center justify-between cursor-pointer">
<span class="flex items-center gap-2.5">
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
                HoneyBee Reserve
              </span>
<span class="text-[11px] text-gray-400">7</span>
</label>
</div>
</div>
<!-- Rating Filter (14px spacing) -->
<div class="pt-3 border-t border-gray-100">
<span class="text-xs font-bold text-gray-900 uppercase tracking-wider block mb-3.5">
            Rating
          </span>
<div class="space-y-[14px] text-xs">
<label class="flex items-center gap-2.5 cursor-pointer">
<input class="text-[#1b5e20] focus:ring-0 h-4 w-4" name="rating-filter" type="radio">
<span class="flex items-center text-amber-500">
<i class="bi bi-star-fill text-xs mr-0.5"></i>
<i class="bi bi-star-fill text-xs mr-0.5"></i>
<i class="bi bi-star-fill text-xs mr-0.5"></i>
<i class="bi bi-star-fill text-xs mr-0.5"></i>
<i class="bi bi-star-half text-xs mr-1"></i>
</span>
<span class="font-bold text-gray-800">4.5 &amp; up</span>
</label>
<label class="flex items-center gap-2.5 cursor-pointer">
<input checked="" class="text-[#1b5e20] focus:ring-0 h-4 w-4" name="rating-filter" type="radio">
<span class="flex items-center text-amber-500">
<i class="bi bi-star-fill text-xs mr-0.5"></i>
<i class="bi bi-star-fill text-xs mr-0.5"></i>
<i class="bi bi-star-fill text-xs mr-0.5"></i>
<i class="bi bi-star-fill text-xs mr-0.5"></i>
<i class="bi bi-star text-xs text-gray-300 mr-1"></i>
</span>
<span class="font-bold text-gray-800">4.0 &amp; up</span>
</label>
</div>
</div>
<!-- Quick Toggles -->
<div class="pt-3 border-t border-gray-100 space-y-2">
<label class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 cursor-pointer">
<span class="text-xs font-bold text-gray-800">In Stock Only</span>
<input checked="" class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
</label>
<label class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 cursor-pointer">
<span class="text-xs font-bold text-gray-800">Same-Day Express</span>
<input class="rounded text-[#1b5e20] focus:ring-0 h-4 w-4" type="checkbox">
</label>
</div>
<!-- Apply Filters Button -->
<button class="w-full py-3 rounded-full bg-[#1b5e20] hover:bg-[#144618] text-white text-xs font-bold tracking-wide shadow-sm transition-all active:scale-98" onclick="showToast('Filters applied! Showing 12 matching products.')" type="button">
          Apply Filters
        </button>
</div>
<!-- Seasonal Box Promotion Card -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1b5e20] to-[#2e7d32] p-5 text-white shadow-md">
<span class="px-2.5 py-0.5 rounded-full bg-amber-400 text-slate-900 text-[10px] font-extrabold uppercase inline-block mb-2">
          Seasonal Box
        </span>
<h3 class="text-base font-bold text-white leading-tight mb-1">
          Weekend Harvest Basket
        </h3>
<p class="text-xs text-emerald-100 mb-3">
          Handpicked seasonal organic veggies &amp; fruits bundled with $15 off.
        </p>
<div class="flex items-center justify-between pt-1">
<code class="px-2.5 py-1 rounded bg-black/25 text-emerald-200 text-xs font-bold">CODE: HARVEST15</code>
<button aria-label="Use promo HARVEST15" class="w-8 h-8 rounded-full bg-white text-[#1b5e20] flex items-center justify-center hover:scale-105 transition-transform" onclick="showToast('HARVEST15 promo code added!')" type="button">
<i class="bi bi-arrow-right"></i>
</button>
</div>
</div>
</aside>
