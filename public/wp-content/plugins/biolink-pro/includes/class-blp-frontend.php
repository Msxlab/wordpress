<?php
defined('ABSPATH') || exit;

class BLP_Frontend {

    public function __construct() {
        add_filter('template_include',  [$this, 'maybe_load_template']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
    }

    private function can_preview_profile($profile, $nonce) {
        if (!is_user_logged_in()) {
            return false;
        }

        $safe_nonce = sanitize_text_field((string) $nonce);
        if (!wp_verify_nonce($safe_nonce, 'blp_preview')) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        return (int) $profile->user_id === get_current_user_id();
    }

    /**
     * Detect known bots/crawlers to exclude from analytics.
     */
    private static function is_bot() {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (empty($ua)) return true;

        $bot_patterns = [
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'sogou', 'exabot', 'facebot', 'facebookexternalhit',
            'ia_archiver', 'alexabot', 'mj12bot', 'ahrefsbot', 'semrushbot',
            'dotbot', 'rogerbot', 'linkedinbot', 'embedly', 'quora link',
            'showyoubot', 'outbrain', 'pinterest', 'applebot', 'twitterbot',
            'slackbot', 'telegrambot', 'whatsapp', 'discordbot',
            'bytespider', 'petalbot', 'dataforseo', 'gptbot', 'claudebot',
            'crawl', 'spider', 'bot/', 'bot;', 'headlesschrome',
            'phantomjs', 'wget', 'curl/', 'python-requests', 'httpx',
            'go-http-client', 'java/', 'libwww', 'lwp-trivial',
        ];

        foreach ($bot_patterns as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function get_requested_username() {
        $username = sanitize_key((string) get_query_var('blp_username'));

        if (!$username && isset($_GET['blp_username'])) {
            $username = sanitize_key(wp_unslash($_GET['blp_username']));
        }

        return $username;
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

    public function maybe_load_template($template) {
        $username = $this->get_requested_username();
        if (!$username) return $template;

        if (class_exists('BLP_Security') && BLP_Security::is_lockdown()) {
            status_header(503);
            wp_die('<h1>503 — Service temporarily unavailable</h1><p>' . esc_html(BLP_Security::get_lockdown_message()) . '</p>');
        }

        $is_preview_request = isset($_GET['blp_preview']);
        if ($is_preview_request) {
            $profile = BLP_Database::get_profile_by_username_any($username);
        } else {
            $profile = BLP_Database::get_profile_by_username($username);
        }

        if (!$profile) return $template;

        // Preview mode requires login + nonce + profile ownership (or manager capability).
        if ($is_preview_request && !$this->can_preview_profile($profile, $_GET['blp_nonce'] ?? '')) {
            status_header(403);
            wp_die('<h1>403 — Forbidden</h1><p>Preview authorization failed.</p>');
        }

        // Only track real visits, not previews by the profile owner
        if (!$is_preview_request) {
            // Skip tracking for known bots/crawlers
            if (!self::is_bot()) {
                $referrer = esc_url_raw(substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500));

                // Capture UTM params from the page URL for campaign attribution
                $utm_source   = sanitize_text_field($_GET['utm_source'] ?? '');
                $utm_medium   = sanitize_text_field($_GET['utm_medium'] ?? '');
                $utm_campaign = sanitize_text_field($_GET['utm_campaign'] ?? '');

                // If no HTTP referrer but UTM source exists, use UTM as referrer
                if (empty($referrer) && $utm_source) {
                    $referrer = 'utm:' . substr($utm_source, 0, 100);
                    if ($utm_medium) $referrer .= '/' . substr($utm_medium, 0, 50);
                    if ($utm_campaign) $referrer .= '?' . substr($utm_campaign, 0, 100);
                }

                BLP_Database::track_event([
                    'profile_id' => $profile->id,
                    'link_id'    => null,
                    'event_type' => 'view',
                    'ip_hash'    => hash('sha256', $this->get_client_ip() . wp_salt('auth')),
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                    'referrer'   => $referrer,
                ]);
                BLP_Database::increment_page_view($profile->id);
            }
        }

        return BLP_PLUGIN_DIR . 'public/template.php';
    }

    public function enqueue_public_assets() {
        $username = $this->get_requested_username();
        if (!$username) return;

        wp_enqueue_style('blp-public', BLP_PLUGIN_URL . 'public/css/biolink.css', [], BLP_VERSION);
        wp_enqueue_script('blp-public', BLP_PLUGIN_URL . 'public/js/biolink.js', [], BLP_VERSION, true);
        wp_localize_script('blp-public', 'BLP_Public', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('blp_public_nonce'),
        ]);
    }
}
