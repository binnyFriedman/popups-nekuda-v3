<?php
/**
 * Popup template
 *
 * @var array $data Popup data from get_popup_data()
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render slides for a given viewport variant
 *
 * @param array  $slides  Array of slide content
 * @param string $variant 'desktop' or 'mobile'
 */
function popups_nekuda_render_slides(array $slides, string $variant): void {
    if (empty($slides)) {
        return;
    }
    ?>
<div class="popup__content popup__content--<?php echo esc_attr($variant); ?>">
    <div class="popup__slides" aria-live="polite" aria-atomic="true">
        <?php foreach ($slides as $index => $slide): ?>
        <div class="popup__slide<?php echo $index === 0 ? ' is-active' : ''; ?>"
            data-index="<?php echo esc_attr($index); ?>"
            aria-roledescription="<?php esc_attr_e('slide', POPUPS_NEKUDA_TEXT_DOMAIN); ?>"
            aria-label="<?php echo esc_attr(sprintf(__('Slide %1$d of %2$d', POPUPS_NEKUDA_TEXT_DOMAIN), $index + 1, count($slides))); ?>">
            <?php echo wp_kses_post($slide); ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
}

$popup_title_id = 'popup-title-' . esc_attr($data['id']);
$has_multiple_slides_desktop = !empty($data['slides_desktop']) && count($data['slides_desktop']) > 1;
$has_multiple_slides_mobile = !empty($data['slides_mobile']) && count($data['slides_mobile']) > 1;
?>
<div class="popup" data-popup-id="<?php echo esc_attr($data['id']); ?>"
    data-trigger="<?php echo esc_attr($data['trigger_type']); ?>"
    data-timeout="<?php echo esc_attr($data['trigger_timeout']); ?>"
    data-cookie-key="<?php echo esc_attr($data['cookie_key']); ?>"
    data-cookie-expiry="<?php echo esc_attr($data['cookie_expiry']); ?>"
    style="--popup-max-width: <?php echo esc_attr($data['max_width']); ?>vw; --popup-max-width-mobile: <?php echo esc_attr($data['max_width_mobile']); ?>vw; --popup-max-height: <?php echo esc_attr($data['max_height']); ?>vh;">

    <div class="popup__overlay"></div>

    <div class="popup__container" role="dialog" aria-modal="true"
        aria-labelledby="<?php echo esc_attr($popup_title_id); ?>">

        <h2 id="<?php echo esc_attr($popup_title_id); ?>" class="sr-only">
            <?php echo esc_html(get_the_title($data['id'])); ?>
        </h2>

        <button class="popup__close liquid-glass"
            aria-label="<?php esc_attr_e('Close popup', POPUPS_NEKUDA_TEXT_DOMAIN); ?>">
            <span aria-hidden="true">&times;</span>
        </button>

        <?php if ($has_multiple_slides_desktop): ?>
        <button class="popup__pause popup__pause--desktop liquid-glass"
            aria-label="<?php esc_attr_e('Pause slideshow', POPUPS_NEKUDA_TEXT_DOMAIN); ?>" aria-pressed="false">
            <span class="popup__pause-icon popup__pause-icon--pause" aria-hidden="true">⏸</span>
            <span class="popup__pause-icon popup__pause-icon--play" aria-hidden="true">▶</span>
        </button>
        <?php endif; ?>

        <?php if ($has_multiple_slides_mobile): ?>
        <button class="popup__pause popup__pause--mobile liquid-glass"
            aria-label="<?php esc_attr_e('Pause slideshow', POPUPS_NEKUDA_TEXT_DOMAIN); ?>" aria-pressed="false">
            <span class="popup__pause-icon popup__pause-icon--pause" aria-hidden="true">⏸</span>
            <span class="popup__pause-icon popup__pause-icon--play" aria-hidden="true">▶</span>
        </button>
        <?php endif; ?>

        <?php
        // Render desktop and mobile slides using helper
        popups_nekuda_render_slides($data['slides_desktop'], 'desktop');
        popups_nekuda_render_slides($data['slides_mobile'], 'mobile');
        ?>

    </div>
</div>