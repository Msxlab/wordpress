<?php
/**
 * Remixicon Icon Set Parser
 * Handles Remixicon.com generated icon sets
 *
 * @package ElementPack\Includes\CustomIcons
 */

namespace ElementPack\Includes\CustomIcons\IconSets;

if (!defined('ABSPATH')) {
	exit;
}

class Remixicon extends Icon_Set_Base {

	/**
	 * Get icon set type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'remixicon';
	}

	/**
	 * Check if this is a valid Remixicon set
	 *
	 * @return bool
	 */
	public function is_valid() {
		// Check for remixicon.css or remixicon.min.css
		return file_exists($this->directory . 'remixicon.css') || 
		       file_exists($this->directory . 'remixicon.min.css');
	}

	/**
	 * Parse Remixicon configuration
	 *
	 * @return array|false
	 */
	protected function parse_config() {
		$css_file = $this->directory . 'remixicon.css';
		
		if (!file_exists($css_file)) {
			$css_file = $this->directory . 'remixicon.min.css';
		}
		
		if (!file_exists($css_file)) {
			return false;
		}
		
		$filesystem = $this->get_wp_filesystem();
		$css_content = $filesystem->get_contents($css_file);
		
		if (!$css_content) {
			return false;
		}
		
		// Extract icon names from CSS
		preg_match_all('/\.ri-([a-z0-9\-]+):before/i', $css_content, $matches);
		
		$icons = [];
		if (isset($matches[1]) && !empty($matches[1])) {
			$icons = array_unique($matches[1]);
		}
		
		return [
			'name' => 'remixicon',
			'label' => 'Remixicon',
			'prefix' => 'ri-',
			'displayPrefix' => 'ri-',
			'type' => 'remixicon',
			'icons' => array_values($icons),
			'count' => count($icons),
			'ver' => time(),
			'native' => false,
		];
	}

	/**
	 * Get CSS content
	 *
	 * @return string|false
	 */
	protected function get_css_content() {
		$css_file = $this->directory . 'remixicon.css';
		
		if (!file_exists($css_file)) {
			$css_file = $this->directory . 'remixicon.min.css';
		}
		
		if (!file_exists($css_file)) {
			return false;
		}
		
		$filesystem = $this->get_wp_filesystem();
		$content = $filesystem->get_contents($css_file);
		
		// Update font paths to be relative
		$content = preg_replace('/url\(["\']?\.\.\/fonts\//i', 'url(', $content);
		
		return $content;
	}

	/**
	 * Get font files
	 *
	 * @return array
	 */
	protected function get_font_files() {
		$files = [];
		$font_dir = $this->directory . 'fonts/';
		
		if (!is_dir($font_dir)) {
			// Try in root directory
			$font_dir = $this->directory;
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
					if ($font_dir === $this->directory) {
						$files[] = $file;
					} else {
						$files[] = 'fonts/' . $file;
					}
				}
			}
		}
		
		return $files;
	}
}
