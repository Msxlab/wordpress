<?php
/**
 * Icofont Icon Set Parser
 * Handles Icofont generated icon sets
 *
 * @package ElementPack\Includes\CustomIcons
 */

namespace ElementPack\Includes\CustomIcons\IconSets;

if (!defined('ABSPATH')) {
	exit;
}

class Icofont extends Icon_Set_Base {

	/**
	 * Get icon set type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'icofont';
	}

	/**
	 * Check if this is a valid Icofont set
	 *
	 * @return bool
	 */
	public function is_valid() {
		// Check for icofont.css or icofont.min.css
		return file_exists($this->directory . 'icofont.css') || 
		       file_exists($this->directory . 'icofont.min.css') ||
		       file_exists($this->directory . 'css/icofont.css') ||
		       file_exists($this->directory . 'css/icofont.min.css');
	}

	/**
	 * Parse Icofont configuration
	 *
	 * @return array|false
	 */
	protected function parse_config() {
		// Try different possible locations
		$possible_paths = [
			$this->directory . 'icofont.css',
			$this->directory . 'icofont.min.css',
			$this->directory . 'css/icofont.css',
			$this->directory . 'css/icofont.min.css',
		];
		
		$css_file = null;
		foreach ($possible_paths as $path) {
			if (file_exists($path)) {
				$css_file = $path;
				break;
			}
		}
		
		if (!$css_file) {
			return false;
		}
		
		$filesystem = $this->get_wp_filesystem();
		$css_content = $filesystem->get_contents($css_file);
		
		if (!$css_content) {
			return false;
		}
		
		// Extract icon names from CSS
		// Icofont uses .icofont-icon-name pattern
		preg_match_all('/\.icofont-([a-z0-9\-]+):before/i', $css_content, $matches);
		
		$icons = [];
		if (isset($matches[1]) && !empty($matches[1])) {
			$icons = array_unique($matches[1]);
		}
		
		return [
			'name' => 'icofont',
			'label' => 'Icofont',
			'prefix' => 'icofont-',
			'displayPrefix' => 'icofont-',
			'type' => 'icofont',
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
		$possible_paths = [
			$this->directory . 'icofont.css',
			$this->directory . 'icofont.min.css',
			$this->directory . 'css/icofont.css',
			$this->directory . 'css/icofont.min.css',
		];
		
		$css_file = null;
		foreach ($possible_paths as $path) {
			if (file_exists($path)) {
				$css_file = $path;
				break;
			}
		}
		
		if (!$css_file) {
			return false;
		}
		
		$filesystem = $this->get_wp_filesystem();
		$content = $filesystem->get_contents($css_file);
		
		// Update font paths to be relative
		$content = preg_replace('/url\(["\']?\.\.\/fonts\//i', 'url(', $content);
		$content = preg_replace('/url\(["\']?fonts\//i', 'url(', $content);
		
		return $content;
	}

	/**
	 * Get font files
	 *
	 * @return array
	 */
	protected function get_font_files() {
		$files = [];
		
		// Try different possible font directories
		$possible_dirs = [
			$this->directory . 'fonts/',
			$this->directory . 'font/',
			$this->directory,
		];
		
		$font_dir = null;
		foreach ($possible_dirs as $dir) {
			if (is_dir($dir)) {
				$font_dir = $dir;
				break;
			}
		}
		
		if (!$font_dir) {
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
					if ($font_dir === $this->directory) {
						$files[] = $file;
					} elseif ($font_dir === $this->directory . 'font/') {
						$files[] = 'font/' . $file;
					} else {
						$files[] = 'fonts/' . $file;
					}
				}
			}
		}
		
		return $files;
	}
}
