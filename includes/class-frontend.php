<?php
/**
 * Frontend rendering and asset enqueue
 */

namespace PopupsNekuda;

if (!defined('ABSPATH')) {
    exit;
}

class Frontend {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        add_action('wp_footer', [$this, 'render_popups']);
    }

    /**
     * Enqueue assets only if there are active popups
     */
    public function maybe_enqueue_assets(): void {
        $popups = $this->get_active_popups();

        if (empty($popups)) {
            return;
        }

        // Enqueue CSS
        $css_file = POPUP_DIR . 'assets/css/popup.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'popup-frontend',
                POPUP_URL . 'assets/css/popup.css',
                [],
                POPUP_VERSION
            );
        }

        // Enqueue JS
        $js_file = POPUP_DIR . 'assets/js/popup.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'popup-frontend',
                POPUP_URL . 'assets/js/popup.js',
                [],
                POPUP_VERSION,
                true
            );
        }
    }

    /**
     * Render all active popups
     */
    public function render_popups(): void {
        $popups = $this->get_active_popups();

        if (empty($popups)) {
            return;
        }

        foreach ($popups as $popup) {
            $this->render_single_popup($popup);
        }
    }

    /**
     * Get all popups that should display
     */
    private function get_active_popups(): array {
        $popups = get_posts([
            'post_type'      => 'popup',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ]);

        return array_filter($popups, [$this, 'is_popup_scheduled']);
    }

    /**
     * Check if popup is within schedule
     */
    private function is_popup_scheduled(\WP_Post $popup): bool {
        $today = date('Y-m-d');

        $start = Fields::get($popup->ID, '_popup_schedule_start', '');
        $end = Fields::get($popup->ID, '_popup_schedule_end', '');

        if (!empty($start) && $today < $start) {
            return false;
        }

        if (!empty($end) && $today > $end) {
            return false;
        }

        return true;
    }

    /**
     * Render a single popup
     */
    private function render_single_popup(\WP_Post $popup): void {
        $data = $this->get_popup_data($popup);

        // Skip if no content
        if (empty($data['slides_desktop']) && empty($data['slides_mobile'])) {
            return;
        }

        include POPUP_DIR . 'templates/popup.php';
    }

    /**
     * Get all popup data for template
     */
    private function get_popup_data(\WP_Post $popup): array {
        $slides_desktop = Fields::get($popup->ID, '_popup_slides_desktop', []);
        $slides_mobile = Fields::get($popup->ID, '_popup_slides_mobile', []);

        // Fallback: use desktop content for mobile if mobile is empty
        if (empty($slides_mobile)) {
            $slides_mobile = $slides_desktop;
        }

        $cookie_key = Fields::get($popup->ID, '_popup_cookie_key', '');
        if (empty($cookie_key)) {
            $cookie_key = $popup->post_name ?: 'popup_' . $popup->ID;
        }

        return [
            'id'              => $popup->ID,
            'trigger_type'    => Fields::get($popup->ID, '_popup_trigger_type', 'timeout'),
            'trigger_timeout' => Fields::get($popup->ID, '_popup_trigger_timeout', 3),
            'cookie_key'      => $cookie_key,
            'cookie_expiry'   => Fields::get($popup->ID, '_popup_cookie_expiry', 30),
            'max_width'       => Fields::get($popup->ID, '_popup_max_width', 600),
            'max_height'      => Fields::get($popup->ID, '_popup_max_height', ''),
            'slides_desktop'  => is_array($slides_desktop) ? $slides_desktop : [],
            'slides_mobile'   => is_array($slides_mobile) ? $slides_mobile : [],
        ];
    }
}
