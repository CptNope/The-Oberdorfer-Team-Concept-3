/* The Oberdorfer Team — shared behaviour (no dependencies).
   Progressive enhancement: every page works without this file. */
(function () {
  "use strict";
  var doc = document.documentElement;
  doc.classList.add("js");
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* Reveal: content settles in once, rules draw left-to-right. */
  var reveals = document.querySelectorAll(".reveal, .reveal-rule");
  if ("IntersectionObserver" in window && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add("is-in"); io.unobserve(e.target); }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.05 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add("is-in"); });
  }

  /* Header: solid once the page scrolls past the hero top. */
  var header = document.querySelector(".site-header--overlay");
  if (header) {
    var onScroll = function () { header.classList.toggle("is-scrolled", window.scrollY > 40); };
    window.addEventListener("scroll", onScroll, { passive: true }); onScroll();
  }

  /* Dialog-like panels (mobile menu, contents): toggle + Escape + focus return. */
  function panel(toggleSel, panelSel) {
    var panelEl = document.querySelector(panelSel);
    if (!panelEl) return;
    var toggles = document.querySelectorAll(toggleSel);
    var last = null;
    function set(open) {
      panelEl.classList.toggle("is-open", open);
      panelEl.setAttribute("aria-hidden", open ? "false" : "true");
      if ("inert" in panelEl) panelEl.inert = !open;
      toggles.forEach(function (t) { t.setAttribute("aria-expanded", String(open)); });
      document.body.style.overflow = open ? "hidden" : "";
      if (open) {
        var f = panelEl.querySelector("a, button"); if (f) f.focus();
      } else if (last) { last.focus(); }
    }
    set(false);
    toggles.forEach(function (t) {
      t.addEventListener("click", function () { last = t; set(!panelEl.classList.contains("is-open")); });
    });
    panelEl.addEventListener("click", function (e) {
      if (e.target.closest("[data-close]") || e.target.closest("a[href^='#']")) set(false);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && panelEl.classList.contains("is-open")) set(false);
    });
  }
  panel("[data-menu-toggle]", "#mobile-menu");
  panel("[data-contents-toggle]", "#contents-panel");

  /* Chapter tracker for the documentation bar. */
  var current = document.querySelector("[data-current-chapter]");
  var chapters = document.querySelectorAll("[data-chapter]");
  if (current && chapters.length && "IntersectionObserver" in window) {
    var co = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) current.textContent = e.target.getAttribute("data-chapter");
      });
    }, { rootMargin: "-45% 0px -50% 0px" });
    chapters.forEach(function (c) { co.observe(c); });
  }

  /* Copy token / value buttons. */
  document.querySelectorAll("[data-copy]").forEach(function (b) {
    b.addEventListener("click", function () {
      var v = b.getAttribute("data-copy");
      var done = function () {
        var old = b.getAttribute("aria-label") || "";
        b.setAttribute("data-copied", "true");
        var live = document.getElementById("live");
        if (live) live.textContent = "Copied " + v;
        setTimeout(function () { b.removeAttribute("data-copied"); }, 1400);
      };
      if (navigator.clipboard) navigator.clipboard.writeText(v).then(done, done); else done();
    });
  });

  /* Save-listing toggles (demo). */
  document.querySelectorAll("[data-save]:not([data-save-id])").forEach(function (b) {
    b.addEventListener("click", function (e) {
      e.preventDefault();
      var on = b.getAttribute("aria-pressed") !== "true";
      b.setAttribute("aria-pressed", String(on));
      b.setAttribute("aria-label", (on ? "Remove " : "Save ") + b.getAttribute("data-save"));
    });
  });

  /* Demo form validation: real constraint validation, branded messages. */
  document.querySelectorAll("form[data-demo-form]").forEach(function (form) {
    form.setAttribute("novalidate", "");
    var status = form.querySelector("[data-form-status]");
    function msgFor(input) {
      if (input.validity.valueMissing) return (input.getAttribute("data-label") || "This field") + " is required.";
      if (input.validity.typeMismatch && input.type === "email") return "Enter an email address like name@example.com.";
      if (input.validity.patternMismatch) return input.getAttribute("data-pattern-msg") || "Check the format.";
      return "";
    }
    function check(input) {
      var field = input.closest(".field"); if (!field) return true;
      var err = field.querySelector(".field__error");
      var m = msgFor(input);
      field.classList.toggle("field--error", !!m);
      input.setAttribute("aria-invalid", m ? "true" : "false");
      if (err) { err.hidden = !m; var t = err.querySelector("span"); if (t) t.textContent = m; }
      return !m;
    }
    form.querySelectorAll("input, select, textarea").forEach(function (i) {
      i.addEventListener("blur", function () { if (i.value || i.getAttribute("aria-invalid") === "true") check(i); });
    });
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var ok = true, first = null;
      form.querySelectorAll("[required], [type=email], [pattern]").forEach(function (i) {
        if (!check(i)) { ok = false; first = first || i; }
      });
      if (!ok) { first.focus(); if (status) { status.hidden = false; status.className = "form-status form-status--error"; status.innerHTML = "<svg class='icon' aria-hidden='true'><use href='" + form.getAttribute("data-icons") + "#i-alert'/></svg><div><strong>A few details are missing.</strong>Fix the highlighted fields and send again.</div>"; } return; }
      var btn = form.querySelector("button[type=submit]");
      btn.setAttribute("aria-busy", "true");
      setTimeout(function () {
        btn.removeAttribute("aria-busy");
        if (status) { status.hidden = false; status.className = "form-status form-status--success"; status.innerHTML = "<svg class='icon' aria-hidden='true'><use href='" + form.getAttribute("data-icons") + "#i-check'/></svg><div><strong>Thank you. This is a demonstration form.</strong>Nothing was sent. On the live site, the inquiry would route to Brandon and Kaitlyn and confirm the next step here.</div>"; status.focus(); }
      }, 900);
    });
  });
})();

