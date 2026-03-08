<?php
defined('ABSPATH') || exit;

class BLP_Ajax {

    public function __construct() {
        // Auth-required actions
        $admin_actions = [
            'blp_save_profile',
            'blp_save_design',
            'blp_add_link',
            'blp_update_link',
            'blp_delete_link',
            'blp_toggle_link',
            'blp_reorder_links',
            'blp_get_analytics',
            'blp_export_analytics_csv',
            'blp_get_profiles',
            'blp_switch_profile',
            'blp_create_profile',
            'blp_duplicate_profile',
            'blp_delete_profile',
            'blp_set_profile_status',
            'blp_bulk_profile_status',
            'blp_export_profile',
            'blp_import_profile',
            'blp_upload_font',
            'blp_import_links_csv',
            'blp_get_security_status',
            'blp_save_security_settings',
            'blp_set_security_mode',
            'blp_run_integrity_scan',
            'blp_pull_remote_policy',
            'blp_send_security_test_alert',
            'blp_get_license_status',
            'blp_save_license_settings',
            'blp_license_save_key',
            'blp_license_activate',
            'blp_license_validate',
            'blp_license_deactivate',
        ];
        foreach ($admin_actions as $action) {
            add_action("wp_ajax_{$action}", [$this, $action]);
        }

        // Public (no-auth) actions
        add_action('wp_ajax_nopriv_blp_track_click', [$this, 'blp_track_click']);
        add_action('wp_ajax_blp_track_click',        [$this, 'blp_track_click']);
    }

    /* ── Helpers ─────────────────────────────── */

