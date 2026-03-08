<?php

namespace ElementPack\Admin;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Permission Manager Class
 * Handles role-based and multisite permission management for Element Pack widgets
 */
class ElementPack_Permission_Manager
{

    public function __construct()
    {
        // Register AJAX handlers only if user has proper license
        if ($this->is_permission_manager_license()) {
            add_action('wp_ajax_ep_get_role_elements', array($this, 'get_role_elements_ajax'));
            add_action('wp_ajax_ep_save_role_elements', array($this, 'save_role_elements_ajax'));
            add_action('wp_ajax_ep_reset_role_elements', array($this, 'reset_role_elements_ajax'));

            // Enqueue Permission Manager scripts
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

            // Localize script data
            add_action('admin_enqueue_scripts', array($this, 'localize_scripts'));
        }
    }

    /**
     * Get allowed widgets for user
     * 
     * @param string $widget_name
     * @return bool
     */
    public function bdt_get_allowed_widgets_for_user($widget_name)
    {
        // If license check fails → always allow
        if (!$this->is_permission_manager_license()) {
            return true;
        }

        /**
         * 🔹 Multisite restrictions
         */
        if (is_multisite()) {

            // Main site admin → always allowed
            if (is_main_site() && current_user_can('administrator')) {
                return true;
            }

            $current_blog_id = get_current_blog_id();

            // Get subsite elements from the main site
            $main_site_id = get_main_site_id();
            switch_to_blog($main_site_id);
            $subsite_elements = get_option('ep_subsite_elements', []);
            restore_current_blog();

            // No restrictions configured → allow everything
            if (empty($subsite_elements)) {
                return true;
            }

            // Subsite-specific restriction
            return !empty($subsite_elements[$current_blog_id][$widget_name])
                && $subsite_elements[$current_blog_id][$widget_name] === 'on';
        }

        /**
         * 🔹 Role-based restrictions
         */
        $role_elements = get_option('ep_role_elements', []);

        // No role restrictions or admin → allow everything
        if (empty($role_elements) || current_user_can('administrator')) {
            return true;
        }

        $current_user = wp_get_current_user();
        $user_roles = (array) $current_user->roles;

        $allowed_widgets = [];

        foreach ($user_roles as $role) {
            if (!empty($role_elements[$role]) && is_array($role_elements[$role])) {
                foreach ($role_elements[$role] as $role_element => $status) {
                    if ($status === 'on') {
                        $allowed_widgets[] = $role_element;
                    }
                }
            }
        }

        $allowed_widgets = array_unique($allowed_widgets);

        return in_array($widget_name, $allowed_widgets, true);
    }


    /**
     * Check if current license supports Permission Manager functionality
     * Only agency and developer license holders can access Permission Manager
     * 
     * @access private
     * @return bool
     */
    public function is_permission_manager_license()
    {
        $license_info = \ElementPack\Base\Element_Pack_Base::get_register_info();

        // Check if license info is valid and available
        if (
            empty($license_info) ||
            !is_object($license_info) ||
            empty($license_info->license_title) ||
            empty($license_info->is_valid)
        ) {
            return false;
        }

        $license_title = strtolower($license_info->license_title);

        // Check for agency or developer license
        if (
            strpos($license_title, 'agency') !== false ||
            strpos($license_title, 'developer') !== false
        ) {
            return true;
        }

        return false;
    }

