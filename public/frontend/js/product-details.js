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

function switchMainImage(btn, altText) {
      document.querySelectorAll('.thumbnail-btn').forEach(el => {
        el.classList.remove('ring-2', 'ring-primary', 'opacity-100');
        el.classList.add('opacity-70');
      });
      btn.classList.add('ring-2', 'ring-primary', 'opacity-100');
      btn.classList.remove('opacity-70');

      const mainImg = document.getElementById('mainProductPhoto');
      mainImg.setAttribute('data-alt', altText);
      mainImg.classList.add('scale-95');
      setTimeout(() => {
        mainImg.classList.remove('scale-95');
      }, 150);
    }

    function selectWeight(btn, label, price, original, unit) {
      document.querySelectorAll('#weightSelectorGroup .weight-pill').forEach(el => {
        el.classList.remove('active', 'bg-primary-container', 'text-on-primary');
        el.classList.add('bg-surface-container-lowest', 'text-on-surface');
      });
      btn.classList.add('active', 'bg-primary-container', 'text-on-primary');
      btn.classList.remove('bg-surface-container-lowest', 'text-on-surface');

      document.getElementById('selectedWeightLabel').innerText = label;
      document.getElementById('currentPriceDisplay').innerText = '$' + price.toFixed(2);
      document.getElementById('originalPriceDisplay').innerText = '$' + original.toFixed(2);
      document.getElementById('unitPriceDisplay').innerText = unit;
    }

    function selectThickness(btn) {
      document.querySelectorAll('.thickness-btn').forEach(el => {
        el.classList.remove('bg-primary-container', 'text-on-primary');
        el.classList.add('bg-surface-container-lowest', 'text-on-surface');
      });
      btn.classList.add('bg-primary-container', 'text-on-primary');
      btn.classList.remove('bg-surface-container-lowest', 'text-on-surface');
    }

    function adjustProductQty(delta) {
      const input = document.getElementById('detailQtyInput');
      let val = parseInt(input.value) || 1;
      val = Math.max(1, Math.min(24, val + delta));
      input.value = val;
    }

    function switchSpecTab(btn, targetId) {
      document.querySelectorAll('.spec-tab-btn').forEach(el => {
        el.classList.remove('active', 'bg-primary-container', 'text-on-primary', 'shadow-sm');
        el.classList.add('bg-surface-container-low', 'text-on-surface-variant');
      });
      btn.classList.add('active', 'bg-primary-container', 'text-on-primary', 'shadow-sm');
      btn.classList.remove('bg-surface-container-low', 'text-on-surface-variant');

      document.querySelectorAll('.spec-tab-content').forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('block');
      });
      const activeContent = document.getElementById(targetId);
      if (activeContent) {
        activeContent.classList.remove('hidden');
        activeContent.classList.add('block');
      }
    }

    function toggleMainWishlist(btn) {
      const icon = btn.querySelector('.material-symbols-outlined');
      if (icon.style.fontVariationSettings && icon.style.fontVariationSettings.includes("'FILL' 1")) {
        icon.style.fontVariationSettings = "'FILL' 0";
        btn.classList.remove('text-error');
        showToast('Removed tenderloin steak from wishlist.');
      } else {
        icon.style.fontVariationSettings = "'FILL' 1";
        btn.classList.add('text-error');
        showToast('Added tenderloin steak to your wishlist!');
      }
    }

    function triggerDetailAddToCart() {
      const qty = parseInt(document.getElementById('detailQtyInput').value) || 1;
      const weight = document.getElementById('selectedWeightLabel').innerText;
      const priceText = document.getElementById('currentPriceDisplay').innerText.replace('$', '');
      const price = parseFloat(priceText);

      for (let i = 0; i < qty; i++) {
        addToCart('Angus Beef Tenderloin (' + weight.split('/')[0].trim() + ')', price, 'portion');
      }
    }

    function buyNowDirect() {
      triggerDetailAddToCart();
      const cartDrawerEl = document.getElementById('cartDrawer');
      if (cartDrawerEl && window.bootstrap) {
        const offcanvas = new bootstrap.Offcanvas(cartDrawerEl);
        offcanvas.show();
      }
    }

    function claimOffer() {
      showToast('Offer claimed! Use promo code FREERIBEYE at checkout.');
    }

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