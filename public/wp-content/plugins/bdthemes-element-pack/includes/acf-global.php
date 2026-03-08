<?php
/**
 * ACF Handler
 *
 * Contains helper functions for ACF fields.
 */

 namespace ElementPack\Includes;
 use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ACF_Global.
 */
class ACF_Global {

	/**
	 * Format Acf Options.
	 *
	 * @since 4.4.8
	 * @access public
	 *
	 * @param array $posts query objects - available custom fields -.
	 * @param array $options display options.
	 *
	 * @return array $results formated control options.
	 */
	public static function format_acf_query_result( $posts, $options ) {

		$results         = array();
		$show_type       = $options['show_type'];
		$show_field_type = $options['show_field_type'];
		$show_group      = $options['show_group'];

		foreach ( $posts as $post ) {

			$acf_settings = unserialize( $post->post_content, ['allowed_classes' => false] );

			$acf_type = $show_field_type ? ucwords( $acf_settings['type'] ) . ': ' : '';

			if ( ! in_array( $acf_settings['type'], self::get_allowed_field_types( $options['field_type'] ), true ) ) {
				continue; }

			$acf_group = $show_group ? ' ( ' . get_the_title( $post->post_parent ) . ' ) ' : '';

			$option_label = $acf_type . $post->post_title . $acf_group;

			$results[ $post->post_name ] = $option_label;
		}

		return $results;
	}

	/**
	 * Get ACF Options Pages Ids.
	 *
	 * List of ids of all options pages registered.
	 *
	 * @access public
	 * @since  4.4.8
	 * @return array $options_page_groups_ids   pages id
	 */
	public static function get_acf_options_pages_ids() {

		$options_page_groups_ids = array();

		if ( function_exists( 'acf_options_page' ) ) {
			$pages = acf_options_page()->get_pages();

			foreach ( $pages as $slug => $page ) {
				$options_page_groups = acf_get_field_groups(
					array(
						'options_page' => $slug,
					)
				);

				foreach ( $options_page_groups as $options_page_group ) {
					$options_page_groups_ids[] = $options_page_group['ID'];
				}
			}
		}

		return $options_page_groups_ids;
	}

	/**
	 * Check if the ACF field is in an options page.
	 *
	 * @access public
	 * @since 4.4.8
	 *
	 * @param int $parent field parent id.
	 * @return bool
	 */
	public static function in_option_page( $parent ) {

		$option_pgs_ids = self::get_acf_options_pages_ids();

		return in_array( $parent, $option_pgs_ids, true );

	}

	/**
	 * Returns allowed field types
	 *
	 * @access public
	 * @since  4.4.8
	 *
	 * @param string $type field category.
	 * @return array
	 */
	public static function get_allowed_field_types( $type ) {

		$default_types = array(
			'textual' => array(
				'text',
				'textarea',
				'number',
				'range',
				'email',
				'url',
				'password',
				'wysiwyg',
			),
			'date'    => array(
				'date_picker',
				'date_time_picker',
			),
			'choice'  => array(
				'select',
				'checkbox',
				'radio',
			),
			'boolean' => array(
				'true_false',
			),
		);

		return $default_types[ $type ];
	}

	/**
	 * Format Acf Values into array ['val : lablel'] || ['val : val']
	 *
	 * @access public
	 * @since 4.4.8
	 *
	 * @param string  $values acf         choice field value/s.
	 * @param string  $return_format      acf field return format.
	 * @param boolean $is_radio           true if the field is radio button.
	 * @param boolean $single_select      true if the field is a select option and multiple value is disabled.
	 *
	 * @return array
	 */
	public static function format_acf_values( $values, $return_format, $is_radio, $single_select = false ) {

		$formated_values = array();

		if ( $is_radio || $single_select ) {

			if ( 'array' === $return_format ) {
				array_push( $formated_values, $values['value'] . ' : ' . $values['label'] );
			} else {
				array_push( $formated_values, $values . ' : ' . $values );
			}
		} else {
			$values = acf_decode_choices( $values );
			foreach ( $values as $index => $value ) {
				if ( 'array' === $return_format ) {
					array_push( $formated_values, $value['value'] . ' : ' . $value['label'] );
				} else {
					array_push( $formated_values, $value . ' : ' . $value );
				}
			}
		}
		return $formated_values;
	}

