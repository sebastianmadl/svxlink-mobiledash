'use strict';

// Theme
function applyTheme(t) {
    document.body.classList.remove('theme-dark','theme-light');
    if (t === 'dark')  document.body.classList.add('theme-dark');
    if (t === 'light') document.body.classList.add('theme-light');
}
applyTheme(CFG.theme);

// Live Language Switch
// Walks all [data-i18n] elements and updates their text content
// Also handles [data-i18n-ph] for input placeholders
function applyLanguage(lang) {
    const s = CFG.allStrings[lang] || CFG.allStrings['de'];

    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (s[key] !== undefined) el.textContent = s[key];
    });
    document.querySelectorAll('[data-i18n-ph]').forEach(el => {
        const key = el.getAttribute('data-i18n-ph');
        if (s[key] !== undefined) el.placeholder = s[key];
    });

    // Update active CFG.str so all JS toasts etc use new language immediately
    CFG.str = s;
    CFG.lang = lang;

    // Toggle Text live aktualisieren
    updateTgToggle();

    // Live bar Zustand aktualisieren (z.B. "Listening" → "Warten")
    updateLiveBar();

    // Update html lang attribute
    document.documentElement.lang = lang;
}

function resolveTgName(tg) {
    const custom = CFG.tgNames[tg] || null;
    const caller = (CFG.callerTgNames && CFG.callerTgNames[tg]) || null;
    switch (CFG.tgSource) {
        case 'custom': return custom || 'Talk Group ' + tg;
        case 'dl3el':  return caller || 'Talk Group ' + tg;
        case 'mixed':  return custom || caller || 'Talk Group ' + tg;
        default:       return 'Talk Group ' + tg;
    }
}

function applyTgRename() {
    document.querySelectorAll('.tgname').forEach(el => {
        const row = el.closest('.tgrow');
        if (!row) return;
        const tg = row.querySelector('.tgn')?.textContent?.trim();
        if (!tg) return;
        el.textContent = resolveTgName(tg);
    });
    document.querySelectorAll('.tge-name').forEach(el => {
        const tg = el.id.replace('tge-name-', '');
        if (!tg) return;
        el.textContent = resolveTgName(tg);
        // ✕ / › Sichtbarkeit in Monitor-TG-Liste aktualisieren
        const hasCustom = !!(CFG.tgNames[tg]);
        const delBtn = document.getElementById('tge-del-' + tg);
        const arrSpan = document.getElementById('tge-arr-' + tg);
        if (delBtn)  delBtn.style.display  = (hasCustom && CFG.tgSource !== 'off' && CFG.tgSource !== 'dl3el') ? '' : 'none';
        if (arrSpan) arrSpan.style.display = (hasCustom && CFG.tgSource !== 'off' && CFG.tgSource !== 'dl3el') ? 'none' : '';
    });
}

function deleteTgMonEntry(tg) {
    delete CFG.tgNames[tg];
    applyTgRename();
    renderTgsCustomList();
    fetch('settings.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tg_names=' + encodeURIComponent(JSON.stringify(CFG.tgNames))
    }).catch(e => console.error(e));
}

function updateTgToggle() {
    const on  = CFG.tgSource !== 'off';
    const btn = document.getElementById('tgr-toggle');
    const txt = document.getElementById('tgr-toggle-text');
    const s   = CFG.allStrings[CFG.lang] || CFG.allStrings['de'];
    if (btn) btn.classList.toggle('on', on);
    if (txt) txt.textContent = on ? (s.tg_rename_on || 'Aktiviert') : (s.tg_rename_off || 'Deaktiviert');
    ['tge-dot','tge-dot2','tgs-dot'].forEach(id => {
        const dot = document.getElementById(id);
        if (dot) { dot.classList.toggle('grn', on); dot.classList.toggle('rd', !on); }
    });
}

