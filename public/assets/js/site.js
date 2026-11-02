/* AutoSite — front-end interactions (vanilla JS, no dependencies) */
(() => {
  'use strict';
  const cfg = JSON.parse(document.getElementById('app-config')?.textContent || '{}');
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const fa = n => new Intl.NumberFormat('fa-IR').format(Math.round(n));
  const toEn = s => String(s).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
  const url = p => (cfg.base || '') + '/' + p.replace(/^\//, '');
  const store = {
    get: k => { try { return JSON.parse(localStorage.getItem(k) || '[]'); } catch { return []; } },
    set: (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch {} }
  };
  const toast = (msg) => {
    let w = $('.toast-wrap'); if (!w) { w = document.createElement('div'); w.className = 'toast-wrap'; document.body.append(w); }
    const t = document.createElement('div'); t.className = 'toast'; t.textContent = msg; w.append(t);
    setTimeout(() => t.remove(), 3800);
  };
  $$('.toast').forEach(t => setTimeout(() => t.remove(), 5000));

  /* ---------- header / nav ---------- */
  const header = $('#header'), backdrop = $('#backdrop'), nav = $('#nav');
  const closeAll = () => { nav?.classList.remove('open'); $('#filters')?.classList.remove('open'); backdrop?.classList.remove('show'); document.body.style.overflow = ''; };
  addEventListener('scroll', () => {
    header?.classList.toggle('scrolled', scrollY > 10);
    $('#toTop')?.classList.toggle('show', scrollY > 600);
  }, { passive: true });
  $('[data-toggle="nav"]')?.addEventListener('click', () => { nav.classList.add('open'); backdrop.classList.add('show'); document.body.style.overflow = 'hidden'; });
  backdrop?.addEventListener('click', closeAll);
  $('[data-open-filters]')?.addEventListener('click', () => { $('#filters').classList.add('open'); backdrop.classList.add('show'); });
  $$('[data-close-filters]').forEach(b => b.addEventListener('click', closeAll));
  $('#toTop')?.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));
  $('[data-fab]')?.addEventListener('click', () => $('#fab').classList.toggle('open'));

  /* ---------- theme toggle ---------- */
  const tBtn = $('[data-theme-toggle]');
  const setIcon = () => { if (tBtn) tBtn.querySelector('use').setAttribute('href', cfg.icons + '#' + (document.documentElement.dataset.theme === 'dark' ? 'sun' : 'moon')); };
  setIcon();
  tBtn?.addEventListener('click', () => {
    const n = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = n;
    try { localStorage.setItem('theme', n); } catch {}
    setIcon();
  });

  /* ---------- hero slider ---------- */
  $$('[data-slider]').forEach(sl => {
    const slides = $$('.slide', sl), dots = $$('.slider-dots button', sl);
    if (slides.length < 2) return;
    let i = 0, timer; const dur = +sl.dataset.interval || 6000;
    sl.style.setProperty('--dur', dur + 'ms');
    const go = n => {
      slides[i].classList.remove('active'); slides[i].setAttribute('aria-hidden', 'true'); dots[i]?.classList.remove('active');
      i = (n + slides.length) % slides.length;
      slides[i].classList.add('active'); slides[i].setAttribute('aria-hidden', 'false');
      if (dots[i]) { dots[i].classList.remove('active'); void dots[i].offsetWidth; dots[i].classList.add('active'); }
      clearTimeout(timer); timer = setTimeout(() => go(i + 1), dur);
    };
    dots.forEach((d, k) => d.addEventListener('click', () => go(k)));
    $('[data-next]', sl)?.addEventListener('click', () => go(i + 1));
    $('[data-prev]', sl)?.addEventListener('click', () => go(i - 1));
    let x0 = null;
    sl.addEventListener('touchstart', e => x0 = e.touches[0].clientX, { passive: true });
    sl.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) go(i + (dx > 0 ? 1 : -1)); x0 = null; });
    timer = setTimeout(() => go(1), dur);
  });

  /* ---------- brand → model dependent selects ---------- */
  $$('[data-brand-select]').forEach(bs => {
    const form = bs.closest('form') || document;
    const ms = $('[data-model-select]', form);
    if (!ms) return;
    const load = async (keep) => {
      const first = ms.options[0].outerHTML;
      if (!bs.value) { ms.innerHTML = first; return; }
      ms.disabled = true;
      try {
        const r = await fetch(url('api/models?brand=' + encodeURIComponent(bs.value)));
        const list = await r.json();
        ms.innerHTML = first + list.map(m => `<option value="${m.id}" data-body="${m.body_type || ''}">${m.name.replace(/</g, '&lt;')}</option>`).join('');
        if (keep) ms.value = keep;
      } catch {} finally { ms.disabled = false; }
    };
    bs.addEventListener('change', () => load());
    if (bs.value && ms.options.length <= 1) load(ms.dataset.selected);
  });

  /* ---------- money inputs (thousand separators) ---------- */
  $$('[data-money]').forEach(inp => {
    const fmt = () => { const v = toEn(inp.value).replace(/\D/g, ''); inp.value = v ? Number(v).toLocaleString('en-US') : ''; };
    fmt(); inp.addEventListener('input', fmt);
    inp.form?.addEventListener('submit', () => { inp.value = toEn(inp.value).replace(/\D/g, ''); });
  });

  /* ---------- auto submit ---------- */
  $$('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form?.requestSubmit ? el.form.requestSubmit() : el.form?.submit()));
  $$('[data-autosubmit-form]').forEach(f => setTimeout(() => f.submit(), 600));
  // drop empty fields from GET filter forms → cleaner urls
  $$('form[method="get"]').forEach(f => f.addEventListener('submit', () => {
    $$('input,select', f).forEach(el => { if (!el.value && el.name) el.disabled = true; });
  }));

  /* ---------- favorites & compare ---------- */
  const refreshMarks = () => {
    const fav = store.get('fav').map(String), cmp = store.get('cmp').map(String);
    $$('[data-fav]').forEach(b => b.classList.toggle('on', fav.includes(b.dataset.fav)));
    $$('[data-cmp]').forEach(b => b.classList.toggle('on', cmp.includes(b.dataset.cmp)));
    $$('[data-fav-count]').forEach(c => { c.textContent = fav.length ? fa(fav.length) : ''; c.dataset.n = fav.length; });
    $$('[data-cmp-count]').forEach(c => { c.textContent = cmp.length ? fa(cmp.length) : (c.tagName === 'B' ? '۰' : ''); c.dataset.n = cmp.length; });
    $$('[data-compare-link]').forEach(a => a.href = url('compare?ids=' + cmp.join(',')));
    $('#compareBar')?.classList.toggle('show', cmp.length > 0 && !location.pathname.endsWith('/compare'));
  };
  document.addEventListener('click', e => {
    const f = e.target.closest('[data-fav]'), c = e.target.closest('[data-cmp]');
    if (f) {
      e.preventDefault();
      let l = store.get('fav').map(String); const id = f.dataset.fav;
      const on = l.includes(id); l = on ? l.filter(x => x !== id) : [id, ...l].slice(0, 50);
      store.set('fav', l); toast(on ? 'از علاقه‌مندی‌ها حذف شد' : 'به علاقه‌مندی‌ها اضافه شد'); refreshMarks();
    }
    if (c) {
      e.preventDefault();
      let l = store.get('cmp').map(String); const id = c.dataset.cmp;
      if (l.includes(id)) { l = l.filter(x => x !== id); toast('از مقایسه حذف شد'); }
      else if (l.length >= 3) { toast('حداکثر ۳ خودرو قابل مقایسه است'); return; }
      else { l.push(id); toast('به مقایسه اضافه شد'); }
      store.set('cmp', l); refreshMarks();
    }
    const rm = e.target.closest('[data-cmp-remove]');
    if (rm) { const l = store.get('cmp').map(String).filter(x => x !== rm.dataset.cmpRemove); store.set('cmp', l); location.href = url('compare?ids=' + l.join(',')); }
    if (e.target.closest('[data-cmp-clear]')) { store.set('cmp', []); refreshMarks(); }
  });
  refreshMarks();
  // compare page opened without ids → use stored list
  if (location.pathname.endsWith('/compare') && !new URLSearchParams(location.search).get('ids') && store.get('cmp').length) {
    location.replace(url('compare?ids=' + store.get('cmp').join(',')));
  }
  const favList = $('[data-fav-list]');
  if (favList) {
    const ids = store.get('fav');
    if (!ids.length) $('[data-fav-empty]').hidden = false;
    else fetch(url('api/cars?ids=' + ids.join(','))).then(r => r.json()).then(d => {
      favList.innerHTML = d.html.join('');
      if (!d.html.length) $('[data-fav-empty]').hidden = false;
      refreshMarks(); observe();
    });
  }

  /* ---------- gallery + lightbox ---------- */
  const gal = $('[data-gallery]');
  if (gal) {
    const imgs = JSON.parse($('[data-images]', gal)?.textContent || '[]');
    const main = $('[data-main]', gal), lb = $('#lightbox'), lbImg = $('img', lb), lbCount = $('.lb-count', lb);
    let cur = 0;
    const show = n => {
      if (!imgs.length) return;
      cur = (n + imgs.length) % imgs.length;
      main.style.opacity = .4; setTimeout(() => { main.src = imgs[cur]; main.style.opacity = 1; }, 120);
      $$('[data-thumb]', gal).forEach(t => t.classList.toggle('on', +t.dataset.thumb === cur));
      if (lb.classList.contains('open')) { lbImg.src = imgs[cur]; lbCount.textContent = fa(cur + 1) + ' / ' + fa(imgs.length); }
    };
    $$('[data-thumb]', gal).forEach(t => t.addEventListener('click', () => { if (t.querySelector('.more')) openLb(+t.dataset.thumb); else show(+t.dataset.thumb); }));
    $('[data-gnext]', gal)?.addEventListener('click', e => { e.stopPropagation(); show(cur + 1); });
    $('[data-gprev]', gal)?.addEventListener('click', e => { e.stopPropagation(); show(cur - 1); });
    const openLb = n => { lb.classList.add('open'); document.body.style.overflow = 'hidden'; show(n ?? cur); lbImg.src = imgs[cur]; lbCount.textContent = fa(cur + 1) + ' / ' + fa(imgs.length); };
    const closeLb = () => { lb.classList.remove('open'); document.body.style.overflow = ''; };
    $('[data-open-lightbox]', gal).addEventListener('click', () => openLb());
    $('[data-lb-close]', lb).addEventListener('click', closeLb);
    $('[data-lb-next]', lb).addEventListener('click', () => show(cur + 1));
    $('[data-lb-prev]', lb).addEventListener('click', () => show(cur - 1));
    lb.addEventListener('click', e => { if (e.target === lb) closeLb(); });
    addEventListener('keydown', e => {
      if (!lb.classList.contains('open')) return;
      if (e.key === 'Escape') closeLb(); if (e.key === 'ArrowLeft') show(cur + 1); if (e.key === 'ArrowRight') show(cur - 1);
    });
    let x0 = null;
    lb.addEventListener('touchstart', e => x0 = e.touches[0].clientX, { passive: true });
    lb.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) show(cur + (dx > 0 ? 1 : -1)); x0 = null; });
  }

  /* ---------- detail tabs scroll-spy ---------- */
  const spy = $('[data-spy]');
  if (spy && 'IntersectionObserver' in window) {
    const links = $$('a', spy);
    const io = new IntersectionObserver(es => es.forEach(en => {
      if (en.isIntersecting) links.forEach(l => l.classList.toggle('on', l.getAttribute('href') === '#' + en.target.id));
    }), { rootMargin: '-40% 0px -55% 0px' });
    links.forEach(l => { const t = $(l.getAttribute('href')); if (t) io.observe(t); });
  }

  /* ---------- installment calculator ---------- */
  $$('[data-calc]').forEach(c => {
    const price = +c.dataset.price, rate = +c.dataset.rate / 100 / 12;
    const down = $('[data-down]', c), months = $('[data-months]', c);
    const calc = () => {
      const d = price * down.value / 100, loan = price - d, n = +months.value;
      const inst = rate ? loan * rate / (1 - Math.pow(1 + rate, -n)) : loan / n;
      $('[data-down-pct]', c).textContent = fa(down.value);
      $('[data-months-v]', c).textContent = fa(n);
      $('[data-o-down]', c).textContent = fa(d) + ' تومان';
      $('[data-o-loan]', c).textContent = fa(loan) + ' تومان';
      $('[data-o-inst]', c).textContent = fa(inst) + ' تومان';
      $('[data-o-total]', c).textContent = fa(d + inst * n) + ' تومان';
      const di = $('[data-down-input]'); if (di) di.value = Math.round(d).toLocaleString('en-US');
    };
    down.addEventListener('input', calc); months.addEventListener('input', calc); calc();
  });

  /* ---------- modals ---------- */
  const openModal = id => { const m = document.getElementById(id); if (!m) return; m.classList.add('open'); document.body.style.overflow = 'hidden'; setTimeout(() => $('input:not([type=hidden])', m)?.focus(), 50); };
  const closeModal = m => { m.classList.remove('open'); document.body.style.overflow = ''; };
  $$('[data-modal-open]').forEach(b => b.addEventListener('click', () => openModal(b.dataset.modalOpen)));
  $$('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m || e.target.closest('[data-modal-close]')) closeModal(m); });
  });
  addEventListener('keydown', e => { if (e.key === 'Escape') { $$('.modal.open').forEach(closeModal); closeAll(); } });

  $$('[data-ajax-form]').forEach(f => f.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = $('button:not([type=button])', f), msg = $('.form-msg', f);
    btn.disabled = true;
    $$('[data-money]', f).forEach(i => i.value = toEn(i.value).replace(/\D/g, ''));
    try {
      const r = await fetch(f.action, { method: 'POST', body: new FormData(f), headers: { Accept: 'application/json' } });
      const d = await r.json();
      msg.innerHTML = `<div class="alert ${d.ok ? 'ok' : 'error'}"></div>`;
      msg.firstChild.textContent = d.msg;
      if (d.ok) { f.reset(); setTimeout(() => closeModal(f.closest('.modal')), 2200); }
      window.refreshCaptcha?.(f);
    } catch { msg.innerHTML = '<div class="alert error">خطا در ارسال. دوباره تلاش کنید.</div>'; }
    btn.disabled = false;
  }));

  /* ---------- share ---------- */
  $$('[data-share]').forEach(b => b.addEventListener('click', async () => {
    const data = { title: document.title, url: location.href };
    if (navigator.share) { try { await navigator.share(data); } catch {} }
    else { try { await navigator.clipboard.writeText(location.href); toast('لینک کپی شد'); } catch {} }
  }));

  /* ---------- qty steppers ---------- */
  $$('[data-qty]').forEach(q => {
    const inp = $('input', q), max = +inp.dataset.max || 20;
    const set = v => inp.value = Math.max(1, Math.min(max, v));
    $('[data-inc]', q).addEventListener('click', () => set(+toEn(inp.value) + 1));
    $('[data-dec]', q).addEventListener('click', () => set(+toEn(inp.value) - 1));
  });

  /* ---------- checkout pay type → summary ---------- */
  $$('input[name="pay_type"]').forEach(r => r.addEventListener('change', () => {
    const out = $('[data-pay-amount]'); if (!out) return;
    out.textContent = r.closest('label').querySelector('small').textContent.split('—')[0].trim();
  }));

  /* ---------- reveal on scroll ---------- */
  const observe = () => {
    const els = $$('.reveal:not(.in)');
    if (!('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('in')); return; }
    const io = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } }), { rootMargin: '0px 0px -40px 0px' });
    els.forEach((el, i) => { el.style.transitionDelay = (i % 4) * 60 + 'ms'; io.observe(el); });
  };
  observe();
})();

