<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

// Load apps to find chat app icon
$apps = load_apps_json(__DIR__ . '/../../data/json/apps.json');

// Find chat app for hero icon
$chat_app = null;
foreach ($apps as $app) {
  if (isset($app['id']) && ($app['id'] === 'Offline-Chat' || $app['id'] === 'chat')) {
    $chat_app = $app;
    break;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Chat - OfflineBox</title>
  <link rel="stylesheet" href="chat.css">
  <meta name="robots" content="noindex">
</head>
<body>
  <div class="container">
    <div class="section">
      <div class="section-header">
        <a class="back-link" href="../home/home.php" title="Back to Home" aria-label="Back to Home">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
        <h2 class="section-title">Chat</h2>
      </div>
      <div class="hero-card">
        <div class="hero-content">
          <div class="hero-icon">
            <?php
              $icon = '/data/icons/chat.svg'; // fallback
              if ($chat_app && isset($chat_app['icon'])) {
                $icon = $chat_app['icon'];
              }
              if (strpos($icon, '/data/') === 0) {
                $icon = '../..' . $icon;
              }
            ?>
            <img src="<?= htmlspecialchars($icon) ?>" alt="Chat" width="20" height="20" />
          </div>
            <div class="hero-text">
              <p>A simple, local chat for this OfflineBox — messages are stored on the device and visible to anyone who opens this page.</p>
            </div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-header">
        <h2 class="section-title">Messages</h2>
        <div class="header-actions"></div>
      </div>
      <div class="card chat-window">
        <div id="chat-error" style="color:var(--danger);margin-bottom:8px;display:none"></div>
        <div class="messages" id="messages">Loading…</div>
      </div>

      <!-- Controls moved outside the card: textarea (message bubble) on top, name left + send right below -->
      <div class="chat-controls">
        <div class="chat-preview">
          <textarea id="msg" placeholder="Type a message…" autocomplete="off" rows="2"></textarea>
        </div>
        <div class="chat-meta-row">
          <input id="name" class="name-input" placeholder="anonymous" autocomplete="name">
          <button id="send" class="btn-primary" disabled>Send</button>
        </div>
      </div>
    </div>
  </div>

<script>
/* Chat page JS: keep behavior but render avatars/initials and consistent DOM */
let room = 'default';
let name = localStorage.getItem('chat_name') || '';
const nameInput = document.getElementById('name');
if (nameInput) nameInput.value = name;
const messagesEl = document.getElementById('messages');

function initialsFor(n){
  if(!n) return '';
  return n.trim().split(/\s+/).map(s=>s[0]).join('').slice(0,2).toUpperCase();
}

function formatWhen(when){
  try{ if (!when) return ''; if (typeof when === 'string') return when; const d = new Date(when); return d.toISOString().replace('T',' ').split('.')[0]; }catch(e){ return '' }
}

function render(list){
  messagesEl.innerHTML = '';
  if (!list || list.length === 0){
    const p = document.createElement('div'); p.className = 'muted'; p.textContent = 'No messages yet';
    messagesEl.appendChild(p);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return;
  }

  list.forEach(m=>{
    const from = m.from || 'anon';
    const isYou = (from === (document.getElementById('name').value || name));

    const d = document.createElement('div');
    d.className = 'msg ' + (isYou ? 'you' : 'they');

    const content = document.createElement('div');
    content.className = 'msg-row';

    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble';
    bubble.textContent = m.text || '';

    const metaRow = document.createElement('div');
    metaRow.className = 'msg-meta-row';
    const nameEl = document.createElement('div'); nameEl.className = 'msg-name'; nameEl.textContent = from;
    const dateEl = document.createElement('div'); dateEl.className = 'msg-date'; dateEl.textContent = formatWhen(m.when);
    metaRow.appendChild(nameEl); metaRow.appendChild(dateEl);

    content.appendChild(bubble);
    content.appendChild(metaRow);

    d.appendChild(content);
    messagesEl.appendChild(d);
  });

  messagesEl.scrollTop = messagesEl.scrollHeight;
}

async function load(){
  try{
    const res = await fetch('api.php?room='+encodeURIComponent(room));
    const json = await res.json();
    if (!res.ok){
      const err = (json && (json.message || json.error)) ? (json.message || json.error) : 'Server error';
      const errEl = document.getElementById('chat-error'); if (errEl){ errEl.textContent = err; errEl.style.display = 'block'; }
      render([]);
      return;
    }
    const errEl = document.getElementById('chat-error'); if (errEl) errEl.style.display = 'none';
    render(json.messages||[]);

    const nameEl2 = document.getElementById('name');
    if (name && nameEl2 && (!nameEl2.value || nameEl2.value.trim() === '')) nameEl2.value = name;
    const msgEl = document.getElementById('msg');
    if (msgEl && document.activeElement !== nameEl2 && document.activeElement !== msgEl) msgEl.focus();
  }catch(e){ console.error(e); if (messagesEl) messagesEl.textContent='Failed to load'; }
}

async function doSend(){
  const text = (msgInput && msgInput.value || '').trim();
  if(!text) return;
  name = (nameInput && nameInput.value.trim()) || name || 'anon';
  localStorage.setItem('chat_name', name);
  await fetch('api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({room, text, from: name})});
  if (msgInput) msgInput.value='';
  updateSendState();
  load();
}

// wire buttons and inputs (normalized variable names)
const msgInput = document.getElementById('msg');
const sendBtn = document.getElementById('send');

function updateSendState(){
  const hasText = msgInput && msgInput.value && msgInput.value.trim().length > 0;
  if (sendBtn){
    sendBtn.disabled = !hasText;
    sendBtn.classList.toggle('enabled', hasText);
    sendBtn.style.cursor = hasText ? 'pointer' : 'not-allowed';
  }
}

if (msgInput) msgInput.addEventListener('input', updateSendState);
if (msgInput) msgInput.addEventListener('keydown', (e)=>{ if (e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); if (!sendBtn.disabled) doSend(); } });
if (sendBtn) sendBtn.addEventListener('click', doSend);
if (nameInput) nameInput.addEventListener('blur', ()=>{ name = nameInput.value.trim() || name || 'anon'; localStorage.setItem('chat_name', name); });
if (nameInput) nameInput.addEventListener('keydown', (e)=>{ if(e.key === 'Enter'){ e.preventDefault(); name = nameInput.value.trim() || name || 'anon'; localStorage.setItem('chat_name', name); if (msgInput) msgInput.focus(); } });

updateSendState();
load();
setInterval(load, 3000);
</script>
</body>
</html>
