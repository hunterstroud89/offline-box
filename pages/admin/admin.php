<?php
require_once __DIR__ . '/../../data/helpers/auto-config.php';

$apps = load_apps_json(__DIR__ . '/../../data/json/apps.json');

// Load background configuration
$background_file = __DIR__ . '/../../data/json/background.json';
$background_config = 'default';
if (file_exists($background_file)) {
  $bg_data = json_decode(file_get_contents($background_file), true);
  $background_config = $bg_data['background'] ?? 'default';
}

// Find admin app for hero icon
$admin_app = null;
foreach ($apps as $app) {
  if (isset($app['id']) && $app['id'] === 'admin') {
    $admin_app = $app;
    break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin</title>
  <link rel="stylesheet" href="admin.css">
  <?php if ($background_config !== 'default'): ?>
    <style>
      body {
        background-image: url('../../data/backgrounds/<?= htmlspecialchars($background_config) ?>');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
      }
      body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(18, 18, 18, 0.85);
        z-index: -1;
      }
    </style>
  <?php endif; ?>
</head>
<body>
  <div class="container">
    <!-- Hero Section -->
    <div class="section">
      <div class="section-header">
        <a class="back-link" href="../home/home.php" title="Back to Home" aria-label="Back to Home">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
        <h2 class="section-title">Admin</h2>
      </div>
      <div class="hero-card">
        <div class="hero-content">
          <div class="hero-icon">
            <?php if ($admin_app && isset($admin_app['icon'])): ?>
              <?php
                $admin_icon = $admin_app['icon'];
                if (strpos($admin_icon, '/data/') === 0) {
                  $admin_icon = '../..' . $admin_icon;
                }
              ?>
              <img src="<?= htmlspecialchars($admin_icon) ?>" alt="<?= htmlspecialchars($admin_app['label']) ?>" width="20" height="20" />
            <?php else: ?>
              <!-- Fallback SVG -->
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.14 12.94a7.14 7.14 0 0 0 0-1.88l2.03-1.58a.5.5 0 0 0 .12-.63l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.02 7.02 0 0 0-1.6-.93L14.5 2.5a.5.5 0 0 0-.5-.5h-3.99a.5.5 0 0 0-.5.5l-.38 2.53c-.57.22-1.1.5-1.6.83l-2.39-.96a.5.5 0 0 0-.6.22L2.7 8.85a.5.5 0 0 0 .12.63l2.03 1.58c-.05.31-.08.63-.08.95s.03.64.08.95L2.82 14.6a.5.5 0 0 0-.12.63l1.92 3.32c.14.24.44.35.7.26l2.39-.96c.5.33 1.03.61 1.6.83l.38 2.53c.05.28.28.47.5.47h3.99c.22 0 .45-.19.5-.47l.38-2.53c.57-.22 1.1-.5 1.6-.83l2.39.96c.26.09.56-.02.7-.26l1.92-3.32a.5.5 0 0 0-.12-.63l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z" fill="currentColor"/>
              </svg>
            <?php endif; ?>
          </div>
          <div class="hero-text">
            <h3>System Administration</h3>
            <p>Manage applications, services, and system settings.</p>
          </div>
        </div>
        
        <div class="admin-nav">
          <button class="nav-btn" onclick="scrollToSection('home-sections')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <polyline points="9,22 9,12 15,12 15,22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Home Sections
          </button>
          
          <button class="nav-btn" onclick="scrollToSection('apps-section')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
              <rect x="9" y="9" width="6" height="6" stroke="currentColor" stroke-width="2" fill="none"/>
            </svg>
            Applications
          </button>
          
          <button class="nav-btn" onclick="scrollToSection('folders-section')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Folders
          </button>
          
          <button class="nav-btn" onclick="scrollToSection('background-section')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
              <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2" fill="none"/>
              <polyline points="21,15 16,10 5,21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Background
          </button>
          
          <button class="nav-btn" onclick="scrollToSection('services-section')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
              <path d="M12 1v6m0 6v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="m12 12 5.196-3v6L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="m12 12-5.196-3v6L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Services
          </button>
          
          <button class="nav-btn" onclick="scrollToSection('system-section')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
              <line x1="8" y1="21" x2="16" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <line x1="12" y1="17" x2="12" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            System Info
          </button>
        </div>
      </div>
    </div>

    <!-- Home Sections Management -->
    <div class="section" id="home-sections">
      <div class="section-header">
        <h2 class="section-title">Home Sections</h2>
      </div>
      <div class="card">
        <div class="admin-list" id="home-sections-list">
          <!-- populated by JS -->
        </div>
        <div class="admin-controls">
          <button class="btn-primary" id="saveHomeSectionsBtn">Save Sections</button>
        </div>
      </div>
    </div>

    <!-- Applications Management -->
    <div class="section" id="apps-section">
      <div class="section-header">
        <h2 class="section-title">Applications</h2>
        <div class="header-actions">
          <button class="icon-btn" type="button" onclick="toggleEditMode()" title="Edit Applications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <button class="icon-btn" type="button" onclick="addApp()" title="Add Application">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="card">
        <div class="admin-list" id="apps-list">
          <?php foreach ($apps as $index => $app): ?>
            <div class="admin-item" data-index="<?= $index ?>" draggable="true">
              <button class="drag-handle" type="button" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 6h8M10 12h8M10 18h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
              <img class="admin-icon" src="<?= htmlspecialchars('../../' . ltrim($app['icon'], '/')) ?>" alt="<?= htmlspecialchars($app['label']) ?>" />
              <div class="admin-info">
                <input type="text" class="admin-name-input" value="<?= htmlspecialchars($app['label']) ?>" data-field="label" readonly />
                <div class="hardcoded-url-checkbox" style="display: none;">
                  <label>
                    <input type="checkbox" class="admin-hardcoded-input" data-field="hardcoded_url" <?= (!empty($app['hardcoded_url']) && $app['hardcoded_url']) ? 'checked' : '' ?> />
                    <span>Hardcode</span>
                  </label>
                </div>
                <input type="text" class="admin-url-input" value="<?= htmlspecialchars($app['url']) ?>" data-field="url" placeholder="URL" readonly style="display: none;" />
                <input type="text" class="admin-icon-input" value="<?= htmlspecialchars($app['icon']) ?>" data-field="icon" placeholder="Icon path" readonly style="display: none;" />
                <select class="admin-folder-select" data-field="folder" style="display: none;">
                  <option value="">No Folder</option>
                  <!-- Populated by JavaScript -->
                </select>
              </div>
              <div class="admin-actions">
                <div class="visibility-toggle <?php echo ($app['visible'] ?? true) ? 'active' : ''; ?>" onclick="toggleVisibility(<?php echo $index; ?>)">
                  <div class="toggle-indicator"></div>
                </div>
                <button class="icon-btn btn-delete" onclick="deleteApp(<?php echo $index; ?>)" style="display: none;" title="Delete Application">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="admin-controls">
          <button class="btn-primary" id="saveAppsBtn" type="button" onclick="saveChanges()" disabled>Save Changes</button>
        </div>
      </div>
    </div>

    <!-- Folders Management -->
    <div class="section" id="folders-section">
      <div class="section-header">
        <h2 class="section-title">Folders</h2>
        <div class="header-actions">
          <button class="icon-btn" type="button" onclick="addNewFolder()" title="Add New Folder">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="card">
        <div class="folders-grid" id="folders-list">
          <!-- populated by JS -->
        </div>
        <div class="admin-controls">
          <button class="btn-primary" id="saveFoldersBtn" type="button" onclick="saveFolders()" disabled>Save Changes</button>
        </div>
      </div>
    </div>

    <!-- Background Selection -->
    <div class="section" id="background-section">
      <div class="section-header">
        <h2 class="section-title">Background</h2>
        <div class="header-actions">
          <button class="icon-btn" type="button" id="uploadBackgroundBtn" title="Upload Background">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="card">
        <div class="background-grid" id="background-grid">
          <div class="background-option" data-background="default">
            <div class="background-preview default-background">
              <div class="default-indicator">Default</div>
            </div>
            <div class="background-name">Default</div>
          </div>
          <!-- Background images will be loaded here by JavaScript -->
        </div>
        <input type="file" id="backgroundFileInput" accept="image/*,.gif" style="display: none;">
      </div>
    </div>

    <!-- Services Management -->
    <div class="section" id="services-section">
      <div class="section-header">
        <h2 class="section-title">Services</h2>
      </div>
      <div class="card">
        <div class="services-container">
          <div class="services-grid" id="services-grid">
            <!-- Services will be populated by JavaScript -->
          </div>
        </div>
      </div>
    </div>

    <!-- System Information -->
    <div class="section" id="system-section">
      <div class="section-header">
        <h2 class="section-title">System</h2>
        <div class="header-actions">
          <button class="icon-btn" type="button" onclick="window.open('/pages/system-info/system-info.php', '_blank')" title="View System Information">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <polyline points="15,3 21,3 21,9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <line x1="10" y1="14" x2="21" y2="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="card">
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">PHP Version</div>
            <div class="info-value"><?= phpversion() ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Server Software</div>
            <div class="info-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Document Root</div>
            <div class="info-value"><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></div>
          </div>
          <div class="info-item">
            <div class="info-label">Server Name</div>
            <div class="info-value"><?= $_SERVER['SERVER_NAME'] ?? 'Unknown' ?></div>
          </div>
        </div>
      </div>
    </div>
    
    
  </div>

  <script>
    let apps = <?= json_encode($apps) ?>;
    let hasChanges = false;
    let isEditMode = false;
  let showHiddenApps = false; // control whether hidden apps are expanded in the list

    function applyEditModeState() {
      const editBtn = document.querySelector('#apps-section .header-actions .icon-btn');
      const inputs = document.querySelectorAll('#apps-list .admin-name-input, #apps-list .admin-url-input, #apps-list .admin-icon-input');
      const deleteButtons = document.querySelectorAll('#apps-list .btn-delete');
      const urlInputs = document.querySelectorAll('#apps-list .admin-url-input');
      const iconInputs = document.querySelectorAll('#apps-list .admin-icon-input');
      const folderSelects = document.querySelectorAll('#apps-list .admin-folder-select');
      const hardcodedCheckboxes = document.querySelectorAll('#apps-list .hardcoded-url-checkbox');
      const visibilityToggles = document.querySelectorAll('#apps-list .visibility-toggle');
      
      if (isEditMode) {
        editBtn.title = 'Done Editing';
        inputs.forEach(input => input.removeAttribute('readonly'));
        deleteButtons.forEach(btn => btn.style.display = 'inline-flex');
        urlInputs.forEach(input => input.style.display = 'block');
        iconInputs.forEach(input => input.style.display = 'block');
        folderSelects.forEach(select => {
          select.style.display = 'block';
          populateFolderSelect(select);
        });
        hardcodedCheckboxes.forEach(checkbox => checkbox.style.display = 'block');
        visibilityToggles.forEach(toggle => toggle.style.display = 'none');
      } else {
        editBtn.title = 'Edit Applications';
        inputs.forEach(input => input.setAttribute('readonly', true));
        deleteButtons.forEach(btn => btn.style.display = 'none');
        urlInputs.forEach(input => input.style.display = 'none');
        iconInputs.forEach(input => input.style.display = 'none');
        folderSelects.forEach(select => select.style.display = 'none');
        hardcodedCheckboxes.forEach(checkbox => checkbox.style.display = 'none');
        hardcodedCheckboxes.forEach(checkbox => checkbox.style.display = 'none');
        visibilityToggles.forEach(toggle => toggle.style.display = 'flex');
      }
    }

    function toggleEditMode() {
      isEditMode = !isEditMode;
      applyEditModeState();
    }

    function populateFolderSelect(select) {
      const currentValue = select.dataset.currentFolder || '';
      
      // Get unique folders from apps
      const folders = new Set();
      apps.forEach(app => {
        if (app.folder && app.folder.trim()) {
          folders.add(app.folder);
        }
      });
      
      // Clear and populate select
      select.innerHTML = '<option value="">No Folder</option>';
      Array.from(folders).sort().forEach(folder => {
        const option = document.createElement('option');
        option.value = folder;
        option.textContent = folder;
        if (folder === currentValue) {
          option.selected = true;
        }
        select.appendChild(option);
      });
      
      // Set current value if not in list but exists
      if (currentValue && !folders.has(currentValue)) {
        const option = document.createElement('option');
        option.value = currentValue;
        option.textContent = currentValue;
        option.selected = true;
        select.appendChild(option);
      }
    }

    function toggleVisibility(index) {
      // Ensure visible property exists, default to true if missing
      if (apps[index].visible === undefined) {
        apps[index].visible = true;
      }
      apps[index].visible = apps[index].visible === false ? true : false;
      hasChanges = true;
      updateToggleIndicator(index);
      updateSaveButton();
      // Don't re-render the list until save - just update the toggle indicator
    }

    function updateToggleIndicator(index) {
      const toggle = document.querySelector(`[data-index="${index}"] .visibility-toggle`);
      // Handle missing visible property, default to true
      const isVisible = apps[index].visible !== false;
      if (isVisible) {
        toggle.classList.add('active');
      } else {
        toggle.classList.remove('active');
      }
    }

    function deleteApp(index) {
      if (confirm('Are you sure you want to delete "' + apps[index].label + '"?')) {
        apps.splice(index, 1);
        hasChanges = true;
        updateAppsList();
        updateSaveButton();
      }
    }

    function addApp() {
      // Create a new blank app
      const newApp = {
        id: '',
        label: '',
        icon: '',
        url: '',
        visible: true
      };
      
      apps.push(newApp);
      hasChanges = true;
      
      // Enter edit mode if not already
      if (!isEditMode) {
        isEditMode = true;
      }
      
      updateAppsList();
      applyEditModeState();
      updateSaveButton();
      
      // Focus on the new app's name field
      setTimeout(() => {
        const newIndex = apps.length - 1;
        const nameInput = document.querySelector(`[data-index="${newIndex}"] .admin-name-input`);
        if (nameInput) {
          nameInput.focus();
          nameInput.select();
        }
      }, 100);
    }

  // expose for inline onclick handlers
  window.addApp = addApp;

    function updateAppsList() {
  const container = document.querySelector('#apps-list');
      container.innerHTML = '';

      // Partition apps into visible and hidden groups (visible by default)
      const visibleIndices = [];
      const hiddenIndices = [];
      apps.forEach((a, i) => { if (a.visible === false) hiddenIndices.push(i); else visibleIndices.push(i); });

      function makeItem(app, index) {
        const item = document.createElement('div');
        item.className = 'admin-item';
        item.setAttribute('data-index', index);

        const readonlyAttr = isEditMode ? '' : 'readonly';
        const deleteDisplay = isEditMode ? 'inline-block' : 'none';

        item.innerHTML = `
          <img class="admin-icon" src="../../${app.icon.replace(/^\//, '')}" alt="${app.label}" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB4PSIzIiB5PSIzIiB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHJ4PSI0IiBzdHJva2U9IiM4MDgwODAiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIvPjxjaXJjbGUgY3g9IjgiIGN5PSI4IiByPSIyIiBmaWxsPSIjODA4MDgwIi8+PHBhdGggZD0iTTIxIDIxTDE1IDEzTDEwIDE4TDMgMTIiIHN0cm9rZT0iIzgwODA4MCIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz48L3N2Zz4='; this.style.opacity='0.5';" />
          <div class="admin-info">
            <input type="text" class="admin-name-input" value="${app.label}" data-field="label" placeholder="App Name" ${readonlyAttr} />
            <div class="hardcoded-url-checkbox" style="display: ${isEditMode ? 'block' : 'none'};">
              <label>
                <input type="checkbox" class="admin-hardcoded-input" data-field="hardcoded_url" ${app.hardcoded_url ? 'checked' : ''} />
                <span>Hardcode</span>
              </label>
            </div>
            <input type="text" class="admin-url-input" value="${app.url}" data-field="url" placeholder="URL or file path" ${readonlyAttr} style="display: ${isEditMode ? 'block' : 'none'};" />
            <input type="text" class="admin-icon-input" value="${app.icon}" data-field="icon" placeholder="Icon path" ${readonlyAttr} style="display: ${isEditMode ? 'block' : 'none'};" />
            <select class="admin-folder-select" data-field="folder" data-current-folder="${app.folder || ''}" style="display: ${isEditMode ? 'block' : 'none'};">
              <option value="">No Folder</option>
            </select>
          </div>
          <div class="admin-actions">
            <div class="visibility-toggle ${app.visible !== false ? 'active' : ''}" onclick="toggleVisibility(${index})" style="display: ${isEditMode ? 'none' : 'flex'};">
              <div class="toggle-indicator"></div>
            </div>
            <button class="icon-btn btn-delete" onclick="deleteApp(${index})" style="display: ${deleteDisplay};" title="Delete Application">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
          <button class="drag-handle" type="button" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 6h8M10 12h8M10 18h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        `;

        item.setAttribute('draggable', 'true');
        container.appendChild(item);

        // Attach drag handlers
        item.addEventListener('dragstart', (e) => {
          // Only allow drag when starting from the drag-handle. Be robust if e.target is a non-element (text/SVG child).
          const handle = (e.target && e.target.closest) ? e.target.closest('.drag-handle') : null;
          if (!handle) { e.preventDefault(); return; }
          e.dataTransfer.setData('text/plain', e.currentTarget.getAttribute('data-index'));
          e.dataTransfer.effectAllowed = 'move';
          e.currentTarget.classList.add('dragging');
        });

        item.addEventListener('dragover', (e) => { e.preventDefault(); e.currentTarget.classList.add('drag-over'); e.dataTransfer.dropEffect = 'move'; });
        item.addEventListener('dragleave', (e) => { e.currentTarget.classList.remove('drag-over'); });
        item.addEventListener('drop', (e) => {
          e.preventDefault(); e.currentTarget.classList.remove('drag-over');
          const src = parseInt(e.dataTransfer.getData('text/plain'));
          const dst = parseInt(e.currentTarget.getAttribute('data-index'));
          if (isNaN(src) || isNaN(dst) || src === dst) return;
          const moved = apps.splice(src, 1)[0];
          apps.splice(dst, 0, moved);
          hasChanges = true; updateAppsList(); updateSaveButton();
        });
        item.addEventListener('dragend', (e) => { e.currentTarget.classList.remove('dragging'); document.querySelectorAll('.admin-item.drag-over').forEach(n => n.classList.remove('drag-over')); });

        return item;
      }

      // Append visible items first and keep a reference to the last visible element
      let lastVisibleEl = null;
      visibleIndices.forEach(i => { lastVisibleEl = makeItem(apps[i], i); });

      // If there are hidden apps, show a right-aligned toggle button below visible apps
      if (hiddenIndices.length > 0) {
        const forceExpand = isEditMode;

        // remove bottom border on the last visible item so the toggle reads as a single divider
        if (lastVisibleEl) lastVisibleEl.classList.add('no-border-bottom');

        // Add toggle row (right-aligned)
        const toggleRow = document.createElement('div');
        toggleRow.className = 'admin-show-hidden';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-small show-hidden-btn';
        btn.textContent = (showHiddenApps || forceExpand) ? 'Hide' : `Show all (${hiddenIndices.length})`;
        btn.addEventListener('click', ()=>{ showHiddenApps = !showHiddenApps; updateAppsList(); });
        toggleRow.appendChild(btn);
        container.appendChild(toggleRow);

        // If expanded (either by toggle or because we're in edit mode), render separator and hidden items
        if (showHiddenApps || forceExpand) {
          const sep = document.createElement('div');
          sep.className = 'admin-separator';
          container.appendChild(sep);
          hiddenIndices.forEach(i => makeItem(apps[i], i));
        }
      }

      // Reapply edit mode state to new elements
      applyEditModeState();

      // Ensure the list allows dragover for smooth dropping
  const list = document.querySelector('#apps-list');
  list.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; });
    }

    function updateSaveButton() {
      const appsSaveBtn = document.getElementById('saveAppsBtn');
      if (appsSaveBtn) appsSaveBtn.disabled = !hasChanges;
    }

    // Track input changes
    document.addEventListener('input', function(e) {
      if (e.target.matches('.admin-name-input, .admin-url-input, .admin-icon-input')) {
        const index = parseInt(e.target.closest('.admin-item').getAttribute('data-index'));
        const field = e.target.getAttribute('data-field');
        apps[index][field] = e.target.value;
        
        // Auto-generate ID and icon path when label is entered
        if (field === 'label' && e.target.value.trim()) {
          const id = e.target.value.trim().toLowerCase().replace(/[^a-z0-9]/g, '');
          apps[index].id = id;
          
          // Auto-suggest icon path if empty
          if (!apps[index].icon.trim()) {
            apps[index].icon = `/data/icons/${id}.svg`;
            const iconInput = e.target.closest('.admin-item').querySelector('.admin-icon-input');
            if (iconInput) {
              iconInput.value = apps[index].icon;
            }
          }
        }
        
        hasChanges = true;
        updateSaveButton();
        
        // Update the icon image if it's the icon field
        if (field === 'icon') {
          const iconImg = e.target.closest('.admin-item').querySelector('.admin-icon');
          iconImg.src = '../../' + e.target.value.replace(/^\//, '');
          iconImg.style.opacity = '1'; // Reset opacity when loading new icon
        }
      }
    });
    
    // Track folder select changes
    document.addEventListener('change', function(e) {
      if (e.target.matches('.admin-folder-select')) {
        const index = parseInt(e.target.closest('.admin-item').getAttribute('data-index'));
        const field = e.target.getAttribute('data-field');
        if (e.target.value === '') {
          delete apps[index][field]; // Remove folder property if "No Folder" selected
        } else {
          apps[index][field] = e.target.value;
        }
        
        hasChanges = true;
        updateSaveButton();
      }
    });
    
    // Track checkbox changes  
    document.addEventListener('change', function(e) {
      if (e.target.matches('.admin-hardcoded-input')) {
        const index = parseInt(e.target.closest('.admin-item').getAttribute('data-index'));
        const field = e.target.getAttribute('data-field');
        apps[index][field] = e.target.checked;
        
        hasChanges = true;
        updateSaveButton();
      }
    });

    async function saveChanges() {
      if (!hasChanges) {
        alert('No changes to save.');
        return;
      }
      
      try {
        // Basic client-side validation: ensure each app has at least a label (icon/url may be empty)
        for (let i = 0; i < apps.length; i++) {
          const a = apps[i];
          if (!a.label || !a.label.toString().trim()) {
            alert('Please fill in a label for all apps before saving. Missing on row ' + (i+1));
            return;
          }
        }

        const response = await fetch('save-apps.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(apps)
        });
        
        const text = await response.text();
        let json;
        try { json = JSON.parse(text); } catch(e) { json = null; }
        if (response.ok) {
          hasChanges = false;
          updateSaveButton();
          updateAppsList(); // Re-render the list to move items between visible/hidden groups
          console.info('Changes saved successfully');
        } else {
          const msg = (json && json.error) ? json.error : (json && json.message) ? json.message : text || 'Failed to save changes';
          alert('Save failed: ' + msg);
          throw new Error(msg);
        }
      } catch (error) {
        console.error('Save error:', error);
        alert('Failed to save changes. Check the console for details.');
      }
    }

  // expose for inline onclick handlers
  window.saveChanges = saveChanges;

    // Initialize the page: render JS-driven list so drag handles and buttons appear
    document.addEventListener('DOMContentLoaded', function() {
      updateAppsList();
      applyEditModeState();
      updateSaveButton();
      
      // Initialize folder select values
      apps.forEach((app, index) => {
        const select = document.querySelector(`[data-index="${index}"] .admin-folder-select`);
        if (select) {
          select.dataset.currentFolder = app.folder || '';
        }
      });
    });

    // Home Sections management
    (function(){
      const sectionsUrl = '../../data/json/home_sections.json';
      const listEl = document.getElementById('home-sections-list');
      const saveBtn = document.getElementById('saveHomeSectionsBtn');
      let sectionsArray = [];

      function renderSections(){
        if (!listEl) return;
        listEl.innerHTML = '';
        
        // Sort by order
        sectionsArray.sort((a, b) => (a.order || 999) - (b.order || 999));
        
        sectionsArray.forEach(section => {
          const key = section.key;
          const row = document.createElement('div');
          row.className = 'admin-item';
          row.setAttribute('data-key', key);
          row.setAttribute('draggable', 'true');
          
          // Choose an inline svg for the section key
          let svg = '';
          if (key === 'hero') {
            svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
          } else if (key === 'favorites') {
            svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>';
          } else if (key === 'recent_files') {
            svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 3h18v18H3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><path d="M8 7h8M8 11h8M8 15h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
          }

          row.innerHTML = `
            <div class="admin-icon" style="width:28px;height:28px;margin-right:8px;display:flex;align-items:center;justify-content:center;">${svg}</div>
            <div class="admin-info" style="flex:1;min-width:0">
              <div style="font-weight:600">${key.replace('_',' ')}</div>
            </div>
            <div class="admin-actions">
              <div class="visibility-toggle ${section.visible ? 'active' : ''}" data-key="${key}">
                <div class="toggle-indicator"></div>
              </div>
            </div>
            <button class="drag-handle" type="button" draggable="true" title="Drag to reorder" aria-label="Drag to reorder">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 6h8M10 12h8M10 18h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          `;
          listEl.appendChild(row);
        });

        // attach toggle handlers
        listEl.querySelectorAll('.visibility-toggle').forEach(el=>{
          el.addEventListener('click', ()=>{
            const key = el.getAttribute('data-key');
            const section = sectionsArray.find(s => s.key === key);
            if (section) {
              section.visible = !section.visible;
              el.classList.toggle('active', section.visible);
              saveBtn.disabled = false;
            }
          });
        });

        // Attach drag handlers for reordering
        listEl.querySelectorAll('.admin-item').forEach(item=>{
          item.addEventListener('dragstart', (e)=>{
            const handle = (e.target && e.target.closest) ? e.target.closest('.drag-handle') : null;
            if (!handle) { e.preventDefault(); return; }
            e.dataTransfer.setData('text/plain', item.getAttribute('data-key'));
            e.dataTransfer.effectAllowed = 'move';
            item.classList.add('dragging');
          });
          item.addEventListener('dragover', (e)=>{ e.preventDefault(); item.classList.add('drag-over'); e.dataTransfer.dropEffect = 'move'; });
          item.addEventListener('dragleave', ()=> item.classList.remove('drag-over'));
          item.addEventListener('drop', (e)=>{
            e.preventDefault();
            item.classList.remove('drag-over');
            const srcKey = e.dataTransfer.getData('text/plain');
            const dstKey = item.getAttribute('data-key');
            if (!srcKey || srcKey === dstKey) return;

            // Find the sections
            const srcIndex = sectionsArray.findIndex(s => s.key === srcKey);
            const dstIndex = sectionsArray.findIndex(s => s.key === dstKey);
            if (srcIndex === -1 || dstIndex === -1) return;

            // Remove source item and insert at destination
            const [movedItem] = sectionsArray.splice(srcIndex, 1);
            sectionsArray.splice(dstIndex, 0, movedItem);
            
            // Update order numbers
            sectionsArray.forEach((section, index) => {
              section.order = index + 1;
            });
            
            renderSections();
            saveBtn.disabled = false;
          });
          item.addEventListener('dragend', ()=> item.classList.remove('dragging'));
        });

        // Allow dropping on the container for smoother UX
        listEl.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; });
      }

      async function loadSections(){
        try{
          const res = await fetch(sectionsUrl);
          const data = await res.json();
          
          // Handle both old object format and new array format
          if (Array.isArray(data)) {
            sectionsArray = data;
          } else {
            // Convert old format to new format
            sectionsArray = [
              { key: 'hero', visible: data.hero !== false, order: 1 },
              { key: 'favorites', visible: data.favorites !== false, order: 2 },
              { key: 'recent_files', visible: data.recent_files !== false, order: 3 }
            ];
          }
          
          renderSections();
          if (saveBtn) saveBtn.disabled = true;
        }catch(e){
          console.error('Failed loading sections', e);
          // Use defaults if loading fails
          sectionsArray = [
            { key: 'hero', visible: true, order: 1 },
            { key: 'favorites', visible: true, order: 2 },
            { key: 'recent_files', visible: true, order: 3 }
          ];
          renderSections();
        }
      }

      async function saveSections(){
        try{
          const res = await fetch('home-sections-save.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(sectionsArray) });
        if (res.ok) { 
          saveBtn.disabled = true; 
          console.info('Home sections saved');
          try {
            // Signal other open pages that home sections changed
            if (typeof BroadcastChannel !== 'undefined') {
              const bc = new BroadcastChannel('offlinebox');
              bc.postMessage({ type: 'home_sections_saved' });
              setTimeout(() => bc.close(), 100);
            }
          } catch (e) { /* ignore */ }
        }
          else { throw new Error('Save failed'); }
        }catch(e){ console.error(e); alert('Failed to save home sections'); }
      }

      if (saveBtn) saveBtn.addEventListener('click', saveSections);
      loadSections();
    })();

    // Background Management
    (function() {
      let selectedBackground = 'default';
      
      function loadBackgrounds() {
        fetch('../../data/helpers/backgrounds-api.php')
          .then(response => response.json())
          .then(data => {
            displayBackgrounds(data.backgrounds || []);
            selectedBackground = data.current || 'default';
            updateSelection();
          })
          .catch(error => {
            console.error('Error loading backgrounds:', error);
          });
      }
      
      function displayBackgrounds(backgrounds) {
        const grid = document.getElementById('background-grid');
        
        // Keep default option, add background images after it
        const defaultOption = grid.querySelector('.background-option[data-background="default"]');
        
        // Clear existing background options (except default)
        const existingOptions = grid.querySelectorAll('.background-option:not([data-background="default"])');
        existingOptions.forEach(option => option.remove());
        
        backgrounds.forEach(bg => {
          const option = document.createElement('div');
          option.className = 'background-option';
          option.setAttribute('data-background', bg.filename);
          
          const preview = document.createElement('div');
          preview.className = 'background-preview';
          preview.style.backgroundImage = `url('../../data/backgrounds/${bg.filename}')`;
          
          const name = document.createElement('div');
          name.className = 'background-name';
          name.textContent = bg.name;
          
          option.appendChild(preview);
          option.appendChild(name);
          
          option.addEventListener('click', () => selectBackground(bg.filename));
          
          grid.appendChild(option);
        });
      }
      
      function selectBackground(background) {
        selectedBackground = background;
        updateSelection();
        saveBackground();
      }
      
      function updateSelection() {
        const options = document.querySelectorAll('.background-option');
        options.forEach(option => {
          const bgValue = option.getAttribute('data-background');
          if (bgValue === selectedBackground) {
            option.classList.add('selected');
          } else {
            option.classList.remove('selected');
          }
        });
      }
      
      function saveBackground() {
        const formData = new FormData();
        formData.append('background', selectedBackground);
        
        fetch('../../data/helpers/backgrounds-api.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Notify other windows of background change
            if (typeof BroadcastChannel !== 'undefined') {
              try {
                const bc = new BroadcastChannel('offlinebox');
                bc.postMessage({type: 'background_changed', background: selectedBackground});
              } catch(e) {}
            }
          } else {
            alert('Failed to save background: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error saving background:', error);
          alert('Failed to save background');
        });
      }
      
      function handleFileUpload() {
        const fileInput = document.getElementById('backgroundFileInput');
        const file = fileInput.files[0];
        
        if (!file) return;
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
          alert('Please select an image file');
          return;
        }
        
        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
          alert('File size must be less than 10MB');
          return;
        }
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'upload');
        
        const uploadBtn = document.getElementById('uploadBackgroundBtn');
        uploadBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="17,8 12,3 7,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="3" x2="12" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        uploadBtn.disabled = true;
        
        fetch('../../data/helpers/backgrounds-api.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            loadBackgrounds(); // Reload to show new background
            fileInput.value = ''; // Clear file input
          } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error uploading background:', error);
          alert('Upload failed');
        })
        .finally(() => {
          uploadBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
          uploadBtn.disabled = false;
        });
      }
      
      // Initialize background management
      const defaultOption = document.querySelector('.background-option[data-background="default"]');
      if (defaultOption) {
        defaultOption.addEventListener('click', () => selectBackground('default'));
      }
      
      const uploadBtn = document.getElementById('uploadBackgroundBtn');
      const fileInput = document.getElementById('backgroundFileInput');
      
      if (uploadBtn) {
        uploadBtn.addEventListener('click', () => fileInput.click());
      }
      
      if (fileInput) {
        fileInput.addEventListener('change', handleFileUpload);
      }
      
      loadBackgrounds();
    })();

    // Listen for background changes and update admin page background
    (function() {
      function updateAdminBackground(background) {
        if (background === 'default') {
          // Remove background image and overlay
          document.body.style.backgroundImage = '';
          const existingOverlay = document.body.querySelector('::before');
          if (document.body.style.position) {
            document.body.style.position = '';
          }
          // Remove any existing background styles
          const existingStyle = document.getElementById('dynamic-background-style');
          if (existingStyle) {
            existingStyle.remove();
          }
        } else {
          // Add background image and overlay
          let styleEl = document.getElementById('dynamic-background-style');
          if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'dynamic-background-style';
            document.head.appendChild(styleEl);
          }
          
          styleEl.textContent = `
            body {
              background-image: url('../../data/backgrounds/${background}');
              background-size: cover;
              background-position: center;
              background-repeat: no-repeat;
              background-attachment: fixed;
            }
            body::before {
              content: '';
              position: fixed;
              top: 0;
              left: 0;
              right: 0;
              bottom: 0;
              background: rgba(18, 18, 18, 0.85);
              z-index: -1;
            }
          `;
        }
      }

      if (typeof BroadcastChannel !== 'undefined') {
        try {
          const bc = new BroadcastChannel('offlinebox');
          bc.addEventListener('message', (m) => {
            if (m.data && m.data.type === 'background_changed') {
              updateAdminBackground(m.data.background);
            }
          });
        } catch(e) {}
      }
    })();

    // Navigation function
    function scrollToSection(sectionId) {
      const element = document.getElementById(sectionId);
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    // Folders Management
    let foldersData = [];
    let foldersChanged = false;

    function loadFolders() {
      // Extract unique folders from apps data (including placeholder apps to detect empty folders)
      const folders = new Set();
      apps.forEach(app => {
        if (app.folder && (app.visible !== false || app.is_folder_placeholder)) {
          folders.add(app.folder);
        }
      });

      foldersData = Array.from(folders).map(folderName => ({
        name: folderName,
        apps: apps.filter(app => app.folder === folderName && app.visible !== false && !app.is_folder_placeholder)
      }));

      renderFolders();
    }

    function renderFolders() {
      const container = document.getElementById('folders-list');
      container.innerHTML = '';

      foldersData.forEach((folder, index) => {
        const folderEl = document.createElement('div');
        folderEl.className = 'folder-item';
        folderEl.innerHTML = `
          <div class="folder-actions">
            <button onclick="editFolder(${index})" title="Edit Folder Name">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <button class="delete-btn" onclick="deleteFolder(${index})" title="Delete Folder">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="m19,6 -2,14a2,2 0,0,1 -2,2H9a2,2 0,0,1 -2,-2L5,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
          <div class="folder-header">
            <div class="folder-info">
              <div class="folder-name">
                <span class="folder-name-text">${folder.name}</span>
                <input type="text" class="folder-name-input" value="${folder.name}">
              </div>
              <div class="folder-apps">${folder.apps.length} app${folder.apps.length !== 1 ? 's' : ''}</div>
              <div class="folder-count">Folder</div>
            </div>
          </div>
        `;
        container.appendChild(folderEl);
      });
    }

    function addNewFolder() {
      const folderName = prompt('Enter folder name:');
      if (folderName && folderName.trim()) {
        const trimmedName = folderName.trim();
        if (!foldersData.find(f => f.name === trimmedName)) {
          // Create a placeholder app to represent the folder
          const placeholderApp = {
            id: `folder_${trimmedName.toLowerCase().replace(/[^a-z0-9]/g, '_')}_placeholder`,
            label: `${trimmedName} Folder`,
            icon: "/data/icons/home.png",
            url: "#",
            visible: false,
            folder: trimmedName,
            is_folder_placeholder: true
          };
          
          // Add placeholder to apps array
          apps.push(placeholderApp);
          
          foldersChanged = true;
          document.getElementById('saveFoldersBtn').disabled = false;
          loadFolders(); // Reload to show the new folder
        } else {
          alert('A folder with this name already exists.');
        }
      }
    }

    function editFolder(index) {
      const folderItem = document.querySelectorAll('.folder-item')[index];
      const nameText = folderItem.querySelector('.folder-name-text');
      const nameInput = folderItem.querySelector('.folder-name-input');
      
      // Show input, hide text
      nameText.style.display = 'none';
      nameInput.style.display = 'block';
      nameInput.focus();
      nameInput.select();
      
      // Add editing class
      folderItem.classList.add('editing');

      const saveEdit = () => {
        const newName = nameInput.value.trim();
        if (newName && newName !== foldersData[index].name) {
          if (!foldersData.find((f, i) => i !== index && f.name === newName)) {
            const oldName = foldersData[index].name;
            foldersData[index].name = newName;
            
            // Update apps data
            apps.forEach(app => {
              if (app.folder === oldName) {
                app.folder = newName;
              }
            });
            
            foldersChanged = true;
            document.getElementById('saveFoldersBtn').disabled = false;
            renderFolders();
          } else {
            alert('A folder with this name already exists.');
            nameInput.value = foldersData[index].name;
            finishEdit();
          }
        } else {
          finishEdit();
        }
      };

      const finishEdit = () => {
        nameText.style.display = 'block';
        nameInput.style.display = 'none';
        folderItem.classList.remove('editing');
        nameInput.removeEventListener('blur', saveEdit);
        nameInput.removeEventListener('keydown', handleKeydown);
      };

      const handleKeydown = (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          saveEdit();
        } else if (e.key === 'Escape') {
          nameInput.value = foldersData[index].name;
          finishEdit();
        }
      };

      nameInput.addEventListener('blur', saveEdit);
      nameInput.addEventListener('keydown', handleKeydown);
    }

    function deleteFolder(index) {
      const folder = foldersData[index];
      if (confirm(`Delete folder "${folder.name}"? Apps in this folder will become ungrouped.`)) {
        // Remove folder from apps and remove any placeholder apps
        // Process in reverse order to avoid index shifting issues when splicing
        for (let i = apps.length - 1; i >= 0; i--) {
          const app = apps[i];
          if (app.folder === folder.name) {
            if (app.is_folder_placeholder) {
              // Remove placeholder apps entirely
              apps.splice(i, 1);
            } else {
              // Just remove folder assignment from real apps
              delete app.folder;
            }
          }
        }
        
        foldersData.splice(index, 1);
        foldersChanged = true;
        document.getElementById('saveFoldersBtn').disabled = false;
        renderFolders();
      }
    }

    function saveFolders() {
      if (!foldersChanged) return;
      
      const saveBtn = document.getElementById('saveFoldersBtn');
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';
      
      fetch('admin-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_apps', apps: apps })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          foldersChanged = false;
          saveBtn.textContent = 'Save Changes';
          loadFolders(); // Reload to sync with any changes
        } else {
          alert('Error: ' + (data.error || 'Failed to save folders'));
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save Changes';
        }
      })
      .catch(e => {
        console.error('Save error:', e);
        alert('Error saving folders');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
      });
    }

    // Initialize folders and services when page loads
    document.addEventListener('DOMContentLoaded', () => {
      loadFolders();
      loadServices();
    });

    // Simple Services Display
    async function loadServices() {
      const container = document.getElementById('services-grid');
      
      // Show loading state
      container.innerHTML = `
        <div class="service-loading">
          <div class="loading-spinner"></div>
          <span>Checking services...</span>
        </div>
      `;
      
      try {
        const response = await fetch('check-services.php');
        const data = await response.json();
        
        if (data.success) {
          container.innerHTML = data.services.map(service => `
            <div class="service-item">
              <div class="service-actions">
                <a href="${service.url}" target="_blank" class="service-link" title="Open ${service.name}">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 17L17 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 7h10v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>
              </div>
              <div class="service-header">
                <div class="service-info">
                  <div class="service-name">${service.name}</div>
                  <div class="service-description">${service.description}</div>
                  <div class="service-port">Port: ${service.port}</div>
                </div>
                <div class="service-status">
                  <div class="service-indicator ${service.status}"></div>
                  <span class="service-text ${service.status}">${service.status}</span>
                  ${service.name === 'Kiwix' ? `
                    <button class="service-restart-btn" onclick="restartKiwix()" title="Restart Kiwix with all ZIM files">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M23 4v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>Restart</span>
                    </button>
                  ` : ''}
                </div>
              </div>
            </div>
          `).join('');
        } else {
          throw new Error(data.error || 'Failed to load services');
        }
        
      } catch (error) {
        console.error('Error loading services:', error);
        container.innerHTML = '<div class="service-error">Error loading services</div>';
      }
    }

    // Restart Kiwix function
    async function restartKiwix() {
      const button = event.target.closest('button');
      const originalContent = button.innerHTML;
      
      // Show loading state
      button.innerHTML = `
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 18v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M4.93 4.93l2.83 2.83" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M16.24 16.24l2.83 2.83" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M2 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M18 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M4.93 19.07l2.83-2.83" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M16.24 7.76l2.83-2.83" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      `;
      button.disabled = true;
      
      try {
        const response = await fetch('restart-service.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ action: 'restart-kiwix' })
        });
        
        const result = await response.json();
        
        if (result.success) {
          // Show success and reload services
          button.style.background = '#10b981';
          setTimeout(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
            button.style.background = '';
            loadServices(); // Reload to show updated status
          }, 2000);
        } else {
          throw new Error(result.error || result.message);
        }
        
      } catch (error) {
        console.error('Error restarting Kiwix:', error);
        button.style.background = '#ef4444';
        setTimeout(() => {
          button.innerHTML = originalContent;
          button.disabled = false;
          button.style.background = '';
        }, 2000);
      }
    }
  </script>
</body>
</html>
