
// --- helpers added ---
function setTgActive(v){
  const el=document.querySelector("#tgActive, .tg-active, [data-role='tg-active']");
  if(!el) return;
  el.textContent = (v===null||v===undefined||v===""?"":String(v));
}
function setReflectorDot(state){
  const dot=document.querySelector("#reflectorDot, .reflector-dot, [data-role='reflector-dot']");
  if(!dot) return;
  dot.classList.remove('dot-green','dot-orange','dot-red');
  if(state==='green') dot.classList.add('dot-green');
  else if(state==='orange') dot.classList.add('dot-orange');
  else dot.classList.add('dot-red');
}
function setLive(on){
  const el=document.querySelector("#liveTag, .live-tag, [data-role='live']");
  if(!el) return;
  el.style.display = on ? '' : 'none';
}
let __moduleActive = null;
setTgActive("");
setLive(false);
// --- end helpers ---

'use strict';

// ── Navigation ────────────────────────────────────────────────────────────────
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

// ── Toast ─────────────────────────────────────────────────────────────────────
let _tt = null;
function toast(msg, type='', ms=2500) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (type ? ' ' + type : '');
    clearTimeout(_tt);
    _tt = setTimeout(() => t.classList.remove('show'), ms);
}

// ── Activity Store ────────────────────────────────────────────────────────────
// Map: key → {cs, date, time, tg, primary, name, active}
const acts = new Map();
let curTalker = null;
let radioState = 'Listening';
let curMode = null;
let connOK = false;
function setStatusDot(){
  const dot = document.getElementById('ref-dot');
  if(!dot) return;
  dot.classList.remove('blue','grn','org','rd');
  if(!connOK) dot.classList.add('rd');
  else if(curMode) dot.classList.add('org');
  else dot.classList.add('grn');
}

function initStatusDot(){
  try{
    const badge = document.getElementById('badge');
    const rconn = document.getElementById('rconn');
    // Determine initial connection state from server-rendered DOM
    if (badge && badge.classList.contains('connected')) connOK = true;
    else if (rconn && (rconn.classList.contains('ok') || /Verbunden/i.test(rconn.textContent))) connOK = true;
    else connOK = false;
    setStatusDot();
  }catch(e){}
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initStatusDot);
else initStatusDot();


let lastTG = '—';

