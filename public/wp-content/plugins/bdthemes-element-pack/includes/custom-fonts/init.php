<?php
/**
 * Element Pack Custom Fonts Initializer
 *
 * @package ElementPack\Includes\CustomFonts
 */

namespace ElementPack\Includes\CustomFonts;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Define constants
define('EP_CUSTOM_FONTS_PATH', BDTEP_INC_PATH . 'custom-fonts/');
define('EP_CUSTOM_FONTS_URL', BDTEP_URL . 'includes/custom-fonts/');

/**
 * Initialize Custom Fonts Feature
 */
class Custom_Fonts_Init {

	/**
	 * Instance
	 *
	 * @var Custom_Fonts_Init
	 */
	private static $_instance = null;

	/**
	 * Get Instance
	 */
	public static function instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once EP_CUSTOM_FONTS_PATH . 'class-ep-custom-fonts-manager.php';
		require_once EP_CUSTOM_FONTS_PATH . 'class-ep-custom-fonts-admin.php';
		require_once EP_CUSTOM_FONTS_PATH . 'class-ep-font-face-generator.php';
		require_once EP_CUSTOM_FONTS_PATH . 'class-ep-font-uploader.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Initialize main manager
		EP_Custom_Fonts_Manager::instance();

		// Initialize admin if in admin area
		if (is_admin()) {
			EP_Custom_Fonts_Admin::instance();
		}
	}
}

// Initialize
Custom_Fonts_Init::instance();
