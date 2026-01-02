<?php
/**
 * Admin meta boxes and save handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Popup_Admin {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_popup', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Register all meta boxes
     */
    public function register_meta_boxes(): void {
        add_meta_box(
            'popup_trigger_settings',
            __('Trigger Settings', 'popups-nekuda'),
            [$this, 'render_trigger_settings'],
            'popup',
            'normal',
            'high'
        );
    }

    /**
     * Render Trigger Settings meta box
     */
    public function render_trigger_settings(\WP_Post $post): void {
        wp_nonce_field('popup_save_meta', 'popup_meta_nonce');

        Popup_Fields::radio($post->ID, '_popup_trigger_type', [
            'exit_intent' => __('Exit Intent', 'popups-nekuda'),
            'timeout'     => __('Timeout', 'popups-nekuda'),
        ], [
            'label'   => __('Trigger Type', 'popups-nekuda'),
            'default' => 'timeout',
        ]);

        Popup_Fields::text($post->ID, '_popup_trigger_timeout', [
            'label'   => __('Timeout (seconds)', 'popups-nekuda'),
            'type'    => 'number',
            'default' => '3',
            'class'   => 'popup-field--timeout',
            'attrs'   => 'min="1" step="1"',
        ]);
    }

    /**
     * Save meta fields
     */
    public function save_meta(int $post_id): void {
        if (!isset($_POST['popup_meta_nonce']) || !wp_verify_nonce($_POST['popup_meta_nonce'], 'popup_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Trigger settings
        $trigger_type = sanitize_text_field($_POST['_popup_trigger_type'] ?? 'timeout');
        if (!in_array($trigger_type, ['exit_intent', 'timeout'])) {
            $trigger_type = 'timeout';
        }
        Popup_Fields::save($post_id, '_popup_trigger_type', $trigger_type);

        $timeout = absint($_POST['_popup_trigger_timeout'] ?? 3);
        Popup_Fields::save($post_id, '_popup_trigger_timeout', $timeout);
    }

    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_assets(string $hook): void {
        global $post_type;

        if ($post_type !== 'popup') {
            return;
        }

        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        $css_file = POPUP_DIR . 'assets/css/admin.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'popup-admin',
                POPUP_URL . 'assets/css/admin.css',
                [],
                POPUP_VERSION
            );
        }

        // Inline script for trigger type toggle
        wp_add_inline_script('jquery', $this->get_trigger_toggle_script());
    }

    /**
     * Get inline script for trigger type visibility toggle
     */
    private function get_trigger_toggle_script(): string {
        return <<<'JS'
(function($) {
    function updateTriggerVisibility() {
        var selected = $('input[name="_popup_trigger_type"]:checked').val();
        $('.popup-field--timeout').toggle(selected === 'timeout');
    }
    $(document).ready(function() {
        updateTriggerVisibility();
        $('input[name="_popup_trigger_type"]').on('change', updateTriggerVisibility);
    });
})(jQuery);
JS;
    }
}
