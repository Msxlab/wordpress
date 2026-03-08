<?php
/**
 * Element Pack Custom Icons Initializer
 * Main entry point for custom icons feature
 *
 * @package ElementPack\Includes\CustomIcons
 */

namespace ElementPack\Includes\CustomIcons;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Define constants
define('BDT_CUSTOM_ICONS_PATH', BDTEP_INC_PATH . 'custom-icons/');
define('BDT_CUSTOM_ICONS_URL', BDTEP_URL . 'includes/custom-icons/');

/**
 * Initialize Custom Icons Feature
 */
class Custom_Icons_Init {

	/**
	 * Instance
	 *
	 * @var Custom_Icons_Init
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
		require_once BDT_CUSTOM_ICONS_PATH . 'class-bdt-custom-icons-manager.php';
		require_once BDT_CUSTOM_ICONS_PATH . 'class-bdt-custom-icons.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Initialize manager
		BDT_Custom_Icons_Manager::instance();

		// Initialize handler
		BDT_Custom_Icons::instance();

		// Enqueue custom icon styles on frontend
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles'], 999);
		add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueue_frontend_styles'], 999);

		// Register custom icons with Elementor - Use very high priority to ensure we run last
		add_filter('elementor/icons_manager/additional_tabs', [$this, 'register_custom_icon_tabs'], 999);
	}

	/**
	 * Enqueue frontend styles for custom icons
	 */
	public function enqueue_frontend_styles() {
		$icons = BDT_Custom_Icons::get_custom_icons_config();

		if (empty($icons)) {
			return;
		}

		foreach ($icons as $icon_set) {
			if (!isset($icon_set['custom_icon_post_id'])) {
				continue;
			}

			$post_id = $icon_set['custom_icon_post_id'];
			$icon_set_url = get_post_meta($post_id, '_bdt_icon_set_url', true);

			if (!$icon_set_url) {
				continue;
			}

			$css_url = trailingslashit($icon_set_url) . 'style.css';
			$handle = 'bdt-custom-icons-' . sanitize_title($icon_set['name']);

			wp_enqueue_style(
				$handle,
				$css_url,
				[],
				isset($icon_set['ver']) ? $icon_set['ver'] : BDTEP_VER
			);
		}
	}

	/**
	 * Register custom icon tabs with Elementor
	 *
	 * @param array $tabs Existing icon tabs
	 * @return array Modified tabs
	 */
	public function register_custom_icon_tabs($tabs) {
		$custom_icons = BDT_Custom_Icons::get_custom_icons_config();

		if (empty($custom_icons)) {
			return $tabs;
		}

		foreach ($custom_icons as $icon_set) {
			// Skip if essential data missing
			if (empty($icon_set['name'])) {
				continue;
			}

			$post_id = isset($icon_set['custom_icon_post_id']) ? $icon_set['custom_icon_post_id'] : 0;
			
			if (!$post_id) {
				continue;
			}

			$icon_set_url = get_post_meta($post_id, '_bdt_icon_set_url', true);

			if (!$icon_set_url) {
				continue;
			}

			// Get stored icon data
			$stored_config = get_post_meta($post_id, BDT_Custom_Icons::META_KEY, true);
			$icon_data = $stored_config ? json_decode($stored_config, true) : [];

			// Get icons list
			$icons_array = isset($icon_data['icons']) ? $icon_data['icons'] : (isset($icon_set['icons']) ? $icon_set['icons'] : []);
			
			if (empty($icons_array)) {
				continue;
			}

			// Get the prefix (ensure it has the proper format)
			$prefix = isset($icon_data['prefix']) ? $icon_data['prefix'] : (isset($icon_set['prefix']) ? $icon_set['prefix'] : '');
			
			// Remove trailing dash if present for consistency
			$prefix = rtrim($prefix, '-');
			
			// Icons should be a simple array of icon names (without prefix)
			// Elementor will add the prefix automatically
			$icons_list = $icons_array;

			// Create unique tab key with bdt prefix to avoid conflicts
			// Use post_id to ensure uniqueness even if same icon set uploaded multiple times
			$tab_key = 'bdt-custom-' . sanitize_key($icon_set['name']) . '-' . $post_id;

			// Get first icon for label - construct full icon class with prefix
			// Try to find a non-duotone icon for label (for Icofont compatibility)
			$first_icon_name = $icons_array[0];
			
			// For Icofont, skip duotone icons and find a regular one
			if ($prefix === 'icofont') {
				foreach ($icons_array as $icon_name) {
					// These are typically non-duotone in Icofont
					if (!in_array($icon_name, ['access-levels', 'accessibility', 'add-users', 'address'])) {
						$first_icon_name = $icon_name;
						break;
					}
				}
			}
			
			// Construct the labelIcon with proper prefix format
			if ($prefix) {
				// Ensure prefix doesn't end with dash, then add dash before icon name
				$labelIcon = $prefix . '-' . $first_icon_name;
			} else {
				$labelIcon = $first_icon_name;
			}

			// Add to Elementor icon tabs
			$tabs[$tab_key] = [
				'name'          => $tab_key,
				'label'         => isset($icon_set['label']) ? $icon_set['label'] : $icon_set['name'],
				'url'           => trailingslashit($icon_set_url) . 'style.css',
				'enqueue'       => [trailingslashit($icon_set_url) . 'style.css'],
				'prefix'        => $prefix ? $prefix . '-' : '', // Elementor expects trailing dash
				'displayPrefix' => $prefix,
				'labelIcon'     => $labelIcon,
				'ver'           => isset($icon_data['ver']) ? $icon_data['ver'] : BDTEP_VER,
				'fetchJson'     => '',
				'native'        => false,
				'icons'         => $icons_list,
			];
		}

		return $tabs;
	}
}

// Initialize
Custom_Icons_Init::instance();
