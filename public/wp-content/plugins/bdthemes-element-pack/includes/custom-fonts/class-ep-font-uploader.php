<?php
/**
 * Element Pack Font Uploader
 * Handles font file uploads and validation
 *
 * @package ElementPack\Includes\CustomFonts
 */

namespace ElementPack\Includes\CustomFonts;

if (!defined('ABSPATH')) {
	exit;
}

class EP_Font_Uploader {

	private static $_instance = null;

	public static function instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	private function __construct() {
		add_filter('upload_mimes', [$this, 'allow_font_mime_types'], 10, 1);
		add_filter('wp_check_filetype_and_ext', [$this, 'check_filetype'], 10, 5);
		add_filter('mime_types', [$this, 'allow_font_mime_types']);
	}

	/**
	 * Get supported font file types
	 *
	 * @return array Supported file types with MIME types
	 */
	public function get_supported_types() {
		return [
			'woff2' => [
				'ext'   => 'woff2',
				'mime'  => 'font/woff2|application/octet-stream|font/x-woff2|application/x-font-woff2',
				'label' => 'WOFF2',
			],
			'woff'  => [
				'ext'   => 'woff',
				'mime'  => 'font/woff|application/font-woff|application/x-font-woff|application/octet-stream',
				'label' => 'WOFF',
			],
			'ttf'   => [
				'ext'   => 'ttf',
				'mime'  => 'font/ttf|application/x-font-ttf|application/octet-stream|font/sfnt|application/x-font-truetype',
				'label' => 'TTF',
			],
			'otf'   => [
				'ext'   => 'otf',
				'mime'  => 'font/otf|application/x-font-otf|application/octet-stream|font/opentype',
				'label' => 'OTF',
			],
			'eot'   => [
				'ext'   => 'eot',
				'mime'  => 'application/vnd.ms-fontobject|application/octet-stream|application/x-vnd.ms-fontobject',
				'label' => 'EOT',
			],
			'svg'   => [
				'ext'   => 'svg',
				'mime'  => 'image/svg+xml|application/octet-stream',
				'label' => 'SVG',
			],
		];
	}

	/**
	 * Allow font MIME types for upload
	 *
	 * @param array $mimes Existing MIME types
	 * @return array Modified MIME types
	 */
	public function allow_font_mime_types($mimes) {
		// Add all font MIME types
		$mimes['woff2'] = 'font/woff2';
		$mimes['woff']  = 'font/woff';
		$mimes['ttf']   = 'font/ttf';
		$mimes['eot']   = 'application/vnd.ms-fontobject';
		$mimes['svg']   = 'image/svg+xml';
		$mimes['otf']   = 'font/otf';

		return $mimes;
	}

	/**
	 * Check file type for font files
	 *
	 * @param array  $data     File data
	 * @param string $file     File path
	 * @param string $filename File name
	 * @param array  $mimes    MIME types
	 * @param string $real_mime Real MIME type
	 * @return array Modified file data
	 */
	public function check_filetype($data, $file, $filename, $mimes, $real_mime = null) {
		// Get file extension
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		
		// Get font types
		$font_types = $this->get_supported_types();

		// Check if it's a supported font file
		if (isset($font_types[$ext])) {
			$mime_parts = explode('|', $font_types[$ext]['mime']);
			
			// Set the file type data
			$data['ext'] = $ext;
			$data['type'] = $mime_parts[0];
			
			// Accept any of the valid MIME types for this extension
			if ($real_mime && in_array($real_mime, $mime_parts)) {
				$data['type'] = $real_mime;
			}
		}

		return $data;
	}

	/**
	 * Validate font file
	 *
	 * @param string $file_path Path to file
	 * @param string $format    Font format (woff2, woff, ttf, etc.)
	 * @return bool|WP_Error True if valid, WP_Error on failure
	 */
	public function validate_font_file($file_path, $format) {
		if (!file_exists($file_path)) {
			return new \WP_Error('file_not_found', __('File not found.', 'bdthemes-element-pack'));
		}

		$font_types = $this->get_supported_types();

		if (!isset($font_types[$format])) {
			return new \WP_Error('invalid_format', __('Invalid font format.', 'bdthemes-element-pack'));
		}

		$allowed_mimes = explode('|', $font_types[$format]['mime']);
		$file_type = wp_check_filetype($file_path, null);

		if (!in_array($file_type['type'], $allowed_mimes)) {
			return new \WP_Error('invalid_mime', __('Invalid font file type.', 'bdthemes-element-pack'));
		}

		// Additional SVG validation (check for malicious code)
		if ($format === 'svg') {
			$svg_content = file_get_contents($file_path);

			if ($svg_content === false) {
				return new \WP_Error('read_error', __('Could not read font file.', 'bdthemes-element-pack'));
			}

			// Basic check for script tags in SVG
			if (preg_match('/<script[\s>]/i', $svg_content)) {
				return new \WP_Error('invalid_svg', __('SVG file contains potentially harmful code.', 'bdthemes-element-pack'));
			}
		}

		return true;
	}
}

// Initialize
EP_Font_Uploader::instance();
