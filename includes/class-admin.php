<?php
/**
 * Admin meta boxes and save handlers
 */

namespace PopupsNekuda;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_popup', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_popup_get_editor', [$this, 'ajax_get_editor']);
        add_action('wp_ajax_popup_search_content', [$this, 'ajax_search_content']);
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
            'popup_display_rules',
            __('Display Rules', 'popups-nekuda'),
            [$this, 'render_display_rules'],
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

        add_meta_box(
            'popup_slides_mobile',
            __('Mobile Slides', 'popups-nekuda'),
            [$this, 'render_slides_mobile'],
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

        Fields::radio($post->ID, '_popup_trigger_type', [
            'exit_intent' => __('Exit Intent', 'popups-nekuda'),
            'timeout'     => __('Timeout', 'popups-nekuda'),
        ], [
            'label'   => __('Trigger Type', 'popups-nekuda'),
            'default' => 'timeout',
        ]);

        Fields::text($post->ID, '_popup_trigger_timeout', [
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
        $cookie_key = Fields::get($post->ID, '_popup_cookie_key', '');
        if (empty($cookie_key) && $post->post_name) {
            $cookie_key = $post->post_name;
        }

        Fields::text($post->ID, '_popup_cookie_key', [
            'label'   => __('Cookie Key', 'popups-nekuda'),
            'default' => $cookie_key,
            'attrs'   => 'placeholder="auto-generated-from-slug"',
        ]);

        Fields::text($post->ID, '_popup_cookie_expiry', [
            'label'   => __('Cookie Expiry (days)', 'popups-nekuda'),
            'type'    => 'number',
            'default' => '30',
            'attrs'   => 'min="1" step="1"',
        ]);

        Fields::text($post->ID, '_popup_schedule_start', [
            'label' => __('Schedule Start Date (optional)', 'popups-nekuda'),
            'type'  => 'date',
        ]);

        Fields::text($post->ID, '_popup_schedule_end', [
            'label' => __('Schedule End Date (optional)', 'popups-nekuda'),
            'type'  => 'date',
        ]);
    }

    /**
     * Render Display Constraints meta box
     */
    public function render_display_constraints(\WP_Post $post): void {
        Fields::text($post->ID, '_popup_max_width', [
            'label'   => __('Max Width (px)', 'popups-nekuda'),
            'type'    => 'number',
            'default' => '600',
            'attrs'   => 'min="200" step="10"',
        ]);

        Fields::text($post->ID, '_popup_max_height', [
            'label'   => __('Max Height (px or empty for auto)', 'popups-nekuda'),
            'type'    => 'number',
            'attrs'   => 'min="100" step="10" placeholder="auto"',
        ]);
    }

    /**
     * Render Display Rules meta box
     */
    public function render_display_rules(\WP_Post $post): void {
        Fields::select2_multi($post->ID, '_popup_include', [
            'label'       => __('Include', 'popups-nekuda'),
            'description' => __('Leave empty to show on all pages', 'popups-nekuda'),
            'placeholder' => __('Search pages, posts, categories...', 'popups-nekuda'),
        ]);

        Fields::select2_multi($post->ID, '_popup_exclude', [
            'label'       => __('Exclude', 'popups-nekuda'),
            'description' => __('Hide popup on these pages', 'popups-nekuda'),
            'placeholder' => __('Search pages, posts, categories...', 'popups-nekuda'),
        ]);
    }

    /**
     * Render Desktop Slides meta box
     */
    public function render_slides_desktop(\WP_Post $post): void {
        $this->render_slides_repeater($post->ID, '_popup_slides_desktop', 'desktop');
    }

    /**
     * Render Mobile Slides meta box
     */
    public function render_slides_mobile(\WP_Post $post): void {
        echo '<p class="description">' . __('Leave empty to use desktop content on mobile.', 'popups-nekuda') . '</p>';
        $this->render_slides_repeater($post->ID, '_popup_slides_mobile', 'mobile');
    }

    /**
     * Render slides repeater UI
     */
    private function render_slides_repeater(int $post_id, string $key, string $type): void {
        $slides = Fields::get($post_id, $key, []);
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
        // Create a clean editor ID (TinyMCE has issues with IDs starting with underscore)
        $editor_id = 'popup_editor_' . str_replace('_popup_slides_', '', $key) . '_' . $index;
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

        // Create editor ID matching the JS format
        $editor_id = 'popup_editor_' . str_replace('_popup_slides_', '', $key) . '_' . $index;
        $field_name = $key . '[' . $index . ']';

        ob_start();
        echo '<div class="popup-slide-item" data-index="' . esc_attr($index) . '">';
        echo '<div class="popup-slide-header">';
        echo '<span class="popup-slide-title">' . sprintf(__('Slide %d', 'popups-nekuda'), $index + 1) . '</span>';
        echo '<button type="button" class="button popup-remove-slide">' . __('Remove', 'popups-nekuda') . '</button>';
        echo '</div>';
        echo '<div class="popup-slide-content">';

        wp_editor('', $editor_id, [
            'textarea_name' => $field_name,
            'textarea_rows' => 10,
            'media_buttons' => true,
            'teeny'         => false,
            'quicktags'     => true,
            'tinymce'       => [
                'wpautop' => true,
            ],
        ]);

        echo '</div>';
        echo '</div>';
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

    /**
     * AJAX handler for Select2 content search
     * 
     * Returns posts, pages, CPTs, and taxonomy terms matching the search query
     */
    public function ajax_search_content(): void {
        check_ajax_referer('popup_admin_nonce', 'nonce');

        $search = sanitize_text_field($_GET['q'] ?? '');
        $results = [];

        // Add special options (always show at top when no search or matching)
        if (empty($search) || stripos('homepage', $search) !== false || stripos('home', $search) !== false) {
            $results[] = [
                'id'   => 'special:home',
                'text' => __('Homepage', 'popups-nekuda'),
                'type' => __('Special', 'popups-nekuda'),
            ];
        }

        if (empty($search) || stripos('blog', $search) !== false) {
            $results[] = [
                'id'   => 'special:blog',
                'text' => __('Blog Page', 'popups-nekuda'),
                'type' => __('Special', 'popups-nekuda'),
            ];
        }

        // Add post types (when no search or searching for type names)
        $post_types = get_post_types(['public' => true], 'objects');
        foreach ($post_types as $post_type) {
            if ($post_type->name === 'attachment') {
                continue;
            }

            $type_name = $post_type->labels->name;
            if (empty($search) || stripos($type_name, $search) !== false || stripos('all ' . $type_name, $search) !== false) {
                $results[] = [
                    'id'   => 'post_type:' . $post_type->name,
                    'text' => sprintf(__('All %s', 'popups-nekuda'), $type_name),
                    'type' => __('Post Type', 'popups-nekuda'),
                ];
            }
        }

        // Search posts/pages
        if (!empty($search)) {
            $posts = get_posts([
                'post_type'      => array_keys($post_types),
                'post_status'    => 'publish',
                's'              => $search,
                'posts_per_page' => 20,
                'orderby'        => 'relevance',
            ]);

            foreach ($posts as $post) {
                if ($post->post_type === 'attachment') {
                    continue;
                }

                $type_obj = get_post_type_object($post->post_type);
                $type_label = $type_obj ? $type_obj->labels->singular_name : $post->post_type;

                $results[] = [
                    'id'   => 'post:' . $post->ID,
                    'text' => $post->post_title,
                    'type' => $type_label,
                ];
            }
        }

        // Search taxonomy terms
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy'   => $taxonomy->name,
                'search'     => $search,
                'number'     => 20,
                'hide_empty' => false,
            ]);

            if (is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $results[] = [
                    'id'   => 'term:' . $taxonomy->name . ':' . $term->term_id,
                    'text' => $term->name,
                    'type' => $taxonomy->labels->singular_name,
                ];
            }
        }

        wp_send_json(['results' => $results]);
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
        Fields::save($post_id, '_popup_trigger_type', $trigger_type);

        $timeout = absint($_POST['_popup_trigger_timeout'] ?? 3);
        Fields::save($post_id, '_popup_trigger_timeout', $timeout);

        // Cookie & scheduling
        $cookie_key = sanitize_key($_POST['_popup_cookie_key'] ?? '');
        Fields::save($post_id, '_popup_cookie_key', $cookie_key);

        $cookie_expiry = absint($_POST['_popup_cookie_expiry'] ?? 30);
        Fields::save($post_id, '_popup_cookie_expiry', $cookie_expiry);

        $schedule_start = sanitize_text_field($_POST['_popup_schedule_start'] ?? '');
        Fields::save($post_id, '_popup_schedule_start', $schedule_start);

        $schedule_end = sanitize_text_field($_POST['_popup_schedule_end'] ?? '');
        Fields::save($post_id, '_popup_schedule_end', $schedule_end);

        // Display constraints
        $max_width = absint($_POST['_popup_max_width'] ?? 600);
        Fields::save($post_id, '_popup_max_width', $max_width ?: 600);

        $max_height = $_POST['_popup_max_height'] ?? '';
        Fields::save($post_id, '_popup_max_height', $max_height ? absint($max_height) : '');

        // Display rules (include/exclude)
        $include = $this->sanitize_display_rules($_POST['_popup_include'] ?? []);
        Fields::save($post_id, '_popup_include', $include);

        $exclude = $this->sanitize_display_rules($_POST['_popup_exclude'] ?? []);
        Fields::save($post_id, '_popup_exclude', $exclude);

        // Desktop slides
        $raw_desktop = isset($_POST['_popup_slides_desktop']) ? $_POST['_popup_slides_desktop'] : [];
        
        // Debug: Log what we're receiving
        error_log('Popup Save - Raw Desktop: ' . print_r($raw_desktop, true));
        
        $slides_desktop = $this->sanitize_slides($raw_desktop);
        
        error_log('Popup Save - Sanitized Desktop: ' . print_r($slides_desktop, true));
        
        Fields::save($post_id, '_popup_slides_desktop', $slides_desktop);

        // Mobile slides
        $raw_mobile = isset($_POST['_popup_slides_mobile']) ? $_POST['_popup_slides_mobile'] : [];
        $slides_mobile = $this->sanitize_slides($raw_mobile);
        Fields::save($post_id, '_popup_slides_mobile', $slides_mobile);
    }

    /**
     * Sanitize slides array
     */
    private function sanitize_slides($slides): array {
        if (!is_array($slides)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($slides as $content) {
            if (!is_string($content)) {
                continue;
            }
            $clean = wp_kses_post($content);
            // Keep slide if it has any content (including images)
            if (!empty(trim($clean))) {
                $sanitized[] = $clean;
            }
        }
        
        return array_values($sanitized);
    }

    /**
     * Sanitize display rules array
     * 
     * Valid formats:
     * - special:home
     * - special:blog
     * - post:123
     * - post_type:car
     * - term:category:5
     * 
     * @param mixed $rules Raw input from form
     * @return array Sanitized array of rule strings
     */
    private function sanitize_display_rules($rules): array {
        if (!is_array($rules)) {
            return [];
        }

        $sanitized = [];
        $valid_prefixes = ['special:', 'post:', 'post_type:', 'term:'];

        foreach ($rules as $rule) {
            if (!is_string($rule) || empty($rule)) {
                continue;
            }

            $rule = sanitize_text_field($rule);

            // Validate rule format
            $is_valid = false;
            foreach ($valid_prefixes as $prefix) {
                if (str_starts_with($rule, $prefix)) {
                    $is_valid = true;
                    break;
                }
            }

            if ($is_valid) {
                $sanitized[] = $rule;
            }
        }

        return array_unique($sanitized);
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

        // Enqueue media library for dynamically added editors
        wp_enqueue_media();

        // Enqueue Select2 from CDN
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

        $css_file = POPUP_DIR . 'assets/css/admin.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'popup-admin',
                POPUP_URL . 'assets/css/admin.css',
                ['select2'],
                POPUP_VERSION
            );
        }

        $js_file = POPUP_DIR . 'assets/js/admin.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'popup-admin',
                POPUP_URL . 'assets/js/admin.js',
                ['jquery', 'wp-tinymce', 'select2'],
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

        // Inline script for Select2 initialization
        wp_add_inline_script('select2', $this->get_select2_init_script(), 'after');
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

    /**
     * Get inline script for Select2 initialization
     */
    private function get_select2_init_script(): string {
        return <<<'JS'
(function($) {
    $(document).ready(function() {
        $('.popup-select2').each(function() {
            var $select = $(this);
            
            $select.select2({
                ajax: {
                    url: popupAdmin.ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'popup_search_content',
                            nonce: popupAdmin.nonce,
                            q: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return data;
                    },
                    cache: true
                },
                placeholder: $select.data('placeholder') || 'Search...',
                allowClear: true,
                minimumInputLength: 0,
                templateResult: function(item) {
                    if (item.loading) {
                        return item.text;
                    }
                    
                    var $container = $('<div class="popup-select2-result"></div>');
                    $container.append('<span class="popup-select2-result__text">' + item.text + '</span>');
                    
                    if (item.type) {
                        $container.append('<span class="popup-select2-result__type">' + item.type + '</span>');
                    }
                    
                    return $container;
                },
                templateSelection: function(item) {
                    return item.text;
                }
            });
        });
    });
})(jQuery);
JS;
    }
}