    /**
     * Render license requirement notice for Permission Manager
     * 
     * @access private
     * @return void
     */
    private function render_license_requirement_notice()
    {
        ?>
        <div class="ep-dashboard-panel">
            <!-- <div class="bdt-container bdt-container-xlarge"> -->
            <div class="bdt-grid bdt-grid-stack" bdt-grid>
                <div class="bdt-width-expand">

                    <!-- Permission Manager Header -->
                    <!-- <div class="ep-dashboard-header">
                            <h2 class="ep-dashboard-title"><?php esc_html_e('Permission Manager', 'bdthemes-element-pack'); ?></h2>
                            <p class="ep-dashboard-subtitle"><?php esc_html_e('Advanced role-based widget permission management for Element Pack.', 'bdthemes-element-pack'); ?></p>
                        </div> -->

                    <!-- License Requirement Notice -->
                    <div class="ep-license-requirement-notice">
                        <!-- <div class="bdt-card bdt-card-body"> -->
                        <div class="bdt-alert bdt-alert-warning" bdt-alert>
                            <h3 style="margin-top: 0;"><i class="dashicons dashicons-admin-network"></i>
                                <?php esc_html_e('Agency or Developer License Required', 'bdthemes-element-pack'); ?></h3>
                            <p style="font-size: 16px; line-height: 1.6;">
                                <?php esc_html_e('The Permission Manager is an advanced feature exclusively available for Agency and Developer license holders. This powerful tool allows you to:', 'bdthemes-element-pack'); ?>
                            </p>

                            <ul style="margin: 20px 0; padding-left: 20px;">
                                <li><?php esc_html_e('Control widget access by user roles', 'bdthemes-element-pack'); ?></li>
                                <li><?php esc_html_e('Manage permissions across multisite networks', 'bdthemes-element-pack'); ?>
                                </li>
                                <li><?php esc_html_e('Create custom permission profiles for different user types', 'bdthemes-element-pack'); ?>
                                </li>
                                <li><?php esc_html_e('Enhance security and control in client projects', 'bdthemes-element-pack'); ?>
                                </li>
                            </ul>

                            <div style="margin-top: 25px;">
                                <a href="https://elementpack.pro/pricing/" target="_blank" class="bdt-button bdt-button-primary"
                                    style="margin-right: 10px; border-radius: 6px;">
                                    <?php esc_html_e('Upgrade License', 'bdthemes-element-pack'); ?>
                                </a>
                            </div>

                            <?php
                            // Show current license info if available
                            $license_info = \ElementPack\Base\Element_Pack_Base::get_register_info();
                            if (!empty($license_info) && !empty($license_info->license_title)): ?>
                                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                                    <p style="margin: 0; font-size: 14px; color: #666;">
                                        <strong><?php esc_html_e('Current License:', 'bdthemes-element-pack'); ?></strong>
                                        <?php echo esc_html($license_info->license_title); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- </div> -->
                    </div>

                </div>
            </div>
            <!-- </div> -->
        </div>
        <?php
    }

    /**
     * Enqueue Permission Manager scripts
     */
    public function enqueue_scripts($hook)
    {
        // Only enqueue on the Element Pack settings page
        if (isset($_GET['page']) && $_GET['page'] === 'element_pack_options') {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

            wp_enqueue_script(
                'ep-permission-manager',
                BDTEP_ADMIN_URL . 'assets/js/ep-permission-manager' . $suffix . '.js',
                array('jquery'),
                BDTEP_VER,
                true
            );

            wp_enqueue_style(
                'ep-permission-manager',
                BDTEP_ADMIN_URL . 'assets/css/ep-permission-manager.css',
                array(),
                BDTEP_VER
            );
        }
    }