	/**
	 * Get ACF field value.
	 *
	 * @access public
	 * @since 4.4.8
	 *
	 * @param string $field_key  acf key.
	 * @param int    $parent     acf parent id.
	 */
	public function get_acf_field_value( $field_key, $parent ) {
		if ( self::in_option_page( $parent ) ) {
			return get_field_object( $field_key, 'option' )['value'];
		} else {
			if ( is_preview() ) {
				add_filter( 'acf/pre_load_post_id', array( $this, 'fix_post_id_on_preview' ), 10, 2 );
			}
			return get_field_object( $field_key )['value'];
		}
	}

	/**
	 * Fix PostId conflict on Preview.
	 *
	 * @access public
	 * @since 4.4.8
	 *
	 * @param null $null       $null.
	 * @param int  $post_id    post id.
	 */
	public static function fix_post_id_on_preview( $null, $post_id ) {
		if ( is_preview() ) {
			return get_the_ID();
		} else {
			$acf_post_id = isset( $post_id->ID ) ? $post_id->ID : $post_id;
			if ( ! empty( $acf_post_id ) ) {
				return $acf_post_id;
			} else {
				return $null;
			}
		}
	}

	/**
     * @return array of ACF fields
	 * Third party ACF widget
     */
    public function getAcfFields($field_type)
    {
		$group = false; // New added
        $results = [];

        // ACF Fields saved in the database
        $acf_fields = get_posts(['post_type' => 'acf-field', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'suppress_filters' => false]);
		
        foreach ($acf_fields as $acf_field) {
            $acf_field_parent = false; 
            if ($acf_field->post_parent) {
                $acf_field_parent = get_post($acf_field->post_parent);
                if ($acf_field_parent) {
                    $acf_field_parent_settings = maybe_unserialize($acf_field_parent->post_content);
                }
            }
            $acf_field_settings = maybe_unserialize($acf_field->post_content);
            //if (isset($acf_field_settings['type']) && (empty($types) || in_array($acf_field_settings['type'], $types))) {
            if (isset($acf_field_settings['type']) && in_array($acf_field_settings['type'], $field_type)) {
                if ($group && $acf_field_parent) {
                    if (empty($acf_list[$acf_field_parent->post_excerpt]) || is_array($acf_list[$acf_field_parent->post_excerpt])) {
                        if (isset($acf_field_parent_settings['type']) && $acf_field_parent_settings['type'] == 'group') {
                            $acf_list[$acf_field_parent->post_excerpt]['options'][$acf_field_parent->post_excerpt . '_' . $acf_field->post_excerpt] = $acf_field->post_title;
                        } else {
                            $acf_list[$acf_field_parent->post_excerpt]['options'][$acf_field->post_excerpt] = $acf_field->post_title;
                        }
                        $acf_list[$acf_field_parent->post_excerpt]['label'] = $acf_field_parent->post_title;
                    }
                } else {
                    if ($acf_field_parent) {
                        if (isset($acf_field_parent_settings['type']) && $acf_field_parent_settings['type'] == 'group') {
                            $acf_list[$acf_field_parent->post_excerpt . '_' . $acf_field->post_excerpt] = $acf_field->post_title;
                        } else {
                            $acf_list[$acf_field->post_excerpt] = $acf_field->post_title;
                        }
                    }
                }
            }
        }
        if (!empty($acf_list)) {
            foreach ($acf_list as $akey => $acf) {
                // if (strlen($data['q']) > 2) {
                //     if (strpos($akey, $data['q']) === false && strpos($acf, $data['q']) === false) {
                //         continue;
                //     }
                // }
                $results[] = ['id' => $akey, 'text' => esc_attr($acf)];
            }
        }
        return $results;
    }

	public function get_acf_subfield_label( $repeater_field, $subfield_name, $post_id ) {
		$field = get_field_object( $repeater_field, $post_id );

		if ( empty( $field['sub_fields'] ) ) {
			return '';
		}

		foreach ( $field['sub_fields'] as $sub ) {
			if ( $sub['name'] === $subfield_name ) {
				return $sub['label'];
			}
		}

		return '';
	}

	/**
	 * Get ACF subfield object
	 *
	 * @param string $repeater_field Repeater field name
	 * @param string $subfield_name Subfield name
	 * @param int $post_id Post ID
	 * @return array|null Field object or null
	 */
	public function get_acf_subfield_object( $repeater_field, $subfield_name, $post_id ) {
		if ( ! function_exists( 'get_field_object' ) ) {
			return null;
		}

		$parent_field = get_field_object( $repeater_field, $post_id );
		if ( empty( $parent_field['sub_fields'] ) ) {
			return null;
		}

		foreach ( $parent_field['sub_fields'] as $sub_field ) {
			if ( $sub_field['name'] === $subfield_name ) {
				return $sub_field;
			}
		}

		return null;
	}

