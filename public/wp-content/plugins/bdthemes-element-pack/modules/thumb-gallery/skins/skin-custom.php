<?php

namespace ElementPack\Modules\ThumbGallery\Skins;

use Elementor\Repeater;
use ElementPack\Base\Module_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

use ElementPack\Utils;

use Elementor\Skin_Base as Elementor_Skin_Base;

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

class Skin_Custom extends Elementor_Skin_Base {
	public function get_id() {
		return 'bdt-custom';
	}

	public function get_title() {
		return __( 'Custom', 'bdthemes-element-pack' );
	}


	public function render_image( $image, $size ) {
		$image_url = wp_get_attachment_image_src( $image['gallery_image']['id'], $size );

		$image_url = ( '' != $image_url ) ? $image_url[0] : $image['gallery_image']['url'];

		return $image_url;
	}

	public function render_title( $title ) {
		$settings = $this->parent->get_settings_for_display();

		if ( ! $this->parent->get_settings_for_display( 'show_title' ) ) {
			return;
		}

		$tag     = $this->parent->get_settings_for_display( 'title_tag' );
		$classes = [ 'bdt-thumb-gallery-title' ];
		?>

		<?php if ( 'yes' == $settings['title_link_option'] ) { ?>
			<a href="<?php echo esc_url( get_permalink() ); ?>">
			<?php } ?>

			<<?php echo esc_attr( Utils::get_valid_html_tag( $tag ) ); ?> class="
				<?php echo wp_kses_post( implode( " ", $classes ) ); ?>">
				<?php echo esc_html( $title['image_title'] ); ?>
			</<?php echo esc_attr( Utils::get_valid_html_tag( $tag ) ); ?>>

			<?php if ( 'yes' == $settings['title_link_option'] ) { ?>
			</a>
		<?php } ?>

		<?php
	}

	public function render_text( $text ) {
		if ( ! $this->parent->get_settings_for_display( 'show_text' ) ) {
			return;
		}

		?>
		<div class="bdt-thumb-gallery-text bdt-text-small">
			<?php echo wp_kses_post( $text['image_text'] ); ?>
		</div>
		<?php
	}

	public function render_button() {
		if ( ! $this->parent->get_settings_for_display( 'show_button' ) ) {
			return;
		}

		$settings = $this->parent->get_settings_for_display();
		$instance = $settings['link'];

		$this->parent->add_render_attribute(
			[ 
				'thumb-gallery-button' => [ 
					'class'  => [ 
						'bdt-thumb-gallery-button',
						'bdt-display-inline-block',
						$settings['button_hover_animation'] ? 'elementor-animation-' . $settings['button_hover_animation'] : '',
					],
					'href'   => empty( $instance['url'] ) ? '#' : esc_url( $instance['url'] ),
					'target' => $instance['is_external'] ? '_blank' : '_self'
				]
			], '', '', true
		);

		if ( ! isset( $settings['icon'] ) && ! Icons_Manager::is_migration_allowed() ) {
			// add old default
			$settings['icon'] = 'fas fa-arrow-right';
		}

		$migrated = isset( $settings['__fa4_migrated']['thumb_gallery_icon'] );
		$is_new   = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();

		?>
		<div>
			<a <?php $this->parent->print_render_attribute_string( 'thumb-gallery-button' ); ?>>
				<?php echo esc_html( $settings['button_text'] ); ?>

				<?php if ( $settings['thumb_gallery_icon']['value'] ) : ?>
					<span class="bdt-button-icon-align-<?php echo esc_attr( $settings['icon_align'] ); ?>">

						<?php if ( $is_new || $migrated ) :
							Icons_Manager::render_icon( $settings['thumb_gallery_icon'], [ 'aria-hidden' => 'true', 'class' => 'fa-fw' ] );
						else : ?>
							<i class="<?php echo esc_attr( $settings['icon'] ); ?>" aria-hidden="true"></i>
						<?php endif; ?>

					</span>
				<?php endif; ?>

			</a>
		</div>
		<?php
	}

	public function render_loop_items() {
		$settings             = $this->parent->get_settings_for_display();
		$gallery              = $settings['gallery'];
		$content_transition   = $settings['content_transition'] ? ' bdt-transition-' . $settings['content_transition'] : '';
		$slideshow_fullscreen = $settings['slideshow_fullscreen'] ? ' bdt-height-viewport="offset-top: true"' : '';
		$kenburns_reverse     = $settings['kenburns_reverse'] ? ' bdt-animation-reverse' : '';

		?>
		<ul class="bdt-slideshow-items" <?php echo esc_attr( $slideshow_fullscreen ); ?>>
			<?php
			foreach ( $gallery as $item ) :

				$gallery_image = $this->render_image( $item, 'full' );
				?>
				<li>
					<?php if ( $settings['kenburns_animation'] ) : ?>
						<div
							class="bdt-position-cover bdt-animation-kenburns<?php echo esc_attr( $kenburns_reverse ); ?> bdt-transform-origin-center-left">
						<?php endif; ?>

						<img src="<?php echo esc_url( $gallery_image ); ?>" alt="<?php echo esc_html( get_the_title() ); ?>">

						<?php if ( $settings['kenburns_animation'] ) : ?>
						</div>
					<?php endif; ?>
					<div
						class="bdt-position-z-index bdt-position-<?php echo esc_attr( $settings['content_position'] ); ?> bdt-position-large">

						<?php if ( $settings['show_title'] || $settings['show_text'] || $settings['show_button'] ) : ?>
							<div class="bdt-text-<?php echo esc_attr( $settings['content_align'] ); ?>">
								<div class="bdt-thumb-gallery-content<?php echo esc_attr( $content_transition ); ?>">
									<?php $this->render_title( $item ); ?>
									<?php $this->render_text( $item ); ?>
									<?php $this->render_button(); ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</li>
				<?php
			endforeach;
			?>
		</ul>
		<?php
	}

	public function render() {
		$this->parent->render_header();
		$this->render_loop_items();
		$this->parent->render_navigation();
		$this->render_thumbnavs();
		$this->parent->render_footer();
	}

	public function render_thumbnavs() {
		$settings = $this->parent->get_settings_for_display();

		if ( 'arrows' == $settings['navigation'] || 'none' == $settings['navigation'] ) {
			return;
		}

		$thumbnavs_outside = '';
		$vertical_thumbnav = '';

		if ( 'center-left' == $settings['thumbnavs_position'] || 'center-right' == $settings['thumbnavs_position'] ) {
			if ( $settings['thumbnavs_outside'] ) {
				$thumbnavs_outside = '-out';
			}
			$vertical_thumbnav = ' bdt-thumbnav-vertical';
		}

		?>
		<div
			class="bdt-thumbnav-wrapper bdt-position-<?php echo esc_attr( $settings['thumbnavs_position'] . $thumbnavs_outside ); ?> bdt-position-small">
			<ul class="bdt-thumbnav<?php echo esc_attr( $vertical_thumbnav ); ?>">

				<?php
				$bdt_counter   = 0;
				$gallery_thumb = $settings['gallery'];

				foreach ( $gallery_thumb as $item ) :

					$gallery_thumbnail = $this->render_image( $item, 'thumbnail' );
					echo '<li class="bdt-thumb-gallery-thumbnav" data-bdt-slideshow-item="' . esc_attr( $bdt_counter ) . '"><a class="bdt-overflow-hidden bdt-background-cover" href="#" style="background-image: url(' . esc_url( $gallery_thumbnail ) . ')"></a></li>';
					$bdt_counter++;

				endforeach; ?>
			</ul>
		</div>
		<?php
	}
}