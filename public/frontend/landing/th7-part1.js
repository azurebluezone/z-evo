// split-source: th7.html | part

    // State management
    const zombiePackages = [
      { name: "১০০% কম্বড কটন বেবি কম্বো সেট", price: 750 },
      { name: "রয়েল ক্রাউন জামদানি সিল্ক শাড়ি", price: 2250 },
      { name: "দুবাই চেরি জর্জেট কোটি বোরকা সেট", price: 1850 },
      { name: "ফ্যামিলি মেগা কম্বো (৩টি আইটেম)", price: 4500 }
    ];

    let zombieCurrentPkgIndex = 0;
    let zombieDeliveryCharge = 60;

    function zombieUpdateCalculation() {
      const pkg = zombiePackages[zombieCurrentPkgIndex];
      const subtotal = pkg.price;
      // If mega combo, free delivery
      const del = (zombieCurrentPkgIndex === 3) ? 0 : zombieDeliveryCharge;
      const total = subtotal + del;

      document.getElementById('zombieSummaryPkgName').innerText = pkg.name;
      document.getElementById('zombieSummarySubtotal').innerText = subtotal.toLocaleString('bn-BD') + ' ৳';
      document.getElementById('zombieSummaryDelivery').innerText = del === 0 ? '০ ৳ (ফ্রি)' : del.toLocaleString('bn-BD') + ' ৳';
      document.getElementById('zombieSummaryTotal').innerText = total.toLocaleString('bn-BD') + ' ৳';
      
      const stickyElem = document.getElementById('zombieStickyPrice');
      if (stickyElem) {
        stickyElem.innerText = subtotal.toLocaleString('bn-BD') + ' ৳';
      }
    }

    function zombieSelectPkg(index) {
      zombieCurrentPkgIndex = index;
      const cards = document.querySelectorAll('.zombie-package-card');
      cards.forEach((c, idx) => {
        if (idx === index) {
          c.classList.add('selected');
        } else {
          c.classList.remove('selected');
        }
      });
      zombieUpdateCalculation();
    }

    function zombieSelectDel(charge, elem) {
      zombieDeliveryCharge = charge;
      const cards = document.querySelectorAll('.zombie-del-card');
      cards.forEach(c => {
        c.classList.remove('selected');
        const icon = c.querySelector('i');
        if (icon) {
          icon.className = 'fa-regular fa-circle';
        }
      });
      elem.classList.add('selected');
      const activeIcon = elem.querySelector('i');
      if (activeIcon) {
        activeIcon.className = 'fa-solid fa-circle-dot';
      }
      zombieUpdateCalculation();
    }

    function zombieFilter(category, btn) {
      const buttons = document.querySelectorAll('.zombie-pill-btn');
      buttons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const items = document.querySelectorAll('.zombie-product-card');
      items.forEach(item => {
        if (category === 'all' || item.getAttribute('data-category') === category) {
          item.style.display = 'flex';
        } else {
          item.style.display = 'none';
        }
      });
    }

    function zombieToggleFaq(questionElem) {
      const answer = questionElem.nextElementSibling;
      const icon = questionElem.querySelector('i');
      if (answer.style.display === 'block') {
        answer.style.display = 'none';
        icon.className = 'fa-solid fa-chevron-down';
      } else {
        answer.style.display = 'block';
        icon.className = 'fa-solid fa-chevron-up';
      }
    }

    function zombieSubmitOrder(e) {
      e.preventDefault();
      const name = document.getElementById('zombieCustName').value.trim();
      const phone = document.getElementById('zombieCustPhone').value.trim();
      const address = document.getElementById('zombieCustAddress').value.trim();
      const pkg = zombiePackages[zombieCurrentPkgIndex];
      const del = (zombieCurrentPkgIndex === 3) ? 0 : zombieDeliveryCharge;
      const total = pkg.price + del;

      if (!name || !phone || !address) {
        Swal.fire({
          icon: 'warning',
          title: 'তথ্য অসম্পূর্ণ',
          text: 'দয়া করে আপনার নাম, ফোন নম্বর ও সম্পূর্ণ ঠিকানা লিখুন।',
          confirmButtonColor: '#C2410C'
        });
        return;
      }

      Swal.fire({
        icon: 'success',
        title: 'অর্ডার সফল হয়েছে!',
        html: `
          <div style="text-align: left; font-size: 14px; line-height: 1.6; margin-top: 10px; background: #F8FAFC; padding: 14px; border-radius: 8px;">
            <p><strong>ধন্যবাদ ${name}!</strong> আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে।</p>
            <p><strong>প্যাকেজ:</strong> ${pkg.name}</p>
            <p><strong>মোট প্রদেয় বিল:</strong> ${total.toLocaleString('bn-BD')} ৳ (ক্যাশ অন ডেলিভারি)</p>
            <p><strong>ডেলিভারি ঠিকানা:</strong> ${address}</p>
            <p style="margin-top: 8px; color: #16A34A; font-weight: 600;">খুব শীঘ্রই আমাদের প্রতিনিধি <strong>${phone}</strong> নম্বরে কল করে অর্ডার নিশ্চিত করবেন।</p>
          </div>
        `,
        confirmButtonText: 'ঠিক আছে',
        confirmButtonColor: '#C2410C'
      }).then(() => {
        document.getElementById('zombieOrderForm').reset();
        zombieSelectPkg(0);
      });
    }

    // Init on load
    document.addEventListener('DOMContentLoaded', () => {
      zombieUpdateCalculation();
    });
  