	/**
	 * Format ACF field value based on field type
	 *
	 * @param mixed $field_value Field value
	 * @param array $field_object Field object
	 * @return string Formatted value
	 */
	public function format_acf_field_value( $field_value, $field_object ) {
		if ( ! $field_object ) {
			if ( is_array( $field_value ) ) {
				return esc_html( implode( ', ', $field_value ) );
			}
			return wp_kses_post( $field_value );
		}

		$field_type = $field_object['type'] ?? 'text';

		switch ( $field_type ) {
			case 'image':
				return $this->format_image_field( $field_value );

			case 'file':
				return $this->format_file_field( $field_value );

			case 'link':
				return $this->format_link_field( $field_value );

			case 'url':
				return '<a href="' . esc_url( $field_value ) . '" target="_blank">' . esc_html( $field_value ) . '</a>';

			case 'email':
				return '<a href="mailto:' . esc_attr( $field_value ) . '">' . esc_html( $field_value ) . '</a>';

			case 'true_false':
				return $field_value ? __( 'Yes', 'bdthemes-element-pack' ) : __( 'No', 'bdthemes-element-pack' );

			case 'date_picker':
			case 'date_time_picker':
			case 'time_picker':
				return $this->format_date_field( $field_value, $field_object );

			case 'color_picker':
				return $this->format_color_field( $field_value );

			case 'select':
			case 'checkbox':
			case 'radio':
				return $this->format_choice_field( $field_value );

			case 'post_object':
			case 'page_link':
			case 'relationship':
				return $this->format_post_field( $field_value );

			case 'taxonomy':
				return $this->format_taxonomy_field( $field_value );

			case 'user':
				return $this->format_user_field( $field_value );

			case 'gallery':
				return $this->format_gallery_field( $field_value );

			case 'oembed':
				return ! empty( $field_value ) ? '<a href="' . esc_url( $field_value ) . '" target="_blank">' . esc_html( $field_value ) . '</a>' : '';

			case 'wysiwyg':
				return wp_kses_post( $field_value );

			case 'number':
			case 'range':
				return esc_html( number_format_i18n( $field_value ) );

			case 'password':
				return '••••••••';

			case 'group':
				return $this->format_group_field( $field_value );

			case 'text':
			case 'textarea':
			default:
				if ( is_array( $field_value ) ) {
					return esc_html( implode( ', ', $field_value ) );
				}
				return wp_kses_post( $field_value );
		}
	}

