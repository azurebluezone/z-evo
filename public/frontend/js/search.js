tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "on-secondary-fixed-variant": "#005313",
            "tertiary-container": "#744800",
            "surface-container-lowest": "#ffffff",
            "on-tertiary-container": "#ffb759",
            "surface": "#f8f9ff",
            "secondary-container": "#91f78e",
            "inverse-on-surface": "#eaf1ff",
            "surface-container": "#e6eeff",
            "on-error-container": "#93000a",
            "primary-fixed-dim": "#91d78a",
            "on-secondary-fixed": "#002204",
            "on-primary-fixed": "#002203",
            "inverse-surface": "#27313f",
            "on-surface-variant": "#41493e",
            "surface-variant": "#d9e3f6",
            "on-tertiary-fixed": "#2a1700",
            "error": "#ba1a1a",
            "primary": "#00450d",
            "secondary": "#006e1c",
            "secondary-fixed-dim": "#78dc77",
            "on-surface": "#121c2a",
            "on-background": "#121c2a",
            "primary-container": "#1b5e20",
            "error-container": "#ffdad6",
            "surface-tint": "#2a6b2c",
            "on-primary": "#ffffff",
            "on-primary-container": "#90d689",
            "on-tertiary": "#ffffff",
            "surface-container-low": "#eff4ff",
            "outline-variant": "#c0c9bb",
            "on-error": "#ffffff",
            "tertiary": "#553300",
            "tertiary-fixed-dim": "#ffb95f",
            "surface-bright": "#f8f9ff",
            "on-tertiary-fixed-variant": "#653e00",
            "surface-container-highest": "#d9e3f6",
            "on-secondary": "#ffffff",
            "primary-fixed": "#acf4a4",
            "secondary-fixed": "#94f990",
            "inverse-primary": "#91d78a",
            "outline": "#717a6d",
            "tertiary-fixed": "#ffddb8",
            "surface-container-high": "#dee9fc",
            "background": "#f8f9ff",
            "on-secondary-container": "#00731e",
            "surface-dim": "#d0dbed",
            "on-primary-fixed-variant": "#0c5216"
          },
          borderRadius: {
            DEFAULT: "0.25rem",
            lg: "0.5rem",
            xl: "0.75rem",
            full: "9999px"
          },
          spacing: {
            "space-sm": "0.75rem",
            "space-lg": "1.5rem",
            "space-xs": "0.5rem",
            "space-xxs": "0.25rem",
            "space-md": "1rem",
            margin: "2rem",
            "space-3xl": "4.5rem",
            gutter: "1.5rem",
            "space-xl": "2rem",
            "gutter-mobile": "0.75rem",
            "margin-mobile": "1rem",
            "space-2xl": "3rem"
          },
          fontFamily: {
            "title-md": ["Plus Jakarta Sans", "sans-serif"],
            "display-lg-mobile": ["Plus Jakarta Sans", "sans-serif"],
            "headline-lg": ["Plus Jakarta Sans", "sans-serif"],
            "label-md": ["Plus Jakarta Sans", "sans-serif"],
            "body-lg": ["Inter", "sans-serif"],
            "label-sm": ["Plus Jakarta Sans", "sans-serif"],
            "body-md": ["Inter", "sans-serif"],
            "label-lg": ["Plus Jakarta Sans", "sans-serif"],
            "headline-sm": ["Plus Jakarta Sans", "sans-serif"],
            "price-lg": ["Plus Jakarta Sans", "sans-serif"],
            "price-md": ["Plus Jakarta Sans", "sans-serif"],
            "headline-lg-mobile": ["Plus Jakarta Sans", "sans-serif"],
            "headline-md": ["Plus Jakarta Sans", "sans-serif"],
            "display-lg": ["Plus Jakarta Sans", "sans-serif"],
            "body-sm": ["Inter", "sans-serif"],
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            inter: ['"Inter"', 'sans-serif']
          }
        }
      }
    }

let wishlistCount = 3;

    function updatePrice(val) {
      const priceEl = document.getElementById('priceValue');
      const rangeEl = document.getElementById('priceRange');
      if (priceEl) priceEl.innerText = '$' + parseFloat(val).toFixed(2);
      if (rangeEl) rangeEl.value = val;
    }

    function setViewMode(mode) {
      const grid = document.getElementById('product-grid');
      const gridBtn = document.getElementById('grid-view-btn');
      const listBtn = document.getElementById('list-view-btn');
      if (!grid) return;
      if (mode === 'list') {
        grid.className = 'grid grid-cols-1 gap-4 transition-all';
        if (gridBtn) gridBtn.className = 'p-1.5 rounded-lg text-gray-500 hover:text-[#1b5e20] transition-colors';
        if (listBtn) listBtn.className = 'p-1.5 rounded-lg bg-white text-[#1b5e20] shadow-xs';
      } else {
        grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 transition-all';
        if (gridBtn) gridBtn.className = 'p-1.5 rounded-lg bg-white text-[#1b5e20] shadow-xs';
        if (listBtn) listBtn.className = 'p-1.5 rounded-lg text-gray-500 hover:text-[#1b5e20] transition-colors';
      }
    }

    function resetActiveFilterTags() {
      ['filter-tag-instock', 'filter-tag-price', 'filter-tag-certified'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.remove();
      });
      showToast('All active filters cleared.');
    }

    function resetFormFilters() {
      updatePrice(50);
      showToast('Filters cleared to default catalog settings.');
    }

    function toggleWishlist(btn, productName) {
      btn.classList.toggle('active');
      const badge = document.getElementById('wishlistCount');
      if (btn.classList.contains('active')) {
        wishlistCount++;
        showToast(`Saved ${productName} to your favorites!`);
      } else {
        wishlistCount = Math.max(0, wishlistCount - 1);
        showToast(`Removed from favorites.`);
      }
      if (badge) badge.innerText = wishlistCount;
    }

    function addToCart(name, price, unit) {
      showToast(`Added ${name} (${unit}) to your basket!`);
    }

    let toastTimer;
    function showToast(msg) {
      const toast = document.getElementById('actionToast');
      const text = document.getElementById('toastMessage');
      if (!toast || !text) return;
      text.innerText = msg;
      toast.classList.remove('opacity-0', 'translate-y-2');
      toast.classList.add('opacity-100', 'translate-y-0');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => {
        toast.classList.remove('opacity-100', 'translate-y-0');
        toast.classList.add('opacity-0', 'translate-y-2');
      }, 2500);
    }