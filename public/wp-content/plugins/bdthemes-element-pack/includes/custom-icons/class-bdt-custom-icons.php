<?php
/**
 * Element Pack Custom Icons Handler
 * Handles uploads, metaboxes, and AJAX operations
 *
 * @package ElementPack\Includes\CustomIcons
 */

namespace ElementPack\Includes\CustomIcons;

if (!defined('ABSPATH')) {
	exit;
}

class BDT_Custom_Icons {

	const META_KEY = 'bdt_custom_icon_set_config';
	const OPTION_NAME = 'bdt_custom_icon_sets_config';

	/**
	 * Instance
	 *
	 * @var BDT_Custom_Icons
	 */
	private static $_instance = null;

	/**
	 * Current post ID
	 *
	 * @var int
	 */
	public $current_post_id = 0;

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
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		if (is_admin()) {
			add_action('add_meta_boxes_' . BDT_Custom_Icons_Manager::CPT, [$this, 'add_meta_box']);
			add_action('save_post_' . BDT_Custom_Icons_Manager::CPT, [$this, 'save_post_meta'], 10, 3);
			add_filter('display_post_states', [$this, 'display_post_states'], 10, 2);
			add_action('manage_' . BDT_Custom_Icons_Manager::CPT . '_posts_custom_column', [$this, 'render_columns'], 10, 2);
			add_filter('enter_title_here', [$this, 'update_enter_title_here'], 10, 2);
			add_filter('manage_' . BDT_Custom_Icons_Manager::CPT . '_posts_columns', [$this, 'manage_columns'], 100);
			add_action('current_screen', [$this, 'add_custom_icon_templates']);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		}

		add_action('transition_post_status', [$this, 'transition_post_status'], 10, 3);
		add_action('before_delete_post', [$this, 'handle_delete_icon_set']);
		add_filter('elementor/editor/localize_settings', [$this, 'add_custom_icons_url']);