function toggleTgRename() {
    const wasOff = CFG.tgSource === 'off';
    if (wasOff) {
        CFG.tgSource = CFG._lastSource || 'custom';
    } else {
        CFG._lastSource = CFG.tgSource;
        CFG.tgSource = 'off';
    }
    updateTgToggle();
    applyTgRename();
    fetch('settings.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tg_source=' + encodeURIComponent(CFG.tgSource)
    }).catch(e => console.error(e));
}

function openTgEditor() {
    document.getElementById('settings-overlay').classList.remove('open');
    document.querySelectorAll('.tge-row').forEach(r => r.classList.remove('sel'));
    _selectedTg = null;
    document.getElementById('tge-overlay').classList.add('open');
}

function closeTgEditor() {
    document.getElementById('tge-overlay').classList.remove('open');
}

let _tgeChanged = false;
let _selectedTg = null;





function selectTg(tg) {
    _selectedTg = tg;
    document.querySelectorAll('.tge-row').forEach(r => r.classList.remove('sel'));
    const row = document.getElementById('tge-row-' + tg);
    if (row) row.classList.add('sel');
    openTgPopup(tg);
}

function openTgPopup(tg) {
    const isDl3el = CFG.tgSource === 'dl3el';
    const overlay = document.getElementById('tge-popup-overlay');
    const label   = document.getElementById('tge-popup-tg-label');
    const inp     = document.getElementById('tge-popup-input');
    const edit    = document.getElementById('tge-popup-edit');
    const rdonly  = document.getElementById('tge-popup-readonly');

    if (label) label.textContent = 'TG ' + tg + (isDl3el ? '  —  ' + resolveTgName(tg) : '');

    if (isDl3el) {
        if (edit)   edit.style.display   = 'none';
        if (rdonly) rdonly.style.display = '';
    } else {
        if (edit)   edit.style.display   = '';
        if (rdonly) rdonly.style.display = 'none';
        if (inp) {
            inp.value = CFG.tgNames[tg] || '';
            setTimeout(() => inp.focus(), 150);
        }
    }
    if (overlay) overlay.classList.add('open');
}

function closeTgPopup(e) {
    if (e && e.target.id !== 'tge-popup-overlay') return;
    const overlay = document.getElementById('tge-popup-overlay');
    if (overlay) overlay.classList.remove('open');
    // Deselect row
    document.querySelectorAll('.tge-row').forEach(r => r.classList.remove('sel'));
    _selectedTg = null;
}

function saveTgPopup() {
    if (!_selectedTg) return;
    const inp  = document.getElementById('tge-popup-input');
    const name = inp ? inp.value.trim() : '';
    if (name) CFG.tgNames[_selectedTg] = name;
    else delete CFG.tgNames[_selectedTg];

    // Sofort in UI aktualisieren
    const nameEl = document.getElementById('tge-name-' + _selectedTg);
    if (nameEl) nameEl.textContent = resolveTgName(_selectedTg);
    applyTgRename();   // aktualisiert auch ✕/› in Monitor-Liste
    renderTgsCustomList(); // Non-monitor Custom-Liste ebenfalls neu rendern

    // Popup schließen
    const overlay = document.getElementById('tge-popup-overlay');
    if (overlay) overlay.classList.remove('open');
    document.querySelectorAll('.tge-row').forEach(r => r.classList.remove('sel'));
    _selectedTg = null;

    // In JSON speichern
    fetch('settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tg_names=' + encodeURIComponent(JSON.stringify(CFG.tgNames))
    })
    .then(r => r.json())
    .then(d => { if (d.ok) { _tgeChanged = true; toast(CFG.str.toast_saved || '✓ Gespeichert', 'success'); } })
    .catch(e => console.error('Save tg_names failed:', e));
}

function saveTgName() { saveTgPopup(); }

function openTgSourceEditor() {
    openTgEditor();
}

function closeTgSourceEditor() {
    closeTgEditor();
}

function toggleTgSource() {
    CFG.tgSource = CFG.tgSource === 'off' ? (CFG._lastSource || 'custom') : 'off';
    updateTgToggle();
    applyTgRename();
    fetch('settings.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tg_source=' + encodeURIComponent(CFG.tgSource)
    }).catch(e => console.error(e));
}

