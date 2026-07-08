<?php
/**
 * Plugin Name: Popups Nekuda
 * Plugin URI: https://nekuda.co.il
 * Description: A modern popup system for WordPress. Zero external dependencies.
 * Version: 4.0.0
 * Author: Nekuda
 * Author URI: https://nekuda.co.il
 * License: GPL2
 * Text Domain: popups-nekuda
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://nekuda.co.il/plugins/popups-nekuda
 */

if (!defined('ABSPATH')) {
    exit;
}

define('POPUPS_NEKUDA_VERSION', '4.0.0');
define('POPUPS_NEKUDA_FILE', __FILE__);
define('POPUPS_NEKUDA_DIR', plugin_dir_path(__FILE__));
define('POPUPS_NEKUDA_URL', plugin_dir_url(__FILE__));
define('POPUPS_NEKUDA_BASENAME', plugin_basename(__FILE__));
define('POPUPS_NEKUDA_TEXT_DOMAIN', 'popups-nekuda');

function popups_nekuda_is_plugin_class(string $class): bool {
    return strpos($class, 'PopupsNekuda\\') === 0;
}

function popups_nekuda_get_class_file_path(string $class): string {
    $relative_class = substr($class, strlen('PopupsNekuda\\'));
    $path_parts = explode('\\', $relative_class);
    $class_name = array_pop($path_parts);
    $subdir = !empty($path_parts) ? implode('/', $path_parts) . '/' : '';
    
    return POPUPS_NEKUDA_DIR . 'includes/' . $subdir . 'class-' . strtolower($class_name) . '.php';
}

spl_autoload_register(function (string $class): void {
    if (!popups_nekuda_is_plugin_class($class)) {
        return;
    }

    $file = popups_nekuda_get_class_file_path($class);

    if (file_exists($file)) {
        require_once $file; // phpcs:ignore
    }
});

use PopupsNekuda\Admin;
use PopupsNekuda\Frontend;

/**
 * Load plugin text domain for translations
 */
function popups_nekuda_load_textdomain(): void {
    load_plugin_textdomain(
        POPUPS_NEKUDA_TEXT_DOMAIN,
        false,
        dirname(POPUPS_NEKUDA_BASENAME) . '/languages'
    );
}
add_action('plugins_loaded', 'popups_nekuda_load_textdomain');

/**
 * Initialize plugin components
 */
function popups_nekuda_init(): void {
    if (is_admin()) {
        $GLOBALS['popups_nekuda_admin'] = new Admin();
    } else {
        $GLOBALS['popups_nekuda_frontend'] = new Frontend();
    }
}
add_action('plugins_loaded', 'popups_nekuda_init');

/**
 * Initialize GitHub-based plugin updates
 *
 * Checks for updates from GitHub releases automatically.
 * Create releases on GitHub with version tags (e.g., v3.0.1) to trigger updates.
 *
 * @see https://github.com/YahnisElsts/plugin-update-checker
 */
function popups_nekuda_init_updates(): void {
    $update_checker_file = POPUPS_NEKUDA_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

    if (!file_exists($update_checker_file)) {
        return;
    }

    require_once $update_checker_file;

    $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/binnyFriedman/popups-nekuda-v3',
        POPUPS_NEKUDA_FILE,
        'popups-nekuda-v3'
    );

    // Download the ZIP from GitHub releases
    /** @var \YahnisElsts\PluginUpdateChecker\v5p4\Vcs\GitHubApi $api */
    $api = $update_checker->getVcsApi();
    $api->enableReleaseAssets();
}
add_action('admin_init', 'popups_nekuda_init_updates');

/**
 * Plugin activation hook
 */
function popups_nekuda_activate(): void {
    // Register post type immediately for rewrite rules
    popups_nekuda_register_post_type();

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(POPUPS_NEKUDA_FILE, 'popups_nekuda_activate');

/**
 * Plugin deactivation hook
 */
function popups_nekuda_deactivate(): void {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(POPUPS_NEKUDA_FILE, 'popups_nekuda_deactivate');

/**
 * Register Popup Custom Post Type
 * Using prefixed post type name to avoid conflicts with other plugins
 */
function popups_nekuda_register_post_type(): void {
    $labels = [
        'name'               => __('Popups', POPUPS_NEKUDA_TEXT_DOMAIN),
        'singular_name'      => __('Popup', POPUPS_NEKUDA_TEXT_DOMAIN),
        'add_new'            => __('Add New', POPUPS_NEKUDA_TEXT_DOMAIN),
        'add_new_item'       => __('Add New Popup', POPUPS_NEKUDA_TEXT_DOMAIN),
        'edit_item'          => __('Edit Popup', POPUPS_NEKUDA_TEXT_DOMAIN),
        'new_item'           => __('New Popup', POPUPS_NEKUDA_TEXT_DOMAIN),
        'view_item'          => __('View Popup', POPUPS_NEKUDA_TEXT_DOMAIN),
        'search_items'       => __('Search Popups', POPUPS_NEKUDA_TEXT_DOMAIN),
        'not_found'          => __('No popups found', POPUPS_NEKUDA_TEXT_DOMAIN),
        'not_found_in_trash' => __('No popups found in Trash', POPUPS_NEKUDA_TEXT_DOMAIN),
        'menu_name'          => __('Popups', POPUPS_NEKUDA_TEXT_DOMAIN),
    ];

    $args = [
        'labels'              => $labels,
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => false,
        'rewrite'             => false,
        'capability_type'     => 'post',
        'has_archive'         => false,
        'hierarchical'        => false,
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-welcome-widgets-menus',
        'supports'            => ['title'],
        'show_in_rest'        => false,
    ];

    register_post_type('nekuda_popup', $args);
}
add_action('init', 'popups_nekuda_register_post_type');

/**
 * Handle migration from old 'popup' post type to 'nekuda_popup'
 * This runs once when the plugin is activated/updated
 */
function popups_nekuda_maybe_migrate_post_type(): void {
    $migrated = get_option('popups_nekuda_post_type_migrated', false);

    if ($migrated) {
        return;
    }

    global $wpdb;

    // Check if there are any posts with old post type
    $old_posts = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
            'popup'
        )
    );

    if ($old_posts > 0) {
        // Migrate old posts to new post type
        $wpdb->update(
            $wpdb->posts,
            ['post_type' => 'nekuda_popup'],
            ['post_type' => 'popup'],
            ['%s'],
            ['%s']
        );
    }

    update_option('popups_nekuda_post_type_migrated', true);
}
add_action('admin_init', 'popups_nekuda_maybe_migrate_post_type');