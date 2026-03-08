<?php
namespace UiCore\Elementor;

use Elementor\Controls_Stack;
use Elementor\Plugin;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use PHP_CodeSniffer\Util\Help;
use UiCore\ThemeOptions;
use UiCore\Settings;
use UiCore\Helper;
use UiCore\Data;
use UiCore\ThemeBuilder;
use UiCore\Portfolio;

/**
 * Elementor Related functions
 */
class Core
{

    /**
     * Elementor Font Type Name for Typekit
     */
    const TYPEKIT = 'uicore_typekit';

    /**
     * Elementor Font Type Name for Typekit
     */
    const CUSTOM = 'uicore_custom';


    public function __construct()
    {

        add_filter('elementor/icons_manager/additional_tabs', [$this, 'add_custom_icons']);
        add_filter('add_post_metadata', ['\UiCore\Elementor\Core', 'update_globals_from_elementor'], 20, 5);
        add_filter('update_post_metadata', ['\UiCore\Elementor\Core', 'update_globals_from_elementor'], 20, 5);

        //Add Suport For theme Builder Locations
        add_action( 'elementor/theme/register_locations', [$this, 'elementor_locations'] );

        //Elementor missing ggogle fonts
        add_filter( 'elementor/fonts/additional_fonts',[$this, 'new_google_fonts'],20,1 );

        add_filter( 'elementor/fonts/groups', [ $this, 'register_fonts_groups' ] );
        add_filter( 'elementor/fonts/additional_fonts', [ $this, 'register_fonts_in_control' ] );
        add_action( 'elementor/fonts/print_font_links/' . self::TYPEKIT, [ '\UiCore\Assets', 'print_typekit_font_link' ] );

        $this->elementor_style();

        //Theme Style Button Selectors fix  & optimized_control_loading fix
        if(!is_admin()){
            add_filter('option_elementor_experiment-e_optimized_control_loading', function($val){
                return false;
            });
        }
        add_action( 'elementor/element/kit/section_buttons/after_section_end', [$this, 'override_theme_style_button_control'], 20, 2);

        //Theme Style Container Width
        add_action( 'elementor/element/kit/section_settings-layout/after_section_end', [$this, 'override_theme_style_container_width_control'], 20, 2);

        //MEtform Reset Default btn style
        add_action( 'elementor/element/mf-button/mf_btn_section_style/after_section_end', [$this, 'override_mf_style_button_control'], 20, 2);
        add_action( 'elementor/element/mf-button/mf_btn_border_style_tabs/after_section_end', [$this, 'override_mf_style_button_control_border'], 20, 2);
        add_action( 'elementor/element/mf-file-upload/input_section/after_section_end', [$this, 'override_mf_style_upload_control'], 20, 2);

        //Temp Fix for Elementor 3.7.x
        // \add_filter("elementpack/extend/visibility_controls", function($val){return false;});
        //Remove this bc is overwriting mini cart from woo
        \add_filter("elementpack/widget/wc_mini_cart", function($val){return false;});

        remove_filter( 'woocommerce_add_to_cart_fragments','\ElementPack\Modules\WcMiniCart\Module::element_pack_mini_cart_fragment' );
        remove_filter( 'woocommerce_locate_template','\ElementPack\Modules\WcMiniCart\Module::woocommerce_locate_template', 12, 3 );

        //EP Page Lines fix
		add_action('elementor/documents/register_controls', [$this, 'bdt_lines_fix'], 2, 1);

        //EP Scroll Nav Bg Fix
        add_action( 'elementor/element/bdt-scrollnav/section_style_nav/after_section_start', [$this, 'override_ep_bg_control'], 20, 2);

        //EP add Marquwe in Custom Carousel
        add_action( 'elementor/element/bdt-custom-carousel/section_additional_options/before_section_end', [$this, 'override_ep_custom_carousel_marquee'], 20, 2);
        add_action( 'elementor/element/bdt-custom-carousel/section_slides_style/before_section_end', [$this, 'override_ep_custom_carousel_marquee_style'], 20, 2);
        add_filter( 'elementor/widget/render_content', [$this, 'override_ep_custom_carousel_marquee_render'], 20, 2);


        //those were moved to the Elements plugin
        //TODO: remove this file in the future
        if(!class_exists('\UiCoreElements\Base')){
            // WPML String Translation plugin exist check
            if ( defined( 'WPML_ST_VERSION' ) ) {

                if ( class_exists( 'WPML_Elementor_Module_With_Items' ) ) {
                    $this->load_wpml_modules();
                }

                add_filter( 'wpml_elementor_widgets_to_translate', [$this, 'add_translatable_nodes'] );
            }
        }
    }


    function load_wpml_modules()
    {
        require_once( UICORE_INCLUDES. '/elementor/compatibility/class-wpml-ui-highlighted-text.php');
    }

    function add_translatable_nodes( $nodes_to_translate )
    {
        $nodes_to_translate[ 'highlighted-text' ] = [
			'conditions' => [ 'widgetType' => 'highlighted-text' ],
			'fields'     => [],
			'integration-class' => '\UiCore\Elementor\WPML_UI_HighlightedText',
		];
        return $nodes_to_translate;
    }