function setTgSource(mode) {
    CFG._lastSource = mode;
    CFG.tgSource    = mode;
    ['off','custom','dl3el','mixed'].forEach(m => {
        const el = document.getElementById('tgsrc-' + m);
        if (el) el.classList.toggle('sel', m === mode);
    });
    const ed = document.getElementById('tge-custom-add');
    if (ed) ed.style.display = (mode === 'custom' || mode === 'mixed') ? '' : 'none';
    const cb = document.getElementById('tge-custom-block');
    if (cb) cb.style.display = (mode === 'custom' || mode === 'mixed') ? '' : 'none';
    // Modus Buttons aktiv/inaktiv
    ['custom','dl3el','mixed'].forEach(m => {
        const b = document.getElementById('tgsrc-' + m);
        if (b) b.classList.toggle('active', m === mode);
    });
    // Wenn ein TG ausgewählt ist und Popup offen: Editor-Zustand aktualisieren
    if (_selectedTg) {
        const overlay = document.getElementById('tge-popup-overlay');
        if (overlay && overlay.classList.contains('open')) {
            openTgPopup(_selectedTg);
        }
    }
    updateTgToggle();
    applyTgRename();
    renderTgsCustomList();
    fetch('settings.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tg_source=' + encodeURIComponent(mode)
    }).catch(e => console.error(e));
}

function renderTgsCustomList() {
    const monSet = new Set(CFG.monTGs || []);
    const entries = Object.entries(CFG.tgNames).filter(([tg]) => !monSet.has(tg));

    // ── Inline-Block oberhalb des Editors (tgs-inline-list) ──
    const inline = document.getElementById('tgs-inline-list');
    if (inline) {
        inline.innerHTML = entries.length
            ? entries.map(([tg, customName]) => {
                const tgdbRaw  = (CFG.callerTgNames && CFG.callerTgNames[tg]) || null;
                // Im Editor: führende TG-Nummer entfernen ("TG9990 ECHOTEST" → "ECHOTEST")
                const tgdbName = tgdbRaw ? tgdbRaw.replace(/^\S+\s*/, '').trim() : null;
                const safeTgdb = tgdbName ? tgdbName.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/&lt;(\/?)(?:i|em)&gt;/g,'<$1i>') : '';
                const origLabel = CFG.str.tg_original_label || 'Original';
                const sub = (CFG.tgSource === 'mixed' && tgdbName && tgdbName !== customName)
                    ? `<span style="font-size:10px;color:var(--mu);display:block;margin-top:1px">${h(origLabel)}: ${safeTgdb}</span>`
                    : '';
                return `<div class="tge-row" style="padding:10px 14px;cursor:pointer" onclick="selectTg('${h(tg)}')">
                    <span class="tge-num">${h(tg)}</span>
                    <span style="flex:1;font-family:var(--cond);font-size:13px;color:var(--fg)">${h(customName)}${sub}</span>
                    <span style="color:var(--di);font-size:18px;flex-shrink:0;margin-right:4px">›</span>
                    <button onclick="event.stopPropagation();deleteTgEntry('${h(tg)}')" style="margin-left:4px;background:none;border:none;color:var(--red);font-size:16px;cursor:pointer;padding:0 6px;flex-shrink:0">✕</button>
                </div>`;
            }).join('')
            : '<div style="padding:10px;color:var(--mu);font-size:12px;text-align:center">—</div>';
    }
}

function openAddPopup() {
    const overlay = document.getElementById('tge-add-overlay');
    document.getElementById('tgs-add-tg').value   = '';
    document.getElementById('tgs-add-name').value = '';
    if (overlay) overlay.classList.add('open');
    setTimeout(() => document.getElementById('tgs-add-tg').focus(), 150);
}

function closeAddPopup(e) {
    if (e && e.target.id !== 'tge-add-overlay') return;
    const overlay = document.getElementById('tge-add-overlay');
    if (overlay) overlay.classList.remove('open');
}

