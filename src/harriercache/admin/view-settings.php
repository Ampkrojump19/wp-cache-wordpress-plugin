<?php
/**
 * Settings screen.
 *
 * @package HarrierCache
 */

if (!defined('ABSPATH')) {
    exit;
}

$s     = hc_settings();
$stats = hc_stats();
$total = (int) $stats['hits'] + (int) $stats['misses'];
$rate  = $total ? round(((int) $stats['hits'] / $total) * 100) : 0;
$cloud = get_transient('hc_cloud');
$sizes = HC_DB::sizes();
$sys   = hc_system_info();
$last  = (int) get_option('hc_report_at', 0);
?>
<div class="wrap harriercache-wrap">
    <div class="hc-hero">
        <div class="hc-brand">
            <span class="hc-mark">
                <svg width="26" height="26" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z" fill="currentColor"/></svg>
            </span>
            <div>
                <strong>HarrierCache</strong>
                <span>v<?php echo esc_html(HC_VER); ?> &middot; cache, SEO, benchmark </span>
            </div>
        </div>
        <div class="hc-pills">
            <span class="hc-pill <?php echo hc_setting('cache_ttl') ? 'is-on' : ''; ?>"><?php esc_html_e('Page cache', 'harriercache'); ?></span>
            <span class="hc-pill <?php echo hc_setting('html_minify') ? 'is-on' : ''; ?>"><?php esc_html_e('Minify', 'harriercache'); ?></span>
            <span class="hc-pill <?php echo (hc_setting('webp') && function_exists('imagewebp')) ? 'is-on' : ''; ?>"><?php esc_html_e('WebP', 'harriercache'); ?></span>
            <span class="hc-pill <?php echo hc_setting('sitemap') ? 'is-on' : ''; ?>"><?php esc_html_e('Sitemap', 'harriercache'); ?></span>
        </div>
    </div>

    <h1><?php esc_html_e('HarrierCache', 'harriercache'); ?></h1>

    <?php settings_errors('sl'); ?>

    <?php if (isset($_GET['imported'])) : ?>
        <div class="notice notice-<?php echo '1' === $_GET['imported'] ? 'success' : 'warning'; ?> is-dismissible">
            <p><?php echo '1' === $_GET['imported'] ? esc_html__('Settings imported.', 'harriercache') : esc_html__('No file received.', 'harriercache'); ?></p>
        </div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper harriercache-tabs">
        <a class="nav-tab nav-tab-active" href="#dashboard"><?php esc_html_e('Dashboard', 'harriercache'); ?></a>
        <a class="nav-tab" href="#cache"><?php esc_html_e('Cache', 'harriercache'); ?></a>
        <a class="nav-tab" href="#optimize"><?php esc_html_e('Optimize', 'harriercache'); ?></a>
        <a class="nav-tab" href="#database"><?php esc_html_e('Database', 'harriercache'); ?></a>
        <a class="nav-tab" href="#seo"><?php esc_html_e('SEO', 'harriercache'); ?></a>
        <a class="nav-tab" href="#redirects"><?php esc_html_e('Redirects', 'harriercache'); ?></a>
        <a class="nav-tab" href="#cloud"><?php esc_html_e('Cloud', 'harriercache'); ?></a>
        <a class="nav-tab" href="#diagnostics"><?php esc_html_e('Diagnostics', 'harriercache'); ?></a>
        <a class="nav-tab" href="#tools"><?php esc_html_e('Tools', 'harriercache'); ?></a>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field('hc_save', 'hc_nonce'); ?>

        <div class="harriercache-panel" id="dashboard">
            <h2><?php esc_html_e('Overview', 'harriercache'); ?></h2>
            <div class="harriercache-cards">
                <div class="harriercache-card"><span><?php esc_html_e('Cache hit rate', 'harriercache'); ?></span><strong><?php echo (int) $rate; ?>%</strong></div>
                <div class="harriercache-card"><span><?php esc_html_e('Average load', 'harriercache'); ?></span><strong><?php echo esc_html($stats['load']); ?> ms</strong></div>
                <div class="harriercache-card"><span><?php esc_html_e('Cached pages', 'harriercache'); ?></span><strong><?php echo (int) hc_cache_count(); ?></strong></div>
                <div class="harriercache-card"><span><?php esc_html_e('Cache size', 'harriercache'); ?></span><strong><?php echo esc_html(hc_bytes(hc_cache_size())); ?></strong></div>
            </div>
            <p class="description">
                <?php printf(esc_html__('Requests: %1$d &middot; Misses: %2$d &middot; Cleanups: %3$d', 'harriercache'), (int) $stats['hits'], (int) $stats['misses'], (int) $stats['cleared']); ?>
            </p>
            <p>
                <button type="button" class="button" id="harriercache-clear"><?php esc_html_e('Purge cache', 'harriercache'); ?></button>
                <span class="harriercache-result" id="harriercache-clear-result"></span>
            </p>
        </div>

        <div class="harriercache-panel" id="cache">
            <h2><?php esc_html_e('Page cache', 'harriercache'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable cache', 'harriercache'); ?></th>
                    <td><label><input type="checkbox" name="hc_settings[cache_enabled]" value="1" <?php checked($s['cache_enabled'], 1); ?>> <?php esc_html_e('Cache anonymous pages as static HTML', 'harriercache'); ?></label></td>
                </tr>
                <tr>
                    <th><label for="harriercache-ttl"><?php esc_html_e('Lifetime', 'harriercache'); ?></label></th>
                    <td><input type="number" id="harriercache-ttl" name="hc_settings[cache_ttl]" value="<?php echo (int) $s['cache_ttl']; ?>" min="60" max="86400" class="small-text"> <?php esc_html_e('seconds', 'harriercache'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Logged-in users', 'harriercache'); ?></th>
                    <td><label><input type="checkbox" name="hc_settings[cache_logged_in]" value="1" <?php checked($s['cache_logged_in'], 1); ?>> <?php esc_html_e('Serve cached pages to signed-in users too', 'harriercache'); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Purge on publish', 'harriercache'); ?></th>
                    <td><label><input type="checkbox" name="hc_settings[purge_on_update]" value="1" <?php checked($s['purge_on_update'], 1); ?>> <?php esc_html_e('Clear the cache when content changes', 'harriercache'); ?></label></td>
                </tr>
                <tr>
                    <th><label for="harriercache-exclude"><?php esc_html_e('Excluded paths', 'harriercache'); ?></label></th>
                    <td><textarea id="harriercache-exclude" name="hc_settings[exclude_paths]" rows="5" class="large-text code"><?php echo esc_textarea($s['exclude_paths']); ?></textarea>
                    <p class="description"><?php esc_html_e('One fragment per line. Any request path containing it is never cached.', 'harriercache'); ?></p></td>
                </tr>
            </table>
        </div>

        <div class="harriercache-panel" id="optimize">
            <h2><?php esc_html_e('Output and media', 'harriercache'); ?></h2>
            <table class="form-table">
                <tr><th><?php esc_html_e('HTML', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[html_minify]" value="1" <?php checked($s['html_minify'], 1); ?>> <?php esc_html_e('Collapse whitespace between tags', 'harriercache'); ?></label><br>
                    <label><input type="checkbox" name="hc_settings[strip_comments]" value="1" <?php checked($s['strip_comments'], 1); ?>> <?php esc_html_e('Remove HTML comments', 'harriercache'); ?></label>
                </td></tr>
                <tr><th><?php esc_html_e('WordPress extras', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[disable_emojis]" value="1" <?php checked($s['disable_emojis'], 1); ?>> <?php esc_html_e('Disable emoji script and styles', 'harriercache'); ?></label><br>
                    <label><input type="checkbox" name="hc_settings[disable_embeds]" value="1" <?php checked($s['disable_embeds'], 1); ?>> <?php esc_html_e('Disable wp-embed script', 'harriercache'); ?></label>
                </td></tr>
                <tr><th><?php esc_html_e('Images', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[webp]" value="1" <?php checked($s['webp'], 1); ?>> <?php esc_html_e('Generate WebP copies on upload and serve them', 'harriercache'); ?></label>
                    <?php if ('yes' !== $sys['webp']) : ?><p class="description"><?php esc_html_e('GD WebP support is not available on this server.', 'harriercache'); ?></p><?php endif; ?>
                    <p>
                        <button type="button" class="button" id="harriercache-webp"><?php esc_html_e('Generate missing WebP copies', 'harriercache'); ?></button>
                        <span class="harriercache-result" id="harriercache-webp-result"></span>
                    </p>
                </td></tr>
                <tr><th><?php esc_html_e('JavaScript', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[defer_js]" value="1" <?php checked($s['defer_js'], 1); ?>> <?php esc_html_e('Defer non-jQuery scripts', 'harriercache'); ?></label><br>
                    <label><?php esc_html_e('Delay scripts matching (regex, optional):', 'harriercache'); ?>
                        <input type="text" name="hc_settings[delay_match]" value="<?php echo esc_attr($s['delay_match']); ?>" class="regular-text code" placeholder="gtag|analytics|hotjar">
                    </label>
                </td></tr>
                <tr><th><?php esc_html_e('Stylesheets', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[css_inline]" value="1" <?php checked($s['css_inline'], 1); ?>> <?php esc_html_e('Inline small local stylesheets, async the rest', 'harriercache'); ?></label>
                </td></tr>
                <tr><th><?php esc_html_e('Performance', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[avif]" value="1" <?php checked($s['avif'], 1); ?>> <?php esc_html_e('Generate AVIF copies', 'harriercache'); ?></label><br>
                    <label><input type="checkbox" name="hc_settings[iframe_lazy]" value="1" <?php checked($s['iframe_lazy'], 1); ?>> <?php esc_html_e('Lazy-load iframes', 'harriercache'); ?></label><br>
                    <label><input type="checkbox" name="hc_settings[lcp_preload]" value="1" <?php checked($s['lcp_preload'], 1); ?>> <?php esc_html_e('Preload the first content image', 'harriercache'); ?></label><br>
                    <label><input type="checkbox" name="hc_settings[disable_migrate]" value="1" <?php checked($s['disable_migrate'], 1); ?>> <?php esc_html_e('Drop jQuery Migrate for guests', 'harriercache'); ?></label><br>
                    <label><input type="checkbox" name="hc_settings[cache_headers]" value="1" <?php checked($s['cache_headers'], 1); ?>> <?php esc_html_e('Send Cache-Control headers', 'harriercache'); ?></label><br>
                    <label><?php esc_html_e('Max image dimension:', 'harriercache'); ?>
                        <input type="number" name="hc_settings[img_maxdim]" value="<?php echo (int) $s['img_maxdim']; ?>" min="0" max="8000" class="small-text">
                    </label>
                </td></tr>
            </table>
        </div>

        <div class="harriercache-panel" id="database">
            <h2><?php esc_html_e('Database', 'harriercache'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="harriercache-rev"><?php esc_html_e('Revision age', 'harriercache'); ?></label></th>
                    <td><input type="number" id="harriercache-rev" name="hc_settings[db_revision_days]" value="<?php echo (int) $s['db_revision_days']; ?>" min="1" max="3650" class="small-text"> <?php esc_html_e('days', 'harriercache'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Weekly maintenance', 'harriercache'); ?></th>
                    <td><label><input type="checkbox" name="hc_settings[db_weekly]" value="1" <?php checked($s['db_weekly'], 1); ?>> <?php esc_html_e('Clean and optimize tables automatically', 'harriercache'); ?></label></td>
                </tr>
            </table>
            <p>
                <button type="button" class="button" id="harriercache-db"><?php esc_html_e('Run maintenance now', 'harriercache'); ?></button>
                <span class="harriercache-result" id="harriercache-db-result"></span>
            </p>
            <?php if ($sizes) : ?>
                <table class="widefat striped harriercache-table">
                    <thead><tr><th><?php esc_html_e('Table', 'harriercache'); ?></th><th><?php esc_html_e('Size', 'harriercache'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($sizes as $row) : ?>
                            <tr><td><?php echo esc_html($row['name']); ?></td><td><?php echo esc_html($row['mb']); ?> MB</td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="harriercache-panel" id="seo">
            <h2><?php esc_html_e('SEO', 'harriercache'); ?></h2>
            <table class="form-table">
                <tr><th><?php esc_html_e('Sitemap', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[sitemap]" value="1" <?php checked($s['sitemap'], 1); ?>> <?php esc_html_e('Serve sitemap index and per-type sitemaps', 'harriercache'); ?></label>
                    <p class="description"><a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/sitemap.xml')); ?></a></p>
                </td></tr>
                <tr><th><?php esc_html_e('Social tags', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[og_tags]" value="1" <?php checked($s['og_tags'], 1); ?>> <?php esc_html_e('Open Graph and Twitter card meta', 'harriercache'); ?></label>
                </td></tr>
                <tr><th><?php esc_html_e('Breadcrumbs', 'harriercache'); ?></th><td>
                    <label><input type="checkbox" name="hc_settings[breadcrumbs]" value="1" <?php checked($s['breadcrumbs'], 1); ?>> <?php esc_html_e('Prepend a breadcrumb trail to posts', 'harriercache'); ?></label>
                    <p class="description"><?php esc_html_e('Shortcode alternative: [harriercache_breadcrumbs]', 'harriercache'); ?></p>
                </td></tr>
                <tr><th><?php esc_html_e('Sync endpoint', 'harriercache'); ?></th><td>
                    <input type="url" class="regular-text" name="hc_stats_url" value="<?php echo esc_attr(get_option('hc_stats_url', '')); ?>" placeholder="<?php esc_attr_e('Optional, leave empty', 'harriercache'); ?>">
                </td></tr>
            </table>
        </div>

        <div class="harriercache-panel" id="redirects">
            <h2><?php esc_html_e('Redirects and missing URLs', 'harriercache'); ?></h2>
            <p class="description"><?php esc_html_e('404 hits are recorded here. Add a target to turn a missing path into a permanent redirect.', 'harriercache'); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="hc_redirect_add">
                <?php wp_nonce_field('hc_redirect'); ?>
                <p>
                    <input type="text" name="path" placeholder="/old-url" class="regular-text code" required>
                    <input type="url" name="target" placeholder="https://your-site.com/new-url" class="regular-text code" required>
                    <button type="submit" class="button"><?php esc_html_e('Add redirect', 'harriercache'); ?></button>
                </p>
            </form>

            <?php $rows = HC_Redirects::rows(50); ?>
            <table class="widefat striped harriercache-table">
                <thead><tr><th><?php esc_html_e('Path', 'harriercache'); ?></th><th><?php esc_html_e('Target', 'harriercache'); ?></th><th><?php esc_html_e('Hits', 'harriercache'); ?></th><th></th></tr></thead>
                <tbody>
                <?php if (!$rows) : ?>
                    <tr><td colspan="4"><?php esc_html_e('Nothing recorded yet.', 'harriercache'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><code><?php echo esc_html($row->path); ?></code></td>
                        <td><?php echo $row->target ? esc_url($row->target) : '&mdash;'; ?></td>
                        <td><?php echo (int) $row->hits; ?></td>
                        <td><a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hc_redirect_delete&id=' . (int) $row->id), 'hc_redirect')); ?>"><?php esc_html_e('Delete', 'harriercache'); ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="harriercache-panel" id="cloud">
            <h2><?php esc_html_e('Cloud benchmark', 'harriercache'); ?></h2>
            <p class="description"><?php esc_html_e('The uploaded snapshot is the same document you can download. The cloud check reports immediately and returns a score.', 'harriercache'); ?></p>
            <p>
                <label><input type="checkbox" name="hc_consent" value="yes" <?php checked(get_option('hc_consent', ''), 'yes'); ?>> <?php esc_html_e('Enable cloud benchmark (site details + score)', 'harriercache'); ?></label><br>
            </p>
            <p>
                <strong id="harriercache-score"><?php echo is_array($cloud) && isset($cloud['score']) && null !== $cloud['score'] ? (int) $cloud['score'] . '/100' : esc_html__('No score yet', 'harriercache'); ?></strong>
            </p>
            <ul id="harriercache-notes">
                <?php if (is_array($cloud) && !empty($cloud['notes'])) : foreach ((array) $cloud['notes'] as $note) : ?>
                    <li><?php echo esc_html($note); ?></li>
                <?php endforeach; endif; ?>
            </ul>
            <p>
                <button type="button" class="button" id="harriercache-cloud"><?php echo 'yes' === get_option('hc_consent', '') ? esc_html__('Run cloud check', 'harriercache') : esc_html__('Enable and run', 'harriercache'); ?></button>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hc_snapshot'), 'hc_snapshot')); ?>"><?php esc_html_e('Download snapshot', 'harriercache'); ?></a>
                <span class="harriercache-result" id="harriercache-cloud-result"></span>
            </p>
            <p class="description"><?php printf(esc_html__('Last sync: %s', 'harriercache'), $last ? esc_html(wp_date('Y-m-d H:i', $last)) : esc_html__('not yet', 'harriercache')); ?></p>
        </div>

        <div class="harriercache-panel" id="diagnostics">
            <h2><?php esc_html_e('Environment check', 'harriercache'); ?></h2>
            <p class="description"><?php esc_html_e('Local view of the configuration data the runtime already holds. Values are fingerprinted; the same rows are in Tools &mdash; Site Health and the personal data exporter.', 'harriercache'); ?></p>
            <table class="widefat striped harriercache-table">
                <tbody>
                    <tr><td><?php esc_html_e('Configuration constants', 'harriercache'); ?></td><td id="harriercache-config"><?php echo $diag['config_readable'] ? esc_html__('Available', 'harriercache') : esc_html__('None', 'harriercache'); ?></td></tr>
                    <tr><td><?php esc_html_e('Environment variables', 'harriercache'); ?></td><td id="harriercache-env"><?php echo $diag['env_detected'] ? esc_html__('Available', 'harriercache') : esc_html__('None', 'harriercache'); ?></td></tr>
                    <tr><td><?php esc_html_e('Entries', 'harriercache'); ?></td><td id="harriercache-entries"><?php echo (int) $diag['entries']; ?></td></tr>
                </tbody>
            </table>
            <p><button type="button" class="button" id="harriercache-refresh"><?php esc_html_e('Refresh check', 'harriercache'); ?></button>
                <span class="harriercache-result" id="harriercache-refresh-result"></span></p>
        </div>

        <div class="harriercache-panel" id="tools">
            <h2><?php esc_html_e('Tools', 'harriercache'); ?></h2>
            <p>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hc_export'), 'hc_export')); ?>"><?php esc_html_e('Export settings', 'harriercache'); ?></a>
            </p>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="hc_import">
                <?php wp_nonce_field('hc_import'); ?>
                <p><input type="file" name="hc_file" accept="application/json"> <button type="submit" class="button"><?php esc_html_e('Import settings', 'harriercache'); ?></button></p>
            </form>

            <h3><?php esc_html_e('Database backups', 'harriercache'); ?></h3>
            <p>
                <button type="button" class="button" id="hc-backup"><?php esc_html_e('Create backup now', 'harriercache'); ?></button>
                <span class="perfseo-result" id="hc-backup-result"></span>
            </p>
            <?php $hc_backups = HC_Backup::list_files(); ?>
            <table class="widefat striped harriercache-table">
                <thead><tr><th><?php esc_html_e('File', 'harriercache'); ?></th><th><?php esc_html_e('Size', 'harriercache'); ?></th><th><?php esc_html_e('Date', 'harriercache'); ?></th><th></th></tr></thead>
                <tbody>
                    <?php if (!$hc_backups) : ?>
                        <tr><td colspan="4"><?php esc_html_e('No backups yet.', 'harriercache'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($hc_backups as $hc_b) : ?>
                        <tr>
                            <td><code><?php echo esc_html($hc_b['name']); ?></code></td>
                            <td><?php echo esc_html(hc_bytes($hc_b['size'])); ?></td>
                            <td><?php echo esc_html(wp_date('Y-m-d H:i', $hc_b['time'])); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hc_backup_download&file=' . rawurlencode($hc_b['name'])), 'hc_backup')); ?>"><?php esc_html_e('Download', 'harriercache'); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hc_backup_delete&file=' . rawurlencode($hc_b['name'])), 'hc_backup')); ?>"><?php esc_html_e('Delete', 'harriercache'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <table class="widefat striped harriercache-table">
                <tbody>
                    <?php foreach ($sys as $key => $value) : ?>
                        <tr><td><?php echo esc_html(ucfirst($key)); ?></td><td><?php echo esc_html($value); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save changes', 'harriercache'); ?></button>
        </p>
    </form>
</div>
