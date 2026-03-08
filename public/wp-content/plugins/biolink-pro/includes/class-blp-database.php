<?php
defined('ABSPATH') || exit;

class BLP_Database {

    /* ──────────────────────────────────────────
     * INSTALL / UNINSTALL
     * ────────────────────────────────────────── */

    public static function install() {
        global $wpdb;
        $cc = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Note: dbDelta() manages CREATE/ALTER automatically; do not use IF NOT EXISTS.
        dbDelta("CREATE TABLE {$wpdb->prefix}blp_profiles (
            id            bigint(20)   NOT NULL AUTO_INCREMENT,
            user_id       bigint(20)   NOT NULL,
            username      varchar(100) NOT NULL,
            display_name  varchar(255) DEFAULT '',
            bio           text,
            avatar_url    varchar(500) DEFAULT '',
            theme_settings longtext,
            custom_domain varchar(255) DEFAULT '',
            is_active     tinyint(1)   DEFAULT 1,
            page_views    bigint(20)   DEFAULT 0,
            created_at    datetime     DEFAULT CURRENT_TIMESTAMP,
            updated_at    datetime     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY   (id),
            UNIQUE KEY    username (username),
            KEY           user_id  (user_id)
        ) $cc;");

        dbDelta("CREATE TABLE {$wpdb->prefix}blp_links (
            id            bigint(20)    NOT NULL AUTO_INCREMENT,
            profile_id    bigint(20)    NOT NULL,
            link_type     varchar(30)   DEFAULT 'link',
            title         varchar(255)  NOT NULL,
            title_b       varchar(255)  DEFAULT '',
            url           varchar(2000) NOT NULL DEFAULT '',
            subtitle      varchar(500)  DEFAULT '',
            thumbnail_url varchar(500)  DEFAULT '',
            icon          varchar(100)  DEFAULT '',
            badge         varchar(50)   DEFAULT '',
            badge_color   varchar(20)   DEFAULT '#ef4444',
            metadata      longtext,
            is_visible    tinyint(1)    DEFAULT 1,
            is_featured   tinyint(1)    DEFAULT 0,
            sort_order    int(11)       DEFAULT 0,
            click_count   bigint(20)    DEFAULT 0,
            click_count_b bigint(20)    DEFAULT 0,
            schedule_start datetime     DEFAULT NULL,
            schedule_end   datetime     DEFAULT NULL,
            created_at    datetime      DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY   (id),
            KEY           profile_id (profile_id)
        ) $cc;");

        dbDelta("CREATE TABLE {$wpdb->prefix}blp_analytics (
            id            bigint(20)   NOT NULL AUTO_INCREMENT,
            link_id       bigint(20)   DEFAULT NULL,
            profile_id    bigint(20)   NOT NULL,
            event_type    varchar(20)  DEFAULT 'click',
            ip_hash       varchar(64)  DEFAULT '',
            user_agent    varchar(500) DEFAULT '',
            referrer      varchar(500) DEFAULT '',
            country       varchar(5)   DEFAULT '',
            device        varchar(20)  DEFAULT '',
            clicked_at    datetime     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY   (id),
            KEY           link_id    (link_id),
            KEY           profile_id (profile_id),
            KEY           clicked_at (clicked_at),
            KEY           idx_profile_event_date (profile_id, event_type, clicked_at)
        ) $cc;");

        update_option('blp_db_version', BLP_VERSION);
    }

    /**
     * Run any pending DB upgrades based on stored version.
     * Call this on plugins_loaded to apply schema migrations.
     */
    public static function maybe_upgrade() {
        $installed = get_option('blp_db_version', '0.0.0');
        if (version_compare($installed, BLP_VERSION, '<')) {
            self::install();
        }

        self::migrate_remove_legacy_page_password_column();
    }

