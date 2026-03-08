<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Css_Filter;
use Elementor\Repeater;

trait Shape_Builder_Controls_Trait
{
    public function get_custom_upload_icon()
    {
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/elementor/shapes/';
        $svg_url = $upload_dir['baseurl'] . '/elementor/shapes/';

        // Create directory if it doesn't exist
        if (!file_exists($svg_dir)) {
            wp_mkdir_p($svg_dir);
        }

        $filename = 'custom-upload-icon.svg';
        $filepath = $svg_dir . $filename;
        $fileurl = $svg_url . $filename;

        // Create upload icon SVG if it doesn't exist
        if (!file_exists($filepath)) {
            $svg_content = '<?xml version="1.0" encoding="UTF-8"?>';
            $svg_content .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60" width="60" height="60" fill="#666">';
            $svg_content .= '<rect x="10" y="15" width="40" height="30" rx="3" fill="none" stroke="#666" stroke-width="2" stroke-dasharray="3,3"/>';
            $svg_content .= '<path d="M30 25v10m-5-5h10" stroke="#666" stroke-width="2" stroke-linecap="round"/>';
            $svg_content .= '<text x="30" y="52" text-anchor="middle" font-size="8" fill="#666">Upload</text>';
            $svg_content .= '</svg>';

            file_put_contents($filepath, $svg_content);
        }

        return $fileurl;
    }

    public function create_shape_svg_file($shape)
    {
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/elementor/shapes/';
        $svg_url = $upload_dir['baseurl'] . '/elementor/shapes/';

        // Create directory if it doesn't exist
        if (!file_exists($svg_dir)) {
            wp_mkdir_p($svg_dir);
        }

        $filename = $shape['id'] . '.svg';
        $filepath = $svg_dir . $filename;
        $fileurl = $svg_url . $filename;

        // Generate SVG content
        $viewbox = $shape['viewBox'] ?? '0 0 100 100';
        $path = $shape['path'] ?? '';
        $transform = !empty($shape['transform']) ? 'transform="' . $shape['transform'] . '"' : '';

        $svg_content = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg_content .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $viewbox . '" width="60" height="60" fill="#666">';
        if (!empty($transform)) {
            $svg_content .= '<g ' . $transform . '><path d="' . $path . '"/></g>';
        } else {
            $svg_content .= '<path d="' . $path . '"/>';
        }
        $svg_content .= '</svg>';

        // Write file if it doesn't exist or needs updating
        if (!file_exists($filepath) || md5_file($filepath) !== md5($svg_content)) {
            file_put_contents($filepath, $svg_content);
        }

        return $fileurl;
    }


    public function get_shape_options()
    {
        $shapes = $this->get_shapes();
        $options = [];



        foreach ($shapes as $key => $shape) {
            $options[$shape['id']] = [
                'title' => $shape['name'],
                'image' => $this->create_shape_svg_file($shape),
            ];
        }

        // Add custom upload option first
        $options['custom'] = [
            'title' => __('Custom Upload', 'bdthemes-element-pack'),
            'image' => $this->get_custom_upload_icon(),
        ];

        return $options;
    }