/* ---- Dialogs, toasts and dismissible alerts (shared) ------------------------ */
(function () {
  "use strict";
  document.querySelectorAll("[data-dialog-open]").forEach(function (b) {
    b.addEventListener("click", function () {
      var d = document.getElementById(b.getAttribute("data-dialog-open"));
      if (d && d.showModal) { d.showModal(); }
    });
  });
  document.querySelectorAll("dialog.dialog").forEach(function (d) {
    d.addEventListener("click", function (e) {
      if (e.target === d || e.target.closest("[data-dialog-close]")) d.close();
    });
  });
  var region = null;
  function toast(msg, icons) {
    if (!region) { region = document.createElement("div"); region.className = "toast-region"; region.setAttribute("role", "status"); region.setAttribute("aria-live", "polite"); document.body.appendChild(region); }
    var t = document.createElement("div"); t.className = "toast";
    t.innerHTML = "<svg class='icon' aria-hidden='true'><use href='" + icons + "#i-check'/></svg><span></span><button type='button'>Dismiss</button>";
    t.querySelector("span").textContent = msg;
    function bye() { t.classList.add("is-leaving"); setTimeout(function () { t.remove(); }, 300); }
    t.querySelector("button").addEventListener("click", bye);
    region.appendChild(t); setTimeout(bye, 5000);
  }
  window.otToast = toast;
  document.querySelectorAll("[data-toast]").forEach(function (b) {
    b.addEventListener("click", function () { toast(b.getAttribute("data-toast"), b.getAttribute("data-icons") || "../assets/img/icons.svg"); });
  });
  document.querySelectorAll("[data-dismiss]").forEach(function (b) {
    b.addEventListener("click", function () { var a = b.closest(".alert"); if (a) a.hidden = true; });
  });
})();
