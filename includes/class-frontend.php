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
        $css_file = POPUPS_NEKUDA_DIR . 'assets/css/popup.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'popups-nekuda-frontend',
                POPUPS_NEKUDA_URL . 'assets/css/popup.css',
                [],
                POPUPS_NEKUDA_VERSION
            );
        }

        // Enqueue JS
        $js_file = POPUPS_NEKUDA_DIR . 'assets/js/popup.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'popups-nekuda-frontend',
                POPUPS_NEKUDA_URL . 'assets/js/popup.js',
                [],
                POPUPS_NEKUDA_VERSION,
                true
            );

            wp_localize_script('popups-nekuda-frontend', 'popupSettings', [
                'devMode' => defined('WP_DEBUG') && WP_DEBUG,
            ]);
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
            'post_type'      => 'nekuda_popup',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ]);

        // Build context once for all popups
        $context = DisplayRules::build_context();

        return array_filter($popups, function (\WP_Post $popup) use ($context) {
            return $this->is_popup_scheduled($popup)
                && $this->passes_display_rules($popup, $context);
        });
    }

    /**
     * Check if popup passes display rules for current page
     * 
     * @param \WP_Post $popup   The popup post
     * @param array    $context Current page context from DisplayRules::build_context()
     * @return bool
     */
    private function passes_display_rules(\WP_Post $popup, array $context): bool {
        $include = Fields::get($popup->ID, '_popup_include', []);
        $exclude = Fields::get($popup->ID, '_popup_exclude', []);

        if (!is_array($include)) {
            $include = [];
        }
        if (!is_array($exclude)) {
            $exclude = [];
        }

        return DisplayRules::passes($include, $exclude, $context);
    }

    /**
     * Check if popup is within schedule
     */
    private function is_popup_scheduled(\WP_Post $popup): bool {
        $today = wp_date('Y-m-d');

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

        include POPUPS_NEKUDA_DIR . 'templates/popup.php';
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