    private function verify_nonce($action = 'blp_nonce') {
        if (!check_ajax_referer($action, 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
            exit;
        }
    }

    private function require_admin_capability() {
        if (!current_user_can('manage_biolink')) {
            $this->error('Permission denied.', 403);
        }

        $action = sanitize_key((string) ($_REQUEST['action'] ?? ''));
        if (class_exists('BLP_Security') && !BLP_Security::is_action_allowed($action)) {
            $message = BLP_Security::get_mode_block_message($action);
            $this->error($message !== '' ? $message : 'Action blocked by security policy.', 423);
        }
    }

    private function require_site_admin() {
        if (!current_user_can('manage_options')) {
            $this->error('Only administrators can perform this action.', 403);
        }
    }

    /**
     * Check whether current user can access a profile.
     * Admins (manage_options) can access all profiles.
     * Users with manage_biolink can only access their own profiles.
     */
    private function can_access_profile($profile) {
        if (!$profile) return false;
        if (current_user_can('manage_options')) return true;
        return (int) $profile->user_id === get_current_user_id();
    }

    /**
     * Resolve profile context from request/profile switcher state.
     */
    private function get_profile_id($required = true) {
        $requested_profile_id = (int) ($_POST['profile_id'] ?? 0);

        if ($requested_profile_id > 0) {
            $requested = BLP_Database::get_profile_by_id($requested_profile_id);
            if (!$this->can_access_profile($requested)) {
                $this->error('Access denied.', 403);
            }
            return (int) $requested->id;
        }

        $active_profile_id = (int) get_user_meta(get_current_user_id(), 'blp_active_profile_id', true);
        if ($active_profile_id > 0) {
            $active = BLP_Database::get_profile_by_id($active_profile_id);
            if ($this->can_access_profile($active)) {
                return (int) $active->id;
            }
        }

        $fallback = BLP_Database::get_profile_by_user(get_current_user_id());
        if ($fallback && $this->can_access_profile($fallback)) {
            return (int) $fallback->id;
        }

        if (current_user_can('manage_options')) {
            $all_profiles = BLP_Database::get_all_profiles(1, 0);
            if (!empty($all_profiles)) {
                return (int) $all_profiles[0]->id;
            }
        }

        if ($required) {
            $this->error('Profile not found.', 404);
        }

        return 0;
    }

    /**
     * Verify that a link belongs to an accessible profile.
     * Sends 403 and exits if ownership check fails.
     */
    private function verify_link_ownership($link_id, $expected_profile_id = 0) {
        $link = BLP_Database::get_link((int) $link_id);
        if (!$link) {
            wp_send_json_error(['message' => 'Link not found.'], 404);
            exit;
        }

        $link_profile_id = (int) $link->profile_id;

        if ($expected_profile_id > 0 && $link_profile_id !== (int) $expected_profile_id) {
            wp_send_json_error(['message' => 'Access denied.'], 403);
            exit;
        }

        $profile = BLP_Database::get_profile_by_id($link_profile_id);
        if (!$this->can_access_profile($profile)) {
            wp_send_json_error(['message' => 'Access denied.'], 403);
            exit;
        }

        return $link;
    }

    /**
     * Sanitize and validate a URL.
     * Only http:// and https:// schemes are allowed.
     * Returns empty string if invalid.
     */
    private function sanitize_safe_url($raw_url) {
        $url = esc_url_raw(trim($raw_url));
        if (empty($url)) return '';
        $scheme = wp_parse_url($url, PHP_URL_SCHEME);
        if (!in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return '';
        }
        return $url;
    }

    /**
     * Parse a forwarded IP header and return the best candidate.
     */
    private function extract_forwarded_ip($header_value) {
        if (!is_string($header_value) || $header_value === '') {
            return '';
        }

        $parts = array_map('trim', explode(',', $header_value));
        $valid = [];

        foreach ($parts as $part) {
            if (filter_var($part, FILTER_VALIDATE_IP)) {
                $valid[] = $part;
            }
        }

        if (empty($valid)) {
            return '';
        }

        // Prefer first public IP, fallback to first valid IP.
        foreach ($valid as $ip) {
            if (!$this->is_private_or_reserved_ip($ip)) {
                return $ip;
            }
        }

        return $valid[0];
    }

    /**
     * Returns true for private/reserved/loopback ranges.
     */
    private function is_private_or_reserved_ip($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Resolve client IP with trusted proxy header strategy.
     *
     * - Always trusts REMOTE_ADDR.
     * - Trusts CF-Connecting-IP when Cloudflare context exists.
     * - Trusts X-Forwarded-For / X-Real-IP only behind trusted proxy IPs.
     */
    private function get_client_ip() {
        $remote_addr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $remote_ip   = filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : '';

        $has_cloudflare_context = !empty($_SERVER['HTTP_CF_CONNECTING_IP'])
            && (
                isset($_SERVER['HTTP_CF_RAY'])
                || isset($_SERVER['HTTP_CF_VISITOR'])
                || isset($_SERVER['HTTP_CF_IPCOUNTRY'])
            );

        if ($has_cloudflare_context) {
            $cf_ip = $this->extract_forwarded_ip((string) $_SERVER['HTTP_CF_CONNECTING_IP']);
            if ($cf_ip !== '') {
                return $cf_ip;
            }
        }

        $trust_forwarded_headers = ($remote_ip !== '' && $this->is_private_or_reserved_ip($remote_ip));
        $trust_forwarded_headers = (bool) apply_filters('blp_trust_forwarded_ip_headers', $trust_forwarded_headers, $remote_ip);

        if ($trust_forwarded_headers) {
            $xff_ip = $this->extract_forwarded_ip((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
            if ($xff_ip !== '') {
                return $xff_ip;
            }

            $xreal_ip = $this->extract_forwarded_ip((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
            if ($xreal_ip !== '') {
                return $xreal_ip;
            }
        }

        return $remote_ip !== '' ? $remote_ip : '0.0.0.0';
    }

    /**
     * Sanitize and validate a custom domain (domain/subdomain only).
     * Disallows scheme, path and port.
     */
    private function sanitize_custom_domain($raw_domain) {
        $domain = strtolower(trim((string) $raw_domain));
        if ($domain === '') return '';

        // Strict mode: reject anything that is not plain domain/subdomain text.
        if (preg_match('#^[a-z][a-z0-9+\-.]*://#i', $domain)) return '';
        if (strpos($domain, '/') !== false) return '';
        if (strpos($domain, ':') !== false) return '';
        if (strpos($domain, '?') !== false) return '';
        if (strpos($domain, '#') !== false) return '';
        if (preg_match('/\s/', $domain)) return '';

        if (!preg_match('/^(?=.{1,253}$)(?!-)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $domain)) {
            return '';
        }

        return $domain;
    }

    /**
     * Sanitize a schedule datetime string.
     * Returns null if empty/invalid, otherwise a Y-m-d H:i:s string.
     */
    private function sanitize_schedule_date($raw) {
        $raw = sanitize_text_field(trim($raw));
        if (empty($raw)) return null;

        // Format validation before parsing
        if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?$/', $raw)) {
            return null;
        }

        try {
            $dt_local = new \DateTimeImmutable($raw, wp_timezone());
        } catch (\Exception $e) {
            return null;
        }

        return $dt_local
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * Accept an imported UTC datetime as-is, otherwise normalize from local datetime input.
     */
    private function normalize_import_schedule_date($raw) {
        $value = sanitize_text_field(trim((string) $raw));
        if ($value === '') return null;

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return $this->sanitize_schedule_date($value);
    }

    /**
     * Shared sanitize pipeline for theme settings (used by save + import).
     */
    private function sanitize_theme_settings($settings) {
        if (!is_array($settings)) {
            return [];
        }

        $allowed_keys = [
            'theme', 'bg_type', 'bg_color', 'bg_gradient_start', 'bg_gradient_end',
            'bg_gradient_angle', 'bg_gradient_mid', 'accent_color', 'text_color', 'card_color',
            'card_opacity', 'card_blur', 'font_family', 'button_style', 'button_effect',
            'avatar_shape', 'show_social_icons', 'layout_variant',
            'custom_css', 'enable_particles', 'enable_gradient_anim',
            'bg_effect', 'effect_intensity',
            'bg_image_url', 'bg_image_overlay', 'bg_image_blur',
            'bg_video_url', 'music_url',
            'enable_darklight', 'light_bg_color', 'light_text_color', 'light_card_color',
            'banner_url', 'banner_height',
            'countdown_date', 'countdown_label', 'status_text', 'status_emoji',
            'og_image_url', 'meta_description',
        ];

        $boolean_keys = ['enable_particles', 'enable_gradient_anim', 'show_social_icons', 'enable_darklight'];
        $int_keys = [
            'card_opacity'      => [20, 100],
            'card_blur'         => [0, 30],
            'bg_gradient_angle' => [0, 360],
            'effect_intensity'  => [0, 100],
            'bg_image_overlay'  => [0, 100],
            'bg_image_blur'     => [0, 20],
            'banner_height'     => [100, 400],
        ];

        $allowed_layouts = [
            'center_classic',
            'profile_left_info_right',
            'profile_right_info_left',
            'business_card_horizontal',
            'magazine_split',
            'minimal_stack',
            'executive_clean',
            'studio_bento',
            'bold_banner_top',
            'avatar_floating_sidebar',
            'compact_contact_card',
            'spotlight_onepage',
            'hero_split_pro',
            'stacked_cards_modern',
            'cover_profile_panel',
            'creator_spotlight',
            'minimal_directory',
            'agency_brief',
            'timeline_story',
            'split_hero_cards',
            'mosaic_showcase',
            'executive_sidebar_pro',
            'press_kit_split',
            'minimal_premium_stack',
        ];

        $allowed_bg_effects = ['none', 'mesh', 'spotlight', 'grain', 'orbs', 'aurora', 'waves', 'constellation'];

        $allowed_fonts = [
            'DM Sans', 'Space Grotesk', 'Playfair Display', 'Inter', 'Poppins',
            'Raleway', 'Josefin Sans', 'Bebas Neue', 'Outfit', 'Syne',
            'Plus Jakarta Sans', 'Manrope', 'Montserrat', 'Lora', 'Nunito Sans',
            'Merriweather', 'Oswald', 'Archivo', 'Source Sans 3', 'Rubik',
        ];

        $clean = [];
        foreach ($allowed_keys as $key) {
            if (!isset($settings[$key])) continue;

            if (in_array($key, $boolean_keys, true)) {
                $clean[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            } elseif (isset($int_keys[$key])) {
                $val = (int) $settings[$key];
                $clean[$key] = max($int_keys[$key][0], min($int_keys[$key][1], $val));
            } elseif ($key === 'layout_variant') {
                $layout = sanitize_key((string) $settings[$key]);
                $clean[$key] = in_array($layout, $allowed_layouts, true) ? $layout : 'center_classic';
            } elseif ($key === 'bg_effect') {
                $effect = sanitize_key((string) $settings[$key]);
                $clean[$key] = in_array($effect, $allowed_bg_effects, true) ? $effect : 'none';
            } elseif ($key === 'font_family') {
                $font = sanitize_text_field((string) $settings[$key]);
                if (strpos($font, 'custom:') === 0 || in_array($font, $allowed_fonts, true)) {
                    $clean[$key] = $font;
                } else {
                    $clean[$key] = 'DM Sans';
                }
            } elseif (in_array($key, ['bg_image_url', 'banner_url', 'bg_video_url', 'music_url', 'og_image_url'], true)) {
                $clean[$key] = esc_url_raw($settings[$key]);
            } elseif ($key === 'countdown_date') {
                $cd = sanitize_text_field($settings[$key]);
                $clean[$key] = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $cd) ? $cd : '';
            } elseif ($key === 'status_emoji') {
                $clean[$key] = mb_substr(sanitize_text_field($settings[$key]), 0, 8);
            } else {
                $clean[$key] = sanitize_text_field($settings[$key]);
            }
        }

        if (isset($settings['social_links']) && is_array($settings['social_links'])) {
            $clean_social = [];
            foreach ($settings['social_links'] as $platform => $url) {
                $safe_platform = sanitize_key($platform);
                $safe_url      = $this->sanitize_safe_url($url);
                if ($safe_platform && $safe_url) {
                    $clean_social[$safe_platform] = $safe_url;
                }
            }
            $clean['social_links'] = $clean_social;
        }

        if (isset($settings['custom_css'])) {
            $css = wp_strip_all_tags($settings['custom_css']);
            $css = str_replace("\0", '', $css);
            $css = preg_replace('/\/\*.*?\*\//s', '', $css);
            $css = preg_replace('/expression\s*\(/i', '/* blocked */(', $css);
            $css = preg_replace('/behavior\s*:/i', '/* blocked */:', $css);
            $css = preg_replace('/@import\b/i', '/* blocked-import */', $css);
            $css = preg_replace('/javascript\s*:/i', '/* blocked */:', $css);
            $css = preg_replace('/vbscript\s*:/i', '/* blocked */:', $css);
            $css = preg_replace('/-moz-binding\s*:/i', '/* blocked */:', $css);
            $css = preg_replace('/url\s*\(\s*["\']?\s*data\s*:/i', '/* blocked-data-uri */ url(about:', $css);
            $css = preg_replace('/position\s*:\s*fixed/i', 'position: /* blocked-fixed */ relative', $css);
            $css = preg_replace('/z-index\s*:\s*(\d{5,})/i', 'z-index: /* blocked */ 999', $css);
            $css = preg_replace('/\\\\[0-9a-fA-F]{1,6}\s?/', '', $css);
            $css = mb_substr($css, 0, 10000);
            $clean['custom_css'] = $css;
        }

        return $clean;
    }

    private function sanitize_utm_value($raw, $max_len = 120) {
        $clean = sanitize_text_field((string) $raw);
        if ($clean === '') return '';
        return mb_substr($clean, 0, max(1, (int) $max_len));
    }

    private function escape_csv_field($value) {
        $value = (string) $value;

        // Prevent formula execution when CSV is opened in spreadsheet apps.
        if (preg_match('/^[\t\r\n ]*[=+\-@]/', $value)) {
            $value = "'" . $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * IP-based rate limiting using transients.
     * Returns true if the request should be blocked.
     */
    private function is_rate_limited($action, $limit = 15, $window = 60) {
        $limit  = max(1, (int) $limit);
        $window = max(1, (int) $window);

        $action_key = sanitize_key((string) $action);
        $ip_hash = hash('sha256', $this->get_client_ip() . wp_salt('auth'));
        $rate_key = 'blp_rl_' . $action_key . '_' . $ip_hash;

        $count = (int) get_transient($rate_key);
        if ($count >= $limit) {
            return true;
        }

        set_transient($rate_key, $count + 1, $window);
        return false;
    }

    private function success($data = []) {
        wp_send_json_success($data);
    }

    private function error($msg, $code = 400) {
        wp_send_json_error(['message' => $msg], $code);
        exit;
    }

    private function parse_settings_payload($raw_payload) {
        if (is_array($raw_payload)) {
            return $raw_payload;
        }

        if (!is_string($raw_payload) || $raw_payload === '') {
            return [];
        }

        $decoded = json_decode(wp_unslash($raw_payload), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function sanitize_security_snapshot_for_view($snapshot) {
        if (!is_array($snapshot)) {
            return [];
        }

        if (current_user_can('manage_options')) {
            return $snapshot;
        }

        if (isset($snapshot['settings']) && is_array($snapshot['settings'])) {
            $snapshot['settings']['alertEmail'] = '';
            $snapshot['settings']['webhookUrl'] = '';
            $snapshot['settings']['policyUrl'] = '';
            $snapshot['settings']['policyPublicKey'] = '';
        }

        if (isset($snapshot['events']) && is_array($snapshot['events'])) {
            foreach ($snapshot['events'] as &$event) {
                if (is_array($event) && isset($event['context'])) {
                    $event['context'] = [];
                }
            }
            unset($event);
        }

        return $snapshot;
    }

    private function sanitize_license_snapshot_for_view($snapshot) {
        if (!is_array($snapshot)) {
            return [];
        }

        if (current_user_can('manage_options')) {
            return $snapshot;
        }

        if (isset($snapshot['settings']) && is_array($snapshot['settings'])) {
            $snapshot['settings']['apiBaseUrl'] = '';
        }

        $snapshot['instanceId'] = '';
        $snapshot['lastError'] = '';

        return $snapshot;
    }

    private function format_profiles($profiles) {
        $result = [];
        foreach ($profiles as $p) {
            $result[] = [
                'id'           => (int) $p->id,
                'username'     => $p->username,
                'display_name' => $p->display_name,
                'avatar_url'   => $p->avatar_url,
                'is_active'    => (int) $p->is_active,
                'user_id'      => (int) $p->user_id,
                'links_count'  => isset($p->links_count) ? (int) $p->links_count : 0,
                'updated_at'   => $p->updated_at ?? '',
                'publicUrl'    => blp_profile_url($p->username),
                'publicFallbackUrl' => blp_profile_fallback_url($p->username),
                'previewUrl'   => blp_profile_preview_url($p->username),
            ];
        }

        return $result;
    }

    /* ── Actions ─────────────────────────────── */

    public function blp_get_security_status() {
        $this->verify_nonce();
        $this->require_admin_capability();

        if (!class_exists('BLP_Security')) {
            $this->error('Security module unavailable.', 500);
        }

        $snapshot = BLP_Security::get_status_snapshot();
        $this->success(['security' => $this->sanitize_security_snapshot_for_view($snapshot)]);
    }

    public function blp_save_security_settings() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_Security')) {
            $this->error('Security module unavailable.', 500);
        }

        $settings = $this->parse_settings_payload($_POST['settings'] ?? []);
        $snapshot = BLP_Security::update_settings($settings);

        $this->success(['security' => $snapshot]);
    }

    public function blp_set_security_mode() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_Security')) {
            $this->error('Security module unavailable.', 500);
        }

        $mode = sanitize_key((string) ($_POST['mode'] ?? 'normal'));
        $snapshot = BLP_Security::set_mode($mode, 'Manual mode update from admin panel.', 'admin_ui');
        if (is_wp_error($snapshot)) {
            $this->error($snapshot->get_error_message(), 400);
        }

        $this->success(['security' => $snapshot]);
    }

    public function blp_run_integrity_scan() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_Security')) {
            $this->error('Security module unavailable.', 500);
        }

        $scan = BLP_Security::run_integrity_scan('manual_admin');
        if (is_wp_error($scan)) {
            $this->error($scan->get_error_message(), 500);
        }

        $this->success([
            'scan' => $scan,
            'security' => BLP_Security::get_status_snapshot(),
        ]);
    }

    public function blp_pull_remote_policy() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_Security')) {
            $this->error('Security module unavailable.', 500);
        }

        $policy = BLP_Security::pull_remote_policy(true);
        if (is_wp_error($policy)) {
            $this->error($policy->get_error_message(), 400);
        }

        $this->success([
            'policy' => $policy,
            'security' => BLP_Security::get_status_snapshot(),
        ]);
    }

    public function blp_send_security_test_alert() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_Security')) {
            $this->error('Security module unavailable.', 500);
        }

        BLP_Security::record_event(
            'high',
            'manual_test_alert',
            'Manual security alert test triggered from the admin panel.',
            ['user_id' => get_current_user_id()],
            true
        );

        $this->success(['security' => BLP_Security::get_status_snapshot()]);
    }

    public function blp_get_license_status() {
        $this->verify_nonce();
        $this->require_admin_capability();

        if (!class_exists('BLP_License')) {
            $this->error('License module unavailable.', 500);
        }

        $snapshot = BLP_License::get_status_snapshot();
        $this->success(['license' => $this->sanitize_license_snapshot_for_view($snapshot)]);
    }

    public function blp_save_license_settings() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_License')) {
            $this->error('License module unavailable.', 500);
        }