/* ---------- body damage map: pin → photo viewer ---------- */
(() => {
  const wrap = document.querySelector('[data-damage-view]');
  const modal = document.getElementById('dmgModal');
  if (!wrap || !modal) return;
  let pts = [];
  try { pts = JSON.parse(wrap.dataset.points || '[]'); } catch (e) { pts = []; }
  if (!pts.length) return;
  const q = s => modal.querySelector(s);
  const img = q('.dmg-photo img'), nop = q('.dmg-nophoto');
  let cur = 0, lastFocus = null;
  const svgNs = 'http://www.w3.org/2000/svg';
  const show = i => {
    cur = (i + pts.length) % pts.length;
    const p = pts[cur];
    if (p.photo) { img.src = p.photo; img.hidden = false; nop.hidden = true; } else { img.removeAttribute('src'); img.hidden = true; nop.hidden = false; }
    q('.dmg-part').textContent = p.part;
    const lb = q('.dmg-label'); lb.textContent = p.label; lb.style.setProperty('--c', p.color);
    q('.dmg-note').textContent = p.note || '';
    q('.dmg-note').hidden = !p.note;
    q('.dmg-count').textContent = (cur + 1).toLocaleString('fa-IR') + ' از ' + pts.length.toLocaleString('fa-IR');
    const pin = wrap.querySelector(`[data-pin="${cur}"]`);
    const slot = q('.dmg-ico-slot'); slot.innerHTML = '';
    if (pin) { const s = document.createElementNS(svgNs, 'svg'); s.setAttribute('viewBox', '-14 -14 28 28'); s.setAttribute('width', '34'); s.setAttribute('height', '34');
      [...pin.children].filter(c => !c.classList.contains('pulse') && !c.classList.contains('cam')).forEach(c => s.appendChild(c.cloneNode(true))); slot.appendChild(s); }
    wrap.querySelectorAll('.pin.active').forEach(x => x.classList.remove('active'));
    pin?.classList.add('active');
    q('[data-dmg-prev]').hidden = q('[data-dmg-next]').hidden = pts.length < 2;
  };
  const open = i => { lastFocus = document.activeElement; show(i); modal.hidden = false; requestAnimationFrame(() => modal.classList.add('open')); document.body.style.overflow = 'hidden'; q('[data-dmg-close]').focus(); };
  const close = () => { modal.classList.remove('open'); document.body.style.overflow = ''; setTimeout(() => { modal.hidden = true; }, 220);
    wrap.querySelectorAll('.pin.active').forEach(x => x.classList.remove('active')); lastFocus?.focus?.(); };
  wrap.addEventListener('click', e => {
    const pin = e.target.closest('[data-pin]');
    if (pin) return open(+pin.dataset.pin);
    const part = e.target.closest('[data-part]');
    if (part) { const first = wrap.querySelector(`[data-pin][data-on="${part.dataset.part}"]`); if (first) open(+first.dataset.pin); }
  });
  wrap.addEventListener('keydown', e => { const pin = e.target.closest('[data-pin]'); if (pin && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); open(+pin.dataset.pin); } });
  document.querySelectorAll('[data-open-pin]').forEach(b => b.addEventListener('click', () => open(+b.dataset.openPin)));
  q('[data-dmg-close]').addEventListener('click', close);
  q('[data-dmg-prev]').addEventListener('click', () => show(cur - 1));
  q('[data-dmg-next]').addEventListener('click', () => show(cur + 1));
  modal.addEventListener('click', e => { if (e.target === modal) close(); });
  document.addEventListener('keydown', e => {
    if (modal.hidden) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') show(cur + 1);
    if (e.key === 'ArrowRight') show(cur - 1);
  });
})();