function saveAddPopup() {
    const tg   = document.getElementById('tgs-add-tg')?.value.trim();
    const name = document.getElementById('tgs-add-name')?.value.trim();
    if (!tg || !name) return;
    CFG.tgNames[tg] = name;
    const overlay = document.getElementById('tge-add-overlay');
    if (overlay) overlay.classList.remove('open');
    renderTgsCustomList();
    applyTgRename();
    fetch('settings.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tg_names=' + encodeURIComponent(JSON.stringify(CFG.tgNames))
    })
    .then(r => r.json())
    .then(d => { if (d.ok) toast(CFG.str.toast_saved || '✓', 'success'); })
    .catch(e => console.error(e));
}

function saveTgSourceEntry() { saveAddPopup(); }

function deleteTgEntry(tg) {
    delete CFG.tgNames[tg];
    renderTgsCustomList();
    applyTgRename();
    fetch('settings.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tg_names=' + encodeURIComponent(JSON.stringify(CFG.tgNames))
    }).catch(e => console.error(e));
}

// Settings Modal
function openSettings() {
    // TG-Editor und alle Popups schließen, damit Settings immer im Vordergrund erscheint
    const tgeOverlay = document.getElementById('tge-overlay');
    const tgePopup   = document.getElementById('tge-popup-overlay');
    const addPopup   = document.getElementById('tge-add-overlay');
    if (tgePopup   && tgePopup.classList.contains('open'))   tgePopup.classList.remove('open');
    if (addPopup   && addPopup.classList.contains('open'))   addPopup.classList.remove('open');
    if (tgeOverlay && tgeOverlay.classList.contains('open')) tgeOverlay.classList.remove('open');
    document.getElementById('settings-overlay').classList.add('open');
}

function closeSettings(e) {
    // Close when clicking backdrop or close button
    if (e && e.target.id !== 'settings-overlay' && !e.target.classList.contains('settings-close-btn')) return;
    document.getElementById('settings-overlay').classList.remove('open');
}

function setSetting(key, value) {
    // Immediately update active state on buttons
    document.querySelectorAll('.sopt-btn').forEach(b => {
        const oc = b.getAttribute('onclick') || '';
        if (oc.includes(`'${key}'`) && oc.includes(`'${value}'`)) b.classList.add('active');
        else if (oc.includes(`'${key}'`)) b.classList.remove('active');
    });

    // Apply immediately without waiting for server
    if (key === 'theme')     applyTheme(value);
    if (key === 'lang')      applyLanguage(value);

    // Persist to server
    fetch('settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: key + '=' + encodeURIComponent(value)
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) console.error('Settings save error:', d.msg);
        else if (key === 'theme') CFG.theme = value;
    })
    .catch(e => console.error('Settings fetch failed:', e));
}

// Navigation
const PAGES = ['dashboard','activity','tg','dtmf'];
let curPage = 0;

function go(page, btn) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nb').forEach(b => b.classList.remove('active'));
    const el = document.getElementById('p-' + page);
    if (el) el.classList.add('active');
    if (btn) btn.classList.add('active');
    document.querySelector('main').scrollTop = 0;
    curPage = PAGES.indexOf(page);
    const footer = document.querySelector('.byline-footer');
    if (footer) footer.style.opacity = '0';
    renderActs();
}

// Toast
let _tt = null;
function toast(msg, type='', ms=2500) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (type ? ' ' + type : '');
    clearTimeout(_tt);
    _tt = setTimeout(() => t.classList.remove('show'), ms);
}

// Activity Store
const acts = new Map();
let curTalker = null, radioState = 'Listening', curMode = null, connOK = false;

function setStatusDot() {
    const dot = document.getElementById('ref-dot');
    if (!dot) return;
    dot.classList.remove('blue','grn','org','rd');
    if (!connOK)      dot.classList.add('rd');
    else if (curMode) dot.classList.add('org');
    else              dot.classList.add('grn');
}