    public function register_shape_builder_controls($element, $section_id)
    {
        $pro_lock = bdt_get_widget_badge( str_replace( 'bdt-', '', $this->get_name() ) );
        
        $element->start_controls_section(
            'bdt_shape_builder_section',
            [
                'label' => BDTEP_CP . __('Shape Builder', 'bdthemes-element-pack') . $pro_lock . BDTEP_NC,
                'tab'   => Controls_Manager::TAB_ADVANCED,
            ]
        );

        $element->add_control(
            'bdt_shape_builder_enable',
            [
                'label' => __('Enable Shape Builder', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'bdthemes-element-pack'),
                'label_off' => __('No', 'bdthemes-element-pack'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $repeater = new Repeater();

        $shape_options = $this->get_shape_options();
        
        $repeater->add_control(
            'shape_type',
            [
                'label'   => __('Shape Type', 'bdthemes-element-pack'),
                'label_block'    => true,
                'show_label'     => true,
                'type'           => Controls_Manager::VISUAL_CHOICE,
                'columns'        => 4,
                'options' => $shape_options,
                'default' => 'circle',
            ]
        );

        $repeater->add_control(
            'custom_shape_upload',
            [
                'label'       => __('Custom Shape Upload', 'bdthemes-element-pack'),
                'type'        => \Elementor\Controls_Manager::MEDIA,
                'media_types' => ['svg'],
                'default'     => [
                    'url' => '',
                    'id'  => '',
                ],
                'condition'   => ['shape_type' => 'custom'],
            ]
        );

         $repeater->add_control(
            'custom_shape_color_popover',
            [
                'type' => \Elementor\Controls_Manager::POPOVER_TOGGLE,
                'label' => esc_html__('Color', 'bdthemes-element-pack'),
                'label_off' => esc_html__('Default', 'bdthemes-element-pack'),
                'label_on' => esc_html__('Custom', 'bdthemes-element-pack'),
                'return_value' => 'yes',
                'condition' => [
                    'shape_type' => 'custom',
                ]
            ]
        );

        $repeater->start_popover(); // If you want them in a popover, optional

        $repeater->add_control(
            'custom_shape_fill_color',
            [
                'label' => __('Fill Color', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'condition' => [
                    'custom_shape_color_popover' => 'yes',
                    'shape_type' => 'custom'
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder.bdt-shape-builder-custom{{CURRENT_ITEM}} svg *' => 'fill: {{VALUE}};color: {{VALUE}};',
                ]
            ]
        );

        $repeater->add_control(
            'custom_shape_stroke_color',
            [
                'label' => __('Stroke Color', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'condition' => [
                    'custom_shape_color_popover' => 'yes',
                    'shape_type' => 'custom'
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder.bdt-shape-builder-custom{{CURRENT_ITEM}} svg *' => 'stroke: {{VALUE}};',
                ]
            ]
        );

        $repeater->end_popover();


        $repeater->add_control(
            'shape_color_popover',
            [
                'type' => \Elementor\Controls_Manager::POPOVER_TOGGLE,
                'label' => esc_html__('Color', 'bdthemes-element-pack'),
                'label_off' => esc_html__('Default', 'bdthemes-element-pack'),
                'label_on' => esc_html__('Custom', 'bdthemes-element-pack'),
                'return_value' => 'yes',
                'condition' => [
                    'shape_type!' => 'custom',
                ]
            ]
        );

        $repeater->start_popover(); // If you want them in a popover, optional

        // Fill Type (solid / gradient)
        $repeater->add_control(
            'shape_fill_type',
            [
                'label' => __('Fill Type', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'solid' => [
                        'title' => __('Solid', 'bdthemes-element-pack'),
                        'icon' => 'eicon-paint-brush',
                    ],
                    'gradient' => [
                        'title' => __('Gradient', 'bdthemes-element-pack'),
                        'icon' => 'eicon-barcode',
                    ],
                ],
                'default' => 'solid',
                'toggle' => false,
                'condition' => [
                    'shape_color_popover' => 'yes',
                    'shape_type!' => 'custom'
                ],
            ]
        );

        // Solid color
        $repeater->add_control(
            'shape_color',
            [
                'label' => __('Color', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'condition' => [
                    'shape_fill_type' => 'solid',
                    'shape_color_popover' => 'yes',
                    'shape_type!' => 'custom'
                ],
            ]
        );

        // Gradient controls
        $repeater->add_control(
            'shape_gradient_color_1',
            [
                'label' => __('Color', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#08AEEC',
                'condition' => [
                    'shape_fill_type' => 'gradient',
                    'shape_color_popover' => 'yes',
                    'shape_type!' => 'custom'
                ],
            ]
        );

        $repeater->add_control(
            'shape_gradient_location_1',
            [
                'label' => __('Location', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => ['%' => ['min' => 0, 'max' => 100, 'step' => 1]],
                'default' => ['unit' => '%', 'size' => 0],
                'condition' => [
                    'shape_fill_type' => 'gradient',
                    'shape_color_popover' => 'yes',
                    'shape_type!' => 'custom'
                ],
            ]
        );

        $repeater->add_control(
            'shape_gradient_color_2',
            [
                'label' => __('Second Color', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#20E2AD',
                'condition' => [
                    'shape_fill_type' => 'gradient',
                    'shape_color_popover' => 'yes',
                    'shape_type!' => 'custom'
                ],
            ]
        );

        $repeater->add_control(
            'shape_gradient_location_2',
            [
                'label' => __('Location', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => ['%' => ['min' => 0, 'max' => 100, 'step' => 1]],
                'default' => ['unit' => '%', 'size' => 100],
                'condition' => [
                    'shape_fill_type' => 'gradient',
                    'shape_color_popover' => 'yes',
                    'shape_type!' => 'custom'
                ],
            ]
        );

        $repeater->add_control(
            'shape_gradient_type',
            [
                'label' => __('Gradient Type', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'linear' => __('Linear', 'bdthemes-element-pack'),
                    'radial' => __('Radial', 'bdthemes-element-pack'),
                ],
                'default' => 'linear',
                'condition' => [
                    'shape_fill_type' => 'gradient',
                    'shape_color_popover' => 'yes',
                    'shape_type!' => 'custom'
                ],
            ]
        );

        $repeater->add_control(
            'shape_gradient_angle',
            [
                'label' => __('Angle', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => ['deg' => ['min' => 0, 'max' => 360, 'step' => 1]],
                'default' => ['unit' => 'deg', 'size' => 90],
                'condition' => [
                    'shape_fill_type' => 'gradient',
                    'shape_gradient_type' => 'linear',
                    'shape_color_popover' => 'yes',
                    'shape_type!' => 'custom'
                ],
            ]
        );

        $repeater->end_popover();

        $repeater->add_responsive_control(
            'shape_z_index',
            [
                'label' => __('Z-Index', 'bdthemes-element-pack'),
                'type'  => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => -10,
                        'max' => 10,
                    ]
                ],
                'default' => [
                    'size' => 1
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => 'z-index: {{SIZE}};',
                ]
            ]
        );

        $repeater->add_responsive_control(
            'shape_gap',
            [
                'label' => esc_html__( 'Size ( Width X Height )', 'bdthemes-element-pack' ),
                'type' => Controls_Manager::GAPS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
                'default' => [
                    'unit' => 'px',
                    'row'  => 200,
                    'column' => 200,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}} svg' => 'width: {{COLUMN}}{{UNIT}}; height: {{ROW}}{{UNIT}};',
                ],
            ]
        );

        $repeater->add_control(
            'shape_position_popover',
            [
                'label' => __('Position', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::POPOVER_TOGGLE,
                'label_off' => __('Default', 'bdthemes-element-pack'),
                'label_on' => __('Custom', 'bdthemes-element-pack'),
                'return_value' => 'yes',
            ]
        );

        $repeater->start_popover();

        $left = esc_html__( 'Left', 'elementor' );
		$right = esc_html__( 'Right', 'elementor' );

		$start = is_rtl() ? $right : $left;
		$end = ! is_rtl() ? $right : $left;

        $repeater->add_control(
			'shape_position_horizontal',
			[
				'label' => esc_html__( 'Horizontal Orientation', 'bdthemes-element-pack' ),
				'type' => Controls_Manager::CHOOSE,
				'toggle' => false,
				'default' => 'start',
				'options' => [
					'start' => [
						'title' => $start,
						'icon' => 'eicon-h-align-left',
					],
					'end' => [
						'title' => $end,
						'icon' => 'eicon-h-align-right',
					],
				],
				'render_type' => 'ui',
                'condition' => [
                    'shape_position_popover' => 'yes',
                ]
			]
		);

		$repeater->add_responsive_control(
			'shape_position_horizontal_offset_start',
			[
				'label' => esc_html__( 'Offset', 'elementor' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => -300,
						'max' => 300,
					],
					'%' => [
						'min' => -100,
						'max' => 100,
					],
					'vw' => [
						'min' => -100,
						'max' => 100,
					],
					'vh' => [
						'min' => -100,
						'max' => 100,
					],
				],
				'default' => [
					'size' => 0,
				],
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => 'left: {{SIZE}}{{UNIT}}',
					'body.rtl {{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => 'right: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'shape_position_horizontal' => 'start',
                    'shape_position_popover' => 'yes',
				],
			]
		);

		$repeater->add_responsive_control(
			'shape_position_horizontal_offset_end',
			[
				'label' => esc_html__( 'Offset', 'elementor' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => -300,
						'max' => 300,
					],
					'%' => [
						'min' => -100,
						'max' => 100,
					],
					'vw' => [
						'min' => -100,
						'max' => 100,
					],
					'vh' => [
						'min' => -100,
						'max' => 100,
					],
				],
				'default' => [
					'size' => 0,
				],
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'vh', 'custom' ],
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => 'right: {{SIZE}}{{UNIT}}; left: auto;',
					'body.rtl {{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => 'left: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'shape_position_horizontal' => 'end',
                    'shape_position_popover' => 'yes',
				],
			]
		);

		$repeater->add_control(
			'shape_position_vertical',
			[
				'label' => esc_html__( 'Vertical Orientation', 'elementor' ),
				'type' => Controls_Manager::CHOOSE,
				'toggle' => false,
				'default' => 'start',
				'options' => [
					'start' => [
						'title' => esc_html__( 'Top', 'elementor' ),
						'icon' => 'eicon-v-align-top',
					],
					'end' => [
						'title' => esc_html__( 'Bottom', 'elementor' ),
						'icon' => 'eicon-v-align-bottom',
					],
				],
				'render_type' => 'ui',
                'condition' => [
                    'shape_position_popover' => 'yes',
                ]
			]
		);

		$repeater->add_responsive_control(
			'shape_position_vertical_offset_start',
			[
				'label' => esc_html__( 'Offset', 'elementor' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => -300,
						'max' => 300,
					],
					'%' => [
						'min' => -100,
						'max' => 100,
					],
					'vw' => [
						'min' => -100,
						'max' => 100,
					],
					'vh' => [
						'min' => -100,
						'max' => 100,
					],
				],
				'size_units' => [ 'px', '%', 'em', 'rem', 'vh', 'vw', 'custom' ],
				'default' => [
					'size' => 0,
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => 'top: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
                    'shape_position_vertical' => 'start',
                    'shape_position_popover' => 'yes',
				],
			]
		);

		$repeater->add_responsive_control(
			'shape_position_vertical_offset_end',
			[
				'label' => esc_html__( 'Offset', 'elementor' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => -300,
						'max' => 300,
					],
					'%' => [
						'min' => -100,
						'max' => 100,
					],
					'vw' => [
						'min' => -100,
						'max' => 100,
					],
					'vh' => [
						'min' => -100,
						'max' => 100,
					],
				],
				'size_units' => [ 'px', '%', 'em', 'rem', 'vh', 'vw', 'custom' ],
				'default' => [
					'size' => 0,
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => 'bottom: {{SIZE}}{{UNIT}}; top: auto;',
				],
				'condition' => [
                    'shape_position_vertical' => 'end',
                    'shape_position_popover' => 'yes',
				],
			]
		);

        $repeater->end_popover();

        $repeater->add_control(
            'shape_builder_animation_popover',
            [
                'label' => __('Animation', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::POPOVER_TOGGLE,
                'label_off' => __('Default', 'bdthemes-element-pack'),
                'label_on' => __('Custom', 'bdthemes-element-pack'),
                'return_value' => 'yes',
            ]
        );

        $repeater->start_popover();

        $repeater->add_control(
            'animation_trigger_type',
            [
                'label' => __('Trigger Type', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SELECT,
                'default' => 'on-load',
                'options' => [
                    'on-load' => __('On Load', 'bdthemes-element-pack'),
                    'on-hover' => __('On Hover', 'bdthemes-element-pack'),
                ],
                'condition' => [
                    'shape_builder_animation_popover' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'animation_viewport',
            [
                'label' => __('Viewport', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 0.1,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.1,
                    ],
                ],
                'description' => __('0 = trigger immediately when any part enters viewport, 1 = trigger only when fully visible', 'bdthemes-element-pack'),
                'condition' => [
                    'shape_builder_animation_popover' => 'yes',
                    'animation_trigger_type' => 'on-load',
                ],
            ]
        );

        $repeater->add_control(
            'animation_name',
            [
                'label' => __('Animation Effect', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fade-in',
                'options' => [
                    'fade-in' => __('Fade In', 'bdthemes-element-pack'),
                    'fade-in-up' => __('Fade In Up', 'bdthemes-element-pack'),
                    'fade-in-down' => __('Fade In Down', 'bdthemes-element-pack'),
                    'fade-in-left' => __('Fade In Left', 'bdthemes-element-pack'),
                    'fade-in-right' => __('Fade In Right', 'bdthemes-element-pack'),
                    'zoom-in' => __('Zoom In', 'bdthemes-element-pack'),
                    'zoom-out' => __('Zoom Out', 'bdthemes-element-pack'),
                    'rotate-in' => __('Rotate In', 'bdthemes-element-pack'),
                    'flip-x' => __('Flip X', 'bdthemes-element-pack'),
                    'flip-y' => __('Flip Y', 'bdthemes-element-pack'),
                    'bounce' => __('Bounce', 'bdthemes-element-pack'),
                    'pulse' => __('Pulse', 'bdthemes-element-pack'),
                    'swing' => __('Swing', 'bdthemes-element-pack'),
                    'shake' => __('Shake', 'bdthemes-element-pack'),
                    'slide-in-left' => __('Slide In Left', 'bdthemes-element-pack'),
                    'slide-in-right' => __('Slide In Right', 'bdthemes-element-pack'),
                    'slide-in-up' => __('Slide In Up', 'bdthemes-element-pack'),
                    'slide-in-down' => __('Slide In Down', 'bdthemes-element-pack'),
                ],
                'condition' => [
                    'shape_builder_animation_popover' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'animation_duration',
            [
                'label' => __('Duration (seconds)', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    's' => [
                        'min' => 0.1,
                        'max' => 5,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 's',
                    'size' => 1,
                ],
                'condition' => [
                    'shape_builder_animation_popover' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'animation_delay',
            [
                'label' => __('Delay (seconds)', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    's' => [
                        'min' => 0,
                        'max' => 5,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 's',
                    'size' => 0,
                ],
                'condition' => [
                    'shape_builder_animation_popover' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'animation_easing',
            [
                'label' => __('Easing', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => __('None', 'bdthemes-element-pack'),
                    'power1.out' => __('Power1 Out', 'bdthemes-element-pack'),
                    'power2.out' => __('Power2 Out', 'bdthemes-element-pack'),
                    'power3.out' => __('Power3 Out', 'bdthemes-element-pack'),
                    'power4.out' => __('Power4 Out', 'bdthemes-element-pack'),
                    'back.out' => __('Back Out', 'bdthemes-element-pack'),
                    'elastic.out' => __('Elastic Out', 'bdthemes-element-pack'),
                    'bounce.out' => __('Bounce Out', 'bdthemes-element-pack'),
                    'circ.out' => __('Circ Out', 'bdthemes-element-pack'),
                    'expo.out' => __('Expo Out', 'bdthemes-element-pack'),
                ],
                'condition' => [
                    'shape_builder_animation_popover' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'animation_repeat',
            [
                'label' => __('Repeat', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SELECT,
                'default' => '0',
                'options' => [
                    '0' => __('No Repeat', 'bdthemes-element-pack'),
                    '1' => __('Once', 'bdthemes-element-pack'),
                    '2' => __('Twice', 'bdthemes-element-pack'),
                    '3' => __('3 Times', 'bdthemes-element-pack'),
                    '-1' => __('Infinite', 'bdthemes-element-pack'),
                ],
                'condition' => [
                    'shape_builder_animation_popover' => 'yes',
                    'animation_trigger_type!' => 'on-hover',
                ],
            ]
        );

        $repeater->add_control(
            'animation_yoyo',
            [
                'label' => __('Yoyo Effect', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'bdthemes-element-pack'),
                'label_off' => __('No', 'bdthemes-element-pack'),
                'return_value' => 'yes',
                'default' => '',
                'description' => __('Animation will play forward and then backward', 'bdthemes-element-pack'),
                'condition' => [
                    'shape_builder_animation_popover' => 'yes',
                    'animation_repeat!' => '0',
                    'animation_trigger_type!' => 'on-hover',
                    'animation_name!' => 'rotate-in',
                ],
            ]
        );
        $repeater->end_popover();

        $repeater->start_controls_tabs('tabs_shape_style');
        $repeater->start_controls_tab(
            'tab_shape_normal',
            [
                'label' => __('Normal', 'bdthemes-element-pack'),
            ]
        );

        $repeater->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name'      => 'css_filters',
				'selector'  => '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}',
			]
		);

        $repeater->add_control(
            'shape_offset_popover',
            [
                'label' => __('Transform', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::POPOVER_TOGGLE,
                'label_off' => __('Default', 'bdthemes-element-pack'),
                'label_on' => __('Custom', 'bdthemes-element-pack'),
                'return_value' => 'yes',
            ]
        );

        $repeater->start_popover();

        $repeater->add_responsive_control(
            'shape_position_x',
            [
                'label' => __('Horizontal', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => ['min' => -300, 'max' => 300, 'step' => 1],
                    '%'  => ['min' => -100, 'max' => 100, 'step' => 1],
                    'vw' => ['min' => -100, 'max' => 100, 'step' => 1],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => '--shape-position-x: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'shape_offset_popover' => 'yes',
                ],
            ]
        );

        $repeater->add_responsive_control(
            'shape_position_y',
            [
                'label' => __('Vertical', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => ['min' => -300, 'max' => 300, 'step' => 1],
                    '%'  => ['min' => -100, 'max' => 100, 'step' => 1],
                    'vw' => ['min' => -100, 'max' => 100, 'step' => 1],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => '--shape-position-y: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'shape_offset_popover' => 'yes',
                ],
            ]
        );
        $repeater->add_responsive_control(
            'shape_rotate',
            [
                'label' => __('Rotate', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'deg' => [
                        'min' => -360,
                        'max' => 360,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'deg',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => '--shape-rotate: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'shape_offset_popover' => 'yes',
                ],
            ]
        );
        $repeater->end_popover();
        $repeater->end_controls_tab();

        $repeater->start_controls_tab(
            'tab_shape_hover',
            [
                'label' => __('Hover', 'bdthemes-element-pack'),
            ]
        );

        $repeater->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name'      => 'css_filters_hover',
				'selector'  => '{{WRAPPER}}:hover .bdt-shape-builder{{CURRENT_ITEM}}',
			]
		);

        $repeater->add_control(
            'shape_offset_hover_popover',
            [
                'label' => __('Transform', 'bdthemes-element-pack'),
                'type' => \Elementor\Controls_Manager::POPOVER_TOGGLE,
                'label_off' => __('Default', 'bdthemes-element-pack'),
                'label_on' => __('Custom', 'bdthemes-element-pack'),
                'return_value' => 'yes',
            ]
        );

        $repeater->start_popover();

        $repeater->add_responsive_control(
            'shape_position_x_hover',
            [
                'label' => __('Horizontal', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => ['min' => -300, 'max' => 300, 'step' => 1],
                    '%'  => ['min' => -100, 'max' => 100, 'step' => 1],
                    'vw' => ['min' => -100, 'max' => 100, 'step' => 1],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => '--shape-position-hover-x: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'shape_offset_hover_popover' => 'yes',
                ],
            ]
        );

        $repeater->add_responsive_control(
            'shape_position_y_hover',
            [
                'label' => __('Vertical', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => ['min' => -300, 'max' => 300, 'step' => 1],
                    '%'  => ['min' => -100, 'max' => 100, 'step' => 1],
                    'vw' => ['min' => -100, 'max' => 100, 'step' => 1],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => '--shape-position-hover-y: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'shape_offset_hover_popover' => 'yes',
                ],
            ]
        );
        $repeater->add_responsive_control(
            'shape_rotate_hover',
            [
                'label' => __('Rotate', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'deg' => [
                        'min' => -360,
                        'max' => 360,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'deg',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-shape-builder{{CURRENT_ITEM}}' => '--shape-rotate-hover: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'shape_offset_hover_popover' => 'yes',
                ],
            ]
        );
        $repeater->end_popover();

        $repeater->end_controls_tab();
        $repeater->end_controls_tabs();

        $element->add_control(
            'bdt_shape_builder_list',
            [
                'label'       => __('Shapes', 'bdthemes-element-pack'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'title_field' => '<# 
                    var shapePreview = "";
                    var shapeName = "";
                    var shapeOptions = ' . json_encode($shape_options) . ';
                    
                    if ( shape_type === "custom" && custom_shape_upload.url ) {
                        shapePreview = \'<img src="\' + custom_shape_upload.url + \'" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 8px; object-fit: contain;" />\';
                        shapeName = "Custom SVG";
                    } else if ( shapeOptions[shape_type] && shapeOptions[shape_type].image ) {
                        shapePreview = \'<img src="\' + shapeOptions[shape_type].image + \'" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 8px; object-fit: contain;" />\';
                        shapeName = shapeOptions[shape_type].title || shape_type.charAt(0).toUpperCase() + shape_type.slice(1).replace(/-/g, " ");
                    } else {
                        shapeName = shape_type.charAt(0).toUpperCase() + shape_type.slice(1).replace(/-/g, " ");
                    }
                    
                    print( shapePreview + shapeName );
                #>',
                'frontend_available' => true,
                'condition' => [
                    'bdt_shape_builder_enable' => 'yes',
                ],
            ]
        );

        $element->end_controls_section();
    }
}