/* ---------- captcha: refresh, lazy load inside modals, reset after ajax submit ---------- */
(() => {
  const reload = img => { const base = (img.dataset.src || img.src).split('?')[0]; img.dataset.src = base; img.src = base + '?r=' + Math.random().toString(36).slice(2); };
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-captcha-refresh]');
    if (b) { const box = b.closest('.captcha-box'); reload(box.querySelector('[data-captcha-img]')); const i = box.querySelector('input'); i.value = ''; i.focus(); return; }
    // modal opened → load its captcha the first time
    const opener = e.target.closest('[data-modal-open]');
    if (opener) setTimeout(() => document.querySelectorAll(`#${opener.dataset.modalOpen} [data-captcha-img][data-src]:not([src])`).forEach(reload), 30);
  });
  document.querySelectorAll('[data-captcha-img][data-src]:not([src])').forEach(img => {
    img.closest('form')?.addEventListener('focusin', () => { if (!img.getAttribute('src')) reload(img); }, { once: true });
  });
  window.refreshCaptcha = form => form?.querySelectorAll('[data-captcha-img]').forEach(img => { reload(img); const i = img.closest('.captcha-box')?.querySelector('input'); if (i) i.value = ''; });
})();

/* ---------- stats: count up once when the band scrolls into view ---------- */
(() => {
  const els = document.querySelectorAll('[data-count]');
  if (!els.length || !('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const toEn = s => s.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[,٬]/g, '');
  const fa = s => String(s).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
  const io = new IntersectionObserver(es => es.forEach(en => {
    if (!en.isIntersecting) return;
    io.unobserve(en.target);
    const el = en.target, raw = el.dataset.count, m = toEn(raw).match(/(\d+(?:\.\d+)?)/);
    if (!m) return;
    const target = parseFloat(m[1]), pre = raw.slice(0, raw.search(/[0-9۰-۹]/)), post = raw.replace(/^[^0-9۰-۹]*[0-9۰-۹,٬.]+/, '');
    const t0 = performance.now(), dur = 1400;
    const step = t => { const k = Math.min(1, (t - t0) / dur), v = Math.round(target * (1 - Math.pow(1 - k, 3)));
      el.textContent = pre + fa(v.toLocaleString('en-US')) + post; if (k < 1) requestAnimationFrame(step); else el.textContent = raw; };
    requestAnimationFrame(step);
  }), { threshold: .6 });
  els.forEach(e => io.observe(e));
})();
