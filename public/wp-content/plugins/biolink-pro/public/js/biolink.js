/* BioLink Pro – Public Page JS
   Handles click tracking via the admin-ajax endpoint.
   The inline template.php script handles particle animation.
*/
(function () {
  'use strict';

  /* Click tracking is handled inline in template.php via blpTrackClick().
     This file exists to satisfy the wp_enqueue_script() call and can be
     used for future public-side enhancements. */

  // Add keyboard accessibility to link cards (Enter key = click)
  document.addEventListener('DOMContentLoaded', function () {
    var cards = document.querySelectorAll('.blp-link-card');
    cards.forEach(function (card) {
      card.setAttribute('role', 'button');
      card.setAttribute('tabindex', '0');
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          card.click();
        }
      });
    });
  });

  /* ── Preview Mode: Live Design Updates via postMessage ──── */
  window.addEventListener('message', function (e) {
    if (e.origin !== window.location.origin) return;
    if (!e.data || e.data.type !== 'blp_preview_update') return;
    var d = e.data.design;
    if (!d) return;

    var root = document.documentElement;
    var page = document.getElementById('blp-page');

    // Update CSS variables
    if (d.accent_color) root.style.setProperty('--blp-accent', d.accent_color);
    if (d.text_color)   root.style.setProperty('--blp-text', d.text_color);
    if (d.card_color) {
      var opacity = Math.max(0.2, Math.min(1.0, (parseInt(d.card_opacity) || 90) / 100));
      var hex = d.card_color.replace('#', '');
      if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
      var r = parseInt(hex.substring(0,2), 16);
      var g = parseInt(hex.substring(2,4), 16);
      var b = parseInt(hex.substring(4,6), 16);
      root.style.setProperty('--blp-card', 'rgba('+r+','+g+','+b+','+opacity+')');
    }

    // Update background
    if (page && d.bg_type) {
      if (d.bg_type === 'gradient' || d.bg_type === 'animated') {
        var angle = parseInt(d.bg_gradient_angle) || 135;
        page.style.background = 'linear-gradient(' + angle + 'deg, ' + (d.bg_gradient_start || '#0a0a1a') + ', ' + (d.bg_gradient_end || '#1a0a3a') + ')';
      } else {
        page.style.background = d.bg_color || '#0a0a1a';
      }
    }

    // Update font family
    if (d.font_family) {
      root.style.setProperty('--blp-font', "'" + d.font_family + "', system-ui, sans-serif");
    }

    // Update button border-radius
    var btnMap = { rounded:'12px', pill:'9999px', square:'6px', outline:'12px', glass:'12px', solid:'12px' };
    if (d.button_style && btnMap[d.button_style]) {
      root.style.setProperty('--blp-btn-radius', btnMap[d.button_style]);
    }

    // Update display name and bio if available
    if (d._display_name) {
      var nameEl = document.querySelector('.blp-name');
      if (nameEl) nameEl.textContent = d._display_name;
    }
  });

})();
