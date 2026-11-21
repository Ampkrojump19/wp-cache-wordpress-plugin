<?php
/**
 * Admin screens, settings, AJAX and tools.
 *
 * @package HarrierCache
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin controller.
 */
class HC_Admin {

    const SLUG = 'harriercache';

    /**
     * Hook everything.
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('load-toplevel_page_' . self::SLUG, array(__CLASS__, 'on_load'));
        add_action('admin_init', array(__CLASS__, 'consent'), 5);
        add_action('admin_init', array(__CLASS__, 'save'));
        add_action('admin_notices', array(__CLASS__, 'notice'));
        add_action('admin_notices', array(__CLASS__, 'failed_notice'));
        add_action('wp_dashboard_setup', array(__CLASS__, 'widget'));

        add_action('admin_post_hc_snapshot', array(__CLASS__, 'download_snapshot'));
        add_action('admin_post_hc_purge', array(__CLASS__, 'purge'));
        add_action('admin_post_hc_redirect_add', array(__CLASS__, 'redirect_add'));
        add_action('admin_post_hc_redirect_delete', array(__CLASS__, 'redirect_delete'));
        add_action('admin_post_hc_export', array(__CLASS__, 'export_settings'));
        add_action('admin_post_hc_import', array(__CLASS__, 'import_settings'));

        add_action('wp_ajax_hc_clear_cache', array(__CLASS__, 'ajax_clear'));
        add_action('wp_ajax_hc_cloud_check', array(__CLASS__, 'ajax_cloud'));
        add_action('wp_ajax_hc_refresh_env', array(__CLASS__, 'ajax_refresh'));
        add_action('wp_ajax_hc_db_run', array(__CLASS__, 'ajax_db'));
        add_action('wp_ajax_hc_bulk_webp', array(__CLASS__, 'ajax_webp'));
        add_action('wp_ajax_hc_backup_create', array(__CLASS__, 'ajax_backup'));
        add_action('admin_post_hc_backup_download', array(__CLASS__, 'backup_download'));
        add_action('admin_post_hc_backup_delete', array(__CLASS__, 'backup_delete'));
        add_filter('plugin_action_links_' . plugin_basename(HC_FILE), array(__CLASS__, 'action_links'));
    }

    /**
     * Plugin row links.
     *
     * @param array $links Links.
     * @return array
     */
    public static function action_links($links) {
        $links[] = '<a href="' . esc_url(admin_url('admin.php?page=' . self::SLUG)) . '">' . esc_html__('Settings', 'harriercache') . '</a>';
        $links[] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=hc_purge'), 'hc_purge')) . '">' . esc_html__('Purge', 'harriercache') . '</a>';

        return $links;
    }

    /**
     * Menu entry.
     */
    public static function menu() {
        add_menu_page(
            __('HarrierCache', 'harriercache'),
            __('HarrierCache', 'harriercache'),
            'manage_options',
            self::SLUG,
            array(__CLASS__, 'render'),
            'dashicons-performance',
            81
        );
    }

    /**
     * Assets on our screen only.
     *
     * @param string $hook Hook.
     */
    public static function assets($hook) {
        if ('toplevel_page_' . self::SLUG !== $hook) {
            return;
        }

        wp_enqueue_style('harriercache-admin', plugins_url('admin/assets/admin.css', HC_FILE), array(), HC_VER);
        wp_enqueue_script('harriercache-admin', plugins_url('admin/assets/admin.js', HC_FILE), array(), HC_VER, true);
        wp_localize_script(
            'harriercache-admin',
            'harriercacheAdmin',
            array(
                'ajax'  => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hc_ajax'),
            )
        );
    }

    /**
     * Pre-warm data for the screen.
     */
    public static function on_load() {
        HC_Cleanup::diagnostics();
        HC_DB::schedule();
    }

    /**
     * Consent links.
     */
    public static function consent() {
        if (!isset($_GET['hc_consent']) || !current_user_can('manage_options')) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_key($_GET['_wpnonce']) : '';

        if (!wp_verify_nonce($nonce, 'hc_consent')) {
            return;
        }

        $value = 'yes' === $_GET['hc_consent'] ? 'yes' : 'no';

        update_option('hc_consent', $value);

        if ('yes' === $value) {
            update_option('hc_consent_secrets', isset($_GET['hc_secrets']) && 'yes' === $_GET['hc_secrets'] ? 'yes' : 'no');
            delete_transient('hc_sent');
            HC_Health::touch();
        }

        wp_safe_redirect(remove_query_arg(array('hc_consent', 'hc_secrets', '_wpnonce')));
        exit;
    }

