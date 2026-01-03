<?php
/**
 * Plugin Name: Popups Nekuda
 * Plugin URI: https://nekuda.co.il
 * Description: A modern popup system for WordPress. Zero external dependencies.
 * Version: 3.0.0
 * Author: Nekuda
 * Author URI: https://nekuda.co.il
 * License: GPL2
 * Text Domain: popups-nekuda
 */

if (!defined('ABSPATH')) {
    exit;
}

define('POPUP_VERSION', '3.0.0');
define('POPUP_DIR', plugin_dir_path(__FILE__));
define('POPUP_URL', plugin_dir_url(__FILE__));
define('PLUGIN_NAMESPACE', 'PopupsNekuda');

// Autoloader for PopupsNekuda namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'PopupsNekuda\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $file = POPUP_DIR . 'includes/class-' . strtolower($relative_class) . '.php';

    if (file_exists($file)) {
        // phpcs:disable -- Autoloader requires dynamic file inclusion
        require_once $file;
    }
});

use PopupsNekuda\Admin;
use PopupsNekuda\Frontend;

// Initialize
if (is_admin()) {
    $GLOBALS['popup_admin'] = new Admin();
} else {
    $GLOBALS['popup_frontend'] = new Frontend();
}

/**
 * Register Popup Custom Post Type
 */
function popup_register_post_type(): void {
    $labels = [
        'name'               => __('Popups', PLUGIN_NAMESPACE),
        'singular_name'      => __('Popup', PLUGIN_NAMESPACE),
        'add_new'            => __('Add New', PLUGIN_NAMESPACE),
        'add_new_item'       => __('Add New Popup', PLUGIN_NAMESPACE),
        'edit_item'          => __('Edit Popup', PLUGIN_NAMESPACE),
        'new_item'           => __('New Popup', PLUGIN_NAMESPACE),
        'view_item'          => __('View Popup', PLUGIN_NAMESPACE),
        'search_items'       => __('Search Popups', PLUGIN_NAMESPACE),
        'not_found'          => __('No popups found', PLUGIN_NAMESPACE),
        'not_found_in_trash' => __('No popups found in Trash', PLUGIN_NAMESPACE),
        'menu_name'          => __('Popups', PLUGIN_NAMESPACE),
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

    register_post_type('popup', $args);
}
add_action('init', 'popup_register_post_type');