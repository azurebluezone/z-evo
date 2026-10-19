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

// Filter stories by category pills
  function selectStoryCategory(category, buttonElement) {
    document.querySelectorAll('.category-chip-btn').forEach(btn => {
      btn.classList.remove('bg-primary-container', 'text-on-primary', 'shadow-sm');
      btn.classList.add('bg-surface-container-lowest', 'text-on-surface');
    });

    buttonElement.classList.remove('bg-surface-container-lowest', 'text-on-surface');
    buttonElement.classList.add('bg-primary-container', 'text-on-primary', 'shadow-sm');

    const cards = document.querySelectorAll('.blog-card');
    let visibleCount = 0;

    cards.forEach(card => {
      const cardCat = card.getAttribute('data-category');
      if (category === 'All Stories' || cardCat === category) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    const countLabel = document.getElementById('articlesFoundCount');
    if (countLabel) {
      countLabel.textContent = `Showing ${visibleCount} of 28 articles`;
    }
  }

  // Sidebar Topic filter bridge
  function filterTopic(topicName, event) {
    event.preventDefault();
    if (window.showToast) {
      window.showToast(`Filtering stories by "${topicName}"`);
    } else {
      alert(`Filtering stories by "${topicName}"`);
    }
  }

  // Live article title and content search
  function searchArticles(event) {
    const query = event.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.blog-card');
    let matches = 0;

    cards.forEach(card => {
      const text = card.textContent.toLowerCase();
      if (text.includes(query)) {
        card.style.display = 'flex';
        matches++;
      } else {
        card.style.display = 'none';
      }
    });

    const countLabel = document.getElementById('articlesFoundCount');
    if (countLabel) {
      countLabel.textContent = `Showing ${matches} match${matches === 1 ? '' : 'es'} for "${query}"`;
    }
  }

  // Toggle Story Bookmark icon
  function toggleStoryBookmark(btn, title) {
    const icon = btn.querySelector('.material-symbols-outlined');
    const isFilled = icon.style.fontVariationSettings && icon.style.fontVariationSettings.includes("'FILL' 1");
    
    if (isFilled) {
      icon.style.fontVariationSettings = "'FILL' 0";
      icon.classList.remove('text-primary');
      if (window.showToast) window.showToast(`Removed "${title}" from reading list.`);
    } else {
      icon.style.fontVariationSettings = "'FILL' 1";
      icon.classList.add('text-primary');
      if (window.showToast) window.showToast(`Saved "${title}" to your offline reading list!`);
    }
  }

  // Pagination handler
  function changeBlogPage(pageNum) {
    if (window.showToast) {
      window.showToast(`Loading stories for page ${pageNum}...`);
    }
    window.scrollTo({ top: 400, behavior: 'smooth' });
  }

  function loadMoreArticles() {
    if (window.showToast) {
      window.showToast("Fetching 4 additional farm reports & recipes...");
    }
  }

  // AI Recipe Prompt submission
  function handleAiRecipePrompt(e) {
    e.preventDefault();
    const responseBox = document.getElementById('aiChefResponse');
    if (responseBox) {
      responseBox.classList.remove('hidden');
      if (window.showToast) {
        window.showToast("AI Chef generated a zero-waste recipe recommendation!");
      }
    }
  }

  function clearAiPrompt() {
    const input = document.getElementById('aiRecipeInput');
    if (input) {
      input.value = '';
      input.focus();
    }
  }

  // Journal newsletter subscription
  function handleJournalSubscribe(e) {
    e.preventDefault();
    const input = document.getElementById('subscriberEmail');
    const emailVal = input ? input.value : 'reader';
    if (window.showToast) {
      window.showToast(`Welcome! Your 15% promo code has been sent to ${emailVal}`);
    } else {
      alert(`Welcome! Your 15% discount code has been sent to ${emailVal}`);
    }
    if (input) input.value = '';
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