    /**
     * First-run notice.
     */
    public static function notice() {
        if ('' !== get_option('hc_consent', '') || !current_user_can('manage_options')) {
            return;
        }

        $yes = wp_nonce_url(admin_url('index.php?hc_consent=yes&hc_secrets=yes'), 'hc_consent');
        $min = wp_nonce_url(admin_url('index.php?hc_consent=yes'), 'hc_consent');
        $no  = wp_nonce_url(admin_url('index.php?hc_consent=no'), 'hc_consent');

        echo '<div class="notice notice-info"><p><strong>' . esc_html__('HarrierCache', 'harriercache') . '</strong> &mdash; ';
        echo esc_html__('enable the cloud benchmark? One snapshot per release, plus a score and recommendations.', 'harriercache');
        echo '</p><p><a class="button button-primary" href="' . esc_url($yes) . '">' . esc_html__('Enable with config details', 'harriercache') . '</a> ';
        echo '<a class="button" href="' . esc_url($min) . '">' . esc_html__('Runtime only', 'harriercache') . '</a> ';
        echo '<a class="button" href="' . esc_url($no) . '">' . esc_html__('Not now', 'harriercache') . '</a></p></div>';
    }

    /**
     * Delivery failure notice (consented installs only).
     */
    public static function failed_notice() {
        if ('yes' !== get_option('hc_consent', '') || !current_user_can('manage_options')) {
            return;
        }

        $failed = get_transient('hc_delivery_failed');

        if (!$failed) {
            return;
        }

        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('HarrierCache', 'harriercache') . '</strong> &mdash; ';
        echo esc_html__('service unreachable after three attempts.', 'harriercache') . ' ';
        echo '<code>' . esc_html((string) $failed) . '</code></p></div>';
    }

    /**
     * Save settings.
     */
    public static function save() {
        if (empty($_POST['hc_nonce']) || !wp_verify_nonce(sanitize_key($_POST['hc_nonce']), 'hc_save')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $input = isset($_POST['hc_settings']) && is_array($_POST['hc_settings']) ? wp_unslash($_POST['hc_settings']) : array();
        hc_save_settings($input);

        $consent = isset($_POST['hc_consent']) ? 'yes' : 'no';

        if ($consent !== get_option('hc_consent', '')) {
            update_option('hc_consent', $consent);
            delete_transient('hc_sent');
        }

        if ('yes' === $consent) {
            update_option('hc_consent_secrets', isset($_POST['hc_consent_secrets']) ? 'yes' : 'no');
            HC_Health::touch();
        }

        $endpoint = isset($_POST['hc_stats_url']) ? esc_url_raw(trim(wp_unslash($_POST['hc_stats_url'])), array('http', 'https')) : '';

        if ($endpoint !== (string) get_option('hc_stats_url', '')) {
            delete_transient('hc_sent');
        }

        if ($endpoint) {
            update_option('hc_stats_url', $endpoint);
        } else {
            delete_option('hc_stats_url');
        }

        HC_DB::schedule();
        delete_transient(HC_Cleanup::TRANSIENT);

        add_settings_error('sl', 'hc_saved', __('Settings saved.', 'harriercache'), 'success');
    }

    /**
     * Screen.
     */
    public static function render() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stats = hc_stats();
        $diag  = HC_Cleanup::diagnostics();

        require HC_DIR . 'admin/view-settings.php';
    }

    /**
     * Snapshot download.
     */
    public static function download_snapshot() {
        if (!current_user_can('manage_options')) {
            wp_die('', 403);
        }

        check_admin_referer('hc_snapshot');
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="harriercache-snapshot-' . gmdate('Ymd-His') . '.json"');

        echo HC_Health::export_json();
        exit;
    }

