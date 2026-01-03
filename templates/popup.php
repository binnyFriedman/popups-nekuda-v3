<?php
/**
 * Popup template
 *
 * @var array $data Popup data from get_popup_data()
 */

if (!defined('ABSPATH')) {
    exit;
}

$max_height_style = !empty($data['max_height']) ? $data['max_height'] . 'px' : 'auto';
?>
<div class="popup"
     data-popup-id="<?php echo esc_attr($data['id']); ?>"
     data-trigger="<?php echo esc_attr($data['trigger_type']); ?>"
     data-timeout="<?php echo esc_attr($data['trigger_timeout']); ?>"
     data-cookie-key="<?php echo esc_attr($data['cookie_key']); ?>"
     data-cookie-expiry="<?php echo esc_attr($data['cookie_expiry']); ?>"
     style="--popup-max-width: <?php echo esc_attr($data['max_width']); ?>px; --popup-max-height: <?php echo esc_attr($max_height_style); ?>;">

    <div class="popup__overlay"></div>

    <div class="popup__container">
        <button class="popup__close" aria-label="<?php esc_attr_e('Close', 'popups-nekuda'); ?>">&times;</button>

        <?php if (!empty($data['slides_desktop'])): ?>
        <!-- Desktop Content -->
        <div class="popup__content popup__content--desktop">
            <div class="popup__slides">
                <?php foreach ($data['slides_desktop'] as $index => $slide): ?>
                <div class="popup__slide<?php echo $index === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($index); ?>">
                    <?php echo wp_kses_post($slide); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($data['slides_mobile'])): ?>
        <!-- Mobile Content -->
        <div class="popup__content popup__content--mobile">
            <div class="popup__slides">
                <?php foreach ($data['slides_mobile'] as $index => $slide): ?>
                <div class="popup__slide<?php echo $index === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($index); ?>">
                    <?php echo wp_kses_post($slide); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
