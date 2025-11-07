// Notes app JavaScript - handles navigation and delete operations

// Navigate to edit page for existing note
function editNote(noteId) {
  window.location.href = 'edit.php?id=' + encodeURIComponent(noteId);
}

// Delete a note
function deleteNote(noteId) {
  if (!confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
    return;
  }
  
  fetch('api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      action: 'delete',
      id: noteId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      location.reload(); // Refresh to show updated notes
    } else {
      alert('Error deleting note: ' + (data.error || 'Unknown error'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error deleting note');
  });
}

// Click on note card to edit (but not on action buttons)
document.addEventListener('click', function(e) {
  const noteCard = e.target.closest('.note-card');
  if (noteCard && !e.target.closest('.note-actions')) {
    const noteId = noteCard.dataset.id;
    if (noteId) {
      editNote(noteId);
    }
  }
});
