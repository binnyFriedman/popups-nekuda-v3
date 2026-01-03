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

// Include classes
require_once POPUP_DIR . 'includes/class-fields.php';
require_once POPUP_DIR . 'includes/class-admin.php';
require_once POPUP_DIR . 'includes/class-frontend.php';

// Initialize
if (is_admin()) {
    $GLOBALS['popup_admin'] = new Popup_Admin();
} else {
    $GLOBALS['popup_frontend'] = new Popup_Frontend();
}

/**
 * Register Popup Custom Post Type
 */
function popup_register_post_type(): void {
    $labels = [
        'name'               => __('Popups', 'popups-nekuda'),
        'singular_name'      => __('Popup', 'popups-nekuda'),
        'add_new'            => __('Add New', 'popups-nekuda'),
        'add_new_item'       => __('Add New Popup', 'popups-nekuda'),
        'edit_item'          => __('Edit Popup', 'popups-nekuda'),
        'new_item'           => __('New Popup', 'popups-nekuda'),
        'view_item'          => __('View Popup', 'popups-nekuda'),
        'search_items'       => __('Search Popups', 'popups-nekuda'),
        'not_found'          => __('No popups found', 'popups-nekuda'),
        'not_found_in_trash' => __('No popups found in Trash', 'popups-nekuda'),
        'menu_name'          => __('Popups', 'popups-nekuda'),
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