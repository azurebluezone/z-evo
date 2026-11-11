// split-source: th9.html | tailwind-config

    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#fff1f2',
              100: '#ffe4e6',
              200: '#fecdd3',
              500: '#f43f5e',
              600: '#e11d48',
              700: '#be123c',
              800: '#9f1239',
              900: '#881337',
            },
            softBg: '#FFF7F8',
            babyBlue: '#E0F2FE',
            babyBrown: '#F5EBE6'
          },
          fontFamily: {
            bangla: ['"Anek Bangla"', '"Hind Siliguri"', 'sans-serif'],
          },
          boxShadow: {
            'soft-card': '0 10px 30px -5px rgba(225, 29, 72, 0.08), 0 4px 12px -2px rgba(0, 0, 0, 0.04)',
            'floating-cta': '0 8px 25px rgba(225, 29, 72, 0.35)'
          }
        }
      }
    }
  