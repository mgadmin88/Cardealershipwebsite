/* AutoSite admin panel scripts */
(() => {
  'use strict';
  const cfg = JSON.parse(document.getElementById('app-config')?.textContent || '{}');
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const url = p => (cfg.base || '') + '/' + p.replace(/^\//, '');
  const toEn = s => String(s).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
  const post = (path, data) => {
    const fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) Object.entries(data || {}).forEach(([k, v]) => Array.isArray(v) ? v.forEach(x => fd.append(k + '[]', x)) : fd.append(k, v));
    fd.append('_token', cfg.csrf);
    return fetch(url(path), { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
  };

  // sidebar + theme
  $('[data-side]')?.addEventListener('click', () => { $('#side').classList.add('open'); $('#backdrop').classList.add('show'); });
  $('#backdrop')?.addEventListener('click', () => { $('#side').classList.remove('open'); $('#backdrop').classList.remove('show'); });
  $('[data-adm-theme]')?.addEventListener('click', () => {
    const d = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = d; try { localStorage.setItem('theme', d); localStorage.removeItem('adm-theme'); } catch {}
  });

  $$('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form.submit()));

  // confirm
  document.addEventListener('click', e => { const c = e.target.closest('[data-confirm]'); if (c && !confirm(c.dataset.confirm)) e.preventDefault(); });

  // tabs (remember last tab)
  $$('[data-tabs]').forEach(bar => {
    const show = id => {
      $$('button', bar).forEach(b => b.classList.toggle('on', b.dataset.tab === id));
      $$('.tab').forEach(t => t.classList.toggle('on', t.id === id));
      try { sessionStorage.setItem('tab:' + location.pathname, id); } catch {}
    };
    $$('button', bar).forEach(b => b.addEventListener('click', () => show(b.dataset.tab)));
    try { const s = sessionStorage.getItem('tab:' + location.pathname); if (s && document.getElementById(s)) show(s); } catch {}
    // jump to the tab containing an invalid field
    bar.closest('form')?.addEventListener('invalid', e => { const t = e.target.closest('.tab'); if (t) show(t.id); }, true);
  });

  // brand → models
  $$('[data-brand-select]').forEach(bs => {
    const form = bs.closest('form'), ms = $('[data-model-select]', form);
    if (!ms) return;
    bs.addEventListener('change', async () => {
      const first = '<option value="">انتخاب مدل</option>';
      if (!bs.value) { ms.innerHTML = first; return; }
      const list = await (await fetch(url('api/models?brand=' + bs.value))).json();
      ms.innerHTML = first + list.map(m => `<option value="${m.id}" data-body="${m.body_type || ''}">${m.name.replace(/</g, '&lt;')}</option>`).join('');
    });
    ms.addEventListener('change', () => {
      const body = ms.selectedOptions[0]?.dataset.body, bsel = $('[data-body-select]', form);
      if (body && bsel) bsel.value = body;
    });
  });

  // money format + words
  const words = n => n >= 1e9 ? (n / 1e9).toFixed(2).replace(/\.?0+$/, '') + ' میلیارد تومان' : n >= 1e6 ? (n / 1e6).toFixed(1).replace(/\.0$/, '') + ' میلیون تومان' : '';
  $$('[data-money]').forEach(inp => {
    const fmt = () => {
      const v = toEn(inp.value).replace(/\D/g, ''); inp.value = v ? Number(v).toLocaleString('en-US') : '';
      if (inp.hasAttribute('data-price-words')) { const w = inp.closest('.field').querySelector('[data-words]'); if (w) w.textContent = words(+v); }
    };
    fmt(); inp.addEventListener('input', fmt);
    inp.form?.addEventListener('submit', () => inp.value = toEn(inp.value).replace(/\D/g, ''));
  });

  // condition → hide used-only fields
  const cond = $('[data-cond]');
  if (cond) { const f = () => $$('[data-used-only]').forEach(x => x.style.display = cond.value === 'new' ? 'none' : ''); cond.addEventListener('change', f); f(); }

  // damage editor: pick a type, click the spot, attach photo/note; pins are draggable
  const ed = $('[data-damage-editor]');
  if (ed) {
    const NS = 'http://www.w3.org/2000/svg';
    const glyphs = JSON.parse(ed.dataset.glyphs || '{}');
    const svg = $('[data-damage-svg]', ed), pinsG = $('[data-pins]', svg), list = $('[data-pt-list]', ed);
    const tpl = $('#ptTpl'); let seq = 0;
    const tool = () => ($('input[name="_dmg_tool"]:checked') || {}).value || 'dent';
    const pt = e => { const p = svg.createSVGPoint(); p.x = e.clientX; p.y = e.clientY; return p.matrixTransform(svg.getScreenCTM().inverse()); };
    const okFill = getComputedStyle(svg).getPropertyValue('--dm-ok') || '#e2e8f0';
    const rank = Object.keys(glyphs);
    const render = () => {
      pinsG.innerHTML = '';
      const worst = {};
      $$('[data-part-select]', ed).forEach(s => { if (s.value !== 'ok') worst[s.dataset.partSelect] = s.value; });
      $$('.pt-row', list).forEach((row, i) => {
        const x = +$('[data-x]', row).value, y = +$('[data-y]', row).value, type = $('[data-pt-type]', row).value, part = $('[data-pt-part]', row).value;
        const g = glyphs[type] || { c: '#999', g: '' };
        if (!worst[part] || rank.indexOf(type) > rank.indexOf(worst[part])) worst[part] = type;
        $('.pt-num', row).textContent = (i + 1).toLocaleString('fa-IR');
        $('.pt-num', row).style.background = g.c;
        const el = document.createElementNS(NS, 'g');
        el.setAttribute('class', 'pin'); el.setAttribute('transform', `translate(${x} ${y})`); el.dataset.row = row.dataset.key;
        el.innerHTML = `<circle r="13" fill="${g.c}" stroke="#fff" stroke-width="2.5" filter="url(#dmgShadow)"/>${g.g}<text y="-18" text-anchor="middle" class="pin-n">${(i + 1).toLocaleString('fa-IR')}</text>`;
        pinsG.appendChild(el);
      });
      $$('.part', svg).forEach(p => {
        const st = worst[p.dataset.part];
        p.setAttribute('fill', st ? `color-mix(in oklab, ${glyphs[st].c} 38%, ${okFill.trim() || '#e2e8f0'})` : 'var(--dm-ok)');
      });
      const n = $$('.pt-row', list).length;
      $('[data-pt-count]', ed).textContent = n ? `(${n.toLocaleString('fa-IR')} مورد)` : '';
      $('[data-pt-empty]', ed).hidden = n > 0;
    };
    const focusRow = key => {
      $$('.pt-row', list).forEach(r => r.classList.toggle('focus', r.dataset.key === key));
      const r = $(`.pt-row[data-key="${key}"]`, list); r?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      return r;
    };
    const add = (x, y, part) => {
      const key = 'n' + (++seq) + Date.now().toString(36);
      const frag = tpl.content.cloneNode(true);
      frag.querySelectorAll('[name]').forEach(el => el.name = el.name.replace('__K__', key));
      const row = frag.querySelector('.pt-row'); row.dataset.key = key;
      $('[data-x]', row).value = Math.round(x); $('[data-y]', row).value = Math.round(y);
      $('[data-pt-part]', row).value = part; $('[data-pt-type]', row).value = tool();
      list.appendChild(row); render();
      focusRow(key); $('input[name$="[note]"]', row)?.focus({ preventScroll: true });
    };
    // drag pins / click to add
    let drag = null, moved = false;
    svg.addEventListener('pointerdown', e => {
      const pin = e.target.closest('.pin'); if (!pin) return;
      drag = pin; moved = false; pin.classList.add('dragging'); svg.setPointerCapture(e.pointerId); e.preventDefault();
    });
    svg.addEventListener('pointermove', e => {
      if (!drag) return;
      const p = pt(e), row = $(`.pt-row[data-key="${drag.dataset.row}"]`, list);
      moved = true; drag.setAttribute('transform', `translate(${Math.round(p.x)} ${Math.round(p.y)})`);
      $('[data-x]', row).value = Math.round(Math.max(0, Math.min(600, p.x))); $('[data-y]', row).value = Math.round(Math.max(0, Math.min(780, p.y)));
    });
    svg.addEventListener('pointerup', e => {
      if (drag) {
        const key = drag.dataset.row; drag.classList.remove('dragging'); drag = null;
        if (moved) { const under = document.elementsFromPoint(e.clientX, e.clientY).find(el => el.dataset && el.dataset.part);
          if (under) $(`.pt-row[data-key="${key}"] [data-pt-part]`, list).value = under.dataset.part; render(); }
        focusRow(key); return;
      }
      const part = e.target.closest('[data-part]');
      if (!part) { const h = $('.dmg-canvas .muted', ed); if (h) { h.textContent = 'روی یکی از قطعات بدنه کلیک کنید.'; h.classList.add('warn'); setTimeout(() => { h.textContent = 'برای جابه‌جایی، نقطه را بکشید. برای ویرایش، روی آن کلیک کنید.'; h.classList.remove('warn'); }, 2200); } return; }
      const p = pt(e); add(p.x, p.y, part.dataset.part);
    });
    list.addEventListener('change', e => {
      if (e.target.matches('[data-pt-type],[data-pt-part]')) render();
      if (e.target.matches('[data-pt-file]') && e.target.files[0]) {
        const lab = e.target.closest('.pt-photo'); let im = $('img', lab);
        if (!im) { im = document.createElement('img'); lab.querySelector('svg')?.remove(); lab.prepend(im); }
        im.src = URL.createObjectURL(e.target.files[0]); lab.classList.add('has');
      }
    });
    list.addEventListener('click', e => {
      const del = e.target.closest('[data-pt-del]');
      if (del) { del.closest('.pt-row').remove(); render(); return; }
      const row = e.target.closest('.pt-row'); if (row) focusRow(row.dataset.key);
    });
    $$('[data-part-select]', ed).forEach(s => s.addEventListener('change', render));
    render();
  }

  // inspection bulk
  $$('[data-insp-all]').forEach(b => b.addEventListener('click', () => {
    $$(`#t-insp input[type=radio][value="${b.dataset.inspAll}"]`).forEach(r => r.checked = true);
  }));

  // dropzone + previews
  $$('[data-dropzone]').forEach(dz => {
    const inp = $('[data-file-input]', dz), prev = dz.parentElement.querySelector('[data-preview]');
    const render = () => { if (!prev) return; prev.innerHTML = ''; [...inp.files].forEach(f => { const i = document.createElement('img'); i.src = URL.createObjectURL(f); prev.append(i); }); };
    inp.addEventListener('change', render);
    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('over'); }));
    dz.addEventListener('drop', e => { const dt = new DataTransfer(); [...inp.files, ...e.dataTransfer.files].forEach(f => dt.items.add(f)); inp.files = dt.files; render(); });
  });
  $$('input[type=file][data-single-preview]').forEach(inp => inp.addEventListener('change', () => {
    const img = document.getElementById(inp.dataset.singlePreview); if (img && inp.files[0]) { img.src = URL.createObjectURL(inp.files[0]); img.hidden = false; }
  }));

  // sortable images
  $$('[data-sortable]').forEach(grid => {
    let drag;
    grid.addEventListener('dragstart', e => { drag = e.target.closest('.img-item'); drag?.classList.add('drag'); });
    grid.addEventListener('dragend', () => { drag?.classList.remove('drag'); post('admin/images/sort', { ids: $$('.img-item', grid).map(x => x.dataset.id) }); $$('.first', grid).forEach(f => f.remove()); const f = $('.img-item', grid); if (f) f.insertAdjacentHTML('beforeend', '<span class="flag accent first">تصویر شاخص</span>'); });
    grid.addEventListener('dragover', e => {
      e.preventDefault();
      const over = e.target.closest('.img-item'); if (!over || over === drag) return;
      const r = over.getBoundingClientRect();
      grid.insertBefore(drag, (e.clientX < r.left + r.width / 2) ? over.nextSibling : over);
    });
  });
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-img-del]'); if (!b) return;
    if (!confirm('این تصویر حذف شود؟')) return;
    const r = await post('admin/images/' + b.dataset.imgDel + '/delete', {});
    if (r.ok) b.closest('.img-item').remove();
  });

  // color pickers sync
  $$('.color-field').forEach(cf => {
    const c = $('input[type=color]', cf), t = $('input[type=text]', cf);
    c.addEventListener('input', () => { t.value = c.value; document.documentElement.style.setProperty('--' + cf.dataset.var, c.value); });
    t.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(t.value)) c.value = t.value; });
  });

  // driver/provider field switchers
  $$('[data-switch]').forEach(sel => {
    const scope = sel.closest('form');
    const f = () => $$('[data-for]', scope).forEach(x => { x.hidden = x.dataset.for !== sel.value; $$('input,select,textarea', x).forEach(i => i.disabled = x.hidden); });
    sel.addEventListener('change', f); f();
  });
})();
document.querySelectorAll('input[type=range][name=theme_radius]').forEach(r => r.addEventListener('input', () => { const o = document.querySelector('[data-out="r"]'); if (o) o.textContent = new Intl.NumberFormat('fa-IR').format(r.value); document.documentElement.style.setProperty('--radius', r.value + 'px'); }));

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
