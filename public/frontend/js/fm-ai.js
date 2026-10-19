/* ==========================================================================
   FreshMart AI Features (header AI bar: AI Lens + Voice Search + Scanner)
   Works on every page. Requires Bootstrap 5 bundle + Bootstrap Icons.
   ========================================================================== */
(function () {
  "use strict";
  if (window.__FM_AI__) return;
  window.__FM_AI__ = true;

  var LS_KEY = "fm_ai_last_query";

  /* ---------------- markup ---------------- */
  function drawerHTML() {
    return (
      '<div id="fmAiBackdrop" class="fm-ai-backdrop" hidden></div>' +
      '<aside id="fmAiDrawer" class="fm-ai-drawer" role="dialog" aria-modal="true" aria-label="AI Product Search" hidden>' +
        '<div class="fm-ai-head">' +
          '<div class="fm-ai-tabs" role="tablist">' +
            '<button class="fm-ai-tab is-active" data-fm-tab="camera" role="tab" aria-selected="true">' +
              '<i class="bi bi-camera-fill"></i><span>AI Lens</span></button>' +
            '<button class="fm-ai-tab" data-fm-tab="voice" role="tab" aria-selected="false">' +
              '<i class="bi bi-mic-fill"></i><span>Voice</span></button>' +
          '</div>' +
          '<button class="fm-ai-close" data-fm-close aria-label="Close AI search">&times;</button>' +
        '</div>' +
        '<div class="fm-ai-body">' +
          '<div class="fm-ai-pane" data-fm-pane="camera">' +
            '<div class="fm-ai-scan" id="fmAiStage">' +
              '<i class="bi bi-upc-scan"></i>' +
              '<p>Point your camera at the produce</p>' +
              '<button class="fm-ai-btn" data-fm-scan><i class="bi bi-play-fill"></i> Start scan</button>' +
            '</div>' +
            '<div class="fm-ai-result" id="fmAiResult" hidden>' +
              '<span class="fm-ai-badge">99.2% match</span>' +
              '<h4 id="fmAiName">Hass Avocado</h4>' +
              '<p id="fmAiMeta">Firm · peak ripeness in 24–48h · $2.49 / lb</p>' +
              '<button class="fm-ai-btn fm-ai-btn-alt" data-fm-close>View product</button>' +
            '</div>' +
          '</div>' +
          '<div class="fm-ai-pane" data-fm-pane="voice" hidden>' +
            '<button class="fm-ai-mic" data-fm-mic aria-label="Start voice search">' +
              '<i class="bi bi-mic"></i></button>' +
            '<p class="fm-ai-hint" id="fmAiHint">Tap the mic and say what you need</p>' +
            '<p class="fm-ai-transcript" id="fmAiTranscript"></p>' +
          '</div>' +
        '</div>' +
      '</aside>'
    );
  }

  function headerButtons() {
    return (
      '<span class="fm-ai-trigger">' +
        '<button type="button" class="fm-ai-chip" data-fm-open="camera">' +
          '<i class="bi bi-camera-fill"></i><span>AI Lens</span></button>' +
        '<button type="button" class="fm-ai-chip" data-fm-open="voice">' +
          '<i class="bi bi-mic-fill"></i><span>Voice</span></button>' +
      '</span>'
    );
  }

  /* ---------------- logic ---------------- */
  var drawer, backdrop, lastFocus;

  function open(tab) {
    if (!drawer) return;
    lastFocus = document.activeElement;
    drawer.hidden = false;
    backdrop.hidden = false;
    document.body.classList.add("fm-ai-open");
    backdrop.classList.add("is-open");
    drawer.classList.add("is-open");
    setTab(tab || "camera");
    var f = drawer.querySelector("[data-fm-close]");
    if (f) f.focus();
  }

  function close() {
    if (!drawer) return;
    drawer.classList.remove("is-open");
    backdrop.classList.remove("is-open");
    document.body.classList.remove("fm-ai-open");
    window.setTimeout(function () {
      drawer.hidden = true;
      backdrop.hidden = true;
    }, 240);
    if (lastFocus) lastFocus.focus();
  }

  function setTab(name) {
    drawer.querySelectorAll(".fm-ai-tab").forEach(function (b) {
      var on = b.dataset.fmTab === name;
      b.classList.toggle("is-active", on);
      b.setAttribute("aria-selected", on ? "true" : "false");
    });
    drawer.querySelectorAll(".fm-ai-pane").forEach(function (p) {
      p.hidden = p.dataset.fmPane !== name;
    });
  }

  function scan() {
    var stage = document.getElementById("fmAiStage");
    var res = document.getElementById("fmAiResult");
    var btn = drawer.querySelector("[data-fm-scan]");
    btn.disabled = true;
    stage.classList.add("is-scanning");
    btn.innerHTML = '<span class="fm-ai-spin"></span> Analysing…';
    window.setTimeout(function () {
      stage.classList.remove("is-scanning");
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-play-fill"></i> Scan again';
      res.hidden = false;
      document.getElementById("fmAiName").textContent =
        (Math.random() > 0.5 ? "Hass Avocado" : "Rainbow Carrot");
    }, 1100);
  }

  function voice() {
    var mic = drawer.querySelector("[data-fm-mic]");
    var hint = document.getElementById("fmAiHint");
    var tx = document.getElementById("fmAiTranscript");
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;

    mic.classList.add("is-listening");
    hint.textContent = "Listening…";
    tx.textContent = "";

    if (!SR) {
      tx.textContent = "“firm ripe avocados under $6”";
      hint.textContent = "Demo transcript (mic unavailable in this browser)";
      mic.classList.remove("is-listening");
      return;
    }
    var rec = new SR();
    rec.lang = "en-US";
    rec.interimResults = true;
    rec.onresult = function (e) {
      var txt = "";
      for (var i = e.resultIndex; i < e.results.length; i++) txt += e.results[i][0].transcript;
      tx.textContent = txt;
      try { localStorage.setItem(LS_KEY, txt); } catch (err) {}
    };
    rec.onerror = function () { hint.textContent = "Could not hear that, try again"; };
    rec.onend = function () {
      mic.classList.remove("is-listening");
      hint.textContent = "Tap the mic and say what you need";
    };
    rec.start();
  }

  /* ---------------- mount ---------------- */
  function mount() {
    if (!document.body || document.getElementById("fmAiDrawer")) return;

    document.body.insertAdjacentHTML("beforeend", drawerHTML());
    drawer = document.getElementById("fmAiDrawer");
    backdrop = document.getElementById("fmAiBackdrop");

    // If the page already ships inline AI icons inside the search bar
    // ([data-fm-inline-icons]), don't also inject the header chips.
    var inline = document.querySelector("[data-fm-inline-icons]");

    if (!inline) {
      // put the AI buttons in the header search row (right of the search input)
      var header = document.querySelector("header");
      var form = header && header.querySelector("form[role='search']");
      var host = (form && form.parentElement) || header;
      if (host) host.insertAdjacentHTML("beforeend", headerButtons());
    }

    drawer.addEventListener("click", function (e) {
      var t = e.target;
      if (t.closest("[data-fm-close]")) return close();
      var tab = t.closest("[data-fm-tab]");
      if (tab) return setTab(tab.dataset.fmTab);
      if (t.closest("[data-fm-scan]")) return scan();
      if (t.closest("[data-fm-mic]")) return voice();
    });
    backdrop.addEventListener("click", close);

    document.addEventListener("click", function (e) {
      var o = e.target.closest("[data-fm-open]");
      if (o) { e.preventDefault(); open(o.dataset.fmOpen); }
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && drawer && !drawer.hidden) close();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount);
  } else {
    mount();
  }
})();