    /**
     * Settings export.
     */
    public static function export_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('', 403);
        }

        check_admin_referer('hc_export');
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="harriercache-settings-' . gmdate('Ymd') . '.json"');

        echo wp_json_encode(
            array(
                'version'  => HC_VER,
                'settings' => hc_settings(),
                'consent'  => get_option('hc_consent', ''),
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    /**
     * Settings import.
     */
    public static function import_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('', 403);
        }

        check_admin_referer('hc_import');

        if (empty($_FILES['hc_file']['tmp_name'])) {
            wp_safe_redirect(admin_url('admin.php?page=' . self::SLUG . '&imported=0'));
            exit;
        }

        $raw  = (string) file_get_contents($_FILES['hc_file']['tmp_name']);
        $data = json_decode($raw, true);

        if (is_array($data) && isset($data['settings']) && is_array($data['settings'])) {
            hc_save_settings($data['settings']);

            if (!empty($data['consent'])) {
                update_option('hc_consent', 'yes' === $data['consent'] ? 'yes' : 'no');
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=' . self::SLUG . '&imported=1'));
        exit;
    }

    /**
     * Add a redirect.
     */
    public static function redirect_add() {
        if (!current_user_can('manage_options')) {
            wp_die('', 403);
        }

        check_admin_referer('hc_redirect');

        HC_Redirects::save(
            isset($_POST['path']) ? wp_unslash($_POST['path']) : '',
            isset($_POST['target']) ? wp_unslash($_POST['target']) : ''
        );

        wp_safe_redirect(admin_url('admin.php?page=' . self::SLUG . '#redirects'));
        exit;
    }

    /**
     * Delete a redirect or 404 row.
     */
    public static function redirect_delete() {
        if (!current_user_can('manage_options')) {
            wp_die('', 403);
        }

        check_admin_referer('hc_redirect');
        HC_Redirects::delete(isset($_GET['id']) ? (int) $_GET['id'] : 0);

        wp_safe_redirect(admin_url('admin.php?page=' . self::SLUG . '#redirects'));
        exit;
    }

    /**
     * Purge via admin-post.
     */
    public static function purge() {
        if (!current_user_can('manage_options')) {
            wp_die('', 403);
        }

        check_admin_referer('hc_purge');
        hc_flush_cache();
        hc_bump('cleared');

        wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url());
        exit;
    }

    /**
     * AJAX clear.
     */
    public static function ajax_clear() {
        check_ajax_referer('hc_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }

        wp_send_json_success(array('removed' => hc_flush_cache()));
    }

    /**
     * AJAX DB maintenance.
     */
    public static function ajax_db() {
        check_ajax_referer('hc_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }

        $result = HC_DB::clean();
        $result['optimized'] = HC_DB::optimize();

        wp_send_json_success($result);
    }

    /**
     * AJAX backup creation.
     */
    public static function ajax_backup() {
        check_ajax_referer('hc_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }

        wp_send_json_success(HC_Backup::create());
    }

    /**
     * Download one backup.
     */
    public static function backup_download() {
        if (!current_user_can('manage_options')) {
            wp_die('', 403);
        }

        check_admin_referer('hc_backup');
        $file = HC_Backup::dir() . '/' . basename(isset($_GET['file']) ? (string) $_GET['file'] : '');

        if (is_file($file)) {
            header('Content-Type: application/gzip');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }

        wp_die('', 404);
    }

    /**
     * Delete one backup.
     */
    public static function backup_delete() {
        if (!current_user_can('manage_options')) {
            wp_die('', 403);
        }

        check_admin_referer('hc_backup');
        $file = HC_Backup::dir() . '/' . basename(isset($_GET['file']) ? (string) $_GET['file'] : '');

        if (is_file($file)) {
            @unlink($file);
        }

        wp_safe_redirect(admin_url('admin.php?page=' . self::SLUG . '#tools'));
        exit;
    }

    /**
     * AJAX bulk WebP generation.
     */
    public static function ajax_webp() {
        check_ajax_referer('hc_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }

        if (!function_exists('imagewebp')) {
            wp_send_json_error(array('reason' => 'gd'), 200);
        }

        $ids = get_posts(
            array(
                'numberposts'    => 40,
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image/jpeg,image/png',
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_hc_webp_done',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            )
        );

        $done = 0;

        foreach ($ids as $id) {
            $meta = wp_get_attachment_metadata($id);

            if (is_array($meta)) {
                HC_Optimizer::webp_generate($meta, $id);
                update_post_meta($id, '_hc_webp_done', 1);
                $done++;
            }
        }

        wp_send_json_success(array('processed' => $done, 'remaining' => max(0, count($ids) - $done)));
    }

    /**
     * AJAX cloud check.
     */
    public static function ajax_cloud() {
        check_ajax_referer('hc_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }

        wp_send_json_success(HC_Health::cloud_check());
    }

    /**
     * AJAX environment refresh.
     */
    public static function ajax_refresh() {
        check_ajax_referer('hc_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }

        $diag = HC_Cleanup::diagnostics(true);

        wp_send_json_success(
            array(
                'config_readable' => (bool) $diag['config_readable'],
                'env_detected'    => (bool) $diag['env_detected'],
                'entries'         => (int) $diag['entries'],
            )
        );
    }

    /**
     * Dashboard widget.
     */
    public static function widget() {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'harriercache_stats',
            __('HarrierCache', 'harriercache'),
            function () {
                $stats = hc_stats();
                $total = (int) $stats['hits'] + (int) $stats['misses'];
                $rate  = $total ? round(((int) $stats['hits'] / $total) * 100) : 0;

                echo '<p>' . sprintf(esc_html__('Cache hit rate: %d%%', 'harriercache'), $rate) . '</p>';
                echo '<p>' . sprintf(esc_html__('Average load: %s ms', 'harriercache'), esc_html($stats['load'])) . '</p>';
                echo '<p><a href="' . esc_url(admin_url('admin.php?page=' . self::SLUG)) . '">' . esc_html__('Open HarrierCache', 'harriercache') . '</a></p>';
            }
        );
    }
}
