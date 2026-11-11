// split-source: th8.html | tailwind-config

    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            zombie: {
              midnight: '#060913',
              deep: '#0a0f1d',
              card: '#111827',
              surface: '#161f36',
              gold: '#e6af2e',
              goldHover: '#cfa029',
              goldLight: '#fbf0b9',
              ruby: '#a81c3a',
              roseBorder: 'rgba(230, 175, 46, 0.25)',
              cream: '#fdfbf7',
              creamMuted: '#f3ede2'
            }
          },
          fontFamily: {
            bangla: ['"Hind Siliguri"', 'sans-serif'],
            cinzel: ['"Cinzel"', 'serif'],
            outfit: ['"Outfit"', 'sans-serif']
          },
          boxShadow: {
            'gold-glow': '0 0 25px -3px rgba(230, 175, 46, 0.35)',
            'gold-btn': '0 8px 30px rgba(230, 175, 46, 0.3)',
            'card-soft': '0 10px 30px -5px rgba(0, 0, 0, 0.5)'
          }
        }
      }
    }
  