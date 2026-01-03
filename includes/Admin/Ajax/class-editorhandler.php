<?php
/**
 * AJAX handler for wp_editor instances
 */

namespace PopupsNekuda\Admin\Ajax;

use PopupsNekuda\Admin\MetaBoxes\ContentMetaBox;

if (!defined('ABSPATH')) {
    exit;
}

class EditorHandler {

    public const ACTION = 'popup_get_editor';

    public static function register(): void {
        add_action('wp_ajax_' . self::ACTION, [self::class, 'handle']);
    }

    public static function handle(): void {
        check_ajax_referer('popup_admin_nonce', 'nonce');

        $key = sanitize_text_field($_POST['key'] ?? '');
        $index = absint($_POST['index'] ?? 0);

        if (empty($key)) {
            wp_send_json_error('Invalid key');
        }

        ob_start();
        ContentMetaBox::renderSingleSlide($key, $index, '');
        $html = ob_get_clean();

        // Get the TinyMCE and Quicktags settings for this editor
        ob_start();
        \_WP_Editors::editor_js();
        $scripts = ob_get_clean();

        wp_send_json_success([
            'html'    => $html,
            'scripts' => $scripts,
        ]);
    }
}