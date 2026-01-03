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
        $attrs = $args['attrs'] ?? '';

        echo '<p class="popup-field ' . esc_attr($class) . '">';
        if ($label) {
            echo '<label for="' . esc_attr($key) . '">' . esc_html($label) . '</label>';
        }
        echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" ' . $attrs . '>';
        echo '</p>';
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
    public static function select2_multi(int $post_id, string $key, array $args = []): void {
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
            $label_text = self::get_rule_label($value);
            echo '<option value="' . esc_attr($value) . '" selected="selected">' . esc_html($label_text) . '</option>';
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
    public static function get_rule_label(string $value): string {
        $parsed = DisplayRules::parse_rule($value);
        $prefix = $parsed['prefix'];
        $parts = $parsed['parts'];

        // Special pages
        if ($prefix === DisplayRules::PREFIX_SPECIAL) {
            $page = $parts[0] ?? '';
            if ($page === DisplayRules::SPECIAL_HOME) {
                return __('Homepage', POPUPS_NEKUDA_TEXT_DOMAIN);
            }
            if ($page === DisplayRules::SPECIAL_BLOG) {
                return __('Blog Page', POPUPS_NEKUDA_TEXT_DOMAIN);
            }
            return $value;
        }

        // Post type
        if ($prefix === DisplayRules::PREFIX_POST_TYPE) {
            $type = $parts[0] ?? '';
            $post_type_obj = get_post_type_object($type);
            if ($post_type_obj) {
                return sprintf(__('All %s', POPUPS_NEKUDA_TEXT_DOMAIN), $post_type_obj->labels->name);
            }
            return $value;
        }

        // Specific post
        if ($prefix === DisplayRules::PREFIX_POST) {
            $id = (int) ($parts[0] ?? 0);
            $post = get_post($id);
            if ($post) {
                $type_obj = get_post_type_object($post->post_type);
                $type_label = $type_obj ? $type_obj->labels->singular_name : $post->post_type;
                return sprintf('%s (%s)', $post->post_title, $type_label);
            }
            return sprintf(__('Post #%d', POPUPS_NEKUDA_TEXT_DOMAIN), $id);
        }

        // Taxonomy term
        if ($prefix === DisplayRules::PREFIX_TERM) {
            if (count($parts) === 2) {
                $taxonomy = $parts[0];
                $term_id = (int) $parts[1];
                $term = get_term($term_id, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $tax_obj = get_taxonomy($taxonomy);
                    $tax_label = $tax_obj ? $tax_obj->labels->singular_name : $taxonomy;
                    return sprintf('%s (%s)', $term->name, $tax_label);
                }
            }
            return $value;
        }

        return $value;
    }
}
