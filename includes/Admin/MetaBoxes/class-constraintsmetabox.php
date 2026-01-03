<?php
/**
 * Display Constraints Meta Box
 */

namespace PopupsNekuda\Admin\MetaBoxes;

use PopupsNekuda\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class ConstraintsMetaBox {

    public const ID = 'popup_display_constraints';

    public static function register(): void {
        add_meta_box(
            self::ID,
            __('Display Constraints', POPUPS_NEKUDA_TEXT_DOMAIN),
            [self::class, 'render'],
            'nekuda_popup',
            'side',
            'default'
        );
    }

    public static function render(\WP_Post $post): void {
        Fields::text($post->ID, '_popup_max_width', [
            'label'   => __('Max Width (px)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'default' => '600',
            'attrs'   => ['min' => '200', 'step' => '10'],
        ]);

        Fields::text($post->ID, '_popup_max_height', [
            'label'   => __('Max Height (px or empty for auto)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'attrs'   => ['min' => '100', 'step' => '10', 'placeholder' => 'auto'],
        ]);
    }
}

