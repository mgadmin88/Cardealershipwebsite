/* v3 effects: reveal, scroll progress, header glass, hero light (dust, spotlight, parallax), card tilt + glare, marquee, ripple.
   Everything degrades gracefully: without JS the page is fully visible; with reduced-motion, movement is skipped. */
(() => {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = matchMedia('(hover: hover) and (pointer: fine)').matches;
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const root = document.documentElement;

  /* ---------- brand marquee: duplicate the track for a seamless loop ---------- */
  $$('[data-marquee] .marquee-track').forEach(t => {
    [...t.children].forEach(c => { const k = c.cloneNode(true); k.setAttribute('aria-hidden', 'true'); k.tabIndex = -1; t.appendChild(k); });
    const n = t.children.length / 2;
    t.style.animationDuration = Math.max(25, n * 4.5) + 's';
  });

  /* ---------- scroll progress + header ---------- */
  const bar = document.createElement('div'); bar.className = 'fx-progress'; document.body.appendChild(bar);
  const header = document.getElementById('header');
  let ticking = false;
  const onScroll = () => {
    ticking = false;
    const h = root.scrollHeight - innerHeight;
    bar.style.transform = `scaleX(${h > 0 ? Math.min(1, scrollY / h) : 0})`;
    header?.classList.toggle('scrolled', scrollY > 24);
  };
  addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
  onScroll();

  /* ---------- reveal on scroll ---------- */
  if ('IntersectionObserver' in window) {
    root.classList.add('fx-ready');
    const targets = $$('.section-head, .process, .grid-cars > .car-card, .grid-parts > *, .cats > .cat, .stats, .cta-banner, .faq-box, .features > *, .ccards > *, .dsec, .about-grid > *');
    targets.forEach(el => {
      if (!el.matches('.section-head, .process')) el.classList.add('fx-reveal');
      const sib = el.parentElement ? [...el.parentElement.children].indexOf(el) : 0;
      el.style.setProperty('--d', ((sib % 4) * 0.09) + 's');
    });
    const io = new IntersectionObserver(es => es.forEach(en => {
      if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
    }), { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    targets.forEach(el => io.observe(el));
    // anything already on screen at load
    requestAnimationFrame(() => targets.forEach(el => { const r = el.getBoundingClientRect(); if (r.top < innerHeight && r.bottom > 0) el.classList.add('is-in'); }));
  }

  /* ---------- button ripple (answers a click) ---------- */
  document.addEventListener('pointerdown', e => {
    const b = e.target.closest('.btn'); if (!b || reduce) return;
    const r = b.getBoundingClientRect(), s = Math.max(r.width, r.height), d = document.createElement('span');
    d.className = 'ripple'; d.style.cssText = `width:${s}px;height:${s}px;left:${e.clientX - r.left - s / 2}px;top:${e.clientY - r.top - s / 2}px`;
    if (getComputedStyle(b).position === 'static') b.style.position = 'relative';
    b.style.overflow = 'hidden'; b.appendChild(d); setTimeout(() => d.remove(), 650);
  });

  if (reduce) return;

  /* ---------- car cards: 3D tilt + glare following the pointer ---------- */
  if (finePointer) {
    $$('.car-card').forEach(card => {
      const g = document.createElement('span'); g.className = 'glare'; card.appendChild(g);
      card.addEventListener('pointermove', e => {
        const r = card.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
        card.classList.add('tilt');
        card.style.setProperty('--ry', ((x - .5) * 10).toFixed(2) + 'deg');
        card.style.setProperty('--rx', ((.5 - y) * 8).toFixed(2) + 'deg');
        card.style.setProperty('--gx', (x * 100).toFixed(1) + '%');
        card.style.setProperty('--gy', (y * 100).toFixed(1) + '%');
      });
      card.addEventListener('pointerleave', () => { card.classList.remove('tilt'); card.style.removeProperty('--rx'); card.style.removeProperty('--ry'); });
    });
  }

  /* ---------- hero: spotlight + parallax ---------- */
  const stage = document.querySelector('.showroom .stage');
  if (stage && finePointer) {
    const slider = stage.querySelector('.slider') || stage;
    stage.addEventListener('pointermove', e => {
      const r = stage.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
      stage.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
      stage.style.setProperty('--my', (y * 100).toFixed(1) + '%');
      slider.style.setProperty('--px', (x - .5).toFixed(3));
      slider.style.setProperty('--py', (y - .5).toFixed(3));
    });
    stage.addEventListener('pointerleave', () => { slider.style.setProperty('--px', 0); slider.style.setProperty('--py', 0); });
  }

  /* ---------- hero: dust drifting in the tube light ---------- */
  const cv = document.querySelector('[data-dust]');
  if (cv && cv.getContext) {
    const ctx = cv.getContext('2d'); let W = 0, H = 0, parts = [], raf = 0, visible = true;
    const dpr = Math.min(2, devicePixelRatio || 1);
    const size = () => { W = cv.clientWidth; H = cv.clientHeight; cv.width = W * dpr; cv.height = H * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0); };
    const make = () => ({ x: Math.random() * W, y: Math.random() * H * .75, r: Math.random() * 1.6 + .4, vx: (Math.random() - .5) * .15, vy: Math.random() * .18 + .04,
      a: Math.random() * .5 + .2, tw: Math.random() * Math.PI * 2 });
    size(); parts = Array.from({ length: Math.round(Math.min(70, W / 18)) }, make);
    const draw = () => {
      ctx.clearRect(0, 0, W, H);
      for (const p of parts) {
        p.x += p.vx; p.y += p.vy; p.tw += .02;
        if (p.y > H * .85 || p.x < -5 || p.x > W + 5) { Object.assign(p, make(), { y: -4 }); }
        // brighter near the ceiling lights
        const lit = Math.max(0, 1 - p.y / (H * .8));
        ctx.globalAlpha = p.a * (.45 + .55 * Math.sin(p.tw) ** 2) * (.35 + .65 * lit);
        ctx.fillStyle = '#ffe7c2';
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.fill();
      }
      raf = visible ? requestAnimationFrame(draw) : 0;
    };
    new IntersectionObserver(([en]) => { visible = en.isIntersecting; if (visible && !raf) raf = requestAnimationFrame(draw); }).observe(cv);
    addEventListener('resize', () => { size(); });
    document.addEventListener('visibilitychange', () => { if (document.hidden) { cancelAnimationFrame(raf); raf = 0; } else if (visible && !raf) raf = requestAnimationFrame(draw); });
    raf = requestAnimationFrame(draw);
  }
})();