    function bdt_lines_fix($section){
		$section->update_responsive_control(
			'ep_grid_line_output',
            [
				'selectors' => [
					'#uicore-page' => 'position: relative;',
					'#uicore-page::before' => '
									content: "";
									position: absolute;
									top: 0;
									right: 0;
									bottom: 0;
									left: 0;
									margin-right: auto;
									margin-left: auto;
									pointer-events: none;
									z-index: var(--ep-grid-line-z-index, 0);
									min-height: 100vh;

									width: calc(100% - (2 * 0px));
									max-width: var(--ep-grid-line-max-width, 100%);
									background-size: calc(100% + var(--ep-grid-line-width, 1px)) 100%;
									background-image: repeating-linear-gradient(var(--ep-grid-line-direction, 90deg), var(--ep-grid-line-column-color, transparent), var(--ep-grid-line-column-color, transparent) calc((100% / var(--ep-grid-line-columns, 12)) - var(--ep-grid-line-width, 1px)), var(--ep-grid-line-color, #eee) calc((100% / var(--ep-grid-line-columns, 12)) - var(--ep-grid-line-width, 1px)), var(--ep-grid-line-color, #eee) calc(100% / var(--ep-grid-line-columns, 12)));'

				],
            ]
        );
	}

    function override_mf_style_button_control( Controls_Stack $element, $section_id )
    {
        $element->update_responsive_control(
			'mf_btn_text_padding',
            [
                'default' => [
                    'top' => '',
                    'right' => '',
                    'bottom' => '',
                    'left' => '',
                    'unit' => 'px',
                ]
            ]
        );
        $element->update_responsive_control(
			'mf_btn_text_color',
            [
                'default' => ''
            ]
        );
        $element->update_responsive_control(
			'mf_btn_hover_color',
            [
                'default' => ''
            ]
        );
    }
    function override_mf_style_button_control_border( Controls_Stack $element, $section_id )
    {
        $element->update_responsive_control(
			'mf_btn_border_radius',
            [
                'default' => [
                    'top' => '',
                    'right' => '',
                    'bottom' => '',
                    'left' => '',
                    'unit' => 'px',
                ]
            ]
        );

    }
    function override_mf_style_upload_control( Controls_Stack $element, $section_id )
    {
        $element->update_control(
			'mf_input_color',
            [
                'default' => '',
            ]
        );
        $element->update_control(
			'mf_input_color_hover',
            [
                'default' => '',
            ]
        );
        $element->update_control(
			'mf_input_color_focus',
            [
                'default' => '',
            ]
        );
        $element->update_control(
			'mf_file_upload_file_name_color',
            [
                'default' => '',
            ]
        );
        $element->update_control(
			'mf_file_upload_file_name_hover_color',
            [
                'default' => '',
            ]
        );

    }
    public function override_ep_custom_carousel_marquee_render($content,Widget_Base $element){
        if($element->get_name() === 'bdt-custom-carousel'){
            $settings  = $element->get_settings_for_display();
            if(isset($settings['carousel_marquee']) && $settings['carousel_marquee'] === 'ui-is-marquee'){
                $auto = htmlspecialchars('"slidesPerView":"auto"');

                if($element->get_current_skin_id() === 'bdt-custom-content'){
                    $mobile = htmlspecialchars('"slidesPerView":'.(isset($settings["slides_per_view_mobile"]) ? (int)$settings["slides_per_view_mobile"] : 1) );
                    $tablet = htmlspecialchars('"slidesPerView":'.(isset($settings["slides_per_view_tablet"]) ? (int)$settings["slides_per_view_tablet"] : 2) );
                    $lg = htmlspecialchars('"slidesPerView":'.(isset($settings["slides_per_view"]) ? (int)$settings["slides_per_view"] : 3) );

                    $content = \str_replace([$mobile,$tablet,$lg],$auto,$content);
                }


                if(isset($settings['carousel_marquee_reverse']) && $settings['carousel_marquee_reverse'] === 'yes'){
                    $autoplay = htmlspecialchars('{"autoplay":{"delay":null}');
                    $autoplay_reverse = htmlspecialchars('{"autoplay":{"delay":null, "reverseDirection":true}');
                    $content = \str_replace($autoplay,$autoplay_reverse,$content);
                }
            }
        }
        return $content;
    }
    public function override_ep_custom_carousel_marquee_style(Controls_Stack $element, $section_id){
        $element->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'custom_content_typography',
				'label' => __( 'Typography', 'uicore-framework' ),
				'selector' => '{{WRAPPER}} .swiper-wrapper',
				'condition'=> [
					'_skin' => 'bdt-custom-content'
				],
			]
		);

        $control_data = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $element->get_unique_name(), 'skin_template_slides' );
        if ( is_wp_error( $control_data ) ) {
            return;
        }

        // Then you can access and modify the repeater fields as an array directly
        $control_data['fields']['editor_content']['type'] = Controls_Manager::WYSIWYG;

        // And then just update the control in the stack/widget
        $element->update_control( 'skin_template_slides', $control_data );

    }
    public function override_ep_custom_carousel_marquee(Controls_Stack $element, $section_id){

        $element->start_injection(
			[
				'type' => 'control',
				'at'   => 'after',
				'of'   => 'skin',
			] );
        $element->add_control(
			'carousel_marquee',
			[
				'label' => __('Enable Marquee', 'uicore-framework'),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'ui-is-marquee',
                'prefix_class'=> '',
                'render_type'  => 'template',
				'condition' => [
					'skin' => 'carousel'
				]
			]
		);
        $element->add_control(
			'carousel_marquee_reverse',
			[
				'label' => __('Reverse Marquee Direction', 'uicore-framework'),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
                'render_type'  => 'template',
				'condition' => [
					'skin' => 'carousel',
                    'carousel_marquee' => 'ui-is-marquee'
				]
			]
		);
        $element->end_injection();

        $element->update_control(
			'autoplay_speed',
            [
                'condition' => [
                    'autoplay' => 'yes',
					'carousel_marquee!' => 'ui-is-marquee'
				]
            ]
        );
        $element->update_responsive_control(
			'slides_per_view',
            [
                'conditions' => [
                    'relation' => 'or',
                    'terms' => [
                        [
                            'name' => 'carousel_marquee',
                            'operator' => '!=',
                            'value' => 'ui-is-marquee',
                        ],
                        [
                            'relation' => 'and',
                            'terms' => [
                                [
                                    'name' => 'carousel_marquee',
                                    'operator' => '===',
                                    'value' => 'ui-is-marquee',
                                ],
                                [
                                    'name' => '_skin',
                                    'operator' => '!=',
                                    'value' => 'bdt-custom-content',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
    public function override_ep_bg_control( Controls_Stack $element, $section_id )
    {
        $element->add_control(
			'nav_bar_background_color',
			[
				'label'     => __( 'Nav Background Color', 'uicore-framework' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-scrollnav ul' => 'background-color: {{VALUE}};',
				],
			]
		);
        $element->add_control(
            'nav_bar_filter',
            [
                'label' => _x( 'Background Blur', 'uicore-framework' ),
                'type' => Controls_Manager::SLIDER,

                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 25,
                        'step' => 0.1,
                    ],
                ],
                'selectors' => [
					'{{WRAPPER}} .bdt-scrollnav ul' => 'backdrop-filter: blur({{SIZE}}px);'
				],
            ]
        );
        $element->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'        => 'nav_bar_border',
				'label'       => __( 'Border', 'uicore-framework' ),
				'placeholder' => '1px',
				'default'     => '0',
				'selector'    => '{{WRAPPER}} .bdt-scrollnav ul',
			]
		);

		$element->add_responsive_control(
			'nav_bar_border_radius',
			[
				'label'      => __( 'Border Radius', 'uicore-framework' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bdt-scrollnav ul' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$element->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'nav_bar_shadow',
				'selector' => '{{WRAPPER}} .bdt-scrollnav ul',
			]
		);
        $element->update_control('nav_offset',
			array(
				'selectors' => [
					'{{WRAPPER}} .bdt-scrollnav > div' => 'margin: {{SIZE}}{{UNIT}};',
				],
			)
		);
    }

     /**
     * Change Theme stylle Button selector classes
     *
     * @param \Elementor\Controls_Stack $element
     * @param string $section_id
     * @return void
     * @author Andrei Voica <andrei@uicore.co>
     * @since 1.2.3
     */
	public function override_theme_style_container_width_control( Controls_Stack $element, $section_id ) {
        $element->update_responsive_control(
			'container_width',
			array(
				'selectors' => [
					'.uicore-section.uicore-box nav.uicore-container,
                    .uicore-section.uicore-box > .uicore-container, .uicore-ham-creative .uicore-navigation-content,
                    .container-width .uicore-megamenu > .elementor,
                    #wrapper-navbar.uicore-section.uicore-box .uicore-container .uicore-megamenu .uicore-section.uicore-box .uicore-container,
                    #wrapper-navbar.uicore-section.uicore-section-full_width .uicore-container .uicore-megamenu .uicore-section.uicore-box .uicore-container
                    ' => 'max-width: {{SIZE}}{{UNIT}}',
					'.e-container' => '--container-max-width: {{SIZE}}{{UNIT}}',
				],
			)
		);

	}

    static function get_buttons_class($state='default',$style_type='full'){
        //Deprecated fucntion
        return Helper::get_buttons_class($state,$style_type);
    }

    public function fix_ui_controls_experiment( Controls_Stack $element, $section_id ) {
        //add a filter for the option with name "elementor_experiment-e_optimized_control_loading"
        \add_filter('option_elementor_experiment-e_optimized_control_loading', function($val){
            return false;
        });

    }

    /**
     * Change Theme stylle Button selector classes
     *
     * @param \Elementor\Controls_Stack $element
     * @param string $section_id
     * @return void
     * @author Andrei Voica <andrei@uicore.co>
     * @since 1.2.3
     */
	public function override_theme_style_button_control( Controls_Stack $element, $section_id ) {
        $controls_manager = Plugin::$instance->controls_manager;
        $typographyGroup = $controls_manager->get_control_groups('typography');
        foreach ($typographyGroup->get_fields() as $field_key => $field) {
            $control_id = "button_typography_{$field_key}";
            $old_control_data = $controls_manager->get_control_from_stack($element->get_unique_name(), $control_id);
            if(\is_wp_error($old_control_data)){
                continue;
            }
            if($control_id != 'button_typography_font_size'){
                $element->update_control($control_id, [
                    'selectors'  => [
                        $this->get_buttons_class() => isset($old_control_data['selector_value']) ? $old_control_data['selector_value'] : reset($old_control_data['selectors']),
                    ]
                ]);
            }else{
               $element->update_responsive_control(
                    'button_typography_font_size',
                    array(
                        'selectors' => array(
                            $this->get_buttons_class() => 'font-size: {{SIZE}}{{UNIT}};',
                        ),
                    )
                );
            }
        }

        $element->update_control(
			'button_text_color',
			array(
				'selectors' => array(
					$this->get_buttons_class() => 'color: {{VALUE}};',
				),
			)
		);

		$element->update_control(
			'button_background_color',
			array(
				'selectors' => array(
					$this->get_buttons_class() => 'background-color: {{VALUE}};',
				),
			)
		);
        $element->update_control(
            'button_box_shadow',
            array(
                'selector' => $this->get_buttons_class()
            )
        );
        $element->update_control(
            'button_border',
            array(
                'selector' => $this->get_buttons_class()
            )
        );
        $typographyGroup = $controls_manager->get_control_groups('border');

        foreach ($typographyGroup->get_fields() as $field_key => $field) {
            $control_id = "button_border_{$field_key}";
            $old_control_data = $controls_manager->get_control_from_stack($element->get_unique_name(), $control_id);
            if(\is_wp_error($old_control_data)){
                continue;
            }
            $element->update_control($control_id, [
                'selectors'  => [
                    $this->get_buttons_class() => reset($old_control_data['selectors']),
                ]
            ]);
        }
		$border_radius_class = $this->get_buttons_class() . ', .quantity input, .coupon input';
        $element->update_control(
			'button_border_radius',
			array(
				'selectors' => array(
					$border_radius_class => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
        $element->update_control(
			'button_hover_text_color',
			array(
				'selectors' => array(
					$this->get_buttons_class('hover') => 'color: {{VALUE}};',
				),
			)
		);
		$element->update_control(
			'button_hover_background_color',
			array(
				'selectors' => array(
					$this->get_buttons_class('hover') => 'background-color: {{VALUE}};',
				),
			)
		);
        $element->update_control(
            'button_hover_box_shadow',
            array(
                'selector' => $this->get_buttons_class('hover')
            )
        );
        $element->update_control(
            'button_hover_border',
            array(
                'selector' => $this->get_buttons_class('hover')
            )
        );
        $element->update_control(
			'button_hover_border_radius',
			array(
				'selectors' => array(
					$this->get_buttons_class('hover') => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
        $typographyGroup = $controls_manager->get_control_groups('border');

        foreach ($typographyGroup->get_fields() as $field_key => $field) {
            $control_id = "button_hover_border_{$field_key}";
            $old_control_data = $controls_manager->get_control_from_stack($element->get_unique_name(), $control_id);
            if(\is_wp_error($old_control_data)){
                continue;
            }
            $element->update_control($control_id, [
                'selectors'  => [
                    $this->get_buttons_class('hover') => reset($old_control_data['selectors']),
                ]
            ]);
        }
        $element->update_responsive_control(
			'button_padding',
			array(
				'selectors' => array(
					$this->get_buttons_class('default','no_padding').', .bdt-contact-form button.elementor-button.bdt-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

	}

    public static function update_globals_from_elementor($check, $object_id, $meta_key, $value, $prev_value)
    {
        $kit_id = get_option('elementor_active_kit');
        if ($object_id == $kit_id && $meta_key == '_elementor_page_settings') {
            //settings prefix
            $current_settings = Settings::current_settings();

            $is_uicore = \apply_filters('ui_is_theme_options_save',false);
            $the_filter = current_filter();

            //Global colors
            $global_colors = [
                [
                    'option' => 'pColor',
                    'id' => 'uicore_primary',
                    'name' => 'Primary',
                ],
                [
                    'option' => 'sColor',
                    'id' => 'uicore_secondary',
                    'name' => 'Secondary',
                ],
                [
                    'option' => 'aColor',
                    'id' => 'uicore_accent',
                    'name' => 'Accent',
                ],
                [
                    'option' => 'hColor',
                    'id' => 'uicore_headline',
                    'name' => 'Headline',
                ],
                [
                    'option' => 'bColor',
                    'id' => 'uicore_body',
                    'name' => 'Body',
                ],
                [
                    'option' => 'dColor',
                    'id' => 'uicore_dark',
                    'name' => 'Dark Neutral',
                ],
                [
                    'option' => 'lColor',
                    'id' => 'uicore_light',
                    'name' => 'Light Neutral',
                ],
                [
                    'option' => 'wColor',
                    'id' => 'uicore_white',
                    'name' => 'White',
                ],
            ];

            foreach ($global_colors as $id => $color) {
                //let's first check if they are uicore_globals else ovewride them
                if (!$is_uicore) {
                    //is not uicore than we need to update uicore
                    $to_set = $value['system_colors'][$id]['color'];
                    $current_settings[$color['option']] = $to_set;
                } else {
                    //is uicore than we need to update Elementor
                    $value['system_colors'][$id]['color'] = $current_settings[$color['option']];
                    $value['system_colors'][$id]['_id'] = $color['id'];
                    $value['system_colors'][$id]['name'] = $color['name'];
                }
            }

            if ($is_uicore) {
                $value['custom_colors'] = [];
                foreach ($current_settings['custom_colors'] as $index => $custom_color) {
                    $value['custom_colors'][$index] = [
                        '_id' => $custom_color['id'],
                        'name' => $custom_color['label'],
                        'color' => $custom_color['value'],
                    ];
                }
                $value['custom_typography'] = [];
            }else{
                $current_settings['custom_colors'] = [];
                foreach ($value['custom_colors'] as $index => $custom_color) {
                    $current_settings['custom_colors'][$index] = [
                        'label' => $custom_color['title'],
                        'value' => $custom_color['color'],
                        'id'    => $custom_color['_id'],
                    ];
                }
                // $current_settings['custom_typography'] = [];
                // foreach ($value['custom_typography'] as $index => $custom_typography) {
                //     $current_settings['custom_typography'][$index] = [
                //         'label' => $custom_typography['title'],
                //         'id'    => $custom_typography['_id'],
                //         'value' => [
                //             'f'  => $custom_typography['typography_font_family'],
                //             'st' => ($custom_typography['typography_font_weight'] === 'normal') ? 'regular' : $custom_typography['typography_font_weight'],
                //             't'  => isset($custom_typography['typography_text_transform']) ? $custom_typography['typography_text_transform'] : '',
                //             's'  => [
                //                 'd' => [
                //                     'unit'  => $custom_typography['typography_font_size']['unit'],
                //                     'value' => $custom_typography['typography_font_size']['size'],
                //                 ],
                //                 't' => [
                //                     'unit'  => $custom_typography['typography_font_size_tablet']['unit'],
                //                     'value' => $custom_typography['typography_font_size_tablet']['size'],
                //                 ],
                //                 'm' => [
                //                     'unit'  => $custom_typography['typography_font_size_mobile']['unit'],
                //                     'value' => $custom_typography['typography_font_size_mobile']['size'],
                //                 ],
                //             ],
                //             'h'  => [
                //                 'd' => [
                //                     'unit'  => $custom_typography['typography_line_height']['unit'],
                //                     'value' => $custom_typography['typography_line_height']['size'],
                //                 ],
                //                 't' => [
                //                     'unit'  => $custom_typography['typography_line_height_tablet']['unit'],
                //                     'value' => $custom_typography['typography_line_height_tablet']['size'],
                //                 ],
                //                 'm' => [
                //                     'unit'  => $custom_typography['typography_line_height_mobile']['unit'],
                //                     'value' => $custom_typography['typography_line_height_mobile']['size'],
                //                 ],
                //             ],
                //             'ls' => [
                //                 'd' => [
                //                     'unit'  => $custom_typography['typography_letter_spacing']['unit'],
                //                     'value' => $custom_typography['typography_letter_spacing']['size'],
                //                 ],
                //                 't' => [
                //                     'unit'  => $custom_typography['typography_letter_spacing_tablet']['unit'],
                //                     'value' => $custom_typography['typography_letter_spacing_tablet']['size'],
                //                 ],
                //                 'm' => [
                //                     'unit'  => $custom_typography['typography_letter_spacing_mobile']['unit'],
                //                     'value' => $custom_typography['typography_letter_spacing_mobile']['size'],
                //                 ],
                //             ],
                //         ],
                //     ];
                
                // }
            }

            


            //Global Fonts
            $global_fonts = [
                [
                    'option' => 'pFont',
                    'id' => 'uicore_primary',
                    'name' => 'Primary',
                ],
                [
                    'option' => 'sFont',
                    'id' => 'uicore_secondary',
                    'name' => 'Secondary',
                ],
                [
                    'option' => 'tFont',
                    'id' => 'uicore_text',
                    'name' => 'Text',
                ],
                [
                    'option' => 'aFont',
                    'id' => 'uicore_accent',
                    'name' => 'Accent',
                ],
            ];
            foreach ($global_fonts as $id => $font) {
                //let's first check if they are uicore_globals else ovewride them
                if (!$is_uicore) {
                    $to_set = [
                        'f' => $value['system_typography'][$id]['typography_font_family'],
                        'st' => $value['system_typography'][$id]['typography_font_weight'],
                    ];
                    $current_settings[$font['option']] = $to_set;
                } else {
                    $value['system_typography'][$id] = [
                        '_id' => $font['id'],
                        'title' => $font['name'],
                        'typography_font_family' => $current_settings[$font['option']]['f'],
                        'typography_font_weight' => $current_settings[$font['option']]['st'],
                        'typography_typography' => 'custom',
                    ];
                }
            }

            //Buttons are not handled in both ways vbeacause we are forceing to use only UiCore Impl.
            // Settings::update_globals_from_uicore()

            if (!$is_uicore) {
                self::uicore_meta_trick($the_filter, $object_id, $meta_key, $value, $prev_value);
                $check = $value;

                //Update the db
                $new_settings = ThemeOptions::update_all($current_settings,0);

            }



        } elseif ($object_id == $kit_id && $meta_key == '_elementor_css') {
            $elementor_settings = get_post_meta($kit_id, '_elementor_page_settings', true);

            if (!$elementor_settings) {
                Settings::update_globals_from_uicore(null);
            }
        }

        return $check;
    }

    static function uicore_meta_trick(
        $filter,
        $object_id,
        $meta_key,
        $meta_value,
        $unique_or_prev_value,
        $old_value = null
    ) {

        // Remove the filters and save the new meta value. Make sure that
        // the priority and number of arguments are exactly the same as
        // when you added the filters.
        remove_filter('add_post_metadata', ['\UiCore\Elementor\Core', 'update_globals_from_elementor'], 20, 5);
        remove_filter('update_post_metadata', ['\UiCore\Elementor\Core', 'update_globals_from_elementor'], 20, 5);

        // Manually save the meta data.
        if ('add_post_metadata' === $filter) {
            add_metadata('post', $object_id, $meta_key, $meta_value, $unique_or_prev_value);
        } elseif ('update_post_metadata' === $filter) {
            update_metadata('post', $object_id, $meta_key, $meta_value, $unique_or_prev_value);
        }
        // // Finally, re-add the filters.
        // add_filter('add_post_metadata', ['\UiCore\Elementor\Core', 'update_globals_from_elementor'], 20, 5);
        // add_filter('update_post_metadata', ['\UiCore\Elementor\Core', 'update_globals_from_elementor'], 20, 5);

        //just to be sure
        // \Elementor\Plugin::$instance->files_manager->clear_cache();
        // Settings::clear_cache();

    }

    /**
     * Add Support For custom location used in Theme Builder
     *
     * @param [type] $elementor_theme_manager
     * @return void
     * @author Andrei Voica <andrei@uicore.co>
     * @since 1.2.0
     */
    function elementor_locations($elementor_theme_manager)
    {
        $elementor_theme_manager->register_all_core_location();
    }

    /**
     * Add new google fonts to elementor
     *
     * @param [type] $old
     * @return void
     * @author Andrei Voica <andrei@uicore.co>
     * @since 1.2.0
     */
    function new_google_fonts($old)
    {
		$new = [
			"Anek Latin"        =>  'googlefonts',
            "Host Grotesk"      =>  'googlefonts',
			"Instrument Serif"  =>  'googlefonts',
			"Instrument Sans"   =>  'googlefonts',
			"Plus Jakarta Sans" =>  'googlefonts',
			"Space Grotesk"     =>  'googlefonts',
			"Jost"              =>  'googlefonts',
			"Albert Sans"       =>  'googlefonts',
			"Crimson Text"      =>  'googlefonts',
			"League Spartan"    =>  'googlefonts',
			"Bricolage Grotesque"    =>  'googlefonts',
            "Geist"             =>  'googlefonts',
		];
		return array_merge($old, $new);
    }

    public function register_fonts_groups( $font_groups )
    {
		$new_groups = [
            self::CUSTOM =>__( 'UiCore Custom', 'uicore-framework' ),
            self::TYPEKIT =>__( 'UiCore Typekit', 'uicore-framework' ),
        ];
		return array_merge( $new_groups, $font_groups );
	}


    public function register_fonts_in_control( $font_groups )
    {
        $uicore_custom = Data::get_custom_fonts('simple',self::CUSTOM);
        $uicore_typekit = Data::get_typekit_fonts('simple',self::TYPEKIT);

        $new_groups = array_merge($uicore_custom, $uicore_typekit);

		return array_merge( $new_groups, $font_groups );
    }



    function add_custom_icons($tabs = [])
    {
        include UICORE_INCLUDES . '/elementor/generic/icons.php';

        $tabs['uicore-icons'] = [
            'name' => 'uicore-icons',
            'label' => __('Themify Icons', 'uicore-framework'),
            'url' => UICORE_ASSETS . '/fonts/themify/themify-icons.css',
            'enqueue' => [UICORE_ASSETS . '/fonts/themify/themify-icons.css'],
            'prefix' => 'ti-',
            'displayPrefix' => 'ti',
            'labelIcon' => 'ti ti-themify-logo',
            'ver' => '1.0.0',
            'icons' => $icons //$icons from icons.php
        ];

        $tabs['uicore-remix-icons'] = [
            'name' => 'uicore-remix-icons',
            'label' => __('Remix Icons', 'uicore-framework'),
            'url' => UICORE_ASSETS . '/fonts/remix/remix-icons.css',
            'enqueue' => [UICORE_ASSETS . '/fonts/remix/remix-icons.css'],
            'prefix' => 'ri-',
            'displayPrefix' => 'ri',
            'labelIcon' => 'ri ri-remixicon-fill',
            'ver' => '4.6.0',
            'icons' => $remix_icons
        ];

        $tabs['uicore-iconic-icons'] = [
            'name' => 'uicore-iconic-icons',
            'label' => __('Iconic Icons', 'uicore-framework'),
            'url' => UICORE_ASSETS . '/fonts/iconic/iconic-icons.css',
            'enqueue' => [UICORE_ASSETS . '/fonts/iconic/iconic-icons.css'],
            'prefix' => 'ii-',
            'displayPrefix' => 'ii',
            'labelIcon' => 'fas fa-circle', // very similar to iconic logo
            'ver' => '1.0.0',
           'icons' => $iconic_icons
        ];

        $tabs['uicore-lucide-icons'] = [
            'name' => 'uicore-lucide-icons',
            'label' => __('Lucide Icons', 'uicore-framework'),
            'url' => UICORE_ASSETS . '/fonts/lucide/lucide-icons.css',
            'enqueue' => [UICORE_ASSETS . '/fonts/lucide/lucide-icons.css'],
            'prefix' => 'lu-',
            'displayPrefix' => 'lu',
            'labelIcon' => 'fas fa-fingerprint', // the closest we could get to lucide logo
            'ver' => '1.0.0',
           'icons' => $lucide_icons
        ];

        return $tabs;
    }

    /**
     * Get the basic styles
     *
     * @return string
     * @author Lucas Marini <lucas@uicore.co>
     * @since [currentVersion]
     */
    function get_general_styles()
    {
        return '
            #elementor-panel-get-pro-elements-sticky{
                display:none;
            }
            #wrapper-navbar{
                pointer-events:none;
            }
            body #elementor .animated.zoomIn{
                animation-fill-mode:forwards!important;
            }

            .metform-template-item--pro {
                display: none;
            }
			.ui-e-badge {
				height: 16px;
	            width: 16px;
				background-size: 9px;
				display: inline-block;
				vertical-align: middle;
    			margin: -2px 5px 0 0;
			}
            .uicore-tag {
              position: absolute;
              top: 7px;
              right: 7px;
              background: #532df5;
              border-radius: 4.5px;
              color: #fff;
              font-size: 10px;
              line-height: 13px;
              font-weight: 600;
              padding: 4px 6px;
              letter-spacing: .4px;
              text-transform: uppercase;
              -webkit-font-smoothing: antialiased;
            }
            .uicore-green {
              background: #1eaa69;
            }
            .uicore-red {
              background: #dc4545;
            }
            .elementor-panel-menu-item-theme-style-typography,
            .elementor-panel-menu-item-theme-style-buttons,
            .uicore-hide {
                display: none!important;
            }
            .elementor-element .icon .ui-e-widget:after {
				content: "";
			    position: absolute;
			    right: 5px;
			    top: 5px;
			    margin-right: 0;
			    width: 16px;
			    height: 16px;
			    background-size: 9px;
			    background-color: #656c7196;
				transition: background-color .3s ease-in-out;
            }
			.elementor-element:hover .icon .ui-e-widget:after {
				background-color: #532df5;
				transition: background-color .3s ease-in-out;
			}
            #elementor-panel-category-uicore-woo{
                order: -1;
            }
        ';
    }

    /**
     * Get the Uicore Library styles
     *
     * @return string
     * @author Lucas Marini <lucas@uicore.co>
     * @since [currentVersion]
     */
    function get_library_styles()
    {
        return '
            .uicore-template-library-templates-container{
                margin-left: -15px;
                margin-right: -15px;
                box-shadow: none!important;
            }
            .uicore-lib-templates .elementor-template-library-template-page img{
                width:100%;
                height: 239px;
            }
            .uicore-lib-templates .elementor-template-library-template-page .elementor-template-library-template-body {
                height: 239px;
            }
            .uicore-lib-dialog{
                transform: translate3d(-50%, -50%, 0);
                left: 50%;
                top: 50%;
            }
            .uicore-library-logo,
            .ui-e-badge{
                height: 28px;
                width: 28px;
                margin-right: 10px;
                border-radius: 3px;
                background-color: #532df5;
                background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 16 16\' xml:space=\'preserve\'%3E%3Cpath d=\'M5.383 15.217c3.1 0 5.4-2.3 5.4-5.3v-7.9h-2.9v7.9c0 1.4-1.1 2.5-2.5 2.5s-2.5-1.1-2.5-2.5v-7.9h-2.9v7.9c0 3 2.3 5.3 5.4 5.3zM14.283 4.117c1 0 1.7-.7 1.7-1.7s-.7-1.7-1.7-1.7-1.7.7-1.7 1.7.7 1.7 1.7 1.7zM15.683 15.017v-9.6h-2.8v9.6z\' fill=\'%23fff\'/%3E%3C/svg%3E");
                background-size: 16px;
                background-position: center;
                background-repeat: no-repeat;
            }
            .elementor-element .icon .ui-e-widget:after,
            .uicore-library-logo,
            .ui-e-badge {
                border-radius: 3px;
                background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 16 16\' xml:space=\'preserve\'%3E%3Cpath d=\'M5.383 15.217c3.1 0 5.4-2.3 5.4-5.3v-7.9h-2.9v7.9c0 1.4-1.1 2.5-2.5 2.5s-2.5-1.1-2.5-2.5v-7.9h-2.9v7.9c0 3 2.3 5.3 5.4 5.3zM14.283 4.117c1 0 1.7-.7 1.7-1.7s-.7-1.7-1.7-1.7-1.7.7-1.7 1.7.7 1.7 1.7 1.7zM15.683 15.017v-9.6h-2.8v9.6z\' fill=\'%23fff\'/%3E%3C/svg%3E");
                background-position: center;
                background-repeat: no-repeat;
            }

            .uicore-lib-templates .uicore-library-logo{
                width: 70px;
                height: 70px;
                display: inline-block;
                background-size: 40px;
                border-radius: 6px;
                margin: 0;
            }
            .elementor-template-library-template-remote{
                transition:opacity .3s ease-in;
            }
			.uicore-lib-templates .elementor-button-success {
				width: 335px;
			}
			.uicore-lib-templates .uico-error {
				flex-direction: column;
    			align-items: center;
			}
			.uicore-lib-templates .uico-error .register-url {
				height: 55px;
				display: block;
				line-height: 55px;
				font-size: 16px;
				margin-top: 20px;
			}
        ';
    }

    /**
     * Elementor Editor Style, Fonts and Scripts
     *
     * @return void
     * @author Andrei Voica <andrei@uicore.co
     * @since 1.0.0
     */
    public function elementor_style()
    {
        add_action('elementor/editor/before_enqueue_scripts', function () {

            $settings = Settings::current_settings();
            $styles = $this->get_general_styles();


            if( $settings['disable_library'] == 'false' ){
                $styles .= $this->get_library_styles();
            }

            echo '<style id="uicore-csss">' . $styles. '</style>';

            $default = [];
            $purchase = Helper::handle_connect('get');
            if($purchase['token'] === '' && !apply_filters('uicore_is_sandbox',false)){
                $default['purchase'] = 'License';
            }

            $default['blocks'] = 'Blocks';

            switch (\get_post_type()) {
                case 'uicore-tb':
                    $type = wp_get_post_terms(\get_the_ID(), 'tb_type', ['fields' => 'names']);
                    $current_type = $type[0];
                    $type_name = ThemeBuilder\Admin::get_tb_types()[$current_type];
                    break;

                case 'portfolio':
                    $current_type = 'portfolio';
                    $portfolio_config = Portfolio\Common::get_portfolio_display_name();
                    $type_name = $portfolio_config['name'];
                    break;

                default:
                    $current_type = 'pages';
                    $type_name = 'Pages';
                    break;
            }

            // Following scripts required only if Uicore Library is enabled
            if ( $settings['disable_library'] == 'true' ){
                return;
            }

            echo '
            <script>
            var ui_theme_name = "'.(defined('UICORE_THEME_NAME') ? UICORE_THEME_NAME : UICORE_NAME).'";
            var purchase_data = '.\json_encode($purchase).';
            var uicore_data = {
                "v": "' . UICORE_VERSION . '",
                "root": "' . get_site_url() . '",
                "wp_json":"' . get_rest_url(null, 'uicore/v1') . '",
                "nonce":"' . wp_create_nonce('wp_rest') . '",
                "api": "'.UICORE_API.'",
                "messages" : '. json_encode([
                    'api' => [
                        'status' => 'importing',
                        'message' => __('Downloading data from API', 'uicore-framework')
                    ],
                    'assets' => [
                        'status' => 'importing',
                        'message' => __('Downloading assets', 'uicore-framework')
                    ],
                    'template' => [
                        'status' => 'importing',
                        'message' => __('Importing template', 'uicore-framework')
                    ]
                ]) .'
            }
            var uicore_blocks = ' .
                json_encode(Data::get_library('blocks')) .
                ';
                ';
            if($current_type != '_type_block'){
                $default[$current_type] = $type_name;
                echo ' var uicore_extra = ' .
                json_encode(Data::get_library($current_type)) .
                ';';
            }
            echo '
            var uicore_frontend_data = ' .
                json_encode($settings) .
                ';
            var uicore_default = '.json_encode($default).';
            var path = "' . UICORE_NAME . '";
            </script>
            ';

            $prefix = (( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) || defined('UICORE_LOCAL')) ? '' : '.min';

            // wp_enqueue_script(
            //     'uicore-library-manifest',
            //     UICORE_ASSETS . '/js/manifest' . $prefix . '.js',
            //     ['jquery'],
            //     filemtime(UICORE_PATH . '/assets/js/manifest' . $prefix . '.js'),
            //     true
            // );
            wp_enqueue_script(
                'uicore-library-vendor',
                UICORE_ASSETS . '/js/vendor' . $prefix . '.js',
                ['jquery'],
                filemtime(UICORE_PATH . '/assets/js/vendor' . $prefix . '.js'),
                true
            );
            wp_enqueue_script(
                'uicore-library',
                UICORE_ASSETS . '/js/library' . $prefix . '.js',
                ['jquery'],
                filemtime(UICORE_PATH . '/assets/js/library' . $prefix . '.js'),
                true
            );

            // wp_add_inline_script('uicore-vendor', 'var uicore_frontend_data = ' . json_encode(Data::get_frontend_data()), 'before');
        });
        add_action('elementor/frontend/after_enqueue_styles', function () {
            $google_fonts = get_option('uicore_fonts');
            //If Google font url is setted add it to registred style
            if ($google_fonts) {
                wp_enqueue_style('uicore_fonts', $google_fonts);
            }
        });
    }
}
new Core();
