<?php 

trait Shape_Builder_Assets_Trait {
	
	private static $assets_enqueued = false;

    public function enqueue_frontend_assets()
	{
		// Prevent duplicate enqueues
		if (self::$assets_enqueued) {
			return;
		}
		
		self::$assets_enqueued = true;

		// Enqueue styles
		wp_enqueue_style('ep-shape-builder');

		// Enqueue GSAP for animations
		wp_enqueue_script('gsap');

		// Enqueue Shape Builder script
		$file_name = 'ep-shape-builder' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min') . '.js';

		wp_enqueue_script(
			'ep-shape-builder-frontend',
			BDTEP_ASSETS_URL . 'js/modules/' . $file_name,
			['jquery', 'gsap'],
			BDTEP_VER,
			true
		);
	}

    public static function get_filesystem()
	{
		// Check if WP_Filesystem is available
		if (!function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize WP_Filesystem
		WP_Filesystem();
	}

	public function get_shapes()
	{
		self::get_filesystem();
		global $wp_filesystem;
		$shapes_json = $wp_filesystem->get_contents(__DIR__ . '/shapes.json');

		try {
			$shapes = json_decode($shapes_json, true);
		} catch (\Exception $e) {
			return [];
		}

		return $shapes;
	}

	public function shape_builder_editor_scripts()
	{
		$file_name = 'ep-shape-builder' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min') . '.js';

		wp_enqueue_script(
			'ep-shape-builder',
			BDTEP_ASSETS_URL . 'js/modules/' . $file_name,
			['elementor-editor', 'jquery'],
			BDTEP_VER,
			true
		);

		wp_localize_script(
			'ep-shape-builder',
			'epShapeBuilderEditor',
			[
				'shapes' => $this->get_shapes()
			]
		);
	}
}