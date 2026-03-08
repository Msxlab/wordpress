<?php

namespace UiCoreAnimate;

use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

defined('ABSPATH') || exit();

class SplitText
{

    public function __construct()
    {
        add_action('elementor/element/heading/section_title_style/after_section_end', [$this, 'split_animation'], 55);
        add_action('elementor/element/text-editor/section_drop_cap/after_section_end', [$this, 'split_animation'], 55);
        add_action('elementor/element/highlighted-text/section_style_text/after_section_end', [$this, 'split_animation'], 55);
    }

    /**
     *  The split text animation controls for Elementor widgets
     *
     * @param Controls_Stack $widget
     */

    public static function split_animation(Controls_Stack $widget)
    {

        $widget->start_controls_section(
            'section_ui_split_animation',
            [
                'label' => UICORE_ANIMATE_BADGE . esc_html__('Split Text Animation', 'uicore-animate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $widget->add_control(
            'ui_animate_split',
            [
                'label'              => esc_html__('Animate by Characters', 'uicore-animate'),
                'type'               => Controls_Manager::SWITCHER,
                'default'            => '',
                'return_value'       => 'ui-split-animate',
                'frontend_available' => true,
                'prefix_class'       => ' ',
                // 'render_type'		 => 'none'
            ]
        );
        if (is_plugin_active('gtranslate/gtranslate.php')) {
            $widget->add_control(
                'custom_panel_alert',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'warning',
                    'heading' => esc_html__('GTranslate active', 'uicore-animate'),
                    'content' => esc_html__('Google can\'t properly translate split text. When users switch languages, split animations will be disabled on translated pages until the original language is restored.', 'uicore-animate'),
                    'condition' => array(
                        'ui_animate_split' => 'ui-split-animate',
                    ),
                ]
            );
        }
        $widget->add_control(
            'ui_animate_split_by',
            [
                'label' => __('Split by', 'uicore-animate'),
                'type' => Controls_Manager::SELECT,
                'default' => 'chars',
                'options' => [
                    'chars' => __('Char', 'uicore-animate'),
                    'words' => __('word', 'uicore-animate'),
                    'lines' => __('line', 'uicore-animate'),
                ],
                'frontend_available' => true,
                'condition' => array(
                    'ui_animate_split' => 'ui-split-animate',
                ),
                'prefix_class'       => 'ui-splitby-',
                // 'render_type'		=> 'none'
            ]
        );
        $widget->add_control(
            'ui_animate_split_style',
            [
                'label' => __('Animation', 'uicore-animate'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fadeInUp',
                'options' => Helper::get_split_animations_list(),
                'frontend_available' => true,
                'condition' => array(
                    'ui_animate_split' => 'ui-split-animate',
                ),
                // 'render_type'		=> 'none'
            ]
        );


        $widget->add_control(
            'ui_animate_split_speed',
            [
                'label' => __('Speed', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'condition' => array(
                    'ui_animate_split' => 'ui-split-animate',
                ),
                'default' => [
                    'unit' => 'px',
                    'size' => 1500,
                ],
                'range' => [
                    'px' => [
                        'min'  => 10,
                        'max'  => 3000,
                        'step' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} ' => '---ui-speed: {{SIZE}}ms',
                ],
            ]
        );
        $widget->add_control(
            'ui_animate_split_delay',
            [
                'label' => __('Animation Delay', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'condition' => array(
                    'ui_animate_split' => 'ui-split-animate',
                ),
                'default' => [
                    'unit' => 'px',
                    'size' => 200,
                ],
                'range' => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1500,
                        'step' => 10,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} ' => '---ui-delay: {{SIZE}}ms',
                ],
            ]
        );
        $widget->add_control(
            'ui_animate_split_stager',
            [
                'label' => __('Stagger', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'condition' => array(
                    'ui_animate_split' => 'ui-split-animate',
                ),
                'default' => [
                    'unit' => 'px',
                    'size' => 15,
                ],
                'range' => [
                    'px' => [
                        'min'  => 2,
                        'max'  => 300,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} ' => '---ui-stagger: {{SIZE}}ms',
                ],
            ]
        );

        $widget->end_controls_section();
    }
}