function initStatusDot() {
    try {
        const badge = document.getElementById('badge');
        const rconn = document.getElementById('rconn');
        if (badge && badge.classList.contains('connected')) connOK = true;
        else if (rconn && rconn.classList.contains('ok'))  connOK = true;
        setStatusDot();
    } catch(e) {}
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initStatusDot);
else initStatusDot();

let lastTG = '—';
function h(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function renderActs() {
    const sorted = [...acts.values()].sort((a,b) => ((b.date||'')+b.time).localeCompare((a.date||'')+a.time));
    const showName = !!CFG.hasDmr;
    const itemHtml = x => {
        const active = x.cs === curTalker;
        const badge = x.tg === 'EL'
            ? `<span class="badge grn-b">EchoLink</span>`
            : x.tg === 'MOD'
            ? `<span class="badge ${x.active ? 'grn-b' : 'red-b'}">${h(x.modName || 'Module')}</span>`
            : `<span class="badge blu-b">${h(x.tg)}</span>`;
        const baseCs = x.cs.replace(/-.*$/, '').toUpperCase();
        const qrzUrl = `https://www.qrz.com/db/${baseCs}`;
        const csHtml = x.tg === 'MOD'
            ? `<span class="acs">${h(x.cs)}</span>${active ? ' <span class="onair">● ON AIR</span>' : ''}`
            : `<a class="acs-link" href="${qrzUrl}" target="_blank" rel="noopener" onclick="event.stopPropagation()">${h(x.cs)}</a>${active ? ' <span class="onair">● ON AIR</span>' : ''}`;
        const nameCol = showName ? `<span class="an">${x.name ? h(x.name) : ''}</span>` : '';
        return `<div class="ai${showName ? '' : ' ai-no-name'}${active ? ' ai-on' : ''}">
          <div class="at"><div>${h(x.time)}</div><div class="ad">${h((x.date||'').slice(0,5))}</div></div>
          <span class="acs">${csHtml}</span>
          ${nameCol}
          <div>${badge}</div>
        </div>`;
    };
    const empty = `<div class="empty">${h(CFG.str.no_activity || '—')}</div>`;
    const htmlAll  = sorted.length ? sorted.map(itemHtml).join('') : empty;
    const htmlLast = sorted.length ? itemHtml(sorted[0]) : empty;
    const elDash = document.getElementById('alist');  if (elDash) elDash.innerHTML = htmlLast;
    const elAct  = document.getElementById('alist2'); if (elAct)  elAct.innerHTML  = htmlAll;
}

function updateLiveBar() {
    const re = document.getElementById('ls-radio');
    const te = document.getElementById('ls-talker');
    if (re) {
        if (radioState === 'TX')       { re.textContent = 'TX'; re.className = 'lv red'; }
        else if (radioState === 'RX')  { re.textContent = 'RX'; re.className = 'lv grn'; }
        else                           { re.textContent = CFG.str.listening || 'Listening'; re.className = 'lv blue'; }
    }
    if (te) { te.textContent = curTalker || '—'; te.className = 'lv ' + (curTalker ? 'grn' : 'blue'); }
}

// SSE Event Handler
function handle(ev) {
    const tgEl = document.getElementById('ls-tg');
    switch(ev.e) {
        case 'start':
            curTalker = ev.cs; radioState = 'RX';
            const norm = ev.cs.replace(/-.*$/, '').toUpperCase();
            const dmrName = ev.name || (CFG.dmrNames && CFG.dmrNames[norm]) || '';
            acts.set(ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:ev.tg, primary:ev.primary, name:dmrName, active:true});
            if (ev.tg && ev.tg !== '0') lastTG = ev.tg;
            if (tgEl && !curMode && ev.tg !== '0') tgEl.textContent = ev.tg;
            renderActs(); updateLiveBar(); break;

        case 'stop':
            if (curTalker === ev.cs) { curTalker = null; radioState = 'Listening'; }
            if (acts.has(ev.cs)) acts.get(ev.cs).active = false;
            renderActs(); updateLiveBar(); break;

        case 'tx':
            radioState = ev.state === 'ON' ? 'TX' : (curTalker ? 'RX' : 'Listening');
            const live = document.getElementById('live-acts');
            if (live) live.style.display = ev.state === 'ON' ? '' : 'none';
            updateLiveBar(); break;

        case 'sq':
            if (ev.state === 'OPEN'   && !curTalker) radioState = 'RX';
            if (ev.state === 'CLOSED' && !curTalker) radioState = 'Listening';
            updateLiveBar(); break;

        case 'tgsel':
            if (ev.tg) {
                if (ev.tg === '0') { lastTG = null; if (tgEl && !curMode) tgEl.textContent = '—'; }
                else               { lastTG = ev.tg; if (tgEl && !curMode) tgEl.textContent = ev.tg; }
            } break;

        case 'mode':
            curMode = ev.state === 'ON' ? (ev.name || 'MOD') : null;
            const tgEl2 = document.getElementById('ls-tg');
            if (tgEl2) tgEl2.textContent = curMode || '—';
            setStatusDot();
            if ((ev.name||'').toUpperCase() === 'ECHOLINK') { renderActs(); break; }
            if (ev.state === 'ON') {
                const modCs = ev.name;
                acts.set('MOD_'+(ev.name||'MOD'), {cs:modCs, date:ev.date||'', time:ev.time, tg:'MOD', modName:ev.name, primary:false, name:'', active:true});
            } else {
                const modKey = 'MOD_'+(ev.name||'MOD');
                if (acts.has(modKey)) acts.get(modKey).active = false;
            }
            renderActs(); break;

        case 'conn': {
            connOK = !!ev.ok; setStatusDot();
            const badge = document.getElementById('badge');
            const btext = document.getElementById('btext');
            const rconn = document.getElementById('rconn');
            if (ev.ok) {
                if (badge) badge.className = 'badge connected';
                if (btext) btext.textContent = CFG.str.badge_connected;
                if (rconn) { rconn.textContent = CFG.str.connected_dot; rconn.className = 'rv ok'; }
            } else {
                if (badge) badge.className = 'badge disconnected';
                if (btext) btext.textContent = CFG.str.badge_disconnected;
                if (rconn) { rconn.textContent = '✗ ' + ev.msg; rconn.className = 'rv red'; }
            }
            break;
        }
        case 'el':
            acts.set('EL_'+ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:'EL', primary:false, name:ev.name||'', active:false});
            renderActs(); break;
    }
}

