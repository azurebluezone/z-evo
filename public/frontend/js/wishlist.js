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

// Wishlist dynamic interactivity
  let currentItemsCount = 8;

  function removeWishlistItem(buttonElement, itemName) {
    const card = buttonElement.closest('.wishlist-card');
    if (!card) return;
    
    // Smooth disappearance
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
      card.remove();
      currentItemsCount--;
      updateWishlistCounters();
      if (typeof showToast === 'function') {
        showToast(`Removed "${itemName}" from your wishlist.`);
      }
      
      const remainingCards = document.querySelectorAll('.wishlist-card');
      if (remainingCards.length === 0) {
        document.getElementById('wishlistContainer').classList.add('hidden');
        document.getElementById('emptyWishlistState').classList.remove('hidden');
        document.getElementById('emptyWishlistState').classList.add('flex');
      }
    }, 250);
  }

  function updateWishlistCounters() {
    const counterPill = document.getElementById('savedCountPill');
    const headerWishlistBadge = document.getElementById('wishlistCount');
    if (counterPill) counterPill.innerText = `${currentItemsCount} Saved Products`;
    if (headerWishlistBadge) headerWishlistBadge.innerText = currentItemsCount;
  }

  function addAllInStockToCart() {
    const inStockCards = document.querySelectorAll('.wishlist-card[data-instock="true"]');
    let addedCount = 0;

    inStockCards.forEach(card => {
      const name = card.getAttribute('data-name');
      const price = parseFloat(card.getAttribute('data-price'));
      if (name && price && typeof addToCart === 'function') {
        addToCart(name, price, 'Wishlist Item');
        addedCount++;
      }
    });

    if (typeof showToast === 'function') {
      showToast(`Added ${addedCount} farm-fresh items to your cart!`);
    }
  }

  function notifyRestock(productName) {
    if (typeof showToast === 'function') {
      showToast(`Restock alert set for ${productName}. We will email you!`);
    } else {
      alert(`Alert set for ${productName}!`);
    }
  }

  function shareWishlist() {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href);
      if (typeof showToast === 'function') {
        showToast('Wishlist link copied to clipboard!');
      } else {
        alert('Wishlist link copied!');
      }
    }
  }

  function clearAllWishlist() {
    if (confirm('Are you sure you want to clear all items from your wishlist?')) {
      document.querySelectorAll('.wishlist-card').forEach(c => c.remove());
      currentItemsCount = 0;
      updateWishlistCounters();
      document.getElementById('wishlistContainer').classList.add('hidden');
      document.getElementById('emptyWishlistState').classList.remove('hidden');
      document.getElementById('emptyWishlistState').classList.add('flex');
      if (typeof showToast === 'function') {
        showToast('Wishlist has been cleared.');
      }
    }
  }

  function filterWishlistCategory(category, activeBtn) {
    document.querySelectorAll('#categoryChips .category-chip').forEach(btn => {
      btn.classList.remove('bg-primary', 'text-on-primary', 'font-bold');
      btn.classList.add('bg-surface-container-lowest', 'text-on-surface');
    });
    activeBtn.classList.remove('bg-surface-container-lowest', 'text-on-surface');
    activeBtn.classList.add('bg-primary', 'text-on-primary', 'font-bold');

    const cards = document.querySelectorAll('.wishlist-card');
    cards.forEach(card => {
      const itemCat = card.getAttribute('data-category');
      const isSale = card.getAttribute('data-sale') === 'true';

      if (category === 'all') {
        card.style.display = 'flex';
      } else if (category === 'on-sale') {
        card.style.display = isSale ? 'flex' : 'none';
      } else {
        card.style.display = (itemCat === category) ? 'flex' : 'none';
      }
    });
  }

  function toggleLayout(type) {
    const container = document.getElementById('wishlistContainer');
    const gridBtn = document.getElementById('viewGridBtn');
    const listBtn = document.getElementById('viewListBtn');

    if (type === 'list') {
      container.className = 'flex flex-col gap-4 transition-all duration-300';
      listBtn.className = 'p-1.5 rounded-full bg-primary text-on-primary';
      gridBtn.className = 'p-1.5 rounded-full text-on-surface-variant hover:text-on-surface';
    } else {
      container.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 transition-all duration-300';
      gridBtn.className = 'p-1.5 rounded-full bg-primary text-on-primary';
      listBtn.className = 'p-1.5 rounded-full text-on-surface-variant hover:text-on-surface';
    }
  }

  function sortWishlistItems(order) {
    const container = document.getElementById('wishlistContainer');
    const cards = Array.from(container.querySelectorAll('.wishlist-card'));

    cards.sort((a, b) => {
      const priceA = parseFloat(a.getAttribute('data-price') || '0');
      const priceB = parseFloat(b.getAttribute('data-price') || '0');

      if (order === 'price-asc') return priceA - priceB;
      if (order === 'price-desc') return priceB - priceA;
      return 0; // Default order
    });

    cards.forEach(card => container.appendChild(card));
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