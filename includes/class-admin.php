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
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_action('save_post_nekuda_popup', [$this, 'saveMeta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_ajax_popup_get_editor', [$this, 'ajaxGetEditor']);
        add_action('wp_ajax_popup_search_content', [$this, 'ajaxSearchContent']);
    }

    /**
     * Register all meta boxes
     */
    public function registerMetaBoxes(): void {
        add_meta_box(
            'popup_trigger_settings',
            __('Trigger Settings', POPUPS_NEKUDA_TEXT_DOMAIN),
            [$this, 'renderTriggerSettings'],
            'nekuda_popup',
            'normal',
            'high'
        );

        add_meta_box(
            'popup_cookie_scheduling',
            __('Cookie & Scheduling', POPUPS_NEKUDA_TEXT_DOMAIN),
            [$this, 'renderCookieScheduling'],
            'nekuda_popup',
            'normal',
            'high'
        );

        add_meta_box(
            'popup_display_constraints',
            __('Display Constraints', POPUPS_NEKUDA_TEXT_DOMAIN),
            [$this, 'renderDisplayConstraints'],
            'nekuda_popup',
            'side',
            'default'
        );

        add_meta_box(
            'popup_display_rules',
            __('Display Rules', POPUPS_NEKUDA_TEXT_DOMAIN),
            [$this, 'renderDisplayRules'],
            'nekuda_popup',
            'side',
            'default'
        );

        add_meta_box(
            'popup_content',
            __('Popup Content', POPUPS_NEKUDA_TEXT_DOMAIN),
            [$this, 'renderPopupContent'],
            'nekuda_popup',
            'normal',
            'default'
        );
    }

    /**
     * Render Trigger Settings meta box
     */
    public function renderTriggerSettings(\WP_Post $post): void {
        wp_nonce_field('popup_save_meta', 'popup_meta_nonce');

        Fields::radio($post->ID, '_popup_trigger_type', [
            'exit_intent' => __('Exit Intent', POPUPS_NEKUDA_TEXT_DOMAIN),
            'timeout'     => __('Timeout', POPUPS_NEKUDA_TEXT_DOMAIN),
        ], [
            'label'   => __('Trigger Type', POPUPS_NEKUDA_TEXT_DOMAIN),
            'default' => 'timeout',
        ]);

        Fields::text($post->ID, '_popup_trigger_timeout', [
            'label'   => __('Timeout (seconds)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'default' => '3',
            'class'   => 'popup-field--timeout',
            'attrs'   => 'min="1" step="1"',
        ]);
    }

    /**
     * Render Cookie & Scheduling meta box
     */
    public function renderCookieScheduling(\WP_Post $post): void {
        $cookie_key = Fields::get($post->ID, '_popup_cookie_key', '');
        if (empty($cookie_key) && $post->post_name) {
            $cookie_key = $post->post_name;
        }

        Fields::text($post->ID, '_popup_cookie_key', [
            'label'   => __('Cookie Key', POPUPS_NEKUDA_TEXT_DOMAIN),
            'default' => $cookie_key,
            'attrs'   => 'placeholder="auto-generated-from-slug"',
        ]);

        Fields::text($post->ID, '_popup_cookie_expiry', [
            'label'   => __('Cookie Expiry (days)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'default' => '30',
            'attrs'   => 'min="1" step="1"',
        ]);

        Fields::text($post->ID, '_popup_schedule_start', [
            'label' => __('Schedule Start Date (optional)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'  => 'date',
        ]);

        Fields::text($post->ID, '_popup_schedule_end', [
            'label' => __('Schedule End Date (optional)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'  => 'date',
        ]);
    }

    /**
     * Render Display Constraints meta box
     */
    public function renderDisplayConstraints(\WP_Post $post): void {
        Fields::text($post->ID, '_popup_max_width', [
            'label'   => __('Max Width (px)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'default' => '600',
            'attrs'   => 'min="200" step="10"',
        ]);

        Fields::text($post->ID, '_popup_max_height', [
            'label'   => __('Max Height (px or empty for auto)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'attrs'   => 'min="100" step="10" placeholder="auto"',
        ]);
    }

    /**
     * Render Display Rules meta box
     */
    public function renderDisplayRules(\WP_Post $post): void {
        Fields::select2Multi($post->ID, '_popup_include', [
            'label'       => __('Include', POPUPS_NEKUDA_TEXT_DOMAIN),
            'description' => __('Leave empty to show on all pages', POPUPS_NEKUDA_TEXT_DOMAIN),
            'placeholder' => __('Search pages, posts, categories...', POPUPS_NEKUDA_TEXT_DOMAIN),
        ]);

        Fields::select2Multi($post->ID, '_popup_exclude', [
            'label'       => __('Exclude', POPUPS_NEKUDA_TEXT_DOMAIN),
            'description' => __('Hide popup on these pages', POPUPS_NEKUDA_TEXT_DOMAIN),
            'placeholder' => __('Search pages, posts, categories...', POPUPS_NEKUDA_TEXT_DOMAIN),
        ]);
    }

    /**
     * Render unified Popup Content meta box with tabs
     */
    public function renderPopupContent(\WP_Post $post): void {
        $mobile_slides = Fields::get($post->ID, '_popup_slides_mobile', []);
        $has_mobile_content = !empty($mobile_slides) && is_array($mobile_slides);
        
        ?>
<div class="popup-content-tabs">
    <div class="popup-tabs-nav">
        <button type="button" class="popup-tab-btn is-active" data-tab="desktop">
            <span class="dashicons dashicons-desktop"></span>
            <?php _e('Desktop', POPUPS_NEKUDA_TEXT_DOMAIN); ?>
        </button>
        <button type="button" class="popup-tab-btn" data-tab="mobile">
            <span class="dashicons dashicons-smartphone"></span>
            <?php _e('Mobile', POPUPS_NEKUDA_TEXT_DOMAIN); ?>
            <?php if (!$has_mobile_content): ?>
            <span class="popup-tab-sync"
                title="<?php esc_attr_e('Using desktop content', POPUPS_NEKUDA_TEXT_DOMAIN); ?>">↔</span>
            <?php endif; ?>
        </button>
    </div>

    <div class="popup-tabs-content">
        <div class="popup-tab-panel is-active" data-panel="desktop">
            <?php $this->renderSlidesRepeater($post->ID, '_popup_slides_desktop', 'desktop'); ?>
        </div>

        <div class="popup-tab-panel" data-panel="mobile">
            <div class="popup-mobile-notice <?php echo $has_mobile_content ? 'is-hidden' : ''; ?>">
                <span class="dashicons dashicons-info"></span>
                <p><?php _e('Currently using desktop slides on mobile. Add slides below to customize the mobile experience.', POPUPS_NEKUDA_TEXT_DOMAIN); ?>
                </p>
            </div>
            <?php $this->renderSlidesRepeater($post->ID, '_popup_slides_mobile', 'mobile'); ?>
        </div>
    </div>
</div>
<?php
    }

    /**
     * Render slides repeater UI
     */
    private function renderSlidesRepeater(int $post_id, string $key, string $type): void {
        $slides = Fields::get($post_id, $key, []);
        if (!is_array($slides)) {
            $slides = [];
        }

        echo '<div class="popup-slides-repeater" data-type="' . esc_attr($type) . '">';
        echo '<div class="popup-slides-list">';

        if (empty($slides)) {
            $this->renderSingleSlide($key, 0, '');
        } else {
            foreach ($slides as $index => $content) {
                $this->renderSingleSlide($key, $index, $content);
            }
        }

        echo '</div>';
        echo '<button type="button" class="button popup-add-slide">' . __('Add Slide', POPUPS_NEKUDA_TEXT_DOMAIN) . '</button>';
        echo '</div>';
    }

    /**
     * Render a single slide with wp_editor
     */
    private function renderSingleSlide(string $key, int $index, string $content): void {
        // Create a clean editor ID (TinyMCE has issues with IDs starting with underscore)
        $editor_id = 'popup_editor_' . str_replace('_popup_slides_', '', $key) . '_' . $index;
        $field_name = $key . '[' . $index . ']';

        echo '<div class="popup-slide-item" data-index="' . esc_attr($index) . '">';
        echo '<div class="popup-slide-header">';
        echo '<span class="popup-slide-title">' . sprintf(__('Slide %d', POPUPS_NEKUDA_TEXT_DOMAIN), $index + 1) . '</span>';
        echo '<button type="button" class="button popup-remove-slide">' . __('Remove', POPUPS_NEKUDA_TEXT_DOMAIN) . '</button>';
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
    public function ajaxGetEditor(): void {
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
        echo '<span class="popup-slide-title">' . sprintf(__('Slide %d', POPUPS_NEKUDA_TEXT_DOMAIN), $index + 1) . '</span>';
        echo '<button type="button" class="button popup-remove-slide">' . __('Remove', POPUPS_NEKUDA_TEXT_DOMAIN) . '</button>';
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
     * Returns grouped results (ACF-style) for better organization:
     * - Special (Homepage, Blog)
     * - Post Types (All Pages, All Posts, etc.)
     * - Specific posts grouped by type
     * - Taxonomy terms grouped by taxonomy
     */
    public function ajaxSearchContent(): void {
        check_ajax_referer('popup_admin_nonce', 'nonce');

        $search = sanitize_text_field($_GET['q'] ?? '');
        $groups = [];

        $this->addSpecialPagesGroup($groups, $search);
        $this->addPostTypesGroup($groups, $search);
        $this->addSpecificPostsGroups($groups, $search);
        $this->addTaxonomyTermsGroups($groups, $search);

        wp_send_json(['results' => $groups]);
    }

    /**
     * Add special pages group to search results
     */
    private function addSpecialPagesGroup(array &$groups, string $search): void {
        $children = [];

        if ($this->matchesSearch($search, ['homepage', 'home'])) {
            $children[] = [
                'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_SPECIAL, DisplayRules::SPECIAL_HOME),
                'text' => __('Homepage', POPUPS_NEKUDA_TEXT_DOMAIN),
            ];
        }

        if ($this->matchesSearch($search, ['blog'])) {
            $children[] = [
                'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_SPECIAL, DisplayRules::SPECIAL_BLOG),
                'text' => __('Blog Page', POPUPS_NEKUDA_TEXT_DOMAIN),
            ];
        }

        if (!empty($children)) {
            $groups[] = ['text' => __('Special', POPUPS_NEKUDA_TEXT_DOMAIN), 'children' => $children];
        }
    }

    /**
     * Add post types group to search results
     */
    private function addPostTypesGroup(array &$groups, string $search): void {
        $postTypes = get_post_types(['public' => true], 'objects');
        $children = [];

        foreach ($postTypes as $postType) {
            if ($postType->name === 'attachment') {
                continue;
            }

            $typeName = $postType->labels->name;
            if ($this->matchesSearch($search, [$typeName, 'all'])) {
                $children[] = [
                    'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_POST_TYPE, $postType->name),
                    'text' => sprintf(__('All %s', POPUPS_NEKUDA_TEXT_DOMAIN), $typeName),
                ];
            }
        }

        if (!empty($children)) {
            $groups[] = ['text' => __('Post Types', POPUPS_NEKUDA_TEXT_DOMAIN), 'children' => $children];
        }
    }

    /**
     * Add specific posts groups to search results
     */
    private function addSpecificPostsGroups(array &$groups, string $search): void {
        if (empty($search)) {
            return;
        }

        $postTypes = get_post_types(['public' => true], 'objects');
        $posts = get_posts([
            'post_type'      => array_keys($postTypes),
            'post_status'    => 'publish',
            's'              => $search,
            'posts_per_page' => 30,
            'orderby'        => 'relevance',
        ]);

        $postsByType = [];
        foreach ($posts as $post) {
            if ($post->post_type === 'attachment') {
                continue;
            }
            $postsByType[$post->post_type][] = [
                'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_POST, $post->ID),
                'text' => $post->post_title,
            ];
        }

        foreach ($postsByType as $type => $items) {
            $typeObj = get_post_type_object($type);
            $label = $typeObj ? $typeObj->labels->name : ucfirst($type);
            $groups[] = ['text' => $label, 'children' => $items];
        }
    }

    /**
     * Add taxonomy terms groups to search results
     */
    private function addTaxonomyTermsGroups(array &$groups, string $search): void {
        $taxonomies = get_taxonomies(['public' => true], 'objects');

        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy'   => $taxonomy->name,
                'search'     => $search,
                'number'     => 20,
                'hide_empty' => false,
            ]);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $children = array_map(fn($term) => [
                'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_TERM, $taxonomy->name, $term->term_id),
                'text' => $term->name,
            ], $terms);

            if (!empty($children)) {
                $groups[] = ['text' => $taxonomy->labels->name, 'children' => $children];
            }
        }
    }

    /**
     * Check if search term matches any of the given keywords
     */
    private function matchesSearch(string $search, array $keywords): bool {
        if (empty($search)) {
            return true;
        }

        foreach ($keywords as $keyword) {
            if (stripos($keyword, $search) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save meta fields
     */
    public function saveMeta(int $post_id): void {
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
        $include = $this->sanitizeDisplayRules($_POST['_popup_include'] ?? []);
        Fields::save($post_id, '_popup_include', $include);

        $exclude = $this->sanitizeDisplayRules($_POST['_popup_exclude'] ?? []);
        Fields::save($post_id, '_popup_exclude', $exclude);

        // Desktop slides
        $raw_desktop = isset($_POST['_popup_slides_desktop']) ? $_POST['_popup_slides_desktop'] : [];
        $slides_desktop = $this->sanitizeSlides($raw_desktop);
        Fields::save($post_id, '_popup_slides_desktop', $slides_desktop);

        // Mobile slides
        $raw_mobile = isset($_POST['_popup_slides_mobile']) ? $_POST['_popup_slides_mobile'] : [];
        $slides_mobile = $this->sanitizeSlides($raw_mobile);
        Fields::save($post_id, '_popup_slides_mobile', $slides_mobile);
    }

    /**
     * Sanitize slides array
     */
    private function sanitizeSlides($slides): array {
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
     * @return string[] Sanitized array of rule strings
     */
    private function sanitizeDisplayRules($rules): array {
        if (!is_array($rules)) {
            return [];
        }

        $sanitized = [];
        $valid_prefixes = [
            DisplayRules::PREFIX_SPECIAL . ':',
            DisplayRules::PREFIX_POST . ':',
            DisplayRules::PREFIX_POST_TYPE . ':',
            DisplayRules::PREFIX_TERM . ':',
        ];

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
    public function enqueueAdminAssets(string $hook): void {
        global $post_type;

        if ($post_type !== 'nekuda_popup') {
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

        // Inline script for trigger type toggle
        wp_add_inline_script('jquery', $this->getTriggerToggleScript());

        // Inline script for Select2 initialization
        wp_add_inline_script('select2', $this->getSelect2InitScript(), 'after');
    }

    /**
     * Get inline script for trigger type visibility toggle
     */
    private function getTriggerToggleScript(): string {
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
     *
     * Results are grouped (ACF-style) with optgroups for better organization
     */
    private function getSelect2InitScript(): string {
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
                minimumInputLength: 0
            });
        });
    });
})(jQuery);
JS;
    }
}