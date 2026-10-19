tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#f0fdf4',
              100: '#dcfce7',
              200: '#bbf7d0',
              500: '#22c55e',
              600: '#16a34a',
              700: '#15803d',
              800: '#1b5e20',
              900: '#144618',
              accent: '#4caf50'
            },
            surface: {
              cream: '#fbfcf8',
              card: '#f4f6f0'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          }
        }
      }
    }

// State for shopping cart page
  const cartItemsData = {
    'item-1': { name: 'Grass-Fed Angus Beef Tenderloin Steak', unitPrice: 12.99, qty: 2 },
    'item-2': { name: 'Organic Hass Avocados', unitPrice: 4.99, qty: 1 },
    'item-3': { name: 'Pure Organic Whole Milk', unitPrice: 4.20, qty: 1 }
  };

  let activeDiscountPercent = 0.10; // FRESH10 promo active by default (10%)
  let activeCouponCode = 'FRESH10';
  const FREE_SHIPPING_THRESHOLD = 50.00;
  const STANDARD_SHIPPING_FEE = 4.99;
  const TAX_RATE = 0.08; // 8%

  function recalculateTotals() {
    let subtotal = 0;
    let totalItems = 0;

    // Iterate over active rows that are checked
    const rows = document.querySelectorAll('.cart-item-row');
    rows.forEach(row => {
      const id = row.getAttribute('data-id');
      const isChecked = row.querySelector('.item-checkbox').checked;
      if (isChecked && cartItemsData[id]) {
        const itemTotal = cartItemsData[id].unitPrice * cartItemsData[id].qty;
        subtotal += itemTotal;
        totalItems += cartItemsData[id].qty;
      }
    });

    // Update count labels
    const itemCountSpan = document.getElementById('cart-item-count');
    const summaryItemsCount = document.getElementById('summary-items-count');
    if (itemCountSpan) itemCountSpan.innerText = totalItems;
    if (summaryItemsCount) summaryItemsCount.innerText = totalItems;

    // Calculate discount
    const discountAmount = subtotal * activeDiscountPercent;
    const discountedSubtotal = Math.max(0, subtotal - discountAmount);

    // Calculate Tax
    const taxAmount = discountedSubtotal * TAX_RATE;

    // Check Free Shipping Threshold
    const isFreeShipping = subtotal >= FREE_SHIPPING_THRESHOLD;
    const shippingFee = (subtotal > 0 && !isFreeShipping) ? STANDARD_SHIPPING_FEE : 0.00;

    // Calculate Grand Total
    const grandTotal = subtotal > 0 ? (discountedSubtotal + taxAmount + shippingFee) : 0.00;

    // Update UI elements
    const subtotalEl = document.getElementById('summary-subtotal');
    const taxEl = document.getElementById('summary-tax');
    const shippingEl = document.getElementById('summary-shipping');
    const discountEl = document.getElementById('summary-discount');
    const grandTotalEl = document.getElementById('summary-grand-total');
    const discountRow = document.getElementById('discount-row');

    if (subtotalEl) subtotalEl.innerText = `$${subtotal.toFixed(2)}`;
    if (taxEl) taxEl.innerText = `$${taxAmount.toFixed(2)}`;
    
    if (shippingEl) {
      if (isFreeShipping && subtotal > 0) {
        shippingEl.innerHTML = `<span class="text-secondary font-bold">FREE</span>`;
      } else {
        shippingEl.innerText = `$${shippingFee.toFixed(2)}`;
      }
    }

    if (discountRow) {
      if (activeDiscountPercent > 0 && subtotal > 0) {
        discountRow.classList.remove('hidden');
        if (discountEl) discountEl.innerText = `-$${discountAmount.toFixed(2)}`;
      } else {
        discountRow.classList.add('hidden');
      }
    }

    if (grandTotalEl) grandTotalEl.innerText = `$${grandTotal.toFixed(2)}`;

    // Update Free Shipping Progress Bar
    updateShippingBar(subtotal);

    // If all items removed, show empty state
    if (Object.keys(cartItemsData).length === 0) {
      showEmptyCartState();
    }
  }

  function updateShippingBar(currentSubtotal) {
    const banner = document.getElementById('free-shipping-banner');
    const promptText = document.getElementById('shipping-prompt-text');
    const bar = document.getElementById('free-shipping-bar');
    const label = document.getElementById('shipping-percentage-label');
    
    if (!banner || !bar) return;

    if (currentSubtotal >= FREE_SHIPPING_THRESHOLD) {
      bar.style.width = '100%';
      bar.classList.add('bg-secondary');
      bar.classList.remove('bg-primary-container');
      if (label) label.innerText = 'Unlocked! 🎉';
      if (promptText) {
        promptText.innerHTML = `🎉 <span class="text-secondary font-bold">Congratulations!</span> You unlocked <span class="text-primary font-extrabold">FREE Climate-Neutral Delivery</span>.`;
      }
    } else {
      const remaining = (FREE_SHIPPING_THRESHOLD - currentSubtotal).toFixed(2);
      const pct = Math.min(100, Math.max(5, (currentSubtotal / FREE_SHIPPING_THRESHOLD) * 100));
      bar.style.width = `${pct}%`;
      bar.classList.remove('bg-secondary');
      bar.classList.add('bg-primary-container');
      if (label) label.innerText = `${Math.round(pct)}% Reached`;
      if (promptText) {
        promptText.innerHTML = `Add <span class="text-secondary font-bold">$${remaining}</span> more to unlock <span class="text-primary font-extrabold">100% FREE Climate-Neutral Delivery</span>!`;
      }
    }
  }

  function updateItemQuantity(itemId, change) {
    if (!cartItemsData[itemId]) return;

    const newQty = cartItemsData[itemId].qty + change;
    if (newQty <= 0) {
      removeCartItem(itemId);
      return;
    }

    cartItemsData[itemId].qty = newQty;
    const qtySpan = document.getElementById(`qty-${itemId}`);
    const rowTotalSpan = document.getElementById(`item-total-${itemId}`);

    if (qtySpan) qtySpan.innerText = newQty;
    if (rowTotalSpan) {
      const lineTotal = (cartItemsData[itemId].unitPrice * newQty).toFixed(2);
      rowTotalSpan.innerText = `$${lineTotal}`;
    }

    recalculateTotals();
    showToast(`Updated quantity for ${cartItemsData[itemId].name}`);
  }

  function removeCartItem(itemId) {
    const row = document.querySelector(`.cart-item-row[data-id="${itemId}"]`);
    if (row) {
      const itemName = cartItemsData[itemId] ? cartItemsData[itemId].name : 'item';
      delete cartItemsData[itemId];
      row.remove();
      recalculateTotals();
      showToast(`Removed "${itemName}" from your basket.`);
    }
  }

  function clearCart() {
    if (confirm('Are you sure you want to empty your entire shopping basket?')) {
      for (const key in cartItemsData) {
        delete cartItemsData[key];
      }
      const container = document.getElementById('cart-table-body');
      if (container) container.innerHTML = '';
      showEmptyCartState();
      recalculateTotals();
      showToast('All items cleared from your basket.');
    }
  }

  function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    recalculateTotals();
  }

  function showEmptyCartState() {
    const leftCol = document.getElementById('cart-left-col');
    const summaryCol = document.getElementById('order-summary-container');
    const banner = document.getElementById('free-shipping-banner');
    const emptyState = document.getElementById('empty-cart-state');

    if (leftCol) leftCol.classList.add('hidden');
    if (summaryCol) summaryCol.classList.add('hidden');
    if (banner) banner.classList.add('hidden');
    if (emptyState) emptyState.classList.remove('hidden');
  }

  function applyCustomCoupon() {
    const input = document.getElementById('coupon-input');
    if (!input) return;
    const code = input.value.trim().toUpperCase();
    if (code === 'HARVEST15') {
      activeDiscountPercent = 0.15;
      activeCouponCode = 'HARVEST15';
      showToast('Awesome! HARVEST15 (15% OFF) applied.');
    } else if (code === 'FRESH10') {
      activeDiscountPercent = 0.10;
      activeCouponCode = 'FRESH10';
      showToast('FRESH10 (10% OFF) applied.');
    } else if (code === '') {
      showToast('Please enter a coupon code.');
    } else {
      showToast(`Coupon code "${code}" is invalid or expired.`);
    }
    recalculateTotals();
  }

  function applyCouponTag(code) {
    const input = document.getElementById('coupon-input');
    if (input) input.value = code;
    applyCustomCoupon();
  }

  function removeCoupon() {
    activeDiscountPercent = 0;
    activeCouponCode = '';
    const input = document.getElementById('coupon-input');
    if (input) input.value = '';
    recalculateTotals();
    showToast('Promo code removed.');
  }

  function saveForLater(productName) {
    showToast(`Saved "${productName}" to your wishlist!`);
  }

  function refreshCartState() {
    recalculateTotals();
    showToast('Cart refreshed with latest stock & pricing.');
  }

  function quickAddCrossSell(name, price, unit) {
    if (typeof addToCart === 'function') {
      addToCart(name, price, unit);
    } else {
      showToast(`Added ${name} ($${price.toFixed(2)}) to your basket!`);
    }
  }

  function proceedToCheckout() {
    const count = Object.keys(cartItemsData).length;
    if (count === 0) {
      showToast('Your basket is empty!');
      return;
    }
    showToast('Directing you to secure SSL checkout...');
    setTimeout(() => {
      alert('Proceeding to checkout with 256-bit encryption.');
    }, 400);
  }

  function quickPay(provider) {
    showToast(`Initiating express payment via ${provider}...`);
  }

  // Toast Helper fallback if App Shell toast is not ready
  function showToast(msg) {
    const shellToast = document.getElementById('actionToast');
    const shellMsg = document.getElementById('toastMessage');
    if (shellToast && shellMsg && typeof bootstrap !== 'undefined') {
      shellMsg.innerHTML = `<span class="material-symbols-outlined text-base">check_circle</span> ${msg}`;
      const toast = new bootstrap.Toast(shellToast, { delay: 2500 });
      toast.show();
    } else {
      console.log('Cart Notice:', msg);
    }
  }

  // Initial calculation
  document.addEventListener('DOMContentLoaded', () => {
    recalculateTotals();
  });

