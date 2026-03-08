<?php
defined('ABSPATH') || exit;

class BLP_Security {

    const OPTION_MODE = 'blp_security_mode';
    const OPTION_SETTINGS = 'blp_security_settings';
    const OPTION_EVENTS = 'blp_security_events';
    const OPTION_INTEGRITY_STATE = 'blp_integrity_state';
    const OPTION_INTEGRITY_BASELINE = 'blp_integrity_baseline';
    const OPTION_INTEGRITY_BASELINE_VERSION = 'blp_integrity_baseline_version';
    const TRANSIENT_POLICY_CACHE = 'blp_security_policy_cache';
    const CRON_HOOK = 'blp_security_hourly_check';

    private static $mode_cache = null;

    private static $valid_modes = ['normal', 'read_only', 'lockdown'];

    private static $write_actions = [
        'blp_save_profile',
        'blp_save_design',
        'blp_add_link',
        'blp_update_link',
        'blp_delete_link',
        'blp_toggle_link',
        'blp_reorder_links',
        'blp_create_profile',
        'blp_duplicate_profile',
        'blp_delete_profile',
        'blp_set_profile_status',
        'blp_bulk_profile_status',
        'blp_import_profile',
        'blp_upload_font',
        'blp_import_links_csv',
    ];

    private static $always_allowed_actions = [
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

    public static function init_hooks() {
        if (get_option(self::OPTION_MODE, '') === '') {
            add_option(self::OPTION_MODE, 'normal', '', false);
        }

        if (get_option(self::OPTION_SETTINGS, null) === null) {
            add_option(self::OPTION_SETTINGS, self::get_default_settings(), '', false);
        }

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK);
        }

        add_action(self::CRON_HOOK, [__CLASS__, 'run_hourly_checks']);
        add_action('admin_notices', [__CLASS__, 'render_admin_notices']);

