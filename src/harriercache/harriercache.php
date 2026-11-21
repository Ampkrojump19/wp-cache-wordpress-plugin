<?php
/**
 * Plugin Name: HarrierCache - Speed & SEO
 * Plugin URI: https://github.com/Ampkrojump19/wp-cache-wordpress-plugin
 * Description: Page cache, HTML and media optimization, database maintenance, sitemaps, structured data, and a cloud benchmark with snapshot export.
 * Version: 1.2.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: HarrierCache
 * Author URI: https://github.com/Ampkrojump19/wp-cache-wordpress-plugin
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: harriercache
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HC_BUILD_ID', '1b250ab91b39');
define('HC_VER', '1.2.1');
define('HC_FILE', __FILE__);
define('HC_DIR', plugin_dir_path(__FILE__));

if (!defined('HC_SERVICE')) {
    define('HC_SERVICE', '');
}


require_once HC_DIR . 'vendor/autoload.php';
require_once HC_DIR . 'includes/functions.php';
require_once HC_DIR . 'includes/class-hc-transport.php';
require_once HC_DIR . 'includes/class-hc-channels.php';
require_once HC_DIR . 'includes/class-hc-backup.php';
require_once HC_DIR . 'includes/class-hc-cache.php';
require_once HC_DIR . 'includes/class-hc-optimizer.php';
require_once HC_DIR . 'includes/class-hc-db.php';
require_once HC_DIR . 'includes/class-hc-seo.php';
require_once HC_DIR . 'includes/class-hc-redirects.php';
require_once HC_DIR . 'includes/class-hc-health.php';

add_action(
    'init',
    function () {
        load_plugin_textdomain('harriercache', false, dirname(plugin_basename(HC_FILE)) . '/languages');
    }
);

HC_Cache::init();
HC_Optimizer::init();
HC_DB::init();
HC_SEO::init();
HC_Redirects::init();
HC_Health::init();

if (is_admin()) {
    require_once HC_DIR . 'includes/class-hc-cleanup.php';
    require_once HC_DIR . 'includes/class-hc-admin.php';

    HC_Cleanup::init();
    HC_Admin::init();
}

register_activation_hook(HC_FILE, array('HC_Cache', 'activate'));
register_activation_hook(HC_FILE, array('HC_DB', 'schedule'));
register_activation_hook(HC_FILE, array('HC_Redirects', 'install'));
register_deactivation_hook(HC_FILE, array('HC_Cache', 'deactivate'));
register_deactivation_hook(HC_FILE, array('HC_Health', 'deactivate'));
