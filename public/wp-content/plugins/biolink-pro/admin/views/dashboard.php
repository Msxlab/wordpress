<?php
defined('ABSPATH') || exit;
if (!current_user_can('manage_biolink')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'biolink-pro'));
}
?>

<div id="blp-app" class="blp-app">

  <!-- ══ TOPBAR ══════════════════════════════════════════════════ -->
  <div class="blp-topbar">
    <div class="blp-logo">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
      </svg>
      <span>BioLink <strong>Pro</strong></span>
    </div>

    <nav class="blp-tabs" role="tablist" aria-label="BioLink Pro panels">
      <button class="blp-tab active" data-tab="settings" role="tab" aria-selected="true" aria-controls="tab-settings">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/></svg>
        Profile
      </button>
      <button class="blp-tab" data-tab="links" role="tab" aria-selected="false" aria-controls="tab-links">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Links
      </button>
      <button class="blp-tab" data-tab="profiles" role="tab" aria-selected="false" aria-controls="tab-profiles">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
        Profiles
      </button>
      <button class="blp-tab" data-tab="design" role="tab" aria-selected="false" aria-controls="tab-design">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
        Design
      </button>
      <button class="blp-tab" data-tab="analytics" role="tab" aria-selected="false" aria-controls="tab-analytics">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Analytics
      </button>
      <button class="blp-tab" data-tab="security" role="tab" aria-selected="false" aria-controls="tab-security">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 9.5-8 10-4.5-.5-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
        Security
      </button>
    </nav>

    <div class="blp-topbar-right">
      <!-- Profile Switcher -->
      <div class="blp-profile-switcher" id="profile-switcher">
        <button class="blp-btn blp-btn-ghost blp-profile-trigger" id="profile-trigger" title="Switch Profile">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/></svg>
          <span id="current-profile-name">Profile</span>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="blp-profile-dropdown" id="profile-dropdown" style="display:none">
          <div class="blp-profile-list" id="profile-list"></div>
          <div class="blp-profile-dropdown-footer">
            <button class="blp-btn blp-btn-secondary blp-btn-sm" id="btn-new-profile" style="width:100%">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              New Profile
            </button>
          </div>
        </div>
      </div>

      <div id="blp-public-url" style="display:none">
        <a id="blp-view-page" href="#" target="_blank" class="blp-btn blp-btn-ghost">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          View Page
        </a>
      </div>
      <div id="blp-toast" class="blp-toast" style="display:none"></div>
    </div>
  </div>

  <!-- ══ CONTENT ═════════════════════════════════════════════════ -->
  <div class="blp-content">

    <!-- ── TAB: PROFILE SETTINGS ──────────────────────────────── -->
    <div class="blp-panel active" id="tab-settings" role="tabpanel">
      <div class="blp-panel-header">
        <div>
          <h2>Profile Settings</h2>
          <p>Configure your public bio page identity</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <button class="blp-btn blp-btn-ghost blp-btn-sm" id="btn-export-profile" title="Export Profile">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export
          </button>
          <button class="blp-btn blp-btn-ghost blp-btn-sm" id="btn-import-profile" title="Import Profile">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import
          </button>
          <input type="file" id="import-file-input" accept=".json" style="display:none">
          <button class="blp-btn blp-btn-primary" id="btn-save-profile">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Profile
          </button>
        </div>
      </div>

      <div class="blp-settings-grid">
        <!-- Avatar Card -->
        <div class="blp-card blp-avatar-card">
          <h3 class="blp-card-title">Avatar</h3>
          <div class="blp-avatar-upload">
            <div class="blp-avatar-preview" id="avatar-preview">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/></svg>
            </div>
            <div class="blp-avatar-controls">
              <label class="blp-btn blp-btn-secondary" for="avatar-file">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload Photo
              </label>
              <input type="file" id="avatar-file" accept="image/*" style="display:none">
              <p class="blp-help">PNG, JPG up to 2MB. Square works best.</p>
            </div>
          </div>
          <div class="blp-field">
            <label>Or paste image URL</label>
            <input type="url" id="avatar-url" placeholder="https://..." class="blp-input">
          </div>
        </div>

        <!-- Info Card -->
        <div class="blp-card">
          <h3 class="blp-card-title">Public Info</h3>
          <div class="blp-field">
            <label>Username <span class="blp-required">*</span></label>
            <div class="blp-input-prefix">
              <span><?php echo esc_html(home_url('/b/')); ?></span>
              <input type="text" id="profile-username" placeholder="yourname" class="blp-input" autocomplete="off">
            </div>
          </div>
          <div class="blp-field">
            <label>Display Name</label>
            <input type="text" id="profile-display-name" placeholder="Your Name" class="blp-input">
          </div>
          <div class="blp-field">
            <label>Bio</label>
            <textarea id="profile-bio" placeholder="Tell your audience who you are..." class="blp-textarea" rows="3"></textarea>
          </div>
          <div class="blp-field" style="margin-top:16px">
            <label>Custom Domain <small style="opacity:0.5">(optional)</small></label>
            <input type="text" id="profile-custom-domain" placeholder="links.yourdomain.com" class="blp-input">
            <p class="blp-help">Point your domain's CNAME to <code><?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></code> and enter it here. Requires server-level DNS configuration.</p>
          </div>
        </div>
      </div>

      <!-- QR Code Card -->
      <div class="blp-card" id="qr-code-card" style="margin-top:20px;display:none">
        <h3 class="blp-card-title">QR Code</h3>
        <p class="blp-help" style="margin:-4px 0 16px">Scan to open your bio page. Right-click to save.</p>
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
          <div id="qr-code-canvas" style="background:#fff;padding:12px;border-radius:8px;display:inline-block"></div>
          <div>
            <button class="blp-btn blp-btn-secondary blp-btn-sm" id="btn-download-qr">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Download PNG
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── TAB: LINKS ─────────────────────────────────────────── -->
    <div class="blp-panel" id="tab-links" role="tabpanel">
      <div class="blp-panel-header">
        <div>
          <h2>Links</h2>
          <p>Drag to reorder — changes save automatically</p>
        </div>
        <label class="blp-btn blp-btn-secondary" for="csv-import-input" style="cursor:pointer" title="Import links from CSV (title,url columns)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Import CSV
        </label>
        <input type="file" id="csv-import-input" accept=".csv" style="display:none">
        <button class="blp-btn blp-btn-primary" id="btn-add-link">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Link
        </button>
      </div>

      <div class="blp-links-empty" id="links-empty" style="display:none">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        <p>No links yet. Add your first link!</p>
      </div>

      <ul id="links-sortable" class="blp-links-list"></ul>
    </div>

    <!-- ── TAB: PROFILES MANAGEMENT ───────────────────────────── -->
    <div class="blp-panel" id="tab-profiles" role="tabpanel">
      <div class="blp-panel-header">
        <div>
          <h2>Profiles Manager</h2>
          <p>Search, activate/deactivate and manage all profiles from one place</p>
        </div>
        <div class="blp-profiles-header-actions">
          <input type="search" id="profiles-search" class="blp-input blp-input-sm-wide" placeholder="Search username or display name">
          <select id="profiles-status-filter" class="blp-select blp-select-sm" aria-label="Filter by status">
            <option value="all">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <select id="profiles-sort" class="blp-select blp-select-sm" aria-label="Sort profiles">
            <option value="updated_desc">Updated (Newest)</option>
            <option value="updated_asc">Updated (Oldest)</option>
            <option value="username_asc">Username (A-Z)</option>
            <option value="username_desc">Username (Z-A)</option>
            <option value="links_desc">Links (High-Low)</option>
            <option value="links_asc">Links (Low-High)</option>
          </select>
          <button class="blp-btn blp-btn-secondary" id="profiles-refresh">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.13-3.36L23 10M1 14l5.36 4.36A9 9 0 0020.49 15"/></svg>
            Refresh
          </button>
        </div>
      </div>

      <div class="blp-card">
        <div class="blp-profiles-toolbar">
          <div class="blp-profiles-bulk-label">Bulk Actions</div>
          <button class="blp-btn blp-btn-secondary blp-btn-sm" id="profiles-bulk-activate">Set Active</button>
          <button class="blp-btn blp-btn-ghost blp-btn-sm" id="profiles-bulk-deactivate">Set Inactive</button>
          <button class="blp-btn blp-btn-ghost blp-btn-sm" id="profiles-bulk-duplicate">Duplicate</button>
          <button class="blp-btn blp-btn-danger blp-btn-sm" id="profiles-bulk-delete">Delete</button>
        </div>

        <div class="blp-table-wrap">
          <table class="blp-table" id="profiles-table">
            <thead>
              <tr>
                <th style="width:38px"><input type="checkbox" id="profiles-select-all" aria-label="Select all profiles"></th>
                <th>Profile</th>
                <th>Status</th>
                <th>Owner</th>
                <th>Links</th>
                <th>Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="profiles-table-body">
              <tr><td colspan="7" class="blp-table-empty">Loading profiles...</td></tr>
            </tbody>
          </table>
        </div>

        <div class="blp-profiles-pagination">
          <button class="blp-btn blp-btn-ghost blp-btn-sm" id="profiles-prev">Previous</button>
          <div class="blp-profiles-page-info" id="profiles-page-info">Page 1</div>
          <button class="blp-btn blp-btn-ghost blp-btn-sm" id="profiles-next">Next</button>
        </div>
      </div>
    </div>

    <!-- ── TAB: DESIGN ────────────────────────────────────────── -->
    <div class="blp-panel blp-design-panel" id="tab-design">
      <div class="blp-design-controls">
        <div class="blp-panel-header">
          <div>
            <h2>Design Editor</h2>
            <p>Customize your page appearance (autosave enabled)</p>
          </div>
          <div class="blp-design-header-actions">
            <div class="blp-save-status" id="design-save-status" data-state="idle">
              <span class="blp-save-dot"></span>
              <span id="design-save-status-text">Autosave on</span>
            </div>
            <button class="blp-btn blp-btn-secondary" id="btn-save-design">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
              Save Now
            </button>
          </div>
        </div>

        <!-- THEMES -->
        <div class="blp-design-section">
          <h4>Theme Preset</h4>
          <div class="blp-theme-grid" id="theme-grid">
            <?php
            $themes = [
              ['id'=>'midnight-glass', 'name'=>'Midnight Glass', 'colors'=>['#0a0a1a','#7c6df0','#ffffff']],
              ['id'=>'aurora',         'name'=>'Aurora',         'colors'=>['#0f0c29','#a78bfa','#06d6a0']],
              ['id'=>'neon-punk',      'name'=>'Neon Punk',      'colors'=>['#000000','#00ff88','#ff0066']],
              ['id'=>'pastel-dream',   'name'=>'Pastel Dream',   'colors'=>['#fef3f8','#f9a8d4','#a5b4fc']],
              ['id'=>'luxury-gold',    'name'=>'Luxury Gold',    'colors'=>['#0d0d0d','#c9a227','#ffffff']],
              ['id'=>'ocean-depth',    'name'=>'Ocean Depth',    'colors'=>['#001a35','#0ea5e9','#38bdf8']],
              ['id'=>'forest',         'name'=>'Forest',         'colors'=>['#0a1a0e','#22c55e','#86efac']],
              ['id'=>'minimal-white',  'name'=>'Minimal White',  'colors'=>['#ffffff','#111111','#6366f1']],
              ['id'=>'sunset-blaze',   'name'=>'Sunset Blaze',   'colors'=>['#1a0000','#f97316','#fff7ed']],
              ['id'=>'cyber-wave',     'name'=>'Cyber Wave',     'colors'=>['#0a0018','#e040fb','#f3e8ff']],
              ['id'=>'arctic-frost',   'name'=>'Arctic Frost',   'colors'=>['#e0f2fe','#0284c7','#f0f9ff']],
              ['id'=>'rose-garden',    'name'=>'Rose Garden',    'colors'=>['#1a0a10','#fb7185','#fff1f2']],
              ['id'=>'monochrome',     'name'=>'Monochrome',     'colors'=>['#121212','#a3a3a3','#e5e5e5']],
              ['id'=>'retrowave',      'name'=>'Retrowave',      'colors'=>['#0f0028','#ff6ec7','#2d004a']],
              ['id'=>'earth-tone',     'name'=>'Earth Tone',     'colors'=>['#1c1917','#d97706','#fef3c7']],
              ['id'=>'sakura',         'name'=>'Sakura',         'colors'=>['#fdf2f8','#ec4899','#fce7f3']],
              ['id'=>'nordic-slate',   'name'=>'Nordic Slate',   'colors'=>['#0f172a','#38bdf8','#e2e8f0']],
              ['id'=>'terracotta-pro', 'name'=>'Terracotta Pro', 'colors'=>['#2b2118','#ea580c','#ffedd5']],
              ['id'=>'emerald-night',  'name'=>'Emerald Night',  'colors'=>['#022c22','#10b981','#d1fae5']],
            ];
            foreach ($themes as $t): ?>
            <div class="blp-theme-chip" data-theme="<?= esc_attr($t['id']) ?>">
              <div class="blp-theme-preview">
                <?php foreach($t['colors'] as $c): ?>
                <span style="background:<?= esc_attr($c) ?>"></span>
                <?php endforeach; ?>
              </div>
              <span><?= esc_html($t['name']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- BACKGROUND -->
        <div class="blp-design-section">
          <h4>Background</h4>
          <div class="blp-radio-group">
            <label><input type="radio" name="bg_type" value="solid" checked> Solid Color</label>
            <label><input type="radio" name="bg_type" value="gradient"> Gradient</label>
            <label><input type="radio" name="bg_type" value="animated"> Animated</label>
          </div>
          <div id="bg-solid-opts" class="blp-color-row">
            <div class="blp-field-inline">
              <label>Background</label>
              <input type="color" id="bg-color" value="#0a0a1a" class="blp-color-input">
            </div>
          </div>
          <div id="bg-gradient-opts" style="display:none" class="blp-color-row">
            <div class="blp-field-inline">
              <label>From</label>
              <input type="color" id="bg-gradient-start" value="#0a0a1a" class="blp-color-input">
            </div>
            <div class="blp-field-inline">
              <label>To</label>
              <input type="color" id="bg-gradient-end" value="#1a0a3a" class="blp-color-input">
            </div>
            <div class="blp-field-inline">
              <label>Angle</label>
              <input type="number" id="bg-gradient-angle" value="135" min="0" max="360" class="blp-input blp-input-sm">
            </div>
          </div>
        </div>

        <!-- COLORS -->
        <div class="blp-design-section">
          <h4>Colors</h4>
          <div class="blp-color-row">
            <div class="blp-field-inline">
              <label>Accent</label>
              <input type="color" id="accent-color" value="#7c6df0" class="blp-color-input">
            </div>
            <div class="blp-field-inline">
              <label>Text</label>
              <input type="color" id="text-color" value="#ffffff" class="blp-color-input">
            </div>
            <div class="blp-field-inline">
              <label>Card</label>
              <input type="color" id="card-color" value="#1a1a2e" class="blp-color-input">
            </div>
          </div>
          <div class="blp-field">
            <label>Card Opacity <span id="opacity-val">90</span>%</label>
            <input type="range" id="card-opacity" min="20" max="100" value="90" class="blp-slider">
          </div>
        </div>

        <!-- FONTS -->
        <div class="blp-design-section">
          <h4>Typography</h4>
          <div class="blp-field">
            <label>Font Family</label>
            <select id="font-family" class="blp-select">
              <optgroup label="Google Fonts">
              <option value="DM Sans">DM Sans</option>
              <option value="Space Grotesk">Space Grotesk</option>
              <option value="Playfair Display">Playfair Display</option>
              <option value="Inter">Inter</option>
              <option value="Poppins">Poppins</option>
              <option value="Raleway">Raleway</option>
              <option value="Josefin Sans">Josefin Sans</option>
              <option value="Bebas Neue">Bebas Neue</option>
              <option value="Outfit">Outfit</option>
              <option value="Syne">Syne</option>
              <option value="Plus Jakarta Sans">Plus Jakarta Sans</option>
              <option value="Manrope">Manrope</option>
              <option value="Montserrat">Montserrat</option>
              <option value="Lora">Lora</option>
              <option value="Nunito Sans">Nunito Sans</option>
              <option value="Merriweather">Merriweather</option>
              <option value="Oswald">Oswald</option>
              <option value="Archivo">Archivo</option>
              <option value="Source Sans 3">Source Sans 3</option>
              <option value="Rubik">Rubik</option>
              </optgroup>
              <optgroup label="Uploaded Fonts" id="custom-fonts-group">
              </optgroup>
            </select>
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>Upload Custom Font</label>
            <div style="display:flex;gap:8px;align-items:center">
              <label class="blp-btn blp-btn-secondary blp-btn-sm" for="font-file-input" style="cursor:pointer">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload .woff2 / .ttf
              </label>
              <input type="file" id="font-file-input" accept=".woff2,.woff,.ttf,.otf" style="display:none">
              <span id="font-upload-status" class="blp-help" style="margin:0"></span>
            </div>
          </div>
        </div>

        <!-- LAYOUT PRESETS -->
        <div class="blp-design-section">
          <h4>Layout Presets</h4>
          <div class="blp-layout-grid" id="layout-grid">
            <button type="button" class="blp-layout-chip active" data-layout="center_classic">
              <span>Center Classic</span>
              <small>Balanced center profile</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="profile_left_info_right">
              <span>Avatar Left</span>
              <small>Profile left, info right</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="profile_right_info_left">
              <span>Avatar Right</span>
              <small>Profile right, info left</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="business_card_horizontal">
              <span>Business Card</span>
              <small>Horizontal professional card</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="magazine_split">
              <span>Magazine Split</span>
              <small>Editorial split layout</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="minimal_stack">
              <span>Minimal Stack</span>
              <small>Clean and subtle stack</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="executive_clean">
              <span>Executive Clean</span>
              <small>Corporate profile style</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="studio_bento">
              <span>Studio Bento</span>
              <small>Creative bento spacing</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="bold_banner_top">
              <span>Bold Banner</span>
              <small>High-impact top banner</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="avatar_floating_sidebar">
              <span>Floating Sidebar</span>
              <small>Avatar sidebar profile</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="compact_contact_card">
              <span>Contact Card</span>
              <small>Compact mobile-first card</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="spotlight_onepage">
              <span>Spotlight Onepage</span>
              <small>Hero profile with spotlight</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="hero_split_pro">
              <span>Hero Split Pro</span>
              <small>Sticky intro + 2-col links</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="stacked_cards_modern">
              <span>Stacked Cards</span>
              <small>Modern vertical rhythm</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="cover_profile_panel">
              <span>Cover Panel</span>
              <small>Banner-style profile block</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="creator_spotlight">
              <span>Creator Spotlight</span>
              <small>Bold hero + featured CTA</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="minimal_directory">
              <span>Minimal Directory</span>
              <small>Compact contact list grid</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="agency_brief">
              <span>Agency Brief</span>
              <small>Professional multi-column brief</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="timeline_story">
              <span>Timeline Story</span>
              <small>Narrative timeline style cards</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="split_hero_cards">
              <span>Split Hero Cards</span>
              <small>Sticky intro + dense cards</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="mosaic_showcase">
              <span>Mosaic Showcase</span>
              <small>Asymmetric visual grid layout</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="executive_sidebar_pro">
              <span>Executive Sidebar Pro</span>
              <small>Sticky executive panel + content feed</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="press_kit_split">
              <span>Press Kit Split</span>
              <small>Media-ready split showcase</small>
            </button>
            <button type="button" class="blp-layout-chip" data-layout="minimal_premium_stack">
              <span>Minimal Premium Stack</span>
              <small>Clean luxury vertical rhythm</small>
            </button>
          </div>
        </div>

        <!-- AUTO STYLE PRESETS -->
        <div class="blp-design-section">
          <h4>Auto Style</h4>
          <div class="blp-style-preset-grid" id="style-preset-grid">
            <button type="button" class="blp-style-preset" data-style-preset="corporate_flow">Corporate Flow</button>
            <button type="button" class="blp-style-preset" data-style-preset="creative_pulse">Creative Pulse</button>
            <button type="button" class="blp-style-preset" data-style-preset="editorial_light">Editorial Light</button>
            <button type="button" class="blp-style-preset" data-style-preset="midnight_executive">Midnight Executive</button>
            <button type="button" class="blp-style-preset" data-style-preset="bento_brand">Bento Brand</button>
            <button type="button" class="blp-style-preset" data-style-preset="minimal_pro">Minimal Pro</button>
            <button type="button" class="blp-style-preset" data-style-preset="hero_authority">Hero Authority</button>
            <button type="button" class="blp-style-preset" data-style-preset="directory_plus">Directory Plus</button>
            <button type="button" class="blp-style-preset" data-style-preset="agency_showcase">Agency Showcase</button>
            <button type="button" class="blp-style-preset" data-style-preset="timeline_narrative">Timeline Narrative</button>
            <button type="button" class="blp-style-preset" data-style-preset="split_signal">Split Signal</button>
            <button type="button" class="blp-style-preset" data-style-preset="mosaic_glow">Mosaic Glow</button>
            <button type="button" class="blp-style-preset" data-style-preset="executive_slate">Executive Slate</button>
            <button type="button" class="blp-style-preset" data-style-preset="press_kit_luxe">Press Kit Luxe</button>
            <button type="button" class="blp-style-preset" data-style-preset="minimal_mono_pro">Minimal Mono Pro</button>
          </div>
          <p class="blp-help" style="margin-top:10px">One click applies layout + typography + effect combinations.</p>
        </div>

        <!-- BUTTON STYLE -->
        <div class="blp-design-section">
          <h4>Button Style</h4>
          <div class="blp-btn-style-grid" id="btn-style-grid">
            <div class="blp-btn-style active" data-style="rounded">
              <div class="blp-btn-preview" style="border-radius:10px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2)">Rounded</div>
            </div>
            <div class="blp-btn-style" data-style="pill">
              <div class="blp-btn-preview" style="border-radius:9999px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2)">Pill</div>
            </div>
            <div class="blp-btn-style" data-style="square">
              <div class="blp-btn-preview" style="border-radius:4px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2)">Square</div>
            </div>
            <div class="blp-btn-style" data-style="outline">
              <div class="blp-btn-preview" style="border-radius:10px;background:transparent;border:2px solid rgba(255,255,255,0.5)">Outline</div>
            </div>
            <div class="blp-btn-style" data-style="glass">
              <div class="blp-btn-preview" style="border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);backdrop-filter:blur(10px)">Glass</div>
            </div>
            <div class="blp-btn-style" data-style="solid">
              <div class="blp-btn-preview" style="border-radius:10px;background:#7c6df0;border:none;color:#fff">Solid</div>
            </div>
          </div>

          <div class="blp-field" style="margin-top:16px">
            <label>Hover Effect</label>
            <select id="btn-effect" class="blp-select">
              <option value="none">None</option>
              <option value="lift">Lift</option>
              <option value="glow">Glow</option>
              <option value="slide">Slide Fill</option>
              <option value="bounce">Bounce</option>
              <option value="pulse">Pulse</option>
              <option value="tilt">3D Tilt</option>
            </select>
          </div>
        </div>

        <!-- EFFECTS -->
        <div class="blp-design-section">
          <h4>Effects</h4>
          <div class="blp-field">
            <label>Background Effect</label>
            <select id="bg-effect" class="blp-select">
              <option value="none">None</option>
              <option value="mesh">Mesh Glow</option>
              <option value="spotlight">Spotlight</option>
              <option value="grain">Film Grain</option>
              <option value="orbs">Ambient Orbs</option>
              <option value="aurora">Aurora Ribbon</option>
              <option value="waves">Gradient Waves</option>
              <option value="constellation">Constellation</option>
            </select>
          </div>
          <div class="blp-field">
            <label>Effect Intensity <span id="effect-intensity-val">45</span>%</label>
            <input type="range" id="effect-intensity" min="0" max="100" value="45" class="blp-slider">
          </div>
          <label class="blp-toggle-label">
            <span>Show Social Icons</span>
            <input type="checkbox" id="show-social-icons" class="blp-toggle" checked>
            <div class="blp-toggle-track"></div>
          </label>
          <label class="blp-toggle-label">
            <span>Particle Background</span>
            <input type="checkbox" id="enable-particles" class="blp-toggle">
            <div class="blp-toggle-track"></div>
          </label>
          <label class="blp-toggle-label">
            <span>Animated Gradient</span>
            <input type="checkbox" id="enable-gradient-anim" class="blp-toggle">
            <div class="blp-toggle-track"></div>
          </label>
        </div>

        <!-- BACKGROUND IMAGE -->
        <div class="blp-design-section">
          <h4>Background Image</h4>
          <div class="blp-field">
            <label>Image URL</label>
            <input type="url" id="bg-image-url" class="blp-input" placeholder="https://example.com/bg.jpg">
          </div>
          <div class="blp-field">
            <label>Overlay Opacity <span id="bg-image-overlay-val">50</span>%</label>
            <input type="range" id="bg-image-overlay" min="0" max="100" value="50" class="blp-slider">
          </div>
          <div class="blp-field">
            <label>Background Blur <span id="bg-image-blur-val">0</span>px</label>
            <input type="range" id="bg-image-blur" min="0" max="20" value="0" class="blp-slider">
          </div>
        </div>

        <!-- VIDEO BACKGROUND -->
        <div class="blp-design-section">
          <h4>Video Background</h4>
          <div class="blp-field">
            <label>YouTube / Vimeo URL</label>
            <input type="url" id="bg-video-url" class="blp-input" placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
          </div>
          <p class="blp-help">Plays muted, looped video behind your page. Overrides background image when set.</p>
        </div>

        <!-- MUSIC PLAYER -->
        <div class="blp-design-section">
          <h4>Music Player</h4>
          <div class="blp-field">
            <label>Spotify / SoundCloud URL</label>
            <input type="url" id="music-url" class="blp-input" placeholder="https://open.spotify.com/track/... or https://soundcloud.com/...">
          </div>
          <p class="blp-help">Embeds a mini player widget at the bottom of your page.</p>
        </div>

        <!-- DARK/LIGHT MODE TOGGLE -->
        <div class="blp-design-section">
          <h4>Dark/Light Mode</h4>
          <div class="blp-field">
            <label><input type="checkbox" id="enable-darklight-toggle"> Enable visitor dark/light toggle</label>
          </div>
          <div id="darklight-colors" style="display:none;margin-top:12px">
            <div class="blp-field-row">
              <div class="blp-field blp-field-xs">
                <label>Light BG</label>
                <input type="color" id="light-bg-color" value="#f5f5f5" class="blp-color-input">
              </div>
              <div class="blp-field blp-field-xs">
                <label>Light Text</label>
                <input type="color" id="light-text-color" value="#1a1a1a" class="blp-color-input">
              </div>
              <div class="blp-field blp-field-xs">
                <label>Light Card</label>
                <input type="color" id="light-card-color" value="#ffffff" class="blp-color-input">
              </div>
            </div>
          </div>
          <p class="blp-help">Let visitors switch between dark and light modes. Uses <code>prefers-color-scheme</code> for auto-detection.</p>
        </div>

        <!-- GLASSMORPHISM -->
        <div class="blp-design-section">
          <h4>Card Glass Effect</h4>
          <div class="blp-field">
            <label>Card Blur <span id="card-blur-val">0</span>px</label>
            <input type="range" id="card-blur" min="0" max="30" value="0" class="blp-slider">
          </div>
          <p class="blp-help">Add a frosted-glass blur behind link cards.</p>
        </div>

        <!-- GRADIENT MID COLOR -->
        <div class="blp-design-section">
          <h4>Gradient Mid Color</h4>
          <div class="blp-field">
            <label>Middle Stop <small style="opacity:0.5">(optional, for 3-color gradients)</small></label>
            <input type="color" id="bg-gradient-mid" value="#4a1a8a" class="blp-color-input">
            <button type="button" class="blp-btn blp-btn-ghost blp-btn-sm" id="clear-gradient-mid" style="margin-top:6px">Clear mid color</button>
          </div>
        </div>

        <!-- BANNER / COVER IMAGE -->
        <div class="blp-design-section">
          <h4>Banner / Cover</h4>
          <div class="blp-field">
            <label>Banner Image URL</label>
            <input type="url" id="banner-url" class="blp-input" placeholder="https://example.com/banner.jpg">
          </div>
          <div class="blp-field">
            <label>Banner Height <span id="banner-height-val">200</span>px</label>
            <input type="range" id="banner-height" min="100" max="400" value="200" class="blp-slider">
          </div>
        </div>

        <!-- COUNTDOWN TIMER -->
        <div class="blp-design-section">
          <h4>Countdown Timer</h4>
          <div class="blp-field">
            <label>Target Date</label>
            <input type="datetime-local" id="countdown-date" class="blp-input">
          </div>
          <div class="blp-field">
            <label>Label <small style="opacity:0.5">(e.g. "Launch in")</small></label>
            <input type="text" id="countdown-label" class="blp-input" placeholder="Launching in..." maxlength="60">
          </div>
          <p class="blp-help">Displays a live countdown above your links.</p>
        </div>

        <!-- STATUS INDICATOR -->
        <div class="blp-design-section">
          <h4>Status Indicator</h4>
          <div class="blp-field">
            <label>Status Emoji</label>
            <div class="blp-emoji-picker" id="status-emoji-picker">
              <?php
              $status_emojis = ['🟢','🔴','🟡','🟠','🔵','⚪','💼','🎯','✈️','🎉','🔥','💡','🚀','☕','🎵','❤️','⭐','🌙','☀️','🏖️'];
              foreach ($status_emojis as $em): ?>
              <button type="button" class="blp-emoji-opt" data-emoji="<?php echo $em; ?>"><?php echo $em; ?></button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" id="status-emoji" value="">
            <div class="blp-emoji-selected" id="status-emoji-display" style="margin-top:8px">
              <span id="status-emoji-preview" style="font-size:1.5em"></span>
              <button type="button" class="blp-btn blp-btn-ghost blp-btn-sm" id="clear-status-emoji" style="display:none">✕ Clear</button>
            </div>
          </div>
          <div class="blp-field" style="margin-top:12px">
            <label>Status Text</label>
            <input type="text" id="status-text" class="blp-input" placeholder="Available for hire" maxlength="80">
          </div>
          <p class="blp-help">Shown below your bio on the public page.</p>
        </div>

        <!-- SEO SETTINGS -->
        <div class="blp-design-section">
          <h4>SEO & Social Sharing</h4>
          <div class="blp-field">
            <label>Custom OG Image URL</label>
            <input type="url" id="og-image-url" class="blp-input" placeholder="https://example.com/og-image.jpg">
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>Meta Description</label>
            <textarea id="meta-description" class="blp-input" placeholder="Custom description for search engines and social sharing..." maxlength="300" rows="2" style="resize:vertical"></textarea>
          </div>
          <p class="blp-help">Override default OG image and meta description for better social sharing previews.</p>
        </div>

        <!-- AI COLOR SUGGESTIONS -->
        <div class="blp-design-section">
          <h4>AI Color Suggestions</h4>
          <p class="blp-help" style="margin-bottom:10px">Click a palette to apply harmonious colors instantly.</p>
          <div id="ai-color-palettes" class="blp-ai-palettes"></div>
          <button type="button" class="blp-btn blp-btn-ghost blp-btn-sm" id="btn-generate-palettes" style="margin-top:8px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            Generate New Palettes
          </button>
        </div>

        <!-- AVATAR SHAPE -->
        <div class="blp-design-section">
          <h4>Avatar Shape</h4>
          <div class="blp-radio-group">
            <label><input type="radio" name="avatar_shape" value="circle" checked> Circle</label>
            <label><input type="radio" name="avatar_shape" value="square"> Square</label>
            <label><input type="radio" name="avatar_shape" value="hexagon"> Hexagon</label>
          </div>
        </div>

        <!-- SOCIAL LINKS -->
        <div class="blp-design-section">
          <h4>Social Links</h4>
          <p class="blp-help" style="margin:-8px 0 14px">Add your social media profiles — they appear as icons below your avatar.</p>
          <div class="blp-social-links-editor" id="social-links-editor">
            <?php
            $social_platforms = [
              'instagram'  => ['Instagram',  'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z'],
              'tiktok'     => ['TikTok',     'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z'],
              'youtube'    => ['YouTube',    'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'],
              'twitter'    => ['X / Twitter','M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'],
              'linkedin'   => ['LinkedIn',   'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
              'whatsapp'   => ['WhatsApp',   'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z'],
            ];
            $social_platforms_more = [
              'spotify'    => ['Spotify',    'M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z'],
              'discord'    => ['Discord',    'M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189z'],
              'twitch'     => ['Twitch',     'M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z'],
              'github'     => ['GitHub',     'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12'],
              'pinterest'  => ['Pinterest',  'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641 0 12.017 0z'],
              'snapchat'   => ['Snapchat',   'M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12 1.033-.301.165-.088.344-.104.464-.104.182 0 .359.029.509.09.45.149.734.479.734.838.015.449-.39.839-1.213 1.168-.089.029-.209.075-.344.119-.45.135-1.139.36-1.333.81-.09.21-.061.524.12.869l.015.015c.06.136 1.526 3.475 4.791 4.014.255.044.435.27.42.509 0 .075-.015.149-.045.225-.24.569-1.273.988-3.146 1.271-.059.091-.12.375-.164.57-.029.179-.074.36-.134.553-.076.271-.27.405-.555.405h-.008c-.18 0-.39-.044-.63-.104a5.478 5.478 0 00-1.47-.207c-.36 0-.72.044-1.048.164-.81.314-1.455.899-2.22 1.605-.96.885-2.07 1.891-3.766 1.891-.045 0-.09 0-.135-.004h-.105c-1.695 0-2.805-1.005-3.766-1.891-.764-.706-1.41-1.291-2.22-1.605a3.68 3.68 0 00-1.048-.164c-.57 0-1.095.12-1.47.207-.24.06-.45.104-.63.104h-.006c-.285 0-.48-.135-.555-.405a4.1 4.1 0 01-.134-.553c-.045-.195-.105-.479-.165-.57-1.872-.283-2.905-.702-3.146-1.271a.504.504 0 01-.044-.225c-.015-.24.165-.465.42-.509 3.264-.54 4.73-3.879 4.791-4.02l.016-.029c.18-.345.21-.659.119-.869-.195-.434-.884-.659-1.332-.809a3.72 3.72 0 01-.346-.12c-.6-.239-1.166-.585-1.166-1.05 0-.3.18-.599.476-.748a1.2 1.2 0 01.51-.105c.135 0 .33.03.494.104.374.18.72.3 1.033.3.21 0 .346-.045.406-.089a27.14 27.14 0 01-.033-.57c-.104-1.628-.239-3.654.3-4.848C7.847 1.069 11.216.793 12.206.793'],
              'telegram'   => ['Telegram',   'M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z'],
              'facebook'   => ['Facebook',   'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
              'bereal'     => ['BeReal',     'M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 3a7 7 0 110 14 7 7 0 010-14zm0 2a5 5 0 100 10 5 5 0 000-10z'],
            ];
            foreach ($social_platforms as $key => $plat): ?>
            <div class="blp-social-field" data-platform="<?php echo esc_attr($key); ?>">
              <div class="blp-social-icon-wrap">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="<?php echo esc_attr($plat[1]); ?>"/></svg>
              </div>
              <input type="url" class="blp-input blp-social-input" data-platform="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($plat[0]); ?> URL">
            </div>
            <?php endforeach; ?>
            <div class="blp-social-more-wrap" id="social-more-wrap" style="display:none">
              <?php foreach ($social_platforms_more as $key => $plat): ?>
              <div class="blp-social-field" data-platform="<?php echo esc_attr($key); ?>">
                <div class="blp-social-icon-wrap">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="<?php echo esc_attr($plat[1]); ?>"/></svg>
                </div>
                <input type="url" class="blp-input blp-social-input" data-platform="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($plat[0]); ?> URL">
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="blp-btn blp-btn-ghost blp-btn-sm" id="btn-social-more" style="margin-top:8px;width:100%">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              More platforms
            </button>
          </div>
        </div>

        <!-- CUSTOM CSS -->
        <div class="blp-design-section">
          <h4>Custom CSS <span class="blp-badge-pro">PRO</span></h4>
          <textarea id="custom-css" class="blp-textarea blp-code" rows="5" placeholder="/* Add custom styles here */
.blp-page { }
.blp-link-card { }"></textarea>
        </div>
      </div>

      <!-- LIVE PREVIEW -->
      <div class="blp-design-preview">
        <div class="blp-preview-header">
          <div class="blp-preview-dots">
            <span></span><span></span><span></span>
          </div>
          <div class="blp-device-switcher">
            <button class="blp-device-btn active" data-device="mobile" title="Mobile (375px)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            </button>
            <button class="blp-device-btn" data-device="tablet" title="Tablet (768px)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            </button>
            <button class="blp-device-btn" data-device="desktop" title="Desktop (100%)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </button>
          </div>
          <div class="blp-preview-url">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span id="preview-url-text">yourpage.com/b/username</span>
          </div>
        </div>
        <div class="blp-preview-device" id="preview-device-wrap">
          <iframe id="design-preview-frame" src="about:blank" scrolling="yes"></iframe>
        </div>
      </div>
    </div>

    <!-- ── TAB: ANALYTICS ─────────────────────────────────────── -->
    <div class="blp-panel" id="tab-analytics" role="tabpanel">
      <div class="blp-panel-header">
        <div>
          <h2>Analytics</h2>
          <p>Track your page performance</p>
        </div>
        <select id="analytics-range" class="blp-select blp-select-sm">
          <option value="7">Last 7 days</option>
          <option value="30" selected>Last 30 days</option>
          <option value="90">Last 90 days</option>
        </select>
      </div>

      <!-- Stat Cards -->
      <div class="blp-stat-grid">
        <div class="blp-stat-card">
          <div class="blp-stat-icon" style="background:rgba(124,109,240,0.15);color:#7c6df0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <div>
            <div class="blp-stat-value" id="stat-views">–</div>
            <div class="blp-stat-label">Total Views</div>
          </div>
        </div>
        <div class="blp-stat-card">
          <div class="blp-stat-icon" style="background:rgba(6,214,160,0.15);color:#06d6a0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          </div>
          <div>
            <div class="blp-stat-value" id="stat-clicks">–</div>
            <div class="blp-stat-label">Total Clicks</div>
          </div>
        </div>
        <div class="blp-stat-card">
          <div class="blp-stat-icon" style="background:rgba(251,191,36,0.15);color:#fbbf24">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
          <div>
            <div class="blp-stat-value" id="stat-today">–</div>
            <div class="blp-stat-label">Today's Clicks</div>
          </div>
        </div>
        <div class="blp-stat-card">
          <div class="blp-stat-icon" style="background:rgba(248,113,113,0.15);color:#f87171">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          </div>
          <div>
            <div class="blp-stat-value" id="stat-ctr">–</div>
            <div class="blp-stat-label">Click Rate</div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="blp-charts-grid">
        <div class="blp-card blp-chart-card">
          <h3 class="blp-card-title">Clicks Over Time</h3>
          <div class="blp-chart-wrap">
            <canvas id="chart-daily"></canvas>
          </div>
        </div>
        <div class="blp-card blp-chart-card">
          <h3 class="blp-card-title">Device Breakdown</h3>
          <div class="blp-chart-wrap blp-chart-doughnut">
            <canvas id="chart-devices"></canvas>
          </div>
        </div>
      </div>

      <!-- Top Links Table -->
      <div class="blp-card">
        <h3 class="blp-card-title">Top Links by Clicks</h3>
        <div class="blp-table-wrap">
          <table class="blp-table" id="top-links-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Link</th>
                <th>URL</th>
                <th>Clicks</th>
                <th>Share</th>
              </tr>
            </thead>
            <tbody id="top-links-body">
              <tr><td colspan="5" class="blp-table-empty">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Referrers -->
      <div class="blp-card" style="margin-top:24px">
        <h3 class="blp-card-title">Top Referrers</h3>
        <div class="blp-table-wrap">
          <table class="blp-table" id="referrers-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Source</th>
                <th>Visits</th>
                <th>Share</th>
              </tr>
            </thead>
            <tbody id="referrers-body">
              <tr><td colspan="4" class="blp-table-empty">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Heatmap -->
      <div class="blp-card" style="margin-top:24px">
        <h3 class="blp-card-title">Click Heatmap (Hour × Day)</h3>
        <div id="heatmap-container" style="overflow-x:auto">
          <table class="blp-table blp-heatmap-table" id="heatmap-table">
            <thead>
              <tr>
                <th></th>
                <th>12a</th><th>1a</th><th>2a</th><th>3a</th><th>4a</th><th>5a</th>
                <th>6a</th><th>7a</th><th>8a</th><th>9a</th><th>10a</th><th>11a</th>
                <th>12p</th><th>1p</th><th>2p</th><th>3p</th><th>4p</th><th>5p</th>
                <th>6p</th><th>7p</th><th>8p</th><th>9p</th><th>10p</th><th>11p</th>
              </tr>
            </thead>
            <tbody id="heatmap-body"></tbody>
          </table>
        </div>
      </div>

      <!-- Period Comparison -->
      <div class="blp-card" style="margin-top:24px">
        <h3 class="blp-card-title">Period Comparison</h3>
        <div class="blp-stat-grid" style="margin-top:12px">
          <div class="blp-stat-card">
            <div><div class="blp-stat-value" id="comp-views-current">–</div><div class="blp-stat-label">Views (current)</div></div>
          </div>
          <div class="blp-stat-card">
            <div><div class="blp-stat-value" id="comp-views-prev">–</div><div class="blp-stat-label">Views (previous)</div></div>
          </div>
          <div class="blp-stat-card">
            <div><div class="blp-stat-value" id="comp-clicks-current">–</div><div class="blp-stat-label">Clicks (current)</div></div>
          </div>
          <div class="blp-stat-card">
            <div><div class="blp-stat-value" id="comp-clicks-prev">–</div><div class="blp-stat-label">Clicks (previous)</div></div>
          </div>
        </div>
        <p class="blp-help" id="comp-change-text" style="margin-top:8px"></p>
      </div>

      <!-- Export -->
      <div style="margin-top:20px; text-align:right">
        <button class="blp-btn blp-btn-secondary" id="btn-export-csv">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export CSV
        </button>
      </div>
    </div>

    <!-- ── TAB: SECURITY ─────────────────────────────────────── -->
    <div class="blp-panel" id="tab-security" role="tabpanel">
      <div class="blp-panel-header">
        <div>
          <h2>Security & License</h2>
          <p>Tamper detection, containment mode and license infrastructure</p>
        </div>
        <button class="blp-btn blp-btn-secondary" id="btn-security-refresh">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.13-3.36L23 10M1 14l5.36 4.36A9 9 0 0020.49 15"/></svg>
          Refresh Status
        </button>
      </div>

      <div class="blp-settings-grid">
        <div class="blp-card">
          <h3 class="blp-card-title">Containment Mode</h3>
          <div class="blp-field">
            <label>Current Mode</label>
            <div id="security-mode-badge" class="blp-security-pill" data-mode="normal">normal</div>
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>Set Mode</label>
            <select id="security-mode-select" class="blp-select">
              <option value="normal">normal</option>
              <option value="read_only">read_only</option>
              <option value="lockdown">lockdown</option>
            </select>
            <p class="blp-help">read_only blocks write actions; lockdown blocks plugin actions except security recovery.</p>
          </div>
          <button class="blp-btn blp-btn-primary" id="btn-security-set-mode" style="margin-top:8px">Apply Mode</button>
        </div>

        <div class="blp-card">
          <h3 class="blp-card-title">Integrity Scanner</h3>
          <div class="blp-field">
            <label>Status</label>
            <div id="security-integrity-status" class="blp-security-pill" data-status="pending">Unknown</div>
            <p class="blp-help" id="security-integrity-meta">No scan data yet.</p>
          </div>
          <div id="security-integrity-mismatches" class="blp-help" style="max-height:140px;overflow:auto;margin-top:6px"></div>
          <button class="blp-btn blp-btn-secondary" id="btn-security-integrity-scan" style="margin-top:10px">Run Integrity Scan</button>
        </div>

        <div class="blp-card">
          <h3 class="blp-card-title">Alerting</h3>
          <label class="blp-toggle-label">
            <span>Enable alerts</span>
            <input type="checkbox" id="security-alerts-enabled" class="blp-toggle" checked>
            <div class="blp-toggle-track"></div>
          </label>
          <label class="blp-toggle-label">
            <span>Auto read-only on critical tamper</span>
            <input type="checkbox" id="security-auto-readonly" class="blp-toggle" checked>
            <div class="blp-toggle-track"></div>
          </label>
          <div class="blp-field" style="margin-top:10px">
            <label>Alert Email</label>
            <input type="email" id="security-alert-email" class="blp-input" placeholder="admin@example.com">
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>Webhook URL <small style="opacity:0.5">(optional)</small></label>
            <input type="url" id="security-webhook-url" class="blp-input" placeholder="https://...">
          </div>
          <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
            <button class="blp-btn blp-btn-secondary blp-btn-sm" id="btn-security-save-settings">Save Security Settings</button>
            <button class="blp-btn blp-btn-ghost blp-btn-sm" id="btn-security-test-alert">Send Test Alert</button>
          </div>
        </div>

        <div class="blp-card">
          <h3 class="blp-card-title">Remote Policy (Server Later)</h3>
          <div class="blp-field">
            <label>Policy Endpoint URL</label>
            <input type="url" id="security-policy-url" class="blp-input" placeholder="https://api.example.com/v1/policy">
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>Policy Public Key <small style="opacity:0.5">(PEM or base64)</small></label>
            <textarea id="security-policy-public-key" class="blp-textarea" rows="4" placeholder="-----BEGIN PUBLIC KEY-----"></textarea>
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>Lockdown Message</label>
            <input type="text" id="security-lockdown-message" class="blp-input" maxlength="220" placeholder="Temporarily unavailable.">
          </div>
          <label class="blp-toggle-label" style="margin-top:10px">
            <span>Allow unsigned policy (development only)</span>
            <input type="checkbox" id="security-allow-unsigned-policy" class="blp-toggle">
            <div class="blp-toggle-track"></div>
          </label>
          <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
            <button class="blp-btn blp-btn-secondary blp-btn-sm" id="btn-security-policy-pull">Pull Remote Policy</button>
          </div>
        </div>

        <div class="blp-card">
          <h3 class="blp-card-title">License Infrastructure</h3>
          <div class="blp-field">
            <label>License Status</label>
            <div id="license-status-badge" class="blp-security-pill" data-mode="normal">inactive</div>
            <p class="blp-help" id="license-message">No license key configured.</p>
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>License Key</label>
            <input type="text" id="license-key-input" class="blp-input" placeholder="XXXX-XXXX-XXXX-XXXX">
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
            <button class="blp-btn blp-btn-secondary blp-btn-sm" id="btn-license-save-key">Save Key</button>
            <button class="blp-btn blp-btn-ghost blp-btn-sm" id="btn-license-activate">Activate</button>
            <button class="blp-btn blp-btn-ghost blp-btn-sm" id="btn-license-validate">Validate</button>
            <button class="blp-btn blp-btn-danger blp-btn-sm" id="btn-license-deactivate">Deactivate</button>
          </div>
          <div class="blp-help" style="margin-top:10px">
            <div>Domain: <strong id="license-domain">-</strong></div>
            <div>Last Check: <strong id="license-last-check">-</strong></div>
            <div>Expires: <strong id="license-expires">-</strong></div>
          </div>
        </div>

        <div class="blp-card">
          <h3 class="blp-card-title">License API Settings (Plugin-side)</h3>
          <div class="blp-field">
            <label>API Base URL</label>
            <input type="url" id="license-api-base-url" class="blp-input" placeholder="https://license.yourdomain.com/v1/">
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>Product ID</label>
            <input type="text" id="license-product-id" class="blp-input" placeholder="biolink-pro">
          </div>
          <div class="blp-field" style="margin-top:10px">
            <label>Request Timeout (seconds)</label>
            <input type="number" id="license-timeout" class="blp-input" min="3" max="30" value="12">
          </div>
          <label class="blp-toggle-label" style="margin-top:10px">
            <span>Verify SSL</span>
            <input type="checkbox" id="license-verify-ssl" class="blp-toggle" checked>
            <div class="blp-toggle-track"></div>
          </label>
          <button class="blp-btn blp-btn-secondary" id="btn-license-save-settings" style="margin-top:10px">Save License Settings</button>
        </div>
      </div>

      <div class="blp-card" style="margin-top:24px">
        <h3 class="blp-card-title">Recent Security Events</h3>
        <div id="security-events-list" class="blp-security-events"></div>
      </div>
    </div>

  </div><!-- /.blp-content -->
</div><!-- /#blp-app -->

<!-- ══ LINK MODAL ═══════════════════════════════════════════════ -->
<div class="blp-modal-overlay" id="link-modal" style="display:none">
  <div class="blp-modal">
    <div class="blp-modal-header">
      <h3 id="modal-title">Add Link</h3>
      <button class="blp-modal-close" id="modal-close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="blp-modal-body">
      <input type="hidden" id="modal-link-id">
      <input type="hidden" id="modal-link-type" value="link">

      <!-- Link Type Selector -->
      <div class="blp-field">
        <label>Block Type</label>
        <div class="blp-link-type-picker" id="link-type-picker">
          <button class="blp-type-opt active" data-type="link" title="Link">🔗 Link</button>
          <button class="blp-type-opt" data-type="header" title="Header">📌 Header</button>
          <button class="blp-type-opt" data-type="divider" title="Divider">➖ Divider</button>
          <button class="blp-type-opt" data-type="text" title="Text Block">📝 Text</button>
          <button class="blp-type-opt" data-type="embed" title="Embed">▶️ Embed</button>
          <button class="blp-type-opt" data-type="accordion" title="Accordion/FAQ">📂 Accordion</button>
          <button class="blp-type-opt" data-type="vcard" title="Contact Card">📇 vCard</button>
          <button class="blp-type-opt" data-type="testimonial" title="Testimonial">⭐ Testimonial</button>
          <button class="blp-type-opt" data-type="gallery" title="Image Gallery">🖼️ Gallery</button>
        </div>
      </div>

      <!-- Common fields: Title -->
      <div class="blp-field" id="field-title">
        <label>Title <span class="blp-required">*</span></label>
        <input type="text" id="modal-title-input" placeholder="e.g. My YouTube Channel" class="blp-input">
      </div>

      <!-- Subtitle (link, header) -->
      <div class="blp-field" id="field-subtitle" style="display:none">
        <label>Subtitle</label>
        <input type="text" id="modal-subtitle-input" placeholder="Short description" class="blp-input">
      </div>

      <!-- URL field (link, embed) -->
      <div class="blp-field" id="field-url">
        <label>URL <span class="blp-required">*</span></label>
        <input type="url" id="modal-url-input" placeholder="https://..." class="blp-input">
      </div>

      <!-- Thumbnail URL (link) -->
      <div class="blp-field" id="field-thumbnail" style="display:none">
        <label>Thumbnail URL <small style="opacity:0.5">(optional)</small></label>
        <input type="url" id="modal-thumbnail-input" placeholder="https://example.com/image.jpg" class="blp-input">
      </div>

      <!-- Featured toggle (link) -->
      <div class="blp-field" id="field-featured" style="display:none">
        <label class="blp-toggle-label">
          <input type="checkbox" id="modal-featured-input">
          <span>Featured (large card)</span>
        </label>
      </div>

      <!-- Text / Accordion content -->
      <div class="blp-field" id="field-content" style="display:none">
        <label>Content</label>
        <textarea id="modal-content-input" class="blp-input" rows="4" placeholder="Enter your text content..."></textarea>
      </div>

      <!-- Embed type hint -->
      <div class="blp-field" id="field-embed-hint" style="display:none">
        <small style="opacity:0.6">Supported: YouTube, Spotify, SoundCloud, TikTok, Vimeo. Paste the share URL above.</small>
      </div>

      <!-- vCard fields -->
      <div id="field-vcard" style="display:none">
        <div class="blp-field">
          <label>Full Name</label>
          <input type="text" id="modal-vcard-name" placeholder="John Doe" class="blp-input">
        </div>
        <div class="blp-field-row">
          <div class="blp-field">
            <label>Email</label>
            <input type="email" id="modal-vcard-email" placeholder="john@example.com" class="blp-input">
          </div>
          <div class="blp-field">
            <label>Phone</label>
            <input type="tel" id="modal-vcard-phone" placeholder="+1 234 567 8900" class="blp-input">
          </div>
        </div>
        <div class="blp-field-row">
          <div class="blp-field">
            <label>Company</label>
            <input type="text" id="modal-vcard-company" placeholder="Acme Corp" class="blp-input">
          </div>
          <div class="blp-field">
            <label>Job Title</label>
            <input type="text" id="modal-vcard-jobtitle" placeholder="CEO" class="blp-input">
          </div>
        </div>
        <div class="blp-field">
          <label>Address</label>
          <input type="text" id="modal-vcard-address" placeholder="123 Main St, City" class="blp-input">
        </div>
        <div class="blp-field">
          <label>Website</label>
          <input type="url" id="modal-vcard-website" placeholder="https://..." class="blp-input">
        </div>
      </div>

      <!-- Testimonial fields -->
      <div id="field-testimonial" style="display:none">
        <div class="blp-field">
          <label>Review Text <span class="blp-required">*</span></label>
          <textarea id="modal-testimonial-content" class="blp-input" rows="3" placeholder="This product is amazing..."></textarea>
        </div>
        <div class="blp-field-row">
          <div class="blp-field">
            <label>Author Name</label>
            <input type="text" id="modal-testimonial-author" placeholder="Jane Smith" class="blp-input">
          </div>
          <div class="blp-field">
            <label>Company</label>
            <input type="text" id="modal-testimonial-company" placeholder="Acme Corp" class="blp-input">
          </div>
        </div>
        <div class="blp-field-row">
          <div class="blp-field">
            <label>Author Avatar URL</label>
            <input type="url" id="modal-testimonial-avatar" placeholder="https://..." class="blp-input">
          </div>
          <div class="blp-field blp-field-xs">
            <label>Rating</label>
            <select id="modal-testimonial-rating" class="blp-input">
              <option value="5">⭐⭐⭐⭐⭐</option>
              <option value="4">⭐⭐⭐⭐</option>
              <option value="3">⭐⭐⭐</option>
              <option value="2">⭐⭐</option>
              <option value="1">⭐</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Gallery fields -->
      <div id="field-gallery" style="display:none">
        <div class="blp-field">
          <label>Image URLs <small style="opacity:0.5">(one per line)</small></label>
          <textarea id="modal-gallery-images" class="blp-input" rows="4" placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg"></textarea>
        </div>
      </div>

      <!-- Icon / Badge / Color row (link, embed, accordion, header) -->
      <div class="blp-field-row" id="field-icon-badge">
        <div class="blp-field">
          <label>Icon (emoji or name)</label>
          <input type="text" id="modal-icon-input" placeholder="🎥 or youtube" class="blp-input">
        </div>
        <div class="blp-field">
          <label>Badge Text</label>
          <input type="text" id="modal-badge-input" placeholder="NEW" class="blp-input blp-input-sm" maxlength="10">
        </div>
        <div class="blp-field blp-field-xs">
          <label>Badge Color</label>
          <input type="color" id="modal-badge-color" value="#ef4444" class="blp-color-input">
        </div>
      </div>

      <!-- UTM Parameters -->
      <div class="blp-field" id="field-utm" style="display:none">
        <label>UTM Parameters <small style="opacity:0.5">(auto-appended to URL)</small></label>
        <div class="blp-field-row" style="margin-top:6px">
          <div class="blp-field">
            <input type="text" id="modal-utm-source" class="blp-input blp-input-sm" placeholder="utm_source" maxlength="100">
          </div>
          <div class="blp-field">
            <input type="text" id="modal-utm-medium" class="blp-input blp-input-sm" placeholder="utm_medium" maxlength="100">
          </div>
          <div class="blp-field">
            <input type="text" id="modal-utm-campaign" class="blp-input blp-input-sm" placeholder="utm_campaign" maxlength="100">
          </div>
        </div>
      </div>

      <!-- A/B Test Title -->
      <div class="blp-field" id="field-title-b">
        <label>A/B Test Title <small style="opacity:0.5">(optional — visitors see A or B randomly)</small></label>
        <input type="text" id="modal-title-b" class="blp-input" placeholder="Alternative title variant..." maxlength="200">
        <p class="blp-help" id="ab-stats" style="display:none">A: <strong id="ab-clicks-a">0</strong> clicks | B: <strong id="ab-clicks-b">0</strong> clicks</p>
      </div>

      <!-- Schedule -->
      <div class="blp-field-row" id="field-schedule">
        <div class="blp-field">
          <label>Schedule Start <small style="opacity:0.5">(optional)</small></label>
          <input type="datetime-local" id="modal-schedule-start" class="blp-input">
        </div>
        <div class="blp-field">
          <label>Schedule End <small style="opacity:0.5">(optional)</small></label>
          <input type="datetime-local" id="modal-schedule-end" class="blp-input">
        </div>
      </div>

      <!-- Quick icon picker -->
      <div class="blp-field" id="field-icon-picker">
        <label>Quick Icons</label>
        <div class="blp-icon-picker" id="icon-picker">
          <?php
          $icons = ['🌐','📷','🎵','🎥','💼','✉️','📱','🛒','💬','🐦','📸','🎮','💡','📖','🏆','🔗'];
          foreach($icons as $icon): ?>
          <button class="blp-icon-opt" data-icon="<?= $icon ?>"><?= $icon ?></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="blp-modal-footer">
      <button class="blp-btn blp-btn-ghost" id="modal-cancel">Cancel</button>
      <button class="blp-btn blp-btn-primary" id="modal-save">Save Link</button>
    </div>
  </div>
</div>