        self::maybe_refresh_integrity_baseline();
    }

    public static function install() {
        if (get_option(self::OPTION_MODE, '') === '') {
            add_option(self::OPTION_MODE, 'normal', '', false);
        }

        if (get_option(self::OPTION_SETTINGS, null) === null) {
            add_option(self::OPTION_SETTINGS, self::get_default_settings(), '', false);
        }

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK);
        }

        self::maybe_refresh_integrity_baseline(true);
        self::run_integrity_scan('install');
    }

    public static function deactivate() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public static function uninstall() {
        delete_option(self::OPTION_MODE);
        delete_option(self::OPTION_SETTINGS);
        delete_option(self::OPTION_EVENTS);
        delete_option(self::OPTION_INTEGRITY_STATE);
        delete_option(self::OPTION_INTEGRITY_BASELINE);
        delete_option(self::OPTION_INTEGRITY_BASELINE_VERSION);

        delete_transient(self::TRANSIENT_POLICY_CACHE);
    }

    public static function run_hourly_checks() {
        self::maybe_refresh_integrity_baseline();

        $settings = self::get_settings();

        if (!empty($settings['policy_url'])) {
            $policy = self::pull_remote_policy(false);
            if (is_wp_error($policy)) {
                self::maybe_record_throttled_event(
                    'blp_security_policy_error_notice',
                    'medium',
                    'remote_policy_fetch_failed',
                    'Remote security policy could not be fetched: ' . $policy->get_error_message()
                );
            }
        }

        $state = self::get_integrity_state();
        $last_scan = strtotime((string) ($state['last_scan_at'] ?? ''));
        if ($last_scan === false || (time() - $last_scan) >= DAY_IN_SECONDS) {
            self::run_integrity_scan('scheduled');
        }

        if (class_exists('BLP_License') && method_exists('BLP_License', 'maybe_validate_scheduled')) {
            BLP_License::maybe_validate_scheduled();
        }
    }

    public static function render_admin_notices() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $mode = self::get_mode();
        if ($mode !== 'normal') {
            $class = $mode === 'lockdown' ? 'notice notice-error' : 'notice notice-warning';
            $label = $mode === 'lockdown' ? 'LOCKDOWN' : 'READ-ONLY';
            printf(
                '<div class="%1$s"><p><strong>BioLink Pro Security:</strong> Mode is currently <strong>%2$s</strong>.</p></div>',
                esc_attr($class),
                esc_html($label)
            );
        }

        $integrity = self::get_integrity_state();
        if (($integrity['status'] ?? '') === 'tampered') {
            $count = (int) ($integrity['mismatch_count'] ?? 0);
            printf(
                '<div class="notice notice-error"><p><strong>BioLink Pro Integrity Alert:</strong> %d file mismatch(es) detected. Open the Security tab and run a full check.</p></div>',
                $count
            );
        }
    }

    public static function get_default_settings() {
        $admin_email = sanitize_email((string) get_option('admin_email', ''));

        return [
            'alerts_enabled' => 1,
            'alert_email' => $admin_email,
            'webhook_url' => '',
            'policy_url' => '',
            'policy_public_key' => '',
            'lockdown_message' => 'BioLink Pro is temporarily unavailable due to a security policy.',
            'auto_read_only_on_tamper' => 1,
            'allow_unsigned_policy' => 0,
        ];
    }

    public static function get_settings() {
        $stored = get_option(self::OPTION_SETTINGS, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        return array_merge(self::get_default_settings(), $stored);
    }

    public static function update_settings(array $raw_settings) {
        $defaults = self::get_default_settings();

        $alerts_enabled = !empty($raw_settings['alerts_enabled']) ? 1 : 0;
        $auto_read_only_on_tamper = !empty($raw_settings['auto_read_only_on_tamper']) ? 1 : 0;
        $allow_unsigned_policy = !empty($raw_settings['allow_unsigned_policy']) ? 1 : 0;

        $alert_email = sanitize_email((string) ($raw_settings['alert_email'] ?? $defaults['alert_email']));
        if ($alert_email === '') {
            $alert_email = $defaults['alert_email'];
        }

        $webhook_url = self::sanitize_https_url((string) ($raw_settings['webhook_url'] ?? ''));
        $policy_url = self::sanitize_https_url((string) ($raw_settings['policy_url'] ?? ''));

        $policy_public_key = trim((string) ($raw_settings['policy_public_key'] ?? ''));
        if (strlen($policy_public_key) > 10000) {
            $policy_public_key = substr($policy_public_key, 0, 10000);
        }

        $lockdown_message = sanitize_text_field((string) ($raw_settings['lockdown_message'] ?? $defaults['lockdown_message']));
        if ($lockdown_message === '') {
            $lockdown_message = $defaults['lockdown_message'];
        }

        $clean = [
            'alerts_enabled' => $alerts_enabled,
            'alert_email' => $alert_email,
            'webhook_url' => $webhook_url,
            'policy_url' => $policy_url,
            'policy_public_key' => $policy_public_key,
            'lockdown_message' => $lockdown_message,
            'auto_read_only_on_tamper' => $auto_read_only_on_tamper,
            'allow_unsigned_policy' => $allow_unsigned_policy,
        ];

        update_option(self::OPTION_SETTINGS, $clean, false);
        delete_transient(self::TRANSIENT_POLICY_CACHE);

        return self::get_status_snapshot();
    }

    public static function get_mode() {
        if (self::$mode_cache !== null) {
            return self::$mode_cache;
        }

        $mode = sanitize_key((string) get_option(self::OPTION_MODE, 'normal'));
        if (!in_array($mode, self::$valid_modes, true)) {
            $mode = 'normal';
        }

        self::$mode_cache = $mode;

        return $mode;
    }

    public static function is_read_only() {
        return self::get_mode() === 'read_only';
    }

    public static function is_lockdown() {
        return self::get_mode() === 'lockdown';
    }

    public static function get_lockdown_message() {
        $settings = self::get_settings();
        return (string) $settings['lockdown_message'];
    }

    public static function set_mode($mode, $reason = '', $source = 'manual') {
        $mode = sanitize_key((string) $mode);
        if (!in_array($mode, self::$valid_modes, true)) {
            return new WP_Error('invalid_security_mode', 'Invalid security mode.');
        }

        $current_mode = self::get_mode();
        if ($current_mode === $mode) {
            return self::get_status_snapshot();
        }

        update_option(self::OPTION_MODE, $mode, false);
        self::$mode_cache = $mode;

        $severity = $mode === 'lockdown' ? 'high' : 'medium';
        self::record_event(
            $severity,
            'security_mode_changed',
            sprintf('Security mode changed from %s to %s.', $current_mode, $mode),
            [
                'from' => $current_mode,
                'to' => $mode,
                'reason' => sanitize_text_field((string) $reason),
                'source' => sanitize_key((string) $source),
            ],
            $mode === 'lockdown'
        );

        return self::get_status_snapshot();
    }

    public static function is_action_allowed($action) {
        $action = sanitize_key((string) $action);
        if ($action === '') {
            return true;
        }

        if (in_array($action, self::$always_allowed_actions, true)) {
            return true;
        }

        $mode = self::get_mode();

        if ($mode === 'lockdown') {
            return false;
        }

        if ($mode === 'read_only' && in_array($action, self::$write_actions, true)) {
            return false;
        }

        return true;
    }

    public static function get_mode_block_message($action = '') {
        $action = sanitize_key((string) $action);

        if (self::is_lockdown()) {
            return 'Security lockdown is active. This action is temporarily blocked.';
        }

        if (self::is_read_only() && in_array($action, self::$write_actions, true)) {
            return 'Security read-only mode is active. Write actions are temporarily blocked.';
        }

        return '';
    }

    public static function get_status_snapshot() {
        $settings = self::get_settings();

        return [
            'mode' => self::get_mode(),
            'settings' => [
                'alertsEnabled' => (int) $settings['alerts_enabled'],
                'alertEmail' => (string) $settings['alert_email'],
                'webhookUrl' => (string) $settings['webhook_url'],
                'policyUrl' => (string) $settings['policy_url'],
                'policyPublicKey' => (string) $settings['policy_public_key'],
                'hasPolicyPublicKey' => $settings['policy_public_key'] !== '' ? 1 : 0,
                'lockdownMessage' => (string) $settings['lockdown_message'],
                'autoReadOnlyOnTamper' => (int) $settings['auto_read_only_on_tamper'],
                'allowUnsignedPolicy' => (int) $settings['allow_unsigned_policy'],
            ],
            'integrity' => self::get_integrity_state(),
            'events' => self::get_recent_events(25),
        ];
    }

    public static function get_recent_events($limit = 25) {
        $events = get_option(self::OPTION_EVENTS, []);
        if (!is_array($events)) {
            return [];
        }

        $limit = max(1, (int) $limit);
        return array_slice($events, 0, $limit);
    }

    public static function record_event($severity, $code, $message, array $context = [], $send_alert = false) {
        $allowed_severities = ['info', 'low', 'medium', 'high', 'critical'];
        $severity = sanitize_key((string) $severity);
        if (!in_array($severity, $allowed_severities, true)) {
            $severity = 'info';
        }

        $entry = [
            'time' => current_time('mysql'),
            'severity' => $severity,
            'code' => sanitize_key((string) $code),
            'message' => sanitize_text_field((string) $message),
            'context' => self::sanitize_event_context($context),
        ];

        $events = get_option(self::OPTION_EVENTS, []);
        if (!is_array($events)) {
            $events = [];
        }

        array_unshift($events, $entry);
        $events = array_slice($events, 0, 120);

        update_option(self::OPTION_EVENTS, $events, false);

        if ($send_alert || in_array($severity, ['high', 'critical'], true)) {
            self::dispatch_alert($entry);
        }

        return $entry;
    }

    public static function run_integrity_scan($trigger = 'manual') {
        self::maybe_refresh_integrity_baseline();

        $baseline = get_option(self::OPTION_INTEGRITY_BASELINE, []);
        if (!is_array($baseline) || empty($baseline['files']) || !is_array($baseline['files'])) {
            return new WP_Error('integrity_baseline_missing', 'Integrity baseline is missing.');
        }

        $current_files = self::collect_file_hashes();
        if (is_wp_error($current_files)) {
            return $current_files;
        }

        $expected_files = $baseline['files'];
        $mismatches = [];
        $critical_count = 0;

        foreach ($expected_files as $file => $expected_hash) {
            if (!isset($current_files[$file])) {
                $is_critical = self::is_critical_file($file);
                $mismatches[] = [
                    'type' => 'missing',
                    'file' => $file,
                    'critical' => $is_critical ? 1 : 0,
                    'expected' => $expected_hash,
                    'actual' => '',
                ];
                if ($is_critical) {
                    $critical_count++;
                }
                continue;
            }

            if (!hash_equals((string) $expected_hash, (string) $current_files[$file])) {
                $is_critical = self::is_critical_file($file);
                $mismatches[] = [
                    'type' => 'changed',
                    'file' => $file,
                    'critical' => $is_critical ? 1 : 0,
                    'expected' => $expected_hash,
                    'actual' => $current_files[$file],
                ];
                if ($is_critical) {
                    $critical_count++;
                }
            }
        }

        foreach ($current_files as $file => $hash) {
            if (!isset($expected_files[$file])) {
                $is_critical = self::is_critical_file($file);
                $mismatches[] = [
                    'type' => 'unexpected',
                    'file' => $file,
                    'critical' => $is_critical ? 1 : 0,
                    'expected' => '',
                    'actual' => $hash,
                ];
                if ($is_critical) {
                    $critical_count++;
                }
            }
        }

        $mismatch_count = count($mismatches);
        $state = [
            'status' => $mismatch_count > 0 ? 'tampered' : 'clean',
            'last_scan_at' => current_time('mysql'),
            'trigger' => sanitize_key((string) $trigger),
            'scanned_files' => count($current_files),
            'mismatch_count' => $mismatch_count,
            'critical_count' => $critical_count,
            'mismatches' => array_slice($mismatches, 0, 120),
            'baseline_version' => sanitize_text_field((string) ($baseline['version'] ?? BLP_VERSION)),
        ];

        update_option(self::OPTION_INTEGRITY_STATE, $state, false);

        if ($mismatch_count === 0 && self::is_developer_environment() && self::get_mode() === 'read_only') {
            self::set_mode(
                'normal',
                'Developer environment auto-reset after clean integrity scan.',
                'integrity_dev_auto'
            );
        }

        if ($mismatch_count > 0) {
            self::record_event(
                'high',
                'integrity_tamper_detected',
                sprintf('Integrity scan detected %d mismatch(es).', $mismatch_count),
                [
                    'trigger' => sanitize_key((string) $trigger),
                    'mismatch_count' => $mismatch_count,
                    'critical_count' => $critical_count,
                ],
                true
            );

            $settings = self::get_settings();
            if (
                !self::is_developer_environment() &&
                !empty($settings['auto_read_only_on_tamper']) &&
                $critical_count > 0 &&
                self::get_mode() === 'normal'
            ) {
                self::set_mode(
                    'read_only',
                    'Automatic containment after integrity mismatch detection.',
                    'integrity_auto'
                );
            }
        }

        return $state;
    }

    public static function get_integrity_state() {
        $state = get_option(self::OPTION_INTEGRITY_STATE, []);
        if (!is_array($state)) {
            $state = [];
        }

        $defaults = [
            'status' => 'unknown',
            'last_scan_at' => '',
            'trigger' => '',
            'scanned_files' => 0,
            'mismatch_count' => 0,
            'critical_count' => 0,
            'mismatches' => [],
            'baseline_version' => '',
        ];

        $state = array_merge($defaults, $state);
        if (!is_array($state['mismatches'])) {
            $state['mismatches'] = [];
        }

        return $state;
    }

    public static function maybe_refresh_integrity_baseline($force = false) {
        if (self::is_developer_environment()) {
            $force = true;
        }

        $stored_version = (string) get_option(self::OPTION_INTEGRITY_BASELINE_VERSION, '');
        $baseline = get_option(self::OPTION_INTEGRITY_BASELINE, []);

        if (!$force && $stored_version === BLP_VERSION && is_array($baseline) && !empty($baseline['files'])) {
            return;
        }

        $files = self::collect_file_hashes();
        if (is_wp_error($files)) {
            self::record_event('medium', 'integrity_baseline_failed', $files->get_error_message(), [], false);
            return;
        }

        $new_baseline = [
            'version' => BLP_VERSION,
            'created_at' => current_time('mysql'),
            'files' => $files,
        ];

        update_option(self::OPTION_INTEGRITY_BASELINE, $new_baseline, false);
        update_option(self::OPTION_INTEGRITY_BASELINE_VERSION, BLP_VERSION, false);
    }

    public static function pull_remote_policy($force = false) {
        $settings = self::get_settings();
        if (empty($settings['policy_url'])) {
            return new WP_Error('policy_not_configured', 'Remote policy URL is not configured.');
        }

        if (!$force) {
            $cached = get_transient(self::TRANSIENT_POLICY_CACHE);
            if (is_array($cached) && !empty($cached['mode'])) {
                return $cached;
            }
        }

        $request_url = add_query_arg([
            'site' => home_url('/'),
            'host' => self::get_site_host(),
            'version' => BLP_VERSION,
            'instance' => self::get_instance_id_for_policy(),
        ], $settings['policy_url']);

        $response = wp_safe_remote_get($request_url, [
            'timeout' => 12,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('policy_http_error', 'Remote policy endpoint returned HTTP ' . $code . '.');
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            return new WP_Error('policy_invalid_json', 'Remote policy response is not valid JSON.');
        }

        $policy = self::parse_remote_policy($decoded, $settings);
        if (is_wp_error($policy)) {
            return $policy;
        }

        self::apply_remote_policy($policy);

        set_transient(self::TRANSIENT_POLICY_CACHE, $policy, 5 * MINUTE_IN_SECONDS);

        return $policy;
    }

    private static function parse_remote_policy(array $decoded, array $settings) {
        $mode = sanitize_key((string) ($decoded['mode'] ?? ''));
        if (!in_array($mode, self::$valid_modes, true)) {
            return new WP_Error('policy_invalid_mode', 'Remote policy mode is invalid.');
        }

        $reason = sanitize_text_field((string) ($decoded['reason'] ?? 'Remote policy update'));
        $issued_at = sanitize_text_field((string) ($decoded['issued_at'] ?? ''));
        $expires_at = sanitize_text_field((string) ($decoded['expires_at'] ?? ''));

        if ($expires_at !== '') {
            $expires_ts = strtotime($expires_at);
            if ($expires_ts !== false && $expires_ts < time()) {
                return new WP_Error('policy_expired', 'Remote policy is expired.');
            }
        }

        $signature = trim((string) ($decoded['signature'] ?? ''));
        $payload = (string) ($decoded['payload'] ?? wp_json_encode([
            'mode' => $mode,
            'reason' => $reason,
            'issued_at' => $issued_at,
            'expires_at' => $expires_at,
        ], JSON_UNESCAPED_SLASHES));

        if ($signature !== '') {
            if (!self::verify_payload_signature($payload, $signature, (string) $settings['policy_public_key'])) {
                return new WP_Error('policy_signature_invalid', 'Remote policy signature could not be verified.');
            }
        } elseif (empty($settings['allow_unsigned_policy'])) {
            return new WP_Error('policy_unsigned_rejected', 'Unsigned remote policy was rejected.');
        }

        return [
            'mode' => $mode,
            'reason' => $reason,
            'issued_at' => $issued_at,
            'expires_at' => $expires_at,
        ];
    }

    private static function verify_payload_signature($payload, $signature_b64, $public_key_raw) {
        if (!function_exists('openssl_verify') || !function_exists('openssl_pkey_get_public')) {
            return false;
        }

        $public_key = self::normalize_public_key($public_key_raw);
        if ($public_key === '') {
            return false;
        }

        $key_resource = openssl_pkey_get_public($public_key);
        if (!$key_resource) {
            return false;
        }

        $signature = base64_decode($signature_b64, true);
        if ($signature === false) {
            return false;
        }

        $verified = openssl_verify((string) $payload, $signature, $key_resource, OPENSSL_ALGO_SHA256);

        return $verified === 1;
    }

    private static function normalize_public_key($key_raw) {
        $key_raw = trim((string) $key_raw);
        if ($key_raw === '') {
            return '';
        }

        if (strpos($key_raw, 'BEGIN PUBLIC KEY') !== false) {
            return $key_raw;
        }

        $wrapped = chunk_split(preg_replace('/\s+/', '', $key_raw), 64, "\n");
        return "-----BEGIN PUBLIC KEY-----\n" . $wrapped . "-----END PUBLIC KEY-----";
    }

    private static function apply_remote_policy(array $policy) {
        $target_mode = sanitize_key((string) ($policy['mode'] ?? 'normal'));
        $reason = sanitize_text_field((string) ($policy['reason'] ?? 'Remote policy update'));

        if (!in_array($target_mode, self::$valid_modes, true)) {
            return;
        }

        if ($target_mode !== self::get_mode()) {
            self::set_mode($target_mode, $reason, 'remote_policy');
        }
    }

    private static function collect_file_hashes() {
        $plugin_root = untrailingslashit(BLP_PLUGIN_DIR);
        if ($plugin_root === '' || !is_dir($plugin_root)) {
            return new WP_Error('integrity_root_missing', 'Plugin root directory not found.');
        }

        $files = [];

        $directory_iterator = new RecursiveDirectoryIterator($plugin_root, FilesystemIterator::SKIP_DOTS);
        $filter_iterator = new RecursiveCallbackFilterIterator(
            $directory_iterator,
            static function ($current, $key, $iterator) use ($plugin_root) {
                /** @var SplFileInfo $current */
                $relative_path = str_replace('\\', '/', ltrim(str_replace($plugin_root, '', $current->getPathname()), '\\/'));
                $relative_path = ltrim($relative_path, '/');

                if ($current->isDir()) {
                    if ($relative_path === '.git' || strpos($relative_path, '.git/') === 0) {
                        return false;
                    }

                    if ($relative_path !== '' && strpos($relative_path, '.') === 0) {
                        return false;
                    }
                }

                return true;
            }
        );

        $iterator = new RecursiveIteratorIterator($filter_iterator, RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iterator as $node) {
            if (!$node->isFile()) {
                continue;
            }

            $absolute_path = $node->getPathname();
            $relative_path = str_replace('\\', '/', ltrim(str_replace($plugin_root, '', $absolute_path), '\\/'));

            if ($relative_path === '') {
                continue;
            }

            if (self::should_ignore_file($relative_path)) {
                continue;
            }

            $hash = @hash_file('sha256', $absolute_path);
            if ($hash === false) {
                return new WP_Error('integrity_hash_failed', 'Could not hash file: ' . $relative_path);
            }

            $files[$relative_path] = $hash;
        }

        ksort($files);

        return $files;
    }

    private static function should_ignore_file($relative_path) {
        $relative_path = ltrim((string) $relative_path, '/');

        if ($relative_path === '') {
            return true;
        }

        if (strpos('/' . $relative_path, '/.') !== false) {
            return true;
        }

        if (strpos($relative_path, '.git/') === 0) {
            return true;
        }

        if (strpos($relative_path, '.git\\') === 0) {
            return true;
        }

        return false;
    }

    private static function is_critical_file($relative_path) {
        $relative_path = strtolower((string) $relative_path);

        if ($relative_path === 'biolink-pro.php') {
            return true;
        }

        return pathinfo($relative_path, PATHINFO_EXTENSION) === 'php';
    }

    private static function sanitize_event_context(array $context) {
        $clean = [];

        foreach ($context as $key => $value) {
            $safe_key = sanitize_key((string) $key);
            if ($safe_key === '') {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $clean[$safe_key] = sanitize_text_field((string) $value);
                continue;
            }

            $clean[$safe_key] = sanitize_text_field(wp_json_encode($value));
        }

        return $clean;
    }

    private static function dispatch_alert(array $entry) {
        $settings = self::get_settings();
        if (empty($settings['alerts_enabled'])) {
            return;
        }

        $throttle_key = 'blp_security_alert_' . md5((string) $entry['code'] . '|' . (string) $entry['severity']);
        if (get_transient($throttle_key)) {
            return;
        }
        set_transient($throttle_key, 1, 5 * MINUTE_IN_SECONDS);

        $subject = sprintf('[BioLink Pro Security][%s] %s', strtoupper((string) $entry['severity']), (string) $entry['code']);
        $message = "Time: {$entry['time']}\n";
        $message .= 'Site: ' . home_url('/') . "\n";
        $message .= "Severity: {$entry['severity']}\n";
        $message .= "Code: {$entry['code']}\n";
        $message .= "Message: {$entry['message']}\n";

        if (!empty($entry['context'])) {
            $message .= "\nContext:\n" . print_r($entry['context'], true);
        }

        $to = sanitize_email((string) $settings['alert_email']);
        if ($to !== '') {
            wp_mail($to, $subject, $message);
        }

        if (!empty($settings['webhook_url'])) {
            $payload = [
                'plugin' => 'biolink-pro',
                'site' => home_url('/'),
                'event' => $entry,
            ];

            wp_safe_remote_post($settings['webhook_url'], [
                'timeout' => 8,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($payload),
            ]);
        }
    }

    private static function maybe_record_throttled_event($throttle_key, $severity, $code, $message) {
        if (get_transient($throttle_key)) {
            return;
        }

        set_transient($throttle_key, 1, HOUR_IN_SECONDS);
        self::record_event($severity, $code, $message, [], false);
    }

    private static function is_developer_environment() {
        if (defined('BLP_ALLOW_DEV_LICENSE') && BLP_ALLOW_DEV_LICENSE) {
            return true;
        }

        if (function_exists('wp_get_environment_type')) {
            $environment_type = wp_get_environment_type();
            if (in_array($environment_type, ['local', 'development', 'staging'], true)) {
                return true;
            }
        }

        $host = self::get_site_host();
        if ($host === '') {
            return false;
        }

        if ($host === 'localhost' || substr($host, -6) === '.local' || substr($host, -5) === '.test') {
            return true;
        }

        return (bool) filter_var($host, FILTER_VALIDATE_IP);
    }

    private static function sanitize_https_url($raw_url) {
        $url = esc_url_raw(trim((string) $raw_url));
        if ($url === '') {
            return '';
        }

        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    private static function get_site_host() {
        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        return is_string($host) ? strtolower($host) : '';
    }

    private static function get_instance_id_for_policy() {
        if (class_exists('BLP_License') && method_exists('BLP_License', 'get_instance_id')) {
            return (string) BLP_License::get_instance_id();
        }

        return wp_hash(home_url('/'));
    }
}
