<?php

namespace ElementPack\Includes\MegaMenu;

use ElementPack\Base\Handler_Api;

defined('ABSPATH') || exit;

/**
 * Mega Menu API class extending Handler_Api
 * Provides REST API endpoints for mega menu functionality
 */
class Mega_Menu_API extends Handler_Api {

    /**
     * Configure the API prefix
     */
    public function config() {
        $this->prefix = 'megamenu';
    }

    /**
     * Get mega menu content via GET request
     * Endpoint: GET /element-pack/v1/megamenu/ajax_content
     */
    public function get_ajax_content() {
        $menu_item_id = intval($this->request['id']);

        if ('publish' !== get_post_status($menu_item_id) || post_password_required($menu_item_id)) {
            return new \WP_Error(
                'invalid_post',
                esc_html__('Invalid or protected content', 'bdthemes-element-pack'),
                ['status' => 404]
            );
        }

        // Check if Elementor is available
        if (!class_exists('\Elementor\Plugin')) {
            return new \WP_Error(
                'elementor_not_found',
                esc_html__('Elementor plugin is required', 'bdthemes-element-pack'),
                ['status' => 500]
            );
        }

        $elementor = \Elementor\Plugin::instance();
        $output    = $elementor->frontend->get_builder_content_for_display($menu_item_id);

        return [
            'content' => $output,
            'post_id' => $menu_item_id,
        ];
    }
}

new Mega_Menu_API();