    /**
     * Localize script data
     */
    public function localize_scripts($hook)
    {
        // Only localize on the Element Pack settings page
        if (isset($_GET['page']) && $_GET['page'] === 'element_pack_options') {
            wp_localize_script('ep-permission-manager', 'epRoleElementsConfig', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ep_role_elements_nonce'),
                'isMultisite' => is_multisite(),
                'isMainSiteAdmin' => is_multisite() ? is_main_site() && current_user_can('manage_network_options') : false,
                'currentSubsiteId' => is_multisite() ? get_current_blog_id() : 0
            ));

            // Also create the legacy variables that the JS expects
            wp_add_inline_script(
                'ep-permission-manager',
                'var epRoleElementsNonce = "' . wp_create_nonce('ep_role_elements_nonce') . '";' .
                'var epRoleElementsData = {};' .
                'var ajaxurl = "' . admin_url('admin-ajax.php') . '";' .
                'var epIsMultisite = ' . (is_multisite() ? 'true' : 'false') . ';' .
                'var epIsMainSiteAdmin = ' . (is_multisite() ? (is_main_site() && current_user_can('manage_network_options') ? 'true' : 'false') : 'false') . ';' .
                'var epCurrentSubsiteId = ' . (is_multisite() ? get_current_blog_id() : '0') . ';',
                'before'
            );
        }
    }

    /**
     * AJAX handler to get role elements
     */
    public function get_role_elements_ajax()
    {

        // Check if request data exists
        if (!isset($_POST['nonce'])) {
            wp_send_json_error('No nonce provided');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ep_role_elements_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';

        if (empty($role)) {
            wp_send_json_error('Role parameter is required');
        }

        try {
            // Get all available widgets
            $all_widgets = $this->get_all_widgets();

            // Debug: Check for settings widgets
            $settings_widgets = array_filter($all_widgets, function ($widget) {
                return isset($widget['module_type']) && $widget['module_type'] === 'settings';
            });

            // Get allowed widgets for this role/subsite
            if (is_multisite()) {
                // Multisite: Get subsite elements
                $allowed_widgets = $this->get_allowed_widgets_for_subsite($role);
            } else {
                // Single site: Get role elements
                $allowed_widgets = $this->get_allowed_widgets_for_role($role);
            }

            wp_send_json_success(array(
                'all_widgets' => $all_widgets,
                'allowed_widgets' => $allowed_widgets
            ));
        } catch (Exception $e) {
            wp_send_json_error('Error loading widgets: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler to save role elements
     */
    public function save_role_elements_ajax()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ep_role_elements_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $role = sanitize_text_field($_POST['role']);
        $elements = isset($_POST['elements']) ? $_POST['elements'] : array();

        if (empty($role)) {
            wp_send_json_error('Role parameter is required');
        }

        // Sanitize elements
        $sanitized_elements = array();
        if (is_array($elements)) {
            foreach ($elements as $key => $value) {
                $sanitized_elements[sanitize_text_field($key)] = sanitize_text_field($value);
            }
        }

        // Save the elements
        if (is_multisite()) {
            // Multisite: Save subsite elements
            $this->save_subsite_elements($role, $sanitized_elements);
        } else {
            // Single site: Save role elements
            $this->save_role_elements($role, $sanitized_elements);
        }

        wp_send_json_success('Permission saved successfully');
    }

    /**
     * AJAX handler to reset role elements
     */
    public function reset_role_elements_ajax()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ep_role_elements_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $role = sanitize_text_field($_POST['role']);

        if (empty($role)) {
            wp_send_json_error('Role parameter is required');
        }

        // Reset the elements
        if (is_multisite()) {
            // Multisite: Reset subsite elements
            $this->reset_subsite_elements($role);
        } else {
            // Single site: Reset role elements
            $this->reset_role_elements($role);
        }

        wp_send_json_success('Settings reset successfully');
    }

    /**
     * Get all available widgets
     */
    private function get_all_widgets()
    {
        // Get widget settings from ModuleService
        $widgets = array();

        \ElementPack\Admin\ModuleService::get_widget_settings(function ($settings) use (&$widgets) {
            $core_widgets = $settings['settings_fields']['element_pack_active_modules'];
            $extensions = $settings['settings_fields']['element_pack_elementor_extend'];
            $third_party_widgets = $settings['settings_fields']['element_pack_third_party_widget'];
            $other_settings = $settings['settings_fields']['element_pack_other_settings'];

            // Process core widgets
            foreach ($core_widgets as $widget) {
                $widgets[] = array(
                    'name' => $widget['name'],
                    'label' => $widget['label'],
                    'widget_type' => isset($widget['widget_type']) ? $widget['widget_type'] : 'free',
                    'content_type' => isset($widget['content_type']) ? $widget['content_type'] : 'basic',
                    'module_type' => 'core-widgets'
                );
            }

            // Process extensions
            foreach ($extensions as $widget) {
                $widgets[] = array(
                    'name' => $widget['name'],
                    'label' => $widget['label'],
                    'widget_type' => isset($widget['widget_type']) ? $widget['widget_type'] : 'free',
                    'content_type' => isset($widget['content_type']) ? $widget['content_type'] : 'extension',
                    'module_type' => 'extensions'
                );
            }

            // Process third party widgets
            foreach ($third_party_widgets as $widget) {
                $widgets[] = array(
                    'name' => $widget['name'],
                    'label' => $widget['label'],
                    'widget_type' => isset($widget['widget_type']) ? $widget['widget_type'] : 'pro',
                    'content_type' => isset($widget['content_type']) ? $widget['content_type'] : 'third-party',
                    'module_type' => '3rd-party-widgets'
                );
            }

            // Process other settings (special features)
            $exclude_names = [
                'swatches_group_start',
                'ep_variation_swatches_shape',
                'ep_variation_swatches_tooltip',
                'ep_variation_swatches_size',
                'swatches_group_end'
            ];

            // Filter the other_settings array to remove items with matching names
            $filtered_other_settings = [];
            foreach ($other_settings as $index => $setting) {
                if (isset($setting['name']) && !in_array($setting['name'], $exclude_names)) {
                    $filtered_other_settings[] = $setting;
                }
            }

            $other_settings = $filtered_other_settings;

            foreach ($other_settings as $widget) {
                $widgets[] = array(
                    'name' => $widget['name'],
                    'label' => isset($widget['label']) ? $widget['label'] : $widget['name'],
                    'widget_type' => isset($widget['widget_type']) ? $widget['widget_type'] : 'free',
                    'content_type' => isset($widget['content_type']) ? $widget['content_type'] : 'special',
                    'module_type' => 'special-features'
                );
            }
        }, true); // Bypass filtering to get all widgets

        // Add settings items
        $settings_items = array(
            array(
                'name' => 'api-settings',
                'label' => 'API Settings',
                'widget_type' => 'pro',
                'content_type' => 'setting',
                'module_type' => 'settings'
            ),
            array(
                'name' => 'extra-options',
                'label' => 'Extra Options',
                'widget_type' => 'free',
                'content_type' => 'setting',
                'module_type' => 'settings'
            ),
            array(
                'name' => 'rollback-version',
                'label' => 'Rollback Version',
                'widget_type' => 'pro',
                'content_type' => 'setting',
                'module_type' => 'settings'
            ),
            array(
                'name' => 'license',
                'label' => 'License',
                'widget_type' => 'free',
                'content_type' => 'setting',
                'module_type' => 'settings'
            )
        );

        // Add settings items to widgets array
        foreach ($settings_items as $setting) {
            $widgets[] = $setting;
        }

        return $widgets;
    }

    /**
     * Get allowed widgets for a role
     */
    private function get_allowed_widgets_for_role($role)
    {
        if (is_multisite()) {
            return $this->get_allowed_widgets_for_subsite($role);
        } else {
            return $this->get_allowed_widgets_for_single_site_role($role);
        }
    }

    /**
     * Get allowed widgets for single site role
     */
    private function get_allowed_widgets_for_single_site_role($role)
    {
        $role_elements = get_option('ep_role_elements', array());

        $allowed_widgets = array();
        if (isset($role_elements[$role]) && is_array($role_elements[$role])) {
            // Only get widgets that are explicitly enabled ('on') for this role
            foreach ($role_elements[$role] as $widget_name => $status) {
                if ($status === 'on') {
                    $allowed_widgets[] = $widget_name;
                }
            }
        }

        return $allowed_widgets;
    }

    /**
     * Get allowed widgets for subsite
     */
    private function get_allowed_widgets_for_subsite($subsite_id)
    {
        $subsite_elements = get_option('ep_subsite_elements', array());

        $allowed_widgets = array();
        if (isset($subsite_elements[$subsite_id]) && is_array($subsite_elements[$subsite_id])) {
            // Only get widgets that are explicitly enabled ('on') for this subsite
            foreach ($subsite_elements[$subsite_id] as $widget_name => $status) {
                if ($status === 'on') {
                    $allowed_widgets[] = $widget_name;
                }
            }
        }

        return $allowed_widgets;
    }

    /**
     * Save role elements for single site
     */
    private function save_role_elements($role, $elements)
    {
        $role_elements = get_option('ep_role_elements', array());
        $role_elements[$role] = $elements;
        update_option('ep_role_elements', $role_elements);
    }

    /**
     * Save subsite elements for multisite
     */
    private function save_subsite_elements($subsite_id, $elements)
    {
        $subsite_elements = get_option('ep_subsite_elements', array());
        $subsite_elements[$subsite_id] = $elements;
        update_option('ep_subsite_elements', $subsite_elements);
    }

    /**
     * Reset role elements for single site
     */
    private function reset_role_elements($role)
    {
        $role_elements = get_option('ep_role_elements', array());
        if (isset($role_elements[$role])) {
            unset($role_elements[$role]);
            update_option('ep_role_elements', $role_elements);
        }
    }

    /**
     * Reset subsite elements for multisite
     */
    private function reset_subsite_elements($subsite_id)
    {
        $subsite_elements = get_option('ep_subsite_elements', array());
        if (isset($subsite_elements[$subsite_id])) {
            unset($subsite_elements[$subsite_id]);
            update_option('ep_subsite_elements', $subsite_elements);
        }
    }

    /**
     * Get admin settings (similar to element_pack_admin_settings in admin-settings.php)
     * 
     * @return array
     */
    private function get_element_pack_admin_settings()
    {
        return \ElementPack\Admin\ModuleService::get_widget_settings(function ($settings) {
            return $settings['settings_fields'];
        });
    }

    /**
     * Determine which menus should be visible for the current user based on their role permissions
     * 
     * @return array
     */
    public function get_visible_menus_for_current_user()
    {

        $is_permission_manager_license = $this->is_permission_manager_license();
        // Administrators always see all menus
        if (
            (!is_multisite() && current_user_can('administrator')) // single-site admin
            || (is_multisite() && is_main_site() && current_user_can('administrator')) // multisite main site admin
            || !$is_permission_manager_license // license bypass
        ) {
            return [
                'core_widgets' => true,
                'third_party' => true,
                'extensions' => true,
                'special_features' => true,
                'api_settings' => true,
            ];
        }

        $allowed_widgets = [];

        if (is_multisite() && !is_main_site()) {
            // Multisite subsite: get restrictions from main site
            $current_blog_id = get_current_blog_id();
            $main_site_id = get_main_site_id();
            switch_to_blog($main_site_id);
            $subsite_elements = get_option('ep_subsite_elements', []);
            restore_current_blog();

            if (empty($subsite_elements)) {
                return [
                    'core_widgets' => true,
                    'third_party' => true,
                    'extensions' => true,
                    'special_features' => true,
                    'api_settings' => true,
                ];
            }

            // Allowed widgets for this subsite
            if (!empty($subsite_elements[$current_blog_id]) && is_array($subsite_elements[$current_blog_id])) {

                // Filter only widgets that are 'on' for this subsite
                $subsite_widgets = array_filter(
                    $subsite_elements[$current_blog_id],
                    fn($status) => $status === 'on'
                );

                $allowed_widgets = array_merge($allowed_widgets, array_keys($subsite_widgets));
            }

        } else {
            // Role-based permissions
            $role_elements = get_option('ep_role_elements', []);
            if (empty($role_elements)) {
                return [
                    'core_widgets' => true,
                    'third_party' => true,
                    'extensions' => true,
                    'special_features' => true,
                    'api_settings' => true,
                ];
            }
            $current_user = wp_get_current_user();
            $user_roles = $current_user->roles;

            foreach ($user_roles as $role) {
                if (!empty($role_elements[$role]) && is_array($role_elements[$role])) {

                    // Filter only widgets that are 'on'
                    $role_widgets = array_filter(
                        $role_elements[$role],
                        fn($status) => $status === 'on'
                    );

                    $allowed_widgets = array_merge($allowed_widgets, array_keys($role_widgets));
                }
            }
        }

        // Remove duplicates
        $allowed_widgets = array_unique($allowed_widgets);

        // Count widgets per category (even if empty - let individual sections decide)
        $category_counts = $this->count_widgets_by_category($allowed_widgets);

        // Determine menu visibility based on widget counts
        return [
            'core_widgets' => $category_counts['core_widgets'] > 0,
            'third_party' => $category_counts['third_party'] > 0,
            'extensions' => $category_counts['extensions'] > 0,
            'special_features' => $category_counts['special_features'] > 0,
        ];
    }

    /**
     * Count widgets by category for given widget list
     * 
     * @param array $widget_names
     * @return array
     */
    public function count_widgets_by_category($widget_names)
    {
        $counts = [
            'core_widgets' => 0,
            'third_party' => 0,
            'extensions' => 0,
            'special_features' => 0,
        ];

        // Get all widget settings to determine categories
        $all_settings = $this->get_element_pack_admin_settings();

        // Check each allowed widget and categorize
        foreach ($widget_names as $widget_name) {
            // Remove common prefixes to match settings keys
            $clean_name = str_replace(['bdt-', 'ep-'], '', $widget_name);

            // Check core widgets
            if (isset($all_settings['element_pack_active_modules'])) {
                foreach ($all_settings['element_pack_active_modules'] as $setting) {
                    if (isset($setting['name']) && ($setting['name'] === $clean_name || $setting['name'] === $widget_name)) {
                        $counts['core_widgets']++;
                        continue 2;
                    }
                }
            }

            // Check 3rd party widgets
            if (isset($all_settings['element_pack_third_party_widget'])) {
                foreach ($all_settings['element_pack_third_party_widget'] as $setting) {
                    if (isset($setting['name']) && ($setting['name'] === $clean_name || $setting['name'] === $widget_name)) {
                        $counts['third_party']++;
                        continue 2;
                    }
                }
            }

            // Check extensions
            if (isset($all_settings['element_pack_elementor_extend'])) {
                foreach ($all_settings['element_pack_elementor_extend'] as $setting) {
                    if (isset($setting['name']) && ($setting['name'] === $clean_name || $setting['name'] === $widget_name)) {
                        $counts['extensions']++;
                        continue 2;
                    }
                }
            }

            // Check special features
            if (isset($all_settings['element_pack_other_settings'])) {
                foreach ($all_settings['element_pack_other_settings'] as $setting) {
                    if (isset($setting['name']) && ($setting['name'] === $clean_name || $setting['name'] === $widget_name)) {
                        $counts['special_features']++;
                        continue 2;
                    }
                }
            }
        }

        return $counts;
    }

    /**
     * Render Permission Manager content
     */
    public function element_pack_permission_manager_content()
    {
        // Check if user has proper license for Permission Manager
        if (!$this->is_permission_manager_license()) {
            $this->render_license_requirement_notice();
            return;
        }

        ?>
        <div class="ep-dashboard-panel ep-permission-manager-panel">
            <!-- <div class="bdt-container bdt-container-xlarge"> -->
            <div class="bdt-grid bdt-grid-stack" bdt-grid>
                <div class="bdt-width-expand">
                    <!-- Permission Manager Content - THIS IS THE WRAPPER THE JS LOOKS FOR -->
                    <div class="ep-permission-manager-content">

                        <?php if (is_multisite()): ?>
                            <!-- Multisite Content -->
                            <div class="ep-multisite-permission-manager">
                                <div class="bdt-card bdt-card-body ep-permission-role-wrap">
                                    <div class="ep-permission-role-content">
                                        <h3 class="bdt-card-title">
                                            <?php esc_html_e('Multisite Permission Manager', 'bdthemes-element-pack'); ?>
                                        </h3>
                                        <p><?php esc_html_e('Manage widget permissions for subsites in your multisite network.', 'bdthemes-element-pack'); ?>
                                        </p>
                                    </div>
                                    <div class="ep-subsite-selector ep-role-selector-wrapper">
                                        <label
                                            for="ep-subsite-selector"><?php esc_html_e('Select Subsite:', 'bdthemes-element-pack'); ?></label>
                                        <select id="ep-subsite-selector" class="bdt-select">
                                            <option value=""><?php esc_html_e('Choose a subsite...', 'bdthemes-element-pack'); ?>
                                            </option>
                                            <?php
                                            $sites = get_sites();
                                            foreach ($sites as $site) {
                                                // Skip the main site (blog_id 1 is typically the main site)
                                                if ($site->blog_id == 1) {
                                                    continue;
                                                }
                                                switch_to_blog($site->blog_id);
                                                $site_name = get_bloginfo('name');
                                                $site_url = get_home_url();
                                                restore_current_blog();
                                                echo '<option value="' . esc_attr($site->blog_id) . '">' . esc_html($site_name) . ' (' . esc_html($site_url) . ')</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="ep-filter-elements">
                                        <span class="ep-filter-element ep-filter-circle"></span>
                                        <span class="ep-filter-element ep-filter-dots"></span>
                                        <span class="ep-filter-element ep-filter-wave"></span>
                                        <span class="ep-filter-element ep-filter-hexagon"></span>
                                        <span class="ep-filter-element ep-filter-zigzag"></span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Single Site Content -->
                            <div class="ep-single-site-permission-manager">
                                <div class="bdt-card bdt-card-body ep-permission-role-wrap">
                                    <div class="ep-permission-role-content">
                                        <h3 class="bdt-card-title">
                                            <?php esc_html_e('🔑 Role-Based Permission Manager', 'bdthemes-element-pack'); ?>
                                        </h3>
                                        <p><?php esc_html_e('Manage Element Pack widget permissions for different user roles.', 'bdthemes-element-pack'); ?>
                                        </p>
                                    </div>

                                    <div class="ep-role-selector-wrapper">
                                        <label
                                            for="ep-role-selector"><?php esc_html_e('Select User Role:', 'bdthemes-element-pack'); ?></label>
                                        <select id="ep-role-selector" class="bdt-select">
                                            <option value=""><?php esc_html_e('Choose a user role...', 'bdthemes-element-pack'); ?>
                                            </option>
                                            <?php
                                            global $wp_roles;
                                            $roles = $wp_roles->roles;
                                            foreach ($roles as $role_key => $role) {
                                                // Skip administrator role as they have full access
                                                if ($role_key !== 'administrator') {
                                                    echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role['name']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="ep-filter-elements">
                                        <span class="ep-filter-element ep-filter-circle"></span>
                                        <span class="ep-filter-element ep-filter-dots"></span>
                                        <span class="ep-filter-element ep-filter-wave"></span>
                                        <span class="ep-filter-element ep-filter-hexagon"></span>
                                        <span class="ep-filter-element ep-filter-zigzag"></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>



                        <!-- Role Elements Container -->
                        <div id="ep-role-elements-container" style="display: none; margin-top: 20px;">
                            <!-- <div class="bdt-card bdt-card-body"> -->
                            <!-- Widget Category Tabs -->
                            <div class="ep-widget-tabs-container" style="margin-bottom: 20px;">
                                <nav class="ep-widget-tabs">
                                    <button type="button" class="ep-tab-button active" data-category="core-widgets">
                                        <i class="dashicons dashicons-admin-plugins"></i>
                                        <?php esc_html_e('Core Widgets', 'bdthemes-element-pack'); ?>
                                    </button>
                                    <button type="button" class="ep-tab-button" data-category="3rd-party-widgets">
                                        <i class="dashicons dashicons-networking"></i>
                                        <?php esc_html_e('3rd Party Widgets', 'bdthemes-element-pack'); ?>
                                    </button>
                                    <button type="button" class="ep-tab-button" data-category="extensions">
                                        <i class="dashicons dashicons-admin-tools"></i>
                                        <?php esc_html_e('Extensions', 'bdthemes-element-pack'); ?>
                                    </button>
                                    <button type="button" class="ep-tab-button" data-category="special-features">
                                        <i class="dashicons dashicons-star-filled"></i>
                                        <?php esc_html_e('Special Features', 'bdthemes-element-pack'); ?>
                                    </button>
                                    <button type="button" class="ep-tab-button" data-category="settings">
                                        <i class="dashicons dashicons-admin-settings"></i>
                                        <?php esc_html_e('Settings', 'bdthemes-element-pack'); ?>
                                    </button>
                                </nav>

                                <div class="ep-nav-elements">
                                    <span class="ep-nav-element ep-nav-circle"></span>
                                    <span class="ep-nav-element ep-nav-dots"></span>
                                    <span class="ep-nav-element ep-nav-line"></span>
                                    <span class="ep-nav-element ep-nav-square"></span>
                                    <span class="ep-nav-element ep-nav-triangle"></span>
                                    <span class="ep-nav-element ep-nav-plus"></span>
                                    <span class="ep-nav-element ep-nav-wave"></span>
                                </div>
                            </div>
                            <div class="ep-widget-content-container">
                                <!-- Loading Indicator -->
                                <div id="ep-role-elements-loading" class="ep-loading-indicator"
                                    style="text-align: center; padding: 20px;">
                                    <div bdt-spinner="ratio: 2"></div>
                                    <p><?php esc_html_e('Loading widgets...', 'bdthemes-element-pack'); ?></p>
                                </div>

                                <div class="ep-role-elements-actions">
                                    <div class="ep-actions-row"
                                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; gap: 15px;">
                                        <div class="ep-search-filter-wrapper">
                                            <div class="ep-search-container" style="flex: 1;">
                                                <input type="text" id="ep-widgets-search"
                                                    placeholder="<?php esc_attr_e('Search widgets...', 'bdthemes-element-pack'); ?>"
                                                    style="width: 100%; max-width: 300px; padding: 5px 12px; border: 1px solid #c3c4c7; border-radius: 4px; font-size: 14px;">
                                            </div>
                                            <div class="ep-filter-buttons" style="display: flex; gap: 5px;">
                                                <button type="button" data-filter="all"
                                                    class="ep-type-filter-btn bdt-button bdt-button-default all active">
                                                    <?php esc_html_e('All', 'bdthemes-element-pack'); ?>
                                                </button>
                                                <button type="button" data-filter="selected"
                                                    class="ep-type-filter-btn bdt-button bdt-button-default selected">
                                                    <?php esc_html_e('Selected', 'bdthemes-element-pack'); ?>
                                                </button>
                                                <button type="button" data-filter="free"
                                                    class="ep-type-filter-btn bdt-button bdt-button-default free">
                                                    <?php esc_html_e('Free', 'bdthemes-element-pack'); ?>
                                                </button>
                                                <button type="button" data-filter="pro"
                                                    class="ep-type-filter-btn bdt-button bdt-button-default pro">
                                                    <?php esc_html_e('Pro', 'bdthemes-element-pack'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="ep-action-buttons" style="display: flex; gap: 5px;">
                                            <button type="button" id="ep-select-all-role-elements"
                                                class="ep-action-btn bdt-button bdt-button-secondary select-all">
                                                <?php esc_html_e('Select All', 'bdthemes-element-pack'); ?>
                                            </button>
                                            <button type="button" id="ep-deselect-all-role-elements"
                                                class="ep-action-btn bdt-button bdt-button-secondary deselect-all">
                                                <?php esc_html_e('Deselect All', 'bdthemes-element-pack'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Widgets Grid -->
                                <div id="ep-widgets-grid" class="ep-widgets-grid" style="display: none;">
                                    <!-- Widgets will be loaded here via AJAX -->
                                </div>

                                <!-- Error Message -->
                                <div id="ep-role-elements-error" class="ep-error-message"
                                    style="display: none; text-align: center; padding: 20px;">
                                    <p><?php esc_html_e('Error loading widgets. Please try again.', 'bdthemes-element-pack'); ?>
                                    </p>
                                </div>
                            </div>
                            <!-- </div> -->
                        </div>

                    </div>
                </div>
            </div>
            <!-- </div> -->
        </div>
        <?php
    }
}