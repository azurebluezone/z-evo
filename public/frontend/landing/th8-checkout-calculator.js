// split-source: th8.html | checkout-calculator

    // Live Dynamic Calculation Logic
    let currentQty = 1;
    const baseUnitPrice = 2600;

    function updateQty(delta) {
      currentQty = Math.max(1, currentQty + delta);
      document.getElementById('zombie-qty-display').innerText = currentQty;
      calculateTotal();
    }

    function calculateTotal() {
      const subtotal = currentQty * baseUnitPrice;
      
      // Selected shipping cost
      const shippingOption = document.querySelector('input[name="delivery_zone"]:checked');
      const shippingCost = shippingOption ? parseInt(shippingOption.value, 10) : 80;
      
      const total = subtotal + shippingCost;

      // Update UI
      document.getElementById('subtotalDisplay').innerText = `৳ ${subtotal.toLocaleString('bn-BD')}`;
      document.getElementById('shippingDisplay').innerText = `৳ ${shippingCost.toLocaleString('bn-BD')}`;
      document.getElementById('totalDisplay').innerText = `৳ ${total.toLocaleString('bn-BD')}`;
    }

    // Handle Order Submission with SweetAlert2
    function handleOrderSubmission(e) {
      e.preventDefault();

      const name = document.getElementById('fullName').value.trim();
      const phone = document.getElementById('phone').value.trim();
      const address = document.getElementById('address').value.trim();
      const shippingOption = document.querySelector('input[name="delivery_zone"]:checked').value;
      const shippingLabel = shippingOption === "80" ? "ঢাকা সিটি (৳৮০)" : "ঢাকার বাইরে (৳১৫০)";
      
      const subtotal = currentQty * baseUnitPrice;
      const finalAmount = subtotal + parseInt(shippingOption, 10);

      // Trigger High-End Confirmation Modal
      Swal.fire({
        title: '<span style="color:#e6af2e; font-family:serif;">অর্ডার সফলভাবে গৃহীত হয়েছে!</span>',
        html: `
          <div style="text-align: left; font-size: 14px; color: #d1d5db; line-height: 1.6; padding: 5px;">
            <p><strong>ধন্যবাদ ${name}!</strong> আপনার <strong>${currentQty} টি</strong> ব্ল্যাক ডায়মন্ড শাড়ির অর্ডার নথিভুক্ত করা হয়েছে।</p>
            <div style="background: rgba(255,255,255,0.05); padding: 12px; border-radius: 8px; margin: 12px 0;">
              <p>📱 <strong>মোবাইল:</strong> +88 ${phone}</p>
              <p>🚚 <strong>ডেলিভারি এলাকা:</strong> ${shippingLabel}</p>
              <p>📍 <strong>ঠিকানা:</strong> ${address}</p>
              <p style="color:#e6af2e; font-weight:bold; font-size:16px; margin-top:6px;">💰 মোট প্রদেয়: ৳${finalAmount.toLocaleString('bn-BD')} (ক্যাশ অন ডেলিভারি)</p>
            </div>
            <p style="font-size:12px; color:#9ca3af;">আমাদের কাস্টমার কেয়ার টিম শীঘ্রই ফোন করে আপনার অর্ডারটি চূড়ান্তভাবে কনফার্ম করবে।</p>
          </div>
        `,
        icon: 'success',
        iconColor: '#10b981',
        background: '#0d1322',
        confirmButtonText: 'ঠিক আছে, ধন্যবাদ',
        confirmButtonColor: '#e6af2e',
        customClass: {
          confirmButton: 'text-black font-bold'
        }
      }).then(() => {
        // Reset form
        document.getElementById('zombieOrderForm').reset();
        currentQty = 1;
        document.getElementById('zombie-qty-display').innerText = 1;
        calculateTotal();
      });
    }

    // Initialize calculations on DOM ready
    window.addEventListener('DOMContentLoaded', () => {
      calculateTotal();
    });
  