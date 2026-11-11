// split-source: th6.html | order-form-logic

    // Helper to format Bengali Numerals
    function toBengaliNum(n) {
      const bnNumbers = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
      return n.toString().split('').map(digit => bnNumbers[digit] || digit).join('');
    }

    // Function to select package from product cards
    function selectItem(type) {
      const packages = document.getElementsByName('product_package');
      if (type === 'combo') {
        packages[0].checked = true;
      } else if (type === 'serum') {
        packages[1].checked = true;
      } else if (type === 'bra') {
        packages[2].checked = true;
      }
      updateSummary();
    }

    // Update live summary
    function updateSummary() {
      const selected = document.querySelector('input[name="product_package"]:checked');
      if (selected) {
        const val = parseInt(selected.value, 10);
        const formatted = toBengaliNum(val.toLocaleString('en-US')) + '৳';
        document.getElementById('summary-subtotal').innerText = formatted;
        document.getElementById('summary-total').innerText = formatted;
      }
    }

    // Handle order submission with sweetAlert feedback
    function handleOrderSubmit(e) {
      e.preventDefault();
      const name = document.getElementById('custName').value.trim();
      const phone = document.getElementById('custPhone').value.trim();
      const address = document.getElementById('custAddress').value.trim();

      if (!name || !phone || !address) {
        Swal.fire({
          icon: 'warning',
          title: 'তথ্য অসম্পূর্ণ',
          text: 'অনুগ্রহ করে নাম, ফোন নম্বর এবং সম্পূর্ণ ঠিকানা সঠিকভাবে লিখুন।',
          confirmButtonColor: '#E11D48',
          confirmButtonText: 'ঠিক আছে'
        });
        return;
      }

      Swal.fire({
        icon: 'success',
        title: 'অভিনন্দন! আপনার অর্ডার সফল হয়েছে 🎉',
        html: `<p class="text-sm text-slate-600 mt-2">ধন্যবাদ <b>${name}</b> আপু! আমাদের কাস্টমার প্রতিনিধি শীঘ্রই <b>${phone}</b> নম্বরে কল করে আপনার ডেলিভারি নিশ্চিত করবেন।</p>`,
        confirmButtonColor: '#E11D48',
        confirmButtonText: 'ধন্যবাদ'
      });

      document.getElementById('orderForm').reset();
      updateSummary();
    }
  