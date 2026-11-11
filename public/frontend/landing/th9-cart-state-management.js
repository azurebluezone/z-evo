// split-source: th9.html | cart-state-management

    // Current Order State
    const orderState = {
      productTitle: 'বেবি পিংক (১ পিস)',
      quantity: 1,
      unitPrice: 599,
      shippingPrice: 60,
      isFreeShipping: false
    };

    function recalculateTotal() {
      const subtotal = orderState.unitPrice;
      const actualShipping = orderState.isFreeShipping ? 0 : orderState.shippingPrice;
      const total = subtotal + actualShipping;

      document.getElementById('summaryProductName').textContent = orderState.productTitle;
      document.getElementById('summaryProductPrice').textContent = '৳ ' + subtotal.toLocaleString('bn-BD');
      
      const shippingLabel = orderState.isFreeShipping ? 'ফ্রি ডেলিভারি!' : '৳ ' + actualShipping.toLocaleString('bn-BD');
      document.getElementById('summaryShippingPrice').textContent = shippingLabel;
      document.getElementById('summaryTotalPrice').textContent = '৳ ' + total.toLocaleString('bn-BD');
      
      const mobileBar = document.getElementById('mobileBarPrice');
      if (mobileBar) {
        mobileBar.textContent = '৳ ' + subtotal.toLocaleString('bn-BD');
      }
    }

    function updateSelection(colorName, qty, price, el) {
      // Highlight card
      const labels = document.querySelectorAll('.bundle-label');
      labels.forEach(lbl => {
        lbl.classList.remove('zombie-card-active', 'border-rose-500');
        lbl.classList.add('border-slate-200');
      });
      el.classList.add('zombie-card-active', 'border-rose-500');
      el.classList.remove('border-slate-200');

      orderState.quantity = qty;
      orderState.unitPrice = price;
      orderState.isFreeShipping = (qty === 3);

      if (qty === 1) {
        orderState.productTitle = (colorName === 'Pink' ? 'বেবি পিংক' : (colorName === 'Blue' ? 'স্কাই ব্লু' : 'কিউট ব্রাউন')) + ' (১ পিস)';
      } else if (qty === 2) {
        orderState.productTitle = '২ পিস স্পেশাল কম্বো';
      } else {
        orderState.productTitle = '৩ পিস ফুল সেট (ফ্রি ডেলিভারি)';
      }

      recalculateTotal();
    }

    function selectPreset(color, qty, price) {
      const formRadio = document.querySelector(`input[name="productChoice"][value*="${qty}"]`) || 
                         document.querySelector(`input[name="productChoice"]`);
      if (formRadio) {
        formRadio.checked = true;
        const parentLabel = formRadio.closest('label');
        if (parentLabel) {
          updateSelection(color, qty, price, parentLabel);
        }
      }
    }

    function setDeliveryZone(amount, el) {
      const container = document.getElementById('deliveryZoneContainer');
      const labels = container.querySelectorAll('label');
      labels.forEach(lbl => {
        lbl.classList.remove('border-rose-500', 'bg-rose-50/60');
        lbl.classList.add('border-slate-200');
      });
      el.classList.add('border-rose-500', 'bg-rose-50/60');
      el.classList.remove('border-slate-200');

      orderState.shippingPrice = amount;
      recalculateTotal();
    }

    function toggleFaq(id) {
      const answer = document.getElementById(`faq-answer-${id}`);
      const icon = document.getElementById(`faq-icon-${id}`);
      if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        icon.classList.add('rotate-180');
      } else {
        answer.classList.add('hidden');
        icon.classList.remove('rotate-180');
      }
    }

    function handleOrderSubmit(e) {
      e.preventDefault();
      const name = document.getElementById('custName').value.trim();
      const phone = document.getElementById('custPhone').value.trim();
      const address = document.getElementById('custAddress').value.trim();

      if (!name || !phone || !address) {
        Swal.fire({
          icon: 'warning',
          title: 'তথ্য অসম্পূর্ণ',
          text: 'দয়া করে আপনার নাম, মোবাইল নম্বর ও সম্পূর্ণ ঠিকানা সঠিকভাবে প্রদান করুন।',
          confirmButtonColor: '#e11d48',
          confirmButtonText: 'ঠিক আছে'
        });
        return;
      }

      const total = orderState.unitPrice + (orderState.isFreeShipping ? 0 : orderState.shippingPrice);

      Swal.fire({
        title: 'অভিনন্দন! অর্ডার সফল হয়েছে',
        html: `
          <div class="text-left text-sm space-y-2 mt-2 bg-rose-50 p-4 rounded-xl border border-rose-200">
            <p><strong>গ্রাহক:</strong> ${name}</p>
            <p><strong>মোবাইল:</strong> ${phone}</p>
            <p><strong>ঠিকানা:</strong> ${address}</p>
            <p><strong>আইটেম:</strong> ${orderState.productTitle}</p>
            <p class="text-rose-700 font-bold text-base border-t border-rose-200 pt-2"><strong>সর্বমোট প্রদেয় বিল:</strong> ৳ ${total.toLocaleString('bn-BD')} (ক্যাশ অন ডেলিভারি)</p>
          </div>
          <p class="text-xs text-slate-500 mt-3">আমাদের কল সেন্টার থেকে অল্প সময়ের মধ্যে কল দিয়ে আপনার অর্ডারটি নিশ্চিত করা হবে।</p>
        `,
        icon: 'success',
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'ধন্যবাদ!'
      });

      // Clear non-critical inputs
      document.getElementById('custName').value = '';
      document.getElementById('custPhone').value = '';
      document.getElementById('custAddress').value = '';
    }

    // Initialize calculations on page load
    window.addEventListener('DOMContentLoaded', () => {
      recalculateTotal();
    });
  