	/**
	 * Format image field
	 */
	private function format_image_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$image_url = $field_value['url'] ?? '';
			$image_alt = $field_value['alt'] ?? '';
			return '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image_alt ) . '" style="max-width: 100px; height: auto;">';
		} elseif ( is_numeric( $field_value ) ) {
			$image_url = wp_get_attachment_url( $field_value );
			return '<img src="' . esc_url( $image_url ) . '" alt="" style="max-width: 100px; height: auto;">';
		}
		return '';
	}

	/**
	 * Format file field
	 */
	private function format_file_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$file_url = $field_value['url'] ?? '';
			$file_title = $field_value['title'] ?? __( 'Download', 'bdthemes-element-pack' );
			return '<a href="' . esc_url( $file_url ) . '" target="_blank">' . esc_html( $file_title ) . '</a>';
		} elseif ( is_numeric( $field_value ) ) {
			$file_url = wp_get_attachment_url( $field_value );
			return '<a href="' . esc_url( $file_url ) . '" target="_blank">' . __( 'Download', 'bdthemes-element-pack' ) . '</a>';
		}
		return '';
	}

	/**
	 * Format link field
	 */
	private function format_link_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$url = $field_value['url'] ?? '';
			$title = $field_value['title'] ?? $url;
			$target = ! empty( $field_value['target'] ) ? ' target="' . esc_attr( $field_value['target'] ) . '"' : '';
			return '<a href="' . esc_url( $url ) . '"' . $target . '>' . esc_html( $title ) . '</a>';
		}
		return '';
	}

	/**
	 * Format date field
	 */
	private function format_date_field( $field_value, $field_object ) {
		if ( ! empty( $field_value ) ) {
			$format = $field_object['return_format'] ?? 'Y-m-d';
			if ( is_numeric( $field_value ) ) {
				return date_i18n( $format, $field_value );
			}
			return esc_html( $field_value );
		}
		return '';
	}

	/**
	 * Format color field
	 */
	private function format_color_field( $field_value ) {
		if ( ! empty( $field_value ) ) {
			return '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr( $field_value ) . '; border: 1px solid #ddd; vertical-align: middle; margin-right: 5px;"></span>' . esc_html( $field_value );
		}
		return '';
	}

	/**
	 * Format choice field (select, checkbox, radio)
	 */
	private function format_choice_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$values = [];
			foreach ( $field_value as $val ) {
				if ( is_array( $val ) && isset( $val['label'] ) ) {
					$values[] = $val['label'];
				} else {
					$values[] = $val;
				}
			}
			return esc_html( implode( ', ', $values ) );
		}
		return esc_html( $field_value );
	}

	/**
	 * Format post field (post_object, page_link, relationship)
	 */
	private function format_post_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$post_titles = [];
			foreach ( $field_value as $post_item ) {
				if ( is_object( $post_item ) && isset( $post_item->post_title ) ) {
					$post_titles[] = '<a href="' . get_permalink( $post_item->ID ) . '">' . esc_html( $post_item->post_title ) . '</a>';
				} elseif ( is_numeric( $post_item ) ) {
					$post_titles[] = '<a href="' . get_permalink( $post_item ) . '">' . esc_html( get_the_title( $post_item ) ) . '</a>';
				}
			}
			return implode( ', ', $post_titles );
		} elseif ( is_object( $field_value ) && isset( $field_value->post_title ) ) {
			return '<a href="' . get_permalink( $field_value->ID ) . '">' . esc_html( $field_value->post_title ) . '</a>';
		} elseif ( is_numeric( $field_value ) ) {
			return '<a href="' . get_permalink( $field_value ) . '">' . esc_html( get_the_title( $field_value ) ) . '</a>';
		}
		return '';
	}

	/**
	 * Format taxonomy field
	 */
	private function format_taxonomy_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$term_names = [];
			foreach ( $field_value as $term ) {
				if ( is_object( $term ) && isset( $term->name ) ) {
					$term_names[] = esc_html( $term->name );
				} elseif ( is_numeric( $term ) ) {
					$term_obj = get_term( $term );
					if ( $term_obj && ! is_wp_error( $term_obj ) ) {
						$term_names[] = esc_html( $term_obj->name );
					}
				}
			}
			return implode( ', ', $term_names );
		} elseif ( is_object( $field_value ) && isset( $field_value->name ) ) {
			return esc_html( $field_value->name );
		}
		return '';
	}

	/**
	 * Format user field
	 */
	private function format_user_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$user_names = [];
			foreach ( $field_value as $user ) {
				if ( is_object( $user ) && isset( $user->display_name ) ) {
					$user_names[] = esc_html( $user->display_name );
				} elseif ( is_numeric( $user ) ) {
					$user_data = get_userdata( $user );
					if ( $user_data ) {
						$user_names[] = esc_html( $user_data->display_name );
					}
				}
			}
			return implode( ', ', $user_names );
		} elseif ( is_object( $field_value ) && isset( $field_value->display_name ) ) {
			return esc_html( $field_value->display_name );
		} elseif ( is_numeric( $field_value ) ) {
			$user_data = get_userdata( $field_value );
			if ( $user_data ) {
				return esc_html( $user_data->display_name );
			}
		}
		return '';
	}

	/**
	 * Format gallery field
	 */
	private function format_gallery_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$gallery_html = '<div style="display: flex; gap: 5px; flex-wrap: wrap;">';
			foreach ( $field_value as $image ) {
				if ( is_array( $image ) && isset( $image['url'] ) ) {
					$gallery_html .= '<img src="' . esc_url( $image['url'] ) . '" alt="" style="max-width: 50px; height: auto;">';
				} elseif ( is_numeric( $image ) ) {
					$image_url = wp_get_attachment_url( $image );
					$gallery_html .= '<img src="' . esc_url( $image_url ) . '" alt="" style="max-width: 50px; height: auto;">';
				}
			}
			$gallery_html .= '</div>';
			return $gallery_html;
		}
		return '';
	}

	/**
	 * Format group field
	 */
	private function format_group_field( $field_value ) {
		if ( is_array( $field_value ) ) {
			$parts = [];
			foreach ( $field_value as $k => $v ) {
				$parts[] = $k . ': ' . ( is_array( $v ) ? wp_json_encode( $v ) : $v );
			}
			return esc_html( implode( ', ', $parts ) );
		}
		return esc_html( $field_value );
	}

}
