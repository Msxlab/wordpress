<?php

namespace ElementPack\Modules\ContentSwitcher\Widgets;

use ElementPack\Base\Module_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Icons_Manager;
use Elementor\Repeater;

use ElementPack\Element_Pack_Loader;
use ElementPack\Includes\Controls\SelectInput\Dynamic_Select;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Content_Switcher extends Module_Base {

	public function get_name() {
		return 'bdt-content-switcher';
	}

	public function get_title() {
		return BDTEP . esc_html__('Content Switcher', 'bdthemes-element-pack');
	}

	public function get_icon() {
		return 'bdt-wi-content-switcher';
	}

	public function get_categories() {
		return ['element-pack'];
	}

	public function get_keywords() {
		return ['switcher', 'tab', 'toggle', 'content', 'switch', 'switcher', 'content switcher', 'element pack'];
	}

	public function get_style_depends() {
		if ($this->ep_is_edit_mode()) {
			return ['ep-styles'];
		} else {
			return ['ep-content-switcher'];
		}
	}

	public function get_script_depends() {
		if ($this->ep_is_edit_mode()) {
			// return ['ep-scripts'];
			return ['ep-content-switcher'];
		} else {
			return ['ep-content-switcher'];
		}
	}

	public function get_custom_help_url() {
		return 'https://youtu.be/4NjUGf9EY0U';
	}

	public function has_widget_inner_wrapper(): bool {
        return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
    }
	
	protected function get_upsale_data(): array {
		return [
			'condition' => ! is_ep_pro(),
			'image' => esc_url( BDTEP_ASSETS_URL . 'images/go-pro.svg' ),
			'image_alt' => esc_attr__( 'Upgrade', 'bdthemes-element-pack' ),
			'title' => esc_html__( 'Unlock Premium Features', 'bdthemes-element-pack' ),
			'description' => sprintf(__( '<ul class="bdt-widget-promotion-list"><li>%1$s</li><li>%2$s</li></ul> These features are available only in Element Pack Pro.', 'bdthemes-element-pack' ), 'Style -> Show Multiple Switches', 'Content Type -> Elementor Template, Link Section, Link Widget'),
			'upgrade_url' => esc_url( 'https://www.elementpack.pro/pricing/?utm_source=widget_panel&utm_medium=ep_widget_panel' ),
			'upgrade_text' => sprintf(__( '<span class="bdt-widget-promotion-btn">%s</span>', 'bdthemes-element-pack' ), 'Upgrade to Pro'),
		];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_switcher_layout',
			[
				'label' => esc_html__('Switcher', 'bdthemes-element-pack'),
			]
		);

		$this->add_control(
			'switcher_style',
			[
				'label'   => esc_html__('Style', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'      => esc_html__('Classic Toggle', 'bdthemes-element-pack'),
					'2'      => esc_html__('Rectangle Switch', 'bdthemes-element-pack'),
					'3'      => esc_html__('Round Slide', 'bdthemes-element-pack'),
					'4'      => esc_html__('Flat Modern', 'bdthemes-element-pack'),
					'5'      => esc_html__('Sleek Slider', 'bdthemes-element-pack'),
					'6'      => esc_html__('Diamond Toggle', 'bdthemes-element-pack'),
					'7'      => esc_html__('Clean Circle', 'bdthemes-element-pack'),
					'8'      => esc_html__('Soft Curve', 'bdthemes-element-pack'),
					'9'      => esc_html__('Square Rotate', 'bdthemes-element-pack'),
					'button' => esc_html__('Show all switches', 'bdthemes-element-pack') . BDTEP_LOCK,
				],
				'classes' => BDTEP_LOCK_CLASS,
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__('Title', 'bdthemes-element-pack'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Switcher Title', 'bdthemes-element-pack'),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'switcher_icon',
			[
				'label'       => esc_html__('Icon', 'bdthemes-element-pack'),
				'type'        => Controls_Manager::ICONS,
				'label_block' => false,
				'skin'        => 'inline',
			]
		);

		$repeater->add_control(
			'switcher_active',
			[
				'label'        => esc_html__('Active', 'bdthemes-element-pack'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'bdthemes-element-pack'),
				'label_off'    => esc_html__('No', 'bdthemes-element-pack'),
				'return_value' => 'yes',
			]
		);

		$repeater->add_control(
			'content_type',
			[
				'label'   => esc_html__('Content Type', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'content',
				'options' => [
					'content' => esc_html__('Content', 'bdthemes-element-pack'),
					'template' => esc_html__('Elementor Template', 'bdthemes-element-pack') . BDTEP_LOCK,
					'link_section'  => esc_html__('Link Section', 'bdthemes-element-pack') . BDTEP_LOCK,
					'link_widget'  => esc_html__('Link Widget', 'bdthemes-element-pack') . BDTEP_LOCK,
					'price_card'  => esc_html__('Price Card', 'bdthemes-element-pack') . BDTEP_LOCK,
				],
				'classes' => BDTEP_LOCK_CLASS,
			]
		);

		$repeater->add_control(
			'price_card_content',
			[
				'label'       => esc_html__('Price Title', 'bdthemes-element-pack'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Starting From', 'bdthemes-element-pack'),
				'label_block' => true,
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);
		$repeater->add_control(
			'currency_symbol',
			[ 
				'label'   => __( 'Currency Symbol', 'bdthemes-element-pack' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [ 
					''             => __( 'None', 'bdthemes-element-pack' ),
					'dollar'       => '&#36; ' . _x( 'Dollar', 'Currency Symbol', 'bdthemes-element-pack' ),
					'euro'         => '&#128; ' . _x( 'Euro', 'Currency Symbol', 'bdthemes-element-pack' ),
					'baht'         => '&#3647; ' . _x( 'Baht', 'Currency Symbol', 'bdthemes-element-pack' ),
					'franc'        => '&#8355; ' . _x( 'Franc', 'Currency Symbol', 'bdthemes-element-pack' ),
					'guilder'      => '&fnof; ' . _x( 'Guilder', 'Currency Symbol', 'bdthemes-element-pack' ),
					'krona'        => 'kr ' . _x( 'Krona', 'Currency Symbol', 'bdthemes-element-pack' ),
					'lira'         => '&#8356; ' . _x( 'Lira', 'Currency Symbol', 'bdthemes-element-pack' ),
					'peseta'       => '&#8359 ' . _x( 'Peseta', 'Currency Symbol', 'bdthemes-element-pack' ),
					'peso'         => '&#8369; ' . _x( 'Peso', 'Currency Symbol', 'bdthemes-element-pack' ),
					'pound'        => '&#163; ' . _x( 'Pound Sterling', 'Currency Symbol', 'bdthemes-element-pack' ),
					'real'         => 'R$ ' . _x( 'Real', 'Currency Symbol', 'bdthemes-element-pack' ),
					'ruble'        => '&#8381; ' . _x( 'Ruble', 'Currency Symbol', 'bdthemes-element-pack' ),
					'rupee'        => '&#8360; ' . _x( 'Rupee', 'Currency Symbol', 'bdthemes-element-pack' ),
					'indian_rupee' => '&#8377; ' . _x( 'Rupee (Indian)', 'Currency Symbol', 'bdthemes-element-pack' ),
					'shekel'       => '&#8362; ' . _x( 'Shekel', 'Currency Symbol', 'bdthemes-element-pack' ),
					'yen'          => '&#165; ' . _x( 'Yen/Yuan', 'Currency Symbol', 'bdthemes-element-pack' ),
					'bdt'          => '&#2547; ' . _x( 'Taka', 'Currency Symbol', 'bdthemes-element-pack' ),
					'won'          => '&#8361; ' . _x( 'Won', 'Currency Symbol', 'bdthemes-element-pack' ),
					'custom'       => __( 'Custom', 'bdthemes-element-pack' ),
				],
				'default' => 'dollar',
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'currency_symbol_custom',
			[ 
				'label'     => __( 'Custom Symbol', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::TEXT,
				'condition' => [ 
					'currency_symbol' => 'custom',
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'price',
			[ 
				'label'   => __( 'Price', 'bdthemes-element-pack' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '49.99',
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);


		$repeater->add_control(
			'currency_format',
			[ 
				'label'   => __( 'Currency Format', 'bdthemes-element-pack' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [ 
					''  => '1,234.56 (Default)',
					',' => '1.234,56',
				],
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'sale',
			[ 
				'label'     => __( 'Sale', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::SWITCHER,
				'separator' => 'before',
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'original_price',
			[ 
				'label'     => __( 'Original Price', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => '79',
				'condition' => [ 
					'sale' => 'yes',
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'period',
			[ 
				'label'   => __( 'Period', 'bdthemes-element-pack' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Monthly', 'bdthemes-element-pack' ),
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'price_card_additional_text',
			[
				'label'       => esc_html__('Additional Text', 'bdthemes-element-pack'),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => esc_html__('Enjoy our special offer!', 'bdthemes-element-pack'),
				'label_block' => true,
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'button_text',
			[ 
				'label'   => __( 'Button Text', 'bdthemes-element-pack' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Select Plan', 'bdthemes-element-pack' ),
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'link',
			[ 
				'label'       => __( 'Link', 'bdthemes-element-pack' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'http://your-link.com',
				'default'     => [ 
					'url' => '#',
				],
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'button_css_id',
			[ 
				'label'       => __( 'Button ID', 'bdthemes-element-pack' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 
					'active' => true,
				],
				'default'     => '',
				'title'       => __( 'Add your custom id WITHOUT the Pound key. e.g: my-id', 'bdthemes-element-pack' ),
				'description' => __( 'Please make sure the ID is unique and not used elsewhere on the page this form is displayed. This field allows <code>A-z 0-9</code> & underscore chars without spaces.', 'bdthemes-element-pack' ),
				'separator'   => 'before',
				'condition'   => [
					'content_type' => 'price_card',
				],
			]
		);

		$repeater->add_control(
			'content',
			[
				'label'       => esc_html__('Content', 'bdthemes-element-pack'),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => esc_html__('Switcher Content', 'bdthemes-element-pack'),
				'label_block' => true,
				'condition'   => [
					'content_type' => 'content',
				],
			]
		);

		$repeater->add_control(
			'saved_templates',
			[
				'label'       => esc_html__('Choose Template', 'bdthemes-element-pack'),
				'type'        => Dynamic_Select::TYPE,
				'label_block' => true,
				'placeholder' => __('Type and select template', 'bdthemes-element-pack'),
				'query_args'  => [
					'query'        => 'elementor_template',
				],
				'condition'   => [
					'content_type' => 'template',
				],
			]
		);

		$repeater->add_control(
			'link_section_id',
			[
				'label'       => __('Section ID', 'bdthemes-element-pack'),
				'description' => __('Paste your section ID here. Don\'t need to add # before ID', 'bdthemes-element-pack'),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'section-id',
				'dynamic'     => ['active' => true],
				'condition'   => [
					'content_type' => 'link_section',
				],
			]
		);

		$repeater->add_control(
			'link_widget_id',
			[
				'label'   => __('Link Widget ID', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'condition'   => [
					'content_type' => 'link_widget',
				],
			]
		);

		$repeater->add_control(
			'link_widget_note',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => __('Note: Add widget ID here without # prefix. This widget will be shown when this item is active. Make sure to add Link Widget option for other items too.', 'bdthemes-element-pack'),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'   => [
					'content_type' => 'link_widget',
				],
			]
		);

		$this->add_control(
			'switcher_items',
			[
				'label'   => esc_html__('Switcher Items', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::REPEATER,
				'default' => [
					[
						'content_type'    => 'content',
						'title'           => esc_html__('Primary', 'bdthemes-element-pack'),
						'content'         => esc_html__('Switcher Content Primary', 'bdthemes-element-pack'),
						'switcher_active' => 'yes',
					],
					[
						'content_type' => 'content',
						'title'        => esc_html__('Secondary', 'bdthemes-element-pack'),
						'content'      => esc_html__('Switcher Content Secondary', 'bdthemes-element-pack'),
					],
					[
						'content_type' => 'content',
						'title'        => esc_html__('Others', 'bdthemes-element-pack'),
						'content'      => esc_html__('Switcher Content Others', 'bdthemes-element-pack'),
					],
				],
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_switcher_additional_options',
			[
				'label' => esc_html__('Additional Options', 'bdthemes-element-pack'),
			]
		);

		$this->add_control(
			'content_position_unchanged',
			[
				'label'        => __('Content Position Unchanged', 'bdthemes-element-pack'),
				'type'         => Controls_Manager::SWITCHER,
				'description'  => __('Keep content in its original position in the page.', 'bdthemes-element-pack'),
			]
		);

		//text align
		$this->add_responsive_control(
			'content_switcher_align',
			[
				'label'   => esc_html__('Alignment', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'   => [
						'title' => esc_html__('Left', 'bdthemes-element-pack'),
						'icon'  => 'fa fa-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'bdthemes-element-pack'),
						'icon'  => 'fa fa-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'bdthemes-element-pack'),
						'icon'  => 'fa fa-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'badge',
			[
				'label' => __('Badge', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SWITCHER,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'badge_text',
			[
				'label' => __('Badge Text', 'bdthemes-element-pack'),
				'type' => Controls_Manager::TEXT,
				'default' => 'Hot',
				'placeholder' => 'Type Step Here',
				'dynamic' => [
					'active' => true,
				],
				'condition' => [
					'badge' => 'yes',
				],
			]
		);

		//badge left right align
		$this->add_control(
			'badge_align',
			[
				'label'   => esc_html__('Badge Align', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'   => [
						'title' => esc_html__('Left', 'bdthemes-element-pack'),
						'icon'  => 'eicon-h-align-left',
					],
					'right'  => [
						'title' => esc_html__('Right', 'bdthemes-element-pack'),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'condition' => [
					'badge' => 'yes',
				],
				'selectors_dictionary' => [
					'left' => 'left: 0;',
					'right' => 'right: 0;',
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher-badge' => '{{VALUE}};',
				],
				'toggle' => false,
				'default' => 'left',
				'render_type' => 'template',
			]
		);

		//arrows style select	
		$this->add_control(
			'arrows_style',
			[
				'label'   => esc_html__('Arrow Style', 'bdthemes-element-pack'),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'       => '01',
					'2'       => '02',
					'3'       => '03',
					'4'       => '04',
				],
				'condition' => [
					'badge' => 'yes',
				],
			]
		);

		$this->add_control(
			'badge_offset_toggle',
			[
				'label' => __('Offset', 'bdthemes-element-pack'),
				'type' => Controls_Manager::POPOVER_TOGGLE,
				'label_off' => __('None', 'bdthemes-element-pack'),
				'label_on' => __('Custom', 'bdthemes-element-pack'),
				'return_value' => 'yes',
				'condition' => [
					'badge' => 'yes',
				],
			]
		);

		$this->start_popover();

		$this->start_controls_tabs('tabs_offset_badge_controls');

		$this->start_controls_tab(
			'tab_offset_badge_controls',
			[
				'label' => __('Badge', 'bdthemes-element-pack'),
			]
		);

		$this->add_responsive_control(
			'badge_horizontal_offset',
			[
				'label' => __('Badge Horizontal Offset', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'tablet_default' => [
					'size' => 0,
				],
				'mobile_default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min' => -300,
						'step' => 1,
						'max' => 300,
					],
				],
				'condition' => [
					'badge_offset_toggle' => 'yes',
					'badge' => 'yes',
				],
				'render_type' => 'ui',
				'selectors' => [
					'{{WRAPPER}}' => '--ep-content-switcher-badge-h-offset: {{SIZE}}px;'
				],
			]
		);

		$this->add_responsive_control(
			'badge_vertical_offset',
			[
				'label' => __('Badge Vertical Offset', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => -40,
				],
				'tablet_default' => [
					'size' => -40,
				],
				'mobile_default' => [
					'size' => -40,
				],
				'range' => [
					'px' => [
						'min' => -300,
						'step' => 1,
						'max' => 300,
					],
				],
				'condition' => [
					'badge_offset_toggle' => 'yes',
					'badge' => 'yes',
				],
				'render_type' => 'ui',
				'selectors' => [
					'{{WRAPPER}}' => '--ep-content-switcher-badge-v-offset: {{SIZE}}px;'
				],
			]
		);

		$this->add_responsive_control(
			'badge_rotate',
			[
				'label' => esc_html__('Badge Rotate', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'tablet_default' => [
					'size' => 0,
				],
				'mobile_default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min' => -360,
						'max' => 360,
						'step' => 5,
					],
				],
				'condition' => [
					'badge_offset_toggle' => 'yes',
					'badge' => 'yes',
				],
				'render_type' => 'ui',
				'selectors' => [
					'{{WRAPPER}}' => '--ep-content-switcher-badge-rotate: {{SIZE}}deg;'
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_offset_arrows_controls',
			[
				'label' => __('Arrow', 'bdthemes-element-pack'),
			]
		);

		$this->add_responsive_control(
			'arrows_horizontal_offset_left',
			[
				'label' => __('Arrow Horizontal Offset', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => -35,
				],
				'tablet_default' => [
					'size' => -35,
				],
				'mobile_default' => [
					'size' => -35,
				],
				'range' => [
					'px' => [
						'min' => -300,
						'step' => 1,
						'max' => 300,
					],
				],
				'condition' => [
					'badge_offset_toggle' => 'yes',
					'badge' => 'yes',
					'badge_align' => 'left',
				],
				'render_type' => 'ui',
				'selectors' => [
					'{{WRAPPER}}' => '--ep-content-switcher-arrows-h-offset-left: {{SIZE}}px;'
				],
			]
		);

		$this->add_responsive_control(
			'arrows_horizontal_offset_right',
			[
				'label' => __('Arrow Horizontal Offset', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 40,
				],
				'tablet_default' => [
					'size' => 40,
				],
				'mobile_default' => [
					'size' => 40,
				],
				'range' => [
					'px' => [
						'min' => -300,
						'step' => 1,
						'max' => 300,
					],
				],
				'condition' => [
					'badge_offset_toggle' => 'yes',
					'badge' => 'yes',
					'badge_align' => 'right',
				],
				'render_type' => 'ui',
				'selectors' => [
					'{{WRAPPER}}' => '--ep-content-switcher-arrows-h-offset-right: {{SIZE}}px;'
				],
			]
		);

		$this->add_responsive_control(
			'arrows_vertical_offset',
			[
				'label' => __('Arrow Vertical Offset', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => -26,
				],
				'tablet_default' => [
					'size' => -26,
				],
				'mobile_default' => [
					'size' => -26,
				],
				'range' => [
					'px' => [
						'min' => -300,
						'step' => 1,
						'max' => 300,
					],
				],
				'condition' => [
					'badge_offset_toggle' => 'yes',
					'badge' => 'yes',
				],
				'render_type' => 'ui',
				'selectors' => [
					'{{WRAPPER}}' => '--ep-content-switcher-arrows-v-offset: {{SIZE}}px;'
				],
			]
		);

		$this->add_responsive_control(
			'arrows_rotate',
			[
				'label' => esc_html__('Arrow Rotate', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'tablet_default' => [
					'size' => 0,
				],
				'mobile_default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min' => -360,
						'max' => 360,
						'step' => 5,
					],
				],
				'condition' => [
					'badge_offset_toggle' => 'yes',
					'badge' => 'yes',
				],
				'render_type' => 'ui',
				'selectors' => [
					'{{WRAPPER}}' => '--ep-content-switcher-arrows-rotate: {{SIZE}}deg;'
				],
			]
		);


		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_popover();

		$this->end_controls_section();

		// switcher style

		$this->start_controls_section(
			'section_style_switch',
			[
				'label' => esc_html__('Switch', 'bdthemes-element-pack'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		//spacing
		$this->add_responsive_control(
			'switch_spacing',
			[
				'label' => esc_html__('Space Between', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'step' => 1,
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-switch-container-wrap' => 'gap: {{SIZE}}px;',
				],
			]
		);

		$this->add_responsive_control(
			'switch_icon_spacing',
			[
				'label' => esc_html__('Icon Spacing', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'step' => 1,
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-switch-container-wrap .bdt-package-text, {{WRAPPER}} .bdt-content-switcher-tab' => 'gap: {{SIZE}}px;',
				],
			]
		);

		//typography
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'switch_typography',
				'selector'  => '{{WRAPPER}} .bdt-switch-container-wrap .bdt-package-text, {{WRAPPER}} .bdt-content-switcher-icon, {{WRAPPER}} .bdt-content-switcher-tab',
			]
		);

		$this->start_controls_tabs('tabs_switch_style');

		$this->start_controls_tab(
			'tab_switch_style_normal',
			[
				'label' => __('Normal', 'bdthemes-element-pack'),
			]
		);

		//switch color
		$this->add_control(
			'switch_color',
			[
				'label' => esc_html__('Color', 'bdthemes-element-pack'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-switch-container-wrap .bdt-package-text, {{WRAPPER}} .bdt-content-switcher-icon i, {{WRAPPER}} .bdt-content-switcher-tab' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bdt-content-switcher-icon svg' => 'fill: {{VALUE}};',
				],
			]
		);

		//button style start
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'switcher_button_background',
				'selector' => '{{WRAPPER}} .bdt-content-switcher-tab',
				'condition' => [
					'switcher_style' => 'button',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'switcher_button_border',
				'selector' => '{{WRAPPER}} .bdt-content-switcher-tab',
				'condition' => [
					'switcher_style' => 'button',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_button_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .bdt-content-switcher-tab' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'switcher_style' => 'button',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_button_padding',
			[
				'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .bdt-content-switcher-tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'switcher_style' => 'button',
				],
			]
		);

		// text shadow
		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'switch_text_shadow',
				'selector' => '{{WRAPPER}} .bdt-switch-container-wrap .bdt-package-text, {{WRAPPER}} .bdt-content-switcher-tab',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'switcher_button_shadow',
				'selector' => '{{WRAPPER}} .bdt-content-switcher-tab',
				'condition' => [
					'switcher_style' => 'button',
				],
			]
		);

		// text stroke
		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'switch_text_stroke',
				'selector' => '{{WRAPPER}} .bdt-switch-container-wrap .bdt-package-text, {{WRAPPER}} .bdt-content-switcher-tab',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_switch_style_active',
			[
				'label' => __('Active', 'bdthemes-element-pack'),
			]
		);

		$this->add_control(
			'switch_active_color',
			[
				'label' => esc_html__('Color', 'bdthemes-element-pack'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-switch-container-wrap .bdt-package-text.bdt-active, {{WRAPPER}} .bdt-content-switcher-icon.bdt-active i, {{WRAPPER}} .bdt-content-switcher-tab.bdt-active .bdt-content-switcher-icon i, {{WRAPPER}} .bdt-content-switcher-tab.bdt-active' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bdt-content-switcher-icon.bdt-active svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'switcher_button_active_background',
				'selector' => '{{WRAPPER}} .bdt-content-switcher-tab.bdt-active',
				'condition' => [
					'switcher_style' => 'button',
				],
			]
		);

		$this->add_control(
			'switcher_button_active_border_color',
			[
				'label'     => esc_html__('Border Color', 'bdthemes-element-pack'),
				'type'      => Controls_Manager::COLOR,
				'condition' => [
					'switcher_style' => 'button',
					'switcher_button_border_border!' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher-tab.bdt-active' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'switch_active_text_shadow',
				'selector' => '{{WRAPPER}} .bdt-switch-container-wrap .bdt-package-text.bdt-active, {{WRAPPER}} .bdt-content-switcher-tab.bdt-active',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'switcher_button_shadow_active',
				'selector' => '{{WRAPPER}} .bdt-content-switcher-tab.bdt-active',
				'condition' => [
					'switcher_style' => 'button',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'switch_active_text_stroke',
				'selector' => '{{WRAPPER}} .bdt-switch-container-wrap .bdt-package-text, {{WRAPPER}} .bdt-content-switcher-tab.bdt-active',
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_switcher',
			[
				'label' => esc_html__('Switcher', 'bdthemes-element-pack'),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'switcher_style!' => 'button',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_width',
			[
				'label' => esc_html__('Width', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'range' => [
					'%' => [
						'min' => 10,
						'max' => 100,
					],
					'px' => [
						'min' => 60,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher .button' => 'width: {{SIZE}}{{UNIT}};',
				],
				// 'condition' => [
				// 	'switcher_style!' => '9',
				// ],
			]
		);

		$this->add_responsive_control(
			'switcher_height',
			[
				'label' => esc_html__('Height', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'range' => [
					'%' => [
						'min' => 10,
						'max' => 100,
					],
					'px' => [
						'min' => 10,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher .button' => 'height: {{SIZE}}{{UNIT}};',
				],
				// 'condition' => [
				// 	'switcher_style!' => '9',
				// ],
			]
		);

		// knob size
		$this->add_responsive_control(
			'switcher_knob_size',
			[
				'label' => esc_html__('Knob Size', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 10,
						'max' => 100,
					],
				],
				'selectors' => [
                    '{{WRAPPER}}' => '--ep-knob-size: {{SIZE}}px;'
                ],
				// 'condition' => [
				// 	'switcher_style!' => '9',
				// ],
			]
		);

		//border radius
		$this->add_responsive_control(
			'switcher_border_radius',
			[
				'label' => esc_html__('Border Radius', 'bdthemes-element-pack'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher .bdt-layer, {{WRAPPER}} .bdt-content-switcher .button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		
		//margin
		$this->add_responsive_control(
			'switcher_margin',
			[
				'label' => esc_html__('Margin', 'bdthemes-element-pack'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher .button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// alignment
		// $this->add_responsive_control(
		// 	'switcher_alignment',
		// 	[
		// 		'label' => esc_html__('Alignment', 'bdthemes-element-pack'),
		// 		'type' => Controls_Manager::CHOOSE,
		// 		'options' => [
		// 			'flex-start'    => [
		// 				'title' => esc_html__('Left', 'bdthemes-element-pack'),
		// 				'icon' => 'eicon-h-align-left',
		// 			],
		// 			'center' => [
		// 				'title' => esc_html__('Center', 'bdthemes-element-pack'),
		// 				'icon' => 'eicon-h-align-center',
		// 			],
		// 			'flex-end' => [
		// 				'title' => esc_html__('Right', 'bdthemes-element-pack'),
		// 				'icon' => 'eicon-h-align-right',
		// 			],
		// 			'space-between' => [
		// 				'title' => esc_html__('Justified', 'bdthemes-element-pack'),
		// 				'icon' => 'eicon-h-align-stretch',
		// 			],
		// 		],
		// 		'selectors' => [
		// 			'{{WRAPPER}} .bdt-switch-container-wrap' => 'justify-content: {{VALUE}};',
		// 		],
		// 	]
		// );

		$this->start_controls_tabs('tabs_switcher_style');

		$this->start_controls_tab(
			'tab_switcher_style_normal',
			[
				'label' => __('Normal', 'bdthemes-element-pack'),
			]
		);

		$this->add_control(
			'switcher_knob_color',
			[
				'label' => esc_html__('Color', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-toggle-button-1 .bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-2 .bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-2 .bdt-knobs:after, {{WRAPPER}} .bdt-toggle-button-3 .bdt-knobs::before, {{WRAPPER}} .bdt-toggle-button-4 .bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-4 .bdt-knobs:after, {{WRAPPER}} .bdt-toggle-button-5 .bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-6 .bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-7 .bdt-knobs span, {{WRAPPER}} .bdt-toggle-button-8 .bdt-knobs span, {{WRAPPER}} .bdt-toggle-button-9 .bdt-knobs span' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'switcher_color',
			[
				'label' => esc_html__('Background Color', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher .bdt-layer' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_switcher_style_active',
			[
				'label' => __('Active', 'bdthemes-element-pack'),
			]
		);

		$this->add_control(
			'switcher_knob_checked_color',
			[
				'label' => esc_html__('Color', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-toggle-button-1 .checkbox:checked+.bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-2 .checkbox:checked+.bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-2 .checkbox:checked+.bdt-knobs:after, {{WRAPPER}} .bdt-toggle-button-3 .checkbox:checked+.bdt-knobs::before, {{WRAPPER}} .bdt-toggle-button-4 .checkbox:checked+.bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-4 .checkbox:checked+.bdt-knobs:after, {{WRAPPER}} .bdt-toggle-button-5 .checkbox:checked+.bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-6 .checkbox:checked+.bdt-knobs:after, {{WRAPPER}} .bdt-toggle-button-7 .bdt-knobs:after, {{WRAPPER}} .bdt-toggle-button-8 .bdt-knobs:after, {{WRAPPER}} .bdt-toggle-button-9 .checkbox:checked+.bdt-knobs span' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'switcher_checked_color',
			[
				'label' => esc_html__('Background Color', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-toggle-button-1 .checkbox:checked~.bdt-layer, {{WRAPPER}} .bdt-toggle-button-2 .checkbox:checked~.bdt-layer, {{WRAPPER}} .bdt-toggle-button-3 .checkbox:checked~.bdt-layer, {{WRAPPER}} .bdt-toggle-button-4 .checkbox:checked~.bdt-layer, {{WRAPPER}} .bdt-toggle-button-5 .checkbox:checked~.bdt-layer, {{WRAPPER}} .bdt-toggle-button-6 .checkbox:checked~.bdt-layer, {{WRAPPER}} .bdt-toggle-button-7 .checkbox:checked~.bdt-layer, {{WRAPPER}} .bdt-toggle-button-7 .bdt-knobs:before, {{WRAPPER}} .bdt-toggle-button-8 .checkbox:checked+.bdt-knobs span, {{WRAPPER}} .bdt-toggle-button-9 .checkbox:checked~.bdt-layer' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'switcher_checked_knob_color',
			[
				'label' => esc_html__('Knob Color', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-toggle-button-7 .checkbox:checked+.bdt-knobs span' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'switcher_style' => '7',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_switcher_bar',
			[
				'label' => esc_html__('Switcher Bar', 'bdthemes-element-pack'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'switcher_bar_background',
				'selector' => '{{WRAPPER}} .bdt-switch-container-wrap',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'switcher_bar_border',
				'selector' => '{{WRAPPER}} .bdt-switch-container-wrap',
			]
		);

		$this->add_responsive_control(
			'switcher_bar_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .bdt-switch-container-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_bar_padding',
			[
				'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .bdt-switch-container-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_bar_margin',
			[
				'label'      => esc_html__('Margin', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .bdt-switch-container-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'switcher_bar_shadow',
				'selector' => '{{WRAPPER}} .bdt-switch-container-wrap',
			]
		);

		$this->end_controls_section();

		// switcher content style
		$this->start_controls_section(
			'section_switcher_content_style',
			[
				'label' => esc_html__('Content', 'bdthemes-element-pack'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		//text color
		$this->add_control(
			'switcher_content_text_color',
			[
				'label' => esc_html__('Color', 'bdthemes-element-pack'),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-switcher-content' => 'color: {{VALUE}};',
				],
			]
		);

		//background type
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'switcher_content_background',
				'types'     => ['classic', 'gradient'],
				'selector'  => '{{WRAPPER}} .bdt-switcher-content',
			]
		);

		//border
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'switcher_content_border',
				'label'     => esc_html__('Border', 'bdthemes-element-pack'),
				'selector'  => '{{WRAPPER}} .bdt-switcher-content',
				'separator' => 'before',
			]
		);

		//border radius
		$this->add_responsive_control(
			'switcher_content_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'selectors'  => [
					'{{WRAPPER}} .bdt-switcher-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_content_padding',
			[
				'label'      => esc_html__('Padding', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .bdt-switcher-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'switcher_content_margin',
			[
				'label'      => esc_html__('Margin', 'bdthemes-element-pack'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .bdt-switcher-content-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		//box shadow
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'switcher_content_box_shadow',
				'selector'  => '{{WRAPPER}} .bdt-switcher-content',
			]
		);

		// $this->add_responsive_control(
		// 	'switcher_content_align',
		// 	[
		// 		'label'   => esc_html__('Alignment', 'bdthemes-element-pack'),
		// 		'type'    => Controls_Manager::CHOOSE,
		// 		'options' => [
		// 			'left'    => [
		// 				'title' => esc_html__('Left', 'bdthemes-element-pack'),
		// 				'icon'  => 'fa fa-align-left',
		// 			],
		// 			'center' => [
		// 				'title' => esc_html__('Center', 'bdthemes-element-pack'),
		// 				'icon'  => 'fa fa-align-center',
		// 			],
		// 			'right' => [
		// 				'title' => esc_html__('Right', 'bdthemes-element-pack'),
		// 				'icon'  => 'fa fa-align-right',
		// 			],
		// 			'justify' => [
		// 				'title' => esc_html__('Justified', 'bdthemes-element-pack'),
		// 				'icon'  => 'fa fa-align-justify',
		// 			],
		// 		],
		// 		'selectors'  => [
		// 			'{{WRAPPER}} .bdt-switcher-content' => 'text-align: {{VALUE}};',
		// 		],
		// 		'separator' => 'before',
		// 	]
		// );

		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_badge',
			[
				'label' => __('Badge', 'bdthemes-element-pack'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'badge' => 'yes',
				],
			]
		);

		$this->add_control(
			'badge_text_color',
			[
				'label' => __('Text Color', 'bdthemes-element-pack'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher-badge' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'badge_arrows_color',
			[
				'label' => __('Arrow Color', 'bdthemes-element-pack'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-switcher-arrows svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'badge_background_color',
			[
				'label' => __('Background Color', 'bdthemes-element-pack'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher-badge' => 'background: {{VALUE}};',
					'{{WRAPPER}} .bdt-content-switcher-badge:before' => 'border-top-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'badge_border',
				'placeholder' => '1px',
				'separator' => 'before',
				'default' => '1px',
				'selector' => '{{WRAPPER}} .bdt-content-switcher-badge',
			]
		);

		$this->add_responsive_control(
			'badge_radius',
			[
				'label' => __('Border Radius', 'bdthemes-element-pack'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_padding',
			[
				'label' => __('Padding', 'bdthemes-element-pack'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .bdt-content-switcher-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'badge_shadow',
				'selector' => '{{WRAPPER}} .bdt-content-switcher-badge',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'badge_typography',
				'selector' => '{{WRAPPER}} .bdt-content-switcher-badge',
			]
		);

		//arrow size
		$this->add_responsive_control(
			'badge_arrows_size',
			[
				'label' => __('Arrow Size', 'bdthemes-element-pack'),
				'type' => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}} .bdt-switcher-arrows' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_pricing',
			[ 
				'label' => __( 'Pricing', 'bdthemes-element-pack' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pricing_align',
			[ 
				'label'                => __( 'Alignment', 'bdthemes-element-pack' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [ 
					'left'   => [ 
						'title' => __( 'Left', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [ 
						'title' => __( 'Center', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [ 
						'title' => __( 'Right', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors_dictionary' => [ 
					'left'   => 'justify-content: flex-start; text-align: left;',
					'right'  => 'justify-content: flex-end; text-align: right;',
					'center' => '    justify-content: center; text-align: center;',
				],
				'selectors'            => [ 
					'{{WRAPPER}} .bdt-price-card-price' => '{{VALUE}};',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_pricing_style' );

		$this->start_controls_tab(
			'tabs_pricing_normal',
			[ 
				'label' => __( 'Normal', 'bdthemes-element-pack' ),
			]
		);

		$this->add_control(
			'price_color',
			[ 
				'label'     => __( 'Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-currency, {{WRAPPER}} .bdt-price-card-integer-part, {{WRAPPER}} .bdt-price-card-fractional-part' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[ 
				'name'     => 'pricing_element_bg_color',
				'selector' => '{{WRAPPER}} .bdt-price-card-price',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[ 
				'name'     => 'pricing_border',
				'selector' => '{{WRAPPER}} .bdt-price-card-price',
			]
		);

		$this->add_responsive_control(
			'readmore_border_radius',
			[ 
				'label'      => esc_html__( 'Border Radius', 'bdthemes-element-pack' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [ 
					'{{WRAPPER}} .bdt-price-card-price' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pricing_element_padding',
			[ 
				'label'      => __( 'Padding', 'bdthemes-element-pack' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [ 
					'{{WRAPPER}} .bdt-price-card-price' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pricing_element_margin',
			[ 
				'label'      => __( 'Margin', 'bdthemes-element-pack' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [ 
					'{{WRAPPER}} .bdt-price-card-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[ 
				'name'     => 'price_shadow',
				'label'    => __( 'Text Shadow', 'bdthemes-element-pack' ),
				'selector' => '{{WRAPPER}} .bdt-price-card-currency, {{WRAPPER}} .bdt-price-card-integer-part, {{WRAPPER}} .bdt-price-card-fractional-part',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 
				'name'     => 'price_typography',
				'selector' => '{{WRAPPER}} .bdt-price-card-price',
			]
		);

		$this->add_control(
			'heading_currency_style',
			[ 
				'label'     => __( 'Currency Symbol', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'currency_size',
			[ 
				'label'     => __( 'Size', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 
					'px' => [ 
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-currency' => 'font-size: calc({{SIZE}}em/100)',
				],
			]
		);

		$this->add_control(
			'currency_horizontal_position',
			[ 
				'label'     => __( 'Position', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'left',
				'options'   => [ 
					'left'  => [ 
						'title' => __( 'Left', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-h-align-left',
					],
					'right' => [ 
						'title' => __( 'Right', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
			]
		);

		$this->add_control(
			'currency_vertical_position',
			[ 
				'label'                => __( 'Vertical Position', 'bdthemes-element-pack' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [ 
					'top'    => [ 
						'title' => __( 'Top', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-top',
					],
					'middle' => [ 
						'title' => __( 'Middle', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'bottom' => [ 
						'title' => __( 'Bottom', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'default'              => 'top',
				'selectors_dictionary' => [ 
					'top'    => 'flex-start',
					'middle' => 'center',
					'bottom' => 'flex-end',
				],
				'selectors'            => [ 
					'{{WRAPPER}} .bdt-price-card-currency' => 'align-self: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'fractional_part_style',
			[ 
				'label'     => __( 'Fractional Part', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'fractional-part_size',
			[ 
				'label'     => __( 'Size', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 
					'px' => [ 
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-fractional-part' => 'font-size: calc({{SIZE}}em/100)',
				],
			]
		);

		$this->add_control(
			'fractional_part_vertical_position',
			[ 
				'label'                => __( 'Vertical Position', 'bdthemes-element-pack' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [ 
					'top'    => [ 
						'title' => __( 'Top', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-top',
					],
					'middle' => [ 
						'title' => __( 'Middle', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'bottom' => [ 
						'title' => __( 'Bottom', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'default'              => 'top',
				'selectors_dictionary' => [ 
					'top'    => 'flex-start',
					'middle' => 'center',
					'bottom' => 'flex-end',
				],
				'selectors'            => [ 
					'{{WRAPPER}} .bdt-price-card-after-price' => 'justify-content: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_original_price_style',
			[ 
				'label'     => __( 'Original Price', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'original_price_color',
			[ 
				'label'     => __( 'Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-original-price' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 
				'name'      => 'original_price_typography',
				'selector'  => '{{WRAPPER}} .bdt-price-card-original-price',
			]
		);

		$this->add_control(
			'original_price_vertical_position',
			[ 
				'label'                => __( 'Vertical Position', 'bdthemes-element-pack' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [ 
					'top'    => [ 
						'title' => __( 'Top', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-top',
					],
					'middle' => [ 
						'title' => __( 'Middle', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'bottom' => [ 
						'title' => __( 'Bottom', 'bdthemes-element-pack' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'selectors_dictionary' => [ 
					'top'    => 'top: 0;',
					'middle' => 'top: 40%;',
					'bottom' => 'bottom: 0;',
				],
				'default'              => 'middle',
				'selectors'            => [ 
					'{{WRAPPER}} .bdt-price-card-original-price' => '{{VALUE}}',
				],
			]
		);

		$this->add_control(
			'original_price_offset_toggle',
			[ 
				'label'        => __( 'Offset', 'bdthemes-element-pack' ),
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label_off'    => __( 'None', 'bdthemes-element-pack' ),
				'label_on'     => __( 'Custom', 'bdthemes-element-pack' ),
				'return_value' => 'yes',
			]
		);

		$this->start_popover();

		$this->add_responsive_control(
			'original_price_horizontal_offset',
			[ 
				'label'          => __( 'Horizontal Offset', 'bdthemes-element-pack' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [ 
					'size' => 0,
				],
				'tablet_default' => [ 
					'size' => 0,
				],
				'mobile_default' => [ 
					'size' => 0,
				],
				'range'          => [ 
					'px' => [ 
						'min'  => -300,
						'step' => 2,
						'max'  => 300,
					],
				],
				'render_type'    => 'ui',
				'selectors'      => [ 
					'{{WRAPPER}}' => '--ep-pt-original-price-h-offset: {{SIZE}}px;'
				],
			]
		);

		$this->add_responsive_control(
			'original_price_vertical_offset',
			[ 
				'label'          => __( 'Vertical Offset', 'bdthemes-element-pack' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [ 
					'size' => 0,
				],
				'tablet_default' => [ 
					'size' => 0,
				],
				'mobile_default' => [ 
					'size' => 0,
				],
				'range'          => [ 
					'px' => [ 
						'min'  => -300,
						'step' => 2,
						'max'  => 300,
					],
				],
				'render_type'    => 'ui',
				'selectors'      => [ 
					'{{WRAPPER}}' => '--ep-pt-original-price-v-offset: {{SIZE}}px;'
				],
			]
		);

		$this->add_responsive_control(
			'original_price_rotate',
			[ 
				'label'          => esc_html__( 'Rotate', 'bdthemes-element-pack' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [ 
					'size' => 0,
				],
				'tablet_default' => [ 
					'size' => 0,
				],
				'mobile_default' => [ 
					'size' => 0,
				],
				'range'          => [ 
					'px' => [ 
						'min'  => -360,
						'max'  => 360,
						'step' => 5,
					],
				],
				'render_type'    => 'ui',
				'selectors'      => [ 
					'{{WRAPPER}}' => '--ep-pt-original-price-rotate: {{SIZE}}deg;'
				],
			]
		);

		$this->end_popover();

		$this->add_control(
			'heading_period_style',
			[ 
				'label'     => __( 'Period', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'period_color',
			[ 
				'label'     => __( 'Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-period' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 
				'name'      => 'period_typography',
				'selector'  => '{{WRAPPER}} .bdt-price-card-period',
			]
		);

		$this->add_control(
			'period_position',
			[ 
				'label'     => __( 'Position', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [ 
					'below'  => 'Below',
					'beside' => 'Beside',
				],
				'default'   => 'below',
			]
		);

		$this->add_control(
			'heading_price_title_style',
			[ 
				'label'     => __( 'Price Title', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'price_title_color',
			[ 
				'label'     => __( 'Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 
				'name'      => 'price_title_typography',
				'selector'  => '{{WRAPPER}} .bdt-price-title',
			]
		);

		$this->add_control(
			'heading_price_additional_text_style',
			[ 
				'label'     => __( 'Additional Text', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'price_additional_text_color',
			[ 
				'label'     => __( 'Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-additional-text' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 
				'name'      => 'price_additional_text_typography',
				'selector'  => '{{WRAPPER}} .bdt-price-additional-text',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tabs_pricing_hover',
			[ 
				'label' => __( 'Hover', 'bdthemes-element-pack' ),
			]
		);

		$this->add_control(
			'price_hover_color',
			[ 
				'label'     => __( 'Price Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-content-switcher:hover .bdt-price-card-currency, {{WRAPPER}} .bdt-content-switcher:hover .bdt-price-card-integer-part, {{WRAPPER}} .bdt-content-switcher:hover .bdt-price-card-fractional-part' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[ 
				'name'     => 'pricing_element_hover_bg_color',
				'selector' => '{{WRAPPER}} .bdt-content-switcher:hover .bdt-price-card-price',
			]
		);

		$this->add_control(
			'pricing_table_border_color',
			[ 
				'label'     => esc_html__( 'Border Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => [ 
					'pricing_border_border!' => '',
				],
				'selectors' => [ 
					'{{WRAPPER}} .bdt-content-switcher:hover .bdt-price-card-price' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'period_hover_color',
			[ 
				'label'     => __( 'Period Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-content-switcher:hover .bdt-price-card-period' => 'color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_price_button',
			[ 
				'label' => __( 'Button', 'bdthemes-element-pack' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_button_style' );
		$this->start_controls_tab(
			'tab_button_normal',
			[ 
				'label'     => __( 'Normal', 'bdthemes-element-pack' ),
			]
		);

		$this->add_control(
			'button_text_color',
			[ 
				'label'     => __( 'Text Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[ 
				'name'      => 'button_background_color',
				'selector'  => '{{WRAPPER}} .bdt-price-card-button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[ 
				'name'        => 'button_border',
				'label'       => __( 'Border', 'bdthemes-element-pack' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .bdt-price-card-button',
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[ 
				'label'      => __( 'Border Radius', 'bdthemes-element-pack' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [ 
					'{{WRAPPER}} .bdt-price-card-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[ 
				'label'      => __( 'Margin', 'bdthemes-element-pack' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [ 
					'{{WRAPPER}} .bdt-price-card-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_text_padding',
			[ 
				'label'      => __( 'Padding', 'bdthemes-element-pack' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [ 
					'{{WRAPPER}} .bdt-price-card-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'button_width',
			[ 
				'label'     => __( 'Width', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vw', '%' ],
				'range'     => [ 
					'px' => [ 
						'min' => 100,
						'max' => 1000,
					],
					'vw' => [ 
						'min' => 10,
						'max' => 100,
					],
					'%' => [ 
						'min' => 10,
						'max' => 100,
					],
				],
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-button' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'button_vertical_offset',
			[ 
				'label'          => __( 'Vertical offset', 'bdthemes-element-pack' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [ 
					'size' => 0,
				],
				'tablet_default' => [ 
					'size' => 0,
				],
				'mobile_default' => [ 
					'size' => 0,
				],
				'range'          => [ 
					'px' => [ 
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'      => [ 
					'{{WRAPPER}} .bdt-price-card-btn-wrap' => 'transform: translateY({{SIZE}}px)',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[ 
				'name'     => 'button_shadow',
				'selector' => '{{WRAPPER}} .bdt-price-card-button',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 
				'name'      => 'button_typography',
				'selector'  => '{{WRAPPER}} .bdt-price-card-button',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[ 
				'label'     => __( 'Hover', 'bdthemes-element-pack' ),
			]
		);

		$this->add_control(
			'button_hover_color',
			[ 
				'label'     => __( 'Text Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-button:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[ 
				'name'      => 'button_background_hover_color',
				'selector'  => '{{WRAPPER}} .bdt-price-card-button:hover',
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[ 
				'label'     => __( 'Border Color', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .bdt-price-card-button:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_animation',
			[ 
				'label'     => __( 'Animation', 'bdthemes-element-pack' ),
				'type'      => Controls_Manager::HOVER_ANIMATION,
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

	}

	private function get_currency_symbol( $symbol_name ) {
		$symbols = [ 
			'dollar'       => '&#36;',
			'baht'         => '&#3647;',
			'euro'         => '&#128;',
			'franc'        => '&#8355;',
			'guilder'      => '&fnof;',
			'indian_rupee' => '&#8377;',
			'krona'        => 'kr',
			'lira'         => '&#8356;',
			'peseta'       => '&#8359',
			'peso'         => '&#8369;',
			'pound'        => '&#163;',
			'real'         => 'R$',
			'ruble'        => '&#8381;',
			'rupee'        => '&#8360;',
			'bdt'          => '&#2547;',
			'shekel'       => '&#8362;',
			'won'          => '&#8361;',
			'yen'          => '&#165;',
		];
		return isset( $symbols[ $symbol_name ] ) ? $symbols[ $symbol_name ] : '';
	}

	public function render_price($settings) {
		$price_settings = $this->get_settings_for_display();

		$symbol = '';
		$image  = '';

		if ( ! empty( $settings['currency_symbol'] ) ) {
			if ( 'custom' !== $settings['currency_symbol'] ) {
				$symbol = $this->get_currency_symbol( $settings['currency_symbol'] );
			} else {
				$symbol = $settings['currency_symbol_custom'];
			}
		}


		$currency_format = empty( $settings['currency_format'] ) ? '.' : $settings['currency_format'];
		$price           = explode( $currency_format, $settings['price'] );
		$intpart         = $price[0];
		$fraction        = '';
		if ( 2 === count( $price ) ) {
			$fraction = $price[1];
		}


		// $price    = explode( '.', $settings['price'] );
		// $intpart  = $price[0];
		// $fraction = '';

		// if ( 2 === sizeof( $price ) ) {
		// 	$fraction = $price[1];
		// }

		$period_position = $price_settings['period_position'];
		$period_class    = ( $period_position == 'below' ) ? ' bdt-price-card-period-position-below' : ' bdt-price-card-period-position-beside';
		$period_element  = '<span class="bdt-price-card-period elementor-typo-excluded' . $period_class . '">' . $settings['period'] . '</span>';

		$currency_position = $price_settings['currency_horizontal_position'];

		$id = 'bdt-price-card-' . $this->get_id();

		$this->add_render_attribute( 'pricing', [ 
			'class' => 'bdt-price-card-price'
		] );

		?>

		<div class="bdt-price-card">
			<div class="bdt-price-title"><?php echo wp_kses_post($settings['price_card_content']); ?></div>

			<div <?php $this->print_render_attribute_string( 'pricing' ); ?>>
				<?php if ( $settings['sale'] && ! empty( $settings['original_price'] ) ) : ?>
					<span class="bdt-price-card-original-price elementor-typo-excluded bdt-display-block">
						<?php echo esc_html( $symbol . $settings['original_price'] ); ?>
					</span>
				<?php endif; ?>

				<?php if ( ! empty( $symbol ) && is_numeric( $intpart ) && 'left' === $currency_position ) : ?>
					<span class="bdt-price-card-currency">
						<?php echo esc_html( $symbol ); ?>
					</span>
				<?php endif; ?>

				<?php if ( ! empty( $intpart ) || 0 <= $intpart ) : ?>
					<span class="bdt-price-card-integer-part">
						<?php echo esc_attr( $intpart ); ?>
					</span>
				<?php endif; ?>

				<?php if ( 0 < $fraction || ( ! empty( $settings['period'] ) && 'beside' === $period_position ) ) : ?>
					<div class="bdt-price-card-after-price">
						<span class="bdt-price-card-fractional-part">
							<?php echo esc_attr( $fraction ); ?>
						</span>
						<?php if ( ! empty( $settings['period'] ) && 'beside' === $period_position ) : ?>
							<?php echo wp_kses_post( $period_element ); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $symbol ) && is_numeric( $intpart ) && 'right' === $currency_position ) : ?>
					<span class="bdt-price-card-currency">
						<?php echo esc_html( $symbol ); ?>
					</span>
				<?php endif; ?>

				<?php if ( ! empty( $settings['period'] ) && 'below' === $period_position ) : ?>
					<?php echo wp_kses_post( $period_element ); ?>
				<?php endif; ?>
			</div>

			<div class="bdt-price-additional-text"><?php echo wp_kses_post($settings['price_card_additional_text']); ?></div>

			<?php $this->render_button( $settings ); ?>
		</div>
		<?php
	}

	public function render_button($settings) {
		$button_settings = $this->get_settings_for_display();
		$button_animation = ( ! empty( $button_settings['button_hover_animation'] ) ) ? ' elementor-animation-' . $button_settings['button_hover_animation'] : '';

		// Generate unique button key using item ID to prevent attribute accumulation
		$button_key = 'button_' . $settings['_id'];

		$this->add_render_attribute(
			$button_key,
			'class',
			[ 
				'bdt-price-card-button',
				'elementor-button',
			]
		);

		if ( ! empty( $settings['button_css_id'] ) ) {
			$this->add_render_attribute( $button_key, 'id', $settings['button_css_id'] );
		}

		if ( ! empty( $settings['link']['url'] ) ) {
			$this->add_link_attributes( $button_key, $settings['link'] );
		}

		if ( ! empty( $button_settings['button_hover_animation'] ) ) {
			$this->add_render_attribute( $button_key, 'class', 'elementor-animation-' . $button_settings['button_hover_animation'] );
		}

		if ( ! empty( $settings['button_text'] ) ) : ?>
			<div class="bdt-price-card-btn-wrap">
				<a <?php $this->print_render_attribute_string( $button_key ); ?>>
					<?php echo esc_html( $settings['button_text'] ); ?>
				</a>
			</div>
		<?php endif;

	}

	public function render_badge(){
		$settings = $this->get_settings_for_display();

		if ( 'yes' !== $settings['badge'] ) {
			return;
		}

		?>
		<?php if ($settings['badge'] and '' != $settings['badge_text']) : ?>
			<?php if($settings['badge_align'] == 'left') : ?>
			<div class="bdt-switcher-arrows bdt-arrows-left">

				<?php echo wp_kses( element_pack_svg_icon('left-badge-arrows-'.$settings['arrows_style']), element_pack_allow_tags( 'svg' ) ); ?>

			</div>
			<?php endif; ?>

			<?php if($settings['badge_align'] == 'right') : ?>
			<div class="bdt-switcher-arrows bdt-arrows-right">

				<?php echo wp_kses( element_pack_svg_icon('right-badge-arrows-'.$settings['arrows_style']), element_pack_allow_tags('svg') ); ?>

			</div>
			<?php endif; ?>

			<div class="bdt-content-switcher-badge">
				<?php echo esc_html($settings['badge_text']); ?>
			</div>
		<?php endif; ?>
		<?php
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$primary   = (isset($settings['switcher_items'][0]) ? $settings['switcher_items'][0] : '');
		$secondary = (isset($settings['switcher_items'][1]) ? $settings['switcher_items'][1] : '');

		$this->add_render_attribute('content-switcher', 'class', 'bdt-content-switcher');
		$this->add_render_attribute('content-switcher', [
			'data-settings' => [
				wp_json_encode(array_filter([
					'id' => '#bdt-content-switcher-' . $this->get_id(),
					'switcherStyle' => $settings['switcher_style'],
				]))
			]
		]);

		// Collect section IDs and widget IDs
		$linked_sections = [];
		$linked_widgets = [];
		
		foreach ($settings['switcher_items'] as $index => $item) {
			if ('link_section' === $item['content_type'] && !empty($item['link_section_id'])) {
				$linked_sections[$index] = $item['link_section_id'];
			} elseif ('link_widget' === $item['content_type'] && !empty($item['link_widget_id'])) {
				$linked_widgets[$index] = $item['link_widget_id'];
			}
		}
		
		// Add data attributes for sections if needed
		if (!empty($linked_sections)) {
			$this->add_render_attribute('content-switcher', [
				'data-linked-sections' => [
					wp_json_encode([
						'id' => $this->get_id(),
						'sections' => $linked_sections,
						'positionUnchanged' => $settings['content_position_unchanged'] == 'yes' ? true : false
					])
				]
			]);
		}
		
		// Add data attributes for widgets if needed
		if (!empty($linked_widgets)) {
			$this->add_render_attribute('content-switcher', [
				'data-linked-widgets' => [
					wp_json_encode([
						'id' => $this->get_id(),
						'widgets' => $linked_widgets
					])
				]
			]);
		}

		?>

		<div <?php $this->print_render_attribute_string('content-switcher'); ?>>
			<div class="bdt-switch-container-wrap">

				<?php if ('button' !== $settings['switcher_style']) : ?>

					<?php $this->render_badge(); ?>

					<?php if (!empty($primary['title']) or !empty($primary['switcher_icon']['value'])) : ?>
					<div class="bdt-package-text bdt-primary-text <?php echo esc_attr(($primary['switcher_active'] == 'yes') ? 'bdt-active' : '') ?>">

						<?php if (!empty($primary['switcher_icon']['value'])) : ?>
						<span class="bdt-content-switcher-icon bdt-primary-icon <?php echo esc_attr(($primary['switcher_active'] == 'yes') ? 'bdt-active' : ''); ?>">
							<?php Icons_Manager::render_icon($primary['switcher_icon'], ['aria-hidden' => 'true']); ?>
						</span>
						<?php endif; ?>

						<?php if (!empty($primary['title'])) : ?>
							<?php echo esc_html($primary['title']); ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>

					<div class="bdt-switch-container button bdt-toggle-button-<?php echo esc_attr($settings['switcher_style']); ?>">
						<input type="checkbox" class="checkbox" <?php echo esc_attr(($secondary['switcher_active'] == 'yes') ? 'checked' : ''); ?>>
						<div class="bdt-knobs">
							<span></span>
						</div>
						<div class="bdt-layer"></div>
					</div>

					<?php if (!empty($secondary['title']) or !empty($secondary['switcher_icon']['value'])) : ?>
					<div class="bdt-package-text bdt-secondary-text <?php echo esc_attr(($secondary['switcher_active'] == 'yes') ? 'bdt-active' : '') ?>">

						<?php if (!empty($secondary['switcher_icon']['value'])) : ?>
						<span class="bdt-content-switcher-icon bdt-secondary-icon <?php echo esc_attr(($secondary['switcher_active'] == 'yes') ? 'bdt-active' : ''); ?>">
							<?php Icons_Manager::render_icon($secondary['switcher_icon'], ['aria-hidden' => 'true']); ?>
						</span>
						<?php endif; ?>

						<?php if (!empty($secondary['title'])) : ?>
							<?php echo esc_html($secondary['title']); ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>

				<?php endif; ?>

				<?php if ('button' == $settings['switcher_style']) :
					foreach ($settings['switcher_items'] as $item) :
						$this->add_render_attribute('button', 'class', esc_attr(($item['switcher_active'] == 'yes') ? 'bdt-content-switcher-tab bdt-active' : 'bdt-content-switcher-tab'), true);

						if (!empty($item['_id'])) {
							$this->add_render_attribute('button', 'id', $this->get_id() . esc_attr($item['_id']), true);
						}

						?>
						
						<a href="javascript:void(0);" <?php $this->print_render_attribute_string('button'); ?>>
							<?php if (!empty($item['switcher_icon']['value'])) : ?>
								<span class="bdt-content-switcher-icon bdt-item-icon">
									<?php Icons_Manager::render_icon($item['switcher_icon'], ['aria-hidden' => 'true']); ?>
								</span>
							<?php endif; ?>
							<?php echo esc_html($item['title']); ?>
						</a>
					<?php endforeach; ?>

					<?php $this->render_badge(); ?>

				<?php endif; ?>

			</div>

			<!-- Content Switcher Content -->
			<div class="bdt-switcher-content-wrapper">

				<?php if ('button' !== $settings['switcher_style']) : ?>

					<div class="bdt-switcher-content bdt-primary <?php echo esc_attr(($primary['switcher_active'] == 'yes') ? 'bdt-active' : ''); ?>">
						<?php
						if ($primary['content_type'] == 'content') {
							echo wp_kses_post($primary['content']);
						} elseif ($primary['content_type'] == 'template') {
							// PHPCS - should not be escaped.
							echo Element_Pack_Loader::elementor()->frontend->get_builder_content_for_display($primary['saved_templates']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} elseif ($primary['content_type'] == 'link_section' && !empty($primary['link_section_id']) && $settings['content_position_unchanged'] !== 'yes') {
							echo '<div class="bdt-switcher-item-content-section bdt-switcher-item-' . esc_attr($primary['link_section_id']) . '"></div>';
						} elseif ($primary['content_type'] == 'link_widget' && !empty($primary['link_widget_id'])) {
							// Widget linked content is handled by JavaScript
							echo '<div class="bdt-switcher-item-linked-widget"></div>';
						} elseif ($primary['content_type'] == 'price_card') {
							$this->render_price($primary);
						}
							// Widget linked content is handled by JavaScript
						?>
					</div>
					<div class="bdt-switcher-content bdt-secondary <?php echo esc_attr(($secondary['switcher_active'] == 'yes') ? 'bdt-active' : ''); ?>">
						<?php
						if ($secondary['content_type'] == 'content') {
							echo wp_kses_post($secondary['content']);
						} elseif ($secondary['content_type'] == 'template') {
							// PHPCS - should not be escaped.
							echo Element_Pack_Loader::elementor()->frontend->get_builder_content_for_display($secondary['saved_templates']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} elseif ($secondary['content_type'] == 'link_section' && !empty($secondary['link_section_id']) && $settings['content_position_unchanged'] !== 'yes') {
							echo '<div class="bdt-switcher-item-content-section bdt-switcher-item-' . esc_attr($secondary['link_section_id']) . '"></div>';
						} elseif ($secondary['content_type'] == 'link_widget' && !empty($secondary['link_widget_id'])) {
							// Widget linked content is handled by JavaScript
							echo '<div class="bdt-switcher-item-linked-widget"></div>';
						} elseif ($secondary['content_type'] == 'price_card') {
							$this->render_price($secondary);
						}
						?>
					</div>
			

				<?php endif; ?>

				<?php if ('button' == $settings['switcher_style']) :
					foreach ($settings['switcher_items'] as $item) :
						$this->add_render_attribute('switcher_content', 'class', esc_attr(($item['switcher_active'] == 'yes') ? 'bdt-switcher-content bdt-active' : 'bdt-switcher-content'), true);

						if (!empty($item['_id'])) {
							$this->add_render_attribute('switcher_content', 'data-content-id', $this->get_id() . esc_attr($item['_id']), true);
						}
						?>

						<div <?php $this->print_render_attribute_string('switcher_content'); ?>>
							<?php
							if ($item['content_type'] == 'content') {
								echo wp_kses_post($item['content']);
							} elseif ($item['content_type'] == 'template') {
								// PHPCS - should not be escaped.
								echo Element_Pack_Loader::elementor()->frontend->get_builder_content_for_display($item['saved_templates']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} elseif ($item['content_type'] == 'link_section' && !empty($item['link_section_id']) && $settings['content_position_unchanged'] !== 'yes') {
								echo '<div class="bdt-switcher-item-content-section bdt-switcher-item-' . esc_attr($item['link_section_id']) . '"></div>';
							} elseif ($item['content_type'] == 'link_widget' && !empty($item['link_widget_id'])) {
								// Widget linked content is handled by JavaScript
								echo '<div class="bdt-switcher-item-linked-widget"></div>';
							} elseif ($item['content_type'] == 'price_card') {
								$this->render_price($item);
							}
							?>
						</div>

					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
