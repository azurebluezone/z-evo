tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          "primary": "#00450d",
          "primary-container": "#1b5e20",
          "on-primary": "#ffffff",
          "secondary": "#006e1c",
          "secondary-container": "#e8f5e9",
          "on-secondary-container": "#004d13",
          "tertiary": "#744800",
          "tertiary-fixed": "#ffddb8",
          "tertiary-fixed-dim": "#ffb95f",
          "on-tertiary-fixed": "#2a1700",
          "surface": "#f8f9fa",
          "surface-container-lowest": "#ffffff",
          "surface-container-low": "#f3f5f2",
          "surface-container": "#e9ece6",
          "surface-container-high": "#e1e5dd",
          "surface-container-highest": "#d6ded1",
          "outline": "#717a6d",
          "outline-variant": "#dbe3d8",
          "on-surface": "#121c2a",
          "on-surface-variant": "#4d574b",
          "error": "#ba1a1a",
          "error-container": "#ffdad6"
        },
        fontFamily: {
          sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          body: ['"Inter"', 'sans-serif'],
        }
      }
    }
  }

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

    if (!container) return;

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
        itemNode.className = 'd-flex items-center justify-between gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs';
        itemNode.innerHTML = `
          <div class="flex-grow-1 min-w-0">
            <h6 class="font-bold text-slate-800 m-0 truncate">${item.name}</h6>
            <span class="text-[11px] text-slate-500">$${item.price.toFixed(2)} / ${item.unit}</span>
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

    if (cartCountBadge) cartCountBadge.innerText = totalItems;
    if (cartTotalHeader) cartTotalHeader.innerText = `$${totalAmount.toFixed(2)}`;
    if (drawerSubtotal) drawerSubtotal.innerText = `$${totalAmount.toFixed(2)}`;
    if (drawerTotal) drawerTotal.innerText = `$${totalAmount.toFixed(2)}`;
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

  function showToast(message) {
    const toastEl = document.getElementById('actionToast');
    const toastMsg = document.getElementById('toastMessage');
    if (!toastEl || !toastMsg) return;
    toastMsg.innerHTML = `<i class="bi bi-check-circle-fill text-base"></i> ${message}`;
    const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
    toast.show();
  }

  function selectCategory(category) {
    document.getElementById('searchCategoryBtn').innerText = category;
  }

  function filterTabClick(btn, filter) {
    document.querySelectorAll('#orderFilterTabs .filter-tab').forEach(t => {
      t.classList.remove('bg-emerald-800', 'text-white', 'shadow-sm');
      t.classList.add('bg-slate-100', 'text-slate-700');
    });
    btn.classList.remove('bg-slate-100', 'text-slate-700');
    btn.classList.add('bg-emerald-800', 'text-white', 'shadow-sm');

    const orders = document.querySelectorAll('.order-entry');
    orders.forEach(card => {
      const status = card.getAttribute('data-status');
      if (filter === 'all' || status === filter) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });
  }

  function searchOrders() {
    const query = document.getElementById('orderSearchInput').value.toLowerCase();
    const orders = document.querySelectorAll('.order-entry');
    orders.forEach(card => {
      const text = card.innerText.toLowerCase();
      if (text.includes(query)) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });
  }

  function reorderItems(orderId, count) {
    showToast(`Reordered ${count} items from Order #${orderId} to basket!`);
    addToCart('Pasture-Raised Eggs', 3.89, '12 Pack');
  }

  // Scroll to Top
  window.addEventListener('scroll', () => {
    const backBtn = document.getElementById('backToTopBtn');
    if (backBtn) {
      if (window.scrollY > 300) {
        backBtn.classList.add('show');
      } else {
        backBtn.classList.remove('show');
      }
    }
  });

  function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.addEventListener('DOMContentLoaded', () => {
    renderCart();
  });