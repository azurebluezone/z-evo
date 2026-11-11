// split-source: th4.html | part

    let currentPkg = {
      qty: 1,
      price: 900,
      title: '১ কেজি বিচি মুক্ত সিডলেস খেজুর'
    };
    let currentDeliveryCharge = 0;

    function selectPackage(qty, price, title, element) {
      document.querySelectorAll('.zombie-pkg-option').forEach(el => el.classList.remove('active'));
      element.classList.add('active');
      const radio = element.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;

      currentPkg = { qty, price, title };
      updateSummary();
    }

    function selectDelivery(charge, areaName, element) {
      document.querySelectorAll('.zombie-del-card').forEach(el => el.classList.remove('active'));
      element.classList.add('active');
      const radio = element.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;

      currentDeliveryCharge = charge;
      updateSummary();
    }

    function updateSummary() {
      const total = currentPkg.price + currentDeliveryCharge;
      document.getElementById('summary-prod-title').innerText = currentPkg.title;
      document.getElementById('summary-prod-price').innerText = currentPkg.price.toLocaleString('bn-BD') + ' ৳';
      document.getElementById('summary-total-price').innerText = total.toLocaleString('bn-BD') + ' ৳';
      document.getElementById('submit-btn-text').innerText = `অর্ডার কনফার্ম করুন (${total.toLocaleString('bn-BD')} ৳)`;

      const mobileBtn = document.querySelector('.zombie-mobile-btn');
      if (mobileBtn) {
        mobileBtn.innerHTML = `<i class="fa-solid fa-cart-shopping"></i> এখনই অর্ডার করুন (${total.toLocaleString('bn-BD')} ৳)`;
      }
    }

    function handlePlaceOrder() {
      const name = document.getElementById('cust-name').value.trim();
      const phone = document.getElementById('cust-phone').value.trim();
      const address = document.getElementById('cust-address').value.trim();

      if (!name) {
        Swal.fire({
          icon: 'warning',
          title: 'নাম লিখুন',
          text: 'দয়া করে আপনার সম্পূর্ণ নাম প্রদান করুন।',
          confirmButtonColor: '#E11D48'
        });
        return;
      }

      if (!phone || phone.length < 11) {
        Swal.fire({
          icon: 'warning',
          title: 'সঠিক মোবাইল নম্বর দিন',
          text: 'আপনার ১১ ডিজিটের সচল মোবাইল নাম্বার লিখুন।',
          confirmButtonColor: '#E11D48'
        });
        return;
      }

      if (!address || address.length < 5) {
        Swal.fire({
          icon: 'warning',
          title: 'ঠিকানা দিন',
          text: 'পণ্য পৌঁছে দেওয়ার জন্য সম্পূর্ণ ঠিকানা লিখুন।',
          confirmButtonColor: '#E11D48'
        });
        return;
      }

      const total = currentPkg.price + currentDeliveryCharge;

      Swal.fire({
        icon: 'success',
        title: 'ধন্যবাদ! অর্ডার সফল হয়েছে 🎉',
        html: `
          <div style="text-align: left; background: #F8FAFC; padding: 15px; border-radius: 8px; font-size: 14px; margin-top: 10px;">
            <p style="margin-bottom: 6px;"><strong>গ্রাহক:</strong> ${name}</p>
            <p style="margin-bottom: 6px;"><strong>ফোন:</strong> ${phone}</p>
            <p style="margin-bottom: 6px;"><strong>প্যাকেজ:</strong> ${currentPkg.title}</p>
            <p style="margin-bottom: 6px;"><strong>সর্বমোট বিল:</strong> ${total.toLocaleString('bn-BD')} ৳ (ক্যাশ অন ডেলিভারি)</p>
            <p style="margin-bottom: 0;"><strong>ডেলিভারি ঠিকানা:</strong> ${address}</p>
          </div>
          <p style="margin-top: 15px; color: #15803D; font-size: 13px; font-weight: 600;">
            আমাদের প্রতিনিধি দ্রুত কল করে আপনার অর্ডারটি কনফার্ম করবেন।
          </p>
        `,
        confirmButtonText: 'ঠিক আছে',
        confirmButtonColor: '#15803D'
      });
    }

    function toggleFaq(btn) {
      const content = btn.nextElementSibling;
      const icon = btn.querySelector('i');
      if (content.style.display === 'none' || !content.style.display) {
        content.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
      } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
      }
    }

    // Countdown Timer Logic
    let totalSecs = 8 * 3600 + 48 * 60 + 35;
    setInterval(() => {
      if (totalSecs > 0) {
        totalSecs--;
        const h = Math.floor(totalSecs / 3600);
        const m = Math.floor((totalSecs % 3600) / 60);
        const s = totalSecs % 60;
        document.getElementById('cd-hours').innerText = h < 10 ? '0' + h : h;
        document.getElementById('cd-mins').innerText = m < 10 ? '0' + m : m;
        document.getElementById('cd-secs').innerText = s < 10 ? '0' + s : s;
      }
    }, 1000);
  