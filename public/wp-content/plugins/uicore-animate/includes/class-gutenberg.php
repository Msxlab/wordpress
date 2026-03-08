<?php

namespace UiCoreAnimate;

/**
 * Gutenberg Handler
 */
class Gutenberg
{

    /**
     * Constructor function to initialize hooks
     *
     * @return void
     */
    public function __construct()
    {
        // add inline script and styles to gutenberg editor
        add_action('enqueue_block_assets', [$this, 'enqueue_block_assets']);

        //register animated background block
        add_action('init', [$this, 'register_uicore_blocks'], 60);

        // Enqueue frontend scripts for animated background block
        add_action('wp_enqueue_scripts', [$this, 'enqueue_animated_background_frontend_scripts']);
    }


    public function register_uicore_blocks()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        //register animated background block
        register_block_type(UICORE_ANIMATE_PATH . '/assets/build/animated-background/block');
    }

    /**
     * Enqueue block assets for Gutenberg editor
     *
     * @return void
     */
    function enqueue_block_assets()
    {
        if (!is_admin()) {
            return;
        }

        if (is_customize_preview()) {
            return;
        }

        //return if is editing widgets (wp-admin/widgets.php)
        if (\get_current_screen()->id == 'widgets') {
            return;
        }

        // Enqueue editor styles and scripts
        $style = Settings::get_option('uianim_style');
        if (is_array($style)) {
            $style = $style['value'];
        } else {
            $style = 'style1';
        }
        wp_enqueue_style('uianim-style', UICORE_ANIMATE_ASSETS . '/css/' . $style . '.css');
        \wp_add_inline_style('uianim-style', '
          .uicore-animate-scroll {
                animation-fill-mode: both;
                animation-timing-function: linear;
                animation-timeline: view(block);
            }

            .uicore-animate-panel h2 button::after {
                content: "UiCore";
                font-size: 11px;
                font-weight: 500;
                background: #5dbad8;
                color: black;
                padding: 2px 5px;
                border-radius: 3px;
                margin-left: 8px;
            }
        ');

        // Enqueue all possible animation scripts for editor preview
        $background_scripts = [
            'fluid-gradient',
            'borealis',
            'mist',
            'mystic-lake',
            'the-shining',
            'flux-stripes',
            'plasma-line',
            'pulse-bubble',
            'noir-haze',
            'echo-sphere',
            'neon-eclipse',
            'void-wave',
            'bit-wave',
            'flame',
            'halftone',
            'light-strings',
            'gradient-mesh',
            'phase-tunnel',
            'perspective-grid',
            'liquid-mask',
            'liquid-image'
        ];
        foreach ($background_scripts as $script) {
            wp_enqueue_script('uicore-animate-' . $script, UICORE_ANIMATE_ASSETS . '/js/backgrounds/' . $script . '.js', array(), UICORE_ANIMATE_VERSION, true);
        }
        // Enqueue OGL dependency
        wp_enqueue_script('uicore-ogl', UICORE_ANIMATE_ASSETS . '/js/deps/uicore-ogl.js', array(), UICORE_ANIMATE_VERSION, true);

        // Enqueue main block editor script
        \wp_enqueue_script('uicore_animate-editor');

        //enqueue css aniamtions styles
        wp_enqueue_style('uicore-animate-bg', UICORE_ANIMATE_ASSETS . '/css/animated-background-gutenberg.css', array(), UICORE_ANIMATE_VERSION);

        // Pass animation lists to JS
        $list = Helper::get_animations_list();
        $animations = [];
        foreach ($list as $value => $label) {
            $animations[] = [
                'label' => $label,
                'value' => $value
            ];
        }
        $animated_background_list = Helper::get_background_animations_list(true);
        $js_animated_background_list = [];
        foreach ($animated_background_list as $group_key => $group) {
            $group_label = ucwords(str_replace(['-', '_'], [' ', ' '], $group_key));
            $options = [];
            foreach ($group as $value => $label) {
                $options[] = [
                    'label' => $label,
                    'value' => $value
                ];
            }
            $js_animated_background_list[] = [
                'label' => $group_label,
                'options' => $options
            ];
        }
        \wp_add_inline_script('uicore_animate-editor', 'var uicore_animations_list = ' . wp_json_encode($animations) . '; var uicore_animated_background_list = ' . wp_json_encode($js_animated_background_list) . ';');
    }

    /**
     * Enqueue frontend scripts for animated background block based on selected animation
     */
    public function enqueue_animated_background_frontend_scripts()
    {
        // Find all blocks in post content
        global $post;
        if (empty($post)) return;
        if (strpos($post->post_content, 'ui-animated-background') === false) return;

        // Use regex to extract all animation values from data-ui-bg-animation attributes
        preg_match_all('/data-ui-bg-animation=["\"](.*?)["\"]/i', $post->post_content, $matches);
        $animations = isset($matches[1]) ? array_unique($matches[1]) : [];
        foreach ($animations as $animation) {
            if ($animation) {
                $animation = $animation === 'ui-fluid-animation-6' ? 'fluid-gradient' : $animation;
                $is_css_animation = $animation && strpos($animation, 'ui-fluid-animation-') === 0;
                if ($is_css_animation) {
                    wp_enqueue_style('uicore-animate-bg', UICORE_ANIMATE_ASSETS . '/css/animated-background-gutenberg.css', array(), UICORE_ANIMATE_VERSION);
                } else {
                    wp_enqueue_script('uicore-animate-' . $animation, UICORE_ANIMATE_ASSETS . '/js/backgrounds/' . $animation . '.js', array(), UICORE_ANIMATE_VERSION, true);
                }
            }
        }
        // Always enqueue OGL dependency
        $ogl_name = $animation === 'bit-wave' ? 'ogl-lib' : 'uicore-ogl';
        wp_enqueue_script($ogl_name, UICORE_ANIMATE_ASSETS . '/js/deps/' . $ogl_name . '.js', array(), UICORE_ANIMATE_VERSION, true);

        //frontend script
        wp_enqueue_script('uicore-animated-background-frontend', UICORE_ANIMATE_ASSETS . '/build/animated-background/frontend.js', array(), UICORE_ANIMATE_VERSION, true);
    }
}
new Gutenberg();
