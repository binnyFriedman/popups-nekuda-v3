<?php
/**
 * Display Constraints Meta Box
 */

namespace PopupsNekuda\Admin\MetaBoxes;

use PopupsNekuda\DisplayConstraints;
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
            'label'   => __('Max Width (vw)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'default' => (string) DisplayConstraints::MAX_WIDTH_VW_DEFAULT,
            'attrs'   => [
                'min'  => (string) DisplayConstraints::MIN_PERCENT,
                'max'  => (string) DisplayConstraints::MAX_PERCENT,
                'step' => '1',
            ],
        ]);

        Fields::text($post->ID, '_popup_max_height', [
            'label'   => __('Max Height (vh)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'default' => (string) DisplayConstraints::MAX_HEIGHT_VH_DEFAULT,
            'attrs'   => [
                'min'  => (string) DisplayConstraints::MIN_PERCENT,
                'max'  => (string) DisplayConstraints::MAX_PERCENT,
                'step' => '1',
            ],
        ]);
    }
}

