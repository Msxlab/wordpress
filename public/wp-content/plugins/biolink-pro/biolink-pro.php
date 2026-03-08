<?php
/**
 * Plugin Name: BioLink Pro
 * Plugin URI:  https://yoursite.com/biolink-pro
 * Description: Create stunning Linktree-style bio pages with advanced themes, live design editor & analytics.
 * Version:     1.0.4
 * Author:      Perfnext
 * Author URI:  https://perfnext.com
 * Developers:  Omer Faruk Karasu - Mustafa YILDIZ
 * License:     GPL v2 or later
 * Text Domain: biolink-pro
 */

defined('ABSPATH') || exit;

// Constants
define('BLP_VERSION',     '1.0.4');
define('BLP_PLUGIN_DIR',  plugin_dir_path(__FILE__));
define('BLP_PLUGIN_URL',  plugin_dir_url(__FILE__));
define('BLP_PLUGIN_FILE', __FILE__);

// Load classes
require_once BLP_PLUGIN_DIR . 'includes/class-blp-database.php';
require_once BLP_PLUGIN_DIR . 'includes/class-blp-security.php';
require_once BLP_PLUGIN_DIR . 'includes/class-blp-license.php';
require_once BLP_PLUGIN_DIR . 'includes/class-blp-admin.php';
require_once BLP_PLUGIN_DIR . 'includes/class-blp-frontend.php';
require_once BLP_PLUGIN_DIR . 'includes/class-blp-ajax.php';

function blp_register_rewrite_rules() {
    add_rewrite_rule('^b/([^/]+)/?$', 'index.php?blp_username=$1', 'top');
    add_rewrite_tag('%blp_username%', '([^/]+)');
}

// Activation: install DB + register rewrites + flush + schedule cron + capabilities
register_activation_hook(__FILE__, function () {
    BLP_Database::install();
    BLP_Security::install();
    BLP_License::install();
    blp_register_rewrite_rules();
    flush_rewrite_rules();
    update_option('blp_rewrite_version', BLP_VERSION);

    // Schedule daily analytics cleanup cron
    if (!wp_next_scheduled('blp_cleanup_old_analytics')) {
        wp_schedule_event(time(), 'daily', 'blp_cleanup_old_analytics');
    }

    // Default role model: admin + editor. Extendable via filter for controlled opt-in.
    $default_roles = ['administrator', 'editor'];
    $roles_with_cap = apply_filters('blp_manage_biolink_roles', $default_roles);
    if (!is_array($roles_with_cap)) {
        $roles_with_cap = $default_roles;
    }

    $roles_with_cap = array_values(array_unique(array_filter(array_map('sanitize_key', $roles_with_cap))));

    foreach ($roles_with_cap as $role_slug) {
        $role = get_role($role_slug);
        if ($role) {
            $role->add_cap('manage_biolink');
        }
    }
});

register_deactivation_hook(__FILE__, function () {
    BLP_Database::deactivate();
    BLP_Security::deactivate();
    // Clear scheduled cron on deactivation
    wp_clear_scheduled_hook('blp_cleanup_old_analytics');
});

function blp_uninstall_plugin() {
    BLP_Database::uninstall();
    BLP_Security::uninstall();
    BLP_License::uninstall();
}

register_uninstall_hook(__FILE__, 'blp_uninstall_plugin');

// Boot the plugin
add_action('plugins_loaded', function () {
    BLP_Database::maybe_upgrade();
    BLP_Security::init_hooks();
    BLP_License::init_hooks();
    new BLP_Admin();
    new BLP_Frontend();
    new BLP_Ajax();
});

// Analytics data retention: delete records older than 180 days
add_action('blp_cleanup_old_analytics', function () {
    BLP_Database::cleanup_old_analytics(180);
});

// Register rewrite rules: /b/{username}
add_action('init', function () {
    blp_register_rewrite_rules();

    // One-time rewrite refresh after plugin update/migration to avoid stale 404s.
    if (get_option('blp_rewrite_version', '') !== BLP_VERSION) {
        flush_rewrite_rules(false);
        update_option('blp_rewrite_version', BLP_VERSION);
    }
});

// ── PWA Service Worker endpoint ──
add_action('template_redirect', function () {
    if (!isset($_GET['blp_sw'])) return;

    header('Content-Type: application/javascript; charset=utf-8');
    header('Service-Worker-Allowed: /');
    echo <<<'JS'
const CACHE_NAME = 'blp-v1';
const OFFLINE_URL = '/';

self.addEventListener('install', event => {
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(clients.claim());
});

self.addEventListener('fetch', event => {
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }
  event.respondWith(
    caches.match(event.request).then(cached => {
      return cached || fetch(event.request).then(response => {
        if (response.status === 200 && event.request.url.match(/\.(css|js|woff2?|ttf|png|jpg|jpeg|svg|webp)$/)) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      });
    })
  );
});
JS;
    exit;
});

