<?php
/**
 * Display Rules Meta Box (Include/Exclude)
 */

namespace PopupsNekuda\Admin\MetaBoxes;

use PopupsNekuda\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class RulesMetaBox {

    public const ID = 'popup_display_rules';

    public static function register(): void {
        add_meta_box(
            self::ID,
            __('Display Rules', POPUPS_NEKUDA_TEXT_DOMAIN),
            [self::class, 'render'],
            'nekuda_popup',
            'side',
            'default'
        );
    }

    public static function render(\WP_Post $post): void {
        Fields::select2Multi($post->ID, '_popup_include', [
            'label'       => __('Include', POPUPS_NEKUDA_TEXT_DOMAIN),
            'description' => __('Leave empty to show on all pages', POPUPS_NEKUDA_TEXT_DOMAIN),
            'placeholder' => __('Search pages, posts, categories...', POPUPS_NEKUDA_TEXT_DOMAIN),
        ]);

        Fields::select2Multi($post->ID, '_popup_exclude', [
            'label'       => __('Exclude', POPUPS_NEKUDA_TEXT_DOMAIN),
            'description' => __('Hide popup on these pages', POPUPS_NEKUDA_TEXT_DOMAIN),
            'placeholder' => __('Search pages, posts, categories...', POPUPS_NEKUDA_TEXT_DOMAIN),
        ]);
    }
}

