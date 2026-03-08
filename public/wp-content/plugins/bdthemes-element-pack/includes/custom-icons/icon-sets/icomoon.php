<?php
/**
 * IcoMoon Icon Set Parser
 * Handles IcoMoon.io generated icon sets
 *
 * @package ElementPack\Includes\CustomIcons
 */

namespace ElementPack\Includes\CustomIcons\IconSets;

if (!defined('ABSPATH')) {
	exit;
}

class Icomoon extends Icon_Set_Base {

	/**
	 * Get icon set type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'icomoon';
	}

	/**
	 * Check if this is a valid IcoMoon icon set
	 *
	 * @return bool
	 */
	public function is_valid() {
		// IcoMoon always has a selection.json file
		return file_exists($this->directory . 'selection.json');
	}

	/**
	 * Parse IcoMoon configuration
	 *
	 * @return array|false
	 */
	protected function parse_config() {
		$selection_file = $this->directory . 'selection.json';
		
		if (!file_exists($selection_file)) {
			return false;
		}
		
		$filesystem = $this->get_wp_filesystem();
		$json_content = $filesystem->get_contents($selection_file);
		
		if (!$json_content) {
			return false;
		}
		
		$data = json_decode($json_content, true);
		
		if (!$data || !isset($data['icons'])) {
			return false;
		}
		
		// Extract icon information
		$icons = [];
		foreach ($data['icons'] as $icon) {
			if (isset($icon['properties']['name'])) {
				$icons[] = $icon['properties']['name'];
			}
		}
		
		// Get prefix from metadata or use default
		$prefix = isset($data['preferences']['fontPref']['prefix']) 
			? $data['preferences']['fontPref']['prefix'] 
			: 'icon-';
		
		// Get font name
		$font_name = isset($data['metadata']['name']) 
			? $data['metadata']['name'] 
			: 'icomoon';
		
		return [
			'name' => sanitize_title($font_name),
			'label' => $font_name,
			'prefix' => $prefix,
			'displayPrefix' => $prefix,
			'type' => 'icomoon',
			'icons' => $icons,
			'count' => count($icons),
			'ver' => time(),
			'fetchJson' => 'selection.json',
			'native' => false,
		];
	}

	/**
	 * Get CSS content
	 *
	 * @return string|false
	 */
	protected function get_css_content() {
		$css_file = $this->directory . 'style.css';
		
		if (!file_exists($css_file)) {
			return false;
		}
		
		$filesystem = $this->get_wp_filesystem();
		return $filesystem->get_contents($css_file);
	}

	/**
	 * Get font files
	 *
	 * @return array
	 */
	protected function get_font_files() {
		$font_dir = $this->directory . 'fonts/';
		$files = [];
		
		if (!is_dir($font_dir)) {
			return $files;
		}
		
		$filesystem = $this->get_wp_filesystem();
		$dir_list = $filesystem->dirlist($font_dir);
		
		if (!$dir_list) {
			return $files;
		}
		
		foreach ($dir_list as $file => $file_info) {
			if ($file_info['type'] === 'f') {
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if (in_array($ext, ['eot', 'svg', 'ttf', 'woff', 'woff2'])) {
					$files[] = 'fonts/' . $file;
				}
			}
		}
		
		// Also include selection.json
		$files[] = 'selection.json';
		
		return $files;
	}
}
