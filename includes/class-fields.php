<?php
/**
 * Lightweight meta field helper
 */

namespace PopupsNekuda;

if (!defined('ABSPATH')) {
    exit;
}

class Fields {

    /**
     * Get meta value with default fallback
     */
    public static function get(int $post_id, string $key, $default = '') {
        $value = get_post_meta($post_id, $key, true);
        return $value !== '' ? $value : $default;
    }

    /**
     * Save meta value
     */
    public static function save(int $post_id, string $key, $value): void {
        if ($value === '' || $value === null) {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }

    /**
     * Render a text input field
     */
    public static function text(int $post_id, string $key, array $args = []): void {
        $value = self::get($post_id, $key, $args['default'] ?? '');
        $type = $args['type'] ?? 'text';
        $label = $args['label'] ?? '';
        $class = $args['class'] ?? '';
        $attrs = self::buildAttributes($args['attrs'] ?? []);

        echo '<p class="popup-field ' . esc_attr($class) . '">';
        if ($label) {
            echo '<label for="' . esc_attr($key) . '">' . esc_html($label) . '</label>';
        }
        echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" ' . $attrs . '>';
        echo '</p>';
    }

    /**
     * Build HTML attributes string from array
     * 
     * @param array|string $attrs Attributes as array or legacy string format
     * @return string Escaped HTML attributes string
     */
    private static function buildAttributes($attrs): string {
        // Support legacy string format during transition (to be removed in 4.0)
        if (is_string($attrs)) {
            return $attrs;
        }

        if (!is_array($attrs) || empty($attrs)) {
            return '';
        }

        $output = [];
        foreach ($attrs as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        return implode(' ', $output);
    }

    /**
     * Render a radio group
     */
    public static function radio(int $post_id, string $key, array $options, array $args = []): void {
        $value = self::get($post_id, $key, $args['default'] ?? '');
        $label = $args['label'] ?? '';

        echo '<fieldset class="popup-field popup-field--radio">';
        if ($label) {
            echo '<legend>' . esc_html($label) . '</legend>';
        }
        foreach ($options as $option_value => $option_label) {
            $checked = checked($value, $option_value, false);
            echo '<label>';
            echo '<input type="radio" name="' . esc_attr($key) . '" value="' . esc_attr($option_value) . '" ' . $checked . '>';
            echo ' ' . esc_html($option_label);
            echo '</label>';
        }
        echo '</fieldset>';
    }

    /**
     * Render a Select2 multi-select field with AJAX search
     *
     * @param int    $post_id Post ID
     * @param string $key     Meta key
     * @param array  $args    Field arguments
     */
    public static function select2Multi(int $post_id, string $key, array $args = []): void {
        $values = self::get($post_id, $key, []);
        if (!is_array($values)) {
            $values = [];
        }

        $label = $args['label'] ?? '';
        $description = $args['description'] ?? '';
        $placeholder = $args['placeholder'] ?? __('Search...', POPUPS_NEKUDA_TEXT_DOMAIN);

        echo '<div class="popup-field popup-field--select2">';
        
        if ($label) {
            echo '<label for="' . esc_attr($key) . '">' . esc_html($label) . '</label>';
        }

        echo '<select id="' . esc_attr($key) . '" name="' . esc_attr($key) . '[]" class="popup-select2" multiple="multiple" data-placeholder="' . esc_attr($placeholder) . '" style="width: 100%;">';

        // Render pre-selected options
        foreach ($values as $value) {
            $labelText = self::getRuleLabel($value);
            echo '<option value="' . esc_attr($value) . '" selected="selected">' . esc_html($labelText) . '</option>';
        }

        echo '</select>';

        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Get human-readable label for a rule value
     *
     * @param string $value Rule value (e.g., 'post:42', 'term:category:5')
     * @return string Human-readable label
     */
    public static function getRuleLabel(string $value): string {
        $parsed = DisplayRules::parse_rule($value);
        $prefix = $parsed['prefix'];
        $parts = $parsed['parts'];

        $labelResolvers = [
            DisplayRules::PREFIX_SPECIAL => fn() => self::getSpecialPageLabel($parts[0] ?? '', $value),
            DisplayRules::PREFIX_POST_TYPE => fn() => self::getPostTypeLabel($parts[0] ?? '', $value),
            DisplayRules::PREFIX_POST => fn() => self::getPostLabel((int)($parts[0] ?? 0)),
            DisplayRules::PREFIX_TERM => fn() => self::getTermLabel($parts, $value),
        ];

        $resolver = $labelResolvers[$prefix] ?? null;

        return $resolver ? $resolver() : $value;
    }

    /**
     * Get label for special page rule
     */
    private static function getSpecialPageLabel(string $page, string $fallback): string {
        $labels = [
            DisplayRules::SPECIAL_HOME => __('Homepage', POPUPS_NEKUDA_TEXT_DOMAIN),
            DisplayRules::SPECIAL_BLOG => __('Blog Page', POPUPS_NEKUDA_TEXT_DOMAIN),
        ];

        return $labels[$page] ?? $fallback;
    }

    /**
     * Get label for post type rule
     */
    private static function getPostTypeLabel(string $type, string $fallback): string {
        $postTypeObj = get_post_type_object($type);

        return $postTypeObj
            ? sprintf(__('All %s', POPUPS_NEKUDA_TEXT_DOMAIN), $postTypeObj->labels->name)
            : $fallback;
    }

    /**
     * Get label for specific post rule
     */
    private static function getPostLabel(int $id): string {
        $post = get_post($id);

        if (!$post) {
            return sprintf(__('Post #%d', POPUPS_NEKUDA_TEXT_DOMAIN), $id);
        }

        $typeObj = get_post_type_object($post->post_type);
        $typeLabel = $typeObj ? $typeObj->labels->singular_name : $post->post_type;

        return sprintf('%s (%s)', $post->post_title, $typeLabel);
    }

    /**
     * Get label for taxonomy term rule
     */
    private static function getTermLabel(array $parts, string $fallback): string {
        if (count($parts) !== 2) {
            return $fallback;
        }

        $taxonomy = $parts[0];
        $termId = (int)$parts[1];
        $term = get_term($termId, $taxonomy);

        if (!$term || is_wp_error($term)) {
            return $fallback;
        }

        $taxObj = get_taxonomy($taxonomy);
        $taxLabel = $taxObj ? $taxObj->labels->singular_name : $taxonomy;

        return sprintf('%s (%s)', $term->name, $taxLabel);
    }
}