function h(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function renderActs() {
    const sorted = [...acts.values()].sort((a,b) => {
        return ((b.date||'') + b.time).localeCompare((a.date||'') + a.time);
    });

    const itemHtml = (x) => {
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

    const htmlAll = sorted.length ? sorted.map(itemHtml).join('') : '<div class="empty">Noch keine Aktivität im Log</div>';
    const htmlLast = sorted.length ? itemHtml(sorted[0]) : '<div class="empty">Noch keine Aktivität im Log</div>';

    // Dashboard: nur den letzten Eintrag
    const elDash = document.getElementById('alist');
    if (elDash) elDash.innerHTML = htmlLast;

    // Aktivitäten-Tab: komplette Liste
    const elAct = document.getElementById('alist2');
    if (elAct) elAct.innerHTML = htmlAll;
}

function updateLiveBar() {
    const re = document.getElementById('ls-radio');
    const te = document.getElementById('ls-talker');
    if (re) {
        if (radioState === 'TX') { re.textContent = 'TX'; re.className = 'lv red'; }
        else if (radioState === 'RX') { re.textContent = 'RX'; re.className = 'lv grn'; }
        else { re.textContent = 'Listening'; re.className = 'lv'; }
    }
    if (te) { te.textContent = curTalker || '—'; te.className = 'lv ' + (curTalker ? 'grn' : ''); }
}

// ── SSE Event Handler ─────────────────────────────────────────────────────────
function handle(ev) {
    const tgEl = document.getElementById('ls-tg');

    switch(ev.e) {
        case 'start':
            curTalker  = ev.cs;
            radioState = 'RX';
            acts.set(ev.cs, { cs: ev.cs, date: ev.date||'', time: ev.time, tg: ev.tg, primary: ev.primary, name: '', active: true });
            if (ev.tg && ev.tg !== '0') { lastTG = ev.tg; }
            if (tgEl && !curMode && ev.tg !== '0') tgEl.textContent = ev.tg;
            renderActs(); updateLiveBar();
            break;

        case 'stop':
            if (curTalker === ev.cs) { curTalker = null; radioState = 'Listening'; }
            if (acts.has(ev.cs)) acts.get(ev.cs).active = false;
            renderActs(); updateLiveBar();
            break;

        case 'tx':
            radioState = ev.state === 'ON' ? 'TX' : (curTalker ? 'RX' : 'Listening');
            const live = document.getElementById('live-acts');
            if (live) live.style.display = (ev.state === 'ON') ? '' : 'none';
            updateLiveBar();
            break;

        case 'sq':
            if (ev.state === 'OPEN' && !curTalker) radioState = 'RX';
            else if (ev.state === 'CLOSED' && !curTalker) radioState = 'Listening';
            updateLiveBar();
            break;

        case 'tgsel':
            if (ev.tg) {
                if (ev.tg === '0') {
                    lastTG = null;
                    if (tgEl && !curMode) tgEl.textContent = '—';
                } else {
                    lastTG = ev.tg;
                    if (tgEl && !curMode) tgEl.textContent = ev.tg;
                }
            }
            break;


        case 'mode': {
            // Show active module name instead of TG (Parrot, EchoLink, etc.)
            if (ev.state === 'ON') curMode = ev.name || 'MOD';
            else curMode = null;

            const tgEl2 = document.getElementById('ls-tg');
            if (tgEl2) tgEl2.textContent = curMode ? curMode : '—';
            setStatusDot();
            break;
        }

        case 'conn': {
            connOK = !!ev.ok;
            setStatusDot();
            const badge = document.getElementById('badge');
            const btext = document.getElementById('btext');
            const rconn = document.getElementById('rconn');
            if (ev.ok) {
                if (badge) badge.className = 'badge connected';
                if (btext) btext.textContent = 'VERBUNDEN';
                if (rconn) { rconn.textContent = '● Verbunden'; rconn.className = 'rv ok'; }
            } else {
                if (badge) badge.className = 'badge disconnected';
                if (btext) btext.textContent = 'GETRENNT';
                if (rconn) { rconn.textContent = '✗ ' + ev.msg; rconn.className = 'rv red'; }
            }
            break;
        }

        case 'el':
            acts.set('EL_'+ev.cs, { cs: ev.cs, date: ev.date||'', time: ev.time, tg: 'EL', primary: false, name: ev.name||'', active: false });
            renderActs();
            break;
    }
}

// ── SSE Connection ────────────────────────────────────────────────────────────
let sse = null, sseTimer = null, sseFails = 0;

function connectSSE() {
    if (sse) { try { sse.close(); } catch(_) {} sse = null; }

    sse = new EventSource('api/stream.php');

    sse.addEventListener('history', e => {
        const data = JSON.parse(e.data);
        // Replay history to populate activity map
        (data.lines || []).forEach(ev => {
            if (ev.e === 'start') {
                if (ev.tg && ev.tg !== '0') lastTG = ev.tg;
                acts.set(ev.cs, { cs: ev.cs, date: ev.date||'', time: ev.time, tg: ev.tg, primary: ev.primary, name: '', active: false });
            } else if (ev.e === 'stop') {
                if (acts.has(ev.cs)) acts.get(ev.cs).active = false;
            } else if (ev.e === 'el') {
                acts.set('EL_'+ev.cs, { cs: ev.cs, date: ev.date||'', time: ev.time, tg: 'EL', primary: false, name: ev.name||'', active: false });
            } else if (ev.e === 'tgsel') {
                const tgEl = document.getElementById('ls-tg');
                if (tgEl && ev.tg) { tgEl.textContent = (ev.tg === '0') ? '—' : ev.tg; }
            }
        });
        renderActs(); updateLiveBar();
        sseFails = 0;
    });

    sse.addEventListener('log', e => handle(JSON.parse(e.data)));

    sse.addEventListener('hb', e => {
        const d = JSON.parse(e.data);
        const map = {'hw-cpu': d.cpu, 'hw-tmp': d.tmp, 'hw-mem': d.mem, 'hw-up': d.up};
        Object.entries(map).forEach(([id, v]) => { const el = document.getElementById(id); if (el && v) el.textContent = v; });
        const n = document.getElementById('lu-note');
        if (n) n.textContent = '↻ Letzte Aktualisierung: ' + d.ts;
    });

    sse.onerror = () => {
        sseFails++;
        const badge = document.getElementById('badge');
        const btext = document.getElementById('btext');
        if (badge) badge.className = 'badge disconnected';
        if (btext) btext.textContent = 'VERBINDUNG…';
        clearTimeout(sseTimer);
        sseTimer = setTimeout(connectSSE, Math.min(2000 * sseFails, 30000));
    };

    sse.onopen = () => {
        const badge = document.getElementById('badge');
        const btext = document.getElementById('btext');
        if (badge) badge.className = 'badge connected';
        if (btext) btext.textContent = 'VERBUNDEN';
        sseFails = 0;
    };
}

// ── DTMF ─────────────────────────────────────────────────────────────────────
let dbuf = '';
function kp(k) { dbuf += k; document.getElementById('ddisp').textContent = dbuf || '–'; if (navigator.vibrate) navigator.vibrate(25); }
function kc() { dbuf = ''; document.getElementById('ddisp').textContent = '–'; }
function ks() { if (dbuf) { sendDTMF(dbuf); setTimeout(kc, 500); } }

function sendDTMF(code) {
    toast('Sende: ' + code, '', 1500);
    fetch('api/dtmf.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'dtmf=' + encodeURIComponent(code)
    })
    .then(() => toast('✓ DTMF gesendet', 'success'))
    .catch(() => toast('✓ DTMF gesendet', 'success'));
}

function activateTG(tg) {
    if (navigator.vibrate) navigator.vibrate(40);
    sendDTMF('*91' + tg + '#');
}
function activateTGMan() {
    const v = (document.getElementById('tgi')?.value || '').trim();
    if (!v || !/^\d+$/.test(v)) { toast('Ungültige TG-Nummer', 'error'); return; }
    activateTG(v);
    document.getElementById('tgi').value = '';
}

// ── Swipe ─────────────────────────────────────────────────────────────────────
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

// ── Visibility ────────────────────────────────────────────────────────────────
document.addEventListener('visibilitychange', () => {
    if (!document.hidden && (!sse || sse.readyState === EventSource.CLOSED)) connectSSE();
});

// ── Start ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', connectSSE);
