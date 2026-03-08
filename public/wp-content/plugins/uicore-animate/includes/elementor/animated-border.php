<?php

namespace UiCoreAnimate;

use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

defined('ABSPATH') || exit();

class AnimatedBorder
{

    public function __construct()
    {
        add_action('elementor/element/before_section_end', [$this, 'animated_border'], 10, 2);
        add_action('elementor/element/container/section_border/before_section_end', [$this, 'animated_border_in_elements'], 10, 2);
        add_action('elementor/element/uicore-advanced-post-grid/section_style_item/before_section_end', [$this, 'animated_border_in_elements'], 10, 2);
        add_action('elementor/element/uicore-icon-list/section_list_items/before_section_end', [$this, 'animated_border_in_elements'], 10, 2);
    }

    public function animated_border($element, $section_id)
    {
        // Check if the section is 'section_border'
        if ('_section_border' !== $section_id) {
            return;
        }
        $this->add_border_controlls($element);
    }

    public function animated_border_in_elements(Controls_Stack $element, $section_id)
    {
        $suffix = $element->get_type() == 'container' ? '' : 'item';
        $this->add_border_controlls($element, $suffix);
    }


    function add_border_controlls(Controls_Stack $element, $suffix = '')
    {
        $suffix = $suffix ? '_' . $suffix : '';
        $is_container = $element->get_type() == 'container';
        $options = [
            '' => __('None', 'uicore-animate'),
            'ui-borderanim-hover' . $suffix => __('Hover Glow', 'uicore-animate'),
            'ui-borderanim-rotate' . $suffix => __('Rotate', 'uicore-animate'),
            'ui-borderanim-rotate' . $suffix . ' ui-gradient' => __('Gradient Rotate', 'uicore-animate'),
            'ui-borderanim-rotate' . $suffix . ' ui-gradient-dual' => __('Gradient Rotate (2)', 'uicore-animate'),
            'ui-borderanim-rotate' . $suffix . ' ui-multicolor' => __('Multicolor Rotate (4)', 'uicore-animate'),
            'ui-borderanim-rotate' . $suffix . ' ui-multicolor-8' => __('Multicolor Rotate (8)', 'uicore-animate'),
            'ui-borderanim-rotate' . $suffix . ' ui-multicolor-12' => __('Multicolor Rotate (12)', 'uicore-animate'),
        ];


        // $condition = [
        //     'relation' => 'or',
        //     'terms' => [
        //         [
        //             'name' => '_border_border',
        //             'operator' => '!in',
        //             'value' => ['none', '']
        //         ]
        //     ]
        // ];
        $condition = [
            '_border_border!' => ''
        ];
        if ($is_container) {
            $condition = [
                "terms" => [
                    [
                        'name' => 'border_border',
                        'operator' => '!in',
                        'value' => ['none', '']
                    ]
                ]
            ];
            $condition = [
                'border_border!' => ''
            ];
        } elseif ($element->get_name() == 'uicore-advanced-post-grid' || $element->get_name() == 'uicore-advanced-post-carousel') {
            $condition = [
                "terms" => [
                    [
                        'name' => 'item_border_border',
                        'operator' => '!in',
                        'value' => ['none', '']
                    ]
                ]
            ];
            $condition = [
                'item_border_border!' => ''
            ];
        } elseif ($element->get_name() == 'uicore-icon-list') {
            $condition = [
                "terms" => [
                    [
                        'name' => 'list_item_border_border',
                        'operator' => '!in',
                        'value' => ['none', '']
                    ]
                ]
            ];
            $condition = [
                'list_item_border_border!' => ''
            ];
        }


        $element->add_control(
            'uicore_animated_border' . $suffix,
            [
                'label' => __('Animated Border', 'uicore-animate'),
                'type' => Controls_Manager::SELECT,
                'options' => $options,
                'prefix_class' => '',
                'default' => '',
                'condition' =>  $condition,
            ]
        );

        $requires_bg_widgets = [
            'uicore-advanced-post-grid',
            'uicore-advanced-post-carousel',
            'uicore-icon-list'
        ];
        if (in_array($element->get_name(), $requires_bg_widgets)) {
            $element->add_control(
                'uicore_animated_border_warning' . $suffix,
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('*requires current widget to have a background.', 'uicore-animate'),
                    'content_classes' => 'elementor-control-field-description',
                    'condition' => [
                        'uicore_animated_border' . $suffix => ['ui-borderanim-hover' . $suffix],
                    ],
                ]
            );
        }

        $element->add_control(
            'uicore_animated_border_color' . $suffix,
            [
                'label' => __('Animated Border Color', 'uicore-animate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f546c4',
                'selectors' => [
                    '{{WRAPPER}}' => '--ui-borderanim-color:{{VALUE}}',
                ],
                'condition' => [
                    'uicore_animated_border' . $suffix . '!' => '',
                ],
            ]
        );
        $element->add_control(
            'uicore_animated_border_color2' . $suffix,
            [
                'label' => __('Animated Border Color 2', 'uicore-animate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4668f5',
                'selectors' => [
                    '{{WRAPPER}}' => '--ui-borderanim-color2:{{VALUE}}',
                ],
                'condition' => [
                    'uicore_animated_border' . $suffix => ['ui-borderanim-rotate' . $suffix . ' ui-multicolor-8', 'ui-borderanim-rotate' . $suffix . ' ui-multicolor-12', 'ui-borderanim-rotate' . $suffix . ' ui-multicolor'],
                ],
            ]
        );
        $element->add_control(
            'uicore_animated_border_color3' . $suffix,
            [
                'label' => __('Animated Border Color 3', 'uicore-animate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#a2f546',
                'selectors' => [
                    '{{WRAPPER}}' => '--ui-borderanim-color3:{{VALUE}}',
                ],
                'condition' => [
                    'uicore_animated_border' . $suffix => ['ui-borderanim-rotate' . $suffix . ' ui-multicolor-8', 'ui-borderanim-rotate' . $suffix . ' ui-multicolor-12', 'ui-borderanim-rotate' . $suffix . ' ui-multicolor'],
                ],
            ]
        );
        $element->add_control(
            'uicore_animated_border_color4' . $suffix,
            [
                'label' => __('Animated Border Color 4', 'uicore-animate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f56d46',
                'selectors' => [
                    '{{WRAPPER}}' => '--ui-borderanim-color4:{{VALUE}}',
                ],
                'condition' => [
                    'uicore_animated_border' . $suffix => ['ui-borderanim-rotate' . $suffix . ' ui-multicolor-8', 'ui-borderanim-rotate' . $suffix . ' ui-multicolor-12', 'ui-borderanim-rotate' . $suffix . ' ui-multicolor'],
                ],
            ]
        );
        //speed control slider
        $element->add_control(
            'uicore_animated_border_speed' . $suffix,
            [
                'label' => __('Speed (seconds)', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' =>  5,
                'selectors' => [
                    '{{WRAPPER}}' => '--ui-borderanim-speed: {{SIZE}}s',
                ],
                'condition' => [
                    'uicore_animated_border' . $suffix . '!' => ['', 'ui-borderanim-hover' . $suffix . ''],
                ],
            ]
        );
    }
}
