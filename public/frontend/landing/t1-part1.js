// split-source: t1.html | part

    // Bengali Number formatting
    function toBengaliNum(num) {
      const bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
      return num.toString().replace(/\d/g, d => bnDigits[d]);
    }

    // Live Countdown Timer
    let totalSecs = 3 * 3600 + 45 * 60 + 20;
    setInterval(() => {
      if (totalSecs > 0) {
        totalSecs--;
        const hours = Math.floor(totalSecs / 3600);
        const mins = Math.floor((totalSecs % 3600) / 60);
        const secs = totalSecs % 60;
        const pad = (n) => n < 10 ? '0' + n : n;
        document.getElementById('zombie-hours').innerText = toBengaliNum(pad(hours));
        document.getElementById('zombie-minutes').innerText = toBengaliNum(pad(mins));
        document.getElementById('zombie-seconds').innerText = toBengaliNum(pad(secs));
      }
    }, 1000);

    // Package Selector
    function selectPackage(pkgId) {
      const el = document.getElementById(pkgId);
      if (el) {
        el.checked = true;
        handlePackageChange();
        const orderSection = document.getElementById('order-form');
        if (orderSection) {
          orderSection.scrollIntoView({ behavior: 'smooth' });
        }
      }
    }

    function handlePackageChange() {
      const selectedRadio = document.querySelector('input[name="wallet_pack"]:checked');
      if (!selectedRadio) return;

      const price = parseInt(selectedRadio.value, 10);
      const name = selectedRadio.getAttribute('data-name');

      ['package1', 'package2', 'package3'].forEach(id => {
        const lbl = document.getElementById('lbl-' + id);
        if (lbl) {
          if (document.getElementById(id).checked) {
            lbl.classList.add('selected');
          } else {
            lbl.classList.remove('selected');
          }
        }
      });

      const formatted = toBengaliNum(price.toLocaleString()) + '৳';
      document.getElementById('bill-product').innerText = name;
      document.getElementById('bill-subtotal').innerText = formatted;
      document.getElementById('bill-total').innerText = formatted;
      document.getElementById('funnel-submit-btn').innerHTML = `<i class="fa-solid fa-lock"></i> অর্ডার নিশ্চিত করতে ক্লিক করুন - ${formatted}`;

      const stickyPrice = document.getElementById('sticky-price-text');
      if (stickyPrice) {
        stickyPrice.innerText = toBengaliNum(price.toLocaleString()) + '৳';
      }
    }

    // Direct Form Submission
    document.getElementById('zombie-direct-order-form').addEventListener('submit', function(e) {
      e.preventDefault();

      const name = document.getElementById('cust_name').value.trim();
      const phone = document.getElementById('cust_phone').value.trim();
      const address = document.getElementById('cust_address').value.trim();
      const selected = document.querySelector('input[name="wallet_pack"]:checked');
      const itemTitle = selected.getAttribute('data-name');
      const price = toBengaliNum(parseInt(selected.value, 10).toLocaleString()) + '৳';

      if (!name || !phone || !address) {
        Swal.fire({
          icon: 'warning',
          title: 'তথ্য অসম্পূর্ণ',
          text: 'দয়া করে আপনার নাম, মোবাইল নাম্বার এবং সম্পূর্ণ ঠিকানা সঠিকভাবে প্রদান করুন।',
          confirmButtonColor: '#dc2626'
        });
        return;
      }

      Swal.fire({
        title: '🎉 ধন্যবাদ আপনার অর্ডারের জন্য!',
        html: `
          <div style="text-align: left; font-size: 0.95rem; line-height: 1.6;">
            <p style="color: #059669; font-weight: 700; font-size: 1.15rem; text-align: center; margin-bottom: 15px;">
              আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে!
            </p>
            <div style="background: #f1f5f9; padding: 14px; border-radius: 10px; border: 1px solid #cbd5e1; color: #1e293b;">
              <p style="margin-bottom: 6px;"><strong>গ্রাহকের নাম:</strong> ${name}</p>
              <p style="margin-bottom: 6px;"><strong>মোবাইল নাম্বার:</strong> ${phone}</p>
              <p style="margin-bottom: 6px;"><strong>নির্বাচিত পণ্য:</strong> ${itemTitle}</p>
              <p style="margin-bottom: 6px;"><strong>সর্বমোট বিল:</strong> ${price} (ক্যাশ অন ডেলিভারি)</p>
              <p style="margin-bottom: 0;"><strong>ডেলিভারি ঠিকানা:</strong> ${address}</p>
            </div>
            <p style="margin-top: 15px; color: #475569; font-size: 0.9rem; text-align: center;">
              📦 আমাদের কল সেন্টার থেকে খুব শীঘ্রই ফোন দিয়ে অর্ডারটি কনফার্ম করা হবে এবং ৪৮ ঘণ্টার মধ্যে প্রোডাক্ট পৌঁছে যাবে।
            </p>
          </div>
        `,
        icon: 'success',
        confirmButtonText: 'ঠিক আছে',
        confirmButtonColor: '#059669',
        customClass: {
          popup: 'rounded-4 shadow-lg'
        }
      }).then(() => {
        document.getElementById('zombie-direct-order-form').reset();
        document.getElementById('package1').checked = true;
        handlePackageChange();
      });
    });

    document.addEventListener('DOMContentLoaded', () => {
      handlePackageChange();
    });
  