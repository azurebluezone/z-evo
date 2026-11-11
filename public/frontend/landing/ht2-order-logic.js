// split-source: ht2.html | order-logic

    // Package selector function updating UI state, summaries, and totals
    function selectPackage(type, price) {
      // Manage visual pill borders
      const options = document.querySelectorAll('.pack-option');
      options.forEach(opt => opt.classList.remove('active-pack'));
      
      const eventTarget = window.event.currentTarget;
      if (eventTarget) {
        eventTarget.classList.add('active-pack');
        const radio = eventTarget.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
      }

      // Format Bengali Numerals
      const banglaDigits = {
        '0': '০', '1': '১', '2': '২', '3': '৩', '4': '৪',
        '5': '৫', '6': '৬', '7': '৭', '8': '৮', '9': '৯'
      };
      
      function toBengali(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",").split('').map(d => banglaDigits[d] || d).join('');
      }

      const formattedPrice = `৳${toBengali(price)}`;
      
      // Update Summary DOM
      const subtotalEl = document.getElementById('summary-subtotal');
      const totalEl = document.getElementById('summary-total');
      const stickyPriceEl = document.getElementById('sticky-price-display');
      const titleEl = document.getElementById('summary-pack-title');

      if (subtotalEl) subtotalEl.textContent = formattedPrice;
      if (totalEl) totalEl.textContent = formattedPrice;
      if (stickyPriceEl) stickyPriceEl.textContent = formattedPrice;

      if (titleEl) {
        if (type === 'combo') {
          titleEl.textContent = 'ধামাকা কম্বো প্যাক (২টি ওয়ালেট)';
        } else if (type === 'brown') {
          titleEl.textContent = 'Jeep Chocolate Brown (1 Pc)';
        } else {
          titleEl.textContent = 'Jeep Classic Black (1 Pc)';
        }
      }
    }

    // Form Submission Handling with SweetAlert2 Toast Feedback
    function handleOrderSubmit(e) {
      e.preventDefault();
      
      const name = document.getElementById('customer_name').value.trim();
      const phone = document.getElementById('customer_phone').value.trim();
      const address = document.getElementById('customer_address').value.trim();

      if (!name || !phone || !address) {
        Swal.fire({
          icon: 'warning',
          title: 'সবগুলো তথ্য পূরণ করুন',
          text: 'নাম, মোবাইল নম্বর ও ঠিকানা দেয়া আবশ্যক।',
          confirmButtonColor: '#DC2626'
        });
        return;
      }

      // Simulate Successful Order Placement
      Swal.fire({
        icon: 'success',
        title: 'ধন্যবাদ! আপনার অর্ডারটি গৃহীত হয়েছে।',
        html: `<p class="text-sm text-slate-600 mt-2">আমাদের কাস্টমার কেয়ার প্রতিনিধি কিছুক্ষণের মধ্যে <b>${phone}</b> নম্বরে কল করে অর্ডার কনফার্ম করবেন।</p>`,
        confirmButtonText: 'ঠিক আছে',
        confirmButtonColor: '#059669',
        customClass: {
          popup: 'rounded-2xl'
        }
      }).then(() => {
        document.getElementById('orderForm').reset();
        selectPackage('black', 1025);
      });
    }

    // Live Countdown Timer logic
    (function initCountdown() {
      let duration = (4 * 3600) + (48 * 60) + 32; // 4 hours 48 mins 32 secs
      const timerDisplay = document.getElementById('countdown-timer');
      
      setInterval(function () {
        if (duration <= 0) {
          duration = 5 * 3600; // loop reset
        }
        duration--;
        
        const hours = Math.floor(duration / 3600);
        const minutes = Math.floor((duration % 3600) / 60);
        const seconds = Math.floor(duration % 60);

        const pad = (n) => (n < 10 ? '0' + n : n);
        
        if (timerDisplay) {
          timerDisplay.textContent = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        }
      }, 1000);
    })();
  