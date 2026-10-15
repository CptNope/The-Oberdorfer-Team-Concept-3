/* Example-website behaviour on demonstration data: search and filters,
   saved homes, tabs, directory and journal filters. Progressive enhancement:
   without JavaScript every card is visible and every link works. */
(function () {
  "use strict";
  var live = document.getElementById("live");
  function say(t) { if (live) live.textContent = t; }

  /* ---- Saved homes (per-browser convenience only) ---------------------- */
  var KEY = "ot-saved";
  function readSaved() { try { return JSON.parse(localStorage.getItem(KEY) || "[]"); } catch (e) { return []; } }
  function writeSaved(v) { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch (e) {} }
  var saved = readSaved();
  function paintSaved() {
    document.querySelectorAll("[data-save-id]").forEach(function (b) {
      var on = saved.indexOf(b.getAttribute("data-save-id")) > -1;
      b.setAttribute("aria-pressed", String(on));
      b.setAttribute("aria-label", (on ? "Remove " : "Save ") + b.getAttribute("data-save"));
    });
    document.querySelectorAll("[data-saved-count] span").forEach(function (s) { s.textContent = saved.length + " saved"; });
  }
  document.querySelectorAll("[data-save-id]").forEach(function (b) {
    b.addEventListener("click", function (ev) {
      ev.preventDefault(); ev.stopImmediatePropagation();
      var id = b.getAttribute("data-save-id"), i = saved.indexOf(id);
      if (i > -1) saved.splice(i, 1); else saved.push(id);
      writeSaved(saved); paintSaved();
      say((i > -1 ? "Removed " : "Saved ") + b.getAttribute("data-save"));
      if (grid) apply();
    }, true);
  });

  /* ---- Listing search ---------------------------------------------------- */
  var form = document.querySelector("[data-listing-search]");
  var grid = document.querySelector("[data-listing-grid]");
  function apply() {
    if (!grid) return;
    var q = (form.q.value || "").trim().toLowerCase();
    var price = form.price.value, beds = parseInt(form.beds.value || "0", 10), type = form.type.value;
    var flags = [].map.call(document.querySelectorAll("[data-flag]:checked"), function (c) { return c.getAttribute("data-flag"); });
    var lo = 0, hi = Infinity;
    if (price) { var p = price.split("-"); lo = +p[0]; hi = +p[1]; }
    var cards = [].slice.call(grid.querySelectorAll(".listing-card")), shown = 0;
    cards.forEach(function (c) {
      var ok = true, d = c.dataset;
      if (q && d.town.indexOf(q) === -1 && c.textContent.toLowerCase().indexOf(q) === -1) ok = false;
      if (+d.price < lo || +d.price > hi) ok = false;
      if (beds && +d.beds < beds) ok = false;
      if (type && d.kind !== type) ok = false;
      flags.forEach(function (f) {
        if (f === "saved") { var id = c.querySelector("[data-save-id]").getAttribute("data-save-id"); if (saved.indexOf(id) === -1) ok = false; }
        else if ((" " + d.flags + " ").indexOf(" " + f + " ") === -1) ok = false;
      });
      c.hidden = !ok; if (ok) shown++;
    });
    var sort = document.querySelector("[data-sort]");
    if (sort) {
      var v = sort.value;
      cards.sort(function (a, b) {
        if (v === "price-asc") return a.dataset.price - b.dataset.price;
        if (v === "price-desc") return b.dataset.price - a.dataset.price;
        if (v === "beds") return b.dataset.beds - a.dataset.beds;
        return b.dataset.new - a.dataset.new;
      }).forEach(function (c) { grid.appendChild(c); });
    }
    var count = document.querySelector("[data-results-count]");
    if (count) count.textContent = shown + (shown === 1 ? " demonstration home" : " demonstration homes");
    var empty = document.querySelector("[data-empty]"); if (empty) empty.hidden = shown > 0;
  }
  if (form && grid) {
    var params = new URLSearchParams(location.search);
    ["q", "price", "beds", "type"].forEach(function (k) { if (params.get(k) && form[k]) form[k].value = params.get(k); });
    form.addEventListener("submit", function (ev) { ev.preventDefault(); apply(); });
    form.addEventListener("change", apply);
    form.q.addEventListener("input", apply);
    document.querySelectorAll("[data-flag], [data-sort]").forEach(function (c) { c.addEventListener("change", apply); });
    var clear = document.querySelector("[data-clear]");
    if (clear) clear.addEventListener("click", function () {
      form.reset(); document.querySelectorAll("[data-flag]").forEach(function (c) { c.checked = false; }); apply(); form.q.focus();
    });
    apply();
  }
  paintSaved();

  /* ---- Tabs (contact) ------------------------------------------------------ */
  document.querySelectorAll('[role="tablist"]').forEach(function (list) {
    var tabs = [].slice.call(list.querySelectorAll('[role="tab"]'));
    function select(t) {
      tabs.forEach(function (x) {
        var on = x === t; x.setAttribute("aria-selected", String(on)); x.tabIndex = on ? 0 : -1;
        document.getElementById(x.getAttribute("aria-controls")).hidden = !on;
      });
    }
    tabs.forEach(function (t, i) {
      t.addEventListener("click", function () { select(t); });
      t.addEventListener("keydown", function (ev) {
        var n = ev.key === "ArrowRight" ? 1 : ev.key === "ArrowLeft" ? -1 : 0;
        if (!n) return; ev.preventDefault();
        var next = tabs[(i + n + tabs.length) % tabs.length]; select(next); next.focus();
      });
    });
    var h = location.hash.replace("#", "");
    var pre = tabs.filter(function (t) { return t.id === "tab-" + h; })[0]; if (pre) select(pre);
  });

  /* ---- Team directory ------------------------------------------------------ */
  var dir = document.querySelector("[data-directory]");
  if (dir) {
    var run = function () {
      var spec = dir.querySelector("#d-spec").selectedIndex, area = dir.querySelector("#d-area").value.toLowerCase();
      var specVal = dir.querySelector("#d-spec").value.toLowerCase().replace(" buyers", "").replace("first-time", "first-time");
      var n = 0;
      document.querySelectorAll("[data-team-grid] .agent-card").forEach(function (c) {
        var ok = true;
        if (spec > 0 && c.dataset.specialty.indexOf(specVal.split(" ")[0]) === -1) ok = false;
        if (area.indexOf("all of") !== 0 && c.dataset.area.indexOf(area) === -1) ok = false;
        c.hidden = !ok; if (ok) n++;
      });
      var cnt = document.querySelector("[data-team-count]"); if (cnt) cnt.textContent = n + (n === 1 ? " team profile" : " team profiles");
    };
    dir.addEventListener("change", run);
    dir.addEventListener("reset", function () { setTimeout(run, 0); });
  }

  /* ---- Journal category chips ------------------------------------------------ */
  var chips = document.querySelectorAll("[data-cat-filter]");
  if (chips.length) {
    chips.forEach(function (b) {
      b.addEventListener("click", function () {
        var cat = b.getAttribute("data-cat-filter"), n = 0;
        chips.forEach(function (x) { x.setAttribute("aria-pressed", String(x === b)); });
        document.querySelectorAll("[data-journal-grid] .article-card").forEach(function (a) {
          var ok = cat === "All" || a.dataset.cat === cat; a.hidden = !ok; if (ok) n++;
        });
        var empty = document.querySelector("[data-journal-empty]"); if (empty) empty.hidden = n > 0;
        say(n + " articles");
      });
    });
  }

  /* ---- Newsletter (demo) ------------------------------------------------------ */
  document.querySelectorAll("[data-newsletter]").forEach(function (f) {
    f.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var i = f.querySelector("input");
      if (!i.value || !i.checkValidity()) { i.setAttribute("aria-invalid", "true"); i.focus(); say("Enter an email address like name@example.com."); return; }
      i.removeAttribute("aria-invalid");
      var b = f.querySelector("button"); b.textContent = "Thank you"; b.disabled = true;
      say("Demonstration only: you are not subscribed.");
    });
  });
})();
