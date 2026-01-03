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
        add_action('wp_ajax_popup_get_editor', [$this, 'ajax_get_editor']);
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

        add_meta_box(
            'popup_cookie_scheduling',
            __('Cookie & Scheduling', 'popups-nekuda'),
            [$this, 'render_cookie_scheduling'],
            'popup',
            'normal',
            'high'
        );

        add_meta_box(
            'popup_display_constraints',
            __('Display Constraints', 'popups-nekuda'),
            [$this, 'render_display_constraints'],
            'popup',
            'side',
            'default'
        );

        add_meta_box(
            'popup_slides_desktop',
            __('Desktop Slides', 'popups-nekuda'),
            [$this, 'render_slides_desktop'],
            'popup',
            'normal',
            'default'
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
     * Render Cookie & Scheduling meta box
     */
    public function render_cookie_scheduling(\WP_Post $post): void {
        $cookie_key = Popup_Fields::get($post->ID, '_popup_cookie_key', '');
        if (empty($cookie_key) && $post->post_name) {
            $cookie_key = $post->post_name;
        }

        Popup_Fields::text($post->ID, '_popup_cookie_key', [
            'label'   => __('Cookie Key', 'popups-nekuda'),
            'default' => $cookie_key,
            'attrs'   => 'placeholder="auto-generated-from-slug"',
        ]);

        Popup_Fields::text($post->ID, '_popup_cookie_expiry', [
            'label'   => __('Cookie Expiry (days)', 'popups-nekuda'),
            'type'    => 'number',
            'default' => '30',
            'attrs'   => 'min="1" step="1"',
        ]);

        Popup_Fields::text($post->ID, '_popup_schedule_start', [
            'label' => __('Schedule Start Date (optional)', 'popups-nekuda'),
            'type'  => 'date',
        ]);

        Popup_Fields::text($post->ID, '_popup_schedule_end', [
            'label' => __('Schedule End Date (optional)', 'popups-nekuda'),
            'type'  => 'date',
        ]);
    }

    /**
     * Render Display Constraints meta box
     */
    public function render_display_constraints(\WP_Post $post): void {
        Popup_Fields::text($post->ID, '_popup_max_width', [
            'label'   => __('Max Width (px)', 'popups-nekuda'),
            'type'    => 'number',
            'default' => '600',
            'attrs'   => 'min="200" step="10"',
        ]);

        Popup_Fields::text($post->ID, '_popup_max_height', [
            'label'   => __('Max Height (px or empty for auto)', 'popups-nekuda'),
            'type'    => 'number',
            'attrs'   => 'min="100" step="10" placeholder="auto"',
        ]);
    }

    /**
     * Render Desktop Slides meta box
     */
    public function render_slides_desktop(\WP_Post $post): void {
        $this->render_slides_repeater($post->ID, '_popup_slides_desktop', 'desktop');
    }

    /**
     * Render slides repeater UI
     */
    private function render_slides_repeater(int $post_id, string $key, string $type): void {
        $slides = Popup_Fields::get($post_id, $key, []);
        if (!is_array($slides)) {
            $slides = [];
        }

        echo '<div class="popup-slides-repeater" data-type="' . esc_attr($type) . '">';
        echo '<div class="popup-slides-list">';

        if (empty($slides)) {
            $this->render_single_slide($key, 0, '');
        } else {
            foreach ($slides as $index => $content) {
                $this->render_single_slide($key, $index, $content);
            }
        }

        echo '</div>';
        echo '<button type="button" class="button popup-add-slide">' . __('Add Slide', 'popups-nekuda') . '</button>';
        echo '</div>';
    }

    /**
     * Render a single slide with wp_editor
     */
    private function render_single_slide(string $key, int $index, string $content): void {
        $editor_id = $key . '_' . $index;
        $field_name = $key . '[' . $index . ']';

        echo '<div class="popup-slide-item" data-index="' . esc_attr($index) . '">';
        echo '<div class="popup-slide-header">';
        echo '<span class="popup-slide-title">' . sprintf(__('Slide %d', 'popups-nekuda'), $index + 1) . '</span>';
        echo '<button type="button" class="button popup-remove-slide">' . __('Remove', 'popups-nekuda') . '</button>';
        echo '</div>';
        echo '<div class="popup-slide-content">';

        wp_editor($content, $editor_id, [
            'textarea_name' => $field_name,
            'textarea_rows' => 10,
            'media_buttons' => true,
            'teeny'         => false,
            'quicktags'     => true,
        ]);

        echo '</div>';
        echo '</div>';
    }

    /**
     * AJAX handler to get a new wp_editor instance
     */
    public function ajax_get_editor(): void {
        check_ajax_referer('popup_admin_nonce', 'nonce');

        $key = sanitize_text_field($_POST['key'] ?? '');
        $index = absint($_POST['index'] ?? 0);

        if (empty($key)) {
            wp_send_json_error('Invalid key');
        }

        ob_start();
        $this->render_single_slide($key, $index, '');
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
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

        // Cookie & scheduling
        $cookie_key = sanitize_key($_POST['_popup_cookie_key'] ?? '');
        Popup_Fields::save($post_id, '_popup_cookie_key', $cookie_key);

        $cookie_expiry = absint($_POST['_popup_cookie_expiry'] ?? 30);
        Popup_Fields::save($post_id, '_popup_cookie_expiry', $cookie_expiry);

        $schedule_start = sanitize_text_field($_POST['_popup_schedule_start'] ?? '');
        Popup_Fields::save($post_id, '_popup_schedule_start', $schedule_start);

        $schedule_end = sanitize_text_field($_POST['_popup_schedule_end'] ?? '');
        Popup_Fields::save($post_id, '_popup_schedule_end', $schedule_end);

        // Display constraints
        $max_width = absint($_POST['_popup_max_width'] ?? 600);
        Popup_Fields::save($post_id, '_popup_max_width', $max_width ?: 600);

        $max_height = $_POST['_popup_max_height'] ?? '';
        Popup_Fields::save($post_id, '_popup_max_height', $max_height ? absint($max_height) : '');

        // Desktop slides
        $slides_desktop = $_POST['_popup_slides_desktop'] ?? [];
        if (is_array($slides_desktop)) {
            $slides_desktop = array_values(array_filter(array_map(function($content) {
                return wp_kses_post($content);
            }, $slides_desktop), function($content) {
                return !empty(trim(strip_tags($content)));
            }));
        } else {
            $slides_desktop = [];
        }
        Popup_Fields::save($post_id, '_popup_slides_desktop', $slides_desktop);
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

        $js_file = POPUP_DIR . 'assets/js/admin.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'popup-admin',
                POPUP_URL . 'assets/js/admin.js',
                ['jquery'],
                POPUP_VERSION,
                true
            );

            wp_localize_script('popup-admin', 'popupAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('popup_admin_nonce'),
            ]);
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
