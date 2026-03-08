<?php
/**
 * Element Pack Custom Icons Manager
 * Manages custom icon sets - CPT registration, admin menu, icon management
 *
 * @package ElementPack\Includes\CustomIcons
 */

namespace ElementPack\Includes\CustomIcons;

if (!defined('ABSPATH')) {
	exit;
}

class BDT_Custom_Icons_Manager {

	const CAPABILITY = 'manage_options';
	const CPT = 'bdt_custom_icons';
	const MENU_SLUG = 'edit.php?post_type=' . self::CPT;

	/**
	 * Instance
	 *
	 * @var BDT_Custom_Icons_Manager
	 */
	private static $_instance = null;

	/**
	 * Icon types
	 *
	 * @var array
	 */
	protected $icon_types = [];

	/**
	 * Has icons flag
	 *
	 * @var bool|null
	 */
	private $has_icons = null;

	/**
	 * Get instance
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
		$this->init_hooks();
		$this->register_icon_types();
	}

	/**
	 * Register icon types
	 */
	private function register_icon_types() {
		require_once BDT_CUSTOM_ICONS_PATH . 'icon-sets/icon-set-base.php';
		require_once BDT_CUSTOM_ICONS_PATH . 'icon-sets/icomoon.php';
		require_once BDT_CUSTOM_ICONS_PATH . 'icon-sets/remixicon.php';
		require_once BDT_CUSTOM_ICONS_PATH . 'icon-sets/icofont.php';

		$this->add_icon_type('icomoon', IconSets\Icomoon::class);
		$this->add_icon_type('remixicon', IconSets\Remixicon::class);
		$this->add_icon_type('icofont', IconSets\Icofont::class);
	}

	/**
	 * Add icon type
	 *
	 * @param string $type Icon type key
	 * @param string $class_name Class name
	 */
	public function add_icon_type($type, $class_name) {
		$this->icon_types[$type] = $class_name;
	}

	/**
	 * Get icon type
	 *
	 * @param string|null $type
	 * @return array|string|false
	 */
	public function get_icon_type($type = null) {
		if (null === $type) {
			return $this->icon_types;
		}

		if (isset($this->icon_types[$type])) {
			return $this->icon_types[$type];
		}

		return false;
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action('init', [$this, 'register_post_type']);
		
		if (is_admin()) {
			add_action('admin_menu', [$this, 'register_admin_menu'], 204); // After Custom Fonts (203)
			add_filter('parent_file', [$this, 'set_parent_file']);
			add_filter('submenu_file', [$this, 'set_submenu_file']);
			add_action('admin_head', [$this, 'clean_admin_listing_page']);
		}

		add_filter('post_updated_messages', [$this, 'post_updated_messages']);
		add_filter('post_row_actions', [$this, 'post_row_actions'], 10, 2);
	}

	/**
	 * Register custom post type
	 */
	public function register_post_type() {
		$labels = [
			'name'               => esc_html_x('Custom Icons', 'CPT Name', 'bdthemes-element-pack'),
			'singular_name'      => esc_html_x('Icon Set', 'CPT Singular Name', 'bdthemes-element-pack'),
			'add_new'            => esc_html__('Add New', 'bdthemes-element-pack'),
			'add_new_item'       => esc_html__('Add New Icon Set', 'bdthemes-element-pack'),
			'edit_item'          => esc_html__('Edit Icon Set', 'bdthemes-element-pack'),
			'new_item'           => esc_html__('New Icon Set', 'bdthemes-element-pack'),
			'all_items'          => esc_html__('All Icon Sets', 'bdthemes-element-pack'),
			'view_item'          => esc_html__('View Icon Set', 'bdthemes-element-pack'),
			'search_items'       => esc_html__('Search Icon Sets', 'bdthemes-element-pack'),
			'not_found'          => esc_html__('No icon sets found', 'bdthemes-element-pack'),
			'not_found_in_trash' => esc_html__('No icon sets found in trash', 'bdthemes-element-pack'),
			'parent_item_colon'  => '',
			'menu_name'          => esc_html_x('Custom Icons', 'CPT Menu Name', 'bdthemes-element-pack'),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => ['title'],
		];

		register_post_type(self::CPT, $args);
	}

	/**
	 * Register admin menu
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'element_pack_options',
			esc_html__('Custom Icons', 'bdthemes-element-pack'),
			esc_html__('Custom Icons', 'bdthemes-element-pack'),
			self::CAPABILITY,
			self::MENU_SLUG,
			'',
			72  // After Custom Fonts (71)
		);
	}

	/**
	 * Set parent file to nest under Element Pack
	 */
	public function set_parent_file($parent_file) {
		global $current_screen;

		if ($current_screen && $current_screen->post_type === self::CPT) {
			return 'element_pack_options';
		}

		return $parent_file;
	}

	/**
	 * Set submenu file to highlight menu item
	 */
	public function set_submenu_file($submenu_file) {
		global $current_screen;

		if ($current_screen && $current_screen->post_type === self::CPT) {
			return self::MENU_SLUG;
		}

		return $submenu_file;
	}

	/**
	 * Clean admin listing page
	 */
	public function clean_admin_listing_page() {
		global $typenow;

		if (self::CPT !== $typenow) {
			return;
		}

		add_filter('months_dropdown_results', '__return_empty_array');
		add_filter('screen_options_show_screen', '__return_false');
	}

	/**
	 * Post updated messages
	 */
	public function post_updated_messages($messages) {
		$messages[self::CPT] = [
			0  => '',
			1  => esc_html__('Icon Set updated.', 'bdthemes-element-pack'),
			2  => esc_html__('Custom field updated.', 'bdthemes-element-pack'),
			3  => esc_html__('Custom field deleted.', 'bdthemes-element-pack'),
			4  => esc_html__('Icon Set updated.', 'bdthemes-element-pack'),
			5  => isset($_GET['revision']) ? sprintf(esc_html__('Icon Set restored to revision from %s', 'bdthemes-element-pack'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
			6  => esc_html__('Icon Set saved.', 'bdthemes-element-pack'),
			7  => esc_html__('Icon Set saved.', 'bdthemes-element-pack'),
			8  => esc_html__('Icon Set submitted.', 'bdthemes-element-pack'),
			9  => esc_html__('Icon Set updated.', 'bdthemes-element-pack'),
			10 => esc_html__('Icon Set draft updated.', 'bdthemes-element-pack'),
		];

		return $messages;
	}

	/**
	 * Customize row actions
	 */
	public function post_row_actions($actions, $post) {
		if (self::CPT !== $post->post_type) {
			return $actions;
		}

		// Remove quick edit
		unset($actions['inline hide-if-no-js']);

		return $actions;
	}

	/**
	 * Check if has icons
	 *
	 * @return bool
	 */
	public function has_icons() {
		if (null !== $this->has_icons) {
			return $this->has_icons;
		}

		$existing_icons = new \WP_Query([
			'post_type'      => self::CPT,
			'posts_per_page' => 1,
		]);

		$this->has_icons = $existing_icons->post_count > 0;

		return $this->has_icons;
	}

	/**
	 * Get supported icon sets
	 *
	 * @return array
	 */
	public static function get_supported_icon_sets() {
		return apply_filters('bdt_custom_icons_supported_types', [
			'icomoon'   => IconSets\Icomoon::class,
			'remixicon' => IconSets\Remixicon::class,
			'icofont'   => IconSets\Icofont::class,
		]);
	}
}
