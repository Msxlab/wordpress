<?php
defined('ABSPATH') || exit;

class BLP_License {

    const OPTION_DATA = 'blp_license_data';
    const OPTION_SETTINGS = 'blp_license_settings';

    public static function init_hooks() {
        // Reserved for future plugin-side hooks.
    }

    public static function install() {
        $data = get_option(self::OPTION_DATA, null);
        if (!is_array($data)) {
            add_option(self::OPTION_DATA, self::get_default_data(), '', false);
        } else {
            self::ensure_data_defaults($data);
        }

        $settings = get_option(self::OPTION_SETTINGS, null);
        if (!is_array($settings)) {
            add_option(self::OPTION_SETTINGS, self::get_default_settings(), '', false);
        } else {
            self::ensure_settings_defaults($settings);
        }
    }

    public static function uninstall() {
        delete_option(self::OPTION_DATA);
        delete_option(self::OPTION_SETTINGS);
    }

    public static function get_default_data() {
        return [
            'key' => '',
            'status' => 'inactive',
            'message' => 'No license key configured.',
            'expires_at' => '',
            'last_check' => '',
            'domain' => self::get_domain(),
            'instance_id' => wp_generate_uuid4(),
            'last_error' => '',
        ];
    }

    public static function get_default_settings() {
        return [
            'api_base_url' => '',
            'product_id' => 'biolink-pro',
            'timeout' => 12,
            'verify_ssl' => 1,
        ];
    }

