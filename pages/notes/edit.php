<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

// Get note ID from URL parameters
$noteId = $_GET['id'] ?? null;
$isEdit = $noteId !== null;

// Load existing note if editing
$note = null;
if ($isEdit) {
  $notes_path = __DIR__ . '/notes.json';
  if (file_exists($notes_path)) {
    $notes = json_decode(file_get_contents($notes_path), true) ?: [];
    foreach ($notes as $n) {
      if ($n['id'] === $noteId) {
        $note = $n;
        break;
      }
    }
  }
  
  // If note not found, redirect back to notes
  if (!$note) {
    header('Location: notes.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= $isEdit ? 'Edit Note' : 'New Note' ?> - OfflineBox</title>
  <link rel="stylesheet" href="notes.css">
  <meta name="robots" content="noindex">
</head>
<body>
  <div class="container">
    <div class="section">
      <div class="section-header">
        <a class="back-link" href="notes.php" title="Back to Notes" aria-label="Back to Notes">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
        <input type="text" class="editable-title" id="note-title" placeholder="<?= $isEdit ? 'Untitled Note' : 'New Note' ?>" maxlength="100" 
               value="<?= htmlspecialchars($note['title'] ?? '') ?>">
        <div class="header-actions">
          <button class="btn-primary" onclick="saveNote()">Save Note</button>
        </div>
      </div>
      
      <div class="edit-container">
        <div class="edit-form">
          <div class="form-group">
            <textarea id="note-content" placeholder="Start writing your note..."><?= htmlspecialchars($note['content'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    let isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    let noteId = <?= $noteId ? "'" . htmlspecialchars($noteId) . "'" : 'null' ?>;

    // Track original values to detect changes
    let originalTitle = document.getElementById('note-title').value;
    let originalContent = document.getElementById('note-content').value;
    let hasChanges = false;

    // Update save button state
    function updateSaveButton() {
      const saveBtn = document.querySelector('.btn-primary');
      if (hasChanges) {
        saveBtn.classList.add('enabled');
      } else {
        saveBtn.classList.remove('enabled');
      }
    }

    // Check for changes
    function checkForChanges() {
      const currentTitle = document.getElementById('note-title').value;
      const currentContent = document.getElementById('note-content').value;
      
      hasChanges = (currentTitle !== originalTitle || currentContent !== originalContent);
      updateSaveButton();
    }

    // Add event listeners to track changes
    document.getElementById('note-title').addEventListener('input', checkForChanges);
    document.getElementById('note-content').addEventListener('input', checkForChanges);

    // Initial state - disable save button for existing notes
    if (isEdit) {
      updateSaveButton();
    } else {
      // For new notes, enable immediately since any content is a change
      hasChanges = true;
      updateSaveButton();
    }

    function saveNote() {
      const title = document.getElementById('note-title').value.trim();
      const content = document.getElementById('note-content').value.trim();
      
      if (!title && !content) {
        alert('Please enter a title or content for your note.');
        return;
      }
      
      const action = isEdit ? 'update' : 'create';
      const data = {
        action: action,
        title: title,
        content: content
      };
      
      if (isEdit) {
        data.id = noteId;
      }
      
      fetch('api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update original values to reflect saved state
          originalTitle = document.getElementById('note-title').value;
          originalContent = document.getElementById('note-content').value;
          hasChanges = false;
          updateSaveButton();
          
          // If it was a new note, update the page to edit mode
          if (!isEdit && data.note && data.note.id) {
            // Update URL to edit mode without page reload
            window.history.replaceState({}, '', 'edit.php?id=' + encodeURIComponent(data.note.id));
            noteId = data.note.id;
            isEdit = true;
          }
        } else {
          alert('Error saving note: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error saving note');
      });
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      // Ctrl+S or Cmd+S to save
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (hasChanges) {
          saveNote();
        }
      }
    });

    // Auto-resize textarea
    const textarea = document.getElementById('note-content');
    textarea.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.max(300, this.scrollHeight) + 'px';
    });

    // Initial resize
    textarea.style.height = Math.max(300, textarea.scrollHeight) + 'px';
  </script>
</body>
</html>
