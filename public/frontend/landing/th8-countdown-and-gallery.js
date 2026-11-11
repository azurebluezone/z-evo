// split-source: th8.html | countdown-and-gallery

    // Set Current Copyright Year
    document.getElementById('zombie-year').innerText = new Date().getFullYear();

    // Gallery Image Switcher
    function changeHeroImage(imgSrc) {
      const mainImg = document.getElementById('zombie-main-img');
      if (mainImg) {
        mainImg.style.opacity = '0.5';
        setTimeout(() => {
          mainImg.src = imgSrc;
          mainImg.style.opacity = '1';
        }, 150);
      }
    }

    // Countdown Timer (18:33:54 initial count-down simulator)
    let totalSeconds = 18 * 3600 + 33 * 60 + 54;
    function updateCountdown() {
      if (totalSeconds > 0) {
        totalSeconds--;
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        
        const formatted = 
          String(hours).padStart(2, '0') + ':' + 
          String(minutes).padStart(2, '0') + ':' + 
          String(seconds).padStart(2, '0');
        
        const countdownEl = document.getElementById('zombie-countdown');
        if (countdownEl) countdownEl.innerText = formatted;
      }
    }
    setInterval(updateCountdown, 1000);

    // FAQ Accordion Toggle
    function toggleFaq(btn) {
      const content = btn.nextElementSibling;
      const icon = btn.querySelector('i');
      
      const isOpen = content.classList.contains('active');
      
      // Close all other FAQs
      document.querySelectorAll('.zombie-accordion-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('[data-purpose="faq-accordion"] i').forEach(ic => ic.style.transform = 'rotate(0deg)');
      
      if (!isOpen) {
        content.classList.add('active');
        icon.style.transform = 'rotate(180deg)';
      }
    }
  