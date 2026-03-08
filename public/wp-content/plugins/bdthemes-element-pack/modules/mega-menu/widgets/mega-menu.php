<?php

namespace ElementPack\Modules\MegaMenu\Widgets;

use ElementPack\Base\Module_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use ElementPack\Includes\MegaMenu\Mega_Menu_Walker;
use ElementPack\Includes\Controls\SelectInput\Dynamic_Select;
use ElementPack\Element_Pack_Loader;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class Mega_menu extends Module_Base {

    public function show_in_panel() {
        return get_post_type() !== 'ep_megamenu_content';
    }

    public function get_name() {
        return 'bdt-mega-menu';
    }

    public function get_title() {
        return esc_html__('Mega Menu', 'bdthemes-element-pack');
    }

    public function get_categories() {
        return ['element-pack'];
    }

    public function get_keywords() {
        return ['mega', 'menu', 'navigation', 'vertical'];
    }

    public function get_icon() {
        return 'bdt-wi-mega-menu bdt-new';
    }

    public function get_style_depends() {
        if ($this->ep_is_edit_mode()) {
            return ['ep-styles'];
        } else {
            return ['ep-mega-menu', 'ep-font'];
        }
    }

    public function get_script_depends() {
        return ['ep-mega-menu', 'bdt-uikit', 'fontawesome'];
    }

    public function get_custom_help_url() {
        return 'https://youtu.be/ZOBLWIZvGLs';
    }

	public function has_widget_inner_wrapper(): bool {
        return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
    }
	
    
    public function register_controls() {
        $this->register_controls_layout();
        $this->register_layout_controls_animation();
        $this->register_vertical_menu_toggle_style();
        $this->register_style_controls_vertical_menu();
        $this->register_menu_item_tabs();
        $this->register_style_controls_submenu();
        $this->register_style_controls_toggle();
        $this->register_style_controls_badge();
    }

    protected function register_controls_layout() {
        $this->start_controls_section(
            'section_content_layout',
            [
                'label' => esc_html__('Layout', 'bdthemes-element-pack'),
            ]
        );
        $this->add_control(
            'navbar',
            [
                'label'   => esc_html__('Select Menu', 'bdthemes-element-pack'),
                'type'    => Controls_Manager::SELECT,
                'options' => element_pack_get_menu(),
                'default' => 0,
            ]
        );
        $this->add_control(
            'ep_megamenu_direction',
            [
                'label'              => esc_html__('Menu Directioin', 'bdthemes-element-pack'),
                'type'               => Controls_Manager::SELECT,
                'options'            => [
                    'horizontal' => esc_html__('Horizontal', 'bdthemes-element-pack'),
                    'vertical'   => esc_html__('Vertical', 'bdthemes-element-pack'),
                ],
                'default'            => 'horizontal',
                'dynamic'            => ['active' => true],
                'frontend_available' => true,
                'render_type'        => 'template',
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_header',
            [
                'label'         => esc_html__('Display Menu as a Toggle', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SWITCHER,
                'label_on'      => esc_html__('Yes', 'bdthemes-element-pack'),
                'label_off'     => esc_html__('No', 'bdthemes-element-pack'),
                'return_value'  => 'yes',
                'default'       => 'no',
                'separator'     => 'before',
                'condition' => [
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_bar_icon',
            [
                'label'         => esc_html__('Show Bar Icon', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SWITCHER,
                'label_on'      => esc_html__('Yes', 'bdthemes-element-pack'),
                'label_off'     => esc_html__('No', 'bdthemes-element-pack'),
                'return_value'  => 'yes',
                'default'       => 'yes',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical',
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_show_text',
            [
                'label'         => esc_html__('Show Text', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SWITCHER,
                'label_on'      => esc_html__('Yes', 'bdthemes-element-pack'),
                'label_off'     => esc_html__('No', 'bdthemes-element-pack'),
                'return_value'  => 'yes',
                'default'       => 'yes',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical',
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_text',
            [
                'label'         => esc_html__('Text', 'bdthemes-element-pack'),
                'label_block'   => true,
                'type'          => Controls_Manager::TEXT,
                'dynamic'       => [ 'active' => true ],
                'default'       => esc_html__('Browse Categories', 'bdthemes-element-pack'),
                'placeholder'   => esc_html__('Browse Categories', 'bdthemes-element-pack'),
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical',
                    'ep_megamenu_vertical_dropdown_show_text' => 'yes'
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_arrows',
            [
                'label'         => esc_html__('Show Arrows', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SWITCHER,
                'label_on'      => esc_html__('Yes', 'bdthemes-element-pack'),
                'label_off'     => esc_html__('No', 'bdthemes-element-pack'),
                'return_value'  => 'yes',
                'default'       => 'yes',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical',
                ]
            ]
        );

        $this->add_control(
            'ep_megamenu_vertical_dropdown_offset',
            [
                'label'         => esc_html__('Offset', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::NUMBER,
                'default'       => 10,
                'frontend_available' => true,
                'render_type' => 'none',
                'separator' => 'before',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_animation_type',
            [
                'label'   => esc_html__('Animation Type', 'bdthemes-element-pack'),
                'type'    => Controls_Manager::SELECT,
                'options' => $this->megamenu_animation_type(),
                'default' => 'bdt-animation-fade',
                'frontend_available' => true,
                'render_type' => 'none',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_animate_out',
            [
                'label'         => esc_html__('Animate Out', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SWITCHER,
                'frontend_available' => true,
                'render_type' => 'none',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_animation_duration',
            [
                'label'   => esc_html__('Duration', 'bdthemes-element-pack'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 200,
                'frontend_available' => true,
                'render_type' => 'none',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_delay_hide',
            [
                'label'   => esc_html__('Delay Hide', 'bdthemes-element-pack'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 800,
                'frontend_available' => true,
                'render_type' => 'none',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );
        $this->add_control(
            'ep_megamenu_vertical_dropdown_mode',
            [
                'label'      => esc_html__('Trigger Type', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SELECT,
                'options'    => [
                    'click'  => esc_html__('Click', 'bdthemes-element-pack'),
                    'hover' => esc_html__('Hover', 'bdthemes-element-pack'),
                ],
                'separator' => 'after',
                'default'    => 'click',
                'frontend_available' => true,
                'render_type' => 'none',
                'condition'     => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );

        $this->add_responsive_control(
            'default_menu_alignment',
            [
                'label'     => esc_html__('Alignment', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start'   => [
                        'title' => esc_html__('Left', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-center',
                    ],
                    'flex-end'  => [
                        'title' => esc_html__('Right', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-right',
                    ]
                ],
                'default'   => 'left',
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu.ep-megamenu-horizontal .bdt-navbar-nav' => 'justify-content: {{VALUE}}',
                ],
                'condition' => [
                    'ep_megamenu_direction' => 'horizontal'
                ]
            ]
        );

        $this->end_controls_section();
        $this->start_controls_section(
            'section_hamburger_menu',
            [
                'label' => esc_html__('Hamburger Menu', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );
        // $this->add_control(
        //     'show_hamburger_menu',
        //     [
        //         'label'     => esc_html__('Start From', 'bdthemes-element-pack'),
        //         'type'      => Controls_Manager::SELECT,
        //         'options'   => [
        //             's' => esc_html__('Mobile', 'bdthemes-element-pack'),
        //             'm' => esc_html__('Tablet', 'bdthemes-element-pack'),
        //             'none' => esc_html__('None', 'bdthemes-element-pack'),
        //         ],
        //         'default'   => 's',
        //         'separator' => 'before',
        //     ]
        // );
        $this->add_control(
            'show_hamburger_menu',
            [
                'label' => esc_html__('Breakpoint', 'bdthemes-element-pack'),
                'type' => Controls_Manager::SELECT,
                'default' => 's',
                'options' => [
                    's' => esc_html__('Mobile (> 767px)', 'bdthemes-element-pack'),
                    'm' => esc_html__('Tablet (> 1024px)', 'bdthemes-element-pack'),
                    'none' => esc_html__('None', 'bdthemes-element-pack'),
                ],
                'prefix_class' => 'bdt-mega-menu-hamburger-',
                'separator' => 'before',

            ]
        );

        $this->add_control(
            'mobile_menu_type',
            [
                'label' => esc_html__('Mobile Menu Type', 'bdthemes-element-pack') . BDTEP_NC,
                'type' => Controls_Manager::SELECT,
                'default' => 'hamburger',
                'options' => [
                    'hamburger' => esc_html__('Hamburger Menu', 'bdthemes-element-pack'),
                    'offcanvas' => esc_html__('Offcanvas', 'bdthemes-element-pack'),
                ],
                'condition' => [
                    'show_hamburger_menu!' => 'none',
                ],
                'frontend_available' => true,
                'render_type' => 'template',
            ]
        );

        $this->add_responsive_control(
            'hamburger_menu_alignment',
            [
                'label'     => esc_html__('Alignment', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start'   => [
                        'title' => esc_html__('Left', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-center',
                    ],
                    'flex-end'  => [
                        'title' => esc_html__('Right', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-right',
                    ]
                ],
                'default'   => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-mobile' => 'justify-content: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_offcanvas',
            [
                'label' => esc_html__('Offcanvas', 'bdthemes-element-pack') . BDTEP_NC,
                'condition' => [
                    'mobile_menu_type' => 'offcanvas',
                    'show_hamburger_menu!' => 'none',
                ],
            ]
        );
        $this->add_control(
            'offcanvas_logo_image',
            [
                'label'   => esc_html__('Logo Image', 'bdthemes-element-pack'),
                'type'    => Controls_Manager::MEDIA,
                'dynamic' => [
                    'active' => true,
                ],
                'default' => [
                    'url' => BDTEP_ASSETS_URL . 'images/logo-with-text.svg',
                ],
            ]
        );
        $this->add_control(
			'offcanvas_overlay',
			[
				'label'        => esc_html__('Overlay', 'bdthemes-element-pack'),
				'type'         => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'separator' => 'before',
			]
		);

		$this->add_control(
			'offcanvas_animations',
			[
				'label'     => esc_html__('Animations', 'bdthemes-element-pack'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'slide',
				'options'   => [
					'slide'  => esc_html__('Slide', 'bdthemes-element-pack'),
					'push'   => esc_html__('Push', 'bdthemes-element-pack'),
					'reveal' => esc_html__('Reveal', 'bdthemes-element-pack'),
					'none'   => esc_html__('None', 'bdthemes-element-pack'),
				],
			]
		);

		$this->add_control(
			'offcanvas_flip',
			[
				'label'        => esc_html__('Flip', 'bdthemes-element-pack'),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'right',
			]
		);

		$this->add_control(
			'offcanvas_close_button',
			[
				'label'   => esc_html__('Close Button', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'separator' => 'before'
			]
		);

		$this->add_control(
			'offcanvas_close_button_text',
			[
				'label'       => esc_html__('Close Button Text', 'bdthemes-element-pack'),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => ['active' => true],
				// 'default'     => esc_html__('Close', 'bdthemes-element-pack'),
				'placeholder' => esc_html__('Close', 'bdthemes-element-pack'),
				'condition' => [
					'offcanvas_close_button' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'button_close_icon_align',
			[
				'label'   => esc_html__('Close Button Align', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [
						'title' => esc_html__('Left', 'bdthemes-element-pack'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'bdthemes-element-pack'),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__('Right', 'bdthemes-element-pack'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'condition' => [
					'offcanvas_close_button' => 'yes',
				],
				'selectors_dictionary' => [
					'left' => 'left: 10px; right: auto;',
					'center' => 'left: 50%; right: auto; transform: translateX(-50%);',
					'right' => 'right: 10px; left: auto;',
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-close' => '{{VALUE}};',
				],
			]
		);

		$this->add_control(
			'offcanvas_bg_close',
			[
				'label'   => esc_html__('Close on Click Background', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'offcanvas_esc_close',
			[
				'label'   => esc_html__('Close on Press ESC', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_responsive_control(
			'offcanvas_width',
			[
				'label'      => esc_html__('Width', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'vw'],
				'range'      => [
					'px' => [
						'min' => 240,
						'max' => 1200,
					],
					'vw' => [
						'min' => 10,
						'max' => 100,
					]
				],
				'selectors' => [
					'body:not(.bdt-offcanvas-flip) {{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar' => 'width: {{SIZE}}{{UNIT}};left: -{{SIZE}}{{UNIT}};',
					'body:not(.bdt-offcanvas-flip) {{WRAPPER}} .bdt-offcanvas.bdt-open>.bdt-offcanvas-bar' => 'left: 0;',
					'.bdt-offcanvas-flip {{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar' => 'width: {{SIZE}}{{UNIT}};right: -{{SIZE}}{{UNIT}};',
					'.bdt-offcanvas-flip {{WRAPPER}} .bdt-offcanvas.bdt-open>.bdt-offcanvas-bar' => 'right: 0;',
				],
				'condition' => [
					'offcanvas_animations!' => ['push', 'reveal'],
				],
				'separator' => 'before'
			]
		);

		$this->add_responsive_control(
			'offcanvas_height',
			[
				'label'      => esc_html__('Height', 'bdthemes-element-pack'),
				'description' => esc_html__('This height option only needs for rare designs. When you will not get the proper height of Offcanvas, you may use this option in that situation.', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'vh'],
				'range'      => [
					'px' => [
						'min' => 200,
						'max' => 1200,
					],
					'vw' => [
						'min' => 10,
						'max' => 100,
					]
				],
				'selectors' => [
					'body:not(.bdt-offcanvas-flip) {{WRAPPER}} .bdt-offcanvas' => 'height: {{SIZE}}{{UNIT}};',
					'.bdt-offcanvas-flip {{WRAPPER}} .bdt-offcanvas' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'offcanvas_animations!' => ['push', 'reveal'],
				]
			]
		);

        // offcanvas footer part template load option
        $this->add_control(
			'template_id',
			[ 
				'label'       => __( 'Select Template', 'bdthemes-element-pack' ),
				'type'        => Dynamic_Select::TYPE,
				'label_block' => true,
				'placeholder' => __( 'Type and select template', 'bdthemes-element-pack' ),
				'query_args'  => [ 
					'query' => 'elementor_template',
				],
			]
		);

        $this->end_controls_section();
    }

    protected function register_style_controls_vertical_menu() {
        $this->start_controls_section(
            'section_style_vertical_menu',
            [
                'label' => esc_html__('Vertical Menu', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition'  => [
                    'ep_megamenu_direction' => 'vertical',
                ],
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_width',
            [
                'label'      => esc_html__('Width', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vh'],
                'range'      => [
                    'px' => [
                        'min'  => 200,
                        'max'  => 600,
                        'step' => 1,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu' => '--ep-megamenu-vertical-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );


        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'vertical_menu_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'types'     => ['classic', 'gradient'],
                'selector'  => '{{WRAPPER}} .ep-megamenu-vertical .bdt-navbar-nav,
                                {{WRAPPER}} .ep-megamenu .ep-default-submenu-panel',
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu-vertical .bdt-navbar-nav,
                    {{WRAPPER}} .ep-megamenu .ep-default-submenu-panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'vertical_menu_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu-vertical .bdt-navbar-nav, {{WRAPPER}} .ep-megamenu .ep-default-submenu-panel',
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu-vertical .bdt-navbar-nav, {{WRAPPER}} .ep-megamenu .ep-default-submenu-panel' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->end_controls_section();
    }
    protected function register_layout_controls_animation() {
        $this->start_controls_section(
            'section_megamenu_layout_animation',
            [
                'label' => esc_html__('Dropdown Settings', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'ep_megamenu_offset',
            [
                'label'         => esc_html__('Offset', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SLIDER,
                'frontend_available' => true,
                'size_units'    => ['px'],
                'range'         => [
                    'px'        => [
                        'min'   => 0,
                        'max'   => 300,
                        'step'  => 1,
                    ]
                ],
                'devices' => ['desktop', 'mobile'],
                'desktop_default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'mobile_default' => [
                    'size' => 5,
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_control(
            'ep_megamenu_animation_type',
            [
                'label'   => esc_html__('Animation Type', 'bdthemes-element-pack'),
                'type'    => Controls_Manager::SELECT,
                'options' => $this->megamenu_animation_type(),
                'default' => 'bdt-animation-fade',
                'frontend_available' => true,
                'render_type' => 'none',
            ]
        );
        $this->add_control(
            'ep_megamenu_animate_out',
            [
                'label'         => esc_html__('Animate Out', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SWITCHER,
                'frontend_available' => true,
                'render_type' => 'none',
            ]
        );
        $this->add_control(
            'ep_megamenu_animation_duration',
            [
                'label'   => esc_html__('Duration', 'bdthemes-element-pack'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 200,
                'frontend_available' => true,
                'render_type' => 'none',
            ]
        );
        $this->add_control(
            'ep_megamenu_delay_hide',
            [
                'label'   => esc_html__('Delay Hide', 'bdthemes-element-pack'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 800,
                'frontend_available' => true,
                'render_type' => 'none',
            ]
        );
        $this->add_control(
            'ep_megamenu_mode',
            [
                'label'      => esc_html__('Trigger Type (Desktop)', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SELECT,
                'options'    => [
                    'click'  => esc_html__('Click', 'bdthemes-element-pack'),
                    'hover' => esc_html__('Hover', 'bdthemes-element-pack'),
                ],
                'default'    => 'hover',
                'frontend_available' => true,
                'render_type' => 'none',
            ]
        );
        $this->end_controls_section();
    }

    protected function register_vertical_menu_toggle_style() {
        $this->start_controls_section(
            'section_style_vertical_menu_toggle',
            [
                'label' => esc_html__('Toggle Button', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'ep_megamenu_vertical_header' => 'yes',
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );
        $this->add_control(
            'vertical_menu_toggle_color',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-vertical-toggle-btn' => 'color: {{VALUE}}',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'vertical_menu_toggle_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'types'     => ['classic', 'gradient'],
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-vertical-toggle-btn',
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_toggle_padding',
            [
                'label'                 => esc_html__('Paddiing', 'bdthemes-element-pack'),
                'type'                  => Controls_Manager::DIMENSIONS,
                'size_units'            => ['px', '%', 'em'],
                'selectors'             => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-vertical-toggle-btn'    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_toggle_margin',
            [
                'label'                 => esc_html__('Margin', 'bdthemes-element-pack'),
                'type'                  => Controls_Manager::DIMENSIONS,
                'size_units'            => ['px', '%', 'em'],
                'selectors'             => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-vertical-toggle-btn'    => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_toggle_spacing',
            [
                'label'         => esc_html__('Spacing', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SLIDER,
                'size_units'    => ['px'],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu-vertical-toggle-btn span' => 'margin: 0px {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'vertical_menu_toggle_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-vertical-toggle-btn',
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_toggle_radius',
            [
                'label'                 => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'                  => Controls_Manager::DIMENSIONS,
                'size_units'            => ['px', '%', 'em'],
                'selectors'             => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-vertical-toggle-btn'    => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'vertical_menu_toggle_typography',
                'label'     => esc_html__('Typography', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-vertical-toggle-btn',
                'separator' => 'after'
            ]
        );

        $this->add_responsive_control(
            'vertical_menu_toggle_bar_size',
            [
                'label'         => esc_html__('Bar Size', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SLIDER,
                'size_units'    => ['px'],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu-vertical-toggle-btn svg' => 'width:{{SIZE}}{{UNIT}}; height:{{SIZE}}{{UNIT}};line-height:{{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before',
                'condition' => [
                    'ep_megamenu_vertical_dropdown_bar_icon' => 'yes'
                ]
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_toggle_arrow_size',
            [
                'label'         => esc_html__('Arrow Size', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SLIDER,
                'size_units'    => ['px'],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu-vertical-toggle-btn i' => 'font-size:{{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'ep_megamenu_vertical_dropdown_arrows' => 'yes'
                ]
            ]
        );
        $this->end_controls_section();
    }

    protected function register_menu_item_tabs() {

        $this->start_controls_section(
            'section_content_style_items',
            [
                'label' => esc_html__('Menu Items', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->start_controls_tabs(
            'tab_menu_item_style'
        );
        $this->start_controls_tab(
            'menu_item_normal',
            [
                'label' => esc_html__('Normal', 'bdthemes-element-pack'),
            ]
        );
        $this->add_control(
            'menu_text_color',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav > li > a,  #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_arrow_color',
            [
                'label'     => esc_html__('Arrow Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav li .bdt-megamenu-indicator,  #ep-megamenu-{{ID}}-virtual.bdt-accordion li .bdt-megamenu-indicator' => 'color: {{VALUE}};',

                    '{{WRAPPER}} .ep-megamenu .ep-default-submenu-panel .menu-item-has-children::before' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'menu_background_color',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'types'     => ['classic', 'gradient'],
                'selector'  => '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav > li > a,
                #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link',
            ]
        );

        $this->add_responsive_control(
            'menu_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav > li > a,
                    #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before'
            ]
        );
        $this->add_responsive_control(
            'menu_margin',
            [
                'label'      => esc_html__('Margin', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav > li > a,
                    #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'menu_item_gap',
            [
                'label'         => esc_html__('Spacing', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SLIDER,
                'size_units'    => ['px'],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav' => 'grid-gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.bdt-accordion' => 'grid-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'menu_border',
                'selector' => '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav > li > a,
                #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link',
            ]
        );

        $this->add_responsive_control(
            'menu_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav > li > a,
                    #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );
        $this->add_responsive_control(
            'menu_arrow_spacing',
            [
                'label'      => esc_html__('Arrow Spacing', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'separator' => 'before',
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu.ep-megamenu-horizontal .bdt-navbar-nav .bdt-megamenu-indicator' => 'margin-left: {{SIZE}}{{UNIT}};',
                    '#ep-megamenu-{{ID}}-virtual.bdt-accordion .bdt-accordion-title .bdt-megamenu-indicator' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'ep_megamenu_direction' => 'horizontal'
                ]
            ]
        );
        $this->add_responsive_control(
            'vertical_menu_arrow_spacing',
            [
                'label'      => esc_html__('Arrow Spacing', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'separator' => 'before',
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu.ep-megamenu-vertical li .bdt-megamenu-indicator' => 'right: {{SIZE}}{{UNIT}};',
                    '#ep-megamenu-{{ID}}-virtual.bdt-accordion .bdt-accordion-title .bdt-megamenu-indicator' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );

        $this->add_control(
            'ep_megamenu_dropdown_arrows',
            [
                'label'         => esc_html__('Hide Dropdown Arrows', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SWITCHER,
                'label_on'      => esc_html__('Yes', 'bdthemes-element-pack'),
                'label_off'     => esc_html__('No', 'bdthemes-element-pack'),
                'return_value'  => 'none',
                'default'       => 'block',
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .bdt-navbar-nav .bdt-megamenu-indicator' => 'display:{{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'vertical_menu_alignment',
            [
                'label'     => esc_html__('Alignment', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start'   => [
                        'title' => esc_html__('Left', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-center',
                    ],
                    'flex-end'  => [
                        'title' => esc_html__('Right', 'bdthemes-element-pack'),
                        'icon'  => 'eicon-h-align-right',
                    ]
                ],
                'default'   => 'left',
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu.ep-megamenu-vertical .bdt-navbar-nav li .ep-menu-nav-link' => 'justify-content: {{VALUE}}',
                ],
                'condition' => [
                    'ep_megamenu_direction' => 'vertical'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'menu_item_typography',
                'label'    => esc_html__('Typography', 'bdthemes-element-pack'),
                'selector' => '{{WRAPPER}} .ep-megamenu .bdt-navbar-nav > li > a,
                #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'menu_item_hover',
            [
                'label' => esc_html__('Hover/Active', 'bdthemes-element-pack'),
            ]
        );
        $this->add_control(
            'menu_text_h_color',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .bdt-navbar-nav > li > a:hover,
                    {{WRAPPER}} .ep-megamenu .bdt-navbar-nav > li > a.active,
                    #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'menu_h_background_color',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'types'     => ['classic', 'gradient'],
                'default'   => '',
                'selector'  => '{{WRAPPER}} .ep-megamenu .megamenu-header-default .bdt-navbar-nav > li > a:hover,
                                {{WRAPPER}} .ep-megamenu .bdt-navbar-nav > li > a.active,
                                #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'menu_h_border',
                'selector' => '{{WRAPPER}} .ep-megamenu .bdt-navbar-nav > li > a:hover,
                                {{WRAPPER}} .ep-megamenu .bdt-navbar-nav > li > a.active,
                                #ep-megamenu-{{ID}}-virtual.bdt-accordion li a.ep-menu-nav-link:hover',
            ]
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();
    }

    protected function register_style_controls_submenu() {
        $this->start_controls_section(
            'style_tab_submenu_item',
            [
                'label' => esc_html__('Dropdown', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs(
            'submenu_active_hover_tabs'
        );
        $this->start_controls_tab(
            'submenu_normal_tab',
            [
                'label' => esc_html__('Normal', 'bdthemes-element-pack'),
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'menu_item_background',
                'label'    => esc_html__('Item background', 'bdthemes-element-pack'),
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .ep-megamenu .menu-item-has-children .bdt-drop,
                               {{WRAPPER}} .ep-megamenu .ep-megamenu-panel.bdt-drop,
                               #ep-megamenu-{{ID}}-virtual.bdt-accordion',
            ]
        );

        $this->add_responsive_control(
            'menu_item_dropdown_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'separator' => 'before',
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-panel.bdt-drop, #ep-megamenu-{{ID}}-virtual.bdt-accordion' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'menu_item_dropdown_margin',
            [
                'label'      => esc_html__('Margin', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-panel.bdt-drop, #ep-megamenu-{{ID}}-virtual.bdt-accordion' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'sub_menu_item_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-panel.bdt-drop, #ep-megamenu-{{ID}}-virtual.bdt-accordion',
                'separator' => 'before',
            ]
        );
        $this->add_responsive_control(
            'menu_item_dropdown_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-panel.bdt-drop, #ep-megamenu-{{ID}}-virtual.bdt-accordion' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],

            ]
        );
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'      => 'sub_menu_item_shadow',
                'label'     => esc_html__('Box Shadow', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-panel.bdt-drop, #ep-megamenu-{{ID}}-virtual.bdt-accordion',
            ]
        );
        $this->add_responsive_control(
            'ep_megamenu_full_width_offset',
            [
                'label'         => esc_html__('Full Width Offset (px)', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SLIDER,
                'size_units'    => ['px'],
                'range'         => [
                    'px'        => [
                        'min'   => 0,
                        'max'   => 400,
                        'step'  => 1,
                    ]
                ],
                'default'       => [
                    'unit'      => 'px',
                    'size'      => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-panel' => '--bdt-position-viewport-offset: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before',
                'condition' => [
                    'ep_megamenu_direction' => 'horizontal'
                ]
            ]
        );

        $this->add_control(
            'submenu_wp_default_subitem',
            [
                'label'     => esc_html__('Classic Submenu', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        $this->add_responsive_control(
            'submenu_item_color',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .menu-item-has-children .bdt-drop li a,
                    #ep-megamenu-{{ID}}-virtual .bdt-accordion-content li a ' => 'color: {{VALUE}}',
                ]
            ]
        );
        $this->add_responsive_control(
            'submenu_item_bg_color',
            [
                'label'     => esc_html__('Background Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .menu-item-has-children .bdt-drop li a,
                    #ep-megamenu-{{ID}}-virtual .bdt-accordion-content li a' => 'background-color: {{VALUE}}',
                ]
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'submene_item_bg_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .menu-item-has-children .bdt-drop li a,
                #ep-megamenu-{{ID}}-virtual .bdt-accordion-content li a',
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'menu_item_submenu_typography',
                'label'    => esc_html__('Typography', 'bdthemes-element-pack'),
                'selector' => '{{WRAPPER}} .ep-megamenu .menu-item-has-children .bdt-drop li a,
                #ep-megamenu-{{ID}}-virtual .bdt-accordion-content li a',
            ]
        );
        $this->end_controls_tab();

        $this->start_controls_tab(
            'submenu_hover_tab',
            [
                'label' => esc_html__('Hover', 'bdthemes-element-pack'),
            ]
        );
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'menu_item_hover_background',
                'label'    => esc_html__('Item background', 'bdthemes-element-pack'),
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .ep-megamenu .menu-item-has-children .bdt-drop,:hover
                               {{WRAPPER}} .ep-megamenu .ep-megamenu-panel.bdt-drop:hover,
                               #ep-megamenu-{{ID}}-virtual.bdt-accordion:hover',
            ]
        );

        $this->add_control(
            'submenu_wp_hover_default_subitem',
            [
                'label'     => esc_html__('Classic Submenu', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );
        $this->add_responsive_control(
            'item_text_color_hover',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .menu-item-has-children .ep-megamenu-panel > li > a:active' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .ep-megamenu .menu-item-has-children .ep-megamenu-panel > li:hover > a'  => 'color: {{VALUE}}',
                    '#ep-megamenu-{{ID}}-virtual .bdt-accordion-content li:hover a'  => 'color: {{VALUE}}',
                ],
            ]
        );
        $this->add_responsive_control(
            'item_text_hover_background',
            [
                'label'     => esc_html__('Background Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .menu-item-has-children .ep-megamenu-panel > li > a:active' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .ep-megamenu .menu-item-has-children .ep-megamenu-panel > li:hover > a'  => 'background-color: {{VALUE}}',
                    '#ep-megamenu-{{ID}}-virtual .bdt-accordion-content li:hover a'  => 'background-color: {{VALUE}}',
                ],
            ]
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();
    }

    protected function register_style_controls_toggle() {
        $this->start_controls_section(
            'section_style_hamburger_menu',
            [
                'label' => esc_html__('Hamburger Menu', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'hamburger_menu_toggle_color',
            [
                'label'     => esc_html__('Icon Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-mobile .bdt-navbar-toggle svg' => 'color: {{VALUE}}',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'hamburger_menu_toggle_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'types'     => ['classic', 'gradient'],
                'selector'  => '{{WRAPPER}} .ep-megamenu .megamenu-header-mobile .bdt-navbar-toggle',
            ]
        );
        $this->add_responsive_control(
            'hamburger_menu_toggle_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-mobile .bdt-navbar-toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'hamburger_menu_toggle_margin',
            [
                'label'      => esc_html__('Margin', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-mobile .bdt-navbar-toggle' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'hamburger_menu_toggle_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .megamenu-header-mobile .bdt-navbar-toggle',
            ]
        );

        $this->add_responsive_control(
            'hamburger_menu_toggle_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .megamenu-header-mobile .bdt-navbar-toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'hamburger_toggle_icon_size',
            [
                'label'         => esc_html__('Toggle Icon Size', 'bdthemes-element-pack'),
                'type'          => Controls_Manager::SLIDER,
                'range'         => [
                    'px'        => [
                        'min'   => 0,
                        'max'   => 100,
                        'step'  => 1,
                    ]
                ],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .bdt-navbar-toggle' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
			'hamburger_dropdown_height',
			[
				'label'      => esc_html__('Dropdown Height', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'vh'],
                'default'    => [ 
					'unit' => 'vh',
				],
				'range'      => [
					'px' => [
						'min' => 50,
						'max' => 1280,
					],
					'vh' => [
						'min' => 10,
						'max' => 100,
					]
				],
				'selectors' => [
					'#ep-megamenu-{{ID}}-virtual.bdt-accordion' => 'max-height: {{SIZE}}{{UNIT}}; overflow-y: auto;',
				],
                'condition' => [
                    'mobile_menu_type' => 'hamburger',
                ],
			]
		);

        $this->end_controls_section();
        $this->start_controls_section(
			'section_style_offcanvas_content',
			[
				'label' => esc_html__('Offcanvas', 'bdthemes-element-pack'),
				'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'mobile_menu_type' => 'offcanvas',
                    'show_hamburger_menu!' => 'none',
                ],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'offcanvas_content_background',
				'label'     => esc_html__('Background', 'bdthemes-element-pack'),
				'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar',
			]
		);

        $this->add_responsive_control(
			'offcanvas_content_padding',
			[
				'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-close' => 'top: {{TOP}}{{UNIT}}; right: {{RIGHT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'offcanvas_content_shadow',
				'selector'  => '{{WRAPPER}} .bdt-offcanvas > div',
				'separator' => 'before',
			]
		);

        $this->add_control(
            'offcanvas_overlay_color',
            [
                'label'     => esc_html__('Overlay Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-offcanvas-overlay::before' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_overlay' => 'yes',
                ],
            ]
        );

        /**
         * Header Logo
         */
        $this->add_control(
            'offcanvas_content_heading_header_logo_image',
            [
                'label'     => esc_html__('Header', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'offcanvas_content_header_border_color',
            [
                'label'     => esc_html__('Border Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-offcanvas-logo' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_header_spacing',
            [
                'label'     => esc_html__('Spacing', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-offcanvas-logo' => 'padding-bottom: {{SIZE}}{{UNIT}}; margin-bottom: -{{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-megamenu-offcanvas-content' => 'margin-top: calc({{SIZE}}{{UNIT}} * 2);',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'offcanvas_content_heading_logo_image_height',
            [
                'label'     => esc_html__('Image Height', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-offcanvas .ep-offcanvas-logo img' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_heading_logo_image_width',
            [
                'label'     => esc_html__('Image Width', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 300,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-offcanvas .ep-offcanvas-logo img' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        /**
         * Close Icon
         */
        $this->add_control(
            'offcanvas_heading_close_icon',
            [
                'label'     => esc_html__('Close Icon', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        $this->start_controls_tabs('offcanvas_close_icon_tabs');
        $this->start_controls_tab(
            'close_icon_normal',
            [
                'label' => esc_html__('Normal', 'bdthemes-element-pack'),
            ]
        );
        $this->add_control(
            'close_icon_normal_color',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close svg' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'close_icon_normal_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close',
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'close_icon_normal_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close',
            ]
        );
        $this->add_responsive_control(
            'close_icon_normal_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'close_icon_normal_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'      => 'close_icon_normal_shadow',
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close',
            ]
        );
        $this->add_responsive_control(
            'close_icon_normal_size',
            [
                'label'      => esc_html__('Size', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'close_icon_normal_offset',
            [
                'label'      => esc_html__('Offset', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::POPOVER_TOGGLE,
                'return_value' => 'yes',
            ]
        );
        $this->start_popover();
        $this->add_responsive_control(
            'close_icon_normal_offset_top',
            [
                'label'      => esc_html__('Top', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'condition' => [
                    'close_icon_normal_offset' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close' => 'top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'close_icon_normal_offset_right',
            [
                'label'      => esc_html__('Right', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'condition' => [
                    'close_icon_normal_offset' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->end_popover();
        $this->end_controls_tab();
        $this->start_controls_tab(
            'close_icon_hover',
            [
                'label' => esc_html__('Hover', 'bdthemes-element-pack'),
            ]
        );
        $this->add_control(
            'close_icon_hover_color',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close:hover svg' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'close_icon_hover_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close:hover',
            ]
        );
        $this->add_control(
            'close_icon_hover_border_color',
            [
                'label'     => esc_html__('Border Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'close_icon_normal_border_border!' => '',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'      => 'close_icon_hover_shadow',
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .bdt-offcanvas-close:hover',
            ]
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();

        /**
         * Footer Content
         */
        $this->add_control(
            'offcanvas_content_heading_footer_logo_image',
            [
                'label'     => esc_html__('Footer', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'offcanvas_content_footer_border_color',
            [
                'label'     => esc_html__('Border Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-offcanvas-footer' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_footer_spacing',
            [
                'label'     => esc_html__('Spacing', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-offcanvas-footer' => 'padding-top: {{SIZE}}{{UNIT}}; margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        /**
         * Offcanvas Menu Items
         */
        $this->start_controls_section(
            'section_style_offcanvas_menu_items',
            [
                'label' => esc_html__('Offcanvas Menu', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'mobile_menu_type' => 'offcanvas',
                    'show_hamburger_menu!' => 'none',
                ],
            ]
        );

        $this->start_controls_tabs('offcanvas_content_tabs');
        $this->start_controls_tab(
            'offcanvas_content_normal',
            [
                'label' => esc_html__('Normal', 'bdthemes-element-pack'),
            ]
        );
        $this->add_control(
            'offcanvas_content_normal_color',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'offcanvas_content_normal_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a',
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'offcanvas_content_normal_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a',
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'offcanvas_content_normal_typography',
                'label'     => esc_html__('Typography', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a',
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_gap',
            [
                'label'      => esc_html__('Gap', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar .ep-offcanvas-nav' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        /**
         * Arrow Icon
         */
        $this->add_control(
            'offcanvas_content_normal_arrow_icon_heading',
            [
                'label'     => esc_html__('Arrow Icon', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'offcanvas_content_normal_arrow_color',
            [
                'label'     => esc_html__('Arrow Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a i' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'offcanvas_content_normal_arrow_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a i',
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'offcanvas_content_normal_arrow_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a i',
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_arrow_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a i' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_arrow_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a i' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_arrow_size',
            [
                'label'      => esc_html__('Size', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li a i' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        /**
         * Menu Dropdown
         */
        $this->add_control(
            'offcanvas_content_normal_menu_dropdown_heading',
            [
                'label'     => esc_html__('Menu Dropdown', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'offcanvas_content_normal_menu_dropdown_background',
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-megamenu-panel',
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'offcanvas_content_normal_menu_dropdown_border',
                'selector'  => '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-megamenu-panel',
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_menu_dropdown_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-megamenu-panel' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_menu_dropdown_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-megamenu-panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'offcanvas_content_normal_menu_dropdown_gap',
            [
                'label'      => esc_html__('Spacing', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ep-megamenu .ep-megamenu-offcanvas .ep-megamenu-panel' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->end_controls_tab();
        $this->start_controls_tab(
            'offcanvas_content_hover',
            [
                'label' => esc_html__('Hover/Active', 'bdthemes-element-pack'),
            ]
        );
        $this->add_control(
            'offcanvas_content_hover_color',
            [
                'label'     => esc_html__('Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li:hover a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li.active a' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'offcanvas_content_hover_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li:hover a, {{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li.active a',
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'offcanvas_content_hover_border',
                'label'     => esc_html__('Border', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li:hover a, {{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li.active a',
            ]
        );

        /**
         * Arrow Icon
         */
        $this->add_control(
            'offcanvas_content_hover_arrow_icon_heading',
            [
                'label'     => esc_html__('Arrow Icon', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        $this->add_control(
            'offcanvas_content_hover_arrow_color',
            [
                'label'     => esc_html__('Arrow Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li:hover a i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li.active a i' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'      => 'offcanvas_content_hover_arrow_background',
                'label'     => esc_html__('Background', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li:hover a i, {{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li.active a i',
            ]
        );
        //border color
        $this->add_control(
            'offcanvas_content_hover_arrow_border_color',
            [
                'label'     => esc_html__('Border Color', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li:hover a i, {{WRAPPER}} .bdt-offcanvas .bdt-offcanvas-bar li.active a i' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'offcanvas_content_hover_arrow_border_border!' => '',
                ],
            ]
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();
		$this->end_controls_section();
    }

    protected function register_style_controls_badge() {
        $this->start_controls_section(
            'section_style_badge',
            [
                'label' => esc_html__('Badge', 'bdthemes-element-pack'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'badge_position_x',
            [
                'label'     => esc_html__('Offset (X)', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'     => [
                    'px'    => [
                        'min'   => -200,
                        'max'   => 200,
                        'step'  => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ep-badge-label, #ep-megamenu-{{ID}}-virtual .ep-badge-label' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'badge_position',
            [
                'label'     => esc_html__('Offset (Y)', 'bdthemes-element-pack'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'     => [
                    'px'    => [
                        'min'   => -200,
                        'max'   => 200,
                        'step'  => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ep-badge-label, #ep-megamenu-{{ID}}-virtual .ep-badge-label' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'badge_border',
                'label'    => esc_html__('Border', 'bdthemes-element-pack'),
                'selector' => '{{WRAPPER}} .ep-badge-label, #ep-megamenu-{{ID}}-virtual .ep-badge-label',
                'separator' => 'before'
            ]
        );
        $this->add_responsive_control(
            'badge_radius',
            [
                'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-badge-label, #ep-megamenu-{{ID}}-virtual .ep-badge-label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'badge_padding',
            [
                'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .ep-badge-label, #ep-megamenu-{{ID}}-virtual .ep-badge-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'badge_typography',
                'label'     => esc_html__('Typography', 'bdthemes-element-pack'),
                'selector'  => '{{WRAPPER}} .ep-badge-label, #ep-megamenu-{{ID}}-virtual .ep-badge-label',
            ]
        );

        $this->end_controls_section();
    }

    protected function megamenu_animation_type() {
        $animation_type = [
            'bdt-animation-fade'                => esc_html__('Fade', 'bdthemes-element-pack'),
            'bdt-animation-scale-up'            => esc_html__('Scale UP', 'bdthemes-element-pack'),
            'bdt-animation-slide-top'           => esc_html__('Slide Top', 'bdthemes-element-pack'),
            'bdt-animation-slide-bottom'        => esc_html__('Slide Bottom', 'bdthemes-element-pack'),
            'bdt-animation-slide-left'          => esc_html__('Slide Left', 'bdthemes-element-pack'),
            'bdt-animation-slide-right'         => esc_html__('Slide Right', 'bdthemes-element-pack'),
            'bdt-animation-slide-top-small'     => esc_html__('Slide Top Small', 'bdthemes-element-pack'),
            'bdt-animation-slide-bottom-small'  => esc_html__('Slide Bottom Small', 'bdthemes-element-pack'),
            'bdt-animation-slide-left-small'    => esc_html__('Slide Left Small', 'bdthemes-element-pack'),
            'bdt-animation-slide-right-small'   => esc_html__('Slide Right Small', 'bdthemes-element-pack'),
            'bdt-animation-slide-top-medium'    => esc_html__('Slide Top Medium', 'bdthemes-element-pack'),
            'bdt-animation-slide-bottom-medium' => esc_html__('Slide Bottom Medium', 'bdthemes-element-pack'),
            'bdt-animation-slide-left-medium'   => esc_html__('Slide Left Medium', 'bdthemes-element-pack'),
            'bdt-animation-slide-right-medium'  => esc_html__('Slide Right Medium', 'bdthemes-element-pack'),
            'bdt-animation-kenburns'            => esc_html__('Kenburns', 'bdthemes-element-pack'),
            'bdt-animation-shake'               => esc_html__('Shake', 'bdthemes-element-pack'),
            'reveal-top'                        => esc_html__('Reveal Top', 'bdthemes-element-pack'),
            'reveal-bottom'                     => esc_html__('Reveal Bottom', 'bdthemes-element-pack'),
            'reveal-left'                       => esc_html__('Reveal Left', 'bdthemes-element-pack'),
            'reveal-right'                      => esc_html__('Reveal Right', 'bdthemes-element-pack'),
        ];
        return $animation_type;
    }



    public function render() {
        $mega_menu               = element_pack_option('mega-menu', 'element_pack_other_settings', 'off');

        if ('on' === $mega_menu) {
            $rtl = is_rtl();
            $settings = $this->get_settings_for_display();
            $id = $this->get_id();
            
            if (!$settings['navbar']) {
                element_pack_alert(__('Please select a Menu from layout setting!', 'bdthemes-element-pack'));
            }

            $this->add_render_attribute('vertical-dropmenu', ['class' => ['ep-megamenu-vertical-dropdown']], null, true);

            $this->add_render_attribute(
                'ep-megamenu',
                [
                    'class' => ['ep-megamenu', 'initialized', 'ep-megamenu-' . $settings['ep_megamenu_direction'] . '']
                ],
                null,
                true
            );



            if ($rtl) {
                $this->add_render_attribute('ep-megamenu', ['data-is-rtl' => $rtl,], null, true);
            }
?>
            <div <?php $this->print_render_attribute_string('ep-megamenu'); ?>>
                <div class="megamenu-header-default">
                    <?php if (('yes' === $settings['ep_megamenu_vertical_header']) && ('vertical' === $settings['ep_megamenu_direction'])) { ?>
                        <button class="ep-megamenu-vertical-toggle-btn" type="button">
                            <?php
                            if ('yes' === $settings['ep_megamenu_vertical_dropdown_bar_icon']) {
                            ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z" />
                                </svg>
                            <?php
                            }; ?>

                            <?php if ('yes' === $settings['ep_megamenu_vertical_dropdown_show_text']) { ?>
                                <span> <?php esc_html_e($settings['ep_megamenu_vertical_dropdown_text'], 'bdthemes-element-pack'); ?></span>
                            <?php }; ?>

                            <?php
                            if ('yes' === $settings['ep_megamenu_vertical_dropdown_arrows']) { ?>
                                <i class="ep-icon-arrow-down-3"></i>
                            <?php
                            }; ?>
                        </button>
                        <div <?php $this->print_render_attribute_string('vertical-dropmenu'); ?>>
                            <?php $this->ep_megamenu_dynamic_content_default(); ?>
                        </div>
                    <?php
                    } else {
                        $this->ep_megamenu_dynamic_content_default();
                    } ?>

                </div>
                <div class="megamenu-header-mobile" style="display: none;">
                    <?php if ('offcanvas' === $settings['mobile_menu_type']) {
                        
                        $offcanvas_id = 'ep-megamenu-offcanvas-' . $id;
                        $this->add_render_attribute('offcanvas', 'class', 'ep-megamenu-offcanvas bdt-offcanvas');
                        $this->add_render_attribute('offcanvas', 'id', $offcanvas_id);
                        $this->add_render_attribute('offcanvas', 'data-bdt-offcanvas', 'mode: ' . $settings['offcanvas_animations'] . ';');
                        
                        $this->add_render_attribute('offcanvas', 'data-bdt-offcanvas', 'mode: ' . $settings['offcanvas_animations'] . ';');

                        if ($settings['offcanvas_overlay']) {
                            $this->add_render_attribute('offcanvas', 'data-bdt-offcanvas', 'overlay: true;');
                        }

                        if ('right' == $settings['offcanvas_flip']) {
                            $this->add_render_attribute('offcanvas', 'data-bdt-offcanvas', 'flip: true;');
                        }

                        if ('yes' !== $settings['offcanvas_bg_close']) {
                            $this->add_render_attribute('offcanvas', 'data-bdt-offcanvas', 'bg-close: false;');
                        }

                        if ('yes' !== $settings['offcanvas_esc_close']) {
                            $this->add_render_attribute('offcanvas', 'data-bdt-offcanvas', 'esc-close: false;');
                        }

                        $this->add_render_attribute('offcanvas-toggle', 'data-bdt-toggle', 'target: #' . $offcanvas_id . ';');
                    }
                    ?>
                        
                        <a href="javascript:void(0);" class="bdt-navbar-toggle" <?php $this->print_render_attribute_string('offcanvas-toggle'); ?> aria-label="<?php esc_html_e('Toggle Menu', 'bdthemes-element-pack'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z" />
                            </svg>

                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </a>

                </div>

                <?php if ('offcanvas' === $settings['mobile_menu_type']) : ?>
                    <div <?php $this->print_render_attribute_string('offcanvas'); ?>>
                        <div class="bdt-offcanvas-bar">
                            <div class="ep-offcanvas-header">
                                <?php if (!empty($settings['offcanvas_logo_image']['url'])) : ?>
                                    <div class="ep-offcanvas-logo">
                                        <img src="<?php echo esc_url($settings['offcanvas_logo_image']['url']); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                                    </div>
                                <?php endif; ?>
                                <?php if ('yes' === $settings['offcanvas_close_button']) : ?>
                                    <button class="bdt-offcanvas-close" type="button" data-bdt-close></button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ep-megamenu-offcanvas-content">
                                <?php $this->ep_megamenu_dynamic_content_default_offcanvas(); ?>
                            </div>

                            <div class="ep-offcanvas-footer">
                                <?php if ( ! empty( $settings['template_id'] ) ) {
								// PHPCS - should not be escaped.
								echo Element_Pack_Loader::elementor()->frontend->get_builder_content_for_display( $settings['template_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo wp_kses( element_pack_template_edit_link( $settings['template_id'] ), element_pack_allow_tags('text') );
                                } ?>
                            </div>

                        </div>
                    </div>
                <?php endif; ?>

            </div>
<?php
        } else {
            element_pack_alert(__('Please Enable Mega Menu Modules from Element Pack pro > other setting > Mega Menu', 'bdthemes-element-pack'));
        }
    }

    public function ep_megamenu_dynamic_content_default() {
        $settings = $this->get_settings_for_display();
        $nav_menu = !empty($settings['navbar']) ? wp_get_nav_menu_object($settings['navbar']) : false;
        $id       = $this->get_id();
        if (!$nav_menu) {
            return;
        }
        $nav_menu_args = [
            'fallback_cb'        => false,
            'container'          => false,
            'items_wrap'         => '<ul id="%1$s" class="bdt-navbar-nav %2$s">%3$s</ul>',
            'menu_id'            => 'ep-megamenu-' . $id . '',
            'menu_class'         => '',
            'theme_location'     => 'default_navmenu',
            'menu'               => $nav_menu,
            'echo'               => true,
            'depth'              => 0,
            'walker'             => new Mega_Menu_Walker,
        ];
        wp_nav_menu(apply_filters('widget_nav_menu_args', $nav_menu_args, $nav_menu, $settings));
    }

    public function ep_megamenu_dynamic_content_default_offcanvas() {
        $settings = $this->get_settings_for_display();
        $nav_menu = !empty($settings['navbar']) ? wp_get_nav_menu_object($settings['navbar']) : false;
        $id       = $this->get_id();
        if (!$nav_menu) {
            return;
        }
        $nav_menu_args = [
            'fallback_cb'        => false,
            'container'          => true,
            'items_wrap'         => '<ul id="%1$s" data-bdt-nav class="bdt-nav-default bdt-flex-column ep-offcanvas-nav %2$s">%3$s</ul>',
            'menu_id'            => 'ep-megamenu-' . $id . '',
            'menu_class'         => '',
            'theme_location'     => 'default_navmenu',
            'menu'               => $nav_menu,
            'echo'               => true,
            'depth'              => 0,
            'walker'             => new Mega_Menu_Walker,
        ];
        wp_nav_menu(apply_filters('widget_nav_menu_args', $nav_menu_args, $nav_menu, $settings));
    }

}
