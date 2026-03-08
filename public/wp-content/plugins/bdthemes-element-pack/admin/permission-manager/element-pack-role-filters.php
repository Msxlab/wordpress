<?php

namespace ElementPack\Admin;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Role-based Element Pack Widget Filtering
 * 
 * This class handles filtering Element Pack widgets based on user roles
 * in the Elementor editor.
 */
class Role_Filters {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_role_filter_scripts'));
        add_action('elementor/editor/before_enqueue_styles', array($this, 'enqueue_role_filter_styles'));
        add_action('elementor/editor/localize_settings', array($this, 'localize_role_filter_data'));
        
        // Filter admin settings widgets
        add_action('admin_init', array($this, 'filter_admin_settings_widgets'));
        
        // Enqueue scripts for admin pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_role_filter_scripts'));
        
        // Add admin notice for restricted widgets
        add_action('admin_notices', array($this, 'add_restriction_notice'));		
    }

    /**
     * Enqueue role filter scripts
     */
    public function enqueue_role_filter_scripts() {
        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
            $script_url = BDTEP_ADMIN_URL . 'assets/js/ep-permission-manager.js';
        } else {
            $script_url = BDTEP_ADMIN_URL . 'assets/js/ep-permission-manager.min.js';
        }

        wp_enqueue_script(
            'ep-permission-manager',
            $script_url,
            array('jquery', 'elementor-editor'),
            BDTEP_VER,
            true
        );
        
        // Get restricted widgets data and localize it
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $role_elements = get_option('ep_role_elements', array());
        $restricted_widgets = $this->get_restricted_widgets_for_user($user_roles, $role_elements);

        $localize_data = [
            'restricted_widgets' => $restricted_widgets,
        ];
        
        // Localize restricted widgets for JavaScript access
        wp_localize_script('ep-permission-manager', 'epRestrictedWidgets', $restricted_widgets);
        wp_localize_script('ep-permission-manager', 'epRestrictedWidgets', $localize_data);
        
        // Add script to hide restricted widgets in editor
        $js_code = '
            jQuery(document).ready(function($) {
                
                
                // Hide restricted widgets from editor panels
                function hideRestrictedWidgets() {
                    
                    
                    if (!window.epRestrictedWidgets || window.epRestrictedWidgets.length === 0) {
                        return;
                    }
                    
                    // Debug: Check what widgets are actually in the DOM
                    var foundWidgets = [];
                    $(".elementor-element-wrapper").each(function() {
                        var $wrapper = $(this);
                        var $element = $wrapper.find(".elementor-element");
                        var widgetName = $element.data("widget_type");
                        if (widgetName) {
                            foundWidgets.push(widgetName);
                        }
                    });
                    
                    var hiddenCount = 0;
                    
                    // Method 1: Hide by widget wrapper
                    $(".elementor-element-wrapper").each(function() {
                        var $wrapper = $(this);
                        var $element = $wrapper.find(".elementor-element");
                        var widgetName = $element.data("widget_type");
                        
                        if (widgetName && window.epRestrictedWidgets.includes(widgetName)) {
                            $wrapper.remove();
                            hiddenCount++;
                        }
                    });
                    
                    // Method 2: Hide by direct element
                    $(".elementor-element").each(function() {
                        var $element = $(this);
                        var widgetName = $element.data("widget_type");
                        
                        if (widgetName && window.epRestrictedWidgets.includes(widgetName)) {
                            $element.closest(".elementor-element-wrapper").remove();
                            hiddenCount++;
                        }
                    });
                    
                    // Method 4: Hide from search results
                    $(".elementor-element-wrapper").each(function() {
                        var $wrapper = $(this);
                        var $element = $wrapper.find(".elementor-element");
                        var widgetName = $element.data("widget_type");
                        
                        if (widgetName && window.epRestrictedWidgets.includes(widgetName)) {
                            $wrapper.css("display", "none");
                            hiddenCount++;
                        }
                    });
                    
                }
                
                // Show warning for restricted widgets in use
                function showRestrictedWidgetWarning() {
                    if (window.epRestrictedWidgets && window.epRestrictedWidgets.length > 0) {
                        var restrictedInUse = [];
                        
                        // Check if any restricted widgets are being used in the current page
                        $(".elementor-element").each(function() {
                            var widgetName = $(this).data("widget_type");
                            if (widgetName && window.epRestrictedWidgets.includes(widgetName)) {
                                restrictedInUse.push(widgetName);
                            }
                        });
                        
                        if (restrictedInUse.length > 0) {
                            var warningHtml = "<div class=\"ep-restricted-widgets-warning\" style=\"background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; margin: 10px 0; border-radius: 4px;\">" +
                                "<strong>Warning:</strong> Some widgets on this page are restricted for your role but will continue to display. You can view but not edit these widgets." +
                                "<br><small>Restricted widgets: " + restrictedInUse.join(", ") + "</small>" +
                                "</div>";
                            
                            // Add warning to editor
                            if ($(".ep-restricted-widgets-warning").length === 0) {
                                $(".elementor-panel").prepend(warningHtml);
                            }
                        }
                    }
                }
                
                // Run on page load with delay to ensure DOM is ready
                setTimeout(function() {
                    hideRestrictedWidgets();
                    showRestrictedWidgetWarning();
                }, 1000);
                
                // Run when widgets panel is refreshed
                $(document).on("elementor/editor/init", function() {
                    setTimeout(function() {
                        hideRestrictedWidgets();
                        showRestrictedWidgetWarning();
                    }, 1000);
                });
                
                // Also run when widgets are loaded
                $(document).on("elementor/editor/widgets/ready", function() {   
                    setTimeout(function() {
                        hideRestrictedWidgets();
                        showRestrictedWidgetWarning();
                    }, 500);
                });
                
                // Run when panel is shown
                $(document).on("elementor/panel/init", function() {
                    setTimeout(function() {
                        hideRestrictedWidgets();
                        showRestrictedWidgetWarning();
                    }, 500);
                });
                
                // Run when panel is opened
                $(document).on("elementor/panel/open", function() {
                    setTimeout(function() {
                        hideRestrictedWidgets();
                        showRestrictedWidgetWarning();
                    }, 500);
                });
                
                // Run when widgets are filtered or searched
                $(document).on("elementor/panel/search", function() {
                    setTimeout(function() {
                        hideRestrictedWidgets();
                    }, 100);
                });
                
                // Run periodically to catch dynamically loaded widgets
                setInterval(function() {
                    if (window.epRestrictedWidgets && window.epRestrictedWidgets.length > 0) {
                        hideRestrictedWidgets();
                    }
                }, 1000);
                
                // Also run when DOM changes
                var observer = new MutationObserver(function(mutations) {
                    if (window.epRestrictedWidgets && window.epRestrictedWidgets.length > 0) {
                        hideRestrictedWidgets();
                    }
                });
                
                // Start observing when document is ready
                setTimeout(function() {
                    var panel = document.querySelector(".elementor-panel");
                    if (panel) {
                        observer.observe(panel, { childList: true, subtree: true });
                    }
                }, 2000);
            });
        ';
        
        wp_add_inline_script("ep-permission-manager", $js_code);
    }

    /**
     * Enqueue role filter scripts for admin pages
     */
    public function enqueue_admin_role_filter_scripts() {
        // Only enqueue on Element Pack admin pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'element-pack') === false) {
            return;
        }
        
        wp_enqueue_script(
            'ep-permission-manager-admin',
            BDTEP_ADMIN_URL . 'assets/js/ep-permission-manager.min.js',
            array('jquery'),
            BDTEP_VER,
            true
        );
    }

    /**
     * Enqueue role filter styles
     */
    public function enqueue_role_filter_styles() {
        wp_enqueue_style(
            'ep-role-filters',
            BDTEP_URL . 'assets/css/ep-role-filters.css',
            array(),
            BDTEP_VER
        );
    }

    /**
     * Localize role filter data
     */
    public function localize_role_filter_data($settings) {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Get role elements settings
        $role_elements = get_option('ep_role_elements', array());
        
        // Get restricted widgets for current user
        $restricted_widgets = $this->get_restricted_widgets_for_user($user_roles, $role_elements);
        
        $settings['ep_role_filters'] = array(
            'restricted_widgets' => $restricted_widgets,
            'user_roles' => $user_roles,
            'has_restrictions' => !empty($restricted_widgets)
        );
        
        return $settings;
    }
    
    /**
     * Check if widget is being used in current page
     */
    private function is_widget_used_in_page($widget_name) {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Get the post content
        $content = $post->post_content;
        
        // Check if widget name appears in the content
        return strpos($content, $widget_name) !== false;
    }

    /**
     * Get restricted widgets for user
     */
    public function get_restricted_widgets_for_user($user_roles, $role_elements) {
        
        $restricted_widgets = array();
        
        // Handle multisite restrictions
        if (is_multisite()) {
            return $this->get_restricted_widgets_for_subsite();
        }
        
        // If user has administrator role, no restrictions
        if (in_array('administrator', $user_roles)) {
            return $restricted_widgets;
        }
        
        // Check each user role for restrictions
        foreach ($user_roles as $role) {
            
            if (isset($role_elements[$role]) && !empty($role_elements[$role])) {
                $role_settings = $role_elements[$role];
                
                // Get all available Element Pack widgets
                $all_widgets = $this->get_all_element_pack_widgets();

                
                // Find widgets that are not enabled for this role
                foreach ($all_widgets as $widget_name) {
                    // Remove 'bdt-' prefix for comparison with stored settings
                    $clean_widget_name = str_replace('bdt-', '', $widget_name);
                    
                    // If widget is not explicitly enabled for this role, restrict it
                    if (!isset($role_settings[$clean_widget_name]) || $role_settings[$clean_widget_name] !== 'on') {
                        $restricted_widgets[] = $widget_name;
                    }
                }
            }
        }
        
        return array_unique($restricted_widgets);
    }

    /**
     * Get restricted widgets for multisite subsite
     */
    public function get_restricted_widgets_for_subsite() {
        $restricted_widgets = array();
        
        // If user is main site admin, no restrictions
        if (is_main_site() && current_user_can('administrator')) {
            return $restricted_widgets;
        }
        
        $current_blog_id = get_current_blog_id();
        
        // Get subsite elements settings from the MAIN SITE (not current site)
        // Switch to main site to get the centrally stored configuration
        $main_site_id = get_main_site_id();
        switch_to_blog($main_site_id);
        $subsite_elements = get_option('ep_subsite_elements', array());
        restore_current_blog();
        // Check if current subsite has restrictions configured
        if (isset($subsite_elements[$current_blog_id]) && !empty($subsite_elements[$current_blog_id])) {
            $subsite_settings = $subsite_elements[$current_blog_id];
            
            // Get all available Element Pack widgets
            $all_widgets = $this->get_all_element_pack_widgets();
            
            // Find widgets that are not enabled for this subsite
            foreach ($all_widgets as $widget_name) {
                // Remove 'bdt-' prefix for comparison with stored settings
                $clean_widget_name = str_replace('bdt-', '', $widget_name);
                
                // If widget is not explicitly enabled for this subsite, restrict it
                if (!isset($subsite_settings[$clean_widget_name]) || $subsite_settings[$clean_widget_name] !== 'on') {
                    $restricted_widgets[] = $widget_name;
                }
            }
        }
        return array_unique($restricted_widgets);
    }

    /**
     * Get all Element Pack widgets
     */
    public function get_all_element_pack_widgets() {
        $widgets = array();
        
        // Get widget settings from ModuleService with bypass filter to get ALL widgets
        $widget_settings = \ElementPack\Admin\ModuleService::get_widget_settings(function ($settings) {
            return $settings['settings_fields'];
        }, true); // Pass true to bypass filtering
        
        
        // Get all core widgets
        foreach ($widget_settings['element_pack_active_modules'] as $widget) {
            $widgets[] = 'bdt-' . $widget['name'];
        }
        
        // Get all third party widgets
        foreach ($widget_settings['element_pack_third_party_widget'] as $widget) {
            $widgets[] = 'bdt-' . $widget['name'];
        }
        
        // Get all extensions
        foreach ($widget_settings['element_pack_elementor_extend'] as $widget) {
            $widgets[] = 'bdt-' . $widget['name'];
        }

        // Get all others
        foreach ($widget_settings['element_pack_other_settings'] as $widget) {
            $widgets[] = 'bdt-' . $widget['name'];
        }
        
        return $widgets;
    }

    /**
     * Check if widget is restricted for current user
     */
    public static function is_widget_restricted($widget_name) {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Administrators have no restrictions
        if (in_array('administrator', $user_roles)) {
            return false;
        }
        
        // Get role elements settings
        $role_elements = get_option('ep_role_elements', array());
        
        // Check if widget is enabled for any of user's roles
        foreach ($user_roles as $role) {
            if (isset($role_elements[$role]) && !empty($role_elements[$role])) {
                $role_settings = $role_elements[$role];
                
                // Remove 'bdt-' prefix for comparison
                $clean_widget_name = str_replace('bdt-', '', $widget_name);
                
                if (isset($role_settings[$clean_widget_name]) && $role_settings[$clean_widget_name] === 'on') {
                    return false; // Widget is enabled for this role
                }
            }
        }
        
        // If user has roles but none have been configured with restrictions, no restrictions
        $has_configured_roles = false;
        foreach ($user_roles as $role) {
            if (isset($role_elements[$role]) && !empty($role_elements[$role])) {
                $has_configured_roles = true;
                break;
            }
        }
        
        // If no roles have been configured, no restrictions
        if (!$has_configured_roles) {
            return false;
        }
        
        return true; // Widget is restricted
    }

    /**
     * Get available widgets for current user
     */
    public static function get_available_widgets_for_user() {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Get role elements settings
        $role_elements = get_option('ep_role_elements', array());
        
        $available_widgets = array();
        
        // Check each user role for available widgets
        foreach ($user_roles as $role) {
            if (isset($role_elements[$role]) && !empty($role_elements[$role])) {
                $role_settings = $role_elements[$role];
                
                foreach ($role_settings as $widget_name => $status) {
                    if ($status === 'on') {
                        $available_widgets[] = 'bdt-' . $widget_name;
                    }
                }
            }
        }
        
        return array_unique($available_widgets);
    }

    /**
     * Add admin notice for restricted widgets
     */
    public function add_restriction_notice() {
        if (!current_user_can('manage_options')) {
            $current_user = wp_get_current_user();
            $user_roles = $current_user->roles;
            
            // Check if user has any restrictions (multisite or single site)
            $has_restrictions = false;
            $notice_message = '';
            
            if (is_multisite()) {
                // Multisite: Check subsite restrictions
                $current_blog_id = get_current_blog_id();
                $subsite_elements = get_option('ep_subsite_elements', array());
                
                if (isset($subsite_elements[$current_blog_id]) && !empty($subsite_elements[$current_blog_id])) {
                    $has_restrictions = true;
                    $notice_message = '<strong>Element Pack:</strong> Some widgets are restricted for this subsite. Contact the network administrator if you need access to additional widgets.';
                }
            } else {
                // Single site: Check role restrictions
                $role_elements = get_option('ep_role_elements', array());
                
                foreach ($user_roles as $role) {
                    if (isset($role_elements[$role]) && !empty($role_elements[$role])) {
                        $has_restrictions = true;
                        $notice_message = '<strong>Element Pack:</strong> Some widgets are restricted based on your user role. Contact an administrator if you need access to additional widgets.';
                        break;
                    }
                }
            }
            
            if ($has_restrictions) {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p>' . $notice_message . '</p>';
                echo '</div>';
            }
        }
    }

    /**
     * Filter admin settings widgets
     */
    public function filter_admin_settings_widgets() {
        // Only apply on Element Pack admin pages
        if (!isset($_GET['page']) || (strpos($_GET['page'], 'element-pack') === false && strpos($_GET['page'], 'element_pack') === false)) {
            return;
        }
        
        // Handle multisite vs single site filtering
        $has_restrictions = false;
        
        if (is_multisite()) {
            // Multisite: Check if subsite restrictions are configured
            $current_blog_id = get_current_blog_id();
            
            // Get subsite elements from the MAIN SITE (not current site)
            $main_site_id = get_main_site_id();
            switch_to_blog($main_site_id);
            $subsite_elements = get_option('ep_subsite_elements', array());
            restore_current_blog();
            
            // If user is main site admin, no restrictions
            if (is_main_site() && current_user_can('administrator')) {
                $has_restrictions = false;
            } else {
                // Check if current subsite has restrictions configured
                $has_restrictions = isset($subsite_elements[$current_blog_id]) && !empty($subsite_elements[$current_blog_id]);
            }
        } else {
            // Single site: Check role-based restrictions
            $current_user = wp_get_current_user();
            $user_roles = $current_user->roles;
            $role_elements = get_option('ep_role_elements', array());
            
            // Check if user has any role restrictions
            foreach ($user_roles as $role) {
                if (isset($role_elements[$role]) && !empty($role_elements[$role])) {
                    $has_restrictions = true;
                    break;
                }
            }
        }
        
        // Only apply filters if user has restrictions
        if ($has_restrictions) {
            // Add CSS to hide restricted widgets
            add_action('admin_head', array($this, 'add_admin_settings_css'));
            
            // Add JavaScript to handle restricted widgets
            add_action('admin_footer', array($this, 'add_admin_settings_js'));
        }
    }

    /**
     * Add CSS to hide restricted widgets in admin settings
     */
    public function add_admin_settings_css() {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;

        // Get restricted widgets for current user (handles both multisite and single site)
        if (is_multisite()) {
            $restricted_widgets = $this->get_restricted_widgets_for_subsite();
        } else {
            // Get role elements settings
            $role_elements = get_option('ep_role_elements', array());
            $restricted_widgets = $this->get_restricted_widgets_for_user($user_roles, $role_elements);
        }
        
        if (!empty($restricted_widgets)) {
            echo '<style>';
            foreach ($restricted_widgets as $widget_name) {
                // Remove 'bdt-' prefix for admin settings
                $clean_widget_name = str_replace('bdt-', '', $widget_name);
                
                // Hide the widget option in admin settings
                echo '.ep-option-item-inner:has(input[name*="' . $clean_widget_name . '"]) { display: none !important; }';
                echo '.ep-option-item-inner:has(input[id*="' . $clean_widget_name . '"]) { display: none !important; }';
                echo '.ep-option-item:has(input[name*="' . $clean_widget_name . '"]) { display: none !important; }';
                echo '.ep-option-item:has(input[id*="' . $clean_widget_name . '"]) { display: none !important; }';
            }
            echo '</style>';
        }
    }

    /**
     * Add JavaScript to handle restricted widgets in admin settings
     */
    public function add_admin_settings_js() {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Get restricted widgets for current user (handles both multisite and single site)
        if (is_multisite()) {
            $restricted_widgets = $this->get_restricted_widgets_for_subsite();
        } else {
            // Get role elements settings
            $role_elements = get_option('ep_role_elements', array());
            $restricted_widgets = $this->get_restricted_widgets_for_user($user_roles, $role_elements);
        }
        
        if (!empty($restricted_widgets)) {
            ?>
            <script>
            // Make restricted widgets data available to JavaScript
            window.epRestrictedWidgets = <?php echo json_encode($restricted_widgets); ?>;
            
            jQuery(document).ready(function($) {
                // Hide restricted widgets in admin settings
                var restrictedWidgets = Object.values(window.epRestrictedWidgets || []);

                restrictedWidgets.forEach(function(widgetName) {
                    var cleanWidgetName = widgetName.replace('bdt-', '');   
                    // Hide widget options
                    $('.ep-option-item-inner').each(function() {
                        var $item = $(this);
                        var $input = $item.find('input[name*="' + cleanWidgetName + '"], input[id*="' + cleanWidgetName + '"]');
                        
                        if ($input.length > 0) {
                             $item.parent().remove();
                         }
                     });

                    // Hide widget options
                    $('.ep-option-item.ep-' + cleanWidgetName).each(function() {
                        this.remove();
                    });
                    
                    // Also hide the entire option item
                    $('.ep-option-item').each(function() {
                        var $item = $(this);
                        var $input = $item.find('input[name*="' + cleanWidgetName + '"], input[id*="' + cleanWidgetName + '"]');
                        
                        if ($input.length > 0) {
                            $item.parent().remove();
                        }
                    });
                });
                
                // Update widget counts after hiding restricted widgets
                setTimeout(function() {
                    // Trigger any existing count update functions
                    if (typeof updateTotalStatus === 'function') {
                        updateTotalStatus();
                    }
                    
                    // Update used/unused counts
                    if (typeof RoleFilters !== 'undefined' && typeof RoleFilters.updateAdminCounts === 'function') {
                        RoleFilters.updateAdminCounts();
                    }
                }, 500);
            });
            </script>
            <?php
        }
    }
}

// Initialize role filters
new Role_Filters(); 