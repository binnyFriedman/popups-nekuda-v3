<?php
/**
 * Lightweight meta field helper
 */

if (!defined('ABSPATH')) {
    exit;
}

class Popup_Fields {

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
}
