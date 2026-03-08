<?php
defined('ABSPATH') || exit;

class BLP_Admin {

    public function __construct() {
        add_action('admin_menu',            [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu() {
        add_menu_page(
            __('BioLink Pro', 'biolink-pro'),
            __('BioLink Pro', 'biolink-pro'),
            'manage_biolink',
            'biolink-pro',
            [$this, 'render_page'],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>'),
            30
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_biolink-pro') return;

        // Google Fonts
        wp_enqueue_style('blp-fonts',
            'https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&family=Josefin+Sans:wght@300;400;500;600;700&family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700&family=Syne:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Manrope:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&family=Lora:wght@400;500;600;700&family=Nunito+Sans:wght@300;400;500;600;700&family=Merriweather:wght@300;400;700&family=Oswald:wght@300;400;500;600;700&family=Archivo:wght@300;400;500;600;700&family=Source+Sans+3:wght@300;400;500;600;700&family=Rubik:wght@300;400;500;600;700&display=swap',
            [], null
        );

        // SortableJS
        wp_enqueue_script('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js', [], '1.15.2', true);

        // Chart.js
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', [], '4.4.2', true);

        // Admin CSS (depends on fonts being loaded)
        wp_enqueue_style('blp-admin', BLP_PLUGIN_URL . 'admin/css/admin.css', ['blp-fonts'], BLP_VERSION);

        // Admin JS
        wp_enqueue_script('blp-admin', BLP_PLUGIN_URL . 'admin/js/admin.js', ['jquery', 'sortablejs', 'chartjs'], BLP_VERSION, true);

        // Pass data to JS
        $user_id    = get_current_user_id();
        $is_manager = current_user_can('manage_options');

        if ($is_manager) {
            $profiles = BLP_Database::get_all_profiles(1000, 0);
        } else {
            $profiles = BLP_Database::get_profiles_by_user($user_id);
        }

        $active_profile_id = (int) get_user_meta($user_id, 'blp_active_profile_id', true);
        $profile = null;

        if ($active_profile_id > 0) {
            foreach ($profiles as $candidate) {
                if ((int) $candidate->id === $active_profile_id) {
                    $profile = $candidate;
                    break;
                }
            }
        }

        if (!$profile && !empty($profiles)) {
            $profile = $profiles[0];
            update_user_meta($user_id, 'blp_active_profile_id', (int) $profile->id);
        }

        $profile_id = $profile ? (int) $profile->id : 0;

        $links     = $profile_id ? BLP_Database::get_links($profile_id) : [];
        $analytics = $profile_id ? BLP_Database::get_analytics($profile_id, 30) : [];
        $security  = class_exists('BLP_Security') ? BLP_Security::get_status_snapshot() : [];
        $license   = class_exists('BLP_License') ? BLP_License::get_status_snapshot() : [];

        if (!$is_manager) {
            if (isset($security['settings']) && is_array($security['settings'])) {
                $security['settings']['alertEmail'] = '';
                $security['settings']['webhookUrl'] = '';
                $security['settings']['policyUrl'] = '';
                $security['settings']['policyPublicKey'] = '';
            }

            if (isset($security['events']) && is_array($security['events'])) {
                foreach ($security['events'] as &$event) {
                    if (is_array($event) && isset($event['context'])) {
                        $event['context'] = [];
                    }
                }
                unset($event);
            }

            if (isset($license['settings']) && is_array($license['settings'])) {
                $license['settings']['apiBaseUrl'] = '';
            }

            $license['instanceId'] = '';
            $license['lastError'] = '';
        }

        // Build profiles list for switcher
        $profiles_list = [];
        foreach ($profiles as $p) {
            $profiles_list[] = [
                'id'           => (int) $p->id,
                'username'     => $p->username,
                'display_name' => $p->display_name,
                'avatar_url'   => $p->avatar_url,
                'is_active'    => (int) $p->is_active,
                'user_id'      => (int) $p->user_id,
                'publicUrl'    => blp_profile_url($p->username),
                'publicFallbackUrl' => blp_profile_fallback_url($p->username),
                'previewUrl'   => blp_profile_preview_url($p->username),
            ];
        }

        wp_localize_script('blp-admin', 'BLP', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('blp_nonce'),
            'previewNonce' => wp_create_nonce('blp_preview'),
            'profileId'    => $profile_id,
            'profile'      => $profile,
            'profiles'     => $profiles_list,
            'links'        => $links,
            'analytics'    => $analytics,
            'publicUrl'    => $profile ? blp_profile_url($profile->username) : '',
            'publicFallbackUrl' => $profile ? blp_profile_fallback_url($profile->username) : '',
            'previewUrl'   => $profile ? blp_profile_preview_url($profile->username) : '',
            'pluginUrl'    => BLP_PLUGIN_URL,
            'isManager'    => $is_manager ? 1 : 0,
            'currentUserId'=> (int) $user_id,
            'wpTimezone'   => wp_timezone_string() ?: 'UTC',
            'customFonts'  => get_user_meta($user_id, 'blp_custom_fonts', true) ?: [],
            'debug'        => (defined('WP_DEBUG') && WP_DEBUG) ? 1 : 0,
            'security'     => $security,
            'license'      => $license,
            'canManageSecurity' => current_user_can('manage_options') ? 1 : 0,
        ]);
    }

    public function render_page() {
        if (!current_user_can('manage_biolink')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'biolink-pro'));
        }

        $user_id = get_current_user_id();
        $is_manager = current_user_can('manage_options');
        $active_profile_id = (int) get_user_meta($user_id, 'blp_active_profile_id', true);
        $profile = $active_profile_id > 0 ? BLP_Database::get_profile_by_id($active_profile_id) : null;

        if ($profile && !$is_manager && (int) $profile->user_id !== (int) $user_id) {
            $profile = null;
        }

        if (!$profile) {
            $profiles = $is_manager
                ? BLP_Database::get_all_profiles(1, 0)
                : BLP_Database::get_profiles_by_user($user_id);
            $profile = !empty($profiles) ? $profiles[0] : null;
        }

        include BLP_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}
