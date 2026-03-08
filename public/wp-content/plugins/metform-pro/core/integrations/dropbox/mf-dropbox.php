<?php

namespace MetForm_Pro\Core\Integrations\Dropbox;

use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

class MF_Dropbox {

    use Singleton;

    public $dropbox_client_id;

	public $dropbox_client_secret;

    public function __construct()
    {
        $settings = \MetForm\Core\Admin\Base::instance()->get_settings_option();
        $this->dropbox_client_id = isset($settings['mf_dropbox_app_id']) ? $settings['mf_dropbox_app_id'] : '';
        $this->dropbox_client_secret = isset($settings['mf_dropbox_app_secret']) ? $settings['mf_dropbox_app_secret'] : '';
    }
    // Get valid access token, refresh if expired
    public function token()
    {
        if (!get_transient('mf_dropbox_token')) {
           
           return $this->get_new_token();
        }
        return get_option('mf_dropbox_access_token') ? json_decode(get_option('mf_dropbox_access_token')) : false;
    }
    // Get new access token using refresh token
    private function get_new_token(){

        $arr_token = get_option('mf_dropbox_access_token') ? json_decode(get_option('mf_dropbox_access_token')) : false;
        if($arr_token == false){
            return false;
        }

        $url = 'https://api.dropbox.com/oauth2/token';

        $params = array(
            "grant_type" => "refresh_token",
            "refresh_token" => $arr_token->refresh_token,
            "client_id" => $this->dropbox_client_id,
            "client_secret" => $this->dropbox_client_secret,
        );

        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'body' => $params
        ));

        if (is_wp_error($response) || isset(json_decode($response['body'], true)['error'])) {
            return false;
        }

        $data = (array) json_decode($response['body']);
        
        $data['refresh_token'] = $arr_token->refresh_token;
        update_option('mf_dropbox_access_token', json_encode($data));

        set_transient('mf_dropbox_token', $data, $data['expires_in'] - 20);
        $arr_token = json_decode(get_option('mf_dropbox_access_token'));

        if ($arr_token) {
            return $arr_token;
        } else {
            return false;
        }
    }

    // Get all Dropbox folders
    public function get_all_dropbox_folders( ) {    
        // dropbox access token
        $arr_token = $this->token();
        if($arr_token == false) {
            return [];
        }
        $access_token = $arr_token->access_token;
        $response = wp_remote_post(
            "https://api.dropboxapi.com/2/files/list_folder",
            [
                'headers' => [
                    'Authorization' => "Bearer $access_token",
                    'Content-Type'  => 'application/json'
                ],
                'body' => json_encode([
                    'path' => '',
                    'recursive' => false,
                    'include_deleted' => false
                ]),
                'timeout' =>  $arr_token->expires_in
            ]
        );
        

        if ( is_wp_error( $response ) ) return [];

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['entries'] ) ) return [];
        
        $folders = [];
        foreach ( $data['entries'] as $entry ) {
            // Only include folders (not files)
            if ( isset($entry['.tag']) && $entry['.tag'] === 'folder' ) {
                $folders[] = [
                    'id'   => $entry['id'],
                    'name' => $entry['name'],
                    'path' => $entry['path_display'],
                ];
            }
        }

        return $folders;
    }

    /**
     * Upload file to Dropbox
     * 
     * @param string $file_path Local file path
     * @param string $dropbox_folder_path Dropbox folder path (e.g., "/My Folder")
     * @param string $file_name File name for Dropbox
     * @return array|bool Upload result or false on failure
     */
    public function upload_file($file_path, $dropbox_folder_path, $file_name) {
        $arr_token = $this->token();
        if ($arr_token == false || !isset($arr_token->access_token)) {
            return false;
        }
        
        $access_token = sanitize_text_field($arr_token->access_token);
        //File validation
        $file_path = wp_normalize_path($file_path);
        // Read file contents
        if (!file_exists($file_path) || !is_file($file_path) || !is_readable($file_path)) {
            return false;
        }
        //File size validation (Dropbox files/upload limit = 150MB)
        $file_size = filesize($file_path);
        if ($file_size === false || $file_size > 150 * 1024 * 1024) {
            return false;
        }
        
        $file_contents = file_get_contents($file_path);
        if ($file_contents === false) {
            return false;
        }
        
        // Sanitize folder path and file name
        $dropbox_folder_path = trim($dropbox_folder_path, '/');
        $file_name = sanitize_file_name($file_name);
        
        // Construct full Dropbox path
        $dropbox_path = '/' . $dropbox_folder_path . '/' . $file_name;
        
        // Upload to Dropbox using files/upload API
        $response = wp_remote_post(
            'https://content.dropboxapi.com/2/files/upload',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/octet-stream',
                    'Dropbox-API-Arg' => json_encode([
                        'path' => $dropbox_path,
                        'mode' => 'add',
                        'autorename' => true,
                        'mute' => false
                    ])
                ],
                'body' => $file_contents,
                'timeout' => 60
            ]
        );
        if (is_wp_error($response)) {
            return false;
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($result['error'])) {
            return false;
        }
        
        return $result;
    }
    
}