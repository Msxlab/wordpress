<?php

namespace MetForm_Pro\Core\Integrations\Dropbox;

defined( 'ABSPATH' ) || exit;

class Dropbox_Access_Token {
	public $redirect_uri; 

	public $dropbox_client_id;

	public $dropbox_client_secret;

	public function __construct() {

		$settings = \MetForm\Core\Admin\Base::instance()->get_settings_option();
        $this->dropbox_client_id     = isset($settings['mf_dropbox_app_id'])
            ? sanitize_text_field($settings['mf_dropbox_app_id'])
            : '';

        $this->dropbox_client_secret = isset($settings['mf_dropbox_app_secret'])
            ? sanitize_text_field($settings['mf_dropbox_app_secret'])
            : '';

        $this->redirect_uri = esc_url_raw(
            admin_url('admin.php?page=metform-menu-settings')
        );
	}
    public function get_access_token() {

		if (!isset($_GET['code'])) {
            return false;
        }
        $code = sanitize_text_field($_GET['code']);

		$url = 'https://api.dropboxapi.com/oauth2/token';

		$params = array(
			"code" => $code,
			"client_id" => $this->dropbox_client_id,
			"client_secret" => $this->dropbox_client_secret,
			"redirect_uri" => $this->redirect_uri,
			"grant_type" => "authorization_code"
		);

		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'body'        => $params
			)
		);
		if ( is_wp_error($response) or isset(json_decode($response['body'], true)['error'])) {
			return false;
		}
		return $response;
	}
    public function get_code() {
		$url = "https://www.dropbox.com/oauth2/authorize";

		$params = array(
			"response_type"     => "code",
			"client_id"         => $this->dropbox_client_id,
			"redirect_uri"      => $this->redirect_uri,
			"token_access_type" => "offline" // Dropbox uses token_access_type, not access_type
		);

		$request_to = $url . '?' . http_build_query($params);
        return esc_url_raw($request_to);
	}
}