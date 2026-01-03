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
$popup_title_id = 'popup-title-' . esc_attr($data['id']);
$has_multiple_slides_desktop = !empty($data['slides_desktop']) && count($data['slides_desktop']) > 1;
$has_multiple_slides_mobile = !empty($data['slides_mobile']) && count($data['slides_mobile']) > 1;
?>
<div class="popup"
     data-popup-id="<?php echo esc_attr($data['id']); ?>"
     data-trigger="<?php echo esc_attr($data['trigger_type']); ?>"
     data-timeout="<?php echo esc_attr($data['trigger_timeout']); ?>"
     data-cookie-key="<?php echo esc_attr($data['cookie_key']); ?>"
     data-cookie-expiry="<?php echo esc_attr($data['cookie_expiry']); ?>"
     style="--popup-max-width: <?php echo esc_attr($data['max_width']); ?>px; --popup-max-height: <?php echo esc_attr($max_height_style); ?>;">

    <div class="popup__overlay"></div>

    <div class="popup__container"
         role="dialog"
         aria-modal="true"
         aria-labelledby="<?php echo esc_attr($popup_title_id); ?>">
        
        <h2 id="<?php echo esc_attr($popup_title_id); ?>" class="sr-only">
            <?php echo esc_html(get_the_title($data['id'])); ?>
        </h2>
        
        <button class="popup__close liquid-glass" aria-label="<?php esc_attr_e('Close popup', 'popups-nekuda'); ?>">
            <span aria-hidden="true">&times;</span>
        </button>

        <?php if ($has_multiple_slides_desktop): ?>
        <button class="popup__pause popup__pause--desktop liquid-glass" aria-label="<?php esc_attr_e('Pause slideshow', 'popups-nekuda'); ?>" aria-pressed="false">
            <span class="popup__pause-icon popup__pause-icon--pause" aria-hidden="true">⏸</span>
            <span class="popup__pause-icon popup__pause-icon--play" aria-hidden="true">▶</span>
        </button>
        <?php endif; ?>

        <?php if ($has_multiple_slides_mobile): ?>
        <button class="popup__pause popup__pause--mobile liquid-glass" aria-label="<?php esc_attr_e('Pause slideshow', 'popups-nekuda'); ?>" aria-pressed="false">
            <span class="popup__pause-icon popup__pause-icon--pause" aria-hidden="true">⏸</span>
            <span class="popup__pause-icon popup__pause-icon--play" aria-hidden="true">▶</span>
        </button>
        <?php endif; ?>

        <?php if (!empty($data['slides_desktop'])): ?>
        <!-- Desktop Content -->
        <div class="popup__content popup__content--desktop">
            <div class="popup__slides" aria-live="polite" aria-atomic="true">
                <?php foreach ($data['slides_desktop'] as $index => $slide): ?>
                <div class="popup__slide<?php echo $index === 0 ? ' is-active' : ''; ?>" 
                     data-index="<?php echo esc_attr($index); ?>"
                     role="group"
                     aria-roledescription="<?php esc_attr_e('slide', 'popups-nekuda'); ?>"
                     aria-label="<?php echo esc_attr(sprintf(__('Slide %1$d of %2$d', 'popups-nekuda'), $index + 1, count($data['slides_desktop']))); ?>">
                    <?php echo wp_kses_post($slide); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($data['slides_mobile'])): ?>
        <!-- Mobile Content -->
        <div class="popup__content popup__content--mobile">
            <div class="popup__slides" aria-live="polite" aria-atomic="true">
                <?php foreach ($data['slides_mobile'] as $index => $slide): ?>
                <div class="popup__slide<?php echo $index === 0 ? ' is-active' : ''; ?>" 
                     data-index="<?php echo esc_attr($index); ?>"
                     role="group"
                     aria-roledescription="<?php esc_attr_e('slide', 'popups-nekuda'); ?>"
                     aria-label="<?php echo esc_attr(sprintf(__('Slide %1$d of %2$d', 'popups-nekuda'), $index + 1, count($data['slides_mobile']))); ?>">
                    <?php echo wp_kses_post($slide); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
