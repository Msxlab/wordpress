/* BioLink Pro - Admin JS */
(function($) {
  'use strict';

  /* ── State ──────────────────────────────────────────────── */
  const state = {
    profile:   BLP.profile || null,
    links:     BLP.links   || [],
    analytics: BLP.analytics || {},
    security:  BLP.security || {},
    license:   BLP.license || {},
    design: {
      theme: 'midnight-glass',
      bg_type: 'solid',
      bg_color: '#0a0a1a',
      bg_gradient_start: '#0a0a1a',
      bg_gradient_end:   '#1a0a3a',
      bg_gradient_angle: 135,
      accent_color: '#7c6df0',
      text_color:   '#ffffff',
      card_color:   '#1a1a2e',
      card_opacity: 90,
      font_family:  'DM Sans',
      button_style: 'rounded',
      button_effect:'lift',
      avatar_shape: 'circle',
      layout_variant: 'center_classic',
      show_social_icons: true,
      enable_particles: false,
      enable_gradient_anim: false,
      bg_effect: 'none',
      effect_intensity: 45,
      bg_gradient_mid: '',
      card_blur: 0,
      bg_image_url: '',
      bg_image_overlay: 50,
      bg_image_blur: 0,
      bg_video_url: '',
      music_url: '',
      enable_darklight: false,
      light_bg_color: '#f5f5f5',
      light_text_color: '#1a1a1a',
      light_card_color: '#ffffff',
      banner_url: '',
      banner_height: 200,
      countdown_date: '',
      countdown_label: '',
      status_text: '',
      status_emoji: '',
      og_image_url: '',
      meta_description: '',
      custom_css: '',
      social_links: {},
    },
    profilesPage: {
      limit: 20,
      offset: 0,
      total: 0,
      search: '',
      status: 'all',
      sort: 'updated_desc',
      loaded: false,
    },
    selectedProfileIds: new Set(),
    dailyChart:   null,
    deviceChart:  null,
  };

  const HARD_RELOAD_KEYS = new Set([
    'layout_variant', 'avatar_shape', 'button_style', 'button_effect',
    'enable_particles', 'enable_gradient_anim',
    'bg_effect', 'effect_intensity',
    'social_links', 'font_family', 'bg_type', 'show_social_icons'
  ]);
  const DESIGN_AUTOSAVE_DELAY = 900;
  const DEBUG_ENABLED = !!(window.BLP && parseInt(BLP.debug, 10) === 1);

  function debugTrace(message, error) {
    if (!DEBUG_ENABLED || typeof console === 'undefined' || typeof console.debug !== 'function') {
      return;
    }

    if (typeof error !== 'undefined') {
      console.debug('[BLP]', message, error);
      return;
    }

    console.debug('[BLP]', message);
  }

  let designSaveTimer;
  let designSaveInFlight = false;
  let designSaveQueued = false;

  /* ── Init ───────────────────────────────────────────────── */
  $(document).ready(function() {
    BLP.publicFallbackUrl = BLP.publicFallbackUrl || BLP.publicUrl || '';
    BLP.previewUrl = BLP.previewUrl || BLP.publicFallbackUrl || BLP.publicUrl || '';

    initTabs();
    initProfile();
    initLinks();
    initDesign();
    initAnalytics();
    initModal();
    initSecurity();
    initProfilesManager();
    initProfileSwitcher();

    // Restore profile URL button
    if (BLP.publicUrl || BLP.publicFallbackUrl) {
      const viewUrl = BLP.publicFallbackUrl || BLP.publicUrl;
      const displayUrl = BLP.publicUrl || BLP.publicFallbackUrl;
      $('#blp-public-url').show();
      $('#blp-view-page').attr('href', viewUrl);
      $('#preview-url-text').text(displayUrl.replace(/^https?:\/\//, ''));
    }

    setDesignSaveStatus('Autosave on', 'idle');
  });

  /* ── Toast ──────────────────────────────────────────────── */
  function toast(msg, type = 'success') {
    const $t = $('#blp-toast');
    $t.text(msg).removeClass('success error').addClass(type).stop(true).fadeIn(200);
    clearTimeout(state._toastTimer);
    state._toastTimer = setTimeout(() => $t.fadeOut(300), 3000);
  }

  function setDesignSaveStatus(text, status) {
    const $status = $('#design-save-status');
    if (!$status.length) return;
    $status.attr('data-state', status || 'idle');
    $('#design-save-status-text').text(text || 'Autosave on');
  }

  function queueDesignAutosave(immediate = false) {
    clearTimeout(designSaveTimer);

    if (immediate) {
      saveDesignNow(false);
      return;
    }

    setDesignSaveStatus('Unsaved changes', 'dirty');
    designSaveTimer = setTimeout(() => saveDesignNow(false), DESIGN_AUTOSAVE_DELAY);
  }

  function saveDesignNow(manual = false, onComplete) {
    if (!BLP.profileId) {
      setDesignSaveStatus('Save profile first', 'error');
      if (typeof onComplete === 'function') onComplete();
      return;
    }

    if (designSaveInFlight) {
      designSaveQueued = true;
      if (typeof onComplete === 'function') onComplete();
      return;
    }

    designSaveInFlight = true;
    setDesignSaveStatus('Saving...', 'saving');

    ajax('blp_save_design', { profile_id: BLP.profileId, settings: state.design }, data => {
      designSaveInFlight = false;

      if (data && data.design && typeof data.design === 'object') {
        Object.assign(state.design, data.design);
      }

      setDesignSaveStatus('Saved', 'saved');
      if (manual) toast('Design saved!');

      // Only hard-reload if a structural key changed; color-only changes use postMessage
      if (previewNeedsHardReload) {
        refreshPreview();
      }

      if (designSaveQueued) {
        designSaveQueued = false;
        queueDesignAutosave(true);
      }

      setTimeout(() => {
        if (!designSaveInFlight) setDesignSaveStatus('Autosave on', 'idle');
      }, 1300);

      if (typeof onComplete === 'function') onComplete();
    }, () => {
      designSaveInFlight = false;
      setDesignSaveStatus('Save failed', 'error');

      if (designSaveQueued) {
        designSaveQueued = false;
        queueDesignAutosave(true);
      } else {
        designSaveTimer = setTimeout(() => queueDesignAutosave(true), 1400);
      }

      if (manual) toast('Could not save design.', 'error');
      if (typeof onComplete === 'function') onComplete();
    });
  }

  function resetDesignAutosaveState() {
    clearTimeout(designSaveTimer);
    designSaveInFlight = false;
    designSaveQueued = false;
    setDesignSaveStatus('Autosave on', 'idle');
  }

  /* ── AJAX Wrapper ───────────────────────────────────────── */
  function ajax(action, data, onSuccess, onError) {
    $.ajax({
      url: BLP.ajaxUrl,
      method: 'POST',
      data: { action, nonce: BLP.nonce, ...data },
      success(res) {
        if (res.success) {
          onSuccess && onSuccess(res.data);
        } else {
          const msg = res.data?.message || 'An error occurred.';
          toast(msg, 'error');
          onError && onError(msg);
        }
      },
      error() {
        toast('Network error. Please try again.', 'error');
        onError && onError();
      }
    });
  }

  /* ══ TABS ═══════════════════════════════════════════════════ */
  function initTabs() {
    $('.blp-tab').on('click', function() {
      const tab = $(this).data('tab');
      $('.blp-tab').removeClass('active').attr('aria-selected', 'false');
      $(this).addClass('active').attr('aria-selected', 'true');
      $('.blp-panel').removeClass('active');
      $('#tab-' + tab).addClass('active');

      if (tab === 'analytics') loadAnalytics(30);
      if (tab === 'design')    refreshPreview();
      if (tab === 'profiles')  loadProfilesTable(!state.profilesPage.loaded);
      if (tab === 'security')  loadSecurityAndLicenseStatus();
    });
  }

  /* ══ SECURITY + LICENSE ═════════════════════════════════════ */
  function initSecurity() {
    if (!$('#tab-security').length) return;

    renderSecuritySnapshot(BLP.security || {});
    renderLicenseSnapshot(BLP.license || {});

    if (parseInt(BLP.canManageSecurity, 10) !== 1) {
      $('#tab-security').find('input, textarea, select, button').not('#btn-security-refresh').prop('disabled', true);
    }

    $('#btn-security-refresh').on('click', function() {
      loadSecurityAndLicenseStatus();
    });

    $('#btn-security-save-settings').on('click', function() {
      ajax('blp_save_security_settings', {
        settings: JSON.stringify(collectSecuritySettingsPayload())
      }, function(res) {
        renderSecuritySnapshot(res.security || {});
        toast('Security settings saved.');
      });
    });

    $('#btn-security-set-mode').on('click', function() {
      const mode = $('#security-mode-select').val() || 'normal';
      if (mode === 'lockdown' && !confirm('Enable LOCKDOWN mode? This will block most plugin actions until reverted.')) {
        return;
      }

      ajax('blp_set_security_mode', { mode }, function(res) {
        renderSecuritySnapshot(res.security || {});
        toast('Security mode updated to ' + mode + '.');
      });
    });

    $('#btn-security-integrity-scan').on('click', function() {
      const $btn = $(this);
      $btn.prop('disabled', true).text('Scanning...');

      ajax('blp_run_integrity_scan', {}, function(res) {
        renderSecuritySnapshot(res.security || {});
        const mismatchCount = parseInt(res.scan?.mismatch_count || 0, 10);
        if (mismatchCount > 0) {
          toast('Integrity mismatches detected: ' + mismatchCount, 'error');
        } else {
          toast('Integrity scan completed: clean.');
        }
      }, function() {
        toast('Integrity scan failed.', 'error');
      });

      setTimeout(function() {
        $btn.prop('disabled', false).text('Run Integrity Scan');
      }, 800);
    });

    $('#btn-security-policy-pull').on('click', function() {
      ajax('blp_pull_remote_policy', {}, function(res) {
        renderSecuritySnapshot(res.security || {});
        const mode = (res.policy && res.policy.mode) ? res.policy.mode : 'unknown';
        toast('Remote policy pulled (' + mode + ').');
      });
    });

    $('#btn-security-test-alert').on('click', function() {
      ajax('blp_send_security_test_alert', {}, function(res) {
        renderSecuritySnapshot(res.security || {});
        toast('Test alert triggered. Check your notification channels.');
      });
    });

    $('#btn-license-save-settings').on('click', function() {
      ajax('blp_save_license_settings', {
        settings: JSON.stringify(collectLicenseSettingsPayload())
      }, function(res) {
        renderLicenseSnapshot(res.license || {});
        toast('License settings saved.');
      });
    });

    $('#btn-license-save-key').on('click', function() {
      const licenseKey = $('#license-key-input').val().trim();
      ajax('blp_license_save_key', { license_key: licenseKey }, function(res) {
        renderLicenseSnapshot(res.license || {});
        $('#license-key-input').val('');
        toast('License key saved.');
      });
    });

    $('#btn-license-activate').on('click', function() {
      ajax('blp_license_activate', {}, function(res) {
        renderLicenseSnapshot(res.license || {});
        toast('License activation request completed.');
      });
    });

    $('#btn-license-validate').on('click', function() {
      ajax('blp_license_validate', {}, function(res) {
        renderLicenseSnapshot(res.license || {});
        toast('License validation request completed.');
      });
    });

    $('#btn-license-deactivate').on('click', function() {
      if (!confirm('Deactivate license for this domain?')) return;
      ajax('blp_license_deactivate', {}, function(res) {
        renderLicenseSnapshot(res.license || {});
        toast('License deactivated.');
      });
    });

    loadSecurityAndLicenseStatus();
  }

  function loadSecurityAndLicenseStatus() {
    ajax('blp_get_security_status', {}, function(res) {
      renderSecuritySnapshot(res.security || {});
    });

    ajax('blp_get_license_status', {}, function(res) {
      renderLicenseSnapshot(res.license || {});
    });
  }

  function collectSecuritySettingsPayload() {
    return {
      alerts_enabled: $('#security-alerts-enabled').is(':checked') ? 1 : 0,
      auto_read_only_on_tamper: $('#security-auto-readonly').is(':checked') ? 1 : 0,
      alert_email: $('#security-alert-email').val().trim(),
      webhook_url: $('#security-webhook-url').val().trim(),
      policy_url: $('#security-policy-url').val().trim(),
      policy_public_key: $('#security-policy-public-key').val().trim(),
      lockdown_message: $('#security-lockdown-message').val().trim(),
      allow_unsigned_policy: $('#security-allow-unsigned-policy').is(':checked') ? 1 : 0,
    };
  }

  function collectLicenseSettingsPayload() {
    return {
      api_base_url: $('#license-api-base-url').val().trim(),
      product_id: $('#license-product-id').val().trim(),
      timeout: parseInt($('#license-timeout').val(), 10) || 12,
      verify_ssl: $('#license-verify-ssl').is(':checked') ? 1 : 0,
    };
  }

  function renderSecuritySnapshot(snapshot) {
    const data = (snapshot && typeof snapshot === 'object') ? snapshot : {};
    const settings = data.settings || {};
    const integrity = data.integrity || {};
    const mode = (data.mode || 'normal').toLowerCase();

    state.security = data;

    $('#security-mode-badge').text(mode).attr('data-mode', mode);
    $('#security-mode-select').val(mode);

    $('#security-alerts-enabled').prop('checked', parseInt(settings.alertsEnabled, 10) === 1);
    $('#security-auto-readonly').prop('checked', parseInt(settings.autoReadOnlyOnTamper, 10) === 1);
    $('#security-alert-email').val(settings.alertEmail || '');
    $('#security-webhook-url').val(settings.webhookUrl || '');
    $('#security-policy-url').val(settings.policyUrl || '');
    $('#security-policy-public-key').val(settings.policyPublicKey || '');
    $('#security-lockdown-message').val(settings.lockdownMessage || '');
    $('#security-allow-unsigned-policy').prop('checked', parseInt(settings.allowUnsignedPolicy, 10) === 1);

    const integrityStatus = (integrity.status || 'unknown').toLowerCase();
    const mismatchCount = parseInt(integrity.mismatch_count || 0, 10);
    const scannedFiles = parseInt(integrity.scanned_files || 0, 10);
    const lastScan = integrity.last_scan_at || '-';

    $('#security-integrity-status')
      .text(integrityStatus + ' (' + mismatchCount + ' mismatch)')
      .attr('data-status', integrityStatus);
    $('#security-integrity-meta').text('Last scan: ' + lastScan + ' • Files scanned: ' + scannedFiles);

    const mismatches = Array.isArray(integrity.mismatches) ? integrity.mismatches.slice(0, 10) : [];
    if (!mismatches.length) {
      $('#security-integrity-mismatches').html('<span>No mismatch details.</span>');
    } else {
      const mismatchHtml = mismatches.map(function(item) {
        const type = escHtml(item.type || 'changed');
        const file = escHtml(item.file || 'unknown');
        const critical = parseInt(item.critical, 10) === 1 ? ' <strong>(critical)</strong>' : '';
        return '<div>[' + type + '] ' + file + critical + '</div>';
      }).join('');
      $('#security-integrity-mismatches').html(mismatchHtml);
    }

    renderSecurityEvents(Array.isArray(data.events) ? data.events : []);
  }

  function renderSecurityEvents(events) {
    const $list = $('#security-events-list');
    if (!$list.length) return;

    if (!events.length) {
      $list.html('<p class="blp-help" style="margin:0">No security events logged yet.</p>');
      return;
    }

    const html = events.slice(0, 20).map(function(event) {
      const severity = String(event.severity || 'info').toLowerCase();
      const statusAttr = (severity === 'high' || severity === 'critical')
        ? 'error'
        : (severity === 'medium' ? 'pending' : 'clean');

      return [
        '<div class="blp-security-event">',
          '<div class="blp-security-event-head">',
            '<span class="blp-security-event-code">' + escHtml(event.code || 'event') + '</span>',
            '<span class="blp-security-pill" data-status="' + statusAttr + '">' + escHtml(severity) + '</span>',
          '</div>',
          '<div class="blp-security-event-message">' + escHtml(event.message || '') + '</div>',
          '<div class="blp-security-event-time">' + escHtml(event.time || '') + '</div>',
        '</div>'
      ].join('');
    }).join('');

    $list.html(html);
  }

  function renderLicenseSnapshot(snapshot) {
    const data = (snapshot && typeof snapshot === 'object') ? snapshot : {};
    const settings = data.settings || {};
    const status = (data.status || 'inactive').toLowerCase();

    state.license = data;

    $('#license-status-badge').text(status).attr('data-status', status);
    $('#license-message').text(data.message || '');
    $('#license-domain').text(data.domain || '-');
    $('#license-last-check').text(data.lastCheck || '-');
    $('#license-expires').text(data.expiresAt || '-');

    $('#license-api-base-url').val(settings.apiBaseUrl || '');
    $('#license-product-id').val(settings.productId || 'biolink-pro');
    $('#license-timeout').val(parseInt(settings.timeout, 10) || 12);
    $('#license-verify-ssl').prop('checked', parseInt(settings.verifySsl, 10) !== 0);

    if (data.maskedKey) {
      $('#license-key-input').attr('placeholder', data.maskedKey);
    } else {
      $('#license-key-input').attr('placeholder', 'XXXX-XXXX-XXXX-XXXX');
    }
  }

  /* ══ PROFILE ════════════════════════════════════════════════ */
  function initProfile() {
    const p = state.profile;
    if (p) {
      $('#profile-username').val(p.username || '');
      $('#profile-display-name').val(p.display_name || '');
      $('#profile-bio').val(p.bio || '');
      $('#avatar-url').val(p.avatar_url || '');
      $('#profile-custom-domain').val(p.custom_domain || '');
      if (p.avatar_url) showAvatarPreview(p.avatar_url);

      // Load saved design
      if (p.theme_settings) {
        try {
          const saved = JSON.parse(p.theme_settings);
          Object.assign(state.design, saved);
        } catch(e) {
          debugTrace('Could not parse profile theme_settings JSON.', e);
        }
      }
    }

    generateQR();

    // Avatar file preview
    $('#avatar-file').on('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => showAvatarPreview(e.target.result);
        reader.readAsDataURL(file);
      }
    });

    $('#avatar-url').on('blur', function() {
      if (this.value) showAvatarPreview(this.value);
    });

    // Save profile
    $('#btn-save-profile').on('click', function() {
      const $btn = $(this);
      const username = $('#profile-username').val().trim();
      if (!username) { toast('Username is required', 'error'); return; }

      $btn.text('Saving...').prop('disabled', true);

      const formData = new FormData();
      formData.append('action', 'blp_save_profile');
      formData.append('nonce', BLP.nonce);
      formData.append('profile_id', BLP.profileId || 0);
      formData.append('username',     username);
      formData.append('display_name', $('#profile-display-name').val());
      formData.append('bio',          $('#profile-bio').val());
      formData.append('avatar_url',   $('#avatar-url').val());
      formData.append('custom_domain', $('#profile-custom-domain').val());

      const avatarFile = document.getElementById('avatar-file').files[0];
      if (avatarFile) formData.append('avatar', avatarFile);

      fetch(BLP.ajaxUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            toast('Profile saved!');
            // Update state in-place instead of reloading
            if (res.data.profile_id) BLP.profileId = res.data.profile_id;
            if (res.data.publicUrl || res.data.publicFallbackUrl) {
              if (BLP.publicUrl && res.data.publicUrl && BLP.publicUrl !== res.data.publicUrl) {
                previewLoaded = false;
                previewSrc = '';
              }

              BLP.publicUrl = res.data.publicUrl || BLP.publicUrl || '';
              BLP.publicFallbackUrl = res.data.publicFallbackUrl || BLP.publicFallbackUrl || BLP.publicUrl || '';

              const viewUrl = BLP.publicFallbackUrl || BLP.publicUrl;
              const displayUrl = BLP.publicUrl || BLP.publicFallbackUrl;

              $('#blp-public-url').show();
              $('#blp-view-page').attr('href', viewUrl);
              $('#preview-url-text').text(displayUrl.replace(/^https?:\/\//, ''));
            }
            if (res.data.previewUrl) {
              BLP.previewUrl = res.data.previewUrl;
            }

            // Update local profile state
            if (res.data.profile && typeof res.data.profile === 'object') {
              state.profile = res.data.profile;
              BLP.profile = res.data.profile;
            } else {
              state.profile = state.profile || {};
              state.profile.username     = username;
              state.profile.display_name = $('#profile-display-name').val();
              state.profile.bio          = $('#profile-bio').val();
              state.profile.avatar_url   = $('#avatar-url').val();
            }

            const resolvedAvatarUrl = (state.profile && state.profile.avatar_url) ? state.profile.avatar_url : ($('#avatar-url').val() || '');
            if (!state.profile) state.profile = {};
            state.profile.avatar_url = resolvedAvatarUrl;
            $('#avatar-url').val(resolvedAvatarUrl);
            showAvatarPreview(resolvedAvatarUrl);

            // Keep profile switcher list synchronized
            if (res.data.profile_id) {
              const activeId = parseInt(res.data.profile_id, 10);
              const idx = state.profiles.findIndex(p => parseInt(p.id, 10) === activeId);
              const mergedProfile = {
                ...(idx !== -1 ? state.profiles[idx] : {}),
                ...(res.data.profile || {}),
                id: activeId,
                username,
                display_name: $('#profile-display-name').val(),
                avatar_url: resolvedAvatarUrl,
                publicUrl: res.data.publicUrl || BLP.publicUrl || '',
                publicFallbackUrl: res.data.publicFallbackUrl || BLP.publicFallbackUrl || BLP.publicUrl || '',
                previewUrl: res.data.previewUrl || BLP.previewUrl || '',
              };

              if (idx !== -1) {
                state.profiles[idx] = mergedProfile;
              } else {
                state.profiles.push(mergedProfile);
              }

              renderProfileSwitcher();
              updateCurrentProfileName();
            }

            generateQR();

            // Refresh preview with updated URL
            refreshPreview();
          } else {
            toast(res.data?.message || 'Error saving.', 'error');
          }
        })
        .catch(() => toast('Network error', 'error'))
        .finally(() => $btn.text('Save Profile').prop('disabled', false));
    });
  }

  function showAvatarPreview(url) {
    const $wrap = $('#avatar-preview');
    $wrap.empty();
    if (!url) {
      $wrap[0].innerHTML = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.4"><circle cx="12" cy="8" r="4"/><path d="M5 20c0-4 3.5-7 7-7s7 3 7 7"/></svg>';
      return;
    }
    // Use DOM API to avoid attribute-injection XSS
    const img = document.createElement('img');
    img.src = url;
    img.alt = 'Avatar';
    $wrap[0].appendChild(img);
  }

  /* ══ LINKS ══════════════════════════════════════════════════ */
  function initLinks() {
    renderLinks();

    // Sortable drag & drop
    const sortableEl = document.getElementById('links-sortable');
    if (sortableEl && typeof Sortable !== 'undefined') {
      Sortable.create(sortableEl, {
        handle: '.blp-link-handle',
        animation: 180,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd() {
          const orders = {};
          $('#links-sortable .blp-link-item').each(function(i) {
            orders[$(this).data('id')] = i;
          });
          ajax('blp_reorder_links', { profile_id: BLP.profileId, orders });
        }
      });
    }

    // Add link button
    $('#btn-add-link').on('click', () => openModal());
  }

  function renderLinks() {
    const $list  = $('#links-sortable');
    const $empty = $('#links-empty');
    $list.empty();

    if (!state.links.length) {
      $empty.show();
      return;
    }
    $empty.hide();

    state.links.forEach(link => $list.append(buildLinkItem(link)));
  }

  const LINK_TYPE_ICONS = {
    link: '🔗', header: '📌', divider: '➖', text: '📝',
    embed: '▶️', accordion: '📂', vcard: '📇', testimonial: '⭐', gallery: '🖼️'
  };

  function buildLinkItem(link) {
    const linkType = link.link_type || 'link';
    const icon    = link.icon || LINK_TYPE_ICONS[linkType] || '🔗';
    const visible = parseInt(link.is_visible) === 1;
    const badge   = link.badge ? `<span class="blp-link-badge" style="background:${escHtml(link.badge_color)}">${escHtml(link.badge)}</span>` : '';
    const typeBadge = linkType !== 'link' ? `<span class="blp-link-type-badge">${escHtml(linkType)}</span>` : '';
    const subtitle = link.subtitle ? `<div class="blp-link-subtitle">${escHtml(link.subtitle)}</div>` : '';
    const urlLine = link.url ? `<div class="blp-link-url">${escHtml(link.url)}</div>` : '';
    const featured = parseInt(link.is_featured) === 1 ? '<span class="blp-link-featured-badge">★</span>' : '';
    const scheduled = (link.schedule_start || link.schedule_end) ? '<span class="blp-link-schedule-badge" title="Scheduled">🕐</span>' : '';

    return $(`
      <li class="blp-link-item" data-id="${link.id}" data-type="${escHtml(linkType)}">
        <div class="blp-link-handle" title="Drag to reorder">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6"  x2="21" y2="6"/>
            <line x1="8" y1="12" x2="21" y2="12"/>
            <line x1="8" y1="18" x2="21" y2="18"/>
            <line x1="3" y1="6"  x2="3.01" y2="6"/>
            <line x1="3" y1="12" x2="3.01" y2="12"/>
            <line x1="3" y1="18" x2="3.01" y2="18"/>
          </svg>
        </div>
        <div class="blp-link-icon">${escHtml(icon)}</div>
        <div class="blp-link-info">
          <div class="blp-link-title">${escHtml(link.title)} ${badge} ${typeBadge} ${featured} ${scheduled}</div>
          ${subtitle}
          ${urlLine}
          <div class="blp-link-clicks">${link.click_count || 0} clicks</div>
        </div>
        <div class="blp-link-actions">
          <button class="blp-vis-btn ${visible ? 'on' : 'off'}" data-id="${link.id}" data-vis="${visible?1:0}" title="${visible?'Visible':'Hidden'}"></button>
          <button class="blp-btn blp-btn-secondary blp-btn-icon btn-edit-link" data-id="${link.id}" title="Edit">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="blp-btn blp-btn-danger blp-btn-icon btn-delete-link" data-id="${link.id}" title="Delete">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          </button>
        </div>
      </li>
    `);
  }

  // Event delegation for link actions
  $(document).on('click', '.btn-edit-link', function() {
    const id   = $(this).data('id');
    const link = state.links.find(l => parseInt(l.id) === parseInt(id));
    if (link) openModal(link);
  });

  $(document).on('click', '.btn-delete-link', function() {
    const id   = $(this).data('id');
    const link = state.links.find(l => parseInt(l.id) === parseInt(id));
    if (!confirm(`Delete "${link?.title || 'this link'}"?`)) return;

    ajax('blp_delete_link', { id, profile_id: BLP.profileId }, () => {
      state.links = state.links.filter(l => parseInt(l.id) !== parseInt(id));
      renderLinks();
      toast('Link deleted.');
    });
  });

  $(document).on('click', '.blp-vis-btn', function() {
    const $btn = $(this);
    const id   = $btn.data('id');
    const vis  = parseInt($btn.data('vis')) === 1 ? 0 : 1;

    $btn.data('vis', vis).toggleClass('on', vis === 1).toggleClass('off', vis === 0);

    ajax('blp_toggle_link', { id, visible: vis, profile_id: BLP.profileId }, () => {
      const link = state.links.find(l => parseInt(l.id) === parseInt(id));
      if (link) link.is_visible = vis;
    });
  });

  /* ══ MODAL ══════════════════════════════════════════════════ */

  // Define which fields are visible for each link type
  const LINK_TYPE_FIELDS = {
    link:        ['title','subtitle','url','thumbnail','featured','icon-badge','schedule','icon-picker','utm','title-b'],
    header:      ['title','subtitle','icon-badge'],
    divider:     [],
    text:        ['title','content'],
    embed:       ['title','url','embed-hint','icon-badge','schedule','utm','title-b'],
    accordion:   ['title','content','icon-badge','title-b'],
    vcard:       ['title','vcard','icon-badge'],
    testimonial: ['title','testimonial'],
    gallery:     ['title','gallery'],
  };

  function initModal() {
    $('#modal-close, #modal-cancel').on('click', closeModal);
    $('#link-modal').on('click', function(e) {
      if (e.target === this) closeModal();
    });

    // Link type picker
    $(document).on('click', '.blp-type-opt', function() {
      $('.blp-type-opt').removeClass('active');
      $(this).addClass('active');
      const type = $(this).data('type');
      $('#modal-link-type').val(type);
      updateModalFieldVisibility(type);
    });

    // Icon picker
    $(document).on('click', '.blp-icon-opt', function() {
      $('#modal-icon-input').val($(this).data('icon'));
    });

    // Save link
    $('#modal-save').on('click', saveLink);
  }

  function updateModalFieldVisibility(type) {
    const fields = LINK_TYPE_FIELDS[type] || LINK_TYPE_FIELDS.link;
    const allFields = ['title','subtitle','url','thumbnail','featured','content','embed-hint','vcard','testimonial','gallery','icon-badge','schedule','icon-picker','utm','title-b'];
    allFields.forEach(f => {
      const el = document.getElementById('field-' + f);
      if (el) el.style.display = fields.includes(f) ? '' : 'none';
    });
  }

  function openModal(link = null) {
    const isEdit = !!link;
    const linkType = link?.link_type || 'link';
    $('#modal-title').text(isEdit ? 'Edit Block' : 'Add Block');
    $('#modal-link-id').val(link?.id || '');
    $('#modal-link-type').val(linkType);
    $('#modal-title-input').val(link?.title || '');
    $('#modal-subtitle-input').val(link?.subtitle || '');
    $('#modal-url-input').val(link?.url || '');
    $('#modal-thumbnail-input').val(link?.thumbnail_url || '');
    $('#modal-featured-input').prop('checked', parseInt(link?.is_featured) === 1);
    $('#modal-icon-input').val(link?.icon || '');
    $('#modal-badge-input').val(link?.badge || '');
    $('#modal-badge-color').val(link?.badge_color || '#ef4444');
    $('#modal-title-b').val(link?.title_b || '');
    $('#modal-schedule-start').val(link?.schedule_start ? utcDbToInputValue(link.schedule_start) : '');
    $('#modal-schedule-end').val(link?.schedule_end ? utcDbToInputValue(link.schedule_end) : '');

    // UTM fields
    const utmMeta = link?.metadata ? (typeof link.metadata === 'string' ? JSON.parse(link.metadata || '{}') : link.metadata) : {};
    $('#modal-utm-source').val(utmMeta.utm_source || '');
    $('#modal-utm-medium').val(utmMeta.utm_medium || '');
    $('#modal-utm-campaign').val(utmMeta.utm_campaign || '');

    // A/B stats
    if (link?.title_b) {
      $('#ab-stats').show();
      $('#ab-clicks-a').text(link.click_count || 0);
      $('#ab-clicks-b').text(link.click_count_b || 0);
    } else {
      $('#ab-stats').hide();
    }

    // Parse metadata for type-specific fields
    let meta = {};
    if (link?.metadata) {
      try {
        meta = typeof link.metadata === 'string' ? JSON.parse(link.metadata) : link.metadata;
      } catch(e) {
        debugTrace('Could not parse link metadata JSON.', e);
      }
    }

    // Content field (text, accordion)
    $('#modal-content-input').val(meta.content || '');

    // vCard fields
    $('#modal-vcard-name').val(meta.full_name || '');
    $('#modal-vcard-email').val(meta.email || '');
    $('#modal-vcard-phone').val(meta.phone || '');
    $('#modal-vcard-company').val(meta.company || '');
    $('#modal-vcard-jobtitle').val(meta.job_title || '');
    $('#modal-vcard-address').val(meta.address || '');
    $('#modal-vcard-website').val(meta.website || '');

    // Testimonial fields
    $('#modal-testimonial-content').val(meta.content || '');
    $('#modal-testimonial-author').val(meta.author || '');
    $('#modal-testimonial-company').val(meta.company || '');
    $('#modal-testimonial-avatar').val(meta.avatar_url || '');
    $('#modal-testimonial-rating').val(meta.rating || 5);

    // Gallery fields
    $('#modal-gallery-images').val(Array.isArray(meta.images) ? meta.images.join('\n') : '');

    // Set active type picker button
    $('.blp-type-opt').removeClass('active');
    $(`.blp-type-opt[data-type="${linkType}"]`).addClass('active');

    // If editing, disable type picker (can't change type of existing block)
    if (isEdit) {
      $('.blp-type-opt').prop('disabled', true).css('opacity', '0.5');
      $(`.blp-type-opt[data-type="${linkType}"]`).prop('disabled', false).css('opacity', '1');
    } else {
      $('.blp-type-opt').prop('disabled', false).css('opacity', '1');
    }

    updateModalFieldVisibility(linkType);
    $('#link-modal').fadeIn(150);
    setTimeout(() => $('#modal-title-input').focus(), 150);
  }

  function closeModal() {
    $('#link-modal').fadeOut(150);
  }

  function formatDateInWpTimezone(dateObj) {
    const tz = BLP.wpTimezone || 'UTC';

    const toParts = function(zone) {
      return new Intl.DateTimeFormat('en-CA', {
        timeZone: zone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
      }).formatToParts(dateObj);
    };

    try {
      let parts;
      try {
        parts = toParts(tz);
      } catch (err) {
        parts = toParts('UTC');
      }

      const map = {};
      parts.forEach(function(part) { map[part.type] = part.value; });

      return {
        dateTimeLocal: `${map.year}-${map.month}-${map.day}T${map.hour}:${map.minute}`,
        dateTimeSql: `${map.year}-${map.month}-${map.day} ${map.hour}:${map.minute}:${map.second || '00'}`,
      };
    } catch (err) {
      return {
        dateTimeLocal: '',
        dateTimeSql: '',
      };
    }
  }

  function utcDbToInputValue(utcValue) {
    if (!utcValue) return '';

    const dt = new Date(String(utcValue).replace(' ', 'T') + 'Z');
    if (Number.isNaN(dt.getTime())) return '';

    return formatDateInWpTimezone(dt).dateTimeLocal;
  }

  function inputValueToWpSql(inputValue) {
    if (!inputValue) return '';

    const normalized = String(inputValue).trim().replace('T', ' ');
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(normalized)) {
      return normalized + ':00';
    }

    return normalized;
  }

  function buildMetadata(linkType) {
    const meta = {};
    switch (linkType) {
      case 'text':
      case 'accordion':
        meta.content = $('#modal-content-input').val();
        break;
      case 'vcard':
        meta.full_name = $('#modal-vcard-name').val();
        meta.email     = $('#modal-vcard-email').val();
        meta.phone     = $('#modal-vcard-phone').val();
        meta.company   = $('#modal-vcard-company').val();
        meta.job_title = $('#modal-vcard-jobtitle').val();
        meta.address   = $('#modal-vcard-address').val();
        meta.website   = $('#modal-vcard-website').val();
        break;
      case 'testimonial':
        meta.content    = $('#modal-testimonial-content').val();
        meta.author     = $('#modal-testimonial-author').val();
        meta.company    = $('#modal-testimonial-company').val();
        meta.avatar_url = $('#modal-testimonial-avatar').val();
        meta.rating     = parseInt($('#modal-testimonial-rating').val()) || 5;
        break;
      case 'embed':
        meta.embed_type = 'auto';
        break;
      case 'gallery':
        meta.images = ($('#modal-gallery-images').val() || '').split('\n').map(s => s.trim()).filter(Boolean);
        break;
    }
    // UTM params (for link and embed types)
    const utmS = $('#modal-utm-source').val()?.trim();
    const utmM = $('#modal-utm-medium').val()?.trim();
    const utmC = $('#modal-utm-campaign').val()?.trim();
    if (utmS) meta.utm_source = utmS;
    if (utmM) meta.utm_medium = utmM;
    if (utmC) meta.utm_campaign = utmC;

    return JSON.stringify(meta);
  }

  function saveLink() {
    const id       = $('#modal-link-id').val();
    const linkType = $('#modal-link-type').val() || 'link';
    const title    = $('#modal-title-input').val().trim();
    const url      = $('#modal-url-input').val().trim();
    const requiresUrl = linkType === 'link' || linkType === 'embed';

    const scheduleStart = inputValueToWpSql($('#modal-schedule-start').val());
    const scheduleEnd = inputValueToWpSql($('#modal-schedule-end').val());

    if (!title && linkType !== 'divider') { toast('Title is required.', 'error'); return; }
    if (requiresUrl && !url) { toast('URL is required for this block type.', 'error'); return; }

    const data = {
      link_type: linkType,
      title: title || (linkType === 'divider' ? '—' : ''),
      title_b:       $('#modal-title-b').val() || '',
      url: requiresUrl ? url : '',
      subtitle:      $('#modal-subtitle-input').val() || '',
      thumbnail_url: $('#modal-thumbnail-input').val() || '',
      is_featured:   $('#modal-featured-input').is(':checked') ? 1 : 0,
      icon:          $('#modal-icon-input').val(),
      badge:         $('#modal-badge-input').val(),
      badge_color:   $('#modal-badge-color').val(),
      metadata:      buildMetadata(linkType),
      schedule_start: scheduleStart || '',
      schedule_end:   scheduleEnd || '',
    };

    if (id) {
      ajax('blp_update_link', { id, profile_id: BLP.profileId, ...data }, res => {
        const idx = state.links.findIndex(l => parseInt(l.id) === parseInt(id));
        if (idx !== -1) state.links[idx] = res.link;
        renderLinks();
        closeModal();
        toast('Block updated!');
      });
    } else {
      ajax('blp_add_link', { profile_id: BLP.profileId, ...data, sort_order: state.links.length }, res => {
        state.links.push(res.link);
        renderLinks();
        closeModal();
        toast('Block added!');
      });
    }
  }

  /* ══ DESIGN ═════════════════════════════════════════════════ */
  function initDesign() {
    // Load saved settings into UI
    applyDesignToUI(state.design);

    // Theme chips
    $(document).on('click', '.blp-theme-chip', function() {
      const theme = $(this).data('theme');
      $('.blp-theme-chip').removeClass('active');
      $(this).addClass('active');
      state.design.theme = theme;
      applyThemePreset(theme);
      previewNeedsHardReload = true;
      queueDesignAutosave();
      refreshPreview();
    });

    // Layout presets
    $(document).on('click', '.blp-layout-chip', function() {
      const layout = $(this).data('layout') || 'center_classic';
      $('.blp-layout-chip').removeClass('active');
      $(this).addClass('active');
      state.design.layout_variant = layout;
      previewNeedsHardReload = true;
      queueDesignAutosave();
      refreshPreview();
    });

    // One-click auto style packs
    $(document).on('click', '.blp-style-preset', function() {
      const presetKey = $(this).data('style-preset');
      const preset = STYLE_PRESETS[presetKey];
      if (!preset) return;

      const preservedDesign = {
        social_links: state.design.social_links || {},
        custom_css: state.design.custom_css || '',
      };
      Object.assign(state.design, DESIGN_DEFAULTS, preservedDesign, preset);
      applyDesignToUI(state.design);

      previewNeedsHardReload = true;
      queueDesignAutosave();
      refreshPreview();
      toast('Auto style applied.');
    });

    // Button style
    $(document).on('click', '.blp-btn-style', function() {
      $('.blp-btn-style').removeClass('active');
      $(this).addClass('active');
      state.design.button_style = $(this).data('style');
      previewNeedsHardReload = true;
      queueDesignAutosave();
      refreshPreview();
    });

    // All design inputs trigger preview refresh
    const previewTriggers = [
      '#bg-color','#bg-gradient-start','#bg-gradient-end','#bg-gradient-angle','#bg-gradient-mid',
      '#accent-color','#text-color','#card-color','#card-opacity','#card-blur',
      '#bg-effect','#effect-intensity',
      '#font-family','#btn-effect','#custom-css',
      'input[name=bg_type]','input[name=avatar_shape]',
      '#show-social-icons','#enable-particles','#enable-gradient-anim',
      '#bg-image-url','#bg-image-overlay','#bg-image-blur',
      '#banner-url','#banner-height',
      '#countdown-date','#countdown-label',
      '#status-emoji','#status-text',
      '#bg-video-url','#music-url',
      '#enable-darklight-toggle','#light-bg-color','#light-text-color','#light-card-color',
      '#og-image-url','#meta-description',
    ];

    $(previewTriggers.join(',')).on('input change', function() {
      const id  = $(this).attr('id') || $(this).attr('name');
      const val = $(this).is(':checkbox') ? this.checked : $(this).val();

      // Map input to design key
      const map = {
        'bg-color':             'bg_color',
        'bg-gradient-start':    'bg_gradient_start',
        'bg-gradient-end':      'bg_gradient_end',
        'bg-gradient-angle':    'bg_gradient_angle',
        'bg-gradient-mid':      'bg_gradient_mid',
        'accent-color':         'accent_color',
        'text-color':           'text_color',
        'card-color':           'card_color',
        'card-opacity':         'card_opacity',
        'card-blur':            'card_blur',
        'bg-effect':            'bg_effect',
        'effect-intensity':     'effect_intensity',
        'font-family':          'font_family',
        'btn-effect':           'button_effect',
        'custom-css':           'custom_css',
        'bg_type':              'bg_type',
        'avatar_shape':         'avatar_shape',
        'show-social-icons':    'show_social_icons',
        'enable-particles':     'enable_particles',
        'enable-gradient-anim': 'enable_gradient_anim',
        'bg-image-url':         'bg_image_url',
        'bg-image-overlay':     'bg_image_overlay',
        'bg-image-blur':        'bg_image_blur',
        'banner-url':           'banner_url',
        'banner-height':        'banner_height',
        'countdown-date':       'countdown_date',
        'countdown-label':      'countdown_label',
        'status-emoji':         'status_emoji',
        'status-text':          'status_text',
        'bg-video-url':         'bg_video_url',
        'music-url':            'music_url',
        'enable-darklight-toggle': 'enable_darklight',
        'light-bg-color':       'light_bg_color',
        'light-text-color':     'light_text_color',
        'light-card-color':     'light_card_color',
        'og-image-url':         'og_image_url',
        'meta-description':     'meta_description',
      };

      if (map[id]) {
        const designKey = map[id];
        state.design[designKey] = val;
        if (HARD_RELOAD_KEYS.has(designKey)) {
          previewNeedsHardReload = true;
        }
      }

      if (id === 'card-opacity') $('#opacity-val').text(val);
      if (id === 'effect-intensity') $('#effect-intensity-val').text(val);
      if (id === 'card-blur') $('#card-blur-val').text(val);
      if (id === 'bg-image-overlay') $('#bg-image-overlay-val').text(val);
      if (id === 'bg-image-blur') $('#bg-image-blur-val').text(val);
      if (id === 'banner-height') $('#banner-height-val').text(val);

      // Show/hide bg options
      if (id === 'bg_type') {
        $('#bg-solid-opts').toggle(val === 'solid');
        $('#bg-gradient-opts').toggle(val === 'gradient' || val === 'animated');
      }

      queueDesignAutosave();
      refreshPreview();
    });

    // Clear gradient mid color
    $('#clear-gradient-mid').on('click', function() {
      state.design.bg_gradient_mid = '';
      $('#bg-gradient-mid').val('#4a1a8a');
      previewNeedsHardReload = true;
      queueDesignAutosave();
      refreshPreview();
    });

    // Emoji picker for status indicator
    $(document).on('click', '.blp-emoji-opt', function() {
      const emoji = $(this).data('emoji');
      $('.blp-emoji-opt').removeClass('active');
      $(this).addClass('active');
      $('#status-emoji').val(emoji).trigger('change');
      $('#status-emoji-preview').text(emoji);
      $('#clear-status-emoji').show();
      state.design.status_emoji = emoji;
      queueDesignAutosave();
      refreshPreview();
    });

    // Dark/Light mode toggle visibility
    $('#enable-darklight-toggle').on('change', function() {
      $('#darklight-colors').toggle(this.checked);
    });

    $('#clear-status-emoji').on('click', function() {
      $('.blp-emoji-opt').removeClass('active');
      $('#status-emoji').val('').trigger('change');
      $('#status-emoji-preview').text('');
      $(this).hide();
      state.design.status_emoji = '';
      queueDesignAutosave();
      refreshPreview();
    });

    // AI Color Palette Generator
    function hslToHex(h, s, l) {
      s /= 100; l /= 100;
      const a = s * Math.min(l, 1 - l);
      const f = n => { const k = (n + h / 30) % 12; const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1); return Math.round(255 * color).toString(16).padStart(2, '0'); };
      return '#' + f(0) + f(8) + f(4);
    }

    function generatePalettes() {
      const palettes = [];
      const names = ['Deep Ocean', 'Sunset Glow', 'Forest Night', 'Neon Pop', 'Minimal Mono', 'Royal Purple'];
      for (let p = 0; p < 6; p++) {
        const baseHue = Math.floor(Math.random() * 360);
        const bg = hslToHex(baseHue, 15 + Math.random() * 20, 8 + Math.random() * 10);
        const accent = hslToHex((baseHue + 120 + Math.random() * 60) % 360, 60 + Math.random() * 30, 55 + Math.random() * 15);
        const text = hslToHex(baseHue, 5 + Math.random() * 10, 88 + Math.random() * 10);
        const card = hslToHex(baseHue, 10 + Math.random() * 15, 12 + Math.random() * 10);
        palettes.push({ name: names[p], bg, accent, text, card });
      }
      return palettes;
    }

    function renderPalettes() {
      const palettes = generatePalettes();
      const $container = $('#ai-color-palettes');
      $container.empty();
      palettes.forEach(p => {
        $container.append(`
          <div class="blp-ai-palette" data-bg="${p.bg}" data-accent="${p.accent}" data-text="${p.text}" data-card="${p.card}">
            <div class="blp-ai-swatch" style="background:${p.bg}"></div>
            <div class="blp-ai-swatch" style="background:${p.accent}"></div>
            <div class="blp-ai-swatch" style="background:${p.text}"></div>
            <div class="blp-ai-swatch" style="background:${p.card}"></div>
            <span class="blp-ai-palette-label">${p.name}</span>
          </div>
        `);
      });
    }

    renderPalettes();
    $('#btn-generate-palettes').on('click', renderPalettes);

    $(document).on('click', '.blp-ai-palette', function() {
      const bg = $(this).data('bg');
      const accent = $(this).data('accent');
      const text = $(this).data('text');
      const card = $(this).data('card');
      state.design.bg_color = bg;
      state.design.accent_color = accent;
      state.design.text_color = text;
      state.design.card_color = card;
      $('#bg-color').val(bg);
      $('#accent-color').val(accent);
      $('#text-color').val(text);
      $('#card-color').val(card);
      previewNeedsHardReload = true;
      queueDesignAutosave();
      refreshPreview();
      toast('Palette applied!');
    });

    // Font upload
    $('#font-file-input').on('change', function() {
      const file = this.files[0];
      if (!file) return;
      const $status = $('#font-upload-status');
      $status.text('Uploading...');
      const fd = new FormData();
      fd.append('action', 'blp_upload_font');
      fd.append('nonce', BLP.nonce);
      fd.append('font_file', file);
      fetch(BLP.ajaxUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
          if (res.success && res.data.font) {
            const f = res.data.font;
            $('#custom-fonts-group').append(`<option value="custom:${f.name}">${f.name}</option>`);
            $('#font-family').val('custom:' + f.name).trigger('change');
            $status.text('Uploaded: ' + f.name);
            toast('Font uploaded!');
          } else {
            $status.text(res.data?.message || 'Upload failed');
            toast(res.data?.message || 'Upload failed', 'error');
          }
          this.value = '';
        })
        .catch(() => { $status.text('Upload failed'); this.value = ''; });
    });

    // Load user's custom fonts into selector
    if (BLP.customFonts && Array.isArray(BLP.customFonts)) {
      BLP.customFonts.forEach(f => {
        $('#custom-fonts-group').append(`<option value="custom:${f.name}">${f.name}</option>`);
      });
    }

    // CSV link import
    $('#csv-import-input').on('change', function() {
      const file = this.files[0];
      if (!file) return;
      if (!confirm('Import links from CSV? This will add links to your current profile.')) { this.value = ''; return; }
      const fd = new FormData();
      fd.append('action', 'blp_import_links_csv');
      fd.append('nonce', BLP.nonce);
      fd.append('profile_id', BLP.profileId || 0);
      fd.append('csv_file', file);
      fetch(BLP.ajaxUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            toast(`Imported ${res.data.imported} links!`);
            if (res.data.links) { state.links = res.data.links; renderLinks(); }
          } else {
            toast(res.data?.message || 'Import failed', 'error');
          }
          this.value = '';
        })
        .catch(() => { toast('Import failed', 'error'); this.value = ''; });
    });

    // Save now (manual fallback; autosave is primary)
    $('#btn-save-design').on('click', function() {
      const $btn = $(this);
      $btn.text('Saving...').prop('disabled', true);
      saveDesignNow(true, () => $btn.text('Save Now').prop('disabled', false));
    });

    // Social links: populate from saved state and bind events
    initSocialLinks();

    // Initial theme chip state
    $(`.blp-theme-chip[data-theme="${state.design.theme}"]`).addClass('active');
    $(`.blp-btn-style[data-style="${state.design.button_style}"]`).addClass('active');
  }

  function initSocialLinks() {
    const saved = state.design.social_links || {};
    $('.blp-social-input').val('');
    $('.blp-social-field').removeClass('has-value');

    // Populate saved URLs into inputs
    let hasMoreValue = false;
    $('.blp-social-input').each(function() {
      const platform = $(this).data('platform');
      if (saved[platform]) {
        $(this).val(saved[platform]);
        $(this).closest('.blp-social-field').addClass('has-value');
        if ($(this).closest('#social-more-wrap').length) hasMoreValue = true;
      }
    });

    // Auto-expand "More" section if any secondary platform has a value
    if (hasMoreValue) {
      $('#social-more-wrap').show();
      $('#btn-social-more').hide();
    } else {
      $('#social-more-wrap').hide();
      $('#btn-social-more').show();
    }

    // Toggle "More platforms" button
    $('#btn-social-more').off('click.blpMore').on('click.blpMore', function() {
      $('#social-more-wrap').slideDown(200);
      $(this).slideUp(150);
    });

    // On input change, update state
    $(document)
      .off('input.blpSocial', '.blp-social-input')
      .on('input.blpSocial', '.blp-social-input', function() {
      const platform = $(this).data('platform');
      const val = $(this).val().trim();
      if (!state.design.social_links) state.design.social_links = {};
      if (val) {
        state.design.social_links[platform] = val;
        $(this).closest('.blp-social-field').addClass('has-value');
      } else {
        delete state.design.social_links[platform];
        $(this).closest('.blp-social-field').removeClass('has-value');
      }
      previewNeedsHardReload = true;
      queueDesignAutosave();
      refreshPreview();
    });
  }

  function applyDesignToUI(d) {
    $('#bg-color').val(d.bg_color || '#0a0a1a');
    $('#bg-gradient-start').val(d.bg_gradient_start || '#0a0a1a');
    $('#bg-gradient-end').val(d.bg_gradient_end || '#1a0a3a');
    $('#bg-gradient-angle').val(d.bg_gradient_angle || 135);
    $('#bg-gradient-mid').val(d.bg_gradient_mid || '#4a1a8a');
    $('#accent-color').val(d.accent_color || '#7c6df0');
    $('#text-color').val(d.text_color || '#ffffff');
    $('#card-color').val(d.card_color || '#1a1a2e');
    $('#card-opacity').val(d.card_opacity || 90);
    $('#opacity-val').text(d.card_opacity || 90);
    $('#card-blur').val(d.card_blur || 0);
    $('#card-blur-val').text(d.card_blur || 0);
    $('#bg-effect').val(d.bg_effect || 'none');
    $('#effect-intensity').val(d.effect_intensity ?? 45);
    $('#effect-intensity-val').text(d.effect_intensity ?? 45);
    $('#font-family').val(d.font_family || 'DM Sans');
    $('#btn-effect').val(d.button_effect || 'lift');
    $('#custom-css').val(d.custom_css || '');
    const showSocial = !(d.show_social_icons === false || d.show_social_icons === 0 || d.show_social_icons === '0');
    $('#show-social-icons').prop('checked', showSocial);
    $('#enable-particles').prop('checked', !!d.enable_particles);
    $('#enable-gradient-anim').prop('checked', !!d.enable_gradient_anim);

    // Faz 4 fields
    $('#bg-image-url').val(d.bg_image_url || '');
    $('#bg-image-overlay').val(d.bg_image_overlay ?? 50);
    $('#bg-image-overlay-val').text(d.bg_image_overlay ?? 50);
    $('#bg-image-blur').val(d.bg_image_blur || 0);
    $('#bg-image-blur-val').text(d.bg_image_blur || 0);
    $('#banner-url').val(d.banner_url || '');
    $('#banner-height').val(d.banner_height || 200);
    $('#banner-height-val').text(d.banner_height || 200);
    $('#countdown-date').val(d.countdown_date || '');
    $('#countdown-label').val(d.countdown_label || '');
    $('#status-emoji').val(d.status_emoji || '');
    $('#status-text').val(d.status_text || '');
    // Update emoji picker UI
    $('.blp-emoji-opt').removeClass('active');
    if (d.status_emoji) {
      $(`.blp-emoji-opt[data-emoji="${d.status_emoji}"]`).addClass('active');
      $('#status-emoji-preview').text(d.status_emoji);
      $('#clear-status-emoji').show();
    } else {
      $('#status-emoji-preview').text('');
      $('#clear-status-emoji').hide();
    }
    // New fields
    $('#bg-video-url').val(d.bg_video_url || '');
    $('#music-url').val(d.music_url || '');
    $('#enable-darklight-toggle').prop('checked', !!d.enable_darklight);
    $('#darklight-colors').toggle(!!d.enable_darklight);
    $('#light-bg-color').val(d.light_bg_color || '#f5f5f5');
    $('#light-text-color').val(d.light_text_color || '#1a1a1a');
    $('#light-card-color').val(d.light_card_color || '#ffffff');
    $('#og-image-url').val(d.og_image_url || '');
    $('#meta-description').val(d.meta_description || '');

    $(`input[name=bg_type][value="${d.bg_type || 'solid'}"]`).prop('checked', true);
    $(`input[name=avatar_shape][value="${d.avatar_shape || 'circle'}"]`).prop('checked', true);

    $('#bg-solid-opts').toggle(d.bg_type !== 'gradient' && d.bg_type !== 'animated');
    $('#bg-gradient-opts').toggle(d.bg_type === 'gradient' || d.bg_type === 'animated');

    $(`.blp-theme-chip`).removeClass('active');
    $(`.blp-theme-chip[data-theme="${d.theme}"]`).addClass('active');
    $(`.blp-btn-style`).removeClass('active');
    $(`.blp-btn-style[data-style="${d.button_style}"]`).addClass('active');
    $(`.blp-layout-chip`).removeClass('active');
    $(`.blp-layout-chip[data-layout="${d.layout_variant || 'center_classic'}"]`).addClass('active');
  }

  const THEME_PRESETS = {
    'midnight-glass': { bg_color:'#0a0a1a', accent_color:'#7c6df0', text_color:'#ffffff', card_color:'#1a1a2e', bg_type:'solid' },
    'aurora':         { bg_gradient_start:'#0f0c29', bg_gradient_end:'#302b63', accent_color:'#a78bfa', text_color:'#ffffff', card_color:'#1a1535', bg_type:'gradient' },
    'neon-punk':      { bg_color:'#000000', accent_color:'#00ff88', text_color:'#ffffff', card_color:'#0a0a0a', bg_type:'solid' },
    'pastel-dream':   { bg_color:'#fef3f8', accent_color:'#f472b6', text_color:'#1a1a2e', card_color:'#ffffff', bg_type:'solid' },
    'luxury-gold':    { bg_color:'#0d0d0d', accent_color:'#c9a227', text_color:'#f5f0e0', card_color:'#1a1508', bg_type:'solid' },
    'ocean-depth':    { bg_gradient_start:'#001a35', bg_gradient_end:'#003366', accent_color:'#0ea5e9', text_color:'#e0f4ff', card_color:'#002040', bg_type:'gradient' },
    'forest':         { bg_color:'#0a1a0e', accent_color:'#22c55e', text_color:'#dcfce7', card_color:'#0d2012', bg_type:'solid' },
    'minimal-white':  { bg_color:'#f8f9fa', accent_color:'#6366f1', text_color:'#111827', card_color:'#ffffff', bg_type:'solid' },
    'sunset-blaze':   { bg_gradient_start:'#1a0000', bg_gradient_end:'#4a1a00', accent_color:'#f97316', text_color:'#fff7ed', card_color:'#2a1000', bg_type:'gradient', bg_gradient_angle:160 },
    'cyber-wave':     { bg_color:'#0a0018', accent_color:'#e040fb', text_color:'#f3e8ff', card_color:'#1a0030', bg_type:'solid', enable_particles:true },
    'arctic-frost':   { bg_gradient_start:'#e0f2fe', bg_gradient_end:'#f0f9ff', accent_color:'#0284c7', text_color:'#0c4a6e', card_color:'#ffffff', bg_type:'gradient', bg_gradient_angle:180 },
    'rose-garden':    { bg_color:'#1a0a10', accent_color:'#fb7185', text_color:'#fff1f2', card_color:'#2a1018', bg_type:'solid' },
    'monochrome':     { bg_color:'#121212', accent_color:'#a3a3a3', text_color:'#e5e5e5', card_color:'#1e1e1e', bg_type:'solid' },
    'retrowave':      { bg_gradient_start:'#0f0028', bg_gradient_end:'#2d004a', accent_color:'#ff6ec7', text_color:'#ffffff', card_color:'#1a0040', bg_type:'gradient', enable_gradient_anim:true, bg_gradient_angle:135 },
    'earth-tone':     { bg_color:'#1c1917', accent_color:'#d97706', text_color:'#fef3c7', card_color:'#292524', bg_type:'solid' },
    'sakura':         { bg_gradient_start:'#fdf2f8', bg_gradient_end:'#fce7f3', accent_color:'#ec4899', text_color:'#831843', card_color:'#ffffff', bg_type:'gradient', bg_gradient_angle:170 },
    'nordic-slate':   { bg_color:'#0f172a', accent_color:'#38bdf8', text_color:'#e2e8f0', card_color:'#111c33', bg_type:'solid' },
    'terracotta-pro': { bg_gradient_start:'#2b2118', bg_gradient_end:'#4a3421', accent_color:'#ea580c', text_color:'#ffedd5', card_color:'#3a2a1f', bg_type:'gradient', bg_gradient_angle:155 },
    'emerald-night':  { bg_color:'#022c22', accent_color:'#10b981', text_color:'#d1fae5', card_color:'#06382d', bg_type:'solid' },
  };

  const STYLE_PRESETS = {
    corporate_flow: {
      layout_variant: 'executive_clean',
      theme: 'minimal-white',
      font_family: 'Plus Jakarta Sans',
      button_style: 'outline',
      button_effect: 'lift',
      bg_type: 'solid',
      bg_color: '#eef2ff',
      accent_color: '#1d4ed8',
      text_color: '#0f172a',
      card_color: '#ffffff',
      card_opacity: 98,
      enable_particles: false,
      enable_gradient_anim: false,
    },
    creative_pulse: {
      layout_variant: 'studio_bento',
      theme: 'retrowave',
      font_family: 'Syne',
      button_style: 'glass',
      button_effect: 'glow',
      enable_particles: true,
    },
    editorial_light: {
      layout_variant: 'magazine_split',
      theme: 'arctic-frost',
      font_family: 'Lora',
      button_style: 'outline',
      button_effect: 'slide',
      card_opacity: 94,
      enable_particles: false,
    },
    midnight_executive: {
      layout_variant: 'business_card_horizontal',
      theme: 'luxury-gold',
      font_family: 'Manrope',
      button_style: 'solid',
      button_effect: 'tilt',
      card_opacity: 92,
    },
    bento_brand: {
      layout_variant: 'avatar_floating_sidebar',
      theme: 'ocean-depth',
      font_family: 'Outfit',
      button_style: 'rounded',
      button_effect: 'lift',
      enable_gradient_anim: true,
    },
    minimal_pro: {
      layout_variant: 'minimal_stack',
      theme: 'monochrome',
      font_family: 'Inter',
      button_style: 'pill',
      button_effect: 'none',
      card_opacity: 86,
      enable_particles: false,
      enable_gradient_anim: false,
    },
    hero_authority: {
      layout_variant: 'hero_split_pro',
      theme: 'luxury-gold',
      font_family: 'Manrope',
      button_style: 'solid',
      button_effect: 'lift',
      card_opacity: 92,
      enable_particles: false,
      enable_gradient_anim: false,
    },
    directory_plus: {
      layout_variant: 'minimal_directory',
      theme: 'arctic-frost',
      font_family: 'Source Sans 3',
      button_style: 'outline',
      button_effect: 'none',
      card_opacity: 96,
      enable_particles: false,
      enable_gradient_anim: false,
    },
    agency_showcase: {
      layout_variant: 'agency_brief',
      theme: 'ocean-depth',
      font_family: 'Plus Jakarta Sans',
      button_style: 'glass',
      button_effect: 'slide',
      card_opacity: 90,
      enable_particles: false,
      enable_gradient_anim: true,
    },
    timeline_narrative: {
      layout_variant: 'timeline_story',
      theme: 'rose-garden',
      font_family: 'Merriweather',
      button_style: 'outline',
      button_effect: 'lift',
      bg_effect: 'grain',
      effect_intensity: 64,
      enable_particles: false,
      enable_gradient_anim: false,
    },
    split_signal: {
      layout_variant: 'split_hero_cards',
      theme: 'cyber-wave',
      font_family: 'Space Grotesk',
      button_style: 'glass',
      button_effect: 'glow',
      bg_effect: 'mesh',
      effect_intensity: 72,
      enable_particles: true,
      enable_gradient_anim: true,
    },
    mosaic_glow: {
      layout_variant: 'mosaic_showcase',
      theme: 'sunset-blaze',
      font_family: 'Outfit',
      button_style: 'solid',
      button_effect: 'tilt',
      bg_effect: 'orbs',
      effect_intensity: 68,
      enable_particles: false,
      enable_gradient_anim: true,
    },
    executive_slate: {
      layout_variant: 'executive_sidebar_pro',
      theme: 'nordic-slate',
      font_family: 'Plus Jakarta Sans',
      button_style: 'outline',
      button_effect: 'lift',
      bg_effect: 'constellation',
      effect_intensity: 58,
      enable_particles: false,
      enable_gradient_anim: false,
    },
    press_kit_luxe: {
      layout_variant: 'press_kit_split',
      theme: 'terracotta-pro',
      font_family: 'Merriweather',
      button_style: 'solid',
      button_effect: 'slide',
      bg_effect: 'aurora',
      effect_intensity: 66,
      enable_particles: false,
      enable_gradient_anim: true,
    },
    minimal_mono_pro: {
      layout_variant: 'minimal_premium_stack',
      theme: 'monochrome',
      font_family: 'Manrope',
      button_style: 'pill',
      button_effect: 'none',
      bg_effect: 'waves',
      effect_intensity: 46,
      card_opacity: 88,
      enable_particles: false,
      enable_gradient_anim: false,
    },
  };

  // Default design values used for resetting before preset application
  const DESIGN_DEFAULTS = {
    theme: 'midnight-glass',
    bg_type: 'solid',
    bg_color: '#0a0a1a',
    bg_gradient_start: '#0a0a1a',
    bg_gradient_end:   '#1a0a3a',
    bg_gradient_angle: 135,
    accent_color: '#7c6df0',
    text_color:   '#ffffff',
    card_color:   '#1a1a2e',
    card_opacity: 90,
    font_family:  'DM Sans',
    button_style: 'rounded',
    button_effect:'lift',
    avatar_shape: 'circle',
    layout_variant: 'center_classic',
    show_social_icons: true,
    enable_particles: false,
    enable_gradient_anim: false,
    bg_effect: 'none',
    effect_intensity: 45,
    custom_css: '',
    social_links: {},
  };

  function applyThemePreset(theme) {
    const preset = THEME_PRESETS[theme];
    if (!preset) return;
    const preservedDesign = {
      layout_variant: state.design.layout_variant || 'center_classic',
      social_links: state.design.social_links || {},
      custom_css: state.design.custom_css || '',
      show_social_icons: state.design.show_social_icons,
      avatar_shape: state.design.avatar_shape || 'circle',
    };
    // Reset ALL design keys to defaults first, then apply preset on top
    Object.assign(state.design, DESIGN_DEFAULTS, preservedDesign, preset, { theme });
    applyDesignToUI(state.design);
  }

  // Debounced preview refresh
  let previewTimer;
  let previewLoaded = false;
  let previewSrc = '';
  let previewNeedsHardReload = true;

  function getPreviewOverrideParams() {
    const params = {};

    HARD_RELOAD_KEYS.forEach(key => {
      const value = state.design[key];

      if (key === 'social_links') {
        if (value && typeof value === 'object' && Object.keys(value).length) {
          params.d_social_links = JSON.stringify(value);
        }
        return;
      }

      if (value === undefined || value === null || value === '') return;
      params['d_' + key] = typeof value === 'boolean' ? (value ? '1' : '0') : String(value);
    });

    return params;
  }

  function refreshPreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(_doRefreshPreview, 300);
  }

  function _doRefreshPreview() {
    const basePreviewUrl = BLP.previewUrl || BLP.publicFallbackUrl || BLP.publicUrl;
    if (!basePreviewUrl || !BLP.previewNonce) return;
    const frame = document.getElementById('design-preview-frame');
    if (!frame) return;

    const overrideParams = getPreviewOverrideParams();

    let targetPreviewUrl;
    try {
      const urlObj = new URL(basePreviewUrl, window.location.origin);
      urlObj.searchParams.set('blp_preview', '1');
      urlObj.searchParams.set('blp_nonce', BLP.previewNonce);
      Object.keys(overrideParams).forEach(k => urlObj.searchParams.set(k, overrideParams[k]));
      targetPreviewUrl = urlObj.toString();
    } catch (e) {
      targetPreviewUrl = basePreviewUrl;
      targetPreviewUrl += (targetPreviewUrl.indexOf('?') === -1 ? '?' : '&')
        + 'blp_preview=1&blp_nonce=' + encodeURIComponent(BLP.previewNonce);

      Object.keys(overrideParams).forEach(k => {
        targetPreviewUrl += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(overrideParams[k]);
      });
    }

    // If iframe is already loaded with our page, use postMessage for instant update
    if (!previewNeedsHardReload && previewLoaded && previewSrc === targetPreviewUrl && frame.contentWindow) {
      frame.contentWindow.postMessage({
        type: 'blp_preview_update',
        design: state.design
      }, window.location.origin);
      return;
    }

    // First load: set iframe src with auth params
    previewLoaded = false;
    previewSrc = targetPreviewUrl;
    frame.src = targetPreviewUrl;
    const displayUrl = BLP.publicUrl || BLP.publicFallbackUrl || '';
    if (displayUrl) {
      $('#preview-url-text').text(displayUrl.replace(/^https?:\/\//, ''));
    }

    // Mark as loaded once iframe finishes loading
    frame.onload = function() {
      try {
        const doc = frame.contentDocument;
        if (!doc || !doc.getElementById('blp-page')) {
          previewLoaded = false;
          return;
        }
      } catch (e) {
        // Cross-origin: cannot inspect content, assume loaded
      }

      previewLoaded = true;
      previewNeedsHardReload = false;
      // Send initial design state via postMessage
      if (frame.contentWindow) {
        frame.contentWindow.postMessage({
          type: 'blp_preview_update',
          design: state.design
        }, window.location.origin);
      }
    };
  }

  // Device switcher
  $(document).on('click', '.blp-device-btn', function() {
    const device = $(this).data('device');
    $('.blp-device-btn').removeClass('active');
    $(this).addClass('active');
    $('#preview-device-wrap').attr('data-device', device);
  });

  /* ══ ANALYTICS ══════════════════════════════════════════════ */
  function initAnalytics() {
    $('#analytics-range').on('change', function() {
      loadAnalytics(parseInt(this.value));
    });
  }

  function loadAnalytics(days) {
    if (!BLP.profileId) {
      $('#stat-views, #stat-clicks, #stat-today, #stat-ctr').text('—');
      $('#top-links-body').html('<tr><td colspan="5" class="blp-table-empty">Save your profile to see analytics.</td></tr>');
      return;
    }

    ajax('blp_get_analytics', { profile_id: BLP.profileId, days }, data => {
      renderStats(data);
      renderDailyChart(data.daily, days);
      renderDeviceChart(data.devices);
      renderTopLinks(data.top_links);
      renderReferrers(data.referrers);
      renderHeatmap(data.heatmap);
      renderComparison(data);
    });
  }

  function renderStats(data) {
    $('#stat-views').text(formatNum(data.total_views || 0));
    $('#stat-clicks').text(formatNum(data.total_clicks || 0));
    $('#stat-today').text(formatNum(data.today_clicks || 0));

    const ctr = data.total_views > 0
      ? ((data.total_clicks / data.total_views) * 100).toFixed(1) + '%'
      : '0%';
    $('#stat-ctr').text(ctr);
  }

  function renderDailyChart(daily, days) {
    const ctx = document.getElementById('chart-daily');
    if (!ctx) return;

    // Fill in missing days
    const labels = [];
    const values = [];
    const dataMap = {};
    (daily || []).forEach(d => dataMap[d.date] = parseInt(d.clicks));

    const now = new Date();
    for (let i = days - 1; i >= 0; i--) {
      const d = new Date(now);
      d.setDate(d.getDate() - i);
      const key = d.toISOString().split('T')[0];
      labels.push(key.slice(5)); // MM-DD
      values.push(dataMap[key] || 0);
    }

    if (state.dailyChart) state.dailyChart.destroy();
    state.dailyChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Clicks',
          data: values,
          backgroundColor: 'rgba(124,109,240,0.6)',
          borderColor:     'rgba(124,109,240,1)',
          borderWidth: 1,
          borderRadius: 4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: {
            grid:  { color: 'rgba(255,255,255,0.04)' },
            ticks: { color: '#5a5a72', font: { size: 11 }, maxTicksLimit: 10 }
          },
          y: {
            grid:  { color: 'rgba(255,255,255,0.04)' },
            ticks: { color: '#5a5a72', font: { size: 11 }, stepSize: 1 },
            beginAtZero: true
          }
        }
      }
    });
  }

  function renderDeviceChart(devices) {
    const ctx = document.getElementById('chart-devices');
    if (!ctx) return;

    const map = { mobile: 0, desktop: 0, tablet: 0 };
    (devices || []).forEach(d => { map[d.device] = (map[d.device] || 0) + parseInt(d.count); });

    if (state.deviceChart) state.deviceChart.destroy();
    state.deviceChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Mobile', 'Desktop', 'Tablet'],
        datasets: [{
          data: [map.mobile, map.desktop, map.tablet],
          backgroundColor: ['rgba(124,109,240,0.8)', 'rgba(34,211,165,0.8)', 'rgba(251,191,36,0.8)'],
          borderColor:     ['#7c6df0', '#22d3a5', '#fbbf24'],
          borderWidth: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#f0f0f8', font: { size: 12 }, padding: 16 }
          }
        },
        cutout: '70%',
      }
    });
  }

  function renderTopLinks(links) {
    const $body = $('#top-links-body');
    if (!links || !links.length) {
      $body.html('<tr><td colspan="5" class="blp-table-empty">No clicks yet. Share your page!</td></tr>');
      return;
    }

    // period_clicks is returned by the updated analytics query (period-scoped)
    const maxClicks = Math.max(...links.map(l => parseInt(l.period_clicks) || 0), 1);
    let html = '';
    links.forEach((l, i) => {
      const clicks = parseInt(l.period_clicks) || 0;
      const pct = ((clicks / maxClicks) * 100).toFixed(1);
      html += `<tr>
        <td style="color:#5a5a72;font-weight:600">${i + 1}</td>
        <td>
          <div style="font-weight:600">${escHtml(l.title)}</div>
          <div class="blp-progress-bar"><div class="blp-progress-fill" style="width:${pct}%"></div></div>
        </td>
        <td style="color:#5a5a72;font-size:12px">${escHtml(truncate(l.url, 40))}</td>
        <td style="font-weight:700;color:#7c6df0">${formatNum(clicks)}</td>
        <td style="color:#5a5a72">${pct}%</td>
      </tr>`;
    });
    $body.html(html);
  }

  function renderReferrers(referrers) {
    const $body = $('#referrers-body');
    if (!referrers || !referrers.length) {
      $body.html('<tr><td colspan="4" class="blp-table-empty">No referrer data yet.</td></tr>');
      return;
    }

    const total = referrers.reduce((s, r) => s + parseInt(r.count), 0) || 1;
    let html = '';
    referrers.forEach((r, i) => {
      const count = parseInt(r.count) || 0;
      const pct = ((count / total) * 100).toFixed(1);
      html += `<tr>
        <td style="color:#5a5a72;font-weight:600">${i + 1}</td>
        <td>
          <div style="font-weight:600">${escHtml(r.source)}</div>
          <div class="blp-progress-bar"><div class="blp-progress-fill" style="width:${pct}%"></div></div>
        </td>
        <td style="font-weight:700;color:#7c6df0">${formatNum(count)}</td>
        <td style="color:#5a5a72">${pct}%</td>
      </tr>`;
    });
    $body.html(html);
  }

  function renderHeatmap(heatmap) {
    const $body = $('#heatmap-body');
    if (!heatmap || !heatmap.length) {
      $body.html('<tr><td colspan="25" class="blp-table-empty">No click data for heatmap yet.</td></tr>');
      return;
    }

    // Build matrix: dow (1=Sun..7=Sat) x hour (0..23)
    const matrix = {};
    let maxVal = 0;
    heatmap.forEach(h => {
      const key = h.dow + '-' + h.hour;
      const val = parseInt(h.count) || 0;
      matrix[key] = val;
      if (val > maxVal) maxVal = val;
    });

    const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    let html = '';
    days.forEach((day, di) => {
      const dow = di + 1; // DAYOFWEEK: 1=Sun
      html += `<tr><td style="font-weight:600;color:#5a5a72;white-space:nowrap">${day}</td>`;
      for (let h = 0; h < 24; h++) {
        const val = matrix[dow + '-' + h] || 0;
        const intensity = maxVal > 0 ? val / maxVal : 0;
        const bg = `rgba(124,109,240,${(intensity * 0.85 + 0.05).toFixed(2)})`;
        html += `<td style="background:${val ? bg : 'rgba(255,255,255,0.03)'};text-align:center;font-size:10px;padding:4px 2px;min-width:28px;border-radius:3px" title="${day} ${h}:00 — ${val} clicks">${val || ''}</td>`;
      }
      html += '</tr>';
    });
    $body.html(html);
  }

  function renderComparison(data) {
    const cv = data.total_views || 0;
    const cc = data.total_clicks || 0;
    const pv = data.prev_views || 0;
    const pc = data.prev_clicks || 0;

    $('#comp-views-current').text(formatNum(cv));
    $('#comp-views-prev').text(formatNum(pv));
    $('#comp-clicks-current').text(formatNum(cc));
    $('#comp-clicks-prev').text(formatNum(pc));

    const viewsChange = pv > 0 ? (((cv - pv) / pv) * 100).toFixed(1) : (cv > 0 ? '+100' : '0');
    const clicksChange = pc > 0 ? (((cc - pc) / pc) * 100).toFixed(1) : (cc > 0 ? '+100' : '0');

    const vSign = parseFloat(viewsChange) >= 0 ? '+' : '';
    const cSign = parseFloat(clicksChange) >= 0 ? '+' : '';
    const vColor = parseFloat(viewsChange) >= 0 ? '#22d3a5' : '#f87171';
    const cColor = parseFloat(clicksChange) >= 0 ? '#22d3a5' : '#f87171';

    $('#comp-change-text').html(
      `Views: <strong style="color:${vColor}">${vSign}${viewsChange}%</strong> vs previous period | ` +
      `Clicks: <strong style="color:${cColor}">${cSign}${clicksChange}%</strong> vs previous period`
    );
  }

  // CSV Export
  $(document).on('click', '#btn-export-csv', function() {
    const $btn = $(this);
    $btn.prop('disabled', true).text('Exporting...');
    const days = parseInt($('#analytics-range').val()) || 30;
    ajax('blp_export_analytics_csv', { profile_id: BLP.profileId, days }, res => {
      // Trigger download
      const blob = new Blob([res.csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = res.filename || 'analytics.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      toast('CSV exported!');
      $btn.prop('disabled', false).html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export CSV');
    });
  });

  /* ══ PROFILES MANAGER ═══════════════════════════════════════ */
  function initProfilesManager() {
    if (!$('#profiles-table-body').length) return;

    // Initial load for manager table
    loadProfilesTable(true);

    // Search (debounced)
    let searchTimer;
    $('#profiles-search').on('input', function() {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        state.profilesPage.search = $(this).val().trim();
        loadProfilesTable(true);
      }, 250);
    });

    $('#profiles-status-filter, #profiles-sort').on('change', function() {
      loadProfilesTable(true);
    });

    $('#profiles-refresh').on('click', function() {
      loadProfilesTable(true);
    });

    $('#profiles-prev').on('click', function() {
      if (state.profilesPage.offset <= 0) return;
      state.profilesPage.offset = Math.max(0, state.profilesPage.offset - state.profilesPage.limit);
      loadProfilesTable(false);
    });

    $('#profiles-next').on('click', function() {
      const nextOffset = state.profilesPage.offset + state.profilesPage.limit;
      if (nextOffset >= state.profilesPage.total) return;
      state.profilesPage.offset = nextOffset;
      loadProfilesTable(false);
    });

    $('#profiles-select-all').on('change', function() {
      const checked = this.checked;
      $('#profiles-table-body .blp-profile-select').prop('checked', checked);
      syncSelectedProfilesFromTable();
    });

    $(document).on('change', '#profiles-table-body .blp-profile-select', function() {
      syncSelectedProfilesFromTable();
      const all = $('#profiles-table-body .blp-profile-select').length > 0
        && $('#profiles-table-body .blp-profile-select:checked').length === $('#profiles-table-body .blp-profile-select').length;
      $('#profiles-select-all').prop('checked', all);
    });

    $('#profiles-bulk-activate').on('click', function() {
      runBulkProfileStatus(true);
    });

    $('#profiles-bulk-deactivate').on('click', function() {
      runBulkProfileStatus(false);
    });

    $('#profiles-bulk-duplicate').on('click', function() {
      runBulkDuplicateProfiles();
    });

    $('#profiles-bulk-delete').on('click', function() {
      runBulkDeleteProfiles();
    });

    $(document).on('click', '.blp-profile-toggle-status', function() {
      const id = parseInt($(this).data('id'), 10);
      const next = parseInt($(this).data('next'), 10) === 1;
      if (!id) return;
      setSingleProfileStatus(id, next);
    });

    $(document).on('click', '.blp-profile-switch-btn', function() {
      const id = parseInt($(this).data('id'), 10);
      if (!id) return;
      switchProfile(id);
    });

    $(document).on('click', '.blp-profile-edit-btn', function() {
      const id = parseInt($(this).data('id'), 10);
      if (!id) return;
      openProfileEditor(id);
    });

    $(document).on('click', '.blp-profile-duplicate-btn', function() {
      const id = parseInt($(this).data('id'), 10);
      if (!id) return;
      duplicateProfile(id);
    });

    $(document).on('click', '.blp-profile-delete-btn', function() {
      const id = parseInt($(this).data('id'), 10);
      if (!id) return;
      const displayName = $(this).data('name') || 'Profile';
      const linksCount = parseInt($(this).data('links') || 0, 10) || 0;
      deleteProfileById(id, { displayName, linksCount });
    });
  }

  function syncSelectedProfilesFromTable() {
    const ids = [];
    $('#profiles-table-body .blp-profile-select:checked').each(function() {
      ids.push(parseInt($(this).val(), 10));
    });
    state.selectedProfileIds = new Set(ids.filter(Boolean));
  }

  function loadProfilesTable(resetOffset = false) {
    const $body = $('#profiles-table-body');
    if (!$body.length) return;

    if (resetOffset) {
      state.profilesPage.offset = 0;
    }

    const searchVal = $('#profiles-search').val();
    state.profilesPage.search = String(searchVal || '').trim();
    state.profilesPage.status = String($('#profiles-status-filter').val() || 'all');
    state.profilesPage.sort = String($('#profiles-sort').val() || 'updated_desc');

    $body.html('<tr><td colspan="7" class="blp-table-empty">Loading profiles...</td></tr>');

    ajax('blp_get_profiles', {
      limit: state.profilesPage.limit,
      offset: state.profilesPage.offset,
      search: state.profilesPage.search,
      status: state.profilesPage.status,
      sort: state.profilesPage.sort,
    }, function(res) {
      const profiles = Array.isArray(res.profiles) ? res.profiles : [];

      state.profilesPage.total = parseInt(res.total || profiles.length, 10) || 0;
      state.profilesPage.limit = parseInt(res.limit || state.profilesPage.limit, 10) || state.profilesPage.limit;
      state.profilesPage.offset = parseInt(res.offset || state.profilesPage.offset, 10) || 0;
      state.profilesPage.loaded = true;

      renderProfilesTable(profiles);
      renderProfilesPagination();
    }, function() {
      $body.html('<tr><td colspan="7" class="blp-table-empty">Could not load profiles.</td></tr>');
    });
  }

  function renderProfilesTable(profiles) {
    const $body = $('#profiles-table-body');
    if (!$body.length) return;

    if (!profiles || !profiles.length) {
      $body.html('<tr><td colspan="7" class="blp-table-empty">No profiles found.</td></tr>');
      $('#profiles-select-all').prop('checked', false);
      return;
    }

    const currentProfileId = parseInt(BLP.profileId, 10);
    let html = '';
    profiles.forEach(function(p) {
      const pid = parseInt(p.id, 10);
      const displayName = p.display_name || p.username || 'Profile';
      const initial = displayName.charAt(0).toUpperCase();
      const avatarHtml = p.avatar_url
        ? '<img src="' + escHtml(p.avatar_url) + '" alt="">'
        : initial;
      const checked = state.selectedProfileIds.has(pid) ? ' checked' : '';
      const isActive = parseInt(p.is_active, 10) === 1;
      const statusClass = isActive ? 'active' : 'inactive';
      const statusText = isActive ? 'Active' : 'Inactive';
      const statusActionText = isActive ? 'Deactivate' : 'Activate';
      const statusActionNext = isActive ? 0 : 1;
      const profileUrl = p.publicFallbackUrl || p.publicUrl || '#';
      const updated = formatDateTimeLabel(p.updated_at);
      const linksCount = parseInt(p.links_count || 0, 10) || 0;
      const owner = parseInt(p.user_id || 0, 10) || 0;

      html += `<tr>
        <td><input type="checkbox" class="blp-profile-select" value="${pid}"${checked} aria-label="Select profile ${escHtml(displayName)}"></td>
        <td>
          <div class="blp-profile-row-main">
            <div class="blp-profile-row-avatar">${avatarHtml}</div>
            <div class="blp-profile-row-meta">
              <div class="blp-profile-row-name">${escHtml(displayName)}${pid === currentProfileId ? ' <span class="blp-profile-current-pill">Current</span>' : ''}</div>
              <div class="blp-profile-row-username">/${escHtml(p.username || '')}</div>
            </div>
          </div>
        </td>
        <td><span class="blp-profile-status blp-profile-status-${statusClass}">${statusText}</span></td>
        <td>#${owner}</td>
        <td>${formatNum(linksCount)}</td>
        <td>${escHtml(updated)}</td>
        <td>
          <div class="blp-profile-row-actions">
            <button class="blp-btn blp-btn-secondary blp-btn-sm blp-profile-switch-btn" data-id="${pid}">Switch</button>
            <button class="blp-btn blp-btn-ghost blp-btn-sm blp-profile-edit-btn" data-id="${pid}">Edit</button>
            <a class="blp-btn blp-btn-ghost blp-btn-sm" href="${escHtml(profileUrl)}" target="_blank" rel="noopener">Open</a>
            <button class="blp-btn blp-btn-ghost blp-btn-sm blp-profile-duplicate-btn" data-id="${pid}">Duplicate</button>
            <button class="blp-btn blp-btn-ghost blp-btn-sm blp-profile-toggle-status" data-id="${pid}" data-next="${statusActionNext}">${statusActionText}</button>
            <button class="blp-btn blp-btn-danger blp-btn-sm blp-profile-delete-btn" data-id="${pid}" data-name="${escHtml(displayName)}" data-links="${linksCount}">Delete</button>
          </div>
        </td>
      </tr>`;
    });

    $body.html(html);

    const allChecked = $('#profiles-table-body .blp-profile-select').length > 0
      && $('#profiles-table-body .blp-profile-select:checked').length === $('#profiles-table-body .blp-profile-select').length;
    $('#profiles-select-all').prop('checked', allChecked);
  }

  function renderProfilesPagination() {
    const total = Math.max(0, parseInt(state.profilesPage.total, 10) || 0);
    const limit = Math.max(1, parseInt(state.profilesPage.limit, 10) || 20);
    const offset = Math.max(0, parseInt(state.profilesPage.offset, 10) || 0);

    const page = total ? Math.floor(offset / limit) + 1 : 1;
    const totalPages = total ? Math.ceil(total / limit) : 1;
    const start = total ? offset + 1 : 0;
    const end = total ? Math.min(total, offset + limit) : 0;

    $('#profiles-page-info').text(`Page ${page}/${totalPages} • Showing ${start}-${end} of ${total}`);
    $('#profiles-prev').prop('disabled', offset <= 0);
    $('#profiles-next').prop('disabled', offset + limit >= total);
  }

  function setSingleProfileStatus(profileId, isActive) {
    ajax('blp_set_profile_status', {
      profile_id: profileId,
      is_active: isActive ? 1 : 0,
    }, function(res) {
      if (res.active_profile_id && parseInt(res.active_profile_id, 10) > 0
        && parseInt(BLP.profileId, 10) === parseInt(profileId, 10)
        && parseInt(BLP.profileId, 10) !== parseInt(res.active_profile_id, 10)) {
        switchProfile(parseInt(res.active_profile_id, 10));
      }

      loadProfiles(function() {
        loadProfilesTable(false);
      });

      toast(isActive ? 'Profile activated.' : 'Profile deactivated.');
    });
  }

  function runBulkProfileStatus(isActive) {
    const ids = Array.from(state.selectedProfileIds || []);
    if (!ids.length) {
      toast('Select at least one profile first.', 'error');
      return;
    }

    ajax('blp_bulk_profile_status', {
      profile_ids: JSON.stringify(ids),
      is_active: isActive ? 1 : 0,
    }, function(res) {
      state.selectedProfileIds = new Set();
      $('#profiles-select-all').prop('checked', false);

      if (res.active_profile_id && parseInt(res.active_profile_id, 10) > 0
        && !ids.includes(parseInt(res.active_profile_id, 10))
        && ids.includes(parseInt(BLP.profileId, 10))) {
        switchProfile(parseInt(res.active_profile_id, 10));
      }

      loadProfiles(function() {
        loadProfilesTable(false);
      });

      const count = parseInt(res.updated_count || ids.length, 10) || ids.length;
      toast(`${count} profile(s) updated.`);
    });
  }

  function openProfileEditor(profileId) {
    const openSettingsTab = function() {
      $('.blp-tab[data-tab="settings"]').trigger('click');
      setTimeout(() => $('#profile-username').trigger('focus'), 80);
    };

    if (parseInt(BLP.profileId, 10) === parseInt(profileId, 10)) {
      openSettingsTab();
      toast('Profile ready to edit.');
      return;
    }

    switchProfile(profileId, function() {
      openSettingsTab();
    });
  }

  function duplicateProfile(profileId, options = {}) {
    const silent = !!options.silent;
    const onDone = typeof options.onDone === 'function' ? options.onDone : null;

    ajax('blp_duplicate_profile', {
      profile_id: profileId,
    }, function(res) {
      loadProfiles(function() {
        loadProfilesTable(false);

        if (!silent) {
          const copied = parseInt(res.links_copied || 0, 10) || 0;
          toast(`Profile duplicated (${copied} link copied).`);
        }

        if (onDone) onDone(true, res);
      });
    }, function() {
      if (onDone) onDone(false);
    });
  }

  function getProfileDeleteMessage(displayName, linksCount) {
    return [
      `Delete "${displayName}" profile?`,
      '',
      'This will permanently remove:',
      `- Profile settings`,
      `- ${linksCount} link(s)`,
      '- Related analytics/events',
      '',
      'This action cannot be undone.',
    ].join('\n');
  }

  function deleteProfileById(profileId, options = {}) {
    const displayName = String(options.displayName || 'Profile');
    const linksCount = parseInt(options.linksCount || 0, 10) || 0;
    const skipConfirm = !!options.skipConfirm;
    const silent = !!options.silent;
    const onDone = typeof options.onDone === 'function' ? options.onDone : null;

    if (!skipConfirm) {
      const message = getProfileDeleteMessage(displayName, linksCount);
      if (!confirm(message)) {
        if (onDone) onDone(false);
        return;
      }
    }

    ajax('blp_delete_profile', { profile_id: profileId }, function(res) {
      state.profiles = res.profiles || [];
      BLP.profiles = state.profiles;
      renderProfileSwitcher();

      const deletingCurrent = parseInt(BLP.profileId, 10) === parseInt(profileId, 10);
      if (deletingCurrent && state.profiles.length > 0) {
        const replacementId = parseInt(state.profiles[0].id, 10);
        switchProfile(replacementId, function() {
          if ($('#profiles-table-body').length) loadProfilesTable(true);
          if (!silent) toast('Profile deleted.');
          if (onDone) onDone(true);
        });
        return;
      }

      updateCurrentProfileName();
      if ($('#profiles-table-body').length) loadProfilesTable(true);
      if (!silent) toast('Profile deleted.');
      if (onDone) onDone(true);
    }, function() {
      if (onDone) onDone(false);
    });
  }

  function runBulkDuplicateProfiles() {
    const ids = Array.from(state.selectedProfileIds || []);
    if (!ids.length) {
      toast('Select at least one profile first.', 'error');
      return;
    }

    let completed = 0;
    const queue = ids.slice();

    const next = function() {
      if (!queue.length) {
        state.selectedProfileIds = new Set();
        $('#profiles-select-all').prop('checked', false);
        loadProfiles(function() {
          loadProfilesTable(true);
          toast(`${completed} profile(s) duplicated.`);
        });
        return;
      }

      const profileId = queue.shift();
      duplicateProfile(profileId, {
        silent: true,
        onDone: function(success) {
          if (success) completed++;
          next();
        }
      });
    };

    next();
  }

  function runBulkDeleteProfiles() {
    const ids = Array.from(state.selectedProfileIds || []);
    if (!ids.length) {
      toast('Select at least one profile first.', 'error');
      return;
    }

    const confirmation = [
      `Delete ${ids.length} selected profile(s) permanently?`,
      '',
      'This will permanently remove selected profiles, links, and related analytics.',
      'This action cannot be undone.',
    ].join('\n');
    if (!confirm(confirmation)) {
      return;
    }

    let completed = 0;
    const queue = ids.slice();

    const next = function() {
      if (!queue.length) {
        state.selectedProfileIds = new Set();
        $('#profiles-select-all').prop('checked', false);
        loadProfiles(function() {
          loadProfilesTable(true);
          toast(`${completed} profile(s) deleted.`);
        });
        return;
      }

      const profileId = queue.shift();
      const profileMeta = state.profiles.find(function(p) {
        return parseInt(p.id, 10) === parseInt(profileId, 10);
      }) || {};

      deleteProfileById(profileId, {
        displayName: profileMeta.display_name || profileMeta.username || `Profile #${profileId}`,
        linksCount: parseInt(profileMeta.links_count || 0, 10) || 0,
        skipConfirm: true,
        silent: true,
        onDone: function(success) {
          if (success) completed++;
          next();
        }
      });
    };

    next();
  }

  function formatDateTimeLabel(sqlDate) {
    if (!sqlDate) return '—';
    const normalized = String(sqlDate).replace(' ', 'T');
    const dt = new Date(normalized);
    if (Number.isNaN(dt.getTime())) return String(sqlDate);

    return dt.toLocaleString(undefined, {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  /* ══ PROFILE SWITCHER ═══════════════════════════════════════ */
  function initProfileSwitcher() {
    state.profiles = BLP.profiles || [];
    renderProfileSwitcher();

    // Toggle dropdown
    $('#profile-trigger').on('click', function(e) {
      e.stopPropagation();
      $('#profile-dropdown').toggle();
    });

    // Close on outside click
    $(document).on('click', function(e) {
      if (!$(e.target).closest('#profile-switcher').length) {
        $('#profile-dropdown').hide();
      }
    });

    // Switch profile
    $(document).on('click', '.blp-profile-item[data-id]', function(e) {
      if ($(e.target).closest('.blp-profile-item-delete').length) return;
      const id = parseInt($(this).data('id'), 10);
      if (id === parseInt(BLP.profileId, 10)) { $('#profile-dropdown').hide(); return; }
      switchProfile(id);
    });

    // Delete profile
    $(document).on('click', '.blp-profile-item-delete', function(e) {
      e.stopPropagation();
      const id = parseInt($(this).closest('.blp-profile-item').data('id'), 10);
      const profileMeta = state.profiles.find(function(p) {
        return parseInt(p.id, 10) === id;
      }) || {};

      deleteProfileById(id, {
        displayName: profileMeta.display_name || profileMeta.username || 'Profile',
        linksCount: parseInt(profileMeta.links_count || 0, 10) || 0,
        onDone: function(success) {
          if (!success) return;
          loadProfilesTable(true);
        }
      });
    });

    // New profile
    $('#btn-new-profile').on('click', function() {
      const username = prompt('Enter a username for the new profile (min 3 characters):');
      if (!username || username.trim().length < 3) {
        if (username !== null) toast('Username must be at least 3 characters.', 'error');
        return;
      }
      ajax('blp_create_profile', { username: username.trim().toLowerCase() }, function(res) {
        state.profiles.push({
          id: parseInt(res.profile_id, 10),
          username: res.profile.username,
          display_name: res.profile.display_name,
          avatar_url: '',
          is_active: 1,
          user_id: res.profile.user_id,
          publicUrl: res.publicUrl,
          publicFallbackUrl: res.publicFallbackUrl,
          previewUrl: res.previewUrl,
        });
        BLP.profiles = state.profiles;
        toast('Profile created!');
        renderProfileSwitcher();
        loadProfilesTable(true);
        switchProfile(parseInt(res.profile_id, 10));
      });
    });

    // Set initial profile name
    updateCurrentProfileName();
  }

  function loadProfiles(onDone) {
    ajax('blp_get_profiles', { limit: 500, offset: 0 }, function(res) {
      state.profiles = res.profiles || [];
      BLP.profiles = state.profiles;
      renderProfileSwitcher();
      updateCurrentProfileName();
      if (typeof onDone === 'function') onDone();
    }, function() {
      if (typeof onDone === 'function') onDone();
    });
  }

  function renderProfileSwitcher() {
    const $list = $('#profile-list');
    if (!state.profiles || state.profiles.length === 0) {
      $list.html('<div style="padding:12px;text-align:center;color:var(--blp-muted);font-size:12px">No profiles yet</div>');
      return;
    }
    let html = '';
    state.profiles.forEach(function(p) {
      const isActive = parseInt(p.id, 10) === parseInt(BLP.profileId, 10);
      const initial = (p.display_name || p.username || '?').charAt(0).toUpperCase();
      const avatarHtml = p.avatar_url
        ? '<img src="' + escHtml(p.avatar_url) + '" alt="">'
        : initial;
      html += '<button class="blp-profile-item' + (isActive ? ' active' : '') + '" data-id="' + p.id + '">'
        + '<div class="blp-profile-item-avatar">' + avatarHtml + '</div>'
        + '<div class="blp-profile-item-info">'
        + '<div class="blp-profile-item-name">' + escHtml(p.display_name || p.username) + '</div>'
        + '<div class="blp-profile-item-username">/' + escHtml(p.username) + '</div>'
        + '</div>'
        + (state.profiles.length > 1 ? '<div class="blp-profile-item-delete" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></div>' : '')
        + '</button>';
    });
    $list.html(html);
  }

  function switchProfile(profileId, onDone) {
    ajax('blp_switch_profile', { profile_id: profileId }, function(res) {
      // Update global state
      BLP.profileId = parseInt(profileId, 10);
      BLP.publicUrl = res.publicUrl || '';
      BLP.publicFallbackUrl = res.publicFallbackUrl || res.publicUrl || BLP.publicFallbackUrl || '';
      BLP.previewUrl = res.previewUrl || BLP.previewUrl || BLP.publicFallbackUrl || BLP.publicUrl;
      BLP.profile   = res.profile;
      state.profile = res.profile;
      state.links   = res.links || [];
      state.analytics = res.analytics || {};

      // Update design from loaded profile
      const savedDesign = res.design && typeof res.design === 'object' ? res.design : {};
      Object.assign(state.design, DESIGN_DEFAULTS, savedDesign);

      // Re-render everything
      applyDesignToUI(state.design);
      renderLinks();
      initSocialLinks();
      resetDesignAutosaveState();
      previewLoaded = false;
      previewSrc = '';
      previewNeedsHardReload = true;
      refreshPreview();

      // Update profile form fields
      if (res.profile) {
        $('#profile-username').val(res.profile.username || '');
        $('#profile-display-name').val(res.profile.display_name || '');
        $('#profile-bio').val(res.profile.bio || '');
        $('#avatar-url').val(res.profile.avatar_url || '');
        $('#profile-custom-domain').val(res.profile.custom_domain || '');
        showAvatarPreview(res.profile.avatar_url || '');
      }
      generateQR();

      // Update URL button
      if (res.publicUrl || res.publicFallbackUrl) {
        const viewUrl = res.publicFallbackUrl || res.publicUrl;
        const displayUrl = res.publicUrl || res.publicFallbackUrl;
        $('#blp-public-url').show();
        $('#blp-view-page').attr('href', viewUrl);
        $('#preview-url-text').text(displayUrl.replace(/^https?:\/\//, ''));
      }

      // Update profiles list active state
      const switchedId = parseInt(profileId, 10);
      const existingIdx = state.profiles.findIndex(function(p) {
        return parseInt(p.id, 10) === switchedId;
      });
      const switchedProfileEntry = {
        ...(existingIdx !== -1 ? state.profiles[existingIdx] : {}),
        ...(res.profile || {}),
        id: switchedId,
        publicUrl: res.publicUrl || '',
        publicFallbackUrl: res.publicFallbackUrl || res.publicUrl || '',
        previewUrl: res.previewUrl || '',
      };
      if (existingIdx !== -1) {
        state.profiles[existingIdx] = switchedProfileEntry;
      } else {
        state.profiles.push(switchedProfileEntry);
      }
      BLP.profiles = state.profiles;

      state.profiles.forEach(function(p) { if (parseInt(p.id, 10) === parseInt(profileId, 10)) p._active = true; else p._active = false; });
      renderProfileSwitcher();
      updateCurrentProfileName();
      if ($('#profiles-table-body').length) {
        loadProfilesTable(false);
      }
      $('#profile-dropdown').hide();

      toast('Switched to ' + (res.profile.display_name || res.profile.username));
      if (typeof onDone === 'function') {
        onDone(res);
      }
    });
  }

  function updateCurrentProfileName() {
    const p = state.profiles.find(function(pr) { return parseInt(pr.id, 10) === parseInt(BLP.profileId, 10); });
    $('#current-profile-name').text(p ? (p.display_name || p.username) : 'Profile');
  }

  /* ── QR Code ────────────────────────────────────────────── */
  function generateQR() {
    const qrTargetUrl = BLP.publicFallbackUrl || BLP.publicUrl;
    if (!qrTargetUrl) { $('#qr-code-card').hide(); return; }
    $('#qr-code-card').show();
    const $wrap = $('#qr-code-canvas');
    $wrap.empty();

    const primaryQrUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' +
      encodeURIComponent(qrTargetUrl) + '&choe=UTF-8';
    const fallbackQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' +
      encodeURIComponent(qrTargetUrl);

    const img = document.createElement('img');
    img.alt = 'QR Code';
    img.width = 160;
    img.height = 160;
    img.style.display = 'block';
    img.crossOrigin = 'anonymous';
    img.referrerPolicy = 'no-referrer';

    let attemptedFallback = false;
    img.onerror = function() {
      if (!attemptedFallback) {
        attemptedFallback = true;
        img.src = fallbackQrUrl;
        return;
      }

      $wrap.html('<p class="blp-help" style="margin:0">QR code could not be loaded. Check network/CSP settings.</p>');
    };

    img.src = primaryQrUrl;
    $wrap[0].appendChild(img);
  }

  $('#btn-download-qr').on('click', function() {
    const img = $('#qr-code-canvas img')[0];
    if (!img) return;
    // Draw to canvas for download
    const canvas = document.createElement('canvas');
    canvas.width = 400;
    canvas.height = 400;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, 400, 400);

    try {
      ctx.drawImage(img, 20, 20, 360, 360);
      const a = document.createElement('a');
      a.href = canvas.toDataURL('image/png');
      a.download = 'biolink-qr-' + (state.profile?.username || 'code') + '.png';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      toast('QR code downloaded!');
    } catch (err) {
      window.open(img.src, '_blank', 'noopener');
      toast('Direct QR image opened in new tab.', 'error');
    }
  });

  /* ── Export / Import ────────────────────────────────────── */
  $('#btn-export-profile').on('click', function() {
    ajax('blp_export_profile', { profile_id: BLP.profileId }, function(res) {
      const blob = new Blob([JSON.stringify(res.export, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'biolink-' + (res.export.profile.username || 'profile') + '.json';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      toast('Profile exported!');
    });
  });

  $('#btn-import-profile').on('click', function() {
    $('#import-file-input').trigger('click');
  });

  $('#import-file-input').on('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
      try {
        JSON.parse(ev.target.result);
      } catch (err) {
        toast('Invalid JSON file', 'error');
        return;
      }
      ajax('blp_import_profile', { import_data: ev.target.result }, function(res) {
        toast(res.message || 'Imported!');
        // Reload profiles and switch
        if (res.profile_id) {
          loadProfiles(function() {
            loadProfilesTable(true);
            switchProfile(res.profile_id);
          });
        }
      });
    };
    reader.readAsText(file);
    $(this).val('');
  });

  /* ── Helpers ────────────────────────────────────────────── */
  function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function formatNum(n) {
    const parsed = parseInt(n, 10);
    if (Number.isNaN(parsed)) return '0';
    return parsed.toLocaleString();
  }
  function truncate(str, len) {
    return str && str.length > len ? str.slice(0, len) + '…' : (str || '');
  }

})(jQuery);
