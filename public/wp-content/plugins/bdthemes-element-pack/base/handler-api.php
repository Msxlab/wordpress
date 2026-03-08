<?php 
namespace ElementPack\Base;

defined( 'ABSPATH' ) || exit;

/**
 * Base Handler API class for Element Pack
 * Provides a standardized way to handle REST API endpoints
 */
class Handler_Api {

	public $prefix  = '';
	public $param   = '';
	public $request = null;

	public function __construct() {
		$this->config();
		$this->init();
	}

	/**
	 * Configuration method to be overridden by child classes
	 * Set the prefix and param properties here
	 */
	public function config() {
		// Override this method in child classes
	}

	/**
	 * Initialize the REST API route registration
	 */
	public function init() {
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					untrailingslashit( 'element-pack/v1/' . $this->prefix ),
					'/(?P<action>\w+)/' . ltrim( $this->param, '/' ),
					array(
						'methods'             => \WP_REST_Server::ALLMETHODS,
						'callback'            => array( $this, 'callback' ),
						'permission_callback' => '__return_true', 
						// all permissions are implemented inside the callback action
					)
				);
			}
		);
	}

	/**
	 * Main callback handler for REST API requests
	 * Routes requests to appropriate method based on HTTP method and action
	 *
	 * @param \WP_REST_Request $request The REST request object
	 * @return mixed Response from the called method
	 */
	public function callback( $request ) {
		$this->request = $request;
		$action_class  = strtolower( $this->request->get_method() ) . '_' . $this->request['action'];

		if ( method_exists( $this, $action_class ) ) {
			return $this->{$action_class}();
		}

		return new \WP_Error( 
			'method_not_found', 
			esc_html__( 'Method not found', 'bdthemes-element-pack' ), 
			array( 'status' => 404 ) 
		);
	}
}
