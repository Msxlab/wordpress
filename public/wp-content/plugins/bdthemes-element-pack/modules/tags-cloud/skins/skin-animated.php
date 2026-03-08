<?php

namespace ElementPack\Modules\TagsCloud\Skins;

use Elementor\Controls_Manager;
use ElementPack\Base\Module_Base;
use Elementor\Skin_Base as Elementor_Skin_Base;





if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class Skin_Animated extends Elementor_Skin_Base
{

    public function get_id()
    {
        return 'bdt-animated';
    }

    public function get_title()
    {
        return __('Animated', 'bdthemes-element-pack');
    }


    public function render()
    {

        $settings = $this->parent->get_settings_for_display();
        $id = $this->parent->get_id();

        // Check if using static data source
        if ( isset( $settings['data_source'] ) && $settings['data_source'] === 'static' ) {
            $tag_cloud = $this->render_static_tags();
        } else {
            // Dynamic tags (original functionality)
            $taxonomy_filter = (isset($settings['custom_post_type_input']) && !empty($settings['custom_post_type_input'])) ? $settings['custom_post_type_input'] : 'post_tag';

            $cloudSkin = 'animaitedTags';
            $tag_cloud = $this->parent->wp_tag_cloud(
                $cloudSkin,
                array(
                    'taxonomy' => $taxonomy_filter, //$current_taxonomy,
                    'echo' => false,
                    // 'show_count' => '20', //$show_count,
                )
            );
        }

        $globe_height = (isset($settings['globe_height']['size']) && !empty($settings['globe_height']['size']))
            ? $settings['globe_height']['size'] : 350;

        // Ensure animation always runs: provide a default non-zero speed and initial spin
        $computed_max_speed = ( isset($settings['globe_animation_speed']['size']) && $settings['globe_animation_speed']['size'] > 0 )
            ? ($settings['globe_animation_speed']['size'] / 1000)
            : 0.05; // fallback default

        $this->parent->add_render_attribute('tag_animated', 'class', 'bdt-tags-cloud skin-tag-animated');
        $this->parent->add_render_attribute('tag_animated', 'id', 'bdt-canvas-' . $this->parent->get_id());
                $this->parent->add_render_attribute(
            [
                'tag_animated' => [
                    'data-settings' => [
                        wp_json_encode([
                            'idCanvas'           => 'bdt-canvas-' . $this->parent->get_id(),
                            'idTags'             => 'bdt-tags-' . $this->parent->get_id(),
                            'idmyCanvas'         => 'bdt-myCanvas-' . $this->parent->get_id(),
                            'textColour'         => !empty($settings['globe_color']) ? $settings['globe_color'] : '#111111',
                            'outlineColour'      => $settings['globe_outline_colour'],
                            'reverse'            => true,
                            'depth'              => $settings['globe_depth']['size'] / 100,
                            'maxSpeed'           => $settings['globe_animation_speed']['size'] / 1000,
                            'activeCursor'       => $settings['globe_active_cursor'],
                            'bgColour'           => $settings['globe_text_bg'],
                            'bgOutlineThickness' => $settings['globe_bg_outline_thickness']['size'],
                            'bgRadius'           => $settings['globe_text_bg_radius']['size'],
                            // Animation type control
                            'animationType'      => isset($settings['globe_animation_type']) ? $settings['globe_animation_type'] : 'hover',
                            'dragControl'        => ($settings['globe_drag_control'] == true) ? true : false,
                            'fadeIn'             => $settings['globe_fade_in']['size'] * 1000,
                            'freezeActive'       => ($settings['globe_freeze_active'] == true) ? true : false,
                            'outlineDash'        => $settings['globe_outline_dash']['size'],
                            'outlineDashSpace'   => $settings['globe_outline_dash_space']['size'],
                            'outlineDashSpeed'   => $settings['globe_outline_dash_speed']['size'],
                            'outlineIncrease'    => $settings['globe_outline_increase']['size'],
                            'outlineMethod'      => $settings['globe_outline_method'],
                            'outlineRadius'      => $settings['globe_outline_border_radius']['size'],
                            'outlineThickness'   => $settings['globe_outline_thickness']['size'],
                            'shadow'             => $settings['globe_shadow_color'],
                            'shadowBlur'         => $settings['globe_shadow_blur']['size'],
                            'wheelZoom'          => ($settings['globe_wheel_zoom'] == true) ? true : false
                        ]),
                    ],
                ],
            ]
        );

?>

        <div <?php $this->parent->print_render_attribute_string('tag_animated'); ?>>
            <canvas height="<?php echo esc_attr($globe_height) . 'px'; ?>" width="<?php echo esc_attr($globe_height) . 'px'; ?>" id="bdt-myCanvas-<?php echo esc_attr($id); ?>">
                <p>Anything in here will be replaced on browsers that support the canvas element</p>
            </canvas>
            <div id="bdt-tags-<?php echo esc_attr($id); ?>" style="display:none">
                <ul>
                    <?php
                    echo wp_kses_post($tag_cloud);
                    ?>
                </ul>
            </div>
        </div>

<?php
    }

    /**
     * Render static tags for animated skin
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
                '<li><a href="%1$s"%2$s class="%3$s" %4$s data-weight="%5$s"%6$s %7$s>%8$s</a></li>',
                esc_url( $url ),
                $role,
                esc_attr( 'tag-cloud-link tag-link-' . $index . ' tag-link-position-' . $index ),
                $external,
                esc_attr( str_replace( ',', '.', $font_size ) ),
                '',
                $nofollow,
                esc_html( $tag_text )
            );
        }

        return $output;
    }
}
