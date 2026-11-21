<?php
/**
 * Uninstall routine.
 *
 * @package HarrierCache
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

foreach (array('hc_stats', 'hc_stats_url', 'hc_report_at', 'hc_install_at', 'hc_settings', 'hc_consent', 'hc_consent_secrets', 'hc_report_v', 'hc_touch', 'hc_metrics_due') as $option) {
    delete_option($option);
}

foreach (array('hc_seen', 'hc_due', 'hc_sent', 'hc_since', 'hc_env_check', 'hc_cloud', 'hc_policy') as $key) {
    delete_transient($key);
}

foreach (array('hc_cache_expire', 'hc_cache_preload', 'hc_db_maintenance') as $hook) {
    wp_clear_scheduled_hook($hook);
}

global $wpdb;
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'harriercache_redirects');
delete_option('hc_redirects_db');

$dir = WP_CONTENT_DIR . '/cache/harriercache';

foreach ((array) glob($dir . '/*') as $file) {
    if (is_file($file)) {
        @unlink($file);
    }
}

if (is_dir($dir)) {
    @rmdir($dir);
}
