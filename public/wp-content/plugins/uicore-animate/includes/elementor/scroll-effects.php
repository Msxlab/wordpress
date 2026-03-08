<?php

namespace UiCoreAnimate;

use Elementor\Controls_Stack;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit();

class ScrollEffects
{

    public function __construct()
    {
        add_action('elementor/element/container/section_effects/after_section_end', [$this, 'container_onscroll_effect'], 2, 2);
    }

    function container_onscroll_effect(Controls_Stack $element, $section_id)
    {
        $element->start_controls_section(
            'section_onscroll_effect',
            [
                'label' => UICORE_ANIMATE_BADGE . __('Scroll Effect', 'uicore-animate'),
                'tab' => Controls_Manager::TAB_ADVANCED,
            ]
        );

        $element->add_control(
            'uicore_onscroll_effect',
            [
                'label' => __('Scroll Effect', 'uicore-animate'),
                'description' => __('Sticky effects will make all the child sticky while Reveal will reveal the current section (it needs a parent for background)', 'uicore-animate'),
                'label_block' => true,
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => __('None', 'uicore-animate'),
                    'simple-sticky' => __('Simple Sticky', 'uicore-animate'),
                    'sticky-scale' => __('Sticky Scale', 'uicore-animate'),
                    'sticky-scale-small' => __('Sticky Scale Small', 'uicore-animate'),
                    'sticky-scale-alt' => __('Sticky Scale Alt', 'uicore-animate'),
                    'sticky-scale-blur' => __('Sticky Scale & Blur', 'uicore-animate'),
                    'sticky-scale-blur-small' => __('Sticky Scale & Blur Small', 'uicore-animate'),
                    'sticky-parallax' => __('Sticky Parallax', 'uicore-animate'),
                    'sticky-mask' => __('Sticky Mask', 'uicore-animate'),
                    'sticky-mask-grow' => __('Sticky Mask Grow', 'uicore-animate'),
                    'mask-reveal' => __('Reveal Mask', 'uicore-animate'),

                ],
                'default' => '',
                'frontend_available' => true,
            ]
        );
        $element->add_control(
            'uicore_onscroll_offset',
            [
                'label' => __('TOP Offset', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1500,
                        'step' => 1,
                    ],
                    'vh' => [
                        'min' => 0,
                        'max' => 80,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--ui-e-onscroll-offset: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'uicore_onscroll_effect!' => [''],
                ]
            ]
        );
        $element->add_control(
            'uicore_onscroll_items_offset',
            [
                'label' => __('Items Offset', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1500,
                        'step' => 1,
                    ],
                    'vh' => [
                        'min' => 0,
                        'max' => 80,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--ui-e-onscroll-items-offset: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'uicore_onscroll_effect!' => ['', 'mask-reveal', 'simple-sticky'],
                ]
            ]
        );
        $element->add_control(
            'uicore_onscroll_reveal_height',
            [
                'label' => __('Total Min Height', 'uicore-animate'),
                'description' => __('Adjust this based on the revealed content to minimize extra scroll', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1500,
                        'step' => 1,
                    ],
                    'vh' => [
                        'min' => 0,
                        'max' => 300,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'vh',
                    'size' => 170,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--ui-e-onscroll-reveal-height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'uicore_onscroll_effect' => ['mask-reveal'],
                ]
            ]
        );
        $element->end_controls_section();
    }
}