// SSE Connection
let sse = null, sseTimer = null, sseFails = 0;

function connectSSE() {
    if (sse) { try { sse.close(); } catch(_) {} sse = null; }
    sse = new EventSource('api/stream.php');

    sse.addEventListener('history', e => {
        const data = JSON.parse(e.data);
        CFG.hasDmr = !!data.hasDmr;   // Namensspalte nur wenn DMRIds.dat vorhanden
        (data.lines || []).forEach(ev => {
            if (ev.e === 'start') {
                if (ev.tg && ev.tg !== '0') lastTG = ev.tg;
                acts.set(ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:ev.tg, primary:ev.primary, name:ev.name||'', active:false});
            } else if (ev.e === 'stop') {
                if (acts.has(ev.cs)) acts.get(ev.cs).active = false;
            } else if (ev.e === 'el') {
                acts.set('EL_'+ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:'EL', primary:false, name:ev.name||'', active:false});
            } else if (ev.e === 'mode') {
                if ((ev.name||'').toUpperCase() === 'ECHOLINK') return;
                const modKey = 'MOD_'+(ev.name||'MOD');
                const modCs = ev.name;
                if (ev.state === 'ON') {
                    acts.set(modKey, {cs:modCs, date:ev.date||'', time:ev.time, tg:'MOD', modName:ev.name, primary:false, name:'', active:true});
                } else {
                    if (acts.has(modKey)) {
                        acts.get(modKey).active = false;
                    } else {
                        acts.set(modKey, {cs:ev.name, date:ev.date||'', time:ev.time, tg:'MOD', modName:ev.name, primary:false, name:'', active:false});
                    }
                }
            } else if (ev.e === 'tgsel') {
                const tgEl = document.getElementById('ls-tg');
                if (tgEl && ev.tg) tgEl.textContent = ev.tg === '0' ? '—' : ev.tg;
            }
        });
        renderActs(); updateLiveBar(); sseFails = 0;
    });

    sse.addEventListener('dmrnames', e => {
        const names = JSON.parse(e.data).names || {};
        CFG.hasDmr = true;
        // Bestehende Einträge in acts mit Namen befüllen
        acts.forEach((act, key) => {
            if (!act.name) {
                const norm = act.cs.replace(/-.*$/, '').toUpperCase();
                if (names[norm]) act.name = names[norm];
            }
        });
        renderActs();
    });

    sse.addEventListener('dmrnames', e => {
        const data = JSON.parse(e.data);
        CFG.hasDmr = true;
        CFG.dmrNames = data.names || {};
        // Namen rückwirkend in alle vorhandenen acts eintragen
        acts.forEach((act, key) => {
            if (!act.name) {
                const norm = act.cs.replace(/-.*$/, '').toUpperCase();
                if (CFG.dmrNames[norm]) act.name = CFG.dmrNames[norm];
            }
        });
        renderActs();
    });

    // Reflektor hat neu verbunden → Dashboard sofort aktualisieren
    sse.addEventListener('refresh', e => {
        const data = JSON.parse(e.data);
        acts.clear();
        curTalker = null;
        radioState = 'Listening';

        // ── Header: Callsign + Reflector-Host ──
        if (data.callsign) {
            document.querySelectorAll('.cs').forEach(el => el.textContent = data.callsign);
            CFG.callsign = data.callsign;
        }
        if (data.host) {
            document.querySelectorAll('.rf').forEach(el => el.textContent = data.host);
        }

        // ── Status-Zeilen im Dashboard ──
        if (data.defTG) {
            lastTG = data.defTG;
            const tgEl = document.getElementById('ls-tg');
            if (tgEl) tgEl.textContent = data.defTG;
            document.querySelectorAll('[data-i18n="default_tg"]').forEach(label => {
                const val = label.nextElementSibling;
                if (val) val.textContent = data.defTG;
            });
        }
        if (data.monTGs && data.monTGs.length) {
            CFG.monTGs = data.monTGs;
            document.querySelectorAll('[data-i18n="monitor_tgs"]').forEach(label => {
                const val = label.nextElementSibling;
                if (val) val.textContent = data.monTGs.join(', ');
            });
        }
        if (data.callsign) {
            document.querySelectorAll('[data-i18n="callsign_lbl"]').forEach(label => {
                const val = label.nextElementSibling;
                if (val) val.textContent = data.callsign;
            });
        }
        if (data.host) {
            document.querySelectorAll('[data-i18n="host"]').forEach(label => {
                const val = label.nextElementSibling;
                if (val) val.textContent = data.host;
            });
        }

        // ── Activity-Liste neu aufbauen ──
        (data.lines || []).forEach(ev => {
            if (ev.e === 'start') {
                if (ev.tg && ev.tg !== '0') lastTG = ev.tg;
                acts.set(ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:ev.tg, primary:ev.primary, name:ev.name||'', active:false});
            } else if (ev.e === 'stop') {
                if (acts.has(ev.cs)) acts.get(ev.cs).active = false;
            } else if (ev.e === 'el') {
                acts.set('EL_'+ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:'EL', primary:false, name:ev.name||'', active:false});
            } else if (ev.e === 'tgsel') {
                const tgEl = document.getElementById('ls-tg');
                if (tgEl && ev.tg) tgEl.textContent = ev.tg === '0' ? '—' : ev.tg;
            }
        });
        renderActs();
        updateLiveBar();
        toast('↻ ' + (CFG.str.badge_connected || 'Reconnected'), 'success');
    });

    sse.addEventListener('log', e => handle(JSON.parse(e.data)));

    sse.addEventListener('hb', e => {
        const d = JSON.parse(e.data);
        const map = {'hw-cpu':d.cpu,'hw-tmp':d.tmp,'hw-mem':d.mem,'hw-up':d.up};
        Object.entries(map).forEach(([id,v]) => { const el = document.getElementById(id); if (el && v) el.textContent = v; });
        const n = document.getElementById('lu-note');
        if (n && !n.hasAttribute('data-i18n')) n.textContent = CFG.str.toast_last_updated + d.ts;
        else if (n) n.textContent = CFG.str.toast_last_updated + d.ts;
    });

    sse.onerror = () => {
        sseFails++;
        const badge = document.getElementById('badge');
        const btext = document.getElementById('btext');
        if (badge) badge.className = 'badge disconnected';
        if (btext) btext.textContent = CFG.str.badge_connecting;
        clearTimeout(sseTimer);
        sseTimer = setTimeout(connectSSE, Math.min(2000 * sseFails, 30000));
    };

    sse.onopen = () => {
        const badge = document.getElementById('badge');
        const btext = document.getElementById('btext');
        if (badge) badge.className = 'badge connected';
        if (btext) btext.textContent = CFG.str.badge_connected;
        sseFails = 0;
    };
}