// In-memory Shopping Cart State
    let cart = [
      { id: 1, name: 'Organic Hass Avocados', price: 4.99, unit: 'Pack of 3', quantity: 2 },
      { id: 2, name: 'Crisp Farm Broccoli', price: 2.49, unit: '500g bunch', quantity: 1 },
      { id: 3, name: 'Grass-fed Beef Steak', price: 12.99, unit: '450g cut', quantity: 1 }
    ];

    let wishlistCount = 3;

    function renderCart() {
      const container = document.getElementById('cartItemsContainer');
      const cartCountBadge = document.getElementById('cartCountBadge');
      const cartTotalHeader = document.getElementById('cartTotalHeader');
      const drawerSubtotal = document.getElementById('drawerSubtotal');
      const drawerTotal = document.getElementById('drawerTotal');

      container.innerHTML = '';
      let totalAmount = 0;
      let totalItems = 0;

      if (cart.length === 0) {
        container.innerHTML = `
          <div class="text-center py-8 text-gray-400">
            <i class="bi bi-basket text-4xl mb-2 d-inline-block"></i>
            <p class="text-xs">Your basket is currently empty.</p>
          </div>
        `;
      } else {
        cart.forEach((item, index) => {
          totalAmount += item.price * item.quantity;
          totalItems += item.quantity;

          const itemNode = document.createElement('div');
          itemNode.className = 'd-flex items-center justify-between gap-3 p-2 rounded-xl bg-gray-50 border border-gray-100 text-xs';
          itemNode.innerHTML = `
            <div class="flex-grow-1">
              <h6 class="font-bold text-gray-800 m-0">${item.name}</h6>
              <span class="text-[11px] text-gray-500">$${item.price.toFixed(2)} / ${item.unit}</span>
            </div>
            <div class="d-flex items-center gap-1">
              <button class="btn btn-outline-secondary btn-sm px-1.5 py-0 border-0 bg-white" onclick="updateQty(${index}, -1)">-</button>
              <span class="font-bold px-1">${item.quantity}</span>
              <button class="btn btn-outline-secondary btn-sm px-1.5 py-0 border-0 bg-white" onclick="updateQty(${index}, 1)">+</button>
            </div>
            <button class="text-red-500 hover:text-red-700 bg-transparent border-0 p-1" onclick="removeItem(${index})">
              <i class="bi bi-trash"></i>
            </button>
          `;
          container.appendChild(itemNode);
        });
      }

      cartCountBadge.innerText = totalItems;
      cartTotalHeader.innerText = `$${totalAmount.toFixed(2)}`;
      drawerSubtotal.innerText = `$${totalAmount.toFixed(2)}`;
      drawerTotal.innerText = `$${totalAmount.toFixed(2)}`;
    }

    function updateQty(index, change) {
      if (cart[index]) {
        cart[index].quantity += change;
        if (cart[index].quantity <= 0) {
          cart.splice(index, 1);
        }
        renderCart();
      }
    }

    function removeItem(index) {
      cart.splice(index, 1);
      renderCart();
      showToast('Item removed from basket.');
    }

    function addToCart(name, price, unit) {
      const existing = cart.find(item => item.name === name);
      if (existing) {
        existing.quantity += 1;
      } else {
        cart.push({ id: Date.now(), name, price, unit, quantity: 1 });
      }
      renderCart();
      showToast(`Added ${name} to your basket!`);
    }

    function toggleWishlist(btn, productName) {
      btn.classList.toggle('active');
      const badge = document.getElementById('wishlistCount');
      if (btn.classList.contains('active')) {
        wishlistCount++;
        showToast(`Saved ${productName} to your favorites!`);
      } else {
        wishlistCount = Math.max(0, wishlistCount - 1);
      }
      badge.innerText = wishlistCount;
    }

    function showToast(message) {
      const toastEl = document.getElementById('actionToast');
      const toastMsg = document.getElementById('toastMessage');
      toastMsg.innerHTML = `<i class="bi bi-check-circle-fill text-base"></i> ${message}`;
      const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
      toast.show();
    }

    function selectCategory(category) {
      document.getElementById('searchCategoryBtn').innerText = category;
    }

    function handleNewsletter(event) {
      event.preventDefault();
      showToast('Thank you! $15 discount code sent to your inbox.');
      event.target.reset();
    }

    function filterProducts(category, btn) {
      document.querySelectorAll('#productFilterButtons .filter-btn').forEach(b => {
        b.classList.remove('bg-emerald-800', 'text-white');
        b.classList.add('bg-gray-100', 'text-gray-700');
      });
      btn.classList.remove('bg-gray-100', 'text-gray-700');
      btn.classList.add('bg-emerald-800', 'text-white');

      const wrappers = document.querySelectorAll('.product-card-wrapper');
      wrappers.forEach(card => {
        if (category === 'all' || card.getAttribute('data-category') === category) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    }

    // Scroll to Top
    window.addEventListener('scroll', () => {
      const backBtn = document.getElementById('backToTopBtn');
      if (window.scrollY > 300) {
        backBtn.classList.add('show');
      } else {
        backBtn.classList.remove('show');
      }
    });

    function scrollToTop() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Initialize initial state
    document.addEventListener('DOMContentLoaded', () => {
      renderCart();
    });