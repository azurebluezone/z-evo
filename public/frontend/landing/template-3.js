// Generated from resources/views/templates/template-3.blade.php by tools/extract_landing_assets.py.
// Do not edit this JS by hand; edit the Blade source.

document.addEventListener('DOMContentLoaded', function() {
      AOS.init({ duration: 800, easing: 'ease-in-out', once: true });
      
      // Product slider
      new Swiper('.product-swiper', {
        effect: 'coverflow',
        grabCursor: true,
        centeredSlides: true,
        slidesPerView: 'auto',
        coverflowEffect: {
          rotate: 50,
          stretch: 0,
          depth: 100,
          modifier: 1,
          slideShadows: true
        },
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
      });
      
      // Gallery
      if(document.querySelector('.gallery-thumbs')) {
        const galleryThumbs = new Swiper('.gallery-thumbs', {
          spaceBetween: 10,
          slidesPerView: 4,
          freeMode: true,
          watchSlidesProgress: true
        });
        new Swiper('.gallery-main', {
          spaceBetween: 10,
          navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
          thumbs: { swiper: galleryThumbs }
        });
      }
    });