    /**
     * Remove deprecated page_password column after password feature removal.
     * Idempotent and safe for existing installs.
     */
    private static function migrate_remove_legacy_page_password_column() {
        $migration_key = 'blp_migrated_remove_page_password_column';

        if (get_option($migration_key) === BLP_VERSION) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'blp_profiles';

        $column_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW COLUMNS FROM `{$table}` LIKE %s",
            'page_password'
        ));

        if ($column_exists) {
            $dropped = $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN `page_password`");
            if ($dropped === false) {
                return;
            }
        }

        update_option($migration_key, BLP_VERSION);
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function uninstall() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}blp_analytics");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}blp_links");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}blp_profiles");
        delete_option('blp_db_version');
        delete_option('blp_rewrite_version');
        delete_option('blp_migrated_remove_page_password_column');
    }

    /* ──────────────────────────────────────────
     * PROFILES
     * ────────────────────────────────────────── */

    public static function get_profile_by_user($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}blp_profiles WHERE user_id = %d ORDER BY id ASC LIMIT 1",
            $user_id
        ));
    }

    public static function get_profiles_by_user($user_id) {
        global $wpdb;

        $links_subquery = "SELECT profile_id, COUNT(*) AS links_count FROM {$wpdb->prefix}blp_links GROUP BY profile_id";

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, COALESCE(lc.links_count, 0) AS links_count
             FROM {$wpdb->prefix}blp_profiles p
             LEFT JOIN ({$links_subquery}) lc ON lc.profile_id = p.id
             WHERE p.user_id = %d
             ORDER BY p.updated_at DESC, p.id DESC",
            $user_id
        ));
    }

    public static function get_all_profiles($limit = 500, $offset = 0, $search = '', $status = 'all', $sort = 'updated_desc') {
        global $wpdb;

        $limit  = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        $links_subquery = "SELECT profile_id, COUNT(*) AS links_count FROM {$wpdb->prefix}blp_links GROUP BY profile_id";

        $where_clauses = [];
        $params = [];

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = '(p.username LIKE %s OR p.display_name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status === 'active') {
            $where_clauses[] = 'p.is_active = %d';
            $params[] = 1;
        } elseif ($status === 'inactive') {
            $where_clauses[] = 'p.is_active = %d';
            $params[] = 0;
        }

        $order_sql_map = [
            'updated_desc'  => 'p.updated_at DESC, p.id DESC',
            'updated_asc'   => 'p.updated_at ASC, p.id ASC',
            'username_asc'  => 'p.username ASC, p.id DESC',
            'username_desc' => 'p.username DESC, p.id DESC',
            'links_desc'    => 'links_count DESC, p.updated_at DESC, p.id DESC',
            'links_asc'     => 'links_count ASC, p.updated_at DESC, p.id DESC',
        ];
        $order_sql = $order_sql_map[$sort] ?? $order_sql_map['updated_desc'];

        $where_sql = !empty($where_clauses)
            ? 'WHERE ' . implode(' AND ', $where_clauses)
            : '';

        $query = "SELECT p.*, COALESCE(lc.links_count, 0) AS links_count
            FROM {$wpdb->prefix}blp_profiles p
            LEFT JOIN ({$links_subquery}) lc ON lc.profile_id = p.id
            {$where_sql}
            ORDER BY {$order_sql}
            LIMIT %d OFFSET %d";

        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    public static function count_all_profiles($search = '', $status = 'all') {
        global $wpdb;

        $where_clauses = [];
        $params = [];

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = '(username LIKE %s OR display_name LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status === 'active') {
            $where_clauses[] = 'is_active = %d';
            $params[] = 1;
        } elseif ($status === 'inactive') {
            $where_clauses[] = 'is_active = %d';
            $params[] = 0;
        }

        $where_sql = !empty($where_clauses)
            ? 'WHERE ' . implode(' AND ', $where_clauses)
            : '';

        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}blp_profiles {$where_sql}";
        if (!empty($params)) {
            return (int) $wpdb->get_var($wpdb->prepare($query, $params));
        }

        return (int) $wpdb->get_var($query);
    }

    public static function count_profiles_by_user_search($user_id, $search = '') {
        global $wpdb;

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->prefix}blp_profiles
                 WHERE user_id = %d AND (username LIKE %s OR display_name LIKE %s)",
                $user_id,
                $like,
                $like
            ));
        }

        return self::count_profiles_by_user($user_id);
    }

    public static function get_profile_by_id($profile_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}blp_profiles WHERE id = %d LIMIT 1",
            $profile_id
        ));
    }

    public static function get_profile_by_username($username) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}blp_profiles WHERE username = %s AND is_active = 1 LIMIT 1",
            $username
        ));
    }

    public static function get_profile_by_username_any($username) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}blp_profiles WHERE username = %s LIMIT 1",
            $username
        ));
    }

    public static function is_username_taken($username, $exclude_profile_id = 0) {
        global $wpdb;

        if ($exclude_profile_id > 0) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}blp_profiles WHERE username = %s AND id != %d LIMIT 1",
                $username,
                $exclude_profile_id
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}blp_profiles WHERE username = %s LIMIT 1",
                $username
            ));
        }

        return !empty($exists);
    }

    public static function create_profile($user_id, array $data) {
        global $wpdb;

        // Whitelist allowed columns to prevent unexpected data injection
        $allowed_keys = ['username', 'display_name', 'bio', 'avatar_url', 'theme_settings', 'custom_domain', 'is_active', 'user_id'];
        $data = array_intersect_key($data, array_flip($allowed_keys));
        $data['user_id'] = (int) $user_id;

        // Build format array for type safety
        $format = [];
        foreach ($data as $key => $value) {
            $format[] = ($key === 'user_id' || $key === 'is_active') ? '%d' : '%s';
        }

        $inserted = $wpdb->insert("{$wpdb->prefix}blp_profiles", $data, $format);

        if ($inserted === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public static function save_profile($user_id, array $data, $profile_id = 0) {
        global $wpdb;

        if ($profile_id > 0) {
            // Update specific profile (ownership check done in AJAX handler)
            $allowed_keys = ['username', 'display_name', 'bio', 'avatar_url', 'theme_settings', 'custom_domain', 'is_active'];
            $data = array_intersect_key($data, array_flip($allowed_keys));

            if (empty($data)) {
                return (int) $profile_id;
            }

            $format = [];
            foreach ($data as $key => $value) {
                $format[] = ($key === 'is_active') ? '%d' : '%s';
            }

            $updated = $wpdb->update(
                "{$wpdb->prefix}blp_profiles",
                $data,
                ['id' => (int) $profile_id],
                $format,
                ['%d']
            );

            if ($updated === false) {
                return 0;
            }

            return (int) $profile_id;
        }

        return self::create_profile($user_id, $data);
    }

    public static function update_profile_status($profile_id, $is_active) {
        global $wpdb;

        return $wpdb->update(
            "{$wpdb->prefix}blp_profiles",
            ['is_active' => (int) $is_active],
            ['id' => (int) $profile_id]
        );
    }

    public static function delete_profile($profile_id) {
        global $wpdb;

        $profile_id = (int) $profile_id;

        // Delete profile-scoped analytics first (covers link + view events)
        $wpdb->delete("{$wpdb->prefix}blp_analytics", ['profile_id' => $profile_id]);

        // Then delete links in one pass
        $wpdb->delete("{$wpdb->prefix}blp_links", ['profile_id' => $profile_id]);

        // Finally remove profile
        return $wpdb->delete("{$wpdb->prefix}blp_profiles", ['id' => $profile_id]);
    }

    public static function count_profiles_by_user($user_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}blp_profiles WHERE user_id = %d",
            $user_id
        ));
    }

    public static function increment_page_view($profile_id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}blp_profiles SET page_views = page_views + 1 WHERE id = %d",
            $profile_id
        ));
    }

    /* ──────────────────────────────────────────
     * LINKS
     * ────────────────────────────────────────── */

    public static function count_links($profile_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}blp_links WHERE profile_id = %d",
            $profile_id
        ));
    }

    public static function get_links($profile_id, $visible_only = false) {
        global $wpdb;
        $table = "{$wpdb->prefix}blp_links";
        if ($visible_only) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE profile_id = %d AND is_visible = 1 ORDER BY sort_order ASC, id ASC",
                $profile_id
            ));
        }
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE profile_id = %d ORDER BY sort_order ASC, id ASC",
            $profile_id
        ));
    }

    public static function get_link($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}blp_links WHERE id = %d LIMIT 1",
            $id
        ));
    }

    public static function add_link(array $data) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}blp_links", $data);
        return $wpdb->insert_id;
    }

    public static function update_link($id, array $data) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}blp_links", $data, ['id' => $id]);
    }

    public static function delete_link($id) {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}blp_analytics", ['link_id' => $id]);
        return $wpdb->delete("{$wpdb->prefix}blp_links", ['id' => $id]);
    }

    public static function update_sort_order(array $orders) {
        global $wpdb;
        foreach ($orders as $id => $order) {
            $wpdb->update("{$wpdb->prefix}blp_links", ['sort_order' => (int)$order], ['id' => (int)$id]);
        }
    }

    /* ──────────────────────────────────────────
     * ANALYTICS
     * ────────────────────────────────────────── */

    public static function track_event(array $data, $ab_variant = 'a') {
        global $wpdb;

        // Detect device type from user agent
        $ua = $data['user_agent'] ?? '';
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
            $data['device'] = strpos(strtolower($ua), 'tablet') !== false || strpos(strtolower($ua), 'ipad') !== false ? 'tablet' : 'mobile';
        } else {
            $data['device'] = 'desktop';
        }

        // Store analytics timestamps in UTC for consistent querying across environments.
        if (empty($data['clicked_at'])) {
            $data['clicked_at'] = current_time('mysql', true);
        }

        $wpdb->insert("{$wpdb->prefix}blp_analytics", $data);

        // Increment link click count (A/B variant aware)
        if (!empty($data['link_id']) && $data['event_type'] === 'click') {
            $col = ($ab_variant === 'b') ? 'click_count_b' : 'click_count';
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}blp_links SET {$col} = {$col} + 1 WHERE id = %d",
                $data['link_id']
            ));
        }
    }

    public static function get_analytics($profile_id, $days = 30) {
        global $wpdb;

        $days = min(365, max(1, (int) $days));

        $cache_key = 'blp_analytics_' . $profile_id . '_' . $days;
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $wp_tz = wp_timezone();
        $utc_tz = new \DateTimeZone('UTC');

        $now_local = new \DateTime('now', $wp_tz);
        $period_start_local = (clone $now_local)->modify('-' . $days . ' days');
        $period_start_utc = (clone $period_start_local)->setTimezone($utc_tz)->format('Y-m-d H:i:s');

        $today_start_local = (clone $now_local)->setTime(0, 0, 0);
        $today_end_local = (clone $today_start_local)->modify('+1 day');
        $today_start_utc = (clone $today_start_local)->setTimezone($utc_tz)->format('Y-m-d H:i:s');
        $today_end_utc = (clone $today_end_local)->setTimezone($utc_tz)->format('Y-m-d H:i:s');

        $daily = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(clicked_at) AS date, COUNT(*) AS clicks
             FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d
               AND event_type = 'click'
               AND clicked_at >= %s
             GROUP BY DATE(clicked_at)
             ORDER BY date ASC",
            $profile_id, $period_start_utc
        ));

        // Top links by clicks within the selected period
        $top_links = $wpdb->get_results($wpdb->prepare(
            "SELECT l.id, l.title, l.url,
                    COUNT(a.id) AS period_clicks
             FROM {$wpdb->prefix}blp_links l
             LEFT JOIN {$wpdb->prefix}blp_analytics a
               ON a.link_id = l.id
               AND a.event_type = 'click'
               AND a.clicked_at >= %s
             WHERE l.profile_id = %d
             GROUP BY l.id
             ORDER BY period_clicks DESC
             LIMIT 10",
            $period_start_utc, $profile_id
        ));

        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT device, COUNT(*) AS count
             FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d AND event_type = 'click'
               AND clicked_at >= %s
             GROUP BY device",
            $profile_id, $period_start_utc
        ));

        // Period-scoped totals (consistent with $days parameter)
        $total_views = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d AND event_type = 'view'
               AND clicked_at >= %s",
            $profile_id, $period_start_utc
        ));

        $total_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d AND event_type = 'click'
               AND clicked_at >= %s",
            $profile_id, $period_start_utc
        ));

        $today_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d
               AND event_type = 'click'
               AND clicked_at >= %s
               AND clicked_at < %s",
            $profile_id,
            $today_start_utc,
            $today_end_utc
        ));

        // All-time page views (from the profiles counter)
        $all_time_views = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT page_views FROM {$wpdb->prefix}blp_profiles WHERE id = %d",
            $profile_id
        ));

        // Top referrers
        $referrers = $wpdb->get_results($wpdb->prepare(
            "SELECT
                CASE WHEN referrer = '' OR referrer IS NULL THEN 'Direct' ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1) END AS source,
                COUNT(*) AS count
             FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d
               AND clicked_at >= %s
             GROUP BY source
             ORDER BY count DESC
             LIMIT 10",
            $profile_id, $period_start_utc
        ));

        // Heatmap: clicks by hour and day-of-week
        $heatmap = $wpdb->get_results($wpdb->prepare(
            "SELECT DAYOFWEEK(clicked_at) AS dow, HOUR(clicked_at) AS hour, COUNT(*) AS count
             FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d AND event_type = 'click'
               AND clicked_at >= %s
             GROUP BY dow, hour
             ORDER BY dow, hour",
            $profile_id, $period_start_utc
        ));

        // Previous period comparison
        $prev_start_local = (clone $period_start_local)->modify('-' . $days . ' days');
        $prev_start_utc = (clone $prev_start_local)->setTimezone($utc_tz)->format('Y-m-d H:i:s');

        $prev_views = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d AND event_type = 'view'
               AND clicked_at >= %s AND clicked_at < %s",
            $profile_id, $prev_start_utc, $period_start_utc
        ));

        $prev_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}blp_analytics
             WHERE profile_id = %d AND event_type = 'click'
               AND clicked_at >= %s AND clicked_at < %s",
            $profile_id, $prev_start_utc, $period_start_utc
        ));

        $result = compact('daily', 'top_links', 'devices', 'referrers', 'heatmap', 'total_views', 'total_clicks', 'today_clicks', 'all_time_views', 'prev_views', 'prev_clicks');
        set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        return $result;
    }

    /**
     * Delete analytics records older than $days days.
     * Called by WP Cron (blp_cleanup_old_analytics).
     */
    public static function cleanup_old_analytics($days = 180) {
        global $wpdb;
        $days = max(30, (int) $days);
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}blp_analytics WHERE clicked_at < %s",
            $cutoff
        ));
    }
}
