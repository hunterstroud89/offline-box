const API_SCAN = 'api/scan.php';
const GRID = document.getElementById('grid');
const Q = document.getElementById('q');
const COUNT = document.getElementById('count');
const FILTER = document.getElementById('filter-console');

async function load(){
  document.getElementById('refresh').disabled = true;
  try{
    const res = await fetch(API_SCAN);
    const json = await res.json();
    const list = json.games || [];
    window._games = list;
    render(list);
  }catch(e){ console.error(e); GRID.innerHTML = '<div class="muted">Scan error</div>' }
  document.getElementById('refresh').disabled = false;
}

function render(list){
  const q = Q.value.trim().toLowerCase();
  const f = FILTER.value;
  const filtered = list.filter(g=>{
    if(f && g.console !== f) return false;
    if(!q) return true;
    return g.name.toLowerCase().includes(q) || (g.console||'').toLowerCase().includes(q);
  });
  COUNT.textContent = filtered.length;
  GRID.innerHTML = '';
  if(!filtered.length) { GRID.innerHTML = '<div class="muted">No games</div>'; return }

  // If the page uses the index-style favorites grid, render favorite tiles
  if (GRID.classList.contains('favorites-grid')) {
    filtered.forEach(it => {
      const item = document.createElement('a');
      item.className = 'favorite-item';
      item.href = `player.php?rom=${encodeURIComponent(it.path)}&console=${encodeURIComponent(it.console)}&game=${encodeURIComponent(it.name)}`;

      const icon = document.createElement('div');
      icon.className = 'favorite-icon';

      const img = document.createElement('img');
      img.className = 'favorite-img';
      // Try to use a per-game thumbnail if provided, otherwise use a small data URL placeholder
      img.src = it.icon || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"><rect width="24" height="24" fill="%23031b1f" rx="4"/><text x="12" y="15" font-size="9" text-anchor="middle" fill="%239fb1c8">GAME</text></svg>';
      img.alt = it.name;
      img.loading = 'lazy';

      icon.appendChild(img);

      const label = document.createElement('div');
      label.className = 'favorite-label';
      label.textContent = it.name;

      // metadata below the tile (small)
      const meta = document.createElement('div');
      meta.className = 'muted';
      meta.style.fontSize = '12px';
      meta.textContent = `${(it.console||'').toUpperCase()} • ${it.sizeFormatted||''}` + (it.external ? ' • mounted' : '');

      item.appendChild(icon);
      item.appendChild(label);
      // wrap tile and metadata in a container (so CSS spacing is simpler)
      const wrapper = document.createElement('div');
      wrapper.appendChild(item);
      wrapper.appendChild(meta);
      GRID.appendChild(wrapper);
    });
    return;
  }

  // Fallback: original gb-card list rendering
  filtered.forEach(it=>{
    const el = document.createElement('div'); el.className='gb-card';
    el.innerHTML = `<div class="title">${escape(it.name)}</div><div class="meta">${(it.console||'').toUpperCase()} • ${it.sizeFormatted||''} ${it.external?'<span class="badge">mounted</span>':''}</div>`;
    const a = document.createElement('a'); a.className='btn'; a.textContent='Play';
    a.href = `player.php?rom=${encodeURIComponent(it.path)}&console=${encodeURIComponent(it.console)}&game=${encodeURIComponent(it.name)}`;
    const dl = document.createElement('a'); dl.textContent='Download'; dl.className='small'; dl.href = `api/stream.php?file=${encodeURIComponent(it.path)}`;
    const actions = document.createElement('div'); actions.className='actions'; actions.appendChild(a); actions.appendChild(dl);
    el.appendChild(actions);
    GRID.appendChild(el);
  });
}

function escape(s){ return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[c]) }

document.getElementById('refresh').addEventListener('click', load);
Q.addEventListener('input', ()=>render(window._games||[]));
FILTER.addEventListener('change', ()=>render(window._games||[]));
document.getElementById('open-folder').addEventListener('click',(e)=>{ e.preventDefault(); window.location.href='../../files/'; });
load();
