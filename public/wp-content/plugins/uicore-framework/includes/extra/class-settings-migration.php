<?php

namespace UiCore;
defined('ABSPATH') || exit();

// Here we store and define all the needed settings
class SettingsMigration
{

  const MIGRATIONS = [
    '6.2.0',
    '6.3.0',
  ];

  public static function migrate($settings, $force = false)
  {
    foreach (self::MIGRATIONS as $version) {
      $method = 'migrate_' . str_replace('.', '_', $version);
      if($force || (UICORE_VERSION >= $version && method_exists(__CLASS__, $method) && !get_option('uicore_settings_migrated_' . $version))) {
        $settings = self::$method($settings);
        update_option('uicore_settings_migrated_' . $version, true);
      }
    }
    return $settings;
  }

    private static function migrate_6_2_0($settings)
    {
        \error_log('UiCore: Migrating settings to 6.2.0');
        // Typography keys to migrate
        $typography_keys = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'blog_h1', 'blog_h2', 'blog_h3', 'blog_h4', 'blog_h5', 'blog_h6', 'blog_p'];
        foreach ($typography_keys as $key) {
            if (!isset($settings[$key])) continue;
            // s
            if (isset($settings[$key]['s'])) {
                foreach (['d', 't', 'm'] as $device) {
                    if (isset($settings[$key]['s'][$device]) && !is_array($settings[$key]['s'][$device])) {
                        $settings[$key]['s'][$device] = [
                            'value' => $settings[$key]['s'][$device],
                            'unit'  => 'px'
                        ];
                    }
                }
            }
            // h
            if (isset($settings[$key]['h']) && (!is_array($settings[$key]['h']) || (is_array($settings[$key]['h']) && !isset($settings[$key]['h']['d']['value'])))) {
                $h_val = $settings[$key]['h'];
                $settings[$key]['h'] = [
                    'd' => ['value' => $h_val, 'unit' => 'em'],
                    't' => ['value' => $h_val, 'unit' => 'em'],
                    'm' => ['value' => $h_val, 'unit' => 'em'],
                ];
            }
            if (isset($settings[$key]['ls']) && (!is_array($settings[$key]['ls']) || (is_array($settings[$key]['ls']) && !isset($settings[$key]['ls']['d']['value'])))) {
                $ls_val = $settings[$key]['ls'];
                $settings[$key]['ls'] = [
                    'd' => ['value' => $ls_val, 'unit' => 'em'],
                    't' => ['value' => $ls_val, 'unit' => 'em'],
                    'm' => ['value' => $ls_val, 'unit' => 'em'],
                ];
            }
        }
        return $settings;
    }

    private static function migrate_6_3_0($settings)
    {
        \error_log('UiCore: Migrating settings to 6.3.0');

        if (!class_exists('\Elementor\Plugin')) {
            return $settings;
        }

        // merge elementor custom colors into theme options cusotm colors
         $kit_id = get_option('elementor_active_kit');
        if (!$kit_id) {
            $kit_id = Settings::create_default_kit();
        }
        if (is_wp_error($kit_id)) {
            return;
        }
        $meta_old = get_post_meta($kit_id, '_elementor_page_settings', true);
        // \error_log(print_r($meta_old['custom_colors'], true));
        // \error_log(print_r($settings['custom_colors'], true));

        if (isset($meta_old['custom_colors']) && is_array($meta_old['custom_colors'])) {
            $custom_colors = isset($settings['custom_colors']) && is_array($settings['custom_colors']) ? $settings['custom_colors'] : [];

            // Convert all to a common format for union (id, value, label)
            $all_colors = [];
            $seen_ids = [];
            $seen_values = [];

            // Add Elementor colors
            foreach ($meta_old['custom_colors'] as $color) {
                $id = isset($color['_id']) ? $color['_id'] : null;
                $value = isset($color['color']) ? $color['color'] : null;
                $label = isset($color['title']) ? $color['title'] : '';
                if ($id && !in_array($id, $seen_ids, true) && $value && !in_array($value, $seen_values, true)) {
                    $all_colors[] = [
                        'id' => $id,
                        'value' => $value,
                        'label' => $label,
                    ];
                    $seen_ids[] = $id;
                    $seen_values[] = $value;
                }
            }
            // Add theme colors
            foreach ($custom_colors as $color) {
                $id = isset($color['id']) ? $color['id'] : null;
                $value = isset($color['value']) ? $color['value'] : null;
                $label = isset($color['label']) ? $color['label'] : '';
                if ($id && !in_array($id, $seen_ids, true) && $value && !in_array($value, $seen_values, true)) {
                    $all_colors[] = [
                        'id' => $id,
                        'value' => $value,
                        'label' => $label,
                    ];
                    $seen_ids[] = $id;
                    $seen_values[] = $value;
                }
            }

            // Update both theme and Elementor lists to this union
            $settings['custom_colors'] = $all_colors;
            // Convert to Elementor format
            $elementor_colors = [];
            foreach ($all_colors as $color) {
                $elementor_colors[] = [
                    '_id' => $color['id'],
                    'color' => $color['value'],
                    'title' => $color['label'],
                ];
            }
            $meta_old['custom_colors'] = $elementor_colors;
            // \error_log('Updating elementor kit with new global colors');
            // \error_log(print_r($meta_old['custom_colors'], true));
            update_post_meta($kit_id, '_elementor_page_settings', $meta_old);
            $post_css_file = new \Elementor\Core\Files\CSS\Post($kit_id);
            $post_css_file->update();
        }
        // \error_log('UiCore: Migration to 6.2.10 completed.');
        // \error_log(print_r($settings['custom_colors'], true));
        return $settings;
    }

}