<?php
/**
 * Element Pack Custom Fonts Admin
 * Handles admin UI, meta boxes, and file uploads
 *
 * @package ElementPack\Includes\CustomFonts
 */

namespace ElementPack\Includes\CustomFonts;

if (!defined('ABSPATH')) {
	exit;
}

class EP_Custom_Fonts_Admin {

	private static $_instance = null;

	public static function instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	private function __construct() {
		add_action('admin_menu', [$this, 'add_admin_menu'], 203);  // After Theme Builder (202)
		add_filter('parent_file', [$this, 'set_parent_file']);
		add_filter('submenu_file', [$this, 'set_submenu_file']);
		add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
		add_action('save_post_' . EP_Custom_Fonts_Manager::CPT, [$this, 'save_meta_box']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		add_filter('manage_' . EP_Custom_Fonts_Manager::CPT . '_posts_columns', [$this, 'customize_columns']);
		add_action('manage_' . EP_Custom_Fonts_Manager::CPT . '_posts_custom_column', [$this, 'render_column'], 10, 2);
		add_filter('post_row_actions', [$this, 'customize_row_actions'], 10, 2);
		
		// Clear cache on save/delete
		add_action('save_post_' . EP_Custom_Fonts_Manager::CPT, [$this, 'clear_cache']);
		add_action('before_delete_post', [$this, 'clear_cache']);
	}

	/**
	 * Add admin menu (positioned after Theme Builder)
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'element_pack_options',
			esc_html__('Custom Fonts', 'bdthemes-element-pack'),
			esc_html__('Custom Fonts', 'bdthemes-element-pack'),
			'manage_options',
			'edit.php?post_type=' . EP_Custom_Fonts_Manager::CPT,
			'',
			71  // Position right after Theme Builder (70)
		);
	}

	/**
	 * Set parent file to nest under Element Pack
	 */
	public function set_parent_file($parent_file) {
		global $current_screen;
		
		if ($current_screen && $current_screen->post_type === EP_Custom_Fonts_Manager::CPT) {
			return 'element_pack_options';
		}
		
		return $parent_file;
	}

	/**
	 * Set submenu file to highlight the menu item
	 */
	public function set_submenu_file($submenu_file) {
		global $current_screen;
		
		if ($current_screen && $current_screen->post_type === EP_Custom_Fonts_Manager::CPT) {
			return 'edit.php?post_type=' . EP_Custom_Fonts_Manager::CPT;
		}
		
		return $submenu_file;
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ep_font_files',
			esc_html__('Font Files', 'bdthemes-element-pack'),
			[$this, 'render_meta_box'],
			EP_Custom_Fonts_Manager::CPT,
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box
	 */
	public function render_meta_box($post) {
		wp_nonce_field('ep_save_font_meta', 'ep_font_meta_nonce');

		$font_files = get_post_meta($post->ID, EP_Custom_Fonts_Manager::FONT_META_KEY, true);
		if (!is_array($font_files)) {
			$font_files = [];
		}

		// Add at least one empty row
		if (empty($font_files)) {
			$font_files = [
				[
					'font_weight' => '400',
					'font_style'  => 'normal',
					'woff2'       => '',
					'woff'        => '',
					'ttf'         => '',
					'otf'         => '',
					'eot'         => '',
					'svg'         => '',
				]
			];
		}

		?>
		<div class="ep-custom-fonts-wrapper">
			<p class="description">
				<?php esc_html_e('Upload font files for different weights and styles. For best browser compatibility, upload WOFF2 and WOFF formats.', 'bdthemes-element-pack'); ?>
			</p>

			<div class="ep-font-variations">
				<?php foreach ($font_files as $index => $font_file) : ?>
					<div class="ep-font-variation" data-index="<?php echo esc_attr($index); ?>">
						<div class="ep-font-variation-header">
							<h4><?php echo esc_html__('Font Variation', 'bdthemes-element-pack'); ?></h4>
							<!-- <button type="button" class="button ep-remove-variation" <?php echo ($index === 0) ? 'disabled' : ''; ?>>
								<?php //esc_html_e('Remove', 'bdthemes-element-pack'); ?>
							</button> -->
						</div>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label><?php esc_html_e('Font Weight', 'bdthemes-element-pack'); ?></label>
								</th>
								<td>
									<select name="ep_font_files[<?php echo esc_attr($index); ?>][font_weight]" class="regular-text">
										<?php
										$weights = [
											'100' => '100 - Thin',
											'200' => '200 - Extra Light',
											'300' => '300 - Light',
											'400' => '400 - Normal',
											'500' => '500 - Medium',
											'600' => '600 - Semi Bold',
											'700' => '700 - Bold',
											'800' => '800 - Extra Bold',
											'900' => '900 - Black',
										];
										foreach ($weights as $value => $label) {
											$selected = selected(isset($font_file['font_weight']) ? $font_file['font_weight'] : '400', $value, false);
											echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e('Font Style', 'bdthemes-element-pack'); ?></label>
								</th>
								<td>
									<select name="ep_font_files[<?php echo esc_attr($index); ?>][font_style]" class="regular-text">
										<option value="normal" <?php selected(isset($font_file['font_style']) ? $font_file['font_style'] : 'normal', 'normal'); ?>>
											<?php esc_html_e('Normal', 'bdthemes-element-pack'); ?>
										</option>
										<option value="italic" <?php selected(isset($font_file['font_style']) ? $font_file['font_style'] : '', 'italic'); ?>>
											<?php esc_html_e('Italic', 'bdthemes-element-pack'); ?>
										</option>
										<option value="oblique" <?php selected(isset($font_file['font_style']) ? $font_file['font_style'] : '', 'oblique'); ?>>
											<?php esc_html_e('Oblique', 'bdthemes-element-pack'); ?>
										</option>
									</select>
								</td>
							</tr>

							<?php
							$formats = [
								'woff2' => 'WOFF2 (Recommended)',
								'woff'  => 'WOFF',
								'ttf'   => 'TTF',
								'otf'   => 'OTF',
								'eot'   => 'EOT (IE Support)',
								'svg'   => 'SVG (Legacy)',
							];

							foreach ($formats as $format => $label) :
								$file_data = isset($font_file[$format]) ? $font_file[$format] : '';
								$file_url = '';
								$file_id = '';

								if (is_array($file_data)) {
									$file_url = isset($file_data['url']) ? $file_data['url'] : '';
									$file_id = isset($file_data['id']) ? $file_data['id'] : '';
								}
								?>
								<tr class="ep-font-file-row">
									<th scope="row">
										<label><?php echo esc_html($label); ?></label>
									</th>
									<td>
										<input type="hidden" 
											   name="ep_font_files[<?php echo esc_attr($index); ?>][<?php echo esc_attr($format); ?>][id]" 
											   class="ep-font-file-id" 
											   value="<?php echo esc_attr($file_id); ?>">
										<input type="text" 
											   name="ep_font_files[<?php echo esc_attr($index); ?>][<?php echo esc_attr($format); ?>][url]" 
											   class="regular-text ep-font-file-url" 
											   value="<?php echo esc_url($file_url); ?>" 
											   readonly>
										<button type="button" 
												class="button ep-upload-font-file" 
												data-format="<?php echo esc_attr($format); ?>"
												data-index="<?php echo esc_attr($index); ?>">
											<?php esc_html_e('Upload', 'bdthemes-element-pack'); ?>
										</button>
										<?php if ($file_url) : ?>
											<button type="button" class="button ep-remove-font-file">
												<?php esc_html_e('Remove', 'bdthemes-element-pack'); ?>
											</button>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- <p>
				<button type="button" class="button button-primary ep-add-variation">
					<?php //esc_html_e('Add Font Variation', 'bdthemes-element-pack'); ?>
				</button>
			</p> -->
		</div>
		<?php
	}

	/**
	 * Save meta box
	 */
	public function save_meta_box($post_id) {
		// Security checks
		if (!isset($_POST['ep_font_meta_nonce']) || !wp_verify_nonce($_POST['ep_font_meta_nonce'], 'ep_save_font_meta')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Save font files
		if (isset($_POST['ep_font_files']) && is_array($_POST['ep_font_files'])) {
			$font_files = [];

			foreach ($_POST['ep_font_files'] as $variation) {
				$font_data = [
					'font_weight' => sanitize_text_field($variation['font_weight']),
					'font_style'  => sanitize_text_field($variation['font_style']),
				];

				$formats = ['woff2', 'woff', 'ttf', 'otf', 'eot', 'svg'];
				foreach ($formats as $format) {
					if (isset($variation[$format]) && is_array($variation[$format])) {
						$font_data[$format] = [
							'id'  => absint($variation[$format]['id']),
							'url' => esc_url_raw($variation[$format]['url']),
						];
					} else {
						$font_data[$format] = '';
					}
				}

				$font_files[] = $font_data;
			}

			update_post_meta($post_id, EP_Custom_Fonts_Manager::FONT_META_KEY, $font_files);
			
			// Delete cached CSS to regenerate
			delete_post_meta($post_id, EP_Custom_Fonts_Manager::FONT_FACE_META_KEY);
		}
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets($hook) {
		global $post_type;

		if (EP_Custom_Fonts_Manager::CPT !== $post_type) {
			return;
		}

		// Enqueue WordPress media uploader
		wp_enqueue_media();

		// Enqueue admin styles
		$direction_suffix = is_rtl() ? '.rtl' : '';
		wp_enqueue_style(
			'ep-custom-fonts-admin',
			EP_CUSTOM_FONTS_URL . 'assets/css/admin' . $direction_suffix . '.css',
			[],
			BDTEP_VER
		);

		// Enqueue admin scripts
		wp_enqueue_script(
			'ep-custom-fonts-admin',
			EP_CUSTOM_FONTS_URL . 'assets/js/admin.js',
			['jquery', 'media-upload'],
			BDTEP_VER,
			true
		);

		wp_localize_script('ep-custom-fonts-admin', 'EPCustomFontsAdmin', [
			'uploadTitle'  => esc_html__('Select Font File', 'bdthemes-element-pack'),
			'uploadButton' => esc_html__('Use this file', 'bdthemes-element-pack'),
		]);
	}

	/**
	 * Customize columns
	 */
	public function customize_columns($columns) {
		$new_columns = [];
		$new_columns['cb'] = $columns['cb'];
		$new_columns['title'] = esc_html__('Font Name', 'bdthemes-element-pack');
		$new_columns['preview'] = esc_html__('Preview', 'bdthemes-element-pack');
		$new_columns['variations'] = esc_html__('Variations', 'bdthemes-element-pack');
		$new_columns['date'] = $columns['date'];
		return $new_columns;
	}

	/**
	 * Render custom column
	 */
	public function render_column($column, $post_id) {
		if ($column === 'preview') {
			$font_family = get_the_title($post_id);
			$font_css = EP_Custom_Fonts_Manager::instance()->get_font_face_css($font_family);
			
			if ($font_css) {
				$unique_id = 'ep-font-preview-' . $post_id;
				echo '<style>' . $font_css . '</style>';
				echo '<div class="ep-font-preview" style="font-family: \'' . esc_attr($font_family) . '\';">';
				echo esc_html__('Think Big, Act Smarter.', 'bdthemes-element-pack');
				echo '</div>';
			} else {
				echo '<span class="ep-font-no-preview">' . esc_html__('No files', 'bdthemes-element-pack') . '</span>';
			}
		}
		
		if ($column === 'variations') {
			$font_files = get_post_meta($post_id, EP_Custom_Fonts_Manager::FONT_META_KEY, true);
			if (is_array($font_files)) {
				echo '<strong>' . count($font_files) . '</strong> ' . esc_html(_n('variation', 'variations', count($font_files), 'bdthemes-element-pack'));
			} else {
				echo '—';
			}
		}
	}

	/**
	 * Customize row actions
	 */
	public function customize_row_actions($actions, $post) {
		if ($post->post_type === EP_Custom_Fonts_Manager::CPT) {
			unset($actions['inline hide-if-no-js']);
			unset($actions['view']);
		}
		return $actions;
	}

	/**
	 * Clear cache
	 */
	public function clear_cache() {
		EP_Custom_Fonts_Manager::instance()->clear_fonts_cache();
	}
}
