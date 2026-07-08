<?php
/**
 * Handles saving all popup meta fields
 */

namespace PopupsNekuda\Admin;

use PopupsNekuda\Fields;
use PopupsNekuda\DisplayRules;

if (!defined('ABSPATH')) {
    exit;
}

class MetaSaver {

    public static function register(): void {
        add_action('save_post_nekuda_popup', [self::class, 'save']);
    }

    public static function save(int $post_id): void {
        if (!self::canSave($post_id)) {
            return;
        }

        self::saveTriggerSettings($post_id);
        self::saveCookieScheduling($post_id);
        self::saveDisplayConstraints($post_id);
        self::saveDisplayRules($post_id);
        self::saveSlides($post_id);
    }

    private static function canSave(int $post_id): bool {
        if (!isset($_POST['popup_meta_nonce'])) {
            return false;
        }

        if (!wp_verify_nonce($_POST['popup_meta_nonce'], 'popup_save_meta')) {
            return false;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        return current_user_can('edit_post', $post_id);
    }

    private static function saveTriggerSettings(int $post_id): void {
        $trigger_type = sanitize_text_field($_POST['_popup_trigger_type'] ?? 'timeout');
        if (!in_array($trigger_type, ['exit_intent', 'timeout'])) {
            $trigger_type = 'timeout';
        }
        Fields::save($post_id, '_popup_trigger_type', $trigger_type);

        $timeout = absint($_POST['_popup_trigger_timeout'] ?? 3);
        Fields::save($post_id, '_popup_trigger_timeout', $timeout);
    }

    private static function saveCookieScheduling(int $post_id): void {
        Fields::save($post_id, '_popup_cookie_key', sanitize_key($_POST['_popup_cookie_key'] ?? ''));
        Fields::save($post_id, '_popup_cookie_expiry', absint($_POST['_popup_cookie_expiry'] ?? 30));
        Fields::save($post_id, '_popup_schedule_start', sanitize_text_field($_POST['_popup_schedule_start'] ?? ''));
        Fields::save($post_id, '_popup_schedule_end', sanitize_text_field($_POST['_popup_schedule_end'] ?? ''));
    }

    private static function saveDisplayConstraints(int $post_id): void {
        $max_width = absint($_POST['_popup_max_width'] ?? 600);
        Fields::save($post_id, '_popup_max_width', $max_width ?: 600);

        $max_height = $_POST['_popup_max_height'] ?? '';
        Fields::save($post_id, '_popup_max_height', $max_height ? absint($max_height) : '');
    }

    private static function saveDisplayRules(int $post_id): void {
        $include = self::sanitizeRules($_POST['_popup_include'] ?? []);
        Fields::save($post_id, '_popup_include', $include);

        $exclude = self::sanitizeRules($_POST['_popup_exclude'] ?? []);
        Fields::save($post_id, '_popup_exclude', $exclude);

        $url_include = self::sanitizeUrlRules($_POST['_popup_url_include'] ?? []);
        Fields::save($post_id, '_popup_url_include', $url_include);

        $url_exclude = self::sanitizeUrlRules($_POST['_popup_url_exclude'] ?? []);
        Fields::save($post_id, '_popup_url_exclude', $url_exclude);
    }

    private static function saveSlides(int $post_id): void {
        $slides_desktop = self::sanitizeSlides($_POST['_popup_slides_desktop'] ?? []);
        Fields::save($post_id, '_popup_slides_desktop', $slides_desktop);

        $slides_mobile = self::sanitizeSlides($_POST['_popup_slides_mobile'] ?? []);
        Fields::save($post_id, '_popup_slides_mobile', $slides_mobile);
    }

    /**
     * Sanitize slides array - removes non-string values, empty strings, and whitespace-only slides
     * Made public for unit testing
     *
     * @param mixed $slides Input slides array
     * @return array Sanitized and re-indexed slides array
     */
    public static function sanitizeSlides($slides): array {
        if (!is_array($slides)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($slides as $content) {
            if (!is_string($content)) {
                continue;
            }
            $clean = wp_kses_post($content);
            if (!empty(trim($clean))) {
                $sanitized[] = $clean;
            }
        }
        
        return array_values($sanitized);
    }

    /**
     * Sanitize display rules array
     * Made public for unit testing
     *
     * Valid formats:
     * - special:home
     * - special:blog
     * - post:123
     * - post_type:car
     * - term:category:5
     *
     * @param mixed $rules Input rules array
     * @return array Sanitized rules with duplicates removed
     */
    public static function sanitizeRules($rules): array {
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

            foreach ($valid_prefixes as $prefix) {
                if (str_starts_with($rule, $prefix)) {
                    $sanitized[] = $rule;
                    break;
                }
            }
        }

        return array_unique($sanitized);
    }

    /**
     * Sanitize URL rules array
     * Made public for unit testing
     *
     * Valid format: [{type: starts_with|ends_with|exact|contains, value: string}, ...]
     *
     * @param mixed $rules Input rules array
     * @return array Sanitized and re-indexed rules array
     */
    public static function sanitizeUrlRules($rules): array {
        if (!is_array($rules)) {
            return [];
        }

        $valid_types = DisplayRules::URL_MATCH_TYPES;
        $sanitized = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $type = sanitize_text_field($rule['type'] ?? '');
            $value = sanitize_text_field($rule['value'] ?? '');

            if (!in_array($type, $valid_types, true) || $value === '') {
                continue;
            }

            $sanitized[] = [
                'type'  => $type,
                'value' => $value,
            ];
        }

        return array_values($sanitized);
    }
}