// ── PWA Manifest endpoint ──
add_action('template_redirect', function () {
    if (!isset($_GET['blp_manifest'])) return;
    $profile_id = (int) $_GET['blp_manifest'];
    $profile = BLP_Database::get_profile_by_id($profile_id);
    if (!$profile) { wp_die('Not found', '', 404); }

    $name = !empty($profile->display_name) ? $profile->display_name : $profile->username;
    $theme = json_decode($profile->theme_settings ?? '{}', true) ?: [];
    $accent = $theme['accent_color'] ?? '#7c6df0';
    $bg     = $theme['bg_color'] ?? '#0a0a1a';
    $icon   = !empty($profile->avatar_url) ? $profile->avatar_url : '';

    $manifest = [
        'name'             => $name . ' | BioLink',
        'short_name'       => $name,
        'start_url'        => blp_profile_url($profile->username),
        'display'          => 'standalone',
        'background_color' => $bg,
        'theme_color'      => $accent,
        'description'      => !empty($profile->bio) ? wp_trim_words($profile->bio, 20) : $name . '\'s links',
    ];

    if ($icon) {
        $manifest['icons'] = [
            ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => $icon, 'sizes' => '512x512', 'type' => 'image/png'],
        ];
    }

    header('Content-Type: application/manifest+json; charset=utf-8');
    echo wp_json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
});

// Helper: get current user's profile
function blp_get_current_profile() {
    return BLP_Database::get_profile_by_user(get_current_user_id());
}

// ── Shortcode: [biolink username="xxx"] ──
add_shortcode('biolink', function ($atts) {
    if (class_exists('BLP_Security') && BLP_Security::is_lockdown()) {
        return '<p style="text-align:center;opacity:0.7">' . esc_html(BLP_Security::get_lockdown_message()) . '</p>';
    }

    $atts = shortcode_atts(['username' => '', 'id' => ''], $atts, 'biolink');

    $profile = null;
    if (!empty($atts['username'])) {
        $profile = BLP_Database::get_profile_by_username(sanitize_user($atts['username']));
    } elseif (!empty($atts['id'])) {
        $profile = BLP_Database::get_profile_by_id((int) $atts['id']);
    }

    if (!$profile || !$profile->is_active) {
        return '<p style="text-align:center;opacity:0.5">BioLink profile not found.</p>';
    }

    $links = BLP_Database::get_links($profile->id, true);
    $display_name = !empty($profile->display_name) ? esc_html($profile->display_name) : esc_html($profile->username);
    $theme = json_decode($profile->theme_settings ?? '{}', true) ?: [];
    $accent = $theme['accent_color'] ?? '#7c6df0';

    ob_start();
    ?>
    <div class="blp-shortcode-embed" style="max-width:480px;margin:0 auto;font-family:system-ui,sans-serif">
      <div style="text-align:center;margin-bottom:16px">
        <?php if (!empty($profile->avatar_url)): ?>
        <img src="<?php echo esc_url($profile->avatar_url); ?>" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin-bottom:8px" loading="lazy">
        <?php endif; ?>
        <h3 style="margin:0;font-size:18px"><?php echo $display_name; ?></h3>
        <?php if (!empty($profile->bio)): ?>
        <p style="margin:4px 0 0;font-size:13px;opacity:0.7"><?php echo esc_html(wp_trim_words($profile->bio, 20)); ?></p>
        <?php endif; ?>
      </div>
      <?php foreach ($links as $link):
        if ($link->link_type !== 'link' || empty($link->url)) continue;
      ?>
      <a href="<?php echo esc_url($link->url); ?>" target="_blank" rel="noopener noreferrer"
         style="display:block;padding:12px 16px;margin-bottom:8px;border-radius:10px;background:<?php echo esc_attr($accent); ?>;color:#fff;text-decoration:none;text-align:center;font-weight:500;font-size:14px;transition:opacity 0.2s"
         onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
        <?php echo esc_html($link->title); ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
});

// ── Gutenberg Block Registration ──
add_action('init', function () {
    if (!function_exists('register_block_type')) return;

    wp_register_script('blp-block-editor', BLP_PLUGIN_URL . 'admin/js/block-editor.js', [
        'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render'
    ], BLP_VERSION, true);

    register_block_type('biolink-pro/profile', [
        'editor_script'   => 'blp-block-editor',
        'render_callback' => function ($attributes) {
            $username = sanitize_user($attributes['username'] ?? '');
            if (!$username) return '<p style="text-align:center;opacity:0.5">Select a BioLink profile.</p>';
            return do_shortcode('[biolink username="' . esc_attr($username) . '"]');
        },
        'attributes' => [
            'username' => ['type' => 'string', 'default' => ''],
        ],
    ]);
});

// Helper: build public URL for a username
function blp_profile_url($username) {
    return home_url('/b/' . urlencode($username));
}

// Helper: guaranteed public URL using query-var route (rewrite-independent)
function blp_profile_fallback_url($username) {
    return add_query_arg('blp_username', (string) $username, home_url('/'));
}

// Helper: build rewrite-independent preview URL for a username
function blp_profile_preview_url($username) {
    return blp_profile_fallback_url($username);
}
