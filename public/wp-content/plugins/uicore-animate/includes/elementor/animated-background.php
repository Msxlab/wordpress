<?php

namespace UiCoreAnimate;

use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

defined('ABSPATH') || exit();

class AnimatedBackground
{

    public function __construct()
    {
        add_action('elementor/element/section/section_advanced/before_section_start', [$this, 'animated_bg_controls']);
        add_action('elementor/element/container/section_background/before_section_end', [$this, 'animated_bg_controls']);
    }

    /**
     * Animated Background extender
     *
     * @param \Elementor\Controls_Stack $element
     * @param string $section_id
     * @return void
     * @author Andrei Voica <andrei@uicore.co>
     * @since 3.2.1
     */
    function animated_bg_controls(Controls_Stack $section)
    {
        $section->start_injection(
            [
                'type' => 'control',
                'at'   => 'after',
                'of'   => 'background_color',
            ]
        );

        $section->add_control(
            'section_fluid_on',
            [
                'label'        => UICORE_ANIMATE_BADGE . esc_html__('Animated BG', 'uicore-animate'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'description'  => esc_html__('Enable Animated Background.', 'uicore-animate'),
                'render_type'  => 'template',
                'frontend_available' => true,
            ]
        );

        $section->add_control(
            'uicore_fluid_animation',
            [
                'label' => __('Animation', 'uicore-animate'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'groups' => $this->get_animations_list(),
                'render_type'  => 'template',
                'prefix_class' => ' ',
                'frontend_available' => true,
                'condition' => [
                    'section_fluid_on' => 'yes'
                ],
            ]
        );

        $section->add_control(
            'section_fluid_settings',
            [
                'label' => esc_html__('Animation Settings', 'uicore-animate'),
                'type' => Controls_Manager::POPOVER_TOGGLE,
                'label_off' => esc_html__('Default', 'uicore-animate'),
                'label_on' => esc_html__('Custom', 'uicore-animate'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('popover'),
                ],
                'frontend_available' => true,
            ]
        );

        $section->start_popover();

        $section->add_control(
            'section_fluid_scale',
            [
                'label' => esc_html__('Scale', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'default' => 10,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('scale'),
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );
        $section->add_control(
            'section_fluid_intensity',
            [
                'label' => esc_html__('Intensity', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'default' => 50,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('intensity'),
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );
        $section->add_control(
            'section_fluid_static',
            [
                'label' => esc_html__('Disable Motion', 'uicore-animate'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'uicore-animate'),
                'label_off' => esc_html__('No', 'uicore-animate'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('speed'),
                ],
                'render_type' => 'template',
                'frontend_available' => true,
            ]
        );
        $section->add_control(
            'section_fluid_speed',
            [
                'label' => esc_html__('Speed', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' => 20,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('speed'),
                    'section_fluid_static!' => 'yes',
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );
        $section->add_control(
            'section_fluid_progress',
            [
                'label' => esc_html__('Stop Frame', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1000,
                'step' => 1,
                'default' => 10,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'section_fluid_static' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('speed'),
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );
        $section->add_control(
            'section_fluid_noise',
            [
                'label' => esc_html__('Noise', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'default' => 20,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('noise'),
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );
        $section->add_control(
            'section_fluid_angle',
            [
                'label' => esc_html__('Angle', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 360,
                'step' => 1,
                'default' => 0,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('angle'),
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );
        $section->add_control(
            'section_fluid_offset_x',
            [
                'label' => esc_html__('Offset X', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => -400,
                'max' => 400,
                'step' => 1,
                'default' => 0,
                'conditions' => [
                    'relation' => 'or',
                    'terms' => [
                        [
                            'relation' => 'and',
                            'terms' => [
                                [
                                    'name' => 'section_fluid_on',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'uicore_fluid_animation',
                                    'operator' => '===',
                                    'value' => 'flux-stripes'
                                ],
                                [
                                    'name' => 'section_fluid_interactive',
                                    'operator' => '!==',
                                    'value' => 'yes'
                                ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms' => [
                                [
                                    'name' => 'section_fluid_on',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'uicore_fluid_animation',
                                    'operator' => 'in',
                                    'value' => array_values(array_diff($this->get_animations_list('offset'), ['flux-stripes'])),
                                ],
                            ],
                        ],
                    ],
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );
        $section->add_control(
            'section_fluid_offset_y',
            [
                'label' => esc_html__('Offset Y', 'uicore-animate'),
                'type' => Controls_Manager::NUMBER,
                'min' => -400,
                'max' => 400,
                'step' => 1,
                'default' => 0,
                'conditions' => [
                    'relation' => 'or',
                    'terms' => [
                        [
                            'relation' => 'and',
                            'terms' => [
                                [
                                    'name' => 'section_fluid_on',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'uicore_fluid_animation',
                                    'operator' => '===',
                                    'value' => 'flux-stripes'
                                ],
                                [
                                    'name' => 'section_fluid_interactive',
                                    'operator' => '!==',
                                    'value' => 'yes'
                                ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms' => [
                                [
                                    'name' => 'section_fluid_on',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'uicore_fluid_animation',
                                    'operator' => 'in',
                                    'value' => array_values(array_diff($this->get_animations_list('offset'), ['flux-stripes'])),
                                ],
                            ],
                        ],
                    ],
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );

        $section->add_control(
            'section_fluid_texture',
            [
                'label' => esc_html__('Choose Media', 'textdomain'),
                'type' => Controls_Manager::MEDIA,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('texture'),
                ],
                'frontend_available' => true,
            ]
        );

        $section->add_control(
            'section_fluid_interactive',
            [
                'label' => esc_html__('Mouse Interactive', 'uicore-animate'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'uicore-animate'),
                'label_off' => esc_html__('No', 'uicore-animate'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('interactive'),
                ],
                'render_type' => 'none',
                'frontend_available' => true,
            ]
        );

        $section->end_popover();

        $section->add_control(
            'section_fluid_color_bg',
            [
                'label'     => esc_html__('Base Color', 'uicore-animate'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('color_bg'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .ui-e-fluid-canvas' => '--ui-fluid-bg: {{VALUE}}',
                ],
            ]
        );
        $section->add_control(
            'section_fluid_color_1',
            [
                'label'     => esc_html__('Color 1', 'uicore-animate'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('color_1'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .ui-e-fluid-canvas' => '--ui-fluid-1: {{VALUE}}',
                ],
            ]
        );
        $section->add_control(
            'section_fluid_color_2',
            [
                'label'     => esc_html__('Color 2', 'uicore-animate'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('color_2'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .ui-e-fluid-canvas' => '--ui-fluid-2: {{VALUE}}',
                ],
            ]
        );
        $section->add_control(
            'section_fluid_color_3',
            [
                'label'     => esc_html__('Color 3', 'uicore-animate'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('color_3'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .ui-e-fluid-canvas' => '--ui-fluid-3: {{VALUE}}',
                ],
            ]
        );
        $section->add_control(
            'section_fluid_color_4',
            [
                'label'     => esc_html__('Color 4', 'uicore-animate'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'section_fluid_on' => 'yes',
                    'uicore_fluid_animation' => $this->get_animations_list('color_4'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .ui-e-fluid-canvas' => '--ui-fluid-4: {{VALUE}}',
                ],
            ]
        );

        $section->add_control(
            'ui_fluid_opacity',
            [
                'label' => __('Opacity', 'uicore-animate'),
                'type' => Controls_Manager::SLIDER,
                'condition' => [
                    'section_fluid_on' => 'yes',
                ],
                'range' => [
                    'px' => [
                        'min'  => 0.05,
                        'max'  => 1,
                        'step' => 0.05,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ui-e-fluid-canvas' => 'opacity: {{SIZE}}',
                ],
            ]
        );

        $section->add_control('hr', ['type' => Controls_Manager::DIVIDER]);

        $section->end_injection();
    }

    /**
     * Return the animated backgrounds list. New animations should be added in this array.
     *
     * @param string $control - Filter animations based on control compatibility. (optional)
     *
     * @return array - The full animations grouped array list, or a simple array of animation keys for a specific control conditions, if requested.
     */
    public function get_animations_list(string $control = '')
    {
        // Complete Output
        if (empty($control)) {

            $animations = Helper::get_background_animations_list(true);
            $options = [
                [
                    'label' => esc_html__('Default', 'uicore-animate'),
                    'options' => [
                        '' => __('None', 'uicore-animate'),
                    ],
                ]
            ];

            foreach ($animations as $key => $values) {
                $options[] = [
                    'label' => ucwords(str_replace('-', ' ', esc_html($key))),
                    'options' => $values,
                ];
            }

            return $options;
        }

        // Conditional Output
        $animations = Helper::get_background_animations_list();
        $css_animations = Helper::get_background_animations_list(true)['css-animations'];
        $js_animations = array_diff_key($animations, $css_animations);

        switch ($control) {
            case 'popover':
                return $this->parse_conditions($js_animations);
                break;

            case 'color_bg':
                return $this->parse_conditions(
                    $animations,
                    ['flux-stripes', 'ui-fluid-animation-6', 'bit-wave', 'gradient-mesh', 'liquid-mask', 'liquid-image', 'mystic-lake', 'neon-eclipse', 'the-shining', 'plasma-line']
                );
                break;

            case 'color_1':
                return $this->parse_conditions($animations);
                break;

            case 'color_2':
                return $this->parse_conditions(
                    $animations,
                    ['mystic-lake', 'noir-haze', 'void-wave', 'the-shining', 'mist', 'flame', 'liquid-mask', 'liquid-image', 'halftone',]
                );
                break;

            case 'color_4':
            case 'color_3':
                return $this->parse_conditions(
                    $animations,
                    ['mystic-lake', 'pulse-bubble', 'noir-haze', 'void-wave', 'the-shining', 'mist', 'flame', 'halftone', 'bit-wave', 'echo-sphere', 'liquid-mask', 'liquid-image', 'phase-tunnel']
                );
                break;

            case 'intensity':
            case 'scale':
                return $this->parse_conditions($js_animations);
                break;

            case 'offset':
                return $this->parse_conditions($js_animations, ['ui-fluid-animation-6', 'borealis', 'bit-wave', 'void-wave', 'noir-haze', 'mystic-lake', 'gradient-mesh', 'liquid-mask', 'liquid-image']);
                break;

            case 'speed':
                return $this->parse_conditions($js_animations, ['flux-stripes', 'liquid-mask', 'liquid-image']);
                break;

            case 'noise':
                return $this->parse_conditions($js_animations, ['ui-fluid-animation-6', 'liquid-mask', 'perspective-grid', 'haldftone']);
                break;

            case 'angle':
                return ['flux-stripes', 'light-strings', 'plasma-line', 'the-shining', 'mist'];
                break;

            case 'interactive':
                return $this->parse_conditions($js_animations, ['liquid-image']);
                break;

            case 'static':
                return $this->parse_conditions($js_animations, ['ui-fluid-animation-6', 'flux-stripes']);
                break;

            case 'texture':
                return ['liquid-mask', 'liquid-image'];
                break;

            default:
                return $animations;
                break;
        }
    }

    /**
     * Parse the conditions for a specific control by excluding certain animations
     */
    public function parse_conditions($animations, $exclude = [])
    {
        return array_values(array_diff(array_keys($animations), $exclude));
    }

    /**
     * Get the requested JS animated background assets
     *
     * @param string|null $animation
     *
     * @return array - The array of assets to be enqueued.
     */
    public static function get_animated_background_assets($animation = null)
    {
        $assets = [];
        if (!isset($animation) || empty($animation) || (strpos($animation, 'ui-fluid-animation-') === 0 && $animation !== 'ui-fluid-animation-6')) {
            $assets[] = 'animated-background';
            return $assets;
        }

        // bit-wave requires ogl original lib
        $assets[] = $animation === 'bit-wave' ? 'ogl-lib' : 'uicore-ogl';

        // og fluid (original name but nice name)
        $assets[] = $animation === 'ui-fluid-animation-6' ? 'fluid-gradient' : $animation;

        // Important: keep the main script by the end and deps first in the arrays, since the queueing proccess will respect this order
        $assets[] = 'animated-background';
        return $assets;
    }
}
