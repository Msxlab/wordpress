<?php
/**
 * Element Pack Custom Fonts Manager
 * Handles CPT registration and Elementor integration
 *
 * @package ElementPack\Includes\CustomFonts
 */

namespace ElementPack\Includes\CustomFonts;

if (!defined('ABSPATH')) {
	exit;
}

class EP_Custom_Fonts_Manager {

	const CPT = 'ep_custom_font';
	const FONT_META_KEY = 'ep_font_files';
	const FONT_FACE_META_KEY = 'ep_font_face_css';

	private static $_instance = null;

	private $fonts_cache = [];

	public static function instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	private function __construct() {
		add_action('init', [$this, 'register_post_type']);
		add_filter('elementor/fonts/additional_fonts', [$this, 'add_fonts_to_elementor']);
		add_filter('elementor/fonts/groups', [$this, 'add_fonts_group']);
		add_action('elementor/css-file/post/parse', [$this, 'enqueue_fonts_in_post_css']);
		add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_editor_fonts']);
		
		// AJAX for editor
		add_action('wp_ajax_ep_get_custom_font_css', [$this, 'ajax_get_font_css']);
	}

	/**
	 * Register Custom Post Type
	 */
	public function register_post_type() {
		$labels = [
			'name'               => esc_html_x('Custom Fonts', 'Post Type General Name', 'bdthemes-element-pack'),
			'singular_name'      => esc_html_x('Custom Font', 'Post Type Singular Name', 'bdthemes-element-pack'),
			'menu_name'          => esc_html__('Custom Fonts', 'bdthemes-element-pack'),
			'all_items'          => esc_html__('All Fonts', 'bdthemes-element-pack'),
			'add_new_item'       => esc_html__('Add New Font', 'bdthemes-element-pack'),
			'add_new'            => esc_html__('Add New', 'bdthemes-element-pack'),
			'new_item'           => esc_html__('New Font', 'bdthemes-element-pack'),
			'edit_item'          => esc_html__('Edit Font', 'bdthemes-element-pack'),
			'update_item'        => esc_html__('Update Font', 'bdthemes-element-pack'),
			'view_item'          => esc_html__('View Font', 'bdthemes-element-pack'),
			'search_items'       => esc_html__('Search Font', 'bdthemes-element-pack'),
			'not_found'          => esc_html__('Not found', 'bdthemes-element-pack'),
			'not_found_in_trash' => esc_html__('Not found in Trash', 'bdthemes-element-pack'),
		];

		$args = [
			'label'               => esc_html__('Custom Font', 'bdthemes-element-pack'),
			'labels'              => $labels,
			'supports'            => ['title'],
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // We'll add it manually
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'capabilities'        => [
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'delete_posts'       => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
			],
		];

		register_post_type(self::CPT, $args);
	}

	/**
	 * Get all custom fonts
	 */
	public function get_fonts() {
		if (!empty($this->fonts_cache)) {
			return $this->fonts_cache;
		}

		$fonts = [];
		$query = new \WP_Query([
			'post_type'      => self::CPT,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		]);

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$font_name = get_the_title();
				$fonts[$font_name] = 'ep-custom';
			}
			wp_reset_postdata();
		}

		$this->fonts_cache = $fonts;
		return $fonts;
	}

	/**
	 * Add fonts to Elementor typography control
	 */
	public function add_fonts_to_elementor($fonts) {
		$custom_fonts = $this->get_fonts();
		return array_merge($custom_fonts, $fonts);
	}

	/**
	 * Add font group to Elementor
	 */
	public function add_fonts_group($font_groups) {
		$new_groups = [
			'ep-custom' => esc_html__('Element Pack Fonts', 'bdthemes-element-pack'),
		];
		return array_merge($new_groups, $font_groups);
	}

	/**
	 * Enqueue fonts in post CSS
	 */
	public function enqueue_fonts_in_post_css($post_css) {
		$fonts_used = $post_css->get_fonts();
		$custom_fonts = $this->get_fonts();

		foreach ($fonts_used as $font_family) {
			if (isset($custom_fonts[$font_family])) {
				$font_css = $this->get_font_face_css($font_family);
				if ($font_css) {
					$post_css->get_stylesheet()->add_raw_css(
						'/* Element Pack Custom Font: ' . $font_family . ' */' . "\n" .
						$font_css . "\n" .
						'/* End Element Pack Custom Font */'
					);
				}
			}
		}
	}

	/**
	 * Get font face CSS for a font family
	 */
	public function get_font_face_css($font_family) {
		global $wpdb;

		$post_id = $wpdb->get_var($wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_status = 'publish' LIMIT 1",
			$font_family,
			self::CPT
		));

		if (!$post_id) {
			return '';
		}

		// Check if cached CSS exists
		$cached_css = get_post_meta($post_id, self::FONT_FACE_META_KEY, true);
		if ($cached_css) {
			return $cached_css;
		}

		// Generate CSS
		$font_files = get_post_meta($post_id, self::FONT_META_KEY, true);
		if (!is_array($font_files) || empty($font_files)) {
			return '';
		}

		$generator = new EP_Font_Face_Generator();
		$css = $generator->generate_css($font_family, $font_files);

		// Cache it
		update_post_meta($post_id, self::FONT_FACE_META_KEY, $css);

		return $css;
	}

	/**
	 * Enqueue fonts in editor via AJAX
	 */
	public function enqueue_editor_fonts() {
		wp_enqueue_script(
			'ep-custom-fonts-editor',
			EP_CUSTOM_FONTS_URL . 'assets/js/editor.js',
			['jquery', 'elementor-editor'],
			BDTEP_VER,
			true
		);

		wp_localize_script('ep-custom-fonts-editor', 'EPCustomFonts', [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('ep_custom_fonts'),
		]);
	}

	/**
	 * AJAX handler to get font CSS for editor
	 */
	public function ajax_get_font_css() {
		check_ajax_referer('ep_custom_fonts', 'nonce');

		$font_family = isset($_POST['font_family']) ? sanitize_text_field($_POST['font_family']) : '';

		if (empty($font_family)) {
			wp_send_json_error('Font family not specified');
		}

		$css = $this->get_font_face_css($font_family);

		if (empty($css)) {
			wp_send_json_error('Font not found');
		}

		wp_send_json_success(['css' => $css]);
	}

	/**
	 * Clear font cache when font is saved/deleted
	 */
	public function clear_fonts_cache() {
		$this->fonts_cache = [];
	}
}
