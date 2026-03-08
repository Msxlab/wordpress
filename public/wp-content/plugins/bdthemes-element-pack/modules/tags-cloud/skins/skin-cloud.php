<?php

namespace ElementPack\Modules\TagsCloud\Skins;

use Elementor\Skin_Base as Elementor_Skin_Base;
use Elementor\Utils;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Skin_Cloud extends Elementor_Skin_Base
{

    public function get_id()
    {
        return 'bdt-cloud';
    }

    public function get_title()
    {
        return __('Typography', 'bdthemes-element-pack');
    }

    public function render()
    {
        $settings = $this->parent->get_settings_for_display();
        $id = $this->parent->get_id();
        $cloudSkin = 'cloudSkin';

        // Check if using static data source
        if ( isset( $settings['data_source'] ) && $settings['data_source'] === 'static' ) {
            $tag_cloud = $this->render_static_tags();
        } else {
            // Dynamic tags (original functionality)
            $taxonomy_filter = (isset($settings['custom_post_type_input']) && !empty($settings['custom_post_type_input'])) ? $settings['custom_post_type_input'] : 'post_tag';

            $tag_cloud = $this->parent->wp_tag_cloud(
                $cloudSkin,
                array(
                    'taxonomy' => $taxonomy_filter, //$current_taxonomy,
                    'echo' => false,
                    // 'show_count' => '20', //$show_count,
                )
            );
        }

        $cloud_color = $settings['cloud_color'];

        if ($settings['cloud_color'] == 'custom') {
            $cloud_color = (!empty($settings['cloud_custom_color'])) ? $settings['cloud_custom_color'] : '#08AEEC';
        }

        $this->parent->add_render_attribute('skin_typography', 'class', 'bdt-tags-cloud skin-typography');
        $this->parent->add_render_attribute(
            [
                'skin_typography' => [
                    'data-settings' => [
                        wp_json_encode(array_filter([
                            'idCloud'     => 'bdt-cloud-' . $this->parent->get_id(),
                            'cloudColor' => $cloud_color,
                            'cloudStyle' => $settings['cloud_style'],

                        ])),
                    ],
                ],
            ]
        );


?>
        <div <?php $this->parent->print_render_attribute_string('skin_typography'); ?>>
            <div id="bdt-cloud-<?php echo esc_attr($id); ?>" class="bdt-wordcloud">
                <?php echo wp_kses_post($tag_cloud); ?>
            </div>
        </div>



<?php

    }

    /**
     * Render static tags for cloud skin
     */
    protected function render_static_tags() {
        $settings = $this->parent->get_settings_for_display();
        
        if ( empty( $settings['static_tags'] ) ) {
            return '';
        }

        $target = ( isset ( $settings['static_open_new_window'] ) && $settings['static_open_new_window'] == 'yes' ) ? 'target="_blank"' : '';

        // Get all weights to calculate font sizes
        $weights = array();
        foreach ( $settings['static_tags'] as $tag ) {
            $weights[] = isset( $tag['tag_weight'] ) ? $tag['tag_weight'] : 1;
        }

        $min_weight = min( $weights );
        $max_weight = max( $weights );
        $weight_spread = $max_weight - $min_weight;
        if ( $weight_spread <= 0 ) {
            $weight_spread = 1;
        }

        // Font size settings
        $smallest = 12;
        $largest = 120;
        $font_spread = $largest - $smallest;
        $font_step = $font_spread / $weight_spread;

        $output = '';
        $index = 0;

        foreach ( $settings['static_tags'] as $tag ) {
            $index++;
            $tag_text = isset( $tag['tag_text'] ) ? $tag['tag_text'] : '';
            $tag_weight = isset( $tag['tag_weight'] ) ? $tag['tag_weight'] : 1;
            
            // Calculate font size based on weight
            $font_size = $smallest + ( $tag_weight - $min_weight ) * $font_step;
            
            // Get URL
            $url = '#';
            $external = '';
            $nofollow = '';
            
            if ( ! empty( $tag['tag_link']['url'] ) ) {
                $url = $tag['tag_link']['url'];
                
                if ( ! empty( $tag['tag_link']['is_external'] ) ) {
                    $external = 'target="_blank"';
                }
                
                if ( ! empty( $tag['tag_link']['nofollow'] ) ) {
                    $nofollow = 'rel="nofollow"';
                }
            }
            
            // Override with static_open_new_window setting if set
            if ( $target ) {
                $external = $target;
            }
            
            $role = ( $url === '#' ) ? ' role="button"' : '';
            
            $output .= sprintf(
                '<span data-weight="%4$s"><a href="%1$s"%2$s %3$s class="%5$s" %6$s %7$s>%8$s</a> </span>',
                esc_url( $url ),
                $role,
                $external,
                esc_attr( str_replace( ',', '.', $font_size ) ),
                esc_attr( 'tag-cloud-link tag-link-' . $index . ' tag-link-position-' . $index ),
                '',
                $nofollow,
                esc_html( $tag_text )
            );
        }

        return $output;
    }
}
