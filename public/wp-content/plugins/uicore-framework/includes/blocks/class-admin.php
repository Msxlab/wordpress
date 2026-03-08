<?php
namespace UiCore\Blocks;

class Admin {
    public function __construct() {
        add_action('enqueue_block_assets', [$this, 'enqueue_block_assets'], 50);
        add_filter('uicore_blocks_data', [$this, 'add_blocks_data'], 10, 99);
    }

    public function enqueue_block_assets() {
        $custom_font_css = \UiCore\Assets::print_custom_font_link();
        if($custom_font_css) {
            \wp_register_style('uicore-bl-custom-fonts', false);
            \wp_enqueue_style('uicore-bl-custom-fonts');
            \wp_add_inline_style('uicore-bl-custom-fonts', $custom_font_css);
        }
    }

    public function add_blocks_data($data) {
        $data['features'] = [
            'custom_css' => true,
        ];
        return $data;
    }
}
new Admin();