<?php

namespace MetForm_Pro\Core\Integrations\Post\Form_To_Post;

use MetForm_Pro\Traits\Singleton;
use MetForm_Pro\Utils\Render;
use MetForm\Utils\Util;

/**
 * Save Form entries as post
 */
class Loader
{
    use Singleton;

    public $id = 'mf-form-to-post';
    public $label = 'Post';

    public function init()
    {

        add_action('mf_form_settings_tab', [$this, 'tab']);
        add_action('mf_form_settings_tab_content', [$this, 'tab_content']);

        add_action('mf_push_tab_content_' . $this->id, [$this, 'settings_content']);

        add_action('rest_api_init', function() {

            register_rest_route('xs/post', '/settings/(?P<id>\d+)', [
                'methods'  => 'GET',
                'callback' => [$this, 'rest_func'],
                'permission_callback' => '__return_true',
            ]);

            // REST API endpoint for searching users
            register_rest_route('xs/post', '/search-users', [
                'methods'  => 'GET',
                'callback' => [$this, 'search_users'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ]);

        });

    }

    public function rest_func($request) {

        // Security check: Only administrators can access post submission settings
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Sorry, you are not allowed to access this resource.', 'metform-pro'),
                ['status' => 403]
            );
        }

        $id = $request['id'];

        return [
                'fields_settings' => get_option('mf_post_submission_' . $id),
                'custom_fields_settings' => get_option('mf_post_submission_custom_fields_' . $id),
        ];
    }

    /**
     * REST API callback for searching users
     * 
     * @param \WP_REST_Request $request
     * @return array
     */
    public function search_users($request) {
        // Verify nonce
        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('Invalid nonce.', 'metform-pro'),
                ['status' => 403]
            );
        }

        $search = isset($request['search']) ? sanitize_text_field($request['search']) : '';
        $per_page = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $page = isset($request['page']) ? absint($request['page']) : 1;
        $user_id = isset($request['user_id']) ? absint($request['user_id']) : 0;

        // If a specific user ID is requested, fetch that user
        if ($user_id > 0) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                return [
                    'users' => [[
                        'id' => $user->ID,
                        'text' => $user->display_name . ' (' . $user->user_email . ')',
                        'display_name' => $user->display_name,
                        'email' => $user->user_email,
                    ]],
                    'total' => 1,
                    'pages' => 1,
                ];
            }
            return [
                'users' => [],
                'total' => 0,
                'pages' => 0,
            ];
        }

        $args = [
            'number' => $per_page,
            'paged' => $page,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];

        if (!empty($search)) {
            // Clean up search string - remove parentheses and extra formatting
            $clean_search = preg_replace('/[\(\)]/', '', $search);
            $clean_search = trim($clean_search);
            
            $args['search'] = '*' . $clean_search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $users_query = new \WP_User_Query($args);
        $users = $users_query->get_results();
        $total_users = $users_query->get_total();

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->ID,
                'text' => $user->display_name . ' (' . $user->user_email . ')',
                'display_name' => $user->display_name,
                'email' => $user->user_email,
            ];
        }

        return [
            'users' => $result,
            'total' => $total_users,
            'pages' => ceil($total_users / $per_page),
        ];
    }


    public function tab()
    {
        Render::form_tab($this->id, $this->label);
    }

    public function tab_content()
    {
        Render::form_tab_content($this->id);
    }

    public function settings_content()
    {

        if( method_exists(Util::class, 'is_using_feature') && ( !Util::is_using_feature('mf_form_to_post') && !Util::is_old_pro_user() && !Util::is_mid_tier() && !Util::is_top_tier()) ){
            mf_dummy_switch_input([
                'label' => 'Form To Post',
                'help' => 'Create a post from form entries'
            ]);
        }else{
            $data = [
                'name' => 'mf_form_to_post',
                'label' => 'Form To Post',
                'class' => 'mf-form-to-post',
                'details' => 'Create a post from form entries',
            ];
    
            Render::checkbox($data);
    
            Render::seperator();
            Render::div('', 'mf-input-group mf-input-group-inline', $this->form_field_content());
        }

    }

    public function form_field_content()
    {
        ?>
        <div class="mf-form-to-post-fields">

            <div class="mf-form-to-post-fields-section">
                <div class="mf-input-group mf-input-group-inline mf-form-bottom-spacing">
                    <label class="attr-input-label">Post Type</label>
                    <div class="mf-inputs">
                        <select name="mf_post_submission_post_type" class="attr-form-control mf_post_submission_post_type">
                            <?php foreach (get_post_types() as $key => $value): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mf-input-group mf-input-group-inline mf-form-bottom-spacing">
                    <label class="attr-input-label">Author</label>
                    <div class="mf-inputs mf-author-select-wrapper">
                        <div class="mf-searchable-select" data-name="mf_post_submission_author">
                            <input type="hidden" name="mf_post_submission_author" class="mf_post_submission_author" value="">
                            <div class="mf-select-display">
                                <input type="text" class="attr-form-control mf-author-search-input" placeholder="<?php esc_attr_e('Search author...', 'metform-pro'); ?>" autocomplete="off">
                                <span class="mf-select-clear" title="<?php esc_attr_e('Clear selection', 'metform-pro'); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12"><path d="M10.5 1.5L1.5 10.5M1.5 1.5L10.5 10.5" stroke="white" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
                                </span>
                            </div>
                            <ul class="mf-select-dropdown">
                                <li class="mf-select-hint"><?php esc_html_e('Type to search authors...', 'metform-pro'); ?></li>
                                <li class="mf-select-loading" style="display: none;"><?php esc_html_e('Loading...', 'metform-pro'); ?></li>
                                <li class="mf-select-no-results" style="display: none;"><?php esc_html_e('No users found', 'metform-pro'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mf-post-submission-fields-section"></div>
        </div>

        <?php
    }

}

Loader::instance()->init();