		// AJAX
		add_action('wp_ajax_bdt_custom_icon_upload', [$this, 'custom_icons_upload_handler']);
	}

	/**
	 * Add meta box
	 */
	public function add_meta_box() {
		add_meta_box(
			'bdt-custom-icons-metabox',
			esc_html__('Icon Set Upload', 'bdthemes-element-pack'),
			[$this, 'render_metabox'],
			BDT_Custom_Icons_Manager::CPT,
			'normal',
			'default'
		);
	}

	/**
	 * Render metabox
	 */
	public function render_metabox($post) {
		wp_enqueue_media();

		$save_data = self::get_icon_set_config($post->ID);
		
		wp_nonce_field('bdt_save_icon_set', 'bdt_icon_set_nonce');
		
		?>
		<div class="bdt-custom-icons-metabox">
			<div class="bdt-icon-upload-area">
				<div class="bdt-dropzone" id="bdt-icon-dropzone">
					<input type="file" 
						   id="bdt-icon-file-input" 
						   accept=".zip,application/zip,application/x-zip,application/x-zip-compressed" 
						   style="display:none;" />
					<div class="bdt-dropzone-content">
						<span class="dashicons dashicons-upload"></span>
						<h3><?php esc_html_e('Drop your icon set .zip file here', 'bdthemes-element-pack'); ?></h3>
						<p><?php esc_html_e('or click to browse', 'bdthemes-element-pack'); ?></p>
						<p class="description">
							<?php esc_html_e('Supported formats: IcoMoon, Remixicon, Icofont', 'bdthemes-element-pack'); ?>
						</p>
					</div>
				</div>
				<div class="bdt-upload-progress" style="display:none;">
					<div class="bdt-progress-bar">
						<div class="bdt-progress-fill"></div>
					</div>
					<p class="bdt-progress-text"><?php esc_html_e('Uploading...', 'bdthemes-element-pack'); ?></p>
				</div>
			</div>

			<?php if ($save_data): 
				$data = json_decode($save_data, true);
				if ($data && isset($data['name'])):
			?>
			<div class="bdt-icon-set-info">
				<h4><?php esc_html_e('Current Icon Set', 'bdthemes-element-pack'); ?></h4>
				<table class="widefat">
					<tr>
						<td><strong><?php esc_html_e('Name:', 'bdthemes-element-pack'); ?></strong></td>
						<td><?php echo esc_html($data['label'] ?? $data['name']); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e('Prefix:', 'bdthemes-element-pack'); ?></strong></td>
						<td><code><?php echo esc_html($data['prefix'] ?? ''); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e('Icons Count:', 'bdthemes-element-pack'); ?></strong></td>
						<td><?php echo esc_html($data['count'] ?? 0); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e('Type:', 'bdthemes-element-pack'); ?></strong></td>
						<td><?php echo esc_html($data['type'] ?? 'unknown'); ?></td>
					</tr>
				</table>
			</div>
			<?php endif; endif; ?>

			<input type="hidden" 
				   name="<?php echo esc_attr(self::META_KEY); ?>" 
				   id="bdt-icon-config" 
				   value="<?php echo esc_attr($save_data); ?>" />
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets($hook) {
		global $post_type;

		if (BDT_Custom_Icons_Manager::CPT !== $post_type) {
			return;
		}

		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'bdt-custom-icons-admin',
			BDT_CUSTOM_ICONS_URL . 'assets/css/custom-icons.css',
			[],
			BDTEP_VER
		);

		wp_enqueue_script(
			'bdt-custom-icons-admin',
			BDT_CUSTOM_ICONS_URL . 'assets/js/custom-icons' . $suffix . '.js',
			['jquery'],
			BDTEP_VER,
			true
		);

		wp_localize_script('bdt-custom-icons-admin', 'bdtCustomIcons', [
			'ajaxUrl'   => admin_url('admin-ajax.php'),
			'adminUrl'  => admin_url(),
			'nonce'     => wp_create_nonce('bdt-custom-icons'),
			'postId'    => get_the_ID(),
			'editUrl'   => get_the_ID() ? get_edit_post_link(get_the_ID(), '') : '',
			'strings'   => [
				'uploading'        => esc_html__('Uploading...', 'bdthemes-element-pack'),
				'processing'       => esc_html__('Processing...', 'bdthemes-element-pack'),
				'success'          => esc_html__('Icon set uploaded successfully!', 'bdthemes-element-pack'),
				'error'            => esc_html__('Error uploading icon set', 'bdthemes-element-pack'),
				'invalidFile'      => esc_html__('Please upload a valid .zip file', 'bdthemes-element-pack'),
				'duplicatePrefix'  => esc_html__('Warning: This icon set prefix already exists!', 'bdthemes-element-pack'),
			]
		]);
	}

	/**
	 * Save post meta
	 */
	public function save_post_meta($post_id, $post, $update) {
		// Security checks
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}

		if (!isset($_POST['bdt_icon_set_nonce']) || !wp_verify_nonce($_POST['bdt_icon_set_nonce'], 'bdt_save_icon_set')) {
			return $post_id;
		}

		if (!isset($_POST[self::META_KEY])) {
			return delete_post_meta($post_id, self::META_KEY);
		}

		$json = json_decode(stripslashes($_POST[self::META_KEY]), true);
		
		if ($json) {
			foreach ($json as $property => $value) {
				$json[$property] = $this->sanitize_text_field_recursive($value);
			}

			update_post_meta($post_id, self::META_KEY, wp_json_encode($json));
			self::clear_icon_list_option();
		}

		return $post_id;
	}

	/**
	 * Custom icons upload handler (AJAX)
	 */
	public function custom_icons_upload_handler() {
		check_ajax_referer('bdt-custom-icons', 'nonce');

		if (!current_user_can(BDT_Custom_Icons_Manager::CAPABILITY)) {
			wp_send_json_error(['message' => esc_html__('Permission denied', 'bdthemes-element-pack')]);
		}

		$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		
		if (!$post_id) {
			wp_send_json_error(['message' => esc_html__('Invalid post ID', 'bdthemes-element-pack')]);
		}

		$this->current_post_id = $post_id;
		$results = $this->upload_and_extract_zip();

		if (is_wp_error($results)) {
			wp_send_json_error(['message' => $results->get_error_message()]);
		}

		$supported_icon_sets = BDT_Custom_Icons_Manager::get_supported_icon_sets();
		
		foreach ($supported_icon_sets as $key => $handler) {
			$icon_set_handler = new $handler($results['directory']);

			if (!$icon_set_handler->is_valid()) {
				continue;
			}

			$icon_set_handler->handle_new_icon_set();
			$icon_set_handler->move_files($this->current_post_id);
			$config = $icon_set_handler->build_config();

			// Check for duplicate prefix
			if (self::icon_set_prefix_exists($config['prefix'], $post_id)) {
				$config['duplicate_prefix'] = true;
			}

			// Save config to post meta immediately
			update_post_meta($post_id, self::META_KEY, wp_json_encode($config));
			
			// Clear cache
			self::clear_icon_list_option();
			
			// Clear Elementor icon cache
			delete_option('elementor_custom_icons_cache');
			delete_transient('elementor_custom_icons');

			wp_send_json_success(['config' => $config]);
		}

		wp_send_json_error(['message' => esc_html__('Unsupported icon set format', 'bdthemes-element-pack')]);
	}

	/**
	 * Upload and extract zip
	 */
	private function upload_and_extract_zip() {
		if (!isset($_FILES['file'])) {
			return new \WP_Error('no_file', esc_html__('No file uploaded', 'bdthemes-element-pack'));
		}

		$file = $_FILES['file'];
		$filename = $file['name'];
		$ext = pathinfo($filename, PATHINFO_EXTENSION);

		if ('zip' !== $ext) {
			return new \WP_Error('invalid_file', esc_html__('Only zip files are allowed', 'bdthemes-element-pack'));
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		
		$upload_result = wp_handle_upload($file, ['test_form' => false]);

		if (isset($upload_result['error'])) {
			return new \WP_Error('upload_error', $upload_result['error']);
		}

		$zip_file = $upload_result['file'];
		$extract_to = trailingslashit(get_temp_dir() . pathinfo($zip_file, PATHINFO_FILENAME));

		$unzipped = $this->extract_zip($zip_file, $extract_to);

		if (is_wp_error($unzipped)) {
			return $unzipped;
		}

		$filesystem = $this->get_wp_filesystem();
		$source_files = array_keys($filesystem->dirlist($extract_to));

		if (empty($source_files)) {
			return new \WP_Error('empty_archive', esc_html__('Empty or incompatible archive', 'bdthemes-element-pack'));
		}

		$directory = (1 === count($source_files) && $filesystem->is_dir($extract_to . $source_files[0])) 
			? $extract_to . trailingslashit($source_files[0]) 
			: $extract_to;

		return [
			'directory'    => $directory,
			'extracted_to' => $extract_to,
		];
	}

	/**
	 * Extract zip file
	 */
	private function extract_zip($file, $to) {
		$valid_extensions = ['css', 'eot', 'html', 'json', 'otf', 'svg', 'ttf', 'txt', 'woff', 'woff2'];

		$zip = new \ZipArchive();
		$zip->open($file);

		$valid_entries = [];

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$zipped_file_name = $zip->getNameIndex($i);
			$dirname = pathinfo($zipped_file_name, PATHINFO_DIRNAME);

			// Skip __MACOSX directory
			if ('__MACOSX/' === substr($dirname, 0, 9)) {
				continue;
			}

			$zipped_extension = pathinfo($zipped_file_name, PATHINFO_EXTENSION);

			// Skip files with traversal paths
			if (strpos($zipped_file_name, '..') !== false) {
				continue;
			}

			if (in_array($zipped_extension, $valid_extensions, true)) {
				$valid_entries[] = $zipped_file_name;
			}
		}

		$unzip_result = false;

		if (!empty($valid_entries)) {
			$unzip_result = $zip->extractTo($to, $valid_entries);
		}

		$zip->close();

		if (!$unzip_result) {
			$unzip_result = new \WP_Error('extract_error', esc_html__('Could not extract archive', 'bdthemes-element-pack'));
		}

		@unlink($file);

		return $unzip_result;
	}

	/**
	 * Get icon set config
	 */
	public static function get_icon_set_config($id) {
		return get_post_meta($id, self::META_KEY, true);
	}

	/**
	 * Handle delete icon set
	 */
	public function handle_delete_icon_set($post_id) {
		if (BDT_Custom_Icons_Manager::CPT !== get_post_type($post_id)) {
			return;
		}

		// Remove icon set directory
		$icon_set_dir = get_post_meta($post_id, '_bdt_icon_set_path', true);
		
		if (!empty($icon_set_dir) && is_dir($icon_set_dir)) {
			$this->get_wp_filesystem()->rmdir($icon_set_dir, true);
		}

		self::clear_icon_list_option();
	}

	/**
	 * Clear icon list option
	 */
	public static function clear_icon_list_option() {
		delete_option(self::OPTION_NAME);
	}

	/**
	 * Display post states
	 */
	public function display_post_states($post_states, $post) {
		if ('publish' !== $post->post_status || BDT_Custom_Icons_Manager::CPT !== $post->post_type) {
			return $post_states;
		}

		$data = json_decode(self::get_icon_set_config($post->ID), true);
		
		if (!empty($data['count'])) {
			echo sprintf('<span class="bdt-icon-count">%d icons</span>', esc_html($data['count']));
		}

		return $post_states;
	}

	/**
	 * Render columns
	 */
	public function render_columns($column, $post_id) {
		if ('icons_prefix' === $column) {
			$data = json_decode(self::get_icon_set_config($post_id), true);
			
			if (!empty($data['prefix'])) {
				echo '<code>' . esc_html($data['prefix']) . '</code>';
			}
		}

		if ('icons_count' === $column) {
			$data = json_decode(self::get_icon_set_config($post_id), true);
			
			if (!empty($data['count'])) {
				echo esc_html($data['count']);
			}
		}

		if ('icons_type' === $column) {
			$data = json_decode(self::get_icon_set_config($post_id), true);
			
			if (!empty($data['type'])) {
				echo '<span class="bdt-icon-type">' . esc_html(ucfirst($data['type'])) . '</span>';
			}
		}
	}

	/**
	 * Manage columns
	 */
	public function manage_columns($columns) {
		return [
			'cb'           => '<input type="checkbox" />',
			'title'        => esc_html__('Icon Set Name', 'bdthemes-element-pack'),
			'icons_prefix' => esc_html__('Prefix', 'bdthemes-element-pack'),
			'icons_count'  => esc_html__('Icons', 'bdthemes-element-pack'),
			'icons_type'   => esc_html__('Type', 'bdthemes-element-pack'),
			'date'         => esc_html__('Date', 'bdthemes-element-pack'),
		];
	}

	/**
	 * Update enter title here
	 */
	public function update_enter_title_here($title, $post) {
		if (isset($post->post_type) && BDT_Custom_Icons_Manager::CPT === $post->post_type) {
			return esc_html__('Enter Icon Set Name', 'bdthemes-element-pack');
		}

		return $title;
	}

	/**
	 * Add custom icon templates
	 */
	public function add_custom_icon_templates($current_screen) {
		if (BDT_Custom_Icons_Manager::CPT !== $current_screen->id || 'post' !== $current_screen->base) {
			return;
		}

		if (file_exists(BDT_CUSTOM_ICONS_PATH . 'templates.php')) {
			require_once BDT_CUSTOM_ICONS_PATH . 'templates.php';
		}
	}

	/**
	 * Register icon libraries
	 */
	/**
	 * Register icon libraries (DEPRECATED - now handled in init.php)
	 * Kept for backwards compatibility
	 */
	public function register_icon_libraries($additional_sets) {
		// This method is deprecated and no longer used
		// Icon registration is now handled in init.php via register_custom_icon_tabs()
		return $additional_sets;
	}

	/**
	 * Add custom icons URL
	 */
	public function add_custom_icons_url($config) {
		$config['bdtCustomIconsURL'] = admin_url(BDT_Custom_Icons_Manager::MENU_SLUG);
		return $config;
	}

	/**
	 * Get custom icons config
	 */
	public static function get_custom_icons_config() {
		$config = get_option(self::OPTION_NAME, false);

		if (false === $config) {
			$icons = new \WP_Query([
				'post_type'      => BDT_Custom_Icons_Manager::CPT,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			]);

			$config = [];

			foreach ($icons->posts as $icon_set) {
				$set_config = json_decode(self::get_icon_set_config($icon_set->ID), true);
				
				if (!$set_config) {
					continue;
				}

				$set_config['custom_icon_post_id'] = $icon_set->ID;
				$set_config['label'] = $icon_set->post_title;

				if (isset($set_config['fetchJson'])) {
					unset($set_config['icons']);
				}

				// Use post_id as key to allow multiple icon sets of the same type
				$config['icon_set_' . $icon_set->ID] = $set_config;
			}

			update_option(self::OPTION_NAME, $config);
		}

		return $config;
	}

	/**
	 * Check if icon set prefix exists
	 */
	public static function icon_set_prefix_exists($prefix, $exclude_post_id = 0) {
		$config = self::get_custom_icons_config();

		if (empty($config)) {
			return false;
		}

		foreach ($config as $icon_set_name => $icon_config) {
			if (isset($icon_config['custom_icon_post_id']) && $icon_config['custom_icon_post_id'] == $exclude_post_id) {
				continue;
			}

			if ($prefix === $icon_config['prefix']) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Transition post status
	 */
	public function transition_post_status($new_status, $old_status, $post) {
		if (BDT_Custom_Icons_Manager::CPT !== $post->post_type) {
			return;
		}

		if ('publish' === $old_status && 'publish' !== $new_status) {
			$this->clear_icon_list_option();
		}
	}

	/**
	 * Get WP Filesystem
	 */
	private function get_wp_filesystem() {
		global $wp_filesystem;

		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * Sanitize text field recursively
	 */
	private function sanitize_text_field_recursive($value) {
		if (is_array($value)) {
			return array_map([$this, 'sanitize_text_field_recursive'], $value);
		}

		return sanitize_text_field($value);
	}
}
