<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

// Load notes from JSON file
$notes_path = __DIR__ . '/notes.json';
$notes = [];
if (file_exists($notes_path)) {
  $notes_json = file_get_contents($notes_path);
  $notes = json_decode($notes_json, true) ?: [];
}

// Sort notes by updated time (most recent first)
usort($notes, function($a, $b) {
  return strtotime($b['updated'] ?? $b['created']) - strtotime($a['updated'] ?? $a['created']);
});

// Load apps to find notes app icon
$apps = load_apps_json(__DIR__ . '/../../data/json/apps.json');

// Find notes app for hero icon
$notes_app = null;
foreach ($apps as $app) {
  if (isset($app['id']) && $app['id'] === 'notes') {
    $notes_app = $app;
    break;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Notes - OfflineBox</title>
  <link rel="stylesheet" href="notes.css?v=<?= time() ?>">
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
        <h2 class="section-title">Notes</h2>
      </div>
      
      <div class="hero-card">
        <div class="hero-content">
          <div class="hero-icon">
            <?php
              $icon = '/data/icons/notes.svg'; // fallback
              if ($notes_app && isset($notes_app['icon'])) {
                $icon = $notes_app['icon'];
              }
              if (strpos($icon, '/data/') === 0) {
                $icon = '../..' . $icon;
              }
            ?>
            <img src="<?= htmlspecialchars($icon) ?>" alt="Notes" width="20" height="20" />
          </div>
          <div class="hero-text">
            <h3>Quick Notes</h3>
            <p>Create and manage your personal notes stored locally on this OfflineBox device.</p>
          </div>
          <div class="hero-actions">
            <a href="edit.php" class="hero-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              New Note
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-header">
        <h2 class="section-title">Your Notes (<?= count($notes) ?>)</h2>
      </div>
      
      <div class="notes-grid" id="notes-grid">
        <?php if (empty($notes)): ?>
          <div class="empty-state">
            <div class="empty-icon">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>No notes yet</h3>
            <p>Click "New Note" above to create your first note.</p>
          </div>
        <?php else: ?>
          <?php foreach ($notes as $note): ?>
            <div class="note-card" data-id="<?= htmlspecialchars($note['id']) ?>" onclick="editNote('<?= htmlspecialchars($note['id']) ?>')">
              <div class="note-header">
                <h4 class="note-title"><?= htmlspecialchars($note['title'] ?: 'Untitled') ?></h4>
                <div class="note-actions">
                  <a href="edit.php?id=<?= htmlspecialchars($note['id']) ?>" class="note-btn" title="Edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </a>
                  <button class="note-btn delete" onclick="deleteNote('<?= htmlspecialchars($note['id']) ?>')" title="Delete">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
              </div>
              <div class="note-content">
                <?= nl2br(htmlspecialchars($note['content'] ?? '')) ?>
              </div>
              <div class="note-meta">
                <span class="note-date">
                  <?php 
                    $date = $note['updated'] ?? $note['created'];
                    echo date('M j, Y g:i A', strtotime($date));
                  ?>
                </span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="notes.js"></script>
</body>
</html>
