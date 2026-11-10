// Generated from resources/views/templates/template-1.blade.php by tools/extract_landing_assets.py.
// Do not edit this JS by hand; edit the Blade source.

// Countdown timer; the target date lives in data-countdown.
    (function() {
      var el = document.getElementById('countdown-container');
      if (!el || !el.dataset.countdown) return;
      var target = new Date(el.dataset.countdown).getTime();
      function update() {
        var now = new Date().getTime();
        var diff = target - now;
        if (diff < 0) diff = 0;
        var d = Math.floor(diff / (1000*60*60*24));
        var h = Math.floor((diff % (1000*60*60*24)) / (1000*60*60));
        var m = Math.floor((diff % (1000*60*60)) / (1000*60));
        var s = Math.floor((diff % (1000*60)) / 1000);
        document.getElementById('cd-days').textContent = String(d).padStart(2,'0');
        document.getElementById('cd-hours').textContent = String(h).padStart(2,'0');
        document.getElementById('cd-minutes').textContent = String(m).padStart(2,'0');
        document.getElementById('cd-seconds').textContent = String(s).padStart(2,'0');
      }
      update();
      setInterval(update, 1000);
    })();
