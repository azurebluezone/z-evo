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

function toggleAccordion(btn) {
      const isExpanded = btn.getAttribute('aria-expanded') === 'true';
      const content = btn.nextElementSibling;
      const chevron = btn.querySelector('.icon-chevron');

      // Close other accordions for clean single-focus experience
      document.querySelectorAll('#faqAccordionList .accordion-content').forEach(el => {
        if (el !== content) {
          el.style.maxHeight = '0px';
          el.classList.remove('opacity-100');
          el.classList.add('opacity-0');
          el.previousElementSibling.setAttribute('aria-expanded', 'false');
          const otherChevron = el.previousElementSibling.querySelector('.icon-chevron');
          if (otherChevron) otherChevron.style.transform = 'rotate(0deg)';
        }
      });

      if (!isExpanded) {
        btn.setAttribute('aria-expanded', 'true');
        content.style.maxHeight = content.scrollHeight + 32 + 'px';
        content.classList.remove('opacity-0');
        content.classList.add('opacity-100');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
      } else {
        btn.setAttribute('aria-expanded', 'false');
        content.style.maxHeight = '0px';
        content.classList.remove('opacity-100');
        content.classList.add('opacity-0');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
      }
    }

    function filterFaqCategory(category, buttonEl) {
      // Update Tab Styles
      const buttons = document.querySelectorAll('#accordionTabsContainer .faq-tab-btn');
      buttons.forEach(btn => {
        btn.classList.remove('bg-primary', 'text-on-primary', 'shadow-sm');
        btn.classList.add('bg-surface-container-lowest', 'text-on-surface');
      });

      if (buttonEl) {
        buttonEl.classList.remove('bg-surface-container-lowest', 'text-on-surface');
        buttonEl.classList.add('bg-primary', 'text-on-primary', 'shadow-sm');
      }

      // Filter FAQ items
      const items = document.querySelectorAll('#faqAccordionList .faq-item');
      let visibleCount = 0;

      items.forEach(item => {
        const itemCat = item.getAttribute('data-category');
        if (category === 'all' || itemCat === category) {
          item.classList.remove('hidden');
          visibleCount++;
        } else {
          item.classList.add('hidden');
        }
      });

      const noResults = document.getElementById('noFaqResults');
      if (visibleCount === 0) {
        noResults.classList.remove('hidden');
      } else {
        noResults.classList.add('hidden');
      }
    }

    function selectAccordionTab(category) {
      const targetBtn = document.querySelector(`#accordionTabsContainer button[data-tab="${category}"]`);
      if (targetBtn) {
        filterFaqCategory(category, targetBtn);
      }
      const section = document.getElementById('faqAccordionSection');
      if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    function handleLiveSearch(query) {
      const q = query.trim().toLowerCase();
      const clearBtn = document.getElementById('searchClearBtn');
      if (q.length > 0) {
        clearBtn.classList.remove('hidden');
      } else {
        clearBtn.classList.add('hidden');
      }

      const items = document.querySelectorAll('#faqAccordionList .faq-item');
      let matchedCount = 0;

      items.forEach(item => {
        const keywords = (item.getAttribute('data-keywords') || '').toLowerCase();
        const text = item.innerText.toLowerCase();

        if (q === '' || keywords.includes(q) || text.includes(q)) {
          item.classList.remove('hidden');
          matchedCount++;
        } else {
          item.classList.add('hidden');
        }
      });

      const noResults = document.getElementById('noFaqResults');
      if (matchedCount === 0 && q.length > 0) {
        noResults.classList.remove('hidden');
      } else {
        noResults.classList.add('hidden');
      }
    }

    function quickFilterSearch(tag) {
      const searchInput = document.getElementById('faqSearchInput');
      if (searchInput) {
        searchInput.value = tag;
        handleLiveSearch(tag);
        const section = document.getElementById('faqAccordionSection');
        if (section) {
          section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    }

    function clearSearch() {
      const searchInput = document.getElementById('faqSearchInput');
      if (searchInput) searchInput.value = '';
      const clearBtn = document.getElementById('searchClearBtn');
      if (clearBtn) clearBtn.classList.add('hidden');
      filterFaqCategory('all', document.querySelector('#accordionTabsContainer button[data-tab="all"]'));
    }

    function simulateOrderTrack() {
      const input = document.getElementById('trackingInput');
      const val = input ? input.value.trim() : '';
      if (!val) {
        showToast('Please enter an order ID (e.g., FM-8942-NY)');
        return;
      }
      showToast(`Order ${val}: En route in Cold-Chain Van #09. Temperature: 37.4°F. ETA: 22 mins.`);
    }

    function triggerPhotoCreditFlow() {
      showToast('Camera upload initialized. Optical Scan ready for Instant Ripeness verification.');
    }

    function triggerLiveChat() {
      showToast('Connecting you with Brooklyn Hub Agronomist Sarah (< 1 min wait)...');
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