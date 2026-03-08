<?php

namespace UiCoreAnimate;

use Elementor\Controls_Manager;

defined('ABSPATH') || exit();

class Floating
{

    public function __construct()
    {
        add_action('elementor/element/before_section_end', [$this, 'register_controls_for_animations'], 10, 3);
    }

    function register_controls_for_animations($widget, $widget_id, $args)
    {
        static $widgets = [
            'section_effects', /* Section */
        ];

        if (!in_array($widget_id, $widgets)) {
            return;
        }
        //remove 'animation' control
        // check if controll with anme 'animation' exists
        if ($widget->get_controls('animation')) {
            $widget->remove_control('animation');
            $name = 'animation';
        } else {
            $widget->remove_responsive_control('_animation');
            $name = '_animation';
        }


        // add select for Trigger type
        $widget->add_control(
            'uicore_trigger_type',
            [
                'label' => UICORE_ANIMATE_BADGE . __('Trigger Type', 'uicore-animate'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => __('Entrance', 'uicore-animate'),
                    'uicore-animate-scroll' => __('Scroll', 'uicore-animate'),
                ],
                'default' => '',
                'prefix_class' => '',
                'frontend_available' => true,
            ]
        );
        //add the animation controller
        if ($name == 'animation') {
            $widget->add_control(
                'animation',
                [
                    'label' => esc_html__('Entrance Animation', 'elementor'),
                    'type' => Controls_Manager::ANIMATION,
                    'frontend_available' => true,
                    'condition' => [
                        'uicore_trigger_type' => '',
                    ],
                ]
            );
        } else {
            $widget->add_responsive_control(
                '_animation',
                [
                    'label' => esc_html__('Entrance Animation', 'elementor'),
                    'type' => Controls_Manager::ANIMATION,
                    'frontend_available' => true,
                    'condition' => [
                        'uicore_trigger_type' => '',
                    ],
                ]
            );
        }
        //add the scroll animation controller
        $widget->add_control(
            'uicore_scroll_animation',
            [
                'label' => UICORE_ANIMATE_BADGE . esc_html__('Scroll Animation', 'elementor'),
                'type' => Controls_Manager::ANIMATION,
                'prefix_class' => '',
                'condition' => [
                    'uicore_trigger_type' => 'uicore-animate-scroll',
                ],
            ]
        );
        //add offset start controll for scroll
        $widget->add_control(
            'uicore_scroll_offset_start',
            [
                'label' => UICORE_ANIMATE_BADGE . esc_html__('Start Offset (vh)', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'vh' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'vh',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => 'animation-range:entry {{uicore_scroll_offset_end.SIZE}}vh entry {{SIZE}}vh',
                ],
                'condition' => [
                    'uicore_trigger_type' => 'uicore-animate-scroll',
                ],
            ]
        );
        //add offset end controll for scroll
        $widget->add_control(
            'uicore_scroll_offset_end',
            [
                'label' => UICORE_ANIMATE_BADGE . esc_html__('End Offset (vh)', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'vh' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'vh',
                    'size' => 50,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => 'animation-range:entry {{uicore_scroll_offset_start.SIZE}}vh entry {{SIZE}}vh',
                ],
                'condition' => [
                    'uicore_trigger_type' => 'uicore-animate-scroll',
                ],
            ]
        );

        //add  float at the end
        $widget->add_control(
            'uicore_enable_float',
            [
                'label'        => UICORE_ANIMATE_BADGE . esc_html__('Floating effect', 'uicore-animate'),
                'description'  => esc_html__('Add a looping up-down animation.', 'uicore-animate'),
                'type'         => Controls_Manager::SWITCHER,
                'separator'    => 'before',
                'default' => '',
                'prefix_class' => 'ui-float-',
                'return_value' => 'widget',
                'frontend_available' => false,
            ]
        );
        $widget->add_control(
            'uicore_float_size',
            [
                'label' => __('Floating height', 'uicore-animate'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    'ui-float-s' => __('Small', 'uicore-animate'),
                    '' => __('Default', 'uicore-animate'),
                    'ui-float-l' => __('Large', 'uicore-animate'),
                ],
                'condition' => array(
                    'uicore_enable_float' => 'widget',
                ),
                'prefix_class' => ' ',
            ]
        );

        // Update Controls
        $widget->update_control(
            'animation_duration',
            [
                'condition' => [
                    'uicore_trigger_type' => '',
                ],
            ]
        );
        $widget->update_control(
            '_animation_delay',
            [
                'condition' => [
                    'uicore_trigger_type' => '',
                ],
            ]
        );
    }
}
