tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "on-surface-variant": "#41493e",
            "on-tertiary-fixed": "#2a1700",
            "error": "#ba1a1a",
            "inverse-on-surface": "#eaf1ff",
            "primary-fixed-dim": "#91d78a",
            "on-secondary": "#ffffff",
            "on-tertiary-fixed-variant": "#653e00",
            "surface-container-low": "#f4f7f2",
            "outline-variant": "#c0c9bb",
            "tertiary-fixed": "#ffddb8",
            "outline": "#717a6d",
            "surface-container": "#e8eee5",
            "error-container": "#ffdad6",
            "tertiary-container": "#744800",
            "surface-container-high": "#deeadb",
            "on-error": "#ffffff",
            "primary-fixed": "#acf4a4",
            "surface-tint": "#1b5e20",
            "on-tertiary-container": "#ffb759",
            "inverse-surface": "#27313f",
            "on-surface": "#132116",
            "on-background": "#132116",
            "on-secondary-fixed-variant": "#005313",
            "secondary": "#006e1c",
            "secondary-fixed": "#dcfce7",
            "surface-variant": "#d9e3f6",
            "surface-dim": "#d0dbed",
            "primary": "#1b5e20",
            "primary-dark": "#144618",
            "background": "#fafbf9",
            "inverse-primary": "#91d78a",
            "primary-container": "#1b5e20",
            "on-primary-fixed": "#002203",
            "tertiary": "#553300",
            "surface-bright": "#ffffff",
            "surface": "#ffffff",
            "surface-container-highest": "#d9e3f6",
            "secondary-fixed-dim": "#78dc77",
            "on-primary-fixed-variant": "#0c5216",
            "on-primary": "#ffffff",
            "surface-container-lowest": "#ffffff",
            "on-secondary-container": "#15803d",
            "on-primary-container": "#90d689",
            "secondary-container": "#e8f5e9",
            "tertiary-fixed-dim": "#ffb95f",
            "on-tertiary": "#ffffff",
            "on-secondary-fixed": "#002204",
            "on-error-container": "#93000a"
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            inter: ['"Inter"', 'sans-serif']
          }
        }
      }
    }

let basePrice = 4.99;
  let currentQty = 1;

  function updatePricingDisplay() {
    const total = (basePrice * currentQty).toFixed(2);
    const cartLabel = document.getElementById('add-to-cart-label');
    if (cartLabel) {
      cartLabel.innerHTML = `Add to Basket &bull; $${total}`;
    }
  }

  function alterQuantity(delta) {
    currentQty = Math.max(1, currentQty + delta);
    document.getElementById('qty-val').innerText = currentQty;
    updatePricingDisplay();
  }

  function selectRipeness(btn, label, price, unitCost) {
    document.querySelectorAll('.ripeness-tab').forEach(el => {
      el.classList.remove('active', 'bg-primary', 'text-on-primary', 'shadow-xs');
      el.classList.add('bg-surface-container-high', 'text-on-surface');
      const sub = el.querySelector('span');
      if (sub) {
        sub.classList.remove('text-on-primary-container');
        sub.classList.add('text-outline');
      }
    });

    btn.classList.add('active', 'bg-primary', 'text-on-primary', 'shadow-xs');
    btn.classList.remove('bg-surface-container-high', 'text-on-surface');
    const activeSub = btn.querySelector('span');
    if (activeSub) {
      activeSub.classList.remove('text-outline');
      activeSub.classList.add('text-on-primary-container');
    }

    basePrice = price;
    document.getElementById('selected-ripeness-label').innerText = label;
    document.getElementById('current-price').innerText = `$${price.toFixed(2)}`;
    document.getElementById('unit-price').innerText = `$${unitCost.toFixed(2)} / each`;
    updatePricingDisplay();
  }

  function selectThumbnail(idx, newSrc) {
    const buttons = document.querySelectorAll('.thumb-btn');
    buttons.forEach((b, i) => {
      if (i === idx) {
        b.classList.add('ring-2', 'ring-primary', 'opacity-100');
        b.classList.remove('opacity-70');
      } else {
        b.classList.remove('ring-2', 'ring-primary', 'opacity-100');
        b.classList.add('opacity-70');
      }
    });
  }

  function triggerCartAction() {
    const total = (basePrice * currentQty).toFixed(2);
    if (typeof showToast === 'function') {
      showToast(`Added ${currentQty}x Hass Avocados ($${total}) to your basket!`);
    }
  }

  function triggerInstantCheckout() {
    if (typeof showToast === 'function') {
      showToast('Redirecting to 1-Click Express Checkout...');
    }
  }

  function toggleQuickWishlist(btn) {
    btn.classList.toggle('text-error');
    const isSaved = btn.classList.contains('text-error');
    if (typeof showToast === 'function') {
      showToast(isSaved ? 'Saved to your FreshMart Favorites!' : 'Removed from Favorites.');
    }
  }

  function shareQuickProduct() {
    if (navigator.share) {
      navigator.share({
        title: 'Organic Hass Avocados at FreshMart',
        url: window.location.href
      }).catch(() => {});
    } else {
      if (typeof showToast === 'function') {
        showToast('Product link copied to clipboard!');
      }
    }
  }

let wishlistCount = 3;

    function updatePrice(val) {
      document.getElementById('priceValue').innerText = '$' + parseFloat(val).toFixed(2);
      document.getElementById('priceRange').value = val;
    }

    function setViewMode(mode) {
      const grid = document.getElementById('product-grid');
      const gridBtn = document.getElementById('grid-view-btn');
      const listBtn = document.getElementById('list-view-btn');

      if (mode === 'list') {
        grid.className = 'grid grid-cols-1 gap-4 transition-all';
        gridBtn.className = 'p-1.5 rounded-lg text-gray-500 hover:text-[#1b5e20] transition-colors';
        listBtn.className = 'p-1.5 rounded-lg bg-white text-[#1b5e20] shadow-xs';
      } else {
        grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 transition-all';
        gridBtn.className = 'p-1.5 rounded-lg bg-white text-[#1b5e20] shadow-xs';
        listBtn.className = 'p-1.5 rounded-lg text-gray-500 hover:text-[#1b5e20] transition-colors';
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
      badge.innerText = wishlistCount;
    }

    function addToCart(name, price, unit) {
      showToast(`Added ${name} (${unit}) to your basket!`);
    }

    let toastTimer;
    function showToast(msg) {
      const toast = document.getElementById('actionToast');
      const text = document.getElementById('toastMessage');
      text.innerText = msg;
      toast.classList.remove('opacity-0', 'translate-y-2');
      toast.classList.add('opacity-100', 'translate-y-0');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => {
        toast.classList.remove('opacity-100', 'translate-y-0');
        toast.classList.add('opacity-0', 'translate-y-2');
      }, 2500);
    }