        $settings = $this->parse_settings_payload($_POST['settings'] ?? []);
        $snapshot = BLP_License::update_settings($settings);

        $this->success(['license' => $snapshot]);
    }

    public function blp_license_save_key() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_License')) {
            $this->error('License module unavailable.', 500);
        }

        $license_key = wp_unslash((string) ($_POST['license_key'] ?? ''));
        $snapshot = BLP_License::save_license_key($license_key);

        $this->success(['license' => $snapshot]);
    }

    public function blp_license_activate() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_License')) {
            $this->error('License module unavailable.', 500);
        }

        $result = BLP_License::activate_license();
        if (is_wp_error($result)) {
            $this->error($result->get_error_message(), 400);
        }

        $this->success(['license' => $result]);
    }

    public function blp_license_validate() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_License')) {
            $this->error('License module unavailable.', 500);
        }

        $result = BLP_License::validate_license(true);
        if (is_wp_error($result)) {
            $this->error($result->get_error_message(), 400);
        }

        $this->success(['license' => $result]);
    }

    public function blp_license_deactivate() {
        $this->verify_nonce();
        $this->require_admin_capability();
        $this->require_site_admin();

        if (!class_exists('BLP_License')) {
            $this->error('License module unavailable.', 500);
        }

        $result = BLP_License::deactivate_license();
        if (is_wp_error($result)) {
            $this->error($result->get_error_message(), 400);
        }

        $this->success(['license' => $result]);
    }

    public function blp_save_profile() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $current_user_id = get_current_user_id();
        $profile_id = (int) ($_POST['profile_id'] ?? 0);
        $target_profile = null;

        if ($profile_id > 0) {
            $target_profile = BLP_Database::get_profile_by_id($profile_id);
            if (!$this->can_access_profile($target_profile)) {
                $this->error('Access denied.', 403);
            }
        }

        $username = sanitize_key($_POST['username'] ?? '');

        if (!$username) $this->error('Username is required.');
        if (strlen($username) < 3)  $this->error('Username must be at least 3 characters.');
        if (strlen($username) > 50) $this->error('Username must not exceed 50 characters.');

        if (BLP_Database::is_username_taken($username, $profile_id)) {
            $this->error('That username is already taken.');
        }

        // Handle avatar upload
        $avatar_url = $target_profile ? $target_profile->avatar_url : '';
        if (isset($_POST['avatar_url'])) {
            $avatar_url = esc_url_raw($_POST['avatar_url']);
        }

        if (!empty($_FILES['avatar']['tmp_name'])) {
            // Validate file size (max 2MB)
            $max_size = 2 * 1024 * 1024;
            if ($_FILES['avatar']['size'] > $max_size) {
                $this->error('Avatar file is too large. Maximum size is 2MB.', 400);
            }

            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_info = wp_check_filetype_and_ext($_FILES['avatar']['tmp_name'], $_FILES['avatar']['name']);
            $detected_type = $file_info['type'] ?: mime_content_type($_FILES['avatar']['tmp_name']);
            if (!in_array($detected_type, $allowed_types, true)) {
                $this->error('Invalid file type. Allowed: JPG, PNG, GIF, WebP.', 400);
            }

            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            $attachment_id = media_handle_upload('avatar', 0);
            if (is_wp_error($attachment_id)) {
                $this->error($attachment_id->get_error_message(), 400);
            }

            // Generate and use a resized thumbnail (256x256) if possible
            $thumb = wp_get_attachment_image_src($attachment_id, [256, 256]);
            $uploaded_avatar_url = $thumb ? $thumb[0] : wp_get_attachment_url($attachment_id);
            if (empty($uploaded_avatar_url)) {
                $this->error('Avatar upload failed. Please try again.', 500);
            }

            $avatar_url = $uploaded_avatar_url;
        }

        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        if (mb_strlen($display_name) > 100) $this->error('Display name too long (max 100 characters).');

        $bio = sanitize_textarea_field($_POST['bio'] ?? '');
        if (mb_strlen($bio) > 500) $this->error('Bio too long (max 500 characters).');

        // Custom domain
        $custom_domain = '';
        if (isset($_POST['custom_domain'])) {
            $custom_domain_raw = wp_unslash((string) $_POST['custom_domain']);
            $custom_domain = $this->sanitize_custom_domain($custom_domain_raw);
            if (trim($custom_domain_raw) !== '' && $custom_domain === '') {
                $this->error('Invalid custom domain format. Example: links.example.com');
            }
        }

        $save_data = [
            'username'      => $username,
            'display_name'  => $display_name,
            'bio'           => $bio,
            'avatar_url'    => $avatar_url,
            'custom_domain' => $custom_domain,
        ];

        if ($profile_id > 0) {
            $saved_profile_id = BLP_Database::save_profile($current_user_id, $save_data, $profile_id);
        } else {
            $owner_user_id = $current_user_id;
            if (current_user_can('manage_options')) {
                $posted_owner = (int) ($_POST['user_id'] ?? 0);
                if ($posted_owner > 0) {
                    $owner_user_id = $posted_owner;
                }
            }

            $saved_profile_id = BLP_Database::create_profile($owner_user_id, $save_data);
        }

        if ($saved_profile_id <= 0) {
            $this->error('Failed to save profile.', 500);
        }

        update_user_meta($current_user_id, 'blp_active_profile_id', (int) $saved_profile_id);
        $saved_profile = BLP_Database::get_profile_by_id((int) $saved_profile_id);

        $this->success([
            'publicUrl'         => blp_profile_url($username),
            'publicFallbackUrl' => blp_profile_fallback_url($username),
            'previewUrl'        => blp_profile_preview_url($username),
            'profile_id'        => (int) $saved_profile_id,
            'profile'           => $saved_profile,
        ]);
    }

    public function blp_save_design() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = $this->get_profile_id(true);
        if (!$profile_id) $this->error('Profile not found. Save profile settings first.');

        $settings = $_POST['settings'] ?? [];
        if (!is_array($settings)) $this->error('Invalid settings format.');

        $clean = $this->sanitize_theme_settings($settings);

        $updated_profile_id = BLP_Database::save_profile(get_current_user_id(), [
            'theme_settings' => wp_json_encode($clean),
        ], $profile_id);

        if ($updated_profile_id <= 0) {
            $this->error('Failed to save design.', 500);
        }

        $this->success([
            'message'    => 'Design saved!',
            'profile_id' => (int) $profile_id,
            'design'     => $clean,
            'saved_at'   => current_time('mysql'),
        ]);
    }

    /**
     * Allowed link types and which ones require a URL.
     */
    private static $link_types = [
        'link'        => true,   // requires URL
        'header'      => false,
        'divider'     => false,
        'text'        => false,
        'embed'       => true,   // requires URL (YouTube, Spotify, etc.)
        'accordion'   => false,
        'vcard'       => false,
        'testimonial' => false,
        'gallery'     => false,
    ];

    /**
     * Sanitize metadata JSON for a given link type.
     */
    private function sanitize_link_metadata($link_type, $raw_metadata) {
        if (empty($raw_metadata)) return '{}';

        $meta = is_string($raw_metadata) ? json_decode($raw_metadata, true) : $raw_metadata;
        if (!is_array($meta)) return '{}';

        $clean = [];

        switch ($link_type) {
            case 'accordion':
                $clean['content'] = sanitize_textarea_field($meta['content'] ?? '');
                break;
            case 'vcard':
                $clean['full_name'] = sanitize_text_field($meta['full_name'] ?? '');
                $clean['email']     = sanitize_email($meta['email'] ?? '');
                $clean['phone']     = sanitize_text_field($meta['phone'] ?? '');
                $clean['company']   = sanitize_text_field($meta['company'] ?? '');
                $clean['job_title'] = sanitize_text_field($meta['job_title'] ?? '');
                $clean['address']   = sanitize_text_field($meta['address'] ?? '');
                $clean['website']   = esc_url_raw($meta['website'] ?? '');
                break;
            case 'testimonial':
                $clean['content']    = sanitize_textarea_field($meta['content'] ?? '');
                $clean['author']     = sanitize_text_field($meta['author'] ?? '');
                $clean['company']    = sanitize_text_field($meta['company'] ?? '');
                $clean['avatar_url'] = esc_url_raw($meta['avatar_url'] ?? '');
                $clean['rating']     = max(0, min(5, (int) ($meta['rating'] ?? 5)));
                break;
            case 'text':
                $clean['content'] = sanitize_textarea_field($meta['content'] ?? '');
                break;
            case 'embed':
                $clean['embed_type'] = sanitize_text_field($meta['embed_type'] ?? 'auto');
                break;
            case 'gallery':
                $images = $meta['images'] ?? [];
                if (is_string($images)) {
                    $images = preg_split('/\r\n|\r|\n/', $images);
                }

                if (is_array($images)) {
                    $safe_images = [];
                    foreach ($images as $img_url) {
                        $safe_image = $this->sanitize_safe_url((string) $img_url);
                        if ($safe_image) {
                            $safe_images[] = $safe_image;
                        }

                        if (count($safe_images) >= 30) {
                            break;
                        }
                    }

                    if (!empty($safe_images)) {
                        $clean['images'] = $safe_images;
                    }
                }
                break;
            default:
                break;
        }

        if (in_array($link_type, ['link', 'embed'], true)) {
            $utm_source = $this->sanitize_utm_value($meta['utm_source'] ?? '', 120);
            $utm_medium = $this->sanitize_utm_value($meta['utm_medium'] ?? '', 120);
            $utm_campaign = $this->sanitize_utm_value($meta['utm_campaign'] ?? '', 150);

            if ($utm_source !== '') $clean['utm_source'] = $utm_source;
            if ($utm_medium !== '') $clean['utm_medium'] = $utm_medium;
            if ($utm_campaign !== '') $clean['utm_campaign'] = $utm_campaign;
        }

        return wp_json_encode($clean);
    }

    public function blp_add_link() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = $this->get_profile_id(true);
        if (!$profile_id) $this->error('Save your profile first.');

        $link_type = sanitize_key($_POST['link_type'] ?? 'link');
        if (!isset(self::$link_types[$link_type])) {
            $link_type = 'link';
        }
        $requires_url = self::$link_types[$link_type];

        $title = sanitize_text_field($_POST['title'] ?? '');
        if (!$title && $link_type !== 'divider') $this->error('Title is required.');
        if (mb_strlen($title) > 200) $this->error('Title too long (max 200 characters).');

        $url = '';
        if ($requires_url) {
            $url = $this->sanitize_safe_url($_POST['url'] ?? '');
            if (!$url) $this->error('A valid URL (http:// or https://) is required.');
            if (strlen($url) > 2048) $this->error('URL too long (max 2048 characters).');
        }

        $subtitle = sanitize_text_field($_POST['subtitle'] ?? '');
        if (mb_strlen($subtitle) > 500) $subtitle = mb_substr($subtitle, 0, 500);

        $thumbnail_url = esc_url_raw($_POST['thumbnail_url'] ?? '');

        $title_b = sanitize_text_field($_POST['title_b'] ?? '');
        if (mb_strlen($title_b) > 200) $title_b = mb_substr($title_b, 0, 200);

        $id = BLP_Database::add_link([
            'profile_id'    => $profile_id,
            'link_type'     => $link_type,
            'title'         => $title ?: ($link_type === 'divider' ? '—' : ''),
            'title_b'       => $title_b,
            'url'           => $url,
            'subtitle'      => $subtitle,
            'thumbnail_url' => $thumbnail_url,
            'icon'          => sanitize_text_field($_POST['icon'] ?? ''),
            'badge'         => sanitize_text_field($_POST['badge'] ?? ''),
            'badge_color'   => sanitize_hex_color($_POST['badge_color'] ?? '#ef4444') ?: '#ef4444',
            'metadata'      => $this->sanitize_link_metadata($link_type, $_POST['metadata'] ?? ''),
            'is_visible'    => 1,
            'is_featured'   => !empty($_POST['is_featured']) ? 1 : 0,
            'sort_order'    => (int)($_POST['sort_order'] ?? 999),
            'schedule_start' => $this->sanitize_schedule_date($_POST['schedule_start'] ?? ''),
            'schedule_end'   => $this->sanitize_schedule_date($_POST['schedule_end'] ?? ''),
        ]);

        $link = BLP_Database::get_link($id);
        $this->success(['link' => $link]);
    }

    public function blp_update_link() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) $this->error('Link ID missing.');

        $profile_id = $this->get_profile_id(false);

        // SECURITY: verify link belongs to the current user
        $this->verify_link_ownership($id, $profile_id);

        $link_type = sanitize_key($_POST['link_type'] ?? 'link');
        if (!isset(self::$link_types[$link_type])) {
            $link_type = 'link';
        }
        $requires_url = self::$link_types[$link_type];

        $update_title = sanitize_text_field($_POST['title'] ?? '');
        if (mb_strlen($update_title) > 200) $this->error('Title too long (max 200 characters).');

        $url = '';
        if ($requires_url) {
            $url = $this->sanitize_safe_url($_POST['url'] ?? '');
            if (!$url) $this->error('A valid URL (http:// or https://) is required.');
            if (strlen($url) > 2048) $this->error('URL too long (max 2048 characters).');
        }

        $subtitle = sanitize_text_field($_POST['subtitle'] ?? '');
        if (mb_strlen($subtitle) > 500) $subtitle = mb_substr($subtitle, 0, 500);

        $thumbnail_url = esc_url_raw($_POST['thumbnail_url'] ?? '');

        $title_b = sanitize_text_field($_POST['title_b'] ?? '');
        if (mb_strlen($title_b) > 200) $title_b = mb_substr($title_b, 0, 200);

        BLP_Database::update_link($id, [
            'link_type'     => $link_type,
            'title'         => $update_title,
            'title_b'       => $title_b,
            'url'           => $url,
            'subtitle'      => $subtitle,
            'thumbnail_url' => $thumbnail_url,
            'icon'          => sanitize_text_field($_POST['icon'] ?? ''),
            'badge'         => sanitize_text_field($_POST['badge'] ?? ''),
            'badge_color'   => sanitize_hex_color($_POST['badge_color'] ?? '#ef4444') ?: '#ef4444',
            'metadata'      => $this->sanitize_link_metadata($link_type, $_POST['metadata'] ?? ''),
            'is_featured'   => !empty($_POST['is_featured']) ? 1 : 0,
            'schedule_start' => $this->sanitize_schedule_date($_POST['schedule_start'] ?? ''),
            'schedule_end'   => $this->sanitize_schedule_date($_POST['schedule_end'] ?? ''),
        ]);

        $this->success(['link' => BLP_Database::get_link($id)]);
    }

    public function blp_delete_link() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) $this->error('Link ID missing.');

        $profile_id = $this->get_profile_id(false);

        // SECURITY: verify link belongs to the current user
        $this->verify_link_ownership($id, $profile_id);

        BLP_Database::delete_link($id);
        $this->success();
    }

    public function blp_toggle_link() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $id      = (int)($_POST['id'] ?? 0);
        $visible = (int)($_POST['visible'] ?? 0);
        if (!$id) $this->error('Link ID missing.');

        $profile_id = $this->get_profile_id(false);

        // SECURITY: verify link belongs to the current user
        $this->verify_link_ownership($id, $profile_id);

        BLP_Database::update_link($id, ['is_visible' => ($visible ? 1 : 0)]);
        $this->success();
    }

    public function blp_reorder_links() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = $this->get_profile_id(true);
        if (!$profile_id) $this->error('Profile not found.');

        $orders = $_POST['orders'] ?? [];
        if (!is_array($orders)) $this->error('Invalid orders format.');

        // SECURITY: batch-validate ownership — single query instead of N+1
        $profile_links = BLP_Database::get_links($profile_id);
        $owned_ids = array_flip(array_map(function($l) { return (int) $l->id; }, $profile_links));

        $safe_orders = [];
        foreach ($orders as $link_id => $order) {
            $lid = (int) $link_id;
            if (isset($owned_ids[$lid])) {
                $safe_orders[$lid] = (int) $order;
            }
        }

        if (!empty($safe_orders)) {
            BLP_Database::update_sort_order($safe_orders);
        }
        $this->success();
    }

    public function blp_get_analytics() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = $this->get_profile_id(true);
        if (!$profile_id) $this->error('Profile not found.');

        $days = min(365, max(1, (int)($_POST['days'] ?? 30)));
        $data = BLP_Database::get_analytics($profile_id, $days);
        $this->success($data);
    }

    /* ── Multi-Profile Management ────────────── */

    public function blp_get_profiles() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $user_id = get_current_user_id();
        $limit  = min(500, max(1, (int) ($_POST['limit'] ?? 200)));
        $offset = max(0, (int) ($_POST['offset'] ?? 0));
        $search = sanitize_text_field($_POST['search'] ?? '');
        $status = sanitize_key($_POST['status'] ?? 'all');
        if (!in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }
        $sort = sanitize_key($_POST['sort'] ?? 'updated_desc');
        if (!in_array($sort, ['updated_desc', 'updated_asc', 'username_asc', 'username_desc', 'links_desc', 'links_asc'], true)) {
            $sort = 'updated_desc';
        }

        if (current_user_can('manage_options')) {
            $profiles = BLP_Database::get_all_profiles($limit, $offset, $search, $status, $sort);
            $total    = BLP_Database::count_all_profiles($search, $status);
        } else {
            $profiles = BLP_Database::get_profiles_by_user($user_id);

            if ($search !== '') {
                $lower = static function($value) {
                    $text = (string) $value;
                    return function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
                };
                $needle = $lower($search);
                $profiles = array_values(array_filter($profiles, static function($p) use ($needle) {
                    $username = function_exists('mb_strtolower')
                        ? mb_strtolower((string) ($p->username ?? ''))
                        : strtolower((string) ($p->username ?? ''));
                    $display  = function_exists('mb_strtolower')
                        ? mb_strtolower((string) ($p->display_name ?? ''))
                        : strtolower((string) ($p->display_name ?? ''));
                    return strpos($username, $needle) !== false || strpos($display, $needle) !== false;
                }));
            }

            if ($status !== 'all') {
                $target_status = $status === 'active' ? 1 : 0;
                $profiles = array_values(array_filter($profiles, static function($p) use ($target_status) {
                    return (int) ($p->is_active ?? 0) === $target_status;
                }));
            }

            usort($profiles, static function($a, $b) use ($sort) {
                $a_links = (int) ($a->links_count ?? 0);
                $b_links = (int) ($b->links_count ?? 0);
                $a_updated = strtotime((string) ($a->updated_at ?? '1970-01-01 00:00:00')) ?: 0;
                $b_updated = strtotime((string) ($b->updated_at ?? '1970-01-01 00:00:00')) ?: 0;
                $a_user = strtolower((string) ($a->username ?? ''));
                $b_user = strtolower((string) ($b->username ?? ''));

                switch ($sort) {
                    case 'updated_asc':
                        return $a_updated <=> $b_updated;
                    case 'username_asc':
                        return $a_user <=> $b_user;
                    case 'username_desc':
                        return $b_user <=> $a_user;
                    case 'links_desc':
                        return $b_links <=> $a_links;
                    case 'links_asc':
                        return $a_links <=> $b_links;
                    case 'updated_desc':
                    default:
                        return $b_updated <=> $a_updated;
                }
            });

            $total = count($profiles);
            $profiles = array_slice($profiles, $offset, $limit);
        }

        $this->success([
            'profiles' => $this->format_profiles($profiles),
            'total'    => (int) $total,
            'limit'    => (int) $limit,
            'offset'   => (int) $offset,
        ]);
    }

    public function blp_switch_profile() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = (int) ($_POST['profile_id'] ?? 0);
        if (!$profile_id) $this->error('Profile ID is required.');

        $profile = BLP_Database::get_profile_by_id($profile_id);
        if (!$this->can_access_profile($profile)) {
            $this->error('Access denied.', 403);
        }

        update_user_meta(get_current_user_id(), 'blp_active_profile_id', (int) $profile_id);

        $links     = BLP_Database::get_links($profile_id);
        $analytics = BLP_Database::get_analytics($profile_id, 30);
        $theme     = !empty($profile->theme_settings) ? json_decode($profile->theme_settings, true) : [];

        $this->success([
            'profile'   => $profile,
            'links'     => $links,
            'analytics' => $analytics,
            'design'    => $theme ?: new \stdClass(),
            'publicUrl' => blp_profile_url($profile->username),
            'publicFallbackUrl' => blp_profile_fallback_url($profile->username),
            'previewUrl'=> blp_profile_preview_url($profile->username),
        ]);
    }

    public function blp_create_profile() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $current_user_id = get_current_user_id();
        $owner_user_id   = $current_user_id;
        if (current_user_can('manage_options')) {
            $posted_owner = (int) ($_POST['user_id'] ?? 0);
            if ($posted_owner > 0) {
                $owner_user_id = $posted_owner;
            }
        }

        // Scale-safe profile limits per owner
        $max_profiles_per_user = current_user_can('manage_options') ? 1000 : 200;
        $count = BLP_Database::count_profiles_by_user($owner_user_id);
        if ($count >= $max_profiles_per_user) {
            $this->error(sprintf('Maximum number of profiles (%d) reached for this user.', $max_profiles_per_user));
        }

        $username = sanitize_key($_POST['username'] ?? '');
        if (!$username) $this->error('Username is required.');
        if (strlen($username) < 3)  $this->error('Username must be at least 3 characters.');
        if (strlen($username) > 50) $this->error('Username must not exceed 50 characters.');

        // Check uniqueness
        if (BLP_Database::is_username_taken($username)) {
            $this->error('That username is already taken.');
        }

        $profile_id = BLP_Database::create_profile($owner_user_id, [
            'username'     => $username,
            'display_name' => sanitize_text_field($_POST['display_name'] ?? $username),
            'bio'          => '',
            'avatar_url'   => '',
        ]);

        if ($profile_id <= 0) {
            $this->error('Failed to create profile.', 500);
        }

        update_user_meta($current_user_id, 'blp_active_profile_id', (int) $profile_id);

        $profile = BLP_Database::get_profile_by_id($profile_id);

        $this->success([
            'profile'    => $profile,
            'profile_id' => $profile_id,
            'publicUrl'  => blp_profile_url($username),
            'publicFallbackUrl' => blp_profile_fallback_url($username),
            'previewUrl' => blp_profile_preview_url($username),
        ]);
    }

    public function blp_duplicate_profile() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $source_profile_id = (int) ($_POST['profile_id'] ?? 0);
        if (!$source_profile_id) {
            $this->error('Profile ID is required.');
        }

        $source_profile = BLP_Database::get_profile_by_id($source_profile_id);
        if (!$this->can_access_profile($source_profile)) {
            $this->error('Access denied.', 403);
        }

        $base_username = sanitize_key((string) ($source_profile->username ?? 'profile'));
        if ($base_username === '') {
            $base_username = 'profile';
        }

        $candidate_username = $base_username . '-copy';
        $suffix = 2;
        while (BLP_Database::is_username_taken($candidate_username)) {
            $candidate_username = $base_username . '-copy-' . $suffix;
            $suffix++;
            if ($suffix > 9999) {
                $this->error('Could not generate a unique duplicate username.', 500);
            }
        }

        $base_display_name = !empty($source_profile->display_name)
            ? (string) $source_profile->display_name
            : (string) $source_profile->username;

        $duplicate_display_name = sanitize_text_field(trim($base_display_name . ' Copy'));
        if ($duplicate_display_name === '') {
            $duplicate_display_name = 'Profile Copy';
        }

        $new_profile_id = BLP_Database::create_profile((int) $source_profile->user_id, [
            'username'       => $candidate_username,
            'display_name'   => $duplicate_display_name,
            'bio'            => sanitize_textarea_field((string) ($source_profile->bio ?? '')),
            'avatar_url'     => esc_url_raw((string) ($source_profile->avatar_url ?? '')),
            'theme_settings' => !empty($source_profile->theme_settings)
                ? (string) $source_profile->theme_settings
                : wp_json_encode([]),
            'is_active'      => (int) ($source_profile->is_active ?? 1),
        ]);

        if ($new_profile_id <= 0) {
            $this->error('Failed to duplicate profile.', 500);
        }

        $source_links = BLP_Database::get_links($source_profile_id);
        foreach ($source_links as $index => $link) {
            $link_type = sanitize_key((string) ($link->link_type ?? 'link'));
            if (!isset(self::$link_types[$link_type])) {
                $link_type = 'link';
            }

            $requires_url = self::$link_types[$link_type];
            $safe_url = '';
            if ($requires_url) {
                $safe_url = $this->sanitize_safe_url((string) ($link->url ?? ''));
                if (!$safe_url) {
                    continue;
                }
            }

            BLP_Database::add_link([
                'profile_id'     => (int) $new_profile_id,
                'link_type'      => $link_type,
                'title'          => sanitize_text_field((string) ($link->title ?? ('Link ' . ($index + 1)))),
                'title_b'        => sanitize_text_field((string) ($link->title_b ?? '')),
                'url'            => $safe_url,
                'subtitle'       => sanitize_text_field((string) ($link->subtitle ?? '')),
                'thumbnail_url'  => esc_url_raw((string) ($link->thumbnail_url ?? '')),
                'icon'           => sanitize_text_field((string) ($link->icon ?? '')),
                'badge'          => sanitize_text_field((string) ($link->badge ?? '')),
                'badge_color'    => sanitize_hex_color((string) ($link->badge_color ?? '#ef4444')) ?: '#ef4444',
                'metadata'       => $this->sanitize_link_metadata($link_type, (string) ($link->metadata ?? '')),
                'is_visible'     => (int) ($link->is_visible ?? 1),
                'is_featured'    => (int) ($link->is_featured ?? 0),
                'sort_order'     => (int) ($link->sort_order ?? $index),
                'schedule_start' => !empty($link->schedule_start) ? (string) $link->schedule_start : null,
                'schedule_end'   => !empty($link->schedule_end) ? (string) $link->schedule_end : null,
            ]);
        }

        $new_profile = BLP_Database::get_profile_by_id($new_profile_id);
        if (!$new_profile) {
            $this->error('Duplicate created but could not be loaded.', 500);
        }

        $copied_links_count = count(BLP_Database::get_links($new_profile_id));
        $new_profile->links_count = $copied_links_count;
        $formatted = $this->format_profiles([$new_profile]);

        $this->success([
            'profile'      => $formatted[0],
            'profile_id'   => (int) $new_profile_id,
            'links_copied' => (int) $copied_links_count,
        ]);
    }

    public function blp_delete_profile() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $user_id    = get_current_user_id();
        $profile_id = (int) ($_POST['profile_id'] ?? 0);
        if (!$profile_id) $this->error('Profile ID is required.');

        $profile = BLP_Database::get_profile_by_id($profile_id);
        if (!$this->can_access_profile($profile)) {
            $this->error('Access denied.', 403);
        }

        // Don't allow deleting the last profile
        $count = BLP_Database::count_profiles_by_user((int) $profile->user_id);
        if ($count <= 1) $this->error('Cannot delete your only profile.');

        BLP_Database::delete_profile($profile_id);

        if ((int) get_user_meta($user_id, 'blp_active_profile_id', true) === $profile_id) {
            $replacement_id = 0;
            if (current_user_can('manage_options')) {
                $replacement = BLP_Database::get_all_profiles(1, 0);
            } else {
                $replacement = BLP_Database::get_profiles_by_user($user_id);
            }
            if (!empty($replacement)) {
                $replacement_id = (int) $replacement[0]->id;
            }
            update_user_meta($user_id, 'blp_active_profile_id', (int) $replacement_id);
        }

        // Return remaining profiles
        if (current_user_can('manage_options')) {
            $profiles = BLP_Database::get_all_profiles(1000, 0);
        } else {
            $profiles = BLP_Database::get_profiles_by_user($user_id);
        }

        $this->success(['profiles' => $this->format_profiles($profiles)]);
    }

    public function blp_set_profile_status() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = (int) ($_POST['profile_id'] ?? 0);
        if (!$profile_id) {
            $this->error('Profile ID is required.');
        }

        $profile = BLP_Database::get_profile_by_id($profile_id);
        if (!$this->can_access_profile($profile)) {
            $this->error('Access denied.', 403);
        }

        $is_active = filter_var($_POST['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        BLP_Database::update_profile_status($profile_id, $is_active);

        // If active profile got deactivated, switch pointer to another active profile.
        $current_user_id = get_current_user_id();
        $active_profile_id = (int) get_user_meta($current_user_id, 'blp_active_profile_id', true);
        $new_active_profile_id = $active_profile_id;

        if ($is_active === 0 && $active_profile_id === $profile_id) {
            $replacement_profile_id = 0;

            if (current_user_can('manage_options')) {
                $available_profiles = BLP_Database::get_all_profiles(1000, 0);
            } else {
                $available_profiles = BLP_Database::get_profiles_by_user($current_user_id);
            }

            foreach ($available_profiles as $candidate) {
                if ((int) $candidate->id === $profile_id) {
                    continue;
                }
                if ((int) ($candidate->is_active ?? 0) === 1) {
                    $replacement_profile_id = (int) $candidate->id;
                    break;
                }
            }
            update_user_meta($current_user_id, 'blp_active_profile_id', $replacement_profile_id);
            $new_active_profile_id = (int) $replacement_profile_id;
        }

        $updated_profile = BLP_Database::get_profile_by_id($profile_id);
        $updated_profile->links_count = count(BLP_Database::get_links($profile_id));
        $formatted = $this->format_profiles([$updated_profile]);

        $this->success([
            'profile'           => $formatted[0],
            'active_profile_id' => (int) $new_active_profile_id,
        ]);
    }

    public function blp_bulk_profile_status() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $raw_profile_ids = $_POST['profile_ids'] ?? [];
        if (is_string($raw_profile_ids)) {
            $decoded = json_decode(wp_unslash($raw_profile_ids), true);
            if (is_array($decoded)) {
                $raw_profile_ids = $decoded;
            } else {
                $raw_profile_ids = array_map('trim', explode(',', $raw_profile_ids));
            }
        }

        if (!is_array($raw_profile_ids)) {
            $this->error('Invalid profile_ids payload.');
        }

        $profile_ids = array_values(array_unique(array_filter(array_map('intval', $raw_profile_ids))));
        if (empty($profile_ids)) {
            $this->error('No profiles selected.');
        }

        $is_active = filter_var($_POST['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $updated_count = 0;

        foreach ($profile_ids as $profile_id) {
            $profile = BLP_Database::get_profile_by_id($profile_id);
            if (!$this->can_access_profile($profile)) {
                $this->error('Access denied for one or more profiles.', 403);
            }

            BLP_Database::update_profile_status($profile_id, $is_active);
            $updated_count++;
        }

        if ($is_active === 0) {
            $current_user_id = get_current_user_id();
            $active_profile_id = (int) get_user_meta($current_user_id, 'blp_active_profile_id', true);
            if (in_array($active_profile_id, $profile_ids, true)) {
                $replacement_profile_id = 0;

                if (current_user_can('manage_options')) {
                    $available_profiles = BLP_Database::get_all_profiles(1000, 0);
                } else {
                    $available_profiles = BLP_Database::get_profiles_by_user($current_user_id);
                }

                foreach ($available_profiles as $candidate) {
                    if ((int) ($candidate->is_active ?? 0) === 1) {
                        $replacement_profile_id = (int) $candidate->id;
                        break;
                    }
                }
                update_user_meta($current_user_id, 'blp_active_profile_id', $replacement_profile_id);
            }
        }

        $active_profile_id = (int) get_user_meta(get_current_user_id(), 'blp_active_profile_id', true);

        $this->success([
            'updated_count' => (int) $updated_count,
            'is_active'     => (int) $is_active,
            'active_profile_id' => (int) $active_profile_id,
        ]);
    }

    /* ── Export / Import ─────────────────────── */

    public function blp_export_profile() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = $this->get_profile_id(true);
        if (!$profile_id) $this->error('Profile not found.');

        $profile = BLP_Database::get_profile_by_id($profile_id);
        if (!$profile) $this->error('Profile not found.');

        $links = BLP_Database::get_links($profile_id);
        $export = [
            'version'        => BLP_VERSION,
            'exported_at'    => current_time('mysql'),
            'profile'        => [
                'username'       => $profile->username,
                'display_name'   => $profile->display_name,
                'bio'            => $profile->bio,
                'avatar_url'     => $profile->avatar_url,
                'custom_domain'  => $profile->custom_domain,
                'theme_settings' => json_decode($profile->theme_settings, true) ?: [],
            ],
            'links' => array_map(function($link) {
                return [
                    'link_type'      => $link->link_type,
                    'title'          => $link->title,
                    'title_b'        => $link->title_b,
                    'url'            => $link->url,
                    'subtitle'       => $link->subtitle,
                    'thumbnail_url'  => $link->thumbnail_url,
                    'icon'           => $link->icon,
                    'badge'          => $link->badge,
                    'badge_color'    => $link->badge_color,
                    'metadata'       => json_decode($link->metadata, true) ?: new \stdClass(),
                    'is_visible'     => (int) $link->is_visible,
                    'is_featured'    => (int) $link->is_featured,
                    'sort_order'     => (int) $link->sort_order,
                    'schedule_start' => $link->schedule_start,
                    'schedule_end'   => $link->schedule_end,
                ];
            }, $links),
        ];

        $this->success(['export' => $export]);
    }

    public function blp_import_profile() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $json_raw = wp_unslash($_POST['import_data'] ?? '');
        $import = json_decode($json_raw, true);
        if (!$import || !isset($import['profile'])) {
            $this->error('Invalid import data.');
        }

        $p = $import['profile'];
        $username = sanitize_key($p['username'] ?? '');
        if (!$username) $this->error('Invalid username in import data.');

        // Check if username already exists — if so, append a suffix
        if (BLP_Database::is_username_taken($username)) {
            $username = $username . '_' . wp_rand(100, 999);
        }

        $import_theme = $p['theme_settings'] ?? [];
        if (is_string($import_theme)) {
            $decoded_theme = json_decode($import_theme, true);
            $import_theme = is_array($decoded_theme) ? $decoded_theme : [];
        }
        if (!is_array($import_theme)) {
            $import_theme = [];
        }
        $sanitized_theme = $this->sanitize_theme_settings($import_theme);

        $custom_domain_raw = sanitize_text_field((string) ($p['custom_domain'] ?? ''));
        $custom_domain = $this->sanitize_custom_domain($custom_domain_raw);

        $profile_id = BLP_Database::create_profile(get_current_user_id(), [
            'username'       => $username,
            'display_name'   => sanitize_text_field($p['display_name'] ?? $username),
            'bio'            => sanitize_textarea_field($p['bio'] ?? ''),
            'avatar_url'     => esc_url_raw($p['avatar_url'] ?? ''),
            'custom_domain'  => $custom_domain,
            'theme_settings' => wp_json_encode($sanitized_theme),
        ]);
        if (!$profile_id) $this->error('Failed to create profile.');

        // Import links
        if (!empty($import['links']) && is_array($import['links'])) {
            foreach ($import['links'] as $i => $link) {
                $link_type = sanitize_key((string) ($link['link_type'] ?? 'link'));
                if (!isset(self::$link_types[$link_type])) {
                    $link_type = 'link';
                }

                $requires_url = self::$link_types[$link_type];
                $safe_url = '';
                if ($requires_url) {
                    $safe_url = $this->sanitize_safe_url($link['url'] ?? '');
                    if (!$safe_url) {
                        continue;
                    }
                }

                BLP_Database::add_link([
                    'profile_id'     => $profile_id,
                    'link_type'      => $link_type,
                    'title'          => sanitize_text_field($link['title'] ?? ('Link ' . ($i + 1))),
                    'title_b'        => sanitize_text_field($link['title_b'] ?? ''),
                    'url'            => $safe_url,
                    'subtitle'       => sanitize_text_field($link['subtitle'] ?? ''),
                    'thumbnail_url'  => esc_url_raw($link['thumbnail_url'] ?? ''),
                    'icon'           => sanitize_text_field($link['icon'] ?? ''),
                    'badge'          => sanitize_text_field($link['badge'] ?? ''),
                    'badge_color'    => sanitize_hex_color($link['badge_color'] ?? '#ef4444') ?: '#ef4444',
                    'metadata'       => $this->sanitize_link_metadata($link_type, $link['metadata'] ?? []),
                    'is_visible'     => !empty($link['is_visible']) ? 1 : 0,
                    'is_featured'    => !empty($link['is_featured']) ? 1 : 0,
                    'sort_order'     => (int) ($link['sort_order'] ?? $i),
                    'schedule_start' => $this->normalize_import_schedule_date($link['schedule_start'] ?? ''),
                    'schedule_end'   => $this->normalize_import_schedule_date($link['schedule_end'] ?? ''),
                ]);
            }
        }

        // Switch to the newly imported profile
        update_user_meta(get_current_user_id(), 'blp_active_profile_id', $profile_id);

        $this->success([
            'message'    => 'Profile imported successfully!',
            'profile_id' => $profile_id,
            'username'   => $username,
        ]);
    }

    /* ── CSV Export ─────────────────────────── */

    public function blp_export_analytics_csv() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = $this->get_profile_id(true);
        if (!$profile_id) $this->error('Profile not found.');

        $days = max(1, min(365, (int)($_POST['days'] ?? 30)));

        global $wpdb;
        $utc_tz = new \DateTimeZone('UTC');
        $period_start = (new \DateTime('now', wp_timezone()))->modify("-{$days} days")->setTimezone($utc_tz)->format('Y-m-d H:i:s');

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT a.event_type, a.clicked_at, a.device, a.referrer, COALESCE(l.title, '') AS link_title, COALESCE(l.url, '') AS link_url
             FROM {$wpdb->prefix}blp_analytics a
             LEFT JOIN {$wpdb->prefix}blp_links l ON l.id = a.link_id
             WHERE a.profile_id = %d AND a.clicked_at >= %s
             ORDER BY a.clicked_at DESC",
            $profile_id, $period_start
        ), ARRAY_A);

        $csv_lines = [];
        $csv_lines[] = 'Date,Event,Device,Referrer,Link Title,Link URL';
        foreach ($rows as $r) {
            $csv_lines[] = implode(',', [
                $this->escape_csv_field($r['clicked_at']),
                $this->escape_csv_field($r['event_type']),
                $this->escape_csv_field($r['device']),
                $this->escape_csv_field($r['referrer']),
                $this->escape_csv_field($r['link_title']),
                $this->escape_csv_field($r['link_url']),
            ]);
        }

        $this->success([
            'csv' => implode("\n", $csv_lines),
            'filename' => 'biolink-analytics-' . $profile_id . '-' . date('Y-m-d') . '.csv',
        ]);
    }

    /* ── Public: track click ─────────────────── */

    public function blp_track_click() {
        if (class_exists('BLP_Security') && BLP_Security::is_lockdown()) {
            wp_send_json_error(['message' => BLP_Security::get_lockdown_message()], 503);
            return;
        }

        // Rate limiting: max 15 requests per 60 seconds per IP
        if ($this->is_rate_limited('click', 15, 60)) {
            wp_send_json_error(['message' => 'Too many requests.'], 429);
            return;
        }

        if (!check_ajax_referer('blp_public_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
            return;
        }

        $link_id    = (int)($_POST['link_id'] ?? 0);
        $profile_id = (int)($_POST['profile_id'] ?? 0);
        if (!$link_id || !$profile_id) {
            wp_send_json_error(['message' => 'Missing parameters.']);
            return;
        }

        // SECURITY: verify link actually belongs to the claimed profile
        $link = BLP_Database::get_link($link_id);
        if (!$link || (int) $link->profile_id !== $profile_id) {
            wp_send_json_error(['message' => 'Invalid link.'], 400);
            return;
        }

        $ab_variant = sanitize_key($_POST['ab_variant'] ?? 'a');
        if (!in_array($ab_variant, ['a', 'b'], true)) $ab_variant = 'a';

        BLP_Database::track_event([
            'link_id'    => $link_id,
            'profile_id' => $profile_id,
            'event_type' => 'click',
            'ip_hash'    => hash('sha256', $this->get_client_ip() . wp_salt('auth')),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'referrer'   => esc_url_raw(substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500)),
        ], $ab_variant);

        wp_send_json_success();
    }

    /* ── Font Upload ───────────────────────────── */

    public function blp_upload_font() {
        $this->verify_nonce();
        $this->require_admin_capability();

        if (empty($_FILES['font_file']['tmp_name'])) {
            $this->error('No font file uploaded.');
        }

        $max_size = 2 * 1024 * 1024; // 2MB
        if ($_FILES['font_file']['size'] > $max_size) {
            $this->error('Font file too large. Maximum 2MB.', 400);
        }

        $filename = sanitize_file_name($_FILES['font_file']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['woff2', 'woff', 'ttf', 'otf'], true)) {
            $this->error('Invalid font type. Allowed: .woff2, .woff, .ttf, .otf', 400);
        }

        // Allow font MIME types
        add_filter('upload_mimes', function($mimes) {
            $mimes['woff2'] = 'font/woff2';
            $mimes['woff']  = 'font/woff';
            $mimes['ttf']   = 'font/ttf';
            $mimes['otf']   = 'font/otf';
            return $mimes;
        });

        add_filter('wp_check_filetype_and_ext', function($data, $file, $filename) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $font_mimes = ['woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf', 'otf' => 'font/otf'];
            if (isset($font_mimes[$ext])) {
                $data['ext']  = $ext;
                $data['type'] = $font_mimes[$ext];
            }
            return $data;
        }, 10, 3);

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload('font_file', 0);
        if (is_wp_error($attachment_id)) {
            $this->error($attachment_id->get_error_message(), 400);
        }

        $font_url = wp_get_attachment_url($attachment_id);
        $font_name = pathinfo($filename, PATHINFO_FILENAME);
        $font_name = ucwords(str_replace(['-', '_'], ' ', $font_name));

        // Store in user meta as uploaded fonts list
        $user_id = get_current_user_id();
        $fonts = get_user_meta($user_id, 'blp_custom_fonts', true) ?: [];
        $fonts[] = ['name' => $font_name, 'url' => $font_url, 'format' => $ext];
        update_user_meta($user_id, 'blp_custom_fonts', $fonts);

        $this->success(['font' => ['name' => $font_name, 'url' => $font_url, 'format' => $ext], 'fonts' => $fonts]);
    }

    /* ── CSV Link Import ───────────────────────── */

    public function blp_import_links_csv() {
        $this->verify_nonce();
        $this->require_admin_capability();

        $profile_id = $this->get_profile_id(true);
        if (!$profile_id) $this->error('Save your profile first.');

        if (empty($_FILES['csv_file']['tmp_name'])) {
            $this->error('No CSV file uploaded.');
        }

        $max_size = 1 * 1024 * 1024; // 1MB
        if ($_FILES['csv_file']['size'] > $max_size) {
            $this->error('CSV file too large. Maximum 1MB.', 400);
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) $this->error('Could not read CSV file.');

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->error('Empty CSV file.');
        }

        // Normalize header
        $header = array_map(function($h) { return strtolower(trim($h)); }, $header);
        $title_idx = array_search('title', $header);
        $url_idx   = array_search('url', $header);

        if ($title_idx === false) {
            fclose($handle);
            $this->error('CSV must have a "title" column.');
        }

        $imported = 0;
        $sort = BLP_Database::count_links($profile_id);

        while (($row = fgetcsv($handle)) !== false) {
            $title = sanitize_text_field($row[$title_idx] ?? '');
            if (empty($title)) continue;

            $url = '';
            if ($url_idx !== false && !empty($row[$url_idx])) {
                $url = esc_url_raw($row[$url_idx]);
            }

            BLP_Database::add_link([
                'profile_id' => $profile_id,
                'link_type'  => $url ? 'link' : 'text',
                'title'      => mb_substr($title, 0, 200),
                'url'        => $url ? mb_substr($url, 0, 2048) : '',
                'is_visible' => 1,
                'sort_order' => $sort++,
            ]);
            $imported++;

            if ($imported >= 200) break; // Safety limit
        }

        fclose($handle);

        $links = BLP_Database::get_links($profile_id);
        $this->success(['imported' => $imported, 'links' => $links]);
    }
}
