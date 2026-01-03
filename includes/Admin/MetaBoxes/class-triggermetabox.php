<?php
/**
 * Trigger Settings Meta Box
 */

namespace PopupsNekuda\Admin\MetaBoxes;

use PopupsNekuda\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class TriggerMetaBox {

    public const ID = 'popup_trigger_settings';

    public static function register(): void {
        add_meta_box(
            self::ID,
            __('Trigger Settings', POPUPS_NEKUDA_TEXT_DOMAIN),
            [self::class, 'render'],
            'nekuda_popup',
            'normal',
            'high'
        );
    }

    public static function render(\WP_Post $post): void {
        wp_nonce_field('popup_save_meta', 'popup_meta_nonce');

        Fields::radio($post->ID, '_popup_trigger_type', [
            'exit_intent' => __('Exit Intent', POPUPS_NEKUDA_TEXT_DOMAIN),
            'timeout'     => __('Timeout', POPUPS_NEKUDA_TEXT_DOMAIN),
        ], [
            'label'   => __('Trigger Type', POPUPS_NEKUDA_TEXT_DOMAIN),
            'default' => 'timeout',
        ]);

        Fields::text($post->ID, '_popup_trigger_timeout', [
            'label'   => __('Timeout (seconds)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'default' => '3',
            'class'   => 'popup-field--timeout',
            'attrs'   => ['min' => '1', 'step' => '1'],
        ]);
    }
}

