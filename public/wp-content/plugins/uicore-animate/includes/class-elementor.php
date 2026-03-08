<?php

namespace UiCoreAnimate;

use UiCoreAnimate\SplitText;
use UiCoreAnimate\AnimatedBackground as Backgrounds;
use UiCoreAnimate\AnimatedBorder as Borders;
use UiCoreAnimate\Floating;
use UiCoreAnimate\ScrollEffects;

defined('ABSPATH') || exit();

/**
 * Scripts and Styles Class
 */
class Elementor
{
    function __construct()
    {
        // Register new custom animations
        add_filter('elementor/controls/animations/additional_animations', [$this, 'new_animations'], 4);

        //TODO: ADD uicore-the-title and uicore-page-description widgets

        new SplitText();
        new Floating();
        new Backgrounds();
        new Borders();
        new ScrollEffects();

        // Required assets for extending
        add_action('elementor/frontend/section/before_render', [$this, 'should_script_enqueue']);
        add_action('elementor/frontend/container/before_render', [$this, 'should_script_enqueue']);
        add_action('elementor/frontend/widget/before_render', [$this, 'should_script_enqueue']);
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_scripts']);

        add_action('wp_enqueue_scripts', function () {
            $this->enqueue_scripts(null, false);
        });
    }

    /**
     * Updates the Elementor animations list
     *
     * @return array
     */
    public static function new_animations($animations)
    {
        $new_animations = [
            'ZoomOut' => Helper::get_zoom_out_animations_list(),
            'Blur' => Helper::get_blur_animations_list()
        ];
        return \array_merge($animations, $new_animations);
    }

    public function enqueue_scripts($name = null, $enqueue = true)
    {
        $list = [
            'split' => [
                'script'    => true,
                'style'     => true
            ],
            'animated-background' => [
                'script'    => true,
                'style'     => true
            ],
            'animated-border' => [
                'script'    => true,
                'style'     => true
            ],
            'onscroll-effects' => [
                'script'    => true,
                'style'     => false,
            ],
            // Depedencies
            'uicore-ogl' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'deps'
            ],
            'ogl-lib' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'deps'
            ],
            // Background Animations
            'fluid-gradient' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'borealis' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'mist' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'mystic-lake' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'the-shining' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'flux-stripes' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'plasma-line' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'pulse-bubble' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'noir-haze' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'echo-sphere' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'neon-eclipse' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'void-wave' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'bit-wave' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'plasma-line' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'flame' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'halftone' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'light-strings' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'gradient-mesh' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'phase-tunnel' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'perspective-grid' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'liquid-mask' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ],
            'liquid-image' => [
                'script'    => true,
                'style'     => false,
                'directory' => 'backgrounds'
            ]

        ];

        if ($name) {
            $list = [$name => $list[$name]];
        }
        foreach ($list as $type => $data) {

            // TODO: we should review the variables scaping in here

            if ($data['script']) {

                $directory = isset($data['directory'])
                    ? '/assets/js/' . esc_html($data['directory']) . '/'
                    : '/assets/js/';

                if ($enqueue) {
                    // $widget->add_script_depends('ui-e-' . $type);
                    wp_enqueue_script('ui-e-' . $type);
                } else {
                    \wp_register_script('ui-e-' . $type, UICORE_ANIMATE_URL . $directory . $type . '.js', ['jquery'], UICORE_ANIMATE_VERSION, true);
                }
            }
            if ($data['style']) {

                $directory = isset($data['directory'])
                    ? '/assets/css/' . esc_html($data['directory']) . '/'
                    : '/assets/css/';

                if ($enqueue) {
                    wp_enqueue_style('ui-e-' . $type);
                } else {
                    \wp_register_style('ui-e-' . $type, UICORE_ANIMATE_URL . $directory . $type . '.css', [], UICORE_ANIMATE_VERSION);
                }
            }
        }
    }

    public function should_script_enqueue($widget)
    {

        if ('ui-split-animate' === $widget->get_settings_for_display('ui_animate_split')) {
            $this->enqueue_scripts('split');
        }
        if ('' != $widget->get_settings_for_display('uicore_animated_border') || '' != $widget->get_settings_for_display('uicore_animated_border_item')) {
            $this->enqueue_scripts('animated-border');
        }
        if ('' != $widget->get_settings_for_display('uicore_onscroll_effect')) {
            $this->enqueue_scripts('onscroll-effects');
        }
        if ('yes' === $widget->get_settings_for_display('section_fluid_on')) {
            $assets = Backgrounds::get_animated_background_assets($widget->get_settings_for_display('uicore_fluid_animation'));
            foreach ($assets as $asset) {
                $this->enqueue_scripts($asset);
            }
        }
    }
}
