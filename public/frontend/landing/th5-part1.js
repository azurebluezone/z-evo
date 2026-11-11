// split-source: th5.html | part

    // Package State
    let currentProduct = {
      id: 'tea1kg',
      name: '১ কেজি শ্রীমঙ্গল স্পেশাল চা',
      price: 650
    };
    let deliveryCharge = 0;

    function selectPackage(id, price, name) {
      currentProduct = { id, price, name };
      
      // Update UI selection
      document.querySelectorAll('.zombie-package-option').forEach(el => el.classList.remove('active'));
      event.currentTarget.classList.add('active');
      event.currentTarget.querySelector('.zombie-package-radio').checked = true;

      updateSummary();
    }

    function setDelivery(charge, label) {
      deliveryCharge = charge;
      updateSummary();
    }

    function updateSummary() {
      const total = currentProduct.price + deliveryCharge;
      document.getElementById('summaryProdName').textContent = currentProduct.name;
      document.getElementById('summaryProdPrice').textContent = currentProduct.price + ' ৳';
      document.getElementById('summaryTotal').textContent = total + ' ৳';
      document.getElementById('btnTotal').textContent = total + ' ৳';
      document.getElementById('mobileStickyPrice').textContent = total + ' ৳';
    }

    function toggleFaq(btn) {
      const card = btn.closest('.zombie-faq-card');
      card.classList.toggle('open');
    }

    function handleOrderSubmit(e) {
      e.preventDefault();
      const name = document.getElementById('custName').value.trim();
      const phone = document.getElementById('custPhone').value.trim();
      const address = document.getElementById('custAddress').value.trim();
      const total = currentProduct.price + deliveryCharge;

      if (!name || !phone || !address) {
        Swal.fire({
          icon: 'warning',
          title: 'তথ্য অসম্পূর্ণ',
          text: 'অনুগ্রহ করে আপনার নাম, মোবাইল নম্বর এবং সম্পূর্ণ ঠিকানা প্রদান করুন।',
          confirmButtonColor: '#15803d'
        });
        return;
      }

      Swal.fire({
        icon: 'success',
        title: 'অর্ডার সফলভাবে গ্রহণ করা হয়েছে!',
        html: `
          <div style="text-align: left; background: #f0fdf4; padding: 16px; border-radius: 10px; margin-top: 10px;">
            <p class="mb-1"><strong>গ্রাহকের নাম:</strong> ${name}</p>
            <p class="mb-1"><strong>ফোন নম্বর:</strong> ${phone}</p>
            <p class="mb-1"><strong>পণ্য:</strong> ${currentProduct.name}</p>
            <p class="mb-1"><strong>সর্বমোট বিল:</strong> ${total} ৳ (ক্যাশ অন ডেলিভারি)</p>
            <p class="mb-0 text-success fw-bold"><i class="fa-solid fa-phone-volume me-1"></i> আমাদের প্রতিনিধি শীঘ্রই আপনাকে কল করে ডেলিভারি নিশ্চিত করবেন।</p>
          </div>
        `,
        confirmButtonText: 'ধন্যবাদ',
        confirmButtonColor: '#15803d'
      }).then(() => {
        document.getElementById('zombieOrderForm').reset();
      });
    }

    // Urgency Countdown Timer
    let h = 6, m = 45, s = 18;
    setInterval(() => {
      s--;
      if (s < 0) { s = 59; m--; }
      if (m < 0) { m = 59; h--; }
      if (h < 0) { h = 12; }
      document.getElementById('hours').textContent = String(h).padStart(2, '0');
      document.getElementById('minutes').textContent = String(m).padStart(2, '0');
      document.getElementById('seconds').textContent = String(s).padStart(2, '0');
    }, 1000);
  