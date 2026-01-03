<?php
/**
 * Popup Content Meta Box (Desktop/Mobile tabs with slides)
 */

namespace PopupsNekuda\Admin\MetaBoxes;

use PopupsNekuda\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class ContentMetaBox {

    public const ID = 'popup_content';

    public static function register(): void {
        add_meta_box(
            self::ID,
            __('Popup Content', POPUPS_NEKUDA_TEXT_DOMAIN),
            [self::class, 'render'],
            'nekuda_popup',
            'normal',
            'default'
        );
    }

    public static function render(\WP_Post $post): void {
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
            <?php self::renderSlidesRepeater($post->ID, '_popup_slides_desktop', 'desktop'); ?>
        </div>

        <div class="popup-tab-panel" data-panel="mobile">
            <div class="popup-mobile-notice <?php echo $has_mobile_content ? 'is-hidden' : ''; ?>">
                <span class="dashicons dashicons-info"></span>
                <p><?php _e('Currently using desktop slides on mobile. Add slides below to customize the mobile experience.', POPUPS_NEKUDA_TEXT_DOMAIN); ?>
                </p>
            </div>
            <?php self::renderSlidesRepeater($post->ID, '_popup_slides_mobile', 'mobile'); ?>
        </div>
    </div>
</div>
<?php
    }

    /**
     * Render slides repeater UI
     */
    private static function renderSlidesRepeater(int $post_id, string $key, string $type): void {
        $slides = Fields::get($post_id, $key, []);
        if (!is_array($slides)) {
            $slides = [];
        }

        echo '<div class="popup-slides-repeater" data-type="' . esc_attr($type) . '">';
        echo '<div class="popup-slides-list">';

        if (empty($slides)) {
            self::renderSingleSlide($key, 0, '');
        } else {
            foreach ($slides as $index => $content) {
                self::renderSingleSlide($key, $index, $content);
            }
        }

        echo '</div>';
        echo '<button type="button" class="button popup-add-slide">' . __('Add Slide', POPUPS_NEKUDA_TEXT_DOMAIN) . '</button>';
        echo '</div>';
    }

    /**
     * Render a single slide with wp_editor
     */
    public static function renderSingleSlide(string $key, int $index, string $content): void {
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
}

