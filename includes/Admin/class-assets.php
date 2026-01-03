<?php
/**
 * Admin asset management (scripts & styles)
 */

namespace PopupsNekuda\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Assets {

    public static function register(): void {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(string $hook): void {
        global $post_type;

        if ($post_type !== 'nekuda_popup') {
            return;
        }

        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        self::enqueueMedia();
        self::enqueueSelect2();
        self::enqueuePluginAssets();
        self::addInlineScripts();
    }

    private static function enqueueMedia(): void {
        wp_enqueue_media();
    }

    private static function enqueueSelect2(): void {
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            [],
            '4.1.0'
        );

        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            '4.1.0',
            true
        );
    }

    private static function enqueuePluginAssets(): void {
        $css_file = POPUPS_NEKUDA_DIR . 'assets/css/admin.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'popups-nekuda-admin',
                POPUPS_NEKUDA_URL . 'assets/css/admin.css',
                ['select2'],
                POPUPS_NEKUDA_VERSION
            );
        }

        $js_file = POPUPS_NEKUDA_DIR . 'assets/js/admin.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'popups-nekuda-admin',
                POPUPS_NEKUDA_URL . 'assets/js/admin.js',
                ['jquery', 'wp-tinymce', 'select2'],
                POPUPS_NEKUDA_VERSION,
                true
            );

            wp_localize_script('popups-nekuda-admin', 'popupAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('popup_admin_nonce'),
            ]);
        }
    }

    private static function addInlineScripts(): void {
        $trigger_js_file = POPUPS_NEKUDA_DIR . 'assets/js/admin-trigger-toggle.js';
        if (file_exists($trigger_js_file)) {
            wp_enqueue_script(
                'popups-nekuda-trigger-toggle',
                POPUPS_NEKUDA_URL . 'assets/js/admin-trigger-toggle.js',
                ['jquery'],
                POPUPS_NEKUDA_VERSION,
                true
            );
        }

        $select2_init_file = POPUPS_NEKUDA_DIR . 'assets/js/admin-select2-init.js';
        if (file_exists($select2_init_file)) {
            wp_enqueue_script(
                'popups-nekuda-select2-init',
                POPUPS_NEKUDA_URL . 'assets/js/admin-select2-init.js',
                ['jquery', 'select2', 'popups-nekuda-admin'],
                POPUPS_NEKUDA_VERSION,
                true
            );
        }
    }
}