// DTMF
let dbuf = '';
function kp(k) { dbuf += k; document.getElementById('ddisp').textContent = dbuf || '–'; if (navigator.vibrate) navigator.vibrate(25); }
function kc() { dbuf = ''; document.getElementById('ddisp').textContent = '–'; }
function ks() { if (dbuf) { sendDTMF(dbuf); setTimeout(kc, 500); } }

function sendDTMF(code) {
    toast(CFG.str.toast_sending + code, '', 1500);
    fetch('api/dtmf.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'dtmf=' + encodeURIComponent(code)
    })
    .then(() => toast(CFG.str.toast_sent, 'success'))
    .catch(() => toast(CFG.str.toast_sent, 'success'));
}

function activateTG(tg) { if (navigator.vibrate) navigator.vibrate(40); sendDTMF('*91' + tg + '#'); }

function activateTGMan() {
    const v = (document.getElementById('tgi')?.value || '').trim();
    if (!v || !/^\d+$/.test(v)) { toast(CFG.str.toast_invalid_tg, 'error'); return; }
    activateTG(v);
    document.getElementById('tgi').value = '';
}

// Swipe
let sx = 0, sy = 0;
document.addEventListener('touchstart', e => { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, {passive:true});
document.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - sx, dy = e.changedTouches[0].clientY - sy;
    if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) {
        let i = curPage;
        if (dx < 0 && i < PAGES.length-1) i++;
        else if (dx > 0 && i > 0) i--;
        else return;
        go(PAGES[i], document.querySelector(`.nb[data-p="${PAGES[i]}"]`));
    }
}, {passive:true});

// Visibility
document.addEventListener('visibilitychange', () => {
    if (!document.hidden && (!sse || sse.readyState === EventSource.CLOSED)) connectSSE();
});

// Start
document.addEventListener('DOMContentLoaded', connectSSE);
document.addEventListener('DOMContentLoaded', renderTgsCustomList);

// Byline Footer — nur auf TG und DTMF Seiten, blendet nach 2s wieder aus
(function() {
    const footer = document.querySelector('.byline-footer');
    if (!footer) return;
    const main = document.querySelector('main');
    if (!main) return;
    let hideTimer = null;
    main.addEventListener('scroll', () => {
        // Nur auf tg und dtmf Seiten anzeigen
        const activePage = document.querySelector('.page.active');
        const pageId = activePage ? activePage.id : '';
        if (pageId === 'p-dashboard' || pageId === 'p-activity') return;

        const nearBottom = main.scrollTop + main.clientHeight >= main.scrollHeight - 32;
        if (nearBottom) {
            footer.style.opacity = '1';
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => { footer.style.opacity = '0'; }, 2000);
        }
    }, {passive: true});
})();
