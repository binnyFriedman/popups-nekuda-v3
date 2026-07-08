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

        Fields::urlRules($post->ID, '_popup_url_include', [
            'label'       => __('URL Include', POPUPS_NEKUDA_TEXT_DOMAIN),
            'description' => __('Show only when the full URL matches one of these rules', POPUPS_NEKUDA_TEXT_DOMAIN),
        ]);

        Fields::urlRules($post->ID, '_popup_url_exclude', [
            'label'       => __('URL Exclude', POPUPS_NEKUDA_TEXT_DOMAIN),
            'description' => __('Hide when the full URL matches one of these rules', POPUPS_NEKUDA_TEXT_DOMAIN),
        ]);

        self::renderUrlTester();
    }

    private static function renderUrlTester(): void {
        ?>
        <div class="popup-url-tester">
            <h4><?php esc_html_e('URL Rule Tester', POPUPS_NEKUDA_TEXT_DOMAIN); ?></h4>
            <p class="description">
                <?php esc_html_e('Test a full URL against the URL include/exclude rules above. Content rules are not evaluated here.', POPUPS_NEKUDA_TEXT_DOMAIN); ?>
            </p>
            <div class="popup-url-tester-input">
                <input type="text" id="popup-url-tester-url" class="regular-text" placeholder="https://example.com/page?query=1">
                <button type="button" class="button" id="popup-url-tester-run">
                    <?php esc_html_e('Test', POPUPS_NEKUDA_TEXT_DOMAIN); ?>
                </button>
            </div>
            <div id="popup-url-tester-result" class="popup-url-tester-result" aria-live="polite"></div>
        </div>
        <?php
    }
}

