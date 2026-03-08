<?php

namespace ElementPack\Modules\ShapeBuilder;

use ElementPack\Base\Element_Pack_Module_Base;

if (!defined('ABSPATH')) exit;

// include required files
require_once BDTEP_MODULES_PATH . 'shape-builder/controls.php';
require_once BDTEP_MODULES_PATH . 'shape-builder/print-template.php';
require_once BDTEP_MODULES_PATH . 'shape-builder/assets.php';
require_once BDTEP_MODULES_PATH . 'shape-builder/markup.php';

class Module extends Element_Pack_Module_Base
{
	use \Shape_Builder_Controls_Trait;
	use \Shape_Builder_Print_Template_Trait;
	use \Shape_Builder_Assets_Trait;
	use \Shape_Builder_Markup_Trait;

	public function __construct()
	{
		parent::__construct();
		$this->add_actions();
	}

	public function get_name()
	{
		return 'bdt-shape-builder';
	}

	protected function add_actions()
	{
		// Register scripts
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

		// Section support
		add_action('elementor/element/section/section_advanced/after_section_end', [$this, 'register_shape_builder_controls'], 10, 2);
		add_filter('elementor/section/print_template', [$this, 'print_shape_builder_template'], 10, 2);
		add_action('elementor/frontend/section/before_render', [$this, 'should_script_enqueue']);
		add_action('elementor/section/after_render', [$this, 'render_shape_builder_markup_container'], 10, 2);

		// Container support
		add_action('elementor/element/container/section_layout/after_section_end', [$this, 'register_shape_builder_controls'], 10, 2);
		add_filter('elementor/container/print_template', [$this, 'print_shape_builder_template'], 10, 2);
		add_action('elementor/frontend/container/before_render', [$this, 'should_script_enqueue']);
		add_action('elementor/frontend/container/after_render', [$this, 'render_shape_builder_markup_container'], 10, 2);

		// Widget support
		add_action('elementor/element/common/_section_style/after_section_end', [$this, 'register_shape_builder_controls'], 10, 2);
		add_filter('elementor/widget/print_template', [$this, 'print_shape_builder_template'], 10, 2);
		add_filter('elementor/widget/render_content', [$this, 'render_shape_builder_markup'], 10, 2);
		add_action('elementor/frontend/widget/before_render', [$this, 'should_script_enqueue']);

		// Editor scripts
		add_action('elementor/editor/after_enqueue_scripts', [$this, 'shape_builder_editor_scripts'], 10, 1);
		
		// Preview scripts
		add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_scripts']);
	}

	/**
	 * Register and enqueue scripts for editor preview
	 */
	public function enqueue_scripts()
	{
		// Register the script
		$file_name = 'ep-shape-builder' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min') . '.js';
		wp_register_script(
			'ep-shape-builder-frontend',
			BDTEP_ASSETS_URL . 'js/modules/' . $file_name,
			['jquery', 'gsap'],
			BDTEP_VER,
			true
		);
		
		// Enqueue in preview/editor mode
		if (\ElementPack\Element_Pack_Loader::elementor()->preview->is_preview_mode() || \ElementPack\Element_Pack_Loader::elementor()->editor->is_edit_mode()) {
			wp_enqueue_script('gsap');
			wp_enqueue_script('ep-shape-builder-frontend');
		}
	}

	/**
	 * Check if scripts should be enqueued based on settings
	 */
	public function should_script_enqueue($element)
	{
		if ('yes' === $element->get_settings_for_display('bdt_shape_builder_enable')) {
			wp_enqueue_script('gsap');
			wp_enqueue_script('ep-shape-builder-frontend');
			wp_enqueue_style('ep-shape-builder');
		}
	}

	public function render_shape_builder_markup_container($element)
	{		

		$type = $element->get_type(); // 'section' | 'container' | 'widget'

		// Only target sections/containers
		if (! in_array($type, ['section', 'container'], true)) {
			return;
		}

		// Prevent duplicate renders by checking element depth
		$data = $element->get_data();
		// $is_inner = ! empty($data['isInner']);

		// Example: only add markup for *top-level* sections/containers
		// if ($is_inner) {
		// 	return; // skip inner to prevent duplication
		// }

		echo $this->render_shape_builder_markup('', $element);
	}
}
