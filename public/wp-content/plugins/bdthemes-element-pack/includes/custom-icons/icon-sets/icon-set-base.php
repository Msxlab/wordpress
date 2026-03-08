<?php
/**
 * Base class for custom icon sets
 * Abstract class that defines the interface for icon set parsers
 *
 * @package ElementPack\Includes\CustomIcons
 */

namespace ElementPack\Includes\CustomIcons\IconSets;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

abstract class Icon_Set_Base {

	/**
	 * Directory path of extracted icon set
	 *
	 * @var string
	 */
	protected $directory;

	/**
	 * Icon set configuration
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Constructor
	 *
	 * @param string $directory Path to extracted icon set directory
	 */
	public function __construct($directory) {
		$this->directory = trailingslashit($directory);
	}

	/**
	 * Check if this is a valid icon set of this type
	 *
	 * @return bool
	 */
	abstract public function is_valid();

	/**
	 * Get the icon set type name
	 *
	 * @return string
	 */
	abstract public function get_type();

	/**
	 * Parse the icon set and extract configuration
	 *
	 * @return array|false Configuration array or false on failure
	 */
	abstract protected function parse_config();

	/**
	 * Get the CSS file content
	 *
	 * @return string|false
	 */
	abstract protected function get_css_content();

	/**
	 * Get font files that should be uploaded
	 *
	 * @return array Array of font file paths
	 */
	abstract protected function get_font_files();

	/**
	 * Handle new icon set - parse configuration
	 *
	 * @return bool
	 */
	public function handle_new_icon_set() {
		$config = $this->parse_config();
		
		if (false === $config) {
			return false;
		}
		
		$this->config = $config;
		return true;
	}

	/**
	 * Move font files to uploads directory
	 *
	 * @param int $post_id Custom icon post ID
	 * @return bool
	 */
	public function move_files($post_id) {
		$upload_dir = wp_upload_dir();
		$base_path = trailingslashit($upload_dir['basedir']) . 'element-pack/custom-icons/';
		$base_url = trailingslashit($upload_dir['baseurl']) . 'element-pack/custom-icons/';
		
		// Create directory if it doesn't exist
		wp_mkdir_p($base_path);
		
		// Create icon set specific directory
		$icon_set_path = $base_path . $post_id . '/';
		$icon_set_url = $base_url . $post_id . '/';
		wp_mkdir_p($icon_set_path);
		
		$filesystem = $this->get_wp_filesystem();
		$font_files = $this->get_font_files();
		
		$uploaded_files = [];
		
		foreach ($font_files as $file) {
			$source = $this->directory . $file;
			
			if (!file_exists($source)) {
				continue;
			}
			
			// Preserve directory structure (e.g., fonts/icomoon.ttf)
			$destination = $icon_set_path . $file;
			
			// Create subdirectory if needed (e.g., fonts/)
			$destination_dir = dirname($destination);
			if (!is_dir($destination_dir)) {
				wp_mkdir_p($destination_dir);
			}
			
			if ($filesystem->copy($source, $destination, true)) {
				$uploaded_files[] = [
					'path' => $destination,
					'url' => $icon_set_url . $file,
					'file' => $file
				];
			}
		}
		
		// Copy CSS file
		$css_content = $this->get_css_content();
		if ($css_content) {
			$css_file = $icon_set_path . 'style.css';
			$filesystem->put_contents($css_file, $css_content);
			
			$uploaded_files[] = [
				'path' => $css_file,
				'url' => $icon_set_url . 'style.css',
				'file' => 'style.css'
			];
		}
		
		// Store paths in post meta
		update_post_meta($post_id, '_bdt_icon_set_path', $icon_set_path);
		update_post_meta($post_id, '_bdt_icon_set_url', $icon_set_url);
		update_post_meta($post_id, '_bdt_icon_files', $uploaded_files);
		
		// Update config with URLs
		if (isset($this->config['fetchJson'])) {
			$this->config['fetchJson'] = $icon_set_url . basename($this->config['fetchJson']);
		}
		
		// Update font URLs in config
		if (isset($this->config['url'])) {
			$this->config['url'] = $icon_set_url;
		}
		
		// Clean up temporary directory
		$this->cleanup_temp_directory();
		
		return !empty($uploaded_files);
	}

	/**
	 * Build configuration array for Elementor
	 *
	 * @return array
	 */
	public function build_config() {
		return $this->config;
	}

	/**
	 * Get WP Filesystem
	 *
	 * @return \WP_Filesystem_Base
	 */
	protected function get_wp_filesystem() {
		global $wp_filesystem;
		
		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		return $wp_filesystem;
	}

	/**
	 * Clean up temporary extraction directory
	 */
	protected function cleanup_temp_directory() {
		$filesystem = $this->get_wp_filesystem();
		
		if ($this->directory && is_dir($this->directory)) {
			$filesystem->rmdir($this->directory, true);
		}
	}

	/**
	 * Get file URL from path
	 *
	 * @param string $file_path File path in the icon set directory
	 * @return string|false
	 */
	protected function get_file_url($file_path) {
		$upload_dir = wp_upload_dir();
		$base_path = trailingslashit($upload_dir['basedir']) . 'element-pack/custom-icons/';
		$base_url = trailingslashit($upload_dir['baseurl']) . 'element-pack/custom-icons/';
		
		$file_path = str_replace($base_path, $base_url, $file_path);
		
		return apply_filters('bdt_custom_icons_file_url', $file_path);
	}

	/**
	 * Sanitize text field recursively
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	protected function sanitize_text_field_recursive($value) {
		if (is_array($value)) {
			return array_map([$this, 'sanitize_text_field_recursive'], $value);
		}
		
		return sanitize_text_field($value);
	}
}
