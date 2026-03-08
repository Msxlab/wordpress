<?php
/**
 * Element Pack Font Face Generator
 * Generates @font-face CSS rules
 *
 * @package ElementPack\Includes\CustomFonts
 */

namespace ElementPack\Includes\CustomFonts;

if (!defined('ABSPATH')) {
	exit;
}

class EP_Font_Face_Generator {

	/**
	 * Generate CSS for font family
	 *
	 * @param string $font_family Font family name
	 * @param array  $font_files  Font files data
	 * @return string Generated CSS
	 */
	public function generate_css($font_family, $font_files) {
		if (!is_array($font_files) || empty($font_files)) {
			return '';
		}

		$css = '';

		foreach ($font_files as $font_data) {
			$css .= $this->generate_font_face($font_family, $font_data);
		}

		return $css;
	}

	/**
	 * Generate single @font-face rule
	 *
	 * @param string $font_family Font family name
	 * @param array  $font_data   Font variation data
	 * @return string @font-face CSS
	 */
	private function generate_font_face($font_family, $font_data) {
		$src = $this->generate_src($font_data);

		if (empty($src)) {
			return '';
		}

		$font_weight = isset($font_data['font_weight']) ? $font_data['font_weight'] : '400';
		$font_style = isset($font_data['font_style']) ? $font_data['font_style'] : 'normal';

		$css = '@font-face {' . "\n";
		$css .= "\t" . 'font-family: "' . $this->escape_font_family($font_family) . '";' . "\n";
		$css .= "\t" . 'font-style: ' . $font_style . ';' . "\n";
		$css .= "\t" . 'font-weight: ' . $font_weight . ';' . "\n";
		$css .= "\t" . 'font-display: swap;' . "\n"; // Better performance
		$css .= "\t" . 'src: ' . $src . ';' . "\n";
		$css .= '}' . "\n\n";

		return apply_filters('ep_custom_fonts_font_face_css', $css, $font_family, $font_data);
	}

	/**
	 * Generate src property
	 *
	 * @param array $font_data Font data
	 * @return string src property value
	 */
	private function generate_src($font_data) {
		$src_parts = [];

		// EOT for IE
		if (!empty($font_data['eot']) && is_array($font_data['eot']) && !empty($font_data['eot']['url'])) {
			$eot_url = $font_data['eot']['url'];
			// First declaration for IE9 compatibility
			$src_parts[] = "url('" . esc_url($eot_url) . "')";
			// Embedded OpenType (IE6-IE8)
			$src_parts[] = "url('" . esc_url($eot_url) . "?#iefix') format('embedded-opentype')";
		}

		// WOFF2 - Best compression, modern browsers
		if (!empty($font_data['woff2']) && is_array($font_data['woff2']) && !empty($font_data['woff2']['url'])) {
			$src_parts[] = "url('" . esc_url($font_data['woff2']['url']) . "') format('woff2')";
		}

		// WOFF - Wide browser support
		if (!empty($font_data['woff']) && is_array($font_data['woff']) && !empty($font_data['woff']['url'])) {
			$src_parts[] = "url('" . esc_url($font_data['woff']['url']) . "') format('woff')";
		}

		// TTF - Safari, Android, iOS
		if (!empty($font_data['ttf']) && is_array($font_data['ttf']) && !empty($font_data['ttf']['url'])) {
			$src_parts[] = "url('" . esc_url($font_data['ttf']['url']) . "') format('truetype')";
		}

		// OTF - OpenType Font
		if (!empty($font_data['otf']) && is_array($font_data['otf']) && !empty($font_data['otf']['url'])) {
			$src_parts[] = "url('" . esc_url($font_data['otf']['url']) . "') format('opentype')";
		}

		// SVG - Legacy iOS
		if (!empty($font_data['svg']) && is_array($font_data['svg']) && !empty($font_data['svg']['url'])) {
			$src_parts[] = "url('" . esc_url($font_data['svg']['url']) . "') format('svg')";
		}

		if (empty($src_parts)) {
			return '';
		}

		// Join with comma and newline for readability
		return implode(",\n\t\t", $src_parts);
	}

	/**
	 * Escape font family name
	 *
	 * @param string $font_family Font family name
	 * @return string Escaped font family
	 */
	private function escape_font_family($font_family) {
		// Remove any existing quotes
		$font_family = str_replace(['"', "'"], '', $font_family);
		// Escape special characters
		return esc_attr($font_family);
	}
}
