<?php

namespace ElementPack\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


/**
 * Google OAuth Handler for Element Pack
 */
class Google_OAuth_Handler {
    
    public $redirect_uri;
    
    public function __construct() {
        //$this->redirect_uri = admin_url('admin-ajax.php?action=ep_handle_google_callback');
        $this->redirect_uri = admin_url('admin.php?page=element_pack_options');
        add_action('wp_ajax_ep_save_google_oauth', array($this, 'save_google_oauth'));
        add_action('wp_ajax_ep_disconnect_google_oauth', array($this, 'disconnect_google_oauth'));
        add_action('wp_ajax_ep_exchange_google_code', array($this, 'exchange_google_code'));
        // New server-side OAuth flow
        add_action('wp_ajax_ep_get_google_oauth_url', array($this, 'get_google_oauth_url'));

        add_action('admin_init', array($this, 'handle_google_callback'));
        //add_action('wp_ajax_nopriv_ep_handle_google_callback', array($this, 'handle_google_callback'));
        //add_action('wp_ajax_ep_handle_google_callback', array($this, 'handle_google_callback'));
    }
    
    /**
     * Generate OAuth URL for server-side flow
     */
    public function get_google_oauth_url() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'], 'ep_google_oauth_nonce')) {
            wp_die('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Get API settings
        $api_settings = get_option('element_pack_api_settings', array());
        $client_id = isset($api_settings['google_sheets_client_id']) ? $api_settings['google_sheets_client_id'] : '';
        
        if (empty($client_id)) {
            wp_die('Missing Google Client ID in Element Pack settings');
        }
        
        // Build OAuth URL
        $redirect_uri = $this->redirect_uri;
        $state = wp_create_nonce('ep_google_oauth_state');
        
        $oauth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query(array(
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state
        ));
        
        // Store state for verification
        set_transient('ep_oauth_state_' . get_current_user_id(), $state, 600); // 10 minutes
        
        // Redirect to Google OAuth
        wp_redirect($oauth_url);
        exit;
    }
    
    /**
     * Handle Google OAuth callback
     */
    public function handle_google_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'element_pack_options') {
            return;
        }

        if (!isset($_GET['code']) || !isset($_GET['state'])) {
            return;
        }
        
        $state = sanitize_text_field($_GET['state']);
        $stored_state = get_transient('ep_oauth_state_' . get_current_user_id());
        
        if (!$stored_state || $state !== $stored_state) {
            wp_die('Invalid state parameter');
        }
        
        // Clean up state
        delete_transient('ep_oauth_state_' . get_current_user_id());
        
        $auth_code = sanitize_text_field($_GET['code']);
        
        // Get API settings
        $api_settings = get_option('element_pack_api_settings', array());
        $client_id = isset($api_settings['google_sheets_client_id']) ? $api_settings['google_sheets_client_id'] : '';
        $client_secret = isset($api_settings['google_sheets_client_secret']) ? $api_settings['google_sheets_client_secret'] : '';
        
        if (empty($client_id) || empty($client_secret)) {
            wp_die('Missing Google client credentials in Element Pack settings');
        }
        
        // Exchange code for tokens
        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code' => $auth_code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $this->redirect_uri,
                'grant_type' => 'authorization_code'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_die('Token exchange failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_die('Token exchange error (' . $response_code . '): ' . $response_body);
        }
        
        $token_data = json_decode($response_body, true);
        
        if (empty($token_data['access_token'])) {
            wp_die('No access token in token exchange response');
        }
        
        $expires_in = isset($token_data['expires_in']) ? intval($token_data['expires_in']) : 3600;
        $expires_at = time() + $expires_in;
        
        // Save OAuth data including refresh token
        $oauth_data = array(
            'access_token' => $token_data['access_token'],
            'refresh_token' => isset($token_data['refresh_token']) ? $token_data['refresh_token'] : '',
            'expires_in' => $expires_in,
            'expires_at' => $expires_at,
            'created_at' => time()
        );
        
        update_option('element_pack_google_oauth_data', $oauth_data);
        
        // Redirect back to settings page with success message
        wp_redirect(admin_url('admin.php?page=element_pack_options#element_pack_api_settings'));
        exit;
    }
    
    /**
     * Get complete token data including expiration info
     */
    public static function get_token_data() {
        $oauth_data = get_option('element_pack_google_oauth_data', array());
        
        if (empty($oauth_data['access_token'])) {
            return false;
        }
        
        return $oauth_data;
    }
    
    /**
     * Check if OAuth is connected
     */
    public static function is_oauth_connected() {
        $oauth_data = get_option('element_pack_google_oauth_data', array());
        
        if (empty($oauth_data['access_token'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get OAuth access token
     */
    public static function get_access_token() {
        if (!self::is_oauth_connected()) {
            return false;
        }
        
        $oauth_data = get_option('element_pack_google_oauth_data', array());
        return $oauth_data['access_token'];
    }
    
    /**
     * Refresh OAuth token using refresh token
     */
    public static function refresh_token() {
        $oauth_data = get_option('element_pack_google_oauth_data', array());
        
        if (empty($oauth_data['refresh_token'])) {
            return false;
        }
        
        // Get API settings for client credentials
        $api_settings = get_option('element_pack_api_settings', array());
        $client_id = isset($api_settings['google_sheets_client_id']) ? $api_settings['google_sheets_client_id'] : '';
        $client_secret = isset($api_settings['google_sheets_client_secret']) ? $api_settings['google_sheets_client_secret'] : '';
        
        if (empty($client_id) || empty($client_secret)) {
            return false;
        }
        
        // Make refresh token request
        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $oauth_data['refresh_token'],
                'grant_type' => 'refresh_token'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return false;
        }
        
        $token_data = json_decode($response_body, true);
        
        if (empty($token_data['access_token'])) {
            return false;
        }
        
        // Update OAuth data with new token
        $oauth_data['access_token'] = $token_data['access_token'];
        $oauth_data['expires_in'] = isset($token_data['expires_in']) ? $token_data['expires_in'] : 3600;
        $oauth_data['expires_at'] = time() + $oauth_data['expires_in'];
        
        // Keep existing refresh token if new one not provided
        if (isset($token_data['refresh_token'])) {
            $oauth_data['refresh_token'] = $token_data['refresh_token'];
        }
        
        update_option('element_pack_google_oauth_data', $oauth_data);
        
        return true;
    }
    
    /**
     * Disconnect Google OAuth
     */
    public function disconnect_google_oauth() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ep_google_oauth_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get current OAuth data
        $oauth_data = get_option('element_pack_google_oauth_data', array());
        
        // Revoke the access token if available
        if (!empty($oauth_data['access_token'])) {
            $this->revoke_google_token($oauth_data['access_token']);
        }
        
        // Delete OAuth data
        delete_option('element_pack_google_oauth_data');
        
        wp_send_json_success('OAuth connection disconnected successfully');
    }
    
    /**
     * Revoke Google token
     */
    private function revoke_google_token($access_token) {
        wp_remote_post('https://oauth2.googleapis.com/revoke', array(
            'body' => array(
                'token' => $access_token
            ),
            'timeout' => 10
        ));
    }
}

// Initialize the handler
new Google_OAuth_Handler();