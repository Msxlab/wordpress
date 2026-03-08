<?php
namespace UiCore\ThemeBuilder\Widgets;

use Uicore\Elementor\TB_Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

defined('ABSPATH') || exit();

/**
 * Sale Badge Widget
 *
 * @author Lucas Marini <lucas@uicore.co>
 * @since 6.0.0
 */
class Sale_Badge extends TB_Widget_Base {

    public function get_name() {
		return 'uicore-woo-sale-badge';
	}
	public function get_title() {
		return esc_html__( 'Sale Badge', 'uicore-framework' );
	}
	public function get_icon() {
		return 'eicon-posts-ticker ui-e-widget';
	}
	public function get_categories() {
		return ['uicore-woo', 'uicore-theme-builder'];
	}
	public function get_keywords() {
		return [ 'woocommerce', 'shop', 'store', 'product', 'badge', 'sale', 'promotion'];
	}
    public function get_styles() {
        return [];
	}
    public function get_scripts(){
        return [];
    }

	protected function register_controls() {
        if ( $this->no_woo_fallback(true) ) {
            return;
        }

         $this->start_controls_section(
			'section_product_content',
			[
				'label' => esc_html__( 'Content', 'uicore-framework' ),
			]
		);
            $this->register_post_list('product');

            $this->add_control(
                'badge_info',
                [
                    'type' => Controls_Manager::NOTICE,
                    'dismissible' => true,
                    'content' => esc_html__('This widget will only be rendered if inside a product context and, of course, if the product is on sale.', 'uicore-framework' ),
                ]
            );

            $this->add_control(
                'badge_text',
                [
                    'label' => esc_html__( 'Text', 'uicore-framework' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'Sale!', 'uicore-framework' ),
                    'dynamic' => [
                        'active' => true,
                    ],
                ]
            );

        $this->end_controls_section();

		$this->start_controls_section(
			'section_sale_badge',
			[
				'label' => esc_html__( 'Badge', 'uicore-framework' ),
                'tab' => Controls_Manager::TAB_STYLE,
			]
		);
            $this->add_control(
                'badge_color',
                [
                    'label' => esc_html__( 'Text Color', 'uicore-framework' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .onsale' => 'color: {{VALUE}}',
                    ],
                ]
            );

            $this->add_control(
                'badge_backgroubnd',
                [
                    'label' => esc_html__( 'Background Color', 'uicore-framework' ),
                    'type' => Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .onsale' => 'background-color: {{VALUE}}',
                    ],
                ]
            );

            $this->add_responsive_control(
                'badge_padding',
                [
                    'label' => esc_html__( 'Padding', 'uicore-framework' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .onsale' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                    'name' => 'badge_border',
                    'selector' => '{{WRAPPER}} .onsale',
                ]
            );

            $this->add_responsive_control(
                'badge_radius',
                [
                    'label' => esc_html__( 'Border Radius', 'uicore-framework' ),
                    'type' => Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', 'em', '%' ],
                    'selectors' => [
                        '{{WRAPPER}} .onsale' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'badge_typography',
                    'selector' => '{{WRAPPER}} .onsale',
                ]
            );

            $this->add_control(
                'previous_advanced_position',
                [
                    'label' => esc_html__( 'Offset', 'uicore-elements' ),
                    'type' => Controls_Manager::POPOVER_TOGGLE,
                    'label_off' => esc_html__( 'Default', 'uicore-elements' ),
                    'label_on' => esc_html__( 'Custom', 'uicore-elements' ),
                    'return_value' => 'yes',
                ]
            );

            $this->start_popover();

                $this->add_responsive_control(
                    'h_offset',
                    [
                        'label' => esc_html__( 'Horizontal Offset', 'uicore-elements' ),
                        'type' => Controls_Manager::SLIDER,
                        'size_units' => [ 'px', '%',],
                        'range' => [
                            'px' => [
                                'min' => -200,
                                'max' => 200,
                                'step' => 5,
                            ],
                            '%' => [
                                'min' => -100,
                                'max' => 100,
                            ],
                        ],
                        'default' => [
                            'size' => 10,
                            'unit' => 'px',
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .onsale' => 'left: {{SIZE}}{{UNIT}};',
                        ],
                    ]
                );
                $this->add_responsive_control(
                    'v_offset',
                    [
                        'label' => esc_html__( 'Vertical Offset', 'uicore-elements' ),
                        'type' => Controls_Manager::SLIDER,
                        'size_units' => [ 'px', '%',],
                        'range' => [
                            'px' => [
                                'min' => -200,
                                'max' => 200,
                                'step' => 5,
                            ],
                            '%' => [
                                'min' => -100,
                                'max' => 100,
                            ],
                        ],
                        'default' => [
                            'size' => 10,
                            'unit' => 'px',
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .onsale' => 'top: {{SIZE}}{{UNIT}};',
                        ],
                    ]
                );

			$this->end_popover();

		$this->end_controls_section();
	}

	protected function render() {
        if ( $this->no_woo_fallback() ) {
            return;
        }

        $product = $this->get_product_data();
        if (!$product) return;

        // Editor rendering
        if( $this->is_edit_mode() ) {
            $this->render_woo_wrapper();
            $this->render_badge();
            $this->render_woo_wrapper(true);

        // Frontend rendering
        } else if( $product && $product->is_on_sale() ) {
            $this->render_badge();
        }
	}

    protected function render_badge(){
        $text = $this->get_settings_for_display('badge_text');
        ?>
            <span class="onsale"> <?php echo $this->esc_string($text);?> </span>
        <?php
    }

	protected function content_template() {}
}
\Elementor\Plugin::instance()->widgets_manager->register(new Sale_Badge());