    public static function get_data() {
        $stored = get_option(self::OPTION_DATA, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        $merged = array_merge(self::get_default_data(), $stored);
        if ($merged['instance_id'] === '') {
            $merged['instance_id'] = wp_generate_uuid4();
            update_option(self::OPTION_DATA, $merged, false);
        }

        return $merged;
    }

    public static function get_settings() {
        $stored = get_option(self::OPTION_SETTINGS, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        return array_merge(self::get_default_settings(), $stored);
    }

    public static function update_settings(array $raw_settings) {
        $api_base_url = esc_url_raw(trim((string) ($raw_settings['api_base_url'] ?? '')));
        if ($api_base_url !== '') {
            $scheme = strtolower((string) wp_parse_url($api_base_url, PHP_URL_SCHEME));
            if (!in_array($scheme, ['http', 'https'], true)) {
                $api_base_url = '';
            }
        }

        $product_id = sanitize_key((string) ($raw_settings['product_id'] ?? 'biolink-pro'));
        if ($product_id === '') {
            $product_id = 'biolink-pro';
        }

        $timeout = (int) ($raw_settings['timeout'] ?? 12);
        $timeout = max(3, min(30, $timeout));

        $verify_ssl = !empty($raw_settings['verify_ssl']) ? 1 : 0;

        $clean = [
            'api_base_url' => $api_base_url,
            'product_id' => $product_id,
            'timeout' => $timeout,
            'verify_ssl' => $verify_ssl,
        ];

        update_option(self::OPTION_SETTINGS, $clean, false);

        return self::get_status_snapshot();
    }

    public static function save_license_key($raw_key) {
        $key = self::normalize_license_key($raw_key);

        $data = self::get_data();
        $data['key'] = $key;
        $data['status'] = $key === '' ? 'inactive' : 'pending';
        $data['message'] = $key === ''
            ? 'License key removed.'
            : 'License key saved. Activate to verify this domain.';
        $data['last_error'] = '';
        $data['domain'] = self::get_domain();

        update_option(self::OPTION_DATA, $data, false);

        return self::get_status_snapshot();
    }

    public static function activate_license() {
        $data = self::get_data();
        if (empty($data['key'])) {
            return new WP_Error('license_key_missing', 'License key is required.');
        }

        if (self::maybe_apply_developer_license($data['key'])) {
            return self::get_status_snapshot();
        }

        $payload = self::build_payload(['license_key' => $data['key']]);
        $response = self::request('licenses/activate', $payload);

        if (is_wp_error($response)) {
            self::apply_error_state($response, $data['status'] === 'active');
            return $response;
        }

        self::apply_remote_state($response, 'activate');

        return self::get_status_snapshot();
    }

    public static function validate_license($force = true) {
        $data = self::get_data();
        if (empty($data['key'])) {
            return new WP_Error('license_key_missing', 'License key is not set.');
        }

        if (!$force && !self::is_validation_due($data['last_check'])) {
            return self::get_status_snapshot();
        }

        if (self::maybe_apply_developer_license($data['key'])) {
            return self::get_status_snapshot();
        }

        $payload = self::build_payload(['license_key' => $data['key']]);
        $response = self::request('licenses/validate', $payload);

        if (is_wp_error($response)) {
            self::apply_error_state($response, $data['status'] === 'active');
            return $response;
        }

        self::apply_remote_state($response, 'validate');

        return self::get_status_snapshot();
    }

    public static function deactivate_license() {
        $data = self::get_data();

        if (empty($data['key'])) {
            $data['status'] = 'inactive';
            $data['message'] = 'License is already inactive.';
            $data['last_check'] = current_time('mysql');
            update_option(self::OPTION_DATA, $data, false);
            return self::get_status_snapshot();
        }

        if (self::is_developer_key_allowed($data['key'])) {
            $data['status'] = 'inactive';
            $data['message'] = 'Developer key deactivated locally.';
            $data['last_check'] = current_time('mysql');
            $data['last_error'] = '';
            update_option(self::OPTION_DATA, $data, false);
            return self::get_status_snapshot();
        }

        $payload = self::build_payload(['license_key' => $data['key']]);
        $response = self::request('licenses/deactivate', $payload);

        if (is_wp_error($response)) {
            self::apply_error_state($response, false);
            return $response;
        }

        $data['status'] = 'inactive';
        $data['message'] = sanitize_text_field((string) ($response['message'] ?? 'License deactivated.'));
        $data['last_check'] = current_time('mysql');
        $data['last_error'] = '';
        update_option(self::OPTION_DATA, $data, false);

        return self::get_status_snapshot();
    }

    public static function maybe_validate_scheduled() {
        $data = self::get_data();
        if (empty($data['key'])) {
            return;
        }

        if (!self::is_validation_due($data['last_check'])) {
            return;
        }

        self::validate_license(false);
    }

    public static function get_status_snapshot() {
        $data = self::get_data();
        $settings = self::get_settings();

        return [
            'status' => (string) $data['status'],
            'message' => (string) $data['message'],
            'expiresAt' => (string) $data['expires_at'],
            'lastCheck' => (string) $data['last_check'],
            'domain' => (string) $data['domain'],
            'instanceId' => (string) $data['instance_id'],
            'lastError' => (string) $data['last_error'],
            'hasKey' => $data['key'] !== '' ? 1 : 0,
            'maskedKey' => self::mask_license_key((string) $data['key']),
            'settings' => [
                'apiBaseUrl' => (string) $settings['api_base_url'],
                'productId' => (string) $settings['product_id'],
                'timeout' => (int) $settings['timeout'],
                'verifySsl' => (int) $settings['verify_ssl'],
            ],
        ];
    }

    public static function get_instance_id() {
        $data = self::get_data();
        return (string) ($data['instance_id'] ?? '');
    }

    private static function ensure_data_defaults(array $data) {
        $merged = array_merge(self::get_default_data(), $data);
        if ($merged !== $data) {
            update_option(self::OPTION_DATA, $merged, false);
        }
    }

    private static function ensure_settings_defaults(array $settings) {
        $merged = array_merge(self::get_default_settings(), $settings);
        if ($merged !== $settings) {
            update_option(self::OPTION_SETTINGS, $merged, false);
        }
    }

    private static function apply_remote_state(array $response, $action) {
        $data = self::get_data();

        $allowed_statuses = [
            'active',
            'inactive',
            'pending',
            'grace',
            'expired',
            'invalid',
            'revoked',
            'error',
        ];

        $status = sanitize_key((string) ($response['status'] ?? 'error'));
        if (!in_array($status, $allowed_statuses, true)) {
            if (isset($response['valid'])) {
                $status = !empty($response['valid']) ? 'active' : 'invalid';
            } else {
                $status = 'error';
            }
        }

        $data['status'] = $status;
        $data['message'] = sanitize_text_field((string) ($response['message'] ?? self::default_status_message($status, $action)));
        $data['expires_at'] = sanitize_text_field((string) ($response['expires_at'] ?? ''));
        $data['last_check'] = current_time('mysql');
        $data['last_error'] = '';
        $data['domain'] = self::get_domain();

        if (!empty($response['instance_id'])) {
            $data['instance_id'] = sanitize_text_field((string) $response['instance_id']);
        }

        update_option(self::OPTION_DATA, $data, false);

        if (in_array($status, ['invalid', 'expired', 'revoked'], true) && class_exists('BLP_Security')) {
            BLP_Security::record_event(
                'high',
                'license_state_not_valid',
                sprintf('License status became %s during %s.', $status, sanitize_key((string) $action)),
                ['status' => $status, 'action' => sanitize_key((string) $action)],
                true
            );
        }
    }

    private static function apply_error_state(WP_Error $error, $fallback_to_grace) {
        $data = self::get_data();

        $data['status'] = $fallback_to_grace ? 'grace' : 'error';
        $data['message'] = sanitize_text_field((string) $error->get_error_message());
        $data['last_error'] = sanitize_text_field((string) $error->get_error_message());
        $data['last_check'] = current_time('mysql');

        update_option(self::OPTION_DATA, $data, false);

        if (class_exists('BLP_Security')) {
            BLP_Security::record_event(
                'medium',
                'license_remote_error',
                'License endpoint request failed: ' . $error->get_error_message(),
                ['code' => sanitize_key((string) $error->get_error_code())],
                false
            );
        }
    }

    private static function request($endpoint, array $payload) {
        $settings = self::get_settings();

        $base_url = trim((string) $settings['api_base_url']);
        if ($base_url === '') {
            return new WP_Error('license_api_not_configured', 'License API URL is not configured yet.');
        }

        $endpoint = ltrim((string) $endpoint, '/');
        $url = trailingslashit($base_url) . $endpoint;

        $response = wp_safe_remote_post($url, [
            'timeout' => (int) $settings['timeout'],
            'sslverify' => !empty($settings['verify_ssl']),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        if ($code < 200 || $code >= 300) {
            $message = sanitize_text_field((string) ($decoded['message'] ?? 'License API returned HTTP ' . $code . '.'));
            return new WP_Error('license_api_http_error', $message);
        }

        return $decoded;
    }

    private static function build_payload(array $extra = []) {
        $settings = self::get_settings();
        $data = self::get_data();

        return array_merge([
            'product_id' => (string) $settings['product_id'],
            'domain' => self::get_domain(),
            'instance_id' => (string) $data['instance_id'],
            'site_url' => home_url('/'),
            'plugin_version' => BLP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
        ], $extra);
    }

    private static function default_status_message($status, $action) {
        $status = sanitize_key((string) $status);

        if ($status === 'active') {
            return 'License is active.';
        }

        if ($status === 'inactive') {
            return 'License is inactive.';
        }

        if ($status === 'grace') {
            return 'License validation entered grace period due to temporary network/server issues.';
        }

        if ($status === 'invalid') {
            return 'License key is invalid for this domain.';
        }

        if ($status === 'expired') {
            return 'License has expired.';
        }

        if ($status === 'revoked') {
            return 'License has been revoked.';
        }

        return sprintf('License %s response received with status: %s.', sanitize_key((string) $action), $status);
    }

    private static function get_domain() {
        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        if (!is_string($host)) {
            return '';
        }

        return strtolower($host);
    }

    private static function is_validation_due($last_check) {
        if (empty($last_check)) {
            return true;
        }

        $last = strtotime((string) $last_check);
        if ($last === false) {
            return true;
        }

        return (time() - $last) >= (12 * HOUR_IN_SECONDS);
    }

    private static function maybe_apply_developer_license($license_key) {
        if (!self::is_developer_key_allowed($license_key)) {
            return false;
        }

        $data = self::get_data();
        $data['status'] = 'active';
        $data['message'] = 'Developer key active (local testing mode).';
        $data['expires_at'] = '';
        $data['last_check'] = current_time('mysql');
        $data['last_error'] = '';
        $data['domain'] = self::get_domain();

        update_option(self::OPTION_DATA, $data, false);

        return true;
    }

    private static function is_developer_key_allowed($license_key) {
        $license_key = self::normalize_license_key($license_key);
        if ($license_key === '' || !self::is_developer_environment()) {
            return false;
        }

        $configured_key = '';
        if (defined('BLP_DEVELOPER_KEY')) {
            $configured_key = self::normalize_license_key((string) BLP_DEVELOPER_KEY);
        }

        if ($configured_key !== '') {
            return hash_equals($configured_key, $license_key);
        }

        return strpos($license_key, 'DEV-') === 0;
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

        $domain = self::get_domain();
        if ($domain === '') {
            return false;
        }

        if ($domain === 'localhost' || substr($domain, -6) === '.local' || substr($domain, -5) === '.test') {
            return true;
        }

        return (bool) filter_var($domain, FILTER_VALIDATE_IP);
    }

    private static function normalize_license_key($raw_key) {
        $key = strtoupper(trim((string) $raw_key));
        $key = preg_replace('/\s+/', '', $key);
        return sanitize_text_field((string) $key);
    }

    private static function mask_license_key($key) {
        $key = trim((string) $key);
        if ($key === '') {
            return '';
        }

        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, 4) . str_repeat('*', max(0, strlen($key) - 8)) . substr($key, -4);
    }
}
