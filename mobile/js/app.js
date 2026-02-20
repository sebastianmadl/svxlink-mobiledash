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

    // Update html lang attribute
    document.documentElement.lang = lang;
}

// Settings Modal
function openSettings() {
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
    if (key === 'theme') applyTheme(value);
    if (key === 'lang')  applyLanguage(value);

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
    const itemHtml = x => {
        const active = x.cs === curTalker;
        const badge = x.tg === 'EL'
            ? `<span class="badge grn-b">EchoLink</span>`
            : `<span class="badge blu-b">${h(x.tg)}</span>`;
        return `<div class="ai ${active ? 'ai-on' : ''}">
          <div class="at"><div>${h(x.time)}</div><div class="ad">${h((x.date||'').slice(0,5))}</div></div>
          <div class="ai2">
            <span class="acs">${h(x.cs)}${active ? ' <span class="onair">● ON AIR</span>' : ''}</span>
            ${x.name ? '<span class="an">' + h(x.name) + '</span>' : ''}
          </div>
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
        else                           { re.textContent = '—';  re.className = 'lv'; }
    }
    if (te) { te.textContent = curTalker || '—'; te.className = 'lv ' + (curTalker ? 'grn' : ''); }
}

// SSE Event Handler
function handle(ev) {
    const tgEl = document.getElementById('ls-tg');
    switch(ev.e) {
        case 'start':
            curTalker = ev.cs; radioState = 'RX';
            acts.set(ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:ev.tg, primary:ev.primary, name:'', active:true});
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
            setStatusDot(); break;

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
        (data.lines || []).forEach(ev => {
            if (ev.e === 'start') {
                if (ev.tg && ev.tg !== '0') lastTG = ev.tg;
                acts.set(ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:ev.tg, primary:ev.primary, name:'', active:false});
            } else if (ev.e === 'stop') {
                if (acts.has(ev.cs)) acts.get(ev.cs).active = false;
            } else if (ev.e === 'el') {
                acts.set('EL_'+ev.cs, {cs:ev.cs, date:ev.date||'', time:ev.time, tg:'EL', primary:false, name:ev.name||'', active:false});
            } else if (ev.e === 'tgsel') {
                const tgEl = document.getElementById('ls-tg');
                if (tgEl && ev.tg) tgEl.textContent = ev.tg === '0' ? '—' : ev.tg;
            }
        });
        renderActs(); updateLiveBar(); sseFails = 0;
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
