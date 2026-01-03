<?php
/**
 * Cookie & Scheduling Meta Box
 */

namespace PopupsNekuda\Admin\MetaBoxes;

use PopupsNekuda\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class CookieMetaBox {

    public const ID = 'popup_cookie_scheduling';

    public static function register(): void {
        add_meta_box(
            self::ID,
            __('Cookie & Scheduling', POPUPS_NEKUDA_TEXT_DOMAIN),
            [self::class, 'render'],
            'nekuda_popup',
            'normal',
            'high'
        );
    }

    public static function render(\WP_Post $post): void {
        $cookie_key = Fields::get($post->ID, '_popup_cookie_key', '');
        if (empty($cookie_key) && $post->post_name) {
            $cookie_key = $post->post_name;
        }

        Fields::text($post->ID, '_popup_cookie_key', [
            'label'   => __('Cookie Key', POPUPS_NEKUDA_TEXT_DOMAIN),
            'default' => $cookie_key,
            'attrs'   => ['placeholder' => 'auto-generated-from-slug'],
        ]);

        Fields::text($post->ID, '_popup_cookie_expiry', [
            'label'   => __('Cookie Expiry (days)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'    => 'number',
            'default' => '30',
            'attrs'   => ['min' => '1', 'step' => '1'],
        ]);

        Fields::text($post->ID, '_popup_schedule_start', [
            'label' => __('Schedule Start Date (optional)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'  => 'date',
        ]);

        Fields::text($post->ID, '_popup_schedule_end', [
            'label' => __('Schedule End Date (optional)', POPUPS_NEKUDA_TEXT_DOMAIN),
            'type'  => 'date',
        ]);
    }
}

