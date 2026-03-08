<?php

trait Shape_Builder_Markup_Trait
{

    /**
     * Get shape (predefined or custom)
     */
    public function get_shape($shape_type, $settings)
    {
        // ✅ Handle custom uploaded SVG
        if ($shape_type === 'custom' && ! empty($settings['custom_shape_upload']['id'])) {
            $svg = \Elementor\Core\Files\File_Types\Svg::get_inline_svg($settings['custom_shape_upload']['id']);

            return [
                'is_custom'  => true,
                'custom_svg' => $svg,
            ];
        }

        // ✅ Handle predefined shapes
        foreach ($this->get_shapes() as $shape) {
            if ($shape['id'] === $shape_type) {
                return $shape;
            }
        }

        return null;
    }

    /**
     * Generate shape markup
     */
    protected function generate_shape_markup($settings, $wrapper_id)
    {
        $shape_type = $settings['shape_type'] ?? 'circle';
        $shape_id   = $settings['_id'] ?? wp_unique_id('bdt_shape_');
        $shape      = $this->get_shape($shape_type, $settings);

        if (empty($shape)) {
            return '';
        }

        // Build animation data attributes
        $animation_attrs = '';
        if (isset($settings['shape_builder_animation_popover']) && $settings['shape_builder_animation_popover'] === 'yes') {
            $animation_trigger = $settings['animation_trigger_type'] ?? 'on-load';
            $animation_name = $settings['animation_name'] ?? 'fade-in';
            $animation_duration = $settings['animation_duration']['size'] ?? 1;
            $animation_delay = $settings['animation_delay']['size'] ?? 0;
            $animation_easing = $settings['animation_easing'] ?? 'power2.out';
            $animation_repeat = $settings['animation_repeat'] ?? '0';
            $animation_yoyo = isset($settings['animation_yoyo']) && $settings['animation_yoyo'] === 'yes' ? 'true' : 'false';
            $animation_viewport = $settings['animation_viewport']['size'] ?? 0.1;

            $animation_attrs = sprintf(
                ' data-animation-enabled="true" data-animation-trigger="%s" data-animation-name="%s" data-animation-duration="%s" data-animation-delay="%s" data-animation-easing="%s" data-animation-repeat="%s" data-animation-yoyo="%s" data-animation-viewport="%s"',
                esc_attr($animation_trigger),
                esc_attr($animation_name),
                esc_attr($animation_duration),
                esc_attr($animation_delay),
                esc_attr($animation_easing),
                esc_attr($animation_repeat),
                esc_attr($animation_yoyo),
                esc_attr($animation_viewport)
            );
        }

        // ✅ Custom uploaded SVGs (don't modify markup)
        if (! empty($shape['is_custom'])) {
            return '<div data-wrapper-id="' . esc_attr($wrapper_id) . '" class="bdt-shape-builder bdt-shape-builder-custom elementor-repeater-item-' . esc_attr($shape_id) . '"' . $animation_attrs . '>' .
                $shape['custom_svg'] .
                '</div>';
        }

        // ✅ Predefined SVGs (we can control fill and gradient)
        $viewBox = $shape['viewBox'] ?? '0 0 100 100';
        $path    = $shape['path'] ?? '';
        $fill_type = $settings['shape_fill_type'] ?? 'solid';
        $fill_color = 'currentColor';
        $defs = '';

        if ($fill_type === 'solid') {
            $fill_color = $settings['shape_color'] ?? 'currentColor';
        } elseif ($fill_type === 'gradient') {
            $grad_id = 'grad-' . esc_attr($shape_id);
            $color1 = $settings['shape_gradient_color_1'] ?? '#08AEEC';
            $color2 = $settings['shape_gradient_color_2'] ?? '#20E2AD';
            $loc1 = $settings['shape_gradient_location_1']['size'] ?? 0;
            $loc2 = $settings['shape_gradient_location_2']['size'] ?? 100;
            $angle = $settings['shape_gradient_angle']['size'] ?? 90;

            $defs = "
                <defs>
                    <linearGradient id='{$grad_id}' gradientTransform='rotate({$angle})'>
                        <stop offset='{$loc1}%' stop-color='{$color1}' />
                        <stop offset='{$loc2}%' stop-color='{$color2}' />
                    </linearGradient>
                </defs>
            ";
            $fill_color = "url(#{$grad_id})";
        }

        $svg = "
            <svg viewBox='{$viewBox}' preserveAspectRatio='none'>
                {$defs}
                <path d='{$path}' fill='{$fill_color}'></path>
            </svg>
        ";

        return '<div data-wrapper-id="' . esc_attr($wrapper_id) . '" class="bdt-shape-builder elementor-repeater-item-' . esc_attr($shape_id) . '"' . $animation_attrs . '>' . $svg . '</div>';
    }

    /**
     * Render final output with all shapes
     */
    public function render_shape_builder_markup($content, $widget)
    {
        $settings = $widget->get_settings_for_display();
        
        // Check if Shape Builder is enabled
        if (empty($settings['bdt_shape_builder_enable']) || $settings['bdt_shape_builder_enable'] !== 'yes') {
            return $content;
        }
        
        // Get the Elementor-generated unique ID
        $id = $widget->get_id(); // e.g. 'd123abc'

        // Build the wrapper ID (matches frontend)
        $wrapper_id = 'elementor-element-' . $id;

        if (empty($settings['bdt_shape_builder_list']) || ! is_array($settings['bdt_shape_builder_list'])) {
            return $content;
        }

        $output = '';
        foreach ($settings['bdt_shape_builder_list'] as $shape) {
            $output .= $this->generate_shape_markup($shape, $wrapper_id);
        }

        return $content . $output;
